<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Models\Phone;
use App\Services\PhoneManager;

new
#[Layout('layouts.app')]
#[Title('Phones')]
class extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $view = 'devices';

    public string $actionError = '';

    public function refreshStatuses(PhoneManager $manager): void
    {
        try {
            $manager->syncStatuses();
        } catch (\Throwable) {
            // Keep the last known UI state if GeeLark is briefly unreachable.
        }
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['devices', 'table'], true) ? $view : 'devices';
    }

    public function start(int $id, PhoneManager $manager): void
    {
        $this->runAction(fn () => $manager->start(Phone::findOrFail($id)), 'Téléphone démarré.');
    }

    public function stop(int $id, PhoneManager $manager): void
    {
        $this->runAction(fn () => $manager->stop(Phone::findOrFail($id)), 'Téléphone arrêté.');
    }

    public function restart(int $id, PhoneManager $manager): void
    {
        $this->runAction(fn () => $manager->restart(Phone::findOrFail($id)), 'Téléphone redémarré.');
    }

    public function delete(int $id, PhoneManager $manager): void
    {
        $this->runAction(fn () => $manager->delete(Phone::findOrFail($id)), 'Téléphone supprimé.');
    }

    protected function runAction(callable $action, string $success): void
    {
        $this->actionError = '';

        try {
            $action();
            session()->flash('success', $success);
        } catch (\Throwable $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function with(): array
    {
        $phones = Phone::query()
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('group_name', 'like', '%'.$this->search.'%')
                        ->orWhere('country', 'like', '%'.$this->search.'%')
                        ->orWhere('geelark_id', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->get();

        return compact('phones');
    }
};
?>

<div wire:poll.5s="refreshStatuses">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4 animate-rise">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Phones</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Liste, statut, proxy — synchro GeeLark toutes les 5 s</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="gl-view-toggle" role="group" aria-label="Mode d’affichage">
                <button type="button" wire:click="setView('devices')" class="{{ $view === 'devices' ? 'is-active' : '' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
                    Devices
                </button>
                <button type="button" wire:click="setView('table')" class="{{ $view === 'table' ? 'is-active' : '' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Table
                </button>
            </div>
            <a href="{{ route('phones.create') }}" class="gl-btn gl-btn-primary">Créer un phone +</a>
        </div>
    </div>

    @if ($actionError !== '')
        <div class="mb-5 rounded-2xl px-4 py-3 text-sm font-medium" style="background: rgba(239,68,68,0.12); color: #dc2626;">
            {{ $actionError }}
        </div>
    @endif

    <div class="mb-5 flex flex-wrap gap-3 animate-rise">
        <input wire:model.live.debounce.250ms="search" type="search" class="gl-input max-w-sm" placeholder="Rechercher nom, groupe, pays…">
        <select wire:model.live="status" class="gl-input max-w-[180px]">
            <option value="">Tous les statuts</option>
            <option value="online">En ligne</option>
            <option value="offline">Arrêté</option>
            <option value="starting">Démarrage</option>
            <option value="error">Erreur</option>
        </select>
    </div>

    @if ($view === 'devices')
        <div class="animate-rise animate-rise-delay-1">
            @if ($phones->isEmpty())
                <div class="gl-card px-5 py-16 text-center text-[var(--color-muted)]">
                    Aucun téléphone. <a href="{{ route('phones.create') }}" class="font-semibold text-[var(--color-blue)]">Créer le premier</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                    @foreach ($phones as $phone)
                        @php
                            $brand = strtoupper(substr($phone->equipment_info['deviceBrand'] ?? 'GL', 0, 2));
                            $icons = collect($phone->tags ?: ['App', 'Net', 'RPA'])->take(6);
                        @endphp
                        <div class="flex flex-col items-center gap-2" wire:key="device-{{ $phone->id }}">
                            <a href="{{ route('phones.show', $phone) }}" class="phone-device is-{{ $phone->status }} block">
                                <div class="phone-device-screen">
                                    @if ($phone->screenshot_url)
                                        <img src="{{ $phone->screenshot_url }}" alt="Écran {{ $phone->name }}">
                                    @endif
                                    <div class="phone-device-wallpaper">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="text-[9px] font-medium uppercase tracking-wider text-white/60">
                                                    {{ $phone->osLabel() ?: 'Android' }}
                                                </div>
                                                <div class="mt-0.5 text-xs font-bold leading-tight">{{ Str::limit($phone->name, 16) }}</div>
                                            </div>
                                            <span class="phone-device-status-dot mt-1"></span>
                                        </div>

                                        <div>
                                            <div class="mb-1.5 text-[10px] text-white/70">
                                                {{ $phone->country ?: '—' }} · {{ $phone->group_name ?: 'Sans groupe' }}
                                            </div>
                                            <div class="phone-device-icons">
                                                @foreach ($icons as $icon)
                                                    <span title="{{ $icon }}">{{ Str::upper(Str::substr($icon, 0, 2)) }}</span>
                                                @endforeach
                                                @for ($i = $icons->count(); $i < 3; $i++)
                                                    <span>{{ $brand }}</span>
                                                @endfor
                                            </div>
                                            <div class="phone-device-home"></div>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <div class="w-full max-w-[168px] text-center">
                                <a href="{{ route('phones.show', $phone) }}" class="text-xs font-bold hover:text-[var(--color-blue)]">{{ $phone->name }}</a>
                                <div class="mt-1 flex items-center justify-center gap-2">
                                    <span class="gl-pill gl-pill-{{ $phone->status }}">{{ $phone->statusLabel() }}</span>
                                </div>
                                <div class="mt-1 truncate text-[11px] text-[var(--color-muted)]" title="{{ $phone->proxy }}">
                                    {{ $phone->proxy ? Str::limit($phone->proxy, 22) : 'Sans proxy' }}
                                </div>
                                <div class="mt-2 flex flex-wrap justify-center gap-1">
                                    @if ($phone->status !== 'online')
                                        <button wire:click="start({{ $phone->id }})" class="gl-btn gl-btn-success !px-2.5 !py-1 text-[11px]">Start</button>
                                    @else
                                        <button wire:click="stop({{ $phone->id }})" class="gl-btn gl-btn-soft !px-2.5 !py-1 text-[11px]">Stop</button>
                                    @endif
                                    <button wire:click="restart({{ $phone->id }})" class="gl-btn gl-btn-soft !px-2.5 !py-1 text-[11px]">Restart</button>
                                    <button wire:click="delete({{ $phone->id }})" wire:confirm="Supprimer ce cloud phone ?" class="gl-btn gl-btn-danger !px-2.5 !py-1 text-[11px]">Delete</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="gl-card overflow-hidden animate-rise animate-rise-delay-1">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[var(--color-muted)]">
                            <th class="px-5 py-4 font-medium">Phone</th>
                            <th class="px-5 py-4 font-medium">Statut</th>
                            <th class="px-5 py-4 font-medium">Groupe</th>
                            <th class="px-5 py-4 font-medium">Pays</th>
                            <th class="px-5 py-4 font-medium">Proxy</th>
                            <th class="px-5 py-4 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($phones as $phone)
                            <tr class="border-b border-black/5 last:border-0 hover:bg-[#f8f9fc]" wire:key="phone-{{ $phone->id }}">
                                <td class="px-5 py-4">
                                    <a href="{{ route('phones.show', $phone) }}" class="font-semibold hover:text-[var(--color-blue)]">
                                        {{ $phone->name }}
                                    </a>
                                    <div class="mt-0.5 text-xs text-[var(--color-muted)]">{{ $phone->osLabel() ?: 'Android' }} · {{ Str::limit($phone->geelark_id, 16) }}</div>
                                    @if ($phone->tags)
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach ($phone->tags as $tag)
                                                <span class="gl-pill" style="background:#eef0f5;color:#4b5563">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="gl-pill gl-pill-{{ $phone->status }}">{{ $phone->statusLabel() }}</span>
                                </td>
                                <td class="px-5 py-4 text-[var(--color-muted)]">{{ $phone->group_name ?: '—' }}</td>
                                <td class="px-5 py-4 text-[var(--color-muted)]">{{ $phone->country ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="block max-w-[160px] truncate text-[var(--color-muted)]" title="{{ $phone->proxy }}">
                                        {{ $phone->proxy ? Str::limit($phone->proxy, 24) : '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        @if ($phone->status !== 'online')
                                            <button wire:click="start({{ $phone->id }})" class="gl-btn gl-btn-success !px-3 !py-1.5 text-xs">Start</button>
                                        @else
                                            <button wire:click="stop({{ $phone->id }})" class="gl-btn gl-btn-soft !px-3 !py-1.5 text-xs">Stop</button>
                                        @endif
                                        <button wire:click="restart({{ $phone->id }})" class="gl-btn gl-btn-soft !px-3 !py-1.5 text-xs">Restart</button>
                                        <a href="{{ route('phones.show', $phone) }}" class="gl-btn gl-btn-soft !px-3 !py-1.5 text-xs">Détail</a>
                                        <button wire:click="delete({{ $phone->id }})" wire:confirm="Supprimer ce cloud phone ?" class="gl-btn gl-btn-danger !px-3 !py-1.5 text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-[var(--color-muted)]">
                                    Aucun téléphone. <a href="{{ route('phones.create') }}" class="font-semibold text-[var(--color-blue)]">Créer le premier</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
