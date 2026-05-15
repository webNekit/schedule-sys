<!DOCTYPE html>
<html lang="ru" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Вход — Колледж' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen flex items-center justify-center p-4">
    {{ $slot }}

    @livewireScripts
    @vite('resources/js/app.js')
</body>
</html>
