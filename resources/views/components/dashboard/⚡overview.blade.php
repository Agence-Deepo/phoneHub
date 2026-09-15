<?php

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Phone;
use App\Models\Automation;
use App\Services\GeeLarkApiService;
use App\Services\PhoneManager;

new
#[Layout('layouts.app')]
#[Title('Overview')]
class extends Component
{
    public string $query = '';

    public function sync(PhoneManager $manager): void
    {
        try {
            $count = $manager->syncFromRemote();
            session()->flash('success', $count > 0
                ? "{$count} téléphone(s) synchronisé(s)."
                : 'Synchronisation terminée (aucun téléphone distant ou mode démo).');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function startAll(PhoneManager $manager): void
    {
        $phones = Phone::where('status', 'offline')->whereNotNull('geelark_id')->get();
        $ok = 0;

        foreach ($phones as $phone) {
            try {
                $manager->start($phone);
                $ok++;
            } catch (\Throwable) {
                // continue
            }
        }

        session()->flash('success', "{$ok} téléphone(s) démarré(s).");
    }

    public function with(): array
    {
        $total = Phone::count();
        $online = Phone::where('status', 'online')->count();
        $offline = Phone::where('status', 'offline')->count();
        $starting = Phone::where('status', 'starting')->count();
        $errors = Phone::where('status', 'error')->count();
        $automations = Automation::latest()->take(5)->get();
        $recent = Phone::latest()->take(6)->get();
        $demo = app(GeeLarkApiService::class)->isDemoMode();

        $onlinePct = $total > 0 ? round(($online / $total) * 100) : 0;
        $activity = [
            max(28, min(95, 40 + $online * 8)),
            max(24, min(90, 35 + $offline * 6)),
            max(30, min(98, 50 + $starting * 10)),
            max(20, min(85, 30 + $errors * 12)),
            max(35, min(92, 45 + $online * 5)),
            max(22, min(88, 38 + $total * 3)),
            max(40, min(96, 55 + $online * 4)),
        ];

        return compact('total', 'online', 'offline', 'starting', 'errors', 'automations', 'recent', 'demo', 'onlinePct', 'activity');
    }
};
?>

<div>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4 animate-rise">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Overview</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Pilotage interne de tes Cloud Phones</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="sync" class="gl-btn gl-btn-soft">Synchroniser</button>
            <button wire:click="startAll" class="gl-btn gl-btn-primary">Start All</button>
            <a href="{{ route('phones.create') }}" class="gl-btn gl-btn-primary" style="background: var(--color-ink)">Add phone +</a>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Activity chart --}}
        <div class="gl-card p-6 lg:col-span-2 animate-rise animate-rise-delay-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold">Activité phones</h2>
                    <p class="text-sm text-[var(--color-muted)]">Volume d’activité sur 7 créneaux</p>
                </div>
                <span class="text-[var(--color-muted)]">···</span>
            </div>
            <div class="chart-bars">
                @foreach ($activity as $i => $h)
                    <div class="bar" style="height: {{ $h }}%; animation-delay: {{ $i * 0.06 }}s"></div>
                @endforeach
            </div>
            <div class="mt-6">
                <div class="flex items-center gap-3 rounded-2xl border border-black/5 bg-white/70 px-4 py-3 backdrop-blur">
                    <span class="text-sm text-[var(--color-muted)]">Que veux-tu explorer ensuite ?</span>
                    <input wire:model.live.debounce.300ms="query" type="text" class="gl-input flex-1 !py-2" placeholder="ex. phones offline, proxy US…">
                </div>
            </div>
        </div>

        {{-- Status summary --}}
        <div class="gl-card p-6 animate-rise animate-rise-delay-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold">Fleet status</h2>
                <span class="text-[var(--color-muted)]">···</span>
            </div>
            <div class="mb-1 flex items-end gap-3">
                <span class="text-4xl font-extrabold tracking-tight">{{ $total }}</span>
                <span class="gl-pill gl-pill-online mb-1">{{ $onlinePct }}% online</span>
            </div>
            <p class="mb-6 text-sm text-[var(--color-muted)]">Cloud phones gérés</p>

            @php
                $rows = [
                    ['En ligne', $online, $total, '#22c55e'],
                    ['Arrêtés', $offline, $total, '#3b7cff'],
                    ['Erreurs', $errors, $total, '#f472b6'],
                ];
            @endphp

            <div class="space-y-4">
                @foreach ($rows as [$label, $value, $max, $color])
                    <div>
                        <div class="mb-1.5 flex justify-between text-sm">
                            <span class="font-medium">{{ $label }}</span>
                            <span class="text-[var(--color-muted)]">{{ $value }}</span>
                        </div>
                        <div class="gl-bar">
                            <span style="width: {{ $max > 0 ? ($value / $max) * 100 : 0 }}%; background: {{ $color }}"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent phones as dot activity --}}
        <div class="gl-card p-6 animate-rise animate-rise-delay-1">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold">Uptime récent</h2>
                <a href="{{ route('phones.index') }}" class="text-sm font-semibold text-[var(--color-blue)]">Voir tout</a>
            </div>
            <div class="mb-4 flex items-end justify-between gap-2">
                @foreach ($recent as $phone)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div class="dot-stack">
                            @for ($i = 0; $i < 6; $i++)
                                <i class="{{ $phone->status === 'online' && $i < 5 ? 'on' : ($phone->status === 'starting' && $i < 3 ? 'peak' : '') }}"></i>
                            @endfor
                        </div>
                        <span class="max-w-full truncate text-[10px] text-[var(--color-muted)]">{{ Str::limit($phone->name, 8) }}</span>
                    </div>
                @endforeach
                @if ($recent->isEmpty())
                    <p class="text-sm text-[var(--color-muted)]">Aucun téléphone pour l’instant.</p>
                @endif
            </div>
            <div class="text-sm">
                <span class="font-bold">{{ $online }}</span>
                <span class="text-[var(--color-muted)]"> en ligne · </span>
                <span class="font-bold">{{ $offline }}</span>
                <span class="text-[var(--color-muted)]"> arrêtés</span>
            </div>
        </div>

        {{-- Automations --}}
        <div class="gl-card p-6 animate-rise animate-rise-delay-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold">Automations</h2>
                <a href="{{ route('automations.index') }}" class="text-sm font-semibold text-[var(--color-blue)]">Gérer</a>
            </div>
            <div class="space-y-3">
                @forelse ($automations as $auto)
                    <div class="flex items-center justify-between rounded-2xl bg-[#f6f7fb] px-3 py-2.5">
                        <div>
                            <div class="text-sm font-semibold">{{ $auto->type }}</div>
                            <div class="text-xs text-[var(--color-muted)]">{{ $auto->phone?->name ?? '—' }}</div>
                        </div>
                        <span class="gl-pill {{ $auto->status === 'success' ? 'gl-pill-online' : ($auto->status === 'failed' ? 'gl-pill-error' : 'gl-pill-starting') }}">
                            {{ $auto->statusLabel() }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-[var(--color-muted)]">Pas encore d’automatisation.</p>
                @endforelse
            </div>
        </div>

        {{-- Insight --}}
        <div class="gl-insight p-6 animate-rise animate-rise-delay-3">
            <div class="mb-8 flex justify-between">
                <span class="text-sm font-medium text-white/80">System health</span>
                <span class="text-white/70">···</span>
            </div>
            <div class="text-5xl font-extrabold">{{ $onlinePct }}%</div>
            <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/90">
                @if ($demo)
                    Mode démo actif — configure <code class="rounded bg-white/15 px-1">GEELARK_API_TOKEN</code> dans <code class="rounded bg-white/15 px-1">.env</code> pour piloter tes vrais Cloud Phones.
                @elseif ($onlinePct >= 70)
                    La majorité de la flotte est en ligne. Les automatisations peuvent tourner sans interruption.
                @else
                    Une partie de la flotte est arrêtée. Démarre les phones critiques avant de lancer tes scripts.
                @endif
            </p>
        </div>
    </div>
</div>
