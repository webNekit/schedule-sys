<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight">Добро пожаловать, {{ auth()->user()?->name ?? 'Пользователь' }}</h1>
            <p class="text-xs text-zinc-400 mt-1">Интеллектуальный планировщик и панель управления системой расписания</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                Система активна
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- Groups card -->
        <div class="relative overflow-hidden rounded-xl bg-zinc-900 border border-zinc-850 p-5 transition-all duration-300 hover:border-emerald-500/30 group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-zinc-400 font-semibold uppercase tracking-wider block">Группы</span>
                    <span class="mt-2 text-3xl font-bold text-white font-mono block leading-none">{{ $groupsCount }}</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">👥</div>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full mt-4 overflow-hidden">
                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ min(100, $groupsCount * 10) }}%"></div>
            </div>
        </div>

        <!-- Teachers card -->
        <div class="relative overflow-hidden rounded-xl bg-zinc-900 border border-zinc-850 p-5 transition-all duration-300 hover:border-emerald-500/30 group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-zinc-400 font-semibold uppercase tracking-wider block">Преподаватели</span>
                    <span class="mt-2 text-3xl font-bold text-white font-mono block leading-none">{{ $teachersCount }}</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">👨‍🏫</div>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full mt-4 overflow-hidden">
                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ min(100, $teachersCount * 10) }}%"></div>
            </div>
        </div>

        <!-- Rooms card -->
        <div class="relative overflow-hidden rounded-xl bg-zinc-900 border border-zinc-850 p-5 transition-all duration-300 hover:border-emerald-500/30 group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-zinc-400 font-semibold uppercase tracking-wider block">Аудитории</span>
                    <span class="mt-2 text-3xl font-bold text-white font-mono block leading-none">{{ $roomsCount }}</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">📍</div>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full mt-4 overflow-hidden">
                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ min(100, $roomsCount * 10) }}%"></div>
            </div>
        </div>

        <!-- Disciplines card -->
        <div class="relative overflow-hidden rounded-xl bg-zinc-900 border border-zinc-850 p-5 transition-all duration-300 hover:border-emerald-500/30 group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-zinc-400 font-semibold uppercase tracking-wider block">Дисциплины</span>
                    <span class="mt-2 text-3xl font-bold text-white font-mono block leading-none">{{ $disciplinesCount }}</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">📚</div>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full mt-4 overflow-hidden">
                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ min(100, $disciplinesCount * 5) }}%"></div>
            </div>
        </div>

        <!-- Schedules card -->
        <div class="relative overflow-hidden rounded-xl bg-zinc-900 border border-zinc-850 p-5 transition-all duration-300 hover:border-emerald-500/30 group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-zinc-400 font-semibold uppercase tracking-wider block">Расписания</span>
                    <span class="mt-2 text-3xl font-bold text-white font-mono block leading-none">{{ $schedulesCount }}</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">🗓️</div>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full mt-4 overflow-hidden">
                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ min(100, $schedulesCount * 20) }}%"></div>
            </div>
        </div>

        <!-- Conflicts card -->
        <div class="relative overflow-hidden rounded-xl bg-zinc-900 border border-zinc-850 p-5 transition-all duration-300 hover:border-red-500/30 group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-zinc-400 font-semibold uppercase tracking-wider block">Конфликты</span>
                    <span class="mt-2 text-3xl font-bold font-mono block leading-none {{ $pendingConflicts > 0 ? 'text-red-400' : 'text-emerald-400' }}">{{ $pendingConflicts }}</span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-zinc-950 flex items-center justify-center text-sm font-bold {{ $pendingConflicts > 0 ? 'text-red-400 bg-red-500/10' : 'text-emerald-400 bg-emerald-500/10' }} group-hover:scale-110 transition-transform">⚠️</div>
            </div>
            <div class="w-full bg-zinc-800 h-1 rounded-full mt-4 overflow-hidden">
                <div class="h-full transition-all duration-500 {{ $pendingConflicts > 0 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $pendingConflicts * 25) }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Quick actions -->
            <div class="rounded-xl bg-zinc-900 border border-zinc-850 overflow-hidden p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-2">Быстрые действия</h3>
                <p class="text-xs text-zinc-400 mb-4">Основные разделы для управления учебным процессом</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('groups.index') }}" class="flex items-center gap-4 p-4 rounded-xl border border-zinc-800 bg-zinc-950 hover:bg-zinc-900/55 hover:border-emerald-500/30 transition-all duration-300 group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">👥</div>
                        <div>
                            <p class="font-bold text-white text-xs">Управление группами</p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Просмотр и редактирование групп студентов</p>
                        </div>
                    </a>
                    <a href="{{ route('teachers.index') }}" class="flex items-center gap-4 p-4 rounded-xl border border-zinc-800 bg-zinc-950 hover:bg-zinc-900/55 hover:border-emerald-500/30 transition-all duration-300 group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">👨‍🏫</div>
                        <div>
                            <p class="font-bold text-white text-xs">Преподаватели</p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Список преподавателей и их нагрузка</p>
                        </div>
                    </a>
                    <a href="{{ route('schedule.index') }}" class="flex items-center gap-4 p-4 rounded-xl border border-zinc-800 bg-zinc-950 hover:bg-zinc-900/55 hover:border-emerald-500/30 transition-all duration-300 group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">🗓️</div>
                        <div>
                            <p class="font-bold text-white text-xs">Расписание</p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Версии расписания, генерация и публикации</p>
                        </div>
                    </a>
                    <a href="{{ route('curriculum.index') }}" class="flex items-center gap-4 p-4 rounded-xl border border-zinc-800 bg-zinc-950 hover:bg-zinc-900/55 hover:border-emerald-500/30 transition-all duration-300 group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-sm font-bold text-emerald-400 group-hover:scale-110 transition-transform">📚</div>
                        <div>
                            <p class="font-bold text-white text-xs">Учебные планы</p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Импорт и просмотр дисциплин учебного плана</p>
                        </div>
                    </a>
                </div>
            </div>

            <livewire:admin.actions-widget />
        </div>

        <div>
            <!-- Activity feed -->
            <div class="rounded-xl bg-zinc-900 border border-zinc-850 overflow-hidden p-5 flex flex-col h-full">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Активность</h3>
                    <span class="text-[10px] text-zinc-500 font-mono">Последние события</span>
                </div>
                <p class="text-xs text-zinc-400 mb-4">Журнал последних изменений в системе</p>

                <div class="divide-y divide-zinc-800/50 flex-grow overflow-y-auto max-h-[360px] pr-1">
                    @forelse($recentActivity as $log)
                        <div class="py-3 hover:bg-zinc-800/10 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-zinc-950 border border-zinc-800 flex items-center justify-center text-xs text-emerald-400 shrink-0 font-bold">
                                    {{ substr($log->user?->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-zinc-200 font-semibold truncate">{{ $log->description }}</p>
                                    <p class="text-[9px] font-mono text-zinc-500 mt-1">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center italic text-xs text-zinc-600">
                            Нет активности
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>