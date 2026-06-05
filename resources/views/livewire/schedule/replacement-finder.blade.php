<div class="space-y-6">
    <div class="bg-white dark:bg-gray-900 p-6 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Умный поиск замен</h2>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500 shadow-sm"></span> <span class="text-[10px] font-bold text-gray-500 uppercase">Занято</span></div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"></span> <span class="text-[10px] font-bold text-gray-500 uppercase">Свободно</span></div>
            </div>
        </div>

        {{-- Глобальная сетка занятости группы --}}
        @if($this->groupId)
            <div class="mb-8 bg-gray-50 dark:bg-gray-800/40 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50" x-transition>
                <div class="flex items-center justify-between mb-3 px-1">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-500">Занятость группы {{ $groupSearch }} на {{ \Carbon\Carbon::parse($date)->translatedFormat('d F') }}</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500">
                            Всего: {{ count($this->groupBusySlots) }} пар
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    @foreach(range(1, 7) as $slot)
                        @php 
                            $isBusy = in_array($slot, $this->groupBusySlots);
                            $isSelected = $slot == $lessonNumber;
                        @endphp
                        <button type="button" 
                            @disabled(!$groupId) 
                            wire:click="selectLesson({{ $slot }})"
                            @class([
                                'flex-1 h-14 rounded-xl flex flex-col items-center justify-center transition-all border-2 relative overflow-hidden group',
                                'bg-red-500 border-red-600 text-white shadow-lg shadow-red-500/20' => $isBusy,
                                'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-400 hover:border-emerald-400' => !$isBusy && !$isSelected,
                                'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500 text-emerald-600 dark:text-emerald-400 shadow-lg shadow-emerald-500/10' => $isSelected && !$isBusy,
                                'ring-4 ring-red-500/30' => $isSelected && $isBusy,
                            ])>
                            <span class="text-[10px] font-black uppercase opacity-60 mb-0.5">{{ $slot }}-я</span>
                            <span @class(['text-sm font-black', 'text-white' => $isBusy, 'text-gray-900 dark:text-white' => !$isBusy])>
                                {{ $isBusy ? 'ЗАНЯТО' : 'ОКНО' }}
                            </span>
                            @if($isSelected)
                                <div class="absolute bottom-0 inset-x-0 h-1 bg-emerald-500"></div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
        
        <div class="flex flex-wrap lg:flex-nowrap gap-4 items-end">
            {{-- Шаг 1: Дата --}}
            <div class="w-full lg:w-48 shrink-0">
                <label class="block text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-2 ml-1">1. Дата</label>
                <div class="relative">
                    <input type="date" wire:model.live="date" 
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:text-white transition-all">
                </div>
            </div>

            {{-- Шаг 2: Группа --}}
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-2 ml-1">2. Группа</label>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" 
                        class="w-full flex justify-between items-center rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-4 py-2.5 text-left text-sm shadow-sm hover:border-emerald-400 transition-all bg-white dark:bg-gray-800">
                        <span class="truncate">{{ $groupSearch ?: 'Выберите...' }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition
                        class="absolute z-50 w-full md:min-w-[280px] mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl max-h-80 overflow-y-auto">
                        <div class="sticky top-0 bg-white dark:bg-gray-800 p-3 border-b border-gray-100 dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="groupSearch" placeholder="Поиск группы..."
                                class="w-full px-3 py-2 rounded-lg border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 outline-none text-sm dark:text-white">
                        </div>
                        @foreach ($this->filteredGroups as $g)
                            <button type="button" @click="open = false" wire:click="selectGroup({{ $g->id }}, '{{ addslashes($g->name) }}')"
                                class="w-full text-left px-4 py-3 text-sm hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors {{ $groupId == $g->id ? 'bg-emerald-50 text-emerald-700 font-bold border-l-4 border-emerald-500' : '' }}">
                                {{ $g->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Шаг 3: Корпус --}}
            <div @class(['w-full lg:w-48 shrink-0 relative group', 'opacity-50' => !$groupId])>
                <label @class(['block text-[10px] font-black uppercase tracking-widest mb-2 ml-1', 'text-emerald-600 dark:text-emerald-400' => $groupId, 'text-gray-400' => !$groupId])>3. Корпус</label>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @disabled(!$groupId) @click="open = !open" 
                        @class([
                            'w-full flex justify-between items-center rounded-xl border px-4 py-2.5 text-left text-sm shadow-sm transition-all',
                            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-emerald-400 cursor-pointer' => $groupId,
                            'border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 cursor-not-allowed' => !$groupId
                        ])>
                        <span class="truncate">{{ $buildingSearch ?: 'Выберите...' }}</span>
                        @if($groupId)
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                        @else
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        @endif
                    </button>
                    @if($groupId)
                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute z-50 w-full md:min-w-[220px] mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto">
                            <div class="sticky top-0 bg-white dark:bg-gray-800 p-3 border-b border-gray-100 dark:border-gray-700">
                                <input type="text" wire:model.live.debounce.300ms="buildingSearch" placeholder="Поиск..."
                                    class="w-full px-3 py-2 rounded-lg border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 outline-none text-sm dark:text-white">
                            </div>
                            <button type="button" @click="open = false" wire:click="selectBuilding(0, 'Все корпуса')"
                                class="w-full text-left px-4 py-3 text-sm hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors">
                                Все корпуса
                            </button>
                            @foreach ($this->filteredBuildings as $b)
                                <button type="button" @click="open = false" wire:click="selectBuilding({{ $b->id }}, '{{ addslashes($b->name) }}')"
                                    class="w-full text-left px-4 py-3 text-sm hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors {{ $buildingId == $b->id ? 'bg-emerald-50 text-emerald-700 font-bold border-l-4 border-emerald-500' : '' }}">
                                    {{ $b->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Шаг 4: Пара --}}
            <div @class(['w-full lg:w-40 shrink-0 relative group', 'opacity-50' => !$groupId])>
                <label @class(['block text-[10px] font-black uppercase tracking-widest mb-2 ml-1', 'text-emerald-600 dark:text-emerald-400' => $groupId, 'text-gray-400' => !$groupId])>4. Пара</label>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @disabled(!$groupId) @click="open = !open" 
                         @class([
                            'w-full flex justify-between items-center rounded-xl border px-4 py-2.5 text-left text-sm shadow-sm transition-all',
                            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-emerald-400 cursor-pointer' => $groupId,
                            'border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 cursor-not-allowed' => !$groupId
                        ])>
                        <span>{{ $lessonNumber ? $lessonNumber . '-я пара' : 'Выберите...' }}</span>
                        @if($groupId)
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                        @else
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        @endif
                    </button>
                    @if($groupId)
                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl overflow-hidden">
                            @foreach(range(1, 8) as $n)
                                <button type="button" @click="open = false" wire:click="selectLesson({{ $n }})"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors {{ $lessonNumber == $n ? 'bg-emerald-50 text-emerald-700 font-bold border-l-4 border-emerald-500' : '' }}">
                                    {{ $n }}-я пара
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Шаг 5: Дисциплина --}}
            <div @class(['w-full lg:flex-1 min-w-0 relative group', 'opacity-50' => !$lessonNumber])>
                <label @class(['block text-[10px] font-black uppercase tracking-widest mb-2 ml-1', 'text-emerald-600 dark:text-emerald-400' => $lessonNumber, 'text-gray-400' => !$lessonNumber])>5. Предмет</label>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @disabled(!$lessonNumber) @click="open = !open" 
                         @class([
                            'w-full flex justify-between items-center rounded-xl border px-4 py-2.5 text-left text-sm shadow-sm transition-all',
                            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-emerald-400 cursor-pointer' => $lessonNumber,
                            'border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 cursor-not-allowed' => !$lessonNumber
                        ])>
                        <span class="truncate font-medium">{{ $disciplineSearch ?: 'Выберите предмет...' }}</span>
                        @if($lessonNumber)
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                        @else
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        @endif
                    </button>
                    @if($lessonNumber)
                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute z-50 right-0 w-full md:min-w-[500px] mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl max-h-96 overflow-y-auto">
                            <div class="sticky top-0 bg-white dark:bg-gray-800 p-3 border-b border-gray-100 dark:border-gray-700">
                                <input type="text" wire:model.live.debounce.300ms="disciplineSearch" placeholder="Поиск предмета..."
                                    class="w-full px-3 py-2 rounded-lg border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 outline-none text-sm dark:text-white">
                            </div>
                            @foreach ($this->filteredDisciplines as $d)
                                <button type="button" @click="open = false" wire:click="selectDiscipline({{ $d->id }}, '{{ addslashes($d->name) }}')"
                                    @class([
                                        'w-full text-left px-4 py-3 text-sm transition-all flex justify-between items-center group border-b border-gray-50 dark:border-gray-700/50 last:border-0',
                                        'hover:bg-emerald-50 dark:hover:bg-emerald-900/30' => $disciplineId != $d->id,
                                        'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 font-bold border-l-4 border-emerald-500' => $disciplineId == $d->id
                                    ])>
                                    <div class="flex flex-col truncate pr-4 text-left">
                                        @if($d->code)
                                            <span class="font-mono text-[9px] text-gray-400 group-hover:text-emerald-600 transition-colors uppercase">{{ $d->code }}</span>
                                        @endif
                                        <span class="truncate font-medium text-gray-700 dark:text-gray-200">{{ $d->name }}</span>
                                        @if($d->primary_teacher)
                                            <span class="text-[9px] text-gray-400 italic">Ведет: {{ $d->primary_teacher }}</span>
                                        @endif
                                    </div>
                                    @if(isset($d->remaining_hours))
                                        <div class="shrink-0 text-right">
                                            <div class="text-[10px] font-bold text-gray-500 group-hover:text-emerald-700">{{ $d->remaining_hours }} ч.</div>
                                            <div class="text-[9px] text-gray-400">из {{ $d->total_hours }}</div>
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($this->groupId)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-transition>
            @foreach($this->availableTeachers as $teacher)
                <div @class([
                    'bg-white dark:bg-gray-800 p-5 rounded-2xl border transition-all group relative overflow-hidden',
                    'border-gray-200 dark:border-gray-700 hover:border-emerald-500 shadow-sm hover:shadow-lg translate-y-0 hover:-translate-y-1' => $teacher->is_replacement_available,
                    'border-gray-100 dark:border-gray-800 opacity-60 grayscale-[0.5]' => !$teacher->is_replacement_available,
                ])>
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div @class([
                                'w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shadow-inner',
                                'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400' => $teacher->is_replacement_available,
                                'bg-gray-100 dark:bg-gray-700 text-gray-400' => !$teacher->is_replacement_available,
                            ])>
                                {{ substr($teacher->last_name, 0, 1) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white leading-tight">{{ $teacher->full_name }}</h4>
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ $teacher->position?->name }}</p>
                            </div>
                        </div>
                        @if($teacher->is_replacement_available && $this->isSelectionComplete)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500 text-white shadow-sm animate-pulse">
                                ПОДХОДИТ
                            </span>
                        @endif
                    </div>
                    
                    <div class="mt-5 space-y-4">
                        {{-- Сетка занятости --}}
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Занятость на {{ \Carbon\Carbon::parse($date)->format('d.m') }}</span>
                                <span class="text-[10px] text-gray-500">{{ count($teacher->busy_slots) }} пар</span>
                            </div>
                            <div class="flex gap-1">
                                @foreach(range(1, 7) as $slot)
                                    @php 
                                        $isBusy = in_array($slot, $teacher->busy_slots);
                                        $isTarget = $slot == $lessonNumber;
                                    @endphp
                                    <div @class([
                                        'flex-1 h-6 rounded-md flex items-center justify-center text-[10px] font-bold transition-all border',
                                        'bg-red-500 border-red-600 text-white shadow-sm' => $isBusy,
                                        'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-300' => !$isBusy,
                                        'ring-2 ring-emerald-500 ring-offset-2 dark:ring-offset-gray-800 scale-110 z-10' => $isTarget && !$isBusy,
                                        'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-800' => $isTarget && $isBusy,
                                    ])>
                                        {{ $slot }}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-gray-50 dark:border-gray-700/50">
                            @if($teacher->workload_info)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Нагрузка:</span>
                                    <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold">
                                        {{ $teacher->workload_info['remaining'] }} / {{ $teacher->workload_info['total'] }} ч.
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Кабинет:</span>
                                <span class="font-bold text-gray-700 dark:text-gray-300">
                                    {{ $teacher->preferred_room ? "№{$teacher->preferred_room->number}" : '—' }}
                                </span>
                            </div>

                            @if(!$teacher->is_replacement_available)
                                <div class="text-[10px] text-red-600 bg-red-50 dark:bg-red-900/20 p-3 rounded-xl mt-2 border border-red-100 dark:border-red-900/30">
                                    <ul class="space-y-0.5">
                                        @foreach($teacher->availability_reasons as $reason)
                                            <li class="flex items-start gap-1"><span>•</span> <span>{{ $reason }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            
                            <button 
                                wire:click="assignReplacement({{ $teacher->id }}, {{ $teacher->preferred_room?->id ?? 'null' }})"
                                wire:loading.attr="disabled"
                                @disabled(!$this->isSelectionComplete || !$teacher->is_replacement_available)
                                @class([
                                    'w-full mt-4 py-2.5 text-xs font-bold rounded-xl transition-all shadow-md',
                                    'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20 active:scale-95' => $this->isSelectionComplete && $teacher->is_replacement_available,
                                    'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed' => !$this->isSelectionComplete || !$teacher->is_replacement_available,
                                ])>
                                <span wire:loading.remove>
                                    {{ $this->isSelectionComplete ? 'Назначить замену' : 'Выберите предмет' }}
                                </span>
                                <span wire:loading>Обработка...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-20 text-center bg-white dark:bg-gray-900 rounded-2xl border-2 border-dashed border-gray-100 dark:border-gray-800">
            <div class="mx-auto w-16 h-16 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-400">Выберите группу</h3>
            <p class="text-sm text-gray-400 mt-1">Список преподавателей появится сразу после выбора группы</p>
        </div>
    @endif
</div>
