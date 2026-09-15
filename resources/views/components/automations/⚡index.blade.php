<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Automation;
use App\Models\Phone;
use App\Services\PhoneManager;

new
#[Layout('layouts.app')]
#[Title('Automations')]
class extends Component
{
    public ?int $phone_id = null;
    public string $type = 'custom';

    public function launch(PhoneManager $manager): void
    {
        $this->validate([
            'phone_id' => 'required|exists:phones,id',
            'type' => 'required|string|max:80',
        ]);

        try {
            $phone = Phone::findOrFail($this->phone_id);
            $manager->runAutomation($phone, $this->type);
            session()->flash('success', 'Automatisation lancée.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'automations' => Automation::with('phone')->latest()->take(50)->get(),
            'phones' => Phone::orderBy('name')->get(),
        ];
    }
};
?>

<div>
    <div class="mb-8 animate-rise">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Automations</h1>
        <p class="mt-1 text-sm text-[var(--color-muted)]">Lancer et suivre tes tâches sur les Cloud Phones</p>
    </div>

    <div class="mb-5 gl-card p-5 animate-rise">
        <form wire:submit="launch" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label class="mb-1.5 block text-sm font-semibold">Phone</label>
                <select wire:model="phone_id" class="gl-input" required>
                    <option value="">Choisir…</option>
                    @foreach ($phones as $phone)
                        <option value="{{ $phone->id }}">{{ $phone->name }} ({{ $phone->statusLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="mb-1.5 block text-sm font-semibold">Type</label>
                <select wire:model="type" class="gl-input">
                    <option value="custom">Custom</option>
                    <option value="warmup">Warmup</option>
                    <option value="tiktok_follow">TikTok follow</option>
                    <option value="install_stack">Install stack</option>
                </select>
            </div>
            <button type="submit" class="gl-btn gl-btn-primary">Lancer</button>
        </form>
    </div>

    <div class="gl-card overflow-hidden animate-rise animate-rise-delay-1">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr class="border-b border-black/5 text-[var(--color-muted)]">
                        <th class="px-5 py-4 font-medium">Type</th>
                        <th class="px-5 py-4 font-medium">Phone</th>
                        <th class="px-5 py-4 font-medium">Statut</th>
                        <th class="px-5 py-4 font-medium">Message</th>
                        <th class="px-5 py-4 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($automations as $auto)
                        <tr class="border-b border-black/5 last:border-0" wire:key="auto-{{ $auto->id }}">
                            <td class="px-5 py-4 font-semibold">{{ $auto->type }}</td>
                            <td class="px-5 py-4">
                                @if ($auto->phone)
                                    <a href="{{ route('phones.show', $auto->phone) }}" class="hover:text-[var(--color-blue)]">{{ $auto->phone->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="gl-pill {{ $auto->status === 'success' ? 'gl-pill-online' : ($auto->status === 'failed' ? 'gl-pill-error' : 'gl-pill-starting') }}">
                                    {{ $auto->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-[var(--color-muted)]">{{ $auto->message ?: '—' }}</td>
                            <td class="px-5 py-4 text-[var(--color-muted)]">{{ $auto->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-[var(--color-muted)]">Aucune automatisation.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
