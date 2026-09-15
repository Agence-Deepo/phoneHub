<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Connexion')]
class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        session()->regenerate();

        return $this->redirectIntended(default: route('dashboard'), navigate: true);
    }
};
?>

<div class="animate-rise">
    <div class="mb-8 text-center">
        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 shadow-sm backdrop-blur">
            <span class="text-lg font-extrabold tracking-tight" style="color: var(--color-accent)">phoneHub</span>
            <span class="gl-pill" style="background: rgba(59,124,255,0.12); color: #1d4ed8;">Admin</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight">Connexion</h1>
        <p class="mt-2 text-sm text-[var(--color-muted)]">Accède au panel interne Cloud Phones</p>
    </div>

    <form wire:submit="login" class="gl-card space-y-5 p-6 sm:p-8">
        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="username"
                class="gl-input"
                placeholder="admin@phonehub.local"
                required
                autofocus
            >
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold">Mot de passe</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="gl-input"
                placeholder="••••••••"
                required
            >
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-[var(--color-muted)]">
            <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded border-black/10 text-[var(--color-blue)] focus:ring-[var(--color-blue)]">
            Se souvenir de moi
        </label>

        <button type="submit" class="gl-btn gl-btn-primary w-full !py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Se connecter</span>
            <span wire:loading wire:target="login">Connexion…</span>
        </button>
    </form>
</div>
