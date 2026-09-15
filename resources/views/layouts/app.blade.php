<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Overview' }} — phoneHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
        <header
            class="gl-nav mb-6 sm:mb-8 animate-rise"
            x-data="{ open: false }"
            @keydown.escape.window="open = false"
        >
            <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                <div class="flex min-w-0 items-center gap-3 sm:gap-6">
                    <a href="{{ route('dashboard') }}" class="shrink-0 pl-1 text-lg font-extrabold tracking-tight sm:pl-3 sm:text-xl" style="color: var(--color-accent)">
                        phoneHub
                    </a>

                    <nav class="hidden items-center gap-1 md:flex">
                        <a href="{{ route('dashboard') }}" class="gl-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Home</a>
                        <a href="{{ route('phones.index') }}" class="gl-nav-link {{ request()->routeIs('phones.*') ? 'is-active' : '' }}">Phones</a>
                        <a href="{{ route('automations.index') }}" class="gl-nav-link {{ request()->routeIs('automations.*') ? 'is-active' : '' }}">Automations</a>
                    </nav>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    @if(config('geelark.demo_mode') || blank(config('geelark.api_token')))
                        <span class="gl-pill hidden sm:inline-flex" style="background: rgba(255,106,61,0.14); color: #c2410c;">Mode démo</span>
                    @else
                        <span class="gl-pill gl-pill-online hidden sm:inline-flex">API connectée</span>
                    @endif

                    <div class="hidden items-center gap-2 lg:flex">
                        <div class="text-right">
                            <div class="text-xs font-semibold leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-[var(--color-muted)]">Admin</div>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white" style="background: linear-gradient(135deg,#3b7cff,#ff6a3d)" title="{{ auth()->user()->email }}">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="gl-btn gl-btn-soft !px-3 !py-2 text-xs">Logout</button>
                        </form>
                    </div>

                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white lg:hidden" style="background: linear-gradient(135deg,#3b7cff,#ff6a3d)" title="{{ auth()->user()->email }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>

                    <button
                        type="button"
                        class="gl-menu-btn md:hidden"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-controls="mobile-nav"
                        aria-label="Menu"
                    >
                        <svg x-show="!open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                        <svg x-show="open" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div
                id="mobile-nav"
                class="gl-mobile-panel md:hidden"
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
            >
                <nav class="flex flex-col gap-1 px-3 pb-3">
                    <a href="{{ route('dashboard') }}" class="gl-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" @click="open = false">Home</a>
                    <a href="{{ route('phones.index') }}" class="gl-nav-link {{ request()->routeIs('phones.*') ? 'is-active' : '' }}" @click="open = false">Phones</a>
                    <a href="{{ route('automations.index') }}" class="gl-nav-link {{ request()->routeIs('automations.*') ? 'is-active' : '' }}" @click="open = false">Automations</a>
                </nav>

                <div class="mx-3 mb-3 rounded-2xl bg-[#f6f7fb] px-3 py-3">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-[var(--color-muted)]">{{ auth()->user()->email }}</div>
                        </div>
                        @if(config('geelark.demo_mode') || blank(config('geelark.api_token')))
                            <span class="gl-pill shrink-0" style="background: rgba(255,106,61,0.14); color: #c2410c;">Démo</span>
                        @else
                            <span class="gl-pill gl-pill-online shrink-0">API</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="gl-btn gl-btn-soft w-full !py-2.5 text-xs">Se déconnecter</button>
                    </form>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-6 rounded-2xl px-4 py-3 text-sm font-medium animate-rise" style="background: rgba(34,197,94,0.12); color: #15803d;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl px-4 py-3 text-sm font-medium animate-rise" style="background: rgba(239,68,68,0.12); color: #dc2626;">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
