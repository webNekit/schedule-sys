<!DOCTYPE html>
<html lang="ru" class="h-full bg-zinc-950 dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Вход — Educational System Pro' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
    <style>
        @font-face { font-family: 'system'; src: local('-apple-system'); }
        .grid-bg {
            background-image:
                linear-gradient(rgba(39,39,42,0.5) 1px, transparent 1px),
                linear-gradient(90deg, rgba(39,39,42,0.5) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 30%, transparent 75%);
        }
    </style>
</head>
<body class="h-full text-zinc-100 antialiased bg-zinc-950 relative overflow-hidden">
    {{-- Фоновая сетка и свечение --}}
    <div class="absolute inset-0 grid-bg pointer-events-none"></div>
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-emerald-600/10 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="relative min-h-screen flex flex-col items-center justify-center p-4">
        {{ $slot }}

        <footer class="mt-8 text-center text-[10px] text-zinc-600 font-mono space-y-1">
            <p class="flex items-center justify-center gap-1.5 text-emerald-500/70">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                СИСТЕМА АКТИВНА
            </p>
            <p>© {{ date('Y') }} Educational Scheduling Systems Global · v2.4.1</p>
        </footer>
    </div>

    @livewireScripts
    @vite('resources/js/app.js')
</body>
</html>
