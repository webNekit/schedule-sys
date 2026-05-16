<div class="space-y-6">
    {{-- Шапка с редактированием названия --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('curriculum.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                
                @if($editingPlanName)
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="editedPlanName" wire:keydown.enter="savePlanName" wire:keydown.escape="cancelEditPlanName"
                            class="rounded-lg border border-emerald-500 bg-white dark:bg-gray-900 px-3 py-1.5 text-xl font-bold text-gray-900 dark:text-white ring-2 ring-emerald-500/20 outline-none w-96"
                            autofocus>
                        <button wire:click="savePlanName" class="p-2 rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors" title="Сохранить">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button wire:click="cancelEditPlanName" class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" title="Отмена">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @else
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 group cursor-pointer" wire:click="editPlanName" title="Нажмите, чтобы изменить название">
                        {{ $plan->name }}
                        <svg class="w-4 h-4 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </h1>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Специальность • {{ $plan->specialty?->short_name ?? '—' }} • Учебный год: {{ $plan->academicYear?->name ?? '—' }}</p>
        </div>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Статистика плана --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3">
            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Специальность</p>
            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->specialty?->short_name ?? '—' }}</p>
        </div>
        <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3">
            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Учебный год</p>
            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->academicYear?->name ?? '—' }}</p>
        </div>
        <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3">
            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Всего часов</p>
            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format((float)$plan->total_hours, 0, '.', ' ') }}</p>
        </div>
        <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3">
            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Дисциплин</p>
            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->disciplines->count() }}</p>
        </div>
    </div>

    {{-- Блок практик (Календарный учебный график) --}}
    @if($plan->practices && $plan->practices->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Календарный график практик</h3>
            
            {{-- Легенда практик --}}
            <div class="flex flex-wrap items-center gap-4 text-xs font-medium bg-gray-50 dark:bg-gray-900 px-3 py-1.5 rounded-lg border border-gray-100 dark:border-gray-700">
                <span class="flex items-center gap-1.5 text-indigo-700 dark:text-indigo-400"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>Учебная (У)</span>
                <span class="flex items-center gap-1.5 text-pink-700 dark:text-pink-400"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>Производственная (П)</span>
                <span class="flex items-center gap-1.5 text-amber-700 dark:text-amber-400"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Преддипломная (Пд)</span>
                <span class="flex items-center gap-1.5 text-red-700 dark:text-red-400"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Подг./Сдача ГИА (Гп/Дп)</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2.5">
            @foreach($plan->practices->sortBy(['course_number', 'start_date']) as $practice)
                @php
                    // Определяем цвет на основе символа (У, П, Пд, Гп, Дп)
                    $sym = mb_strtolower(trim((string)$practice->symbol));
                    $colorClass = 'bg-gray-50 border-gray-200 text-gray-800'; // по умолчанию
                    
                    if ($sym === 'у' || str_contains($sym, 'учеб')) {
                        $colorClass = 'bg-indigo-50 border-indigo-200 text-indigo-800 dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-300';
                    } elseif ($sym === 'п' || $sym === 'пп') {
                        $colorClass = 'bg-pink-50 border-pink-200 text-pink-800 dark:bg-pink-900/30 dark:border-pink-800 dark:text-pink-300';
                    } elseif ($sym === 'пд') {
                        $colorClass = 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300';
                    } elseif ($sym === 'гп' || $sym === 'дп' || $sym === 'г' || $sym === 'д') {
                        $colorClass = 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300';
                    }
                @endphp
                <div class="inline-flex flex-col px-3.5 py-2 rounded-lg text-sm border {{ $colorClass }}">
                    <span class="font-bold mb-0.5">
                        {{ $practice->course_number }} курс • 
                        @if(str_contains($sym, 'у')) Учебная ({{ $practice->symbol }})
                        @elseif($sym === 'пд') Преддипломная ({{ $practice->symbol }})
                        @elseif(str_contains($sym, 'п')) Производственная ({{ $practice->symbol }})
                        @else Итоговая аттестация ({{ $practice->symbol }})
                        @endif
                    </span>
                    <span class="opacity-80">
                        {{ \Carbon\Carbon::parse($practice->start_date)->format('d.m.Y') }} — 
                        {{ \Carbon\Carbon::parse($practice->end_date)->format('d.m.Y') }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Таблица с фильтрами --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="courseFilter" class="w-full sm:w-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                    <option value="">Все курсы</option>
                    @foreach($this->availableCourses as $course)
                        <option value="{{ $course }}">{{ $course }} курс</option>
                    @endforeach
                </select>
                <select wire:model.live="semesterFilter" class="w-full sm:w-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                    <option value="">Все семестры</option>
                    @foreach($this->availableSemesters as $semester)
                        <option value="{{ $semester }}">{{ $semester }} семестр</option>
                    @endforeach
                </select>
                {{-- Фильтр по преподавателю (восстановлен) --}}
                <select wire:model.live="teacherFilter" class="w-full sm:w-48 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                    <option value="">Все преподаватели</option>
                    @foreach($this->teachersForFilter as $t)
                        <option value="{{ $t->id }}">{{ $t->short_name }}</option>
                    @endforeach
                </select>
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" wire:model.live="disciplineSearch" placeholder="Поиск дисциплины..." class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 pl-10 pr-4 py-2 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                </div>
                @if($courseFilter || $semesterFilter || $teacherFilter || $disciplineSearch)
                    <button wire:click="resetFilters" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">Сбросить</button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80">
                        <th class="text-left px-4 py-3 font-medium text-gray-500 w-10">#</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 min-w-[200px]">Дисциплина</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Курс</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Сем</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12" title="Лекции">Лекц</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12" title="Практики">Прак</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12" title="Лабораторные">Лаб</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12" title="Самостоятельная работа">СРС</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-16">Всего</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500 min-w-[100px]">Контроль</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 min-w-[220px]">Преподаватель / Группа</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($semesters as $semester)
                        @php
                            $d = $semester->discipline;
                            $teacherAssignments = $d->teacherDisciplines;
                            // Проверяем, является ли это заголовком направления (код без цифр)
                            $isHeader = $this->isCycleHeader((string)$d->code);
                        @endphp
                        <tr class="transition-colors {{ $isHeader ? 'bg-gray-50 dark:bg-gray-800/60 font-medium border-t-2 border-gray-200 dark:border-gray-600' : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30' }}">
                            <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $loop->index + 1 }}</td>
                            <td class="px-4 py-2.5">
                                <div>
                                    <p class="text-gray-900 dark:text-white {{ $isHeader ? 'font-bold text-base' : 'font-medium' }}">{{ $d->name }}</p>
                                    @if($d->code)
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-px font-mono uppercase tracking-widest">{{ $d->code }}</p>
                                    @endif
                                </div>
                            </td>
                            
                            @if($isHeader)
                                {{-- Строка-заголовок, скрываем детали и кнопки --}}
                                <td colspan="7" class="text-center text-gray-400 text-xs">Всего по направлению: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $semester->hours_total }} ч.</span></td>
                                <td colspan="2"></td>
                            @else
                                {{-- Обычная дисциплина --}}
                                <td class="px-2 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->course_number }}</td>
                                <td class="px-2 py-2.5 text-center font-bold text-gray-900 dark:text-white">{{ $semester->semester_number }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_lecture ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_practice ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_lab ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_self_study ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ $semester->hours_total ?: '—' }}</td>
                                
                                <td class="px-3 py-2.5 text-center">
                                    @if($semester->controlForm)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium whitespace-nowrap {{ $semester->controlForm->is_exam_session ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $semester->controlForm->short_name ?? $semester->controlForm->name }}
                                        </span>
                                    @elseif($semester->exam_hours > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium whitespace-nowrap bg-red-100 text-red-700">Экзамен</span>
                                    @elseif($semester->course_project_hours > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium whitespace-nowrap bg-purple-100 text-purple-700">Курсовая</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach($teacherAssignments as $td)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-indigo-50 border border-indigo-100 text-[11px] text-indigo-700">
                                                <span class="font-medium">{{ $td->teacher?->short_name ?? '?' }}</span>
                                                <span class="text-indigo-400 opacity-75">({{ $td->group?->name ?? 'Все' }})</span>
                                                <button wire:click="removeAssignment({{ $td->id }})" wire:confirm="Отвязать преподавателя?" class="ml-1 text-indigo-400 hover:text-red-500 focus:outline-none">✕</button>
                                            </span>
                                        @endforeach
                                        
                                        <button wire:click="openAssignModal({{ $d->id }}, '{{ addslashes($d->name) }}')" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-500 hover:text-white transition-colors" title="Назначить преподавателя">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">Нет дисциплин по фильтрам</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Модальное окно назначения преподавателя --}}
    @if($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            wire:click.self="closeAssignModal"
            wire:key="assign-modal-{{ $teacherSearch }}">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $assignDisciplineName }}</h3>
                </div>
                <div class="p-5 space-y-4">
                <div class="relative" x-data="{ focused: false }" x-init="
                    $nextTick(() => $refs.searchInput.focus());
                    Livewire.hook('commit', ({ component, succeed }) => {
                        succeed(() => {
                            if (component.el.contains($refs.searchInput) && focused) {
                                $nextTick(() => $refs.searchInput.focus());
                            }
                        });
                    });
                ">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.500ms="teacherSearch" placeholder="Поиск преподавателя..."
                        x-on:focus="focused = true"
                        x-on:blur="focused = false"
                        x-ref="searchInput"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    </div>
                    <div class="max-h-64 overflow-y-auto -mx-5 -mb-5">
                        @forelse($searchableTeachers as $teacher)
                            <button type="button" wire:click="selectAndAssign({{ $teacher->id }})" wire:key="teacher-{{ $teacher->id }}"
                                class="w-full text-left px-5 py-2.5 text-sm transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $teacher->last_name }} {{ $teacher->first_name }} {{ $teacher->middle_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $teacher->position?->name ?? '—' }} · {{ $teacher->department?->name ?? '—' }}</div>
                            </button>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Преподаватели не найдены
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center">Нажмите на преподавателя, чтобы назначить</p>
                </div>
            </div>
        </div>
    @endif
</div>