<!DOCTYPE html>
<html lang="ru" class="h-full bg-zinc-950 dark" x-data="{
    sidebarOpen: window.innerWidth > 768,
    userMenuOpen: false
}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Колледж — Расписание' }}</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style>
      body {
        font-family: 'Inter', sans-serif;
      }
      .font-mono {
        font-family: 'JetBrains Mono', monospace;
      }
      /* Custom modern scrollbar styles */
      ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }
      ::-webkit-scrollbar-track {
        background: #09090b;
      }
      ::-webkit-scrollbar-thumb {
        background: #27272a;
        border-radius: 9999px;
      }
      ::-webkit-scrollbar-thumb:hover {
        background: #3f3f46;
      }
    </style>
    @vite('resources/css/app.css')
    @livewireStyles
</head>

<body class="h-full text-zinc-100 flex flex-col antialiased bg-zinc-950">
    
    <!-- Top Navbar Header -->
    <header class="border-b border-zinc-900 bg-zinc-950 px-6 py-4 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-900 transition-colors mr-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 flex items-center justify-center shadow-md">
          <span class="text-zinc-950 font-bold text-lg">📅</span>
        </div>
        <div>
          <h1 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
            <span>Система генерации расписания</span>
            <span class="text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full font-mono font-bold">Pro</span>
          </h1>
          <p class="text-[11px] text-zinc-400">Интеллектуальный планировщик колледжа</p>
        </div>
      </div>

      <div class="flex items-center gap-4 text-xs">
        {{-- Notification Bell Widget --}}
        <livewire:notifications-widget />

        {{-- User menu --}}
        <div class="relative" @click.outside="userMenuOpen = false">
            <button @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2 pl-3 pr-2 py-1.5 rounded-lg border border-zinc-900 bg-zinc-900/50 hover:bg-zinc-900 transition-colors group">
                <div class="flex items-center justify-center w-7 h-7 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-semibold">
                    {{ substr(auth()->user()?->name ?? 'Г', 0, 1) }}
                </div>
                <span class="hidden sm:block text-xs font-semibold text-zinc-300 group-hover:text-zinc-100">{{ auth()->user()?->name ?? 'Гость' }}</span>
                <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="userMenuOpen" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 rounded-xl bg-zinc-950 border border-zinc-900 shadow-xl py-1 z-50"
                style="display: none;">
                <div class="px-4 py-2.5 border-b border-zinc-900">
                    <p class="text-xs font-bold text-white">
                        {{ auth()->user()?->name ?? 'Гость' }}
                    </p>
                    <p class="text-[10px] font-mono text-zinc-500 mt-0.5">
                        {{ auth()->user()?->email ?? '' }}
                    </p>
                </div>
                <a href="{{ route('admin.settings') }}"
                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-colors">
                    Настройки
                </a>
                <div class="border-t border-zinc-900 my-1"></div>
                <a href="{{ route('logout') }}"
                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-red-400 hover:bg-red-500/10 transition-colors">
                    Выйти
                </a>
            </div>
        </div>

        <span class="hidden md:inline-block text-zinc-500 text-[11px] font-mono">Educational System Pro</span>
      </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Left Sidebar controls -->
      <aside class="bg-zinc-950 border-r border-zinc-900 transition-all duration-300 ease-in-out flex flex-col justify-between shrink-0 z-30"
             :class="sidebarOpen ? 'w-72 p-5' : 'w-0 p-0 overflow-hidden border-none'" x-cloak>
        <div class="space-y-6 flex-1 overflow-y-auto pr-1">

          <div class="space-y-0.5">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-widest px-3 block mb-2">Навигация</span>
            <div class="space-y-0.5">
              <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" emoji="📊">
                Панель управления
              </x-nav-link>
            </div>
          </div>

          <div class="space-y-0.5">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-widest px-3 block mb-2">Расписание</span>
            <div class="space-y-0.5">
              <x-nav-link href="{{ route('schedule.index') }}" :active="request()->routeIs('schedule.index') || request()->routeIs('schedule.view') || request()->routeIs('schedule.generate')" emoji="🗓️">
                Расписание
              </x-nav-link>
              <x-nav-link href="{{ route('schedule.monitoring') }}" :active="request()->routeIs('schedule.monitoring')" emoji="📈">
                Мониторинг групп
              </x-nav-link>
              <x-nav-link href="{{ route('schedule.replacements') }}" :active="request()->routeIs('schedule.replacements')" emoji="🔄">
                Поиск замен
              </x-nav-link>
            </div>
          </div>

          <div class="space-y-0.5">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-widest px-3 block mb-2">Ресурсы</span>
            <div class="space-y-0.5">
              <x-nav-link href="{{ route('groups.index') }}" :active="request()->routeIs('groups.*') && !request()->routeIs('teachers.*') && !request()->routeIs('rooms.*') && !request()->routeIs('curriculum.*') && !request()->routeIs('admin.specialties')" emoji="👥">
                Группы
              </x-nav-link>
              <x-nav-link href="{{ route('teachers.index') }}" :active="request()->routeIs('teachers.*')" emoji="👨‍🏫">
                Преподаватели
              </x-nav-link>
              <x-nav-link href="{{ route('rooms.index') }}" :active="request()->routeIs('rooms.*') && !request()->routeIs('rooms.manage')" emoji="📍">
                Аудитории
              </x-nav-link>
              <x-nav-link href="{{ route('curriculum.index') }}" :active="request()->routeIs('curriculum.*')" emoji="📚">
                Учебные планы
              </x-nav-link>
            </div>
          </div>

          <div class="space-y-0.5">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-widest px-3 block mb-2">Настройки системы</span>
            <div class="space-y-0.5">
              <x-nav-link href="{{ route('admin.specialties') }}" :active="request()->routeIs('admin.specialties')" emoji="🎓">
                Специальности
              </x-nav-link>
              <x-nav-link href="{{ route('admin.departments') }}" :active="request()->routeIs('admin.departments')" emoji="🏢">
                Кафедры
              </x-nav-link>
              <x-nav-link href="{{ route('admin.positions') }}" :active="request()->routeIs('admin.positions')" emoji="💼">
                Должности
              </x-nav-link>
              <x-nav-link href="{{ route('admin.room-types') }}" :active="request()->routeIs('admin.room-types')" emoji="🏛️">
                Типы аудиторий
              </x-nav-link>
              <x-nav-link href="{{ route('rooms.manage') }}" :active="request()->routeIs('rooms.manage')" emoji="🏢">
                Корпуса и аудитории
              </x-nav-link>
              <x-nav-link href="{{ route('curriculum.periods') }}" :active="request()->routeIs('curriculum.periods')" emoji="📅">
                Периоды и каникулы
              </x-nav-link>
            </div>
          </div>

          <div class="space-y-0.5">
            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-widest px-3 block mb-2">Администрирование</span>
            <div class="space-y-0.5">
              <x-nav-link href="{{ route('admin.roles') }}" :active="request()->routeIs('admin.roles')" emoji="🔑">
                Роли
              </x-nav-link>
              <x-nav-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')" emoji="👤">
                Пользователи
              </x-nav-link>
              <x-nav-link href="{{ route('admin.delete-data') }}" :active="request()->routeIs('admin.delete-data')" emoji="🗑️">
                Очистка данных
              </x-nav-link>
              <x-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')" emoji="⚙️">
                Настройки
              </x-nav-link>
            </div>
          </div>

        </div>

        <div class="text-center text-[10px] text-zinc-500 space-y-1 pt-4 border-t border-zinc-900 shrink-0">
          <p>🖥️ Локальный запуск активен</p>
          <p class="font-mono text-[9px]">v2.4.1 Geometric Balance</p>
        </div>
      </aside>

      <!-- Central Content container -->
      <main class="flex-1 p-6 overflow-y-auto bg-zinc-950">
        {{ $slot }}
      </main>
    </div>

    <!-- Live Status bar -->
    <footer class="h-10 border-t border-zinc-900 bg-zinc-950 px-6 flex items-center justify-between text-[10px] text-zinc-500 font-mono shrink-0">
      <div class="flex items-center gap-4">
        <span class="flex items-center gap-1.5 text-emerald-400">
          <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
          СИСТЕМА АКТИВНА С СИНХРОНИЗАЦИЕЙ ACTIVE
        </span>
      </div>
      <div>
        © 2026 Educational Scheduling Systems Global
      </div>
    </footer>

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