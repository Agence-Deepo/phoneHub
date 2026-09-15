<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GeeLarkApiService
{
    public function isDemoMode(): bool
    {
        if (config('geelark.demo_mode')) {
            return true;
        }

        $mode = config('geelark.auth_mode');

        if ($mode === 'token') {
            return blank(config('geelark.api_token'));
        }

        return blank(config('geelark.app_id')) || blank(config('geelark.api_key'));
    }

    public function listPhones(array $filters = []): array
    {
        return $this->request('/open/v1/phone/list', array_merge([
            'page' => 1,
            'pageSize' => 100,
        ], $filters));
    }

    public function createPhone(array $payload): array
    {
        return $this->request('/open/v1/phone/addNew', $payload);
    }

    public function startPhones(array $ids): array
    {
        return $this->request('/open/v1/phone/start', ['ids' => array_values($ids)]);
    }

    public function stopPhones(array $ids): array
    {
        return $this->request('/open/v1/phone/stop', ['ids' => array_values($ids)]);
    }

    public function deletePhones(array $ids): array
    {
        return $this->request('/open/v1/phone/delete', ['ids' => array_values($ids)]);
    }

    public function queryStatus(array $ids): array
    {
        return $this->request('/open/v1/phone/status', ['ids' => array_values($ids)]);
    }

    public function screenShot(string $id): array
    {
        return $this->request('/open/v1/phone/screenShot', ['id' => $id]);
    }

    public function screenShotResult(string $taskId): array
    {
        return $this->request('/open/v1/phone/screenShot/result', ['taskId' => $taskId]);
    }

    public function installApp(string $envId, string $appVersionId): array
    {
        return $this->request('/open/v1/app/install', [
            'envId' => $envId,
            'appVersionId' => $appVersionId,
        ]);
    }

    public function getInstalledApps(string $envId): array
    {
        return $this->request('/open/v1/app/installed', ['envId' => $envId]);
    }

    public function createCustomTask(array $payload): array
    {
        return $this->request('/open/v1/task/rpa/add', $payload);
    }

    protected function request(string $path, array $body = []): array
    {
        if ($this->isDemoMode()) {
            return $this->demoResponse($path, $body);
        }

        $traceId = strtoupper(str_replace('-', '', (string) Str::uuid()));

        try {
            $response = $this->client($traceId)
                ->post(rtrim((string) config('geelark.base_url'), '/').$path, $body);
        } catch (\Throwable $e) {
            Log::error('GeeLark API connection error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Impossible de joindre l’API GeeLark : '.$e->getMessage(), 0, $e);
        }

        $json = $response->json() ?? [];

        if (! $response->successful()) {
            Log::warning('GeeLark API HTTP error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $json,
            ]);

            throw new RuntimeException('Erreur HTTP GeeLark ('.$response->status().')');
        }

        if (($json['code'] ?? null) !== 0) {
            throw new RuntimeException($json['msg'] ?? 'Erreur API GeeLark (code '.($json['code'] ?? '?').')');
        }

        return $json;
    }

    protected function client(string $traceId): PendingRequest
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'traceId' => $traceId,
        ];

        if (config('geelark.auth_mode') === 'key') {
            $ts = (string) (int) (microtime(true) * 1000);
            $nonce = substr($traceId, 0, 6);
            $appId = (string) config('geelark.app_id');
            $apiKey = (string) config('geelark.api_key');
            $sign = strtoupper(hash('sha256', $appId.$traceId.$ts.$nonce.$apiKey));

            $headers = array_merge($headers, [
                'appId' => $appId,
                'ts' => $ts,
                'nonce' => $nonce,
                'sign' => $sign,
            ]);
        } else {
            $headers['Authorization'] = 'Bearer '.config('geelark.api_token');
        }

        return Http::withHeaders($headers)
            ->timeout((int) config('geelark.timeout', 30))
            ->acceptJson();
    }

    protected function demoResponse(string $path, array $body): array
    {
        $traceId = strtoupper(Str::random(16));

        return match (true) {
            str_contains($path, '/phone/list') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => ['page' => 1, 'pageSize' => 100, 'total' => 0, 'items' => []],
            ],
            str_contains($path, '/phone/addNew') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => [
                    'totalAmount' => 1,
                    'successAmount' => 1,
                    'failAmount' => 0,
                    'details' => [[
                        'index' => 1,
                        'code' => 0,
                        'msg' => 'success',
                        'id' => 'demo_'.Str::lower(Str::random(12)),
                        'profileName' => $body['data'][0]['profileName'] ?? 'Demo Phone',
                        'envSerialNo' => (string) random_int(1000, 9999),
                        'equipmentInfo' => [
                            'countryName' => 'France',
                            'osVersion' => $body['mobileType'] ?? 'Android 13',
                            'deviceBrand' => 'samsung',
                            'deviceModel' => 'Galaxy S23',
                        ],
                    ]],
                ],
            ],
            str_contains($path, '/phone/start') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => [
                    'totalAmount' => count($body['ids'] ?? []),
                    'successAmount' => count($body['ids'] ?? []),
                    'failAmount' => 0,
                    'successDetails' => collect($body['ids'] ?? [])->map(fn ($id) => [
                        'id' => $id,
                        'url' => 'https://example.com/demo-phone/'.$id,
                        'chargingMethod' => 'Per-minute usage',
                    ])->all(),
                    'failDetails' => [],
                ],
            ],
            str_contains($path, '/phone/stop') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => [
                    'totalAmount' => count($body['ids'] ?? []),
                    'successAmount' => count($body['ids'] ?? []),
                    'failAmount' => 0,
                    'successDetails' => [],
                    'failDetails' => [],
                ],
            ],
            str_contains($path, '/phone/delete') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => [
                    'totalAmount' => count($body['ids'] ?? []),
                    'successAmount' => count($body['ids'] ?? []),
                    'failAmount' => 0,
                    'failDetails' => [],
                ],
            ],
            str_contains($path, '/phone/screenShot/result') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => [
                    'status' => 2,
                    'downloadLink' => 'https://placehold.co/360x720/1e3a5f/ffffff/png?text=GeeLark+Demo',
                ],
            ],
            str_contains($path, '/phone/screenShot') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => ['taskId' => 'demo_shot_'.Str::random(8)],
            ],
            str_contains($path, '/app/install') => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
            ],
            default => [
                'code' => 0,
                'msg' => 'success (demo)',
                'traceId' => $traceId,
                'data' => [],
            ],
        };
    }
}
