<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Connexion' }} — phoneHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
    <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full opacity-40" style="background: radial-gradient(circle, rgba(59,124,255,0.35), transparent 70%)"></div>
            <div class="absolute -right-16 bottom-10 h-80 w-80 rounded-full opacity-40" style="background: radial-gradient(circle, rgba(255,106,61,0.28), transparent 70%)"></div>
        </div>

        <div class="relative w-full max-w-md">
            @if (session('success'))
                <div class="mb-4 rounded-2xl px-4 py-3 text-sm font-medium animate-rise" style="background: rgba(34,197,94,0.12); color: #15803d;">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-2xl px-4 py-3 text-sm font-medium animate-rise" style="background: rgba(239,68,68,0.12); color: #dc2626;">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>
</html>
