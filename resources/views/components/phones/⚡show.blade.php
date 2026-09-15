<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Phone;
use App\Services\PhoneManager;

new
#[Layout('layouts.app')]
#[Title('Détail phone')]
class extends Component
{
    public Phone $phone;

    public string $app_name = '';
    public string $app_version_id = '';
    public string $package_name = '';
    public string $automation_type = 'custom';
    public string $actionError = '';

    public function mount(Phone $phone): void
    {
        $this->phone = $phone;
    }

    public function start(PhoneManager $manager): void
    {
        $this->run(fn () => $manager->start($this->phone), 'Téléphone démarré.');
    }

    public function stop(PhoneManager $manager): void
    {
        $this->run(fn () => $manager->stop($this->phone), 'Téléphone arrêté.');
    }

    public function restart(PhoneManager $manager): void
    {
        $this->run(fn () => $manager->restart($this->phone), 'Téléphone redémarré.');
    }

    public function delete(PhoneManager $manager)
    {
        try {
            $manager->delete($this->phone);
            session()->flash('success', 'Téléphone supprimé.');

            return $this->redirect(route('phones.index'), navigate: true);
        } catch (\Throwable $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function screenshot(PhoneManager $manager): void
    {
        try {
            $this->phone = $manager->requestScreenshot($this->phone);
            // En démo le résultat est immédiat
            try {
                $this->phone = $manager->fetchScreenshotResult($this->phone);
                session()->flash('success', 'Capture d’écran disponible.');
            } catch (\Throwable $e) {
                session()->flash('success', 'Capture demandée. '.$e->getMessage());
            }
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function refreshScreenshot(PhoneManager $manager): void
    {
        $this->run(fn () => $manager->fetchScreenshotResult($this->phone), 'Capture récupérée.');
    }

    public function installApp(PhoneManager $manager): void
    {
        $this->validate([
            'app_name' => 'required|string|max:120',
            'app_version_id' => 'required|string|max:80',
            'package_name' => 'nullable|string|max:160',
        ]);

        try {
            $manager->installApp($this->phone, $this->app_name, $this->app_version_id, $this->package_name ?: null);
            $this->reset('app_name', 'app_version_id', 'package_name');
            $this->phone->refresh();
            session()->flash('success', 'Application installée.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function runAutomation(PhoneManager $manager): void
    {
        $this->validate([
            'automation_type' => 'required|string|max:80',
        ]);

        try {
            $manager->runAutomation($this->phone, $this->automation_type, [
                'source' => 'geelark-panel',
            ]);
            $this->phone->refresh();
            session()->flash('success', 'Automatisation lancée.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    protected function run(callable $action, string $success): void
    {
        try {
            $this->phone = $action();
            $this->phone->refresh();
            session()->flash('success', $success);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function with(): array
    {
        $this->phone->load(['apps', 'automations' => fn ($q) => $q->latest()->limit(8)]);

        return [];
    }
};
?>

<div>
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4 animate-rise">
        <div>
            <a href="{{ route('phones.index') }}" class="text-sm font-medium text-[var(--color-muted)] hover:text-[var(--color-ink)]">← Phones</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-extrabold tracking-tight">{{ $phone->name }}</h1>
                <span class="gl-pill gl-pill-{{ $phone->status }}">{{ $phone->statusLabel() }}</span>
            </div>
            <p class="mt-1 text-sm text-[var(--color-muted)]">ID cloud · {{ $phone->geelark_id }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($phone->status !== 'online')
                <button wire:click="start" class="gl-btn gl-btn-success">Démarrer</button>
            @else
                <button wire:click="stop" class="gl-btn gl-btn-soft">Arrêter</button>
                @if ($phone->remote_url)
                    <a href="{{ $phone->remote_url }}" target="_blank" class="gl-btn gl-btn-primary">Ouvrir remote</a>
                @endif
            @endif
            <button wire:click="restart" class="gl-btn gl-btn-soft">Redémarrer</button>
            <button wire:click="delete" wire:confirm="Supprimer définitivement ?" class="gl-btn gl-btn-danger">Supprimer</button>
        </div>
    </div>

    @if ($actionError !== '')
        <div class="mb-6 rounded-2xl px-4 py-3 text-sm font-medium" style="background: rgba(239,68,68,0.12); color: #dc2626;">
            {{ $actionError }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="gl-card space-y-4 p-6 lg:col-span-2 animate-rise animate-rise-delay-1">
            <h2 class="text-lg font-bold">Informations</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'OS' => $phone->osLabel(),
                    'Série' => $phone->serial_no,
                    'Groupe' => $phone->group_name,
                    'Pays' => $phone->country,
                    'Proxy' => $phone->proxy,
                    'Sync' => optional($phone->last_synced_at)?->diffForHumans(),
                ] as $label => $value)
                    <div class="rounded-2xl bg-[#f6f7fb] px-4 py-3">
                        <div class="text-xs font-medium text-[var(--color-muted)]">{{ $label }}</div>
                        <div class="mt-1 break-all text-sm font-semibold">{{ $value ?: '—' }}</div>
                    </div>
                @endforeach
            </div>
            @if ($phone->tags)
                <div class="flex flex-wrap gap-2 pt-1">
                    @foreach ($phone->tags as $tag)
                        <span class="gl-pill" style="background:#eef0f5;color:#4b5563">#{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            @if ($phone->remark)
                <p class="text-sm text-[var(--color-muted)]">{{ $phone->remark }}</p>
            @endif
        </div>

        <div class="gl-card p-6 animate-rise animate-rise-delay-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold">Screenshot</h2>
                <button wire:click="screenshot" class="gl-btn gl-btn-primary !px-3 !py-1.5 text-xs">Capturer</button>
            </div>
            @if ($phone->screenshot_url)
                <img src="{{ $phone->screenshot_url }}" alt="Screenshot" class="w-full rounded-2xl border border-black/5 object-cover">
            @elseif ($phone->screenshot_task_id)
                <p class="mb-3 text-sm text-[var(--color-muted)]">Capture en cours…</p>
                <button wire:click="refreshScreenshot" class="gl-btn gl-btn-soft text-xs">Récupérer le résultat</button>
            @else
                <div class="flex aspect-[9/16] items-center justify-center rounded-2xl bg-[#f6f7fb] text-sm text-[var(--color-muted)]">
                    Aucune capture
                </div>
            @endif
        </div>

        <div class="gl-card p-6 animate-rise animate-rise-delay-1">
            <h2 class="mb-4 text-lg font-bold">Installer une app</h2>
            <form wire:submit="installApp" class="space-y-3">
                <input wire:model="app_name" class="gl-input" placeholder="Nom de l’app" required>
                <input wire:model="app_version_id" class="gl-input" placeholder="App Version ID" required>
                <input wire:model="package_name" class="gl-input" placeholder="Package name (optionnel)">
                <button type="submit" class="gl-btn gl-btn-primary w-full">Installer</button>
            </form>
            <div class="mt-5 space-y-2">
                @forelse ($phone->apps as $app)
                    <div class="flex items-center justify-between rounded-xl bg-[#f6f7fb] px-3 py-2 text-sm">
                        <div>
                            <div class="font-semibold">{{ $app->app_name }}</div>
                            <div class="text-xs text-[var(--color-muted)]">{{ $app->package_name ?: $app->app_version_id }}</div>
                        </div>
                        <span class="gl-pill gl-pill-online">{{ $app->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-[var(--color-muted)]">Aucune app enregistrée localement.</p>
                @endforelse
            </div>
        </div>

        <div class="gl-card p-6 lg:col-span-2 animate-rise animate-rise-delay-2">
            <h2 class="mb-4 text-lg font-bold">Automatisations</h2>
            <form wire:submit="runAutomation" class="mb-5 flex flex-wrap gap-3">
                <select wire:model="automation_type" class="gl-input max-w-xs">
                    <option value="custom">Custom task</option>
                    <option value="warmup">Warmup</option>
                    <option value="tiktok_follow">TikTok follow</option>
                    <option value="install_stack">Install stack</option>
                </select>
                <button type="submit" class="gl-btn gl-btn-primary">Lancer</button>
            </form>
            <div class="space-y-2">
                @forelse ($phone->automations as $auto)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#f6f7fb] px-4 py-3">
                        <div>
                            <div class="text-sm font-semibold">{{ $auto->type }}</div>
                            <div class="text-xs text-[var(--color-muted)]">{{ $auto->message }} · {{ $auto->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="gl-pill {{ $auto->status === 'success' ? 'gl-pill-online' : ($auto->status === 'failed' ? 'gl-pill-error' : 'gl-pill-starting') }}">
                            {{ $auto->statusLabel() }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-[var(--color-muted)]">Aucune automatisation pour ce phone.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
