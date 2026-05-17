<div class="space-y-6">
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
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-5 py-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Специальность</p>
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $plan->specialty?->short_name ?? '—' }}</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-5 py-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Учебный год</p>
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">Учебный год {{ $plan->academicYear?->name ?? '—' }}</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-5 py-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Всего часов</p>
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ number_format((float)$plan->total_hours, 0, '.', ' ') }}</p>
        </div>
    </div>

    @if($plan->practices && $plan->practices->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-gray-900 dark:text-white text-lg">Календарный график практик</h3>
            <div class="flex items-center gap-4 text-xs font-medium">
                <span class="flex items-center gap-1.5 text-indigo-600"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>Учебная (У)</span>
                <span class="flex items-center gap-1.5 text-pink-600"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>Производственная (П)</span>
                <span class="flex items-center gap-1.5 text-amber-600"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Преддипломная (Пд)</span>
                <span class="flex items-center gap-1.5 text-red-600"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Подг./Сдача ГИА (Гп/Дп)</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            @foreach($plan->practices->sortBy(['course_number', 'start_date']) as $practice)
                @php
                    $type = $practice->type;
                    $sym = mb_strtolower(trim((string)$practice->symbol));
                    
                    if ($type === 'edu_practice' || $sym === 'у') {
                        $colorClass = 'bg-indigo-50/50 border-indigo-200 text-indigo-800';
                        $label = 'Учебная (У)';
                    } elseif ($type === 'prod_practice' || $sym === 'п' || $sym === 'пп') {
                        $colorClass = 'bg-pink-50/50 border-pink-200 text-pink-800';
                        $label = 'Производственная (П)';
                    } elseif ($type === 'pre_diploma' || $sym === 'пд') {
                        $colorClass = 'bg-amber-50/50 border-amber-200 text-amber-800';
                        $label = 'Преддипломная (ПД)';
                    } elseif ($sym === 'гп') {
                        $colorClass = 'bg-red-50/50 border-red-200 text-red-800';
                        $label = 'Подготовка ГИА (ГП)';
                    } elseif ($sym === 'дп') {
                        $colorClass = 'bg-red-50/50 border-red-200 text-red-800';
                        $label = 'Сдача ГИА (ДП)';
                    } else {
                        $colorClass = 'bg-red-50/50 border-red-200 text-red-800';
                        $label = mb_strtoupper($practice->symbol);
                    }
                @endphp
                <div class="inline-flex flex-col px-4 py-2.5 rounded-xl text-sm border {{ $colorClass }}">
                    <span class="font-bold mb-1">{{ $practice->course_number }} курс • {{ $label }}</span>
                    <span class="opacity-80 font-medium tracking-wide">
                        {{ \Carbon\Carbon::parse($practice->start_date)->format('d.m.Y') }} — 
                        {{ \Carbon\Carbon::parse($practice->end_date)->format('d.m.Y') }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Экзаменационные сессии --}}
    @php
        $examSemesters = $plan->disciplines
            ->flatMap(fn($d) => $d->semesters)
            ->filter(fn($s) => $s->exam_hours > 0)
            ->groupBy('course_number')
            ->sortKeys();
    @endphp
    @if($examSemesters->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Экзаменационные сессии</h3>
            <div class="flex items-center gap-3 text-xs font-medium">
                <span class="flex items-center gap-1.5 text-purple-700 dark:text-purple-400"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>Экзамен</span>
                <span class="flex items-center gap-1.5 text-rose-700 dark:text-rose-400"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>Диф. зачёт</span>
                <span class="flex items-center gap-1.5 text-yellow-700 dark:text-yellow-400"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>Зачёт</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($examSemesters as $course => $sems)
                @php
                    $courseSems = $sems->pluck('semester_number')->unique()->sort();
                @endphp
                @foreach($courseSems as $semNum)
                    @php
                        $semSems = $sems->filter(fn($s) => $s->semester_number === $semNum);
                        $hasExam = $semSems->contains(fn($s) => $s->controlForm?->code === 'exam' || $s->controlForm?->is_exam_session);
                        $hasDiff = $semSems->contains(fn($s) => $s->controlForm?->code === 'diff_test');
                        $hasTest = $semSems->contains(fn($s) => $s->controlForm?->code === 'test');
                        $blockColor = match (true) {
                            $hasExam => 'bg-purple-50/50 border-purple-200 text-purple-800 dark:bg-purple-900/20 dark:border-purple-800 dark:text-purple-300',
                            $hasDiff => 'bg-rose-50/50 border-rose-200 text-rose-800 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-300',
                            default => 'bg-yellow-50/50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300',
                        };
                        $examNames = $semSems->filter(fn($s) => $s->controlForm?->code === 'exam' || $s->controlForm?->is_exam_session)->pluck('discipline.short_name');
                        $diffNames = $semSems->filter(fn($s) => $s->controlForm?->code === 'diff_test')->pluck('discipline.short_name');
                        $testNames = $semSems->filter(fn($s) => $s->controlForm?->code === 'test')->pluck('discipline.short_name');
                        $parts = [];
                        if ($examNames->isNotEmpty()) $parts[] = 'Экзамены: '.$examNames->implode(', ');
                        if ($diffNames->isNotEmpty()) $parts[] = 'Диф. зачёты: '.$diffNames->implode(', ');
                        if ($testNames->isNotEmpty()) $parts[] = 'Зачёты: '.$testNames->implode(', ');
                    @endphp
                    <div class="inline-flex flex-col px-4 py-2.5 rounded-xl text-sm border {{ $blockColor }}">
                        <span class="font-bold mb-0.5">{{ $course }} курс • {{ $semNum }} семестр</span>
                        <span class="text-xs opacity-80">{!! implode('<br>', $parts) !!}</span>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
    @endif

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
                <select wire:model.live="teacherFilter" class="w-full sm:w-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                    <option value="">Все преподаватели</option>
                    @foreach($this->teachersForFilter as $t)
                        <option value="{{ $t->id }}">{{ $t->short_name }}</option>
                    @endforeach
                </select>
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" wire:model.live="disciplineSearch" placeholder="Поиск дисциплины..." class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 pl-10 pr-4 py-2 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-white">
                        <th class="text-left px-4 py-3 font-medium text-gray-500 w-10">#</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 min-w-[300px]">Дисциплина</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Курс</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Сем</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Лекц</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Прак</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">Лаб</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12">СРС</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-16">Всего</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500 w-24">Контроль</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 min-w-[220px]">Преподаватель / Группа</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($semesters as $semester)
                        @php
                            $d = $semester->discipline;
                            $teacherAssignments = $d->teacherDisciplines;
                            $isNonSchedulable = $this->isNonSchedulable((bool)$d->is_schedulable);
                            $isTrueHeader = empty($semester->hours_total) || preg_match('/^ПМ\.\d+$/ui', $d->code) || in_array($d->code, ['ОП', 'ОГСЭ', 'ЕН', 'ОД']);
                        @endphp
                        <tr class="{{ $isNonSchedulable ? 'bg-gray-50/60 font-medium' : 'hover:bg-gray-50/40' }}">
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $loop->index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-gray-900 dark:text-white {{ $isNonSchedulable && $isTrueHeader ? 'font-bold' : '' }}">{{ $d->name }}</p>
                                    @if($d->code)
                                        <p class="text-[11px] text-gray-400 mt-0.5 font-mono uppercase tracking-widest">{{ $d->code }}</p>
                                    @endif
                                </div>
                            </td>
                            
                            @if($isNonSchedulable)
                                <td colspan="6"></td>
                                <td class="px-2 py-3 text-center">
                                    @if($isTrueHeader)
                                        <span class="text-xs text-gray-400 block whitespace-nowrap">Всего по циклу:</span>
                                    @else
                                        <span class="text-[10px] uppercase tracking-wide text-amber-500 block whitespace-nowrap">Вне сетки пар</span>
                                    @endif
                                    <span class="font-bold text-gray-900">{{ $semester->hours_total }} ч.</span>
                                </td>
                                <td colspan="2"></td>
                            @else
                                <td class="px-2 py-3 text-center text-gray-600">{{ $semester->course_number }}</td>
                                <td class="px-2 py-3 text-center font-bold text-gray-900">{{ $semester->semester_number }}</td>
                                <td class="px-2 py-3 text-center text-gray-600">{{ $semester->hours_lecture ?: '—' }}</td>
                                <td class="px-2 py-3 text-center text-gray-600">{{ $semester->hours_practice ?: '—' }}</td>
                                <td class="px-2 py-3 text-center text-gray-600">{{ $semester->hours_lab ?: '—' }}</td>
                                <td class="px-2 py-3 text-center text-gray-600">{{ $semester->hours_self_study ?: '—' }}</td>
                                <td class="px-2 py-3 text-center font-bold text-emerald-600">{{ $semester->hours_total ?: '—' }}</td>
                                
                                <td class="px-3 py-3 text-center">
                                    @if($semester->controlForm)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $semester->controlForm->is_exam_session ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700' }}">
                                            {{ $semester->controlForm->short_name ?? $semester->controlForm->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach($teacherAssignments as $td)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full border border-gray-200 text-[11px] text-gray-700">
                                                <span class="font-medium">{{ $td->teacher?->short_name ?? '?' }}</span>
                                                <button wire:click="removeAssignment({{ $td->id }})" wire:confirm="Отвязать преподавателя?" class="ml-1 text-gray-400 hover:text-red-500">✕</button>
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
                            <td colspan="11" class="px-6 py-12 text-center text-gray-400">Нет дисциплин по фильтрам</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeAssignModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">{{ $assignDisciplineName }}</h3>
                </div>
                <div class="p-5 space-y-4">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" wire:model.live.debounce.500ms="teacherSearch" placeholder="Поиск преподавателя..." autofocus
                            class="w-full rounded-lg border border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-1 outline-none">
                    </div>
                    <div class="max-h-64 overflow-y-auto -mx-5 -mb-5 divide-y divide-gray-100">
                        @forelse($searchableTeachers as $teacher)
                            <button type="button" wire:click="selectAndAssign({{ $teacher->id }})" class="w-full text-left px-5 py-2.5 text-sm hover:bg-emerald-50">
                                <div class="font-medium text-gray-900">{{ $teacher->last_name }} {{ $teacher->first_name }} {{ $teacher->middle_name }}</div>
                                <div class="text-xs text-gray-500">{{ $teacher->position?->name ?? '—' }}</div>
                            </button>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-gray-400">Преподаватели не найдены</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>