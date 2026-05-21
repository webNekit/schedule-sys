<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Учебные планы</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Кафедры, специальности и учебные планы</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <a href="{{ route('curriculum.import') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                + Импортировать план
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session('error'))
        <div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Поиск планов..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                </div>
                <select wire:model.live="statusFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все статусы</option>
                    <option value="active">Активные</option>
                    <option value="inactive">Неактивные</option>
                </select>
                @if($search || $statusFilter)
                    <button wire:click="resetFilters"
                        class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        Сбросить
                    </button>
                @endif
            </div>
        </div>

        <div class="p-4 space-y-2">
            @forelse($departments as $department)
                @php
                    $deptSpecialties = $department->specialties;
                @endphp
                @if($deptSpecialties->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button wire:click="toggleDepartment({{ $department->id }})"
                            class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left">
                            <svg class="w-4 h-4 text-gray-400 transition-transform {{ ($expandedDepartments[$department->id] ?? false) ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-sm font-bold text-emerald-600 dark:text-emerald-400">К</div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $department->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $department->short_name ?? '' }} • {{ $deptSpecialties->count() }} специальностей</p>
                            </div>
                        </button>

                        @if($expandedDepartments[$department->id] ?? false)
                            <div class="border-t border-gray-200 dark:border-gray-700">
                                @foreach($deptSpecialties as $specialty)
                                    @php $specialtyPlans = $plans->where('specialty_id', $specialty->id); @endphp
                                    <div class="border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                                        <button wire:click="toggleSpecialty({{ $specialty->id }})"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 pl-12 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-left">
                                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform {{ ($expandedSpecialties[$specialty->id] ?? false) ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $specialty->name }}</p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $specialty->code ?? '' }} • {{ $specialtyPlans->count() }} планов</p>
                                            </div>
                                        </button>

                                        @if($expandedSpecialties[$specialty->id] ?? false)
                                            <div class="border-t border-gray-100 dark:border-gray-700">
                                                @forelse($specialtyPlans as $plan)
                                                    <div class="flex items-center gap-3 px-4 py-2.5 pl-20 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                                        <a href="{{ route('curriculum.show', $plan) }}"
                                                            class="flex items-center gap-3 flex-1 min-w-0">
                                                            <div class="w-6 h-6 rounded-md bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-xs font-bold text-amber-600 dark:text-amber-400 shrink-0">П</div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400">{{ $plan->name }}</p>
                                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $plan->academicYear?->name ?? '—' }} • {{ $plan->version ?? '1.0' }} • {{ $plan->total_hours ?? 0 }} ч.</p>
                                                            </div>
                                                            <div class="shrink-0">
                                                                @if($plan->is_active)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                                        Активен
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                                        Нет
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </a>
                                                        <button wire:click.stop="deletePlan({{ $plan->id }})"
                                                            wire:confirm="Удалить учебный план? Это действие нельзя отменить."
                                                            class="shrink-0 p-1.5 rounded-lg text-red-400 hover:text-white hover:bg-red-500 transition-colors opacity-0 group-hover:opacity-100"
                                                            title="Удалить план">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @empty
                                                    <div class="px-4 py-3 pl-20 text-sm text-gray-400 dark:text-gray-500">
                                                        Нет учебных планов для этой специальности
                                                    </div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @empty
                <div class="text-center py-12 text-gray-400 dark:text-gray-500 italic">
                    Учебные планы не найдены
                </div>
            @endforelse
        </div>
    </div>
</div>
