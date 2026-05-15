<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Расписание — Колледж' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
    <style>
        @media print { .no-print { display: none !important; } }
        body { font-size: 16px; }
        @media (max-width: 768px) {
            .schedule-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 min-h-screen antialiased">
    <div class="w-full max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {{ $slot }}
    </div>
    @livewireScripts
    @vite('resources/js/app.js')
</body>
</html>
