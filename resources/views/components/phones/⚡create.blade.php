<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\PhoneManager;

new
#[Layout('layouts.app')]
#[Title('Créer un phone')]
class extends Component
{
    public string $name = '';
    public string $mobile_type = 'Android 13';
    public string $group_name = '';
    public string $tags = '';
    public string $proxy = '';
    public string $country = '';
    public string $remark = '';
    public string $region = '';
    public int $charge_mode = 0;

    public function save(PhoneManager $manager)
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'mobile_type' => 'required|string',
            'group_name' => 'nullable|string|max:120',
            'tags' => 'nullable|string|max:255',
            'proxy' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:80',
            'remark' => 'nullable|string|max:500',
            'region' => 'nullable|in:cn,sgp,us',
            'charge_mode' => 'in:0,1',
        ]);

        try {
            $phone = $manager->create([
                'name' => $this->name,
                'mobile_type' => $this->mobile_type,
                'group_name' => $this->group_name,
                'tags' => $this->tags,
                'proxy' => $this->proxy,
                'country' => $this->country,
                'remark' => $this->remark,
                'region' => $this->region,
                'charge_mode' => $this->charge_mode,
            ]);

            session()->flash('success', 'Cloud phone créé avec succès.');

            return $this->redirect(route('phones.show', $phone), navigate: true);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }
};
?>

<div>
    <div class="mb-8 animate-rise">
        <a href="{{ route('phones.index') }}" class="text-sm font-medium text-[var(--color-muted)] hover:text-[var(--color-ink)]">← Retour</a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Créer un phone</h1>
        <p class="mt-1 text-sm text-[var(--color-muted)]">La clé API reste côté serveur Laravel</p>
    </div>

    <form wire:submit="save" class="gl-card mx-auto max-w-2xl space-y-5 p-6 sm:p-8 animate-rise animate-rise-delay-1">
        <div>
            <label class="mb-1.5 block text-sm font-semibold">Nom</label>
            <input wire:model="name" type="text" class="gl-input" placeholder="ex. TikTok FR-01" required>
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Android</label>
                <select wire:model="mobile_type" class="gl-input">
                    @foreach (['Android 9','Android 10','Android 11','Android 12','Android 13','Android 14','Android 15','Android 16'] as $os)
                        <option value="{{ $os }}">{{ $os }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Région datacenter</label>
                <select wire:model="region" class="gl-input">
                    <option value="">Auto</option>
                    <option value="cn">Chine (cn)</option>
                    <option value="sgp">Singapour (sgp)</option>
                    <option value="us">USA (us)</option>
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Groupe</label>
                <input wire:model="group_name" type="text" class="gl-input" placeholder="ex. Marketing">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Tags</label>
                <input wire:model="tags" type="text" class="gl-input" placeholder="tiktok, fr, warmup">
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Proxy</label>
            <input wire:model="proxy" type="text" class="gl-input" placeholder="socks5://user:pass@host:port">
            <p class="mt-1 text-xs text-[var(--color-muted)]">http, https ou socks5</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Pays (local)</label>
                <input wire:model="country" type="text" class="gl-input" placeholder="France">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold">Facturation</label>
                <select wire:model="charge_mode" class="gl-input">
                    <option value="0">À la minute</option>
                    <option value="1">Mensuel</option>
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-semibold">Remarque</label>
            <textarea wire:model="remark" rows="3" class="gl-input" placeholder="Notes internes…"></textarea>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="gl-btn gl-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Créer le phone</span>
                <span wire:loading wire:target="save">Création…</span>
            </button>
            <a href="{{ route('phones.index') }}" class="gl-btn gl-btn-soft">Annuler</a>
        </div>
    </form>
</div>
