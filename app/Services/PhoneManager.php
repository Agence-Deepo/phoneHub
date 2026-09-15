<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Phone;
use App\Models\PhoneApp;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PhoneManager
{
    public function __construct(
        protected GeeLarkApiService $api,
    ) {}

    public function create(array $data): Phone
    {
        $payload = [
            'mobileType' => $data['mobile_type'] ?? 'Android 13',
            'chargeMode' => (int) ($data['charge_mode'] ?? 0),
            'data' => [[
                'profileName' => $data['name'],
                'proxyInformation' => $data['proxy'] ?: null,
                'profileGroup' => $data['group_name'] ?: null,
                'profileTags' => $this->parseTags($data['tags'] ?? null),
                'profileNote' => $data['remark'] ?? null,
                'mobileLanguage' => 'default',
            ]],
        ];

        if (! empty($data['region'])) {
            $payload['region'] = $data['region'];
        }

        $response = $this->api->createPhone($payload);
        $detail = $response['data']['details'][0] ?? null;

        if (! $detail || ($detail['code'] ?? 1) !== 0) {
            throw new RuntimeException($detail['msg'] ?? 'Échec de la création du cloud phone');
        }

        return Phone::create([
            'geelark_id' => (string) $detail['id'],
            'name' => $detail['profileName'] ?? $data['name'],
            'serial_no' => $detail['envSerialNo'] ?? null,
            'status' => 'offline',
            'country' => $detail['equipmentInfo']['countryName'] ?? ($data['country'] ?? null),
            'group_name' => $data['group_name'] ?? null,
            'tags' => $this->parseTags($data['tags'] ?? null),
            'proxy' => $data['proxy'] ?? null,
            'mobile_type' => $data['mobile_type'] ?? 'Android 13',
            'remark' => $data['remark'] ?? null,
            'equipment_info' => $detail['equipmentInfo'] ?? null,
            'last_synced_at' => now(),
        ]);
    }

    public function start(Phone $phone): Phone
    {
        $this->ensureGeelarkId($phone);
        $response = $this->api->startPhones([$phone->geelark_id]);
        $success = collect($response['data']['successDetails'] ?? [])->firstWhere('id', $phone->geelark_id);

        if (! $success && ($response['data']['failAmount'] ?? 0) > 0) {
            $fail = collect($response['data']['failDetails'] ?? [])->first();
            throw new RuntimeException($fail['msg'] ?? 'Impossible de démarrer le téléphone');
        }

        $phone->update([
            'status' => 'online',
            'remote_url' => $success['url'] ?? $phone->remote_url,
            'last_synced_at' => now(),
        ]);

        return $phone->fresh();
    }

    public function stop(Phone $phone): Phone
    {
        $this->ensureGeelarkId($phone);
        $response = $this->api->stopPhones([$phone->geelark_id]);

        if (($response['data']['failAmount'] ?? 0) > 0 && ($response['data']['successAmount'] ?? 0) === 0) {
            $fail = collect($response['data']['failDetails'] ?? [])->first();
            throw new RuntimeException($fail['msg'] ?? 'Impossible d’arrêter le téléphone');
        }

        $phone->update([
            'status' => 'offline',
            'remote_url' => null,
            'last_synced_at' => now(),
        ]);

        return $phone->fresh();
    }

    public function restart(Phone $phone): Phone
    {
        if ($phone->status === 'online') {
            $this->stop($phone);
        }

        return $this->start($phone->fresh());
    }

    public function delete(Phone $phone): void
    {
        if ($phone->geelark_id) {
            if ($phone->status === 'online') {
                try {
                    $this->stop($phone);
                } catch (\Throwable) {
                    // Best effort before delete
                }
            }

            $response = $this->api->deletePhones([$phone->geelark_id]);

            if (($response['data']['failAmount'] ?? 0) > 0 && ($response['data']['successAmount'] ?? 0) === 0) {
                $fail = collect($response['data']['failDetails'] ?? [])->first();
                throw new RuntimeException($fail['msg'] ?? 'Impossible de supprimer le téléphone');
            }
        }

        $phone->delete();
    }

    public function requestScreenshot(Phone $phone): Phone
    {
        $this->ensureGeelarkId($phone);

        if ($phone->status !== 'online') {
            throw new RuntimeException('Le téléphone doit être en ligne pour capturer l’écran.');
        }

        $response = $this->api->screenShot($phone->geelark_id);
        $taskId = $response['data']['taskId'] ?? null;

        if (! $taskId) {
            throw new RuntimeException('Aucun taskId reçu pour la capture d’écran.');
        }

        $phone->update(['screenshot_task_id' => $taskId]);

        return $phone->fresh();
    }

    public function fetchScreenshotResult(Phone $phone): Phone
    {
        if (! $phone->screenshot_task_id) {
            throw new RuntimeException('Aucune capture en cours.');
        }

        $response = $this->api->screenShotResult($phone->screenshot_task_id);
        $status = (int) ($response['data']['status'] ?? 0);

        if ($status === 1) {
            throw new RuntimeException('Capture encore en cours, réessayez dans quelques secondes.');
        }

        if ($status !== 2) {
            throw new RuntimeException('Échec de la capture d’écran.');
        }

        $phone->update([
            'screenshot_url' => $response['data']['downloadLink'] ?? null,
            'screenshot_task_id' => null,
        ]);

        return $phone->fresh();
    }

    public function installApp(Phone $phone, string $appName, string $appVersionId, ?string $packageName = null): PhoneApp
    {
        $this->ensureGeelarkId($phone);

        if ($phone->status !== 'online') {
            throw new RuntimeException('Le téléphone doit être en ligne pour installer une app.');
        }

        $this->api->installApp($phone->geelark_id, $appVersionId);

        return PhoneApp::create([
            'phone_id' => $phone->id,
            'app_name' => $appName,
            'package_name' => $packageName,
            'app_version_id' => $appVersionId,
            'status' => 'installed',
        ]);
    }

    public function runAutomation(Phone $phone, string $type, array $config = []): Automation
    {
        $automation = Automation::create([
            'phone_id' => $phone->id,
            'type' => $type,
            'status' => 'running',
            'config' => $config,
            'message' => 'Automatisation démarrée',
        ]);

        try {
            if ($this->api->isDemoMode()) {
                $automation->update([
                    'status' => 'success',
                    'geelark_task_id' => 'demo_task_'.$automation->id,
                    'message' => 'Exécutée en mode démo',
                ]);
            } else {
                $response = $this->api->createCustomTask(array_merge($config, [
                    'envId' => $phone->geelark_id,
                    'name' => $type,
                ]));

                $automation->update([
                    'status' => 'running',
                    'geelark_task_id' => (string) ($response['data']['taskId'] ?? $response['data']['id'] ?? ''),
                    'message' => $response['msg'] ?? 'Tâche envoyée à GeeLark',
                ]);
            }
        } catch (\Throwable $e) {
            $automation->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $automation->fresh();
    }

    public function syncFromRemote(): int
    {
        if ($this->api->isDemoMode()) {
            return 0;
        }

        $response = $this->api->listPhones();
        $items = $response['data']['items'] ?? [];
        $count = 0;

        DB::transaction(function () use ($items, &$count) {
            foreach ($items as $item) {
                Phone::updateOrCreate(
                    ['geelark_id' => (string) $item['id']],
                    [
                        'name' => $item['serialName'] ?? 'Sans nom',
                        'serial_no' => $item['serialNo'] ?? null,
                        'status' => Phone::mapApiStatus($item['status'] ?? null),
                        'group_name' => $item['group']['name'] ?? ($item['groupName'] ?? null),
                        'tags' => collect($item['tags'] ?? [])->pluck('name')->filter()->values()->all()
                            ?: ($item['tags'] ?? null),
                        'proxy' => $item['proxy']['server'] ?? ($item['proxy'] ?? null),
                        'equipment_info' => $item['equipmentInfo'] ?? null,
                        'country' => $item['equipmentInfo']['countryName'] ?? null,
                        'remark' => $item['remark'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    protected function ensureGeelarkId(Phone $phone): void
    {
        if (blank($phone->geelark_id)) {
            throw new RuntimeException('Ce téléphone n’a pas d’ID GeeLark.');
        }
    }

    protected function parseTags(null|string|array $tags): array
    {
        if (is_array($tags)) {
            return array_values(array_filter($tags));
        }

        if (blank($tags)) {
            return [];
        }

        return collect(explode(',', $tags))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();
    }
}
