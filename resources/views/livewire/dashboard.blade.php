<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Добро пожаловать, {{ auth()->user()?->name ?? 'Пользователь' }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Обзор системы расписания колледжа</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Система активна
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 transition-all duration-200 hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Группы</p>
                    <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">{{ $groupsCount }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-lg font-bold text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">Г</div>
            </div>
            <div class="mt-3 h-1 w-full rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                <div class="h-1 rounded-full bg-emerald-500" style="width: {{ min(100, $groupsCount * 10) }}%"></div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 transition-all duration-200 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Преподаватели</p>
                    <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">{{ $teachersCount }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-lg font-bold text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">П</div>
            </div>
            <div class="mt-3 h-1 w-full rounded-full bg-blue-100 dark:bg-blue-900/50">
                <div class="h-1 rounded-full bg-blue-500" style="width: {{ min(100, $teachersCount * 10) }}%"></div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 transition-all duration-200 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Аудитории</p>
                    <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">{{ $roomsCount }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-lg font-bold text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">А</div>
            </div>
            <div class="mt-3 h-1 w-full rounded-full bg-purple-100 dark:bg-purple-900/50">
                <div class="h-1 rounded-full bg-purple-500" style="width: {{ min(100, $roomsCount * 10) }}%"></div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 transition-all duration-200 hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Дисциплины</p>
                    <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">{{ $disciplinesCount }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-lg font-bold text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">Д</div>
            </div>
            <div class="mt-3 h-1 w-full rounded-full bg-amber-100 dark:bg-amber-900/50">
                <div class="h-1 rounded-full bg-amber-500" style="width: {{ min(100, $disciplinesCount * 5) }}%"></div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 transition-all duration-200 hover:shadow-md hover:border-cyan-300 dark:hover:border-cyan-700 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Расписаний</p>
                    <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">{{ $schedulesCount }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center text-lg font-bold text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform">Р</div>
            </div>
            <div class="mt-3 h-1 w-full rounded-full bg-cyan-100 dark:bg-cyan-900/50">
                <div class="h-1 rounded-full bg-cyan-500" style="width: {{ min(100, $schedulesCount * 20) }}%"></div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-5 transition-all duration-200 hover:shadow-md group {{ $pendingConflicts > 0 ? 'hover:border-red-300 dark:hover:border-red-700' : 'hover:border-emerald-300 dark:hover:border-emerald-700' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Конфликты</p>
                    <p class="mt-1.5 text-3xl font-bold {{ $pendingConflicts > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $pendingConflicts }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg {{ $pendingConflicts > 0 ? 'bg-red-50 dark:bg-red-900/30' : 'bg-emerald-50 dark:bg-emerald-900/30' }} flex items-center justify-center text-lg font-bold {{ $pendingConflicts > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} group-hover:scale-110 transition-transform">!</div>
            </div>
            <div class="mt-3 h-1 w-full rounded-full {{ $pendingConflicts > 0 ? 'bg-red-100 dark:bg-red-900/50' : 'bg-emerald-100 dark:bg-emerald-900/50' }}">
                <div class="h-1 rounded-full {{ $pendingConflicts > 0 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $pendingConflicts * 25) }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Быстрые действия</h2>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('groups.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-600 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-sm font-bold text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">Г</div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">Управление группами</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Просмотр и редактирование групп</p>
                        </div>
                    </a>
                    <a href="{{ route('teachers.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-sm font-bold text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">П</div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">Преподаватели</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Список и нагрузка</p>
                        </div>
                    </a>
                    <a href="{{ route('schedule.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-cyan-300 dark:hover:border-cyan-600 hover:bg-cyan-50/50 dark:hover:bg-cyan-900/10 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center text-sm font-bold text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform">Р</div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">Расписание</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Версии и публикация</p>
                        </div>
                    </a>
                    <a href="{{ route('curriculum.index') }}" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-600 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-sm font-bold text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">П</div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">Учебные планы</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Импорт и просмотр</p>
                        </div>
                    </a>
                </div>
            </div>

            <livewire:admin.actions-widget />
        </div>

        <div>
            <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Активность</h2>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Последние</span>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($recentActivity as $log)
                        <div class="px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs shrink-0 mt-0.5">
                                    {{ substr($log->user?->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $log->description }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-400 dark:text-gray-500">Нет активности</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
