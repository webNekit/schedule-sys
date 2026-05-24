<!DOCTYPE html>
<html lang="ru" x-data="{
    sidebarOpen: window.innerWidth > 768,
    darkMode: localStorage.getItem('darkMode') === 'true',
    userMenuOpen: false,
    mounted() {
        if (this.darkMode) document.documentElement.classList.add('dark');
        this.$watch('darkMode', val => {
            document.documentElement.classList.toggle('dark', val);
            localStorage.setItem('darkMode', val);
        });
    }
}" x-init="mounted()">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Колледж — Расписание' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>

<body class="bg-gray-50 dark:bg-gray-950 text-gray-800 dark:text-gray-200 min-h-screen antialiased">
    <div class="flex h-screen overflow-hidden">
        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen && window.innerWidth < 768" @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-gray-900/50 backdrop-blur-sm md:hidden"
            x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">
        </div>

        {{-- Sidebar (Увеличена ширина: w-72 вместо w-64) --}}
        <aside
            class="fixed md:static inset-y-0 left-0 z-40 flex flex-col bg-gray-900 dark:bg-gray-950 border-r border-gray-800 transition-all duration-300 ease-in-out overflow-hidden"
            :class="sidebarOpen ? 'w-72 translate-x-0' : 'w-0 -translate-x-full md:translate-x-0 md:border-none'" x-cloak>
            <div class="w-72 flex flex-col h-full shrink-0">
                {{-- Logo --}}
                <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800 shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 text-white text-sm font-bold">
                            К</div>
                        <div>
                            <h1 class="text-base font-semibold text-white leading-tight">Колледж</h1>
                            <p class="text-[10px] text-gray-400 leading-tight">Расписание занятий</p>
                        </div>
                    </a>
                    <button @click="sidebarOpen = false"
                        class="md:hidden p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Navigation --}}
                <nav
                    class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5 scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent">
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500 mb-2">Навигация</p>

                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        Панель управления
                    </x-nav-link>

                    <div class="my-3 border-t border-gray-800"></div>
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500 mb-2">Справочная</p>

                    <x-nav-link href="{{ route('groups.index') }}" :active="request()->routeIs('groups.*') && !request()->routeIs('teachers.*') && !request()->routeIs('rooms.*') && !request()->routeIs('curriculum.*') && !request()->routeIs('admin.specialties')">
                        Группы
                        @if(\App\Models\Group::count() > 0)
                            <span
                                class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-medium bg-gray-700 text-gray-300">{{ \App\Models\Group::count() }}</span>
                        @endif
                    </x-nav-link>

                    <x-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.*')">
                        Преподаватели
                        @if(\App\Models\Teacher::count() > 0)
                            <span
                                class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-medium bg-gray-700 text-gray-300">{{ \App\Models\Teacher::count() }}</span>
                        @endif
                    </x-nav-link>

                    <x-nav-link href="{{ route('rooms.index') }}" :active="request()->routeIs('rooms.*') && !request()->routeIs('rooms.manage')">
                        Аудитории
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.specialties') }}" :active="request()->routeIs('admin.specialties')">
                        Специальности
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.departments') }}" :active="request()->routeIs('admin.departments')">
                        Кафедры
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.positions') }}" :active="request()->routeIs('admin.positions')">
                        Должности
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.room-types') }}" :active="request()->routeIs('admin.room-types')">
                        Типы аудиторий
                    </x-nav-link>

                    <x-nav-link href="{{ route('curriculum.index') }}" :active="request()->routeIs('curriculum.*')">
                        Учебные планы
                    </x-nav-link>

                    <div class="my-3 border-t border-gray-800"></div>
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500 mb-2">Расписание</p>

                    <x-nav-link href="{{ route('schedule.index') }}" :active="request()->routeIs('schedule.index') || request()->routeIs('schedule.view') || request()->routeIs('schedule.generate')">
                        Расписание
                        @if(\App\Models\ScheduleVersion::count() > 0)
                            <span
                                class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-medium bg-gray-700 text-gray-300">{{ \App\Models\ScheduleVersion::count() }}</span>
                        @endif
                    </x-nav-link>

                    <div class="my-3 border-t border-gray-800"></div>
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500 mb-2">Управление</p>

                    <x-nav-link href="{{ route('rooms.manage') }}" :active="request()->routeIs('rooms.manage')">
                        Корпуса и аудитории
                    </x-nav-link>

                    <x-nav-link href="{{ route('curriculum.periods') }}" :active="request()->routeIs('curriculum.periods')">
                        Периоды и каникулы
                    </x-nav-link>

                    <div class="my-3 border-t border-gray-800"></div>
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500 mb-2">Администрирование
                    </p>

                    <x-nav-link href="{{ route('admin.roles') }}" :active="request()->routeIs('admin.roles')">
                        Роли
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')">
                        Пользователи
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.delete-data') }}" :active="request()->routeIs('admin.delete-data')">
                        Очистка данных
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')">
                        Настройки
                    </x-nav-link>
                </nav>

                {{-- User info in sidebar --}}
                <div class="p-4 border-t border-gray-800 shrink-0">
                    <div class="flex items-center gap-3 px-2">
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-600/20 text-emerald-400 text-xs font-semibold shrink-0">
                            {{ substr(auth()->user()?->name ?? 'Г', 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()?->name ?? 'Гость' }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ auth()->user()?->email ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-w-0 w-full">
            {{-- Top Bar --}}
            <header class="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shrink-0">
                <div class="flex items-center justify-between h-full px-4 md:px-6">
                    {{-- Left: toggle + breadcrumbs --}}
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <nav class="hidden sm:flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
                            <a href="{{ route('dashboard') }}"
                                class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors">Главная</a>
                            <span class="text-gray-300 dark:text-gray-600">/</span>
                            <span class="text-gray-700 dark:text-gray-200 font-medium">{{ $title ?? 'Страница' }}</span>
                        </nav>
                    </div>

                    {{-- Right: actions --}}
                    <div class="flex items-center gap-2">

                        {{-- КОЛОКОЛЬЧИК УВЕДОМЛЕНИЙ --}}
                        <livewire:notifications-widget />

                        {{-- Theme toggle --}}
                        <button @click="darkMode = !darkMode"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                            :title="darkMode ? 'Светлая тема' : 'Тёмная тема'">
                            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        {{-- User menu --}}
                        <div class="relative" @click.outside="userMenuOpen = false">
                            <button @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 pl-3 pr-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group">
                                <div
                                    class="flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                                    {{ substr(auth()->user()?->name ?? 'Г', 0, 1) }}
                                </div>
                                <span
                                    class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">{{ auth()->user()?->name ?? 'Гость' }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="userMenuOpen" x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-56 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg shadow-gray-200/50 dark:shadow-gray-950/50 py-1 z-50"
                                style="display: none;">
                                <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ auth()->user()?->name ?? 'Гость' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ auth()->user()?->email ?? '' }}
                                    </p>
                                </div>
                                <a href="#"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    Профиль
                                </a>
                                <a href="{{ route('admin.settings') }}"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    Настройки
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                <a href="{{ route('logout') }}"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    Выйти
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @if (session('message'))
        <div data-flash="{{ session('message') }}" data-flash-type="success" class="hidden"></div>
    @endif
    @if (session('error'))
        <div data-flash="{{ session('error') }}" data-flash-type="error" class="hidden"></div>
    @endif

    <x-toast />

    @livewireScripts
    @vite('resources/js/app.js')
</body>

</html>