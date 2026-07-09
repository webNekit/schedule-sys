<div>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    {{-- Header --}}
    <div class="mb-5">
        <a href="{{ route('curriculum.index') }}"
            class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors mb-2">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Назад к специальностям
        </a>

        <div class="flex items-start justify-between gap-4">
            @if($editingPlanName)
                <div class="flex items-center gap-2">
                    <input type="text" wire:model="editedPlanName"
                        wire:keydown.enter="savePlanName"
                        wire:keydown.escape="cancelEditPlanName"
                        class="rounded-lg border border-emerald-500 bg-white dark:bg-gray-900 px-3 py-1.5 text-xl font-bold text-gray-900 dark:text-white outline-none ring-2 ring-emerald-500/20 w-96"
                        autofocus>
                    <button wire:click="savePlanName" class="text-emerald-500 hover:text-emerald-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    <button wire:click="cancelEditPlanName" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @else
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 group cursor-pointer"
                    wire:click="editPlanName" title="Нажмите для редактирования">
                    Учебный план – {{ $plan->specialty?->name ?? $plan->name }}
                    <svg class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    @if($this->isCurrentYearPlan)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                            <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                            Активный план
                        </span>
                    @endif
                </h1>
            @endif
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 px-4 py-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/40 rounded-lg text-sm text-emerald-700 dark:text-emerald-400">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 px-4 py-2.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 rounded-lg text-sm text-red-700 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- 3-Tab Navigation --}}
    <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700 mb-6">
        <button wire:click="$set('activeTab', 'plan')"
            class="pb-3 text-sm font-semibold border-b-2 transition-colors -mb-px whitespace-nowrap
            {{ $activeTab === 'plan' ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            План дисциплин
        </button>
        <button wire:click="$set('activeTab', 'calendar')"
            class="pb-3 text-sm font-semibold border-b-2 transition-colors -mb-px whitespace-nowrap
            {{ $activeTab === 'calendar' ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Календарь
        </button>
        <button wire:click="$set('activeTab', 'exams')"
            class="pb-3 text-sm font-semibold border-b-2 transition-colors -mb-px whitespace-nowrap
            {{ $activeTab === 'exams' ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Экзамены
        </button>
        <button wire:click="$set('activeTab', 'workload')"
            class="pb-3 text-sm font-semibold border-b-2 transition-colors -mb-px whitespace-nowrap
            {{ $activeTab === 'workload' ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Нагрузка преподавателей
        </button>
    </div>

    {{-- ===== PLAN TAB ===== --}}
    @if($activeTab === 'plan')

    {{-- Course tabs --}}
    <div class="flex items-center gap-5 mb-4">
        @foreach($this->availableCourses as $course)
            <button wire:click="setActiveCourse({{ $course }})"
                class="pb-2 text-sm font-semibold border-b-2 transition-colors
                {{ $activeCourse === $course
                    ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                    : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
                {{ $course }} курс
            </button>
        @endforeach
    </div>

    {{-- Semester selector --}}
    @php
        $semsInCourse = collect($this->semestersByCourse[$activeCourse] ?? []);
        $sem1 = $semsInCourse->where('semester_in_course', 1)->first();
        $sem2 = $semsInCourse->where('semester_in_course', 2)->first();
    @endphp
    <div class="flex items-center gap-3 mb-5">
        <button wire:click="setActiveSemester(1)"
            class="px-4 py-2 rounded-full text-sm font-semibold transition-all border
            {{ $activeSemesterInCourse === 1
                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white shadow-sm'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
            1 семестр@if($sem1) ({{ $sem1['hours_total'] }} ч)@endif
        </button>
        @if($sem2)
            <button wire:click="setActiveSemester(2)"
                class="px-4 py-2 rounded-full text-sm font-semibold transition-all border
                {{ $activeSemesterInCourse === 2
                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white shadow-sm'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
                2 семестр ({{ $sem2['hours_total'] }} ч)
            </button>
        @endif
    </div>

    {{-- Discipline table --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide bg-gray-50/80 dark:bg-gray-800/80">
                        <th class="px-3 py-3 text-left w-10">№</th>
                        <th class="px-3 py-3 text-left min-w-[240px]">Дисциплина</th>
                        <th class="px-2 py-3 text-center w-14">Итого</th>
                        <th class="px-2 py-3 text-center w-16 bg-amber-50 dark:bg-amber-900/10 text-amber-600 dark:text-amber-400">Конт.<br>раб.</th>
                        <th class="px-2 py-3 text-center w-10">Лек</th>
                        <th class="px-2 py-3 text-center w-10">Пр</th>
                        <th class="px-2 py-3 text-center w-10">Лаб</th>
                        <th class="px-2 py-3 text-center w-10">Конс</th>
                        <th class="px-2 py-3 text-center w-10">СР</th>
                        <th class="px-3 py-3 text-center w-20">Аттестация</th>
                        <th class="px-3 py-3 text-left min-w-[200px]">Преподаватели</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @php $rowNum = 0; @endphp
                    @forelse($semesters as $semester)
                        @php
                            $rowNum++;
                            $d = $semester->discipline;
                            $contactHours = ($semester->hours_lecture ?: 0)
                                + ($semester->hours_practice ?: 0)
                                + ($semester->hours_lab ?: 0)
                                + ($semester->hours_consultation ?: 0);
                            $isNonSchedulable = $this->isNonSchedulable((bool)$d->is_schedulable);
                            $semesterAssignments = \App\Models\TeacherDisciplineSemester::where('curriculum_semester_id', $semester->id)
                                ->whereHas('teacherDiscipline', fn($q) => $q->where('academic_year_id', $plan->academic_year_id))
                                ->with('teacherDiscipline.teacher')
                                ->orderBy('sort_order')
                                ->get();
                            // Параллельные занятия: преподаватели не делят часы — берём максимум, а не сумму.
                            $totalAssigned = $d->is_parallel
                                ? (int) ($semesterAssignments->max('planned_hours') ?? 0)
                                : $semesterAssignments->sum('planned_hours');
                            $remaining = $semester->hours_total - $totalAssigned;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-3 py-2.5 text-xs text-gray-400">{{ $d->code ?? $rowNum }}</td>
                            <td class="px-3 py-2.5">
                                <p class="font-medium text-gray-900 dark:text-white leading-snug">{{ $d->name }}</p>
                                @if($d->code)
                                    <p class="text-[9px] text-gray-400 mt-0.5 font-mono uppercase tracking-wider">{{ $d->code }}</p>
                                @endif
                            </td>

                            <td class="px-2 py-2.5 text-center font-bold text-gray-800 dark:text-gray-200 text-sm">{{ $semester->hours_total }}</td>
                            <td class="px-2 py-2.5 text-center font-bold text-amber-600 dark:text-amber-400 bg-amber-50/40 dark:bg-amber-900/10 text-sm">{{ $contactHours ?: '—' }}</td>

                            @if($isNonSchedulable)
                                <td colspan="4" class="px-2 py-2.5 text-center text-[10px] text-gray-400 dark:text-gray-500 italic">вне сетки</td>
                            @else
                                <td class="px-2 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_lecture ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_practice ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_lab ?: '—' }}</td>
                                <td class="px-2 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_consultation ?: '—' }}</td>
                            @endif

                            <td class="px-2 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_self_study ?: '—' }}</td>

                            <td class="px-3 py-2.5 text-center">
                                @if($semester->controlForm)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold
                                        {{ $semester->controlForm->is_exam_session
                                            ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                            : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $semester->controlForm->short_name ?? $semester->controlForm->name }}
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>

                            <td class="px-3 py-2.5">
                                @if(!$isNonSchedulable)
                                    <div class="space-y-1.5 min-w-[180px]">
                                        @foreach($semesterAssignments as $a)
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    @if($semesterAssignments->count() > 1)
                                                        <div class="flex flex-col shrink-0">
                                                            <button wire:click="moveTeacherInSemester({{ $semester->id }}, {{ $a->id }}, 'up')"
                                                                class="text-gray-300 dark:text-gray-600 hover:text-gray-600 dark:hover:text-gray-300 transition-colors disabled:opacity-20 leading-none"
                                                                @if($loop->first) disabled @endif
                                                                title="Переместить выше">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                            </button>
                                                            <button wire:click="moveTeacherInSemester({{ $semester->id }}, {{ $a->id }}, 'down')"
                                                                class="text-gray-300 dark:text-gray-600 hover:text-gray-600 dark:hover:text-gray-300 transition-colors disabled:opacity-20 leading-none"
                                                                @if($loop->last) disabled @endif
                                                                title="Переместить ниже">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                            </button>
                                                        </div>
                                                    @endif
                                                    <span class="w-5 h-5 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    </span>
                                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $a->teacherDiscipline->teacher?->short_name }}</span>
                                                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 shrink-0">{{ $a->planned_hours }} ч</span>
                                                </div>
                                                <button wire:click="removeAssignment({{ $a->teacher_discipline_id }})"
                                                    class="shrink-0 text-gray-300 dark:text-gray-600 hover:text-red-400 dark:hover:text-red-400 transition-colors"
                                                    title="Удалить">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                        <div class="flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500 pt-0.5">
                                            <span>Остаток: <span class="{{ $remaining < 0 ? 'text-red-500' : ($remaining == 0 ? 'text-emerald-500' : 'text-gray-500 dark:text-gray-400') }} font-semibold">{{ $remaining }} ч</span></span>
                                            <span>из {{ $semester->hours_total }}</span>
                                        </div>
                                        <button wire:click="openWorkloadManager({{ $d->id }}, '{{ addslashes($d->name) }}')"
                                            class="text-[11px] text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            Назначить преподавателя
                                        </button>
                                    </div>
                                @else
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 italic">не требуется</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-gray-400 italic">Дисциплины не найдены</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif {{-- end plan tab --}}

    {{-- ===== CALENDAR TAB ===== --}}
    @if($activeTab === 'calendar')
    @php
        $cal = $this->calendarGrid;
        $calWeeks = $cal['weeks'];
        $calMonths = $cal['months'];
        $calGrid = $cal['grid'];
        $calCourses = $cal['courses'];
        $cellClasses = [
            'practice' => 'bg-lime-700/70 dark:bg-lime-800/80 text-lime-100 dark:text-lime-200',
            'exam'     => 'bg-red-600/70 dark:bg-red-800/70 text-red-100 dark:text-red-200',
            'vacation' => 'bg-teal-600/60 dark:bg-teal-800/60 text-teal-100 dark:text-teal-200',
        ];
    @endphp

    {{-- Weekly grid --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm mb-6">
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Календарный план</h3>
            <div class="flex items-center gap-3 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <span class="w-4 h-4 rounded flex items-center justify-center bg-lime-700/70 dark:bg-lime-800/80 text-lime-100 text-[9px] font-bold">У</span> Учебная
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-4 h-4 rounded flex items-center justify-center bg-lime-700/70 dark:bg-lime-800/80 text-lime-100 text-[9px] font-bold">П</span> Производственная
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-4 h-4 rounded flex items-center justify-center bg-red-600/70 dark:bg-red-800/70 text-red-100 text-[9px] font-bold">Э</span> Сессия
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-4 h-4 rounded flex items-center justify-center bg-teal-600/60 dark:bg-teal-800/60 text-teal-100 text-[9px] font-bold">К</span> Каникулы
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="text-[10px] border-collapse" style="min-width: max-content;">
                <thead>
                    {{-- Month headers --}}
                    <tr>
                        <th class="sticky left-0 z-10 px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-700 min-w-[56px]">Курс</th>
                        @foreach($calMonths as $mName => $mData)
                            <th colspan="{{ $mData['count'] }}"
                                class="px-1 py-2 text-center text-[11px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-50/80 dark:bg-gray-900/80 border-b border-r border-gray-200 dark:border-gray-700">
                                {{ $mName }}
                            </th>
                        @endforeach
                    </tr>
                    {{-- Date ranges --}}
                    <tr>
                        <th class="sticky left-0 z-10 px-3 py-1.5 text-left text-[9px] font-medium text-gray-400 bg-gray-50/80 dark:bg-gray-900/80 border-b border-r border-gray-200 dark:border-gray-700">Числа</th>
                        @foreach($calWeeks as $wNum => $week)
                            <th class="px-0.5 py-1.5 text-center text-[9px] font-medium text-gray-400 dark:text-gray-500 bg-gray-50/60 dark:bg-gray-900/60 border-b border-r border-gray-100 dark:border-gray-700/40 w-8">
                                {{ $week['label'] }}
                            </th>
                        @endforeach
                    </tr>
                    {{-- Week numbers --}}
                    <tr>
                        <th class="sticky left-0 z-10 px-3 py-1 text-left text-[9px] font-medium text-gray-400 bg-gray-50/60 dark:bg-gray-900/60 border-b border-r border-gray-200 dark:border-gray-700">Нед</th>
                        @foreach($calWeeks as $wNum => $week)
                            <th class="px-0.5 py-1 text-center text-[9px] text-gray-400 dark:text-gray-600 bg-gray-50/40 dark:bg-gray-900/40 border-b border-r border-gray-100 dark:border-gray-700/30">
                                {{ $wNum }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($calCourses as $course)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50/30 dark:hover:bg-gray-700/10 transition-colors">
                            <td class="sticky left-0 z-10 px-3 py-2 font-bold text-sm text-gray-600 dark:text-gray-400 bg-gray-50/60 dark:bg-gray-900/60 border-r border-gray-200 dark:border-gray-700">
                                {{ $course }}
                            </td>
                            @foreach($calWeeks as $wNum => $week)
                                @php $cell = $calGrid[$course][$wNum] ?? null; @endphp
                                <td class="py-1.5 text-center border-r border-gray-100 dark:border-gray-700/30 w-8">
                                    @if($cell)
                                        <span class="inline-flex items-center justify-center w-7 h-5 rounded text-[9px] font-bold {{ $cellClasses[$cell['type']] ?? 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                                            {{ $cell['symbol'] }}
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    @if(empty($calCourses))
                        <tr>
                            <td class="px-6 py-8 text-center text-gray-400 italic text-sm" style="grid-column: span 53;">
                                Данные календарного плана отсутствуют. Импортируйте учебный план из Excel.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Practice list --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300">Практики и сессии</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-2.5 text-left w-24">Курс</th>
                    <th class="px-4 py-2.5 text-left">Вид деятельности</th>
                    <th class="px-4 py-2.5 text-center w-12">Симв.</th>
                    <th class="px-4 py-2.5 text-center w-48">Период</th>
                    <th class="px-4 py-2.5 text-right w-40">Действие</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @php
                    $calendarRows = collect();
                    foreach($plan->practices as $p) {
                        if ($p->type === 'exam_session') continue;
                        $sym = mb_strtolower(trim((string)$p->symbol));
                        $label = match(true) {
                            $p->type === 'edu_practice' || $sym === 'у'      => 'Учебная практика',
                            $p->type === 'prod_practice' || in_array($sym, ['п', 'пп']) => 'Производственная практика',
                            $p->type === 'pre_diploma'  || $sym === 'пд'     => 'Преддипломная практика',
                            in_array($sym, ['гп', 'дп'])                     => 'Государственная итоговая аттестация',
                            default                                           => ($p->name ?: 'Практика'),
                        };
                        $calendarRows->push([
                            'id' => $p->id,
                            'course' => $p->course_number,
                            'label' => $label,
                            'start' => $p->start_date,
                            'end' => $p->end_date,
                            'symbol' => mb_strtoupper((string)$p->symbol),
                            'type_row' => 'practice',
                            'sort' => $p->course_number . '_' . $p->start_date,
                        ]);
                    }
                    $examSemesters = $plan->disciplines
                        ->flatMap(fn($d) => $d->semesters)
                        ->filter(fn($s) => $s->exam_hours > 0)
                        ->groupBy(fn($s) => $s->course_number . '_' . $s->semester_number);
                    foreach($examSemesters as $key => $sems) {
                        $first = $sems->first();
                        $existingExam = $plan->practices
                            ->where('type', 'exam_session')
                            ->where('course_number', $first->course_number)
                            ->where('symbol', 'Э')
                            ->first();
                        $calendarRows->push([
                            'id' => $existingExam?->id,
                            'course' => $first->course_number,
                            'semester' => $first->semester_number,
                            'label' => 'Экзаменационная сессия (' . $first->semester_number . ' семестр)',
                            'start' => $existingExam?->start_date,
                            'end' => $existingExam?->end_date,
                            'symbol' => 'Э',
                            'type_row' => 'exam',
                            'sort' => $first->course_number . '_exam_' . $first->semester_number,
                        ]);
                    }
                    $sortedCalRows = $calendarRows->sortBy('sort');
                @endphp

                @forelse($sortedCalRows as $row)
                    <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-700/20 transition-colors">
                        <td class="px-4 py-2.5">
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300">{{ $row['course'] }} курс</span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300 font-medium">{{ $row['label'] }}</td>
                        <td class="px-4 py-2.5 text-center font-black text-gray-800 dark:text-gray-200">{{ $row['symbol'] }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-500 dark:text-gray-400 text-xs">
                            @if(isset($row['start'], $row['end']) && $row['start'] && $row['end'])
                                {{ \Carbon\Carbon::parse($row['start'])->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($row['end'])->format('d.m.Y') }}
                            @else
                                <span class="text-gray-300 dark:text-gray-600 italic">не назначено</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if($row['type_row'] === 'exam')
                                <button wire:click="openExamModal({{ $row['course'] }}, {{ $row['semester'] }})"
                                    class="px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-gray-900 transition-all">
                                    {{ isset($row['start']) && $row['start'] ? 'Изменить' : 'Назначить даты' }}
                                </button>
                            @else
                                @php
                                    $practice = $plan->practices->find($row['id']);
                                    $pTeacher = $practice?->teacher;
                                @endphp
                                <div class="flex flex-col items-end gap-1">
                                    @if($pTeacher)
                                        <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">{{ $pTeacher->short_name }}</span>
                                    @endif
                                    <button wire:click="openPracticeTeacherModal({{ $row['id'] }})"
                                        class="px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-gray-900 transition-all">
                                        {{ $pTeacher ? 'Сменить преп.' : 'Назначить преп.' }}
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic text-sm">Нет данных о практиках и сессиях</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif {{-- end calendar tab --}}

    {{-- ===== EXAMS TAB ===== --}}
    @if($activeTab === 'exams')
    @php
        $examRows = collect($this->examScheduleRows);
        $roomOptions = $this->examRoomOptions;
        // Абсолютный номер семестра для выбранного курса и семестра-в-курсе
        $absSemester = ($activeCourse - 1) * 2 + $activeSemesterInCourse;
        $currentRows = $examRows->where('course', $activeCourse)->where('semester', $absSemester)->values();
    @endphp

    {{-- Course tabs (как в плане дисциплин) --}}
    <div class="flex items-center gap-5 mb-4">
        @foreach($this->availableCourses as $course)
            <button wire:click="setActiveCourse({{ $course }})"
                class="pb-2 text-sm font-semibold border-b-2 transition-colors
                {{ $activeCourse === $course
                    ? 'border-gray-900 dark:border-white text-gray-900 dark:text-white'
                    : 'border-transparent text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
                {{ $course }} курс
            </button>
        @endforeach
    </div>

    {{-- Semester selector --}}
    @php
        $semsInCourse = collect($this->semestersByCourse[$activeCourse] ?? []);
        $sem1 = $semsInCourse->where('semester_in_course', 1)->first();
        $sem2 = $semsInCourse->where('semester_in_course', 2)->first();
    @endphp
    <div class="flex items-center gap-3 mb-5">
        <button wire:click="setActiveSemester(1)"
            class="px-4 py-2 rounded-full text-sm font-semibold transition-all border
            {{ $activeSemesterInCourse === 1
                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white shadow-sm'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
            1 семестр
        </button>
        @if($sem2)
            <button wire:click="setActiveSemester(2)"
                class="px-4 py-2 rounded-full text-sm font-semibold transition-all border
                {{ $activeSemesterInCourse === 2
                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white shadow-sm'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}">
                2 семестр
            </button>
        @endif
    </div>

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Расписание экзаменов — {{ $activeCourse }} курс, {{ $absSemester }} семестр</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Назначьте точную дату каждому экзамену — генератор поставит его строго в этот день</p>
        </div>

        @if($currentRows->isEmpty())
            <div class="px-5 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                Нет дисциплин с экзаменами в этом семестре.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/40 text-left text-xs text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5 font-medium">Дисциплина</th>
                            <th class="px-4 py-2.5 font-medium w-44">Дата экзамена</th>
                            <th class="px-4 py-2.5 font-medium w-56">Аудитория</th>
                            <th class="px-4 py-2.5 font-medium w-40">Статус</th>
                            <th class="px-4 py-2.5 font-medium w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($currentRows as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                                <td class="px-4 py-2.5 text-gray-900 dark:text-gray-100">{{ $row['discipline_name'] }}</td>
                                <td class="px-4 py-2.5">
                                    @if($row['has_session'])
                                        @if($row['is_module'])
                                            {{-- Модульный экзамен: дату выбирает пользователь, индикатор показывает завершение МДК/УП/ПП --}}
                                            <div x-data="{ d: @js($row['exam_date']), prereq: @js($row['prereq_date']) }">
                                                <input type="date"
                                                    wire:model="examDates.{{ $row['semester_id'] }}"
                                                    x-on:input="d = $event.target.value"
                                                    value="{{ $row['exam_date'] }}"
                                                    min="{{ $row['min_date'] }}"
                                                    max="{{ $row['max_date'] }}"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                                                @if($row['prereq_date'])
                                                    <p x-show="d && d >= prereq" x-cloak class="text-[10px] text-emerald-500 mt-0.5">✓ МДК, УП и ПП завершены</p>
                                                    <p x-show="!d || d < prereq" class="text-[10px] text-amber-500 mt-0.5">⚠ МДК, УП и ПП ещё не завершены (до {{ \Carbon\Carbon::parse($row['prereq_date'])->format('d.m.Y') }})</p>
                                                @else
                                                    <p class="text-[10px] text-gray-400 mt-0.5">практики в графике не заданы</p>
                                                @endif
                                            </div>
                                        @else
                                            <input type="date"
                                                wire:model="examDates.{{ $row['semester_id'] }}"
                                                value="{{ $row['exam_date'] }}"
                                                min="{{ $row['min_date'] }}"
                                                max="{{ $row['max_date'] }}"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                                            <p class="text-[10px] text-gray-400 mt-0.5">сессия: {{ \Carbon\Carbon::parse($row['min_date'])->format('d.m') }}–{{ \Carbon\Carbon::parse($row['max_date'])->format('d.m.Y') }}</p>
                                        @endif
                                    @else
                                        <span class="text-[11px] text-amber-500">Сессия не задана в графике</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <select wire:model="examRooms.{{ $row['semester_id'] }}"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                                        <option value="">— не задана —</option>
                                        @foreach($roomOptions as $opt)
                                            <option value="{{ $opt['id'] }}" @selected((string)$row['room_id'] === (string)$opt['id'])>{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($row['saved'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            ✓ {{ $row['saved_date'] }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">не назначен</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    <button wire:click="saveExamSchedule({{ $row['semester_id'] }})"
                                        class="px-2.5 py-1 text-xs rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">Сохранить</button>
                                    @if($row['saved'])
                                        <button wire:click="clearExamSchedule({{ $row['semester_id'] }})"
                                            class="px-2 py-1 text-xs rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">✕</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endif {{-- end exams tab --}}

    {{-- ===== WORKLOAD TAB ===== --}}
    @if($activeTab === 'workload')
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Нагрузка преподавателей</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $plan->academicYear?->name ?? '—' }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Преподаватель</th>
                        <th class="px-4 py-3 text-center w-28">Запланировано</th>
                        <th class="px-4 py-3 text-center w-24">Проведено</th>
                        <th class="px-4 py-3 text-left">Дисциплины</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($this->teacherWorkloadSummary as $teacher)
                        @php
                            $teacherDisciplines = $teacher->disciplines;
                            $totalPlanned = 0;
                            $totalConducted = 0;
                            $disciplineNames = [];
                            foreach($teacherDisciplines as $td) {
                                foreach($td->semesters as $tds) {
                                    $totalPlanned += $tds->planned_hours;
                                    $totalConducted += $tds->actual_hours;
                                }
                                if($td->discipline) {
                                    $disciplineNames[] = $td->discipline->name;
                                }
                            }
                            $disciplineNames = array_unique($disciplineNames);
                        @endphp
                        <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">{{ $teacher->full_name }}</p>
                                        @if($teacher->position)
                                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $teacher->position?->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-gray-800 dark:text-gray-200">{{ $totalPlanned }} ч</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold {{ $totalConducted >= $totalPlanned ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $totalConducted }} ч
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach(array_slice($disciplineNames, 0, 4) as $dName)
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[11px] text-gray-600 dark:text-gray-400 max-w-[200px] truncate">{{ $dName }}</span>
                                    @endforeach
                                    @if(count($disciplineNames) > 4)
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[11px] text-gray-500">+{{ count($disciplineNames) - 4 }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                                Преподаватели ещё не назначены. Перейдите в раздел «План дисциплин» для назначения.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif {{-- end workload tab --}}

    {{-- ===== MODALS ===== --}}

    {{-- Exam Dates Modal --}}
    @if($showExamModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            wire:click.self="$set('showExamModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Даты экзаменационной сессии</h3>
                    <button wire:click="$set('showExamModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $examCourse }} курс · {{ $examSemester }} семестр
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Начало</label>
                            <input type="date" wire:model="examStartDate"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-gray-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Конец</label>
                            <input type="date" wire:model="examEndDate"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-gray-500 outline-none transition-colors">
                        </div>
                    </div>
                    @error('examEndDate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button wire:click="$set('showExamModal', false)" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Отмена</button>
                    <button wire:click="saveExamDates" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 dark:text-gray-900 text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">Сохранить</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Practice Teacher Modal --}}
    @if($showPracticeTeacherModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            wire:click.self="$set('showPracticeTeacherModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Назначение на практику</h3>
                    <button wire:click="$set('showPracticeTeacherModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    @php $selectedP = $plan->practices->find($selectedPracticeId); @endphp
                    @if($selectedP)
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $selectedP->course_number }} курс · {{ $selectedP->name ?: 'Практика' }}
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Преподаватель</label>
                        <select wire:model="practiceTeacherId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-gray-500 outline-none transition-colors">
                            <option value="">Без преподавателя</option>
                            @foreach($allTeachers as $t)
                                <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button wire:click="$set('showPracticeTeacherModal', false)" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Отмена</button>
                    <button wire:click="savePracticeTeacher" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 dark:text-gray-900 text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">Сохранить</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Workload Manager Modal --}}
    @if($showWorkloadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            wire:click.self="closeAssignModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $assignDisciplineName }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Управление нагрузкой по семестрам</p>
                        <label class="mt-2 inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" wire:model.live="disciplineParallel"
                                class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Параллельные занятия</span>
                            <span class="text-[10px] text-gray-400">— преподаватели ведут одновременно, не деля часы</span>
                        </label>
                        <div class="mt-2 inline-flex items-center gap-2">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Категория:</span>
                            <select wire:model.live="disciplineCategory"
                                class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-xs px-2 py-1">
                                <option value="general">Обычная</option>
                                <option value="pe">Физкультура</option>
                                <option value="practice">Практика</option>
                                <option value="exam">Экзамен/зачёт</option>
                            </select>
                            <span class="text-[10px] text-gray-400">— как генератор трактует дисциплину</span>
                        </div>
                    </div>
                    <button wire:click="closeAssignModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 flex overflow-hidden">
                    {{-- Semester sidebar --}}
                    <div class="w-52 border-r border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 p-3 space-y-1 overflow-y-auto">
                        @foreach($plan->disciplines->find($assignDisciplineId)->semesters->sortBy('semester_number') as $sem)
                            <button wire:click="selectSemester({{ $sem->id }})"
                                class="w-full text-left px-4 py-2.5 rounded-lg transition-colors text-sm font-medium
                                {{ $activeSemesterId === $sem->id
                                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800' }}">
                                {{ $sem->semester_number }} семестр
                            </button>
                        @endforeach
                    </div>

                    {{-- Main area --}}
                    <div class="flex-1 bg-white dark:bg-gray-800 p-6 overflow-y-auto">
                        @if($activeSemesterId)
                            @php
                                $curSem = \App\Models\CurriculumSemester::find($activeSemesterId);
                                $assignments = $this->workloadState[$activeSemesterId] ?? [];
                                // Параллельные занятия: преподаватели не делят часы — берём максимум, а не сумму.
                                $totalAssignedModal = $disciplineParallel
                                    ? (int) (collect($assignments)->max(fn($a) => (int)($a['hours'] ?? 0)) ?? 0)
                                    : array_sum(array_map(fn($a) => (int)($a['hours'] ?? 0), $assignments));
                                $remainingModal = $curSem->hours_total - $totalAssignedModal;
                            @endphp
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">План семестра</p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $curSem->hours_total }}</span>
                                            <span class="text-xs text-gray-500">ч.</span>
                                        </div>
                                        <div class="mt-1.5 grid grid-cols-2 gap-x-2 text-[10px] text-gray-500">
                                            <span>Лекции:</span><span class="font-bold">{{ $curSem->hours_lecture ?: 0 }}</span>
                                            <span>Практ:</span><span class="font-bold">{{ $curSem->hours_practice ?: 0 }}</span>
                                            <span>Лаб:</span><span class="font-bold">{{ $curSem->hours_lab ?: 0 }}</span>
                                            <span>СРС:</span><span class="font-bold">{{ $curSem->hours_self_study ?: 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-emerald-50 dark:bg-emerald-900/10 p-4 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                                        <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Распределено</p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $totalAssignedModal }}</span>
                                            <span class="text-xs text-emerald-500/70">ч.</span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Остаток</p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-bold {{ $remainingModal < 0 ? 'text-red-500' : ($remainingModal == 0 ? 'text-emerald-500' : 'text-gray-900 dark:text-white') }}">
                                                {{ $remainingModal }}
                                            </span>
                                            <span class="text-xs text-gray-500">ч.</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-center">
                                        <button wire:click="openAssignModal"
                                            class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 dark:text-gray-900 text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                                            + Преподаватель
                                        </button>
                                    </div>
                                </div>

                                <div x-data="{
                                    init() {
                                        new Sortable($refs.list, {
                                            animation: 150,
                                            handle: '.drag-handle',
                                            ghostClass: 'bg-indigo-50 dark:bg-indigo-900/20',
                                            onEnd: (e) => {
                                                let ids = Array.from($refs.list.children).map(el => el.getAttribute('data-index'));
                                                @this.updateSortOrder({{ $activeSemesterId }}, ids);
                                            }
                                        });
                                    }
                                }">
                                    <div x-ref="list" class="space-y-2">
                                        @foreach($assignments as $idx => $data)
                                            <div wire:key="teacher-row-{{ $activeSemesterId }}-{{ $idx }}"
                                                data-index="{{ $idx }}"
                                                class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-600 transition-all">
                                                <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 dark:hover:text-gray-400 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $data['teacher_name'] }}</p>
                                                </div>
                                                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 focus-within:border-gray-400 transition-colors">
                                                    <input type="number"
                                                        wire:model.live.debounce.300ms="workloadState.{{ $activeSemesterId }}.{{ $idx }}.hours"
                                                        class="w-16 bg-transparent border-none text-right font-bold text-sm p-0 focus:ring-0 text-emerald-600 dark:text-emerald-400"
                                                        min="0" step="1">
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">ч.</span>
                                                </div>
                                                <button wire:click="removeTeacherFromSemester({{ $activeSemesterId }}, {{ $idx }})"
                                                    class="p-2 text-gray-300 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if(empty($assignments))
                                        <div class="py-10 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
                                            <p class="text-sm text-gray-400">Нагрузка не распределена</p>
                                            <button wire:click="openAssignModal" class="mt-2 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:underline">+ Добавить преподавателя</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button wire:click="closeAssignModal" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Отмена</button>
                    <button wire:click="saveWorkload" class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 dark:text-gray-900 text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">Сохранить изменения</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Teacher Selection Overlay --}}
    @if($showAssignModal)
        <div class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            wire:click.self="$set('showAssignModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Выбор преподавателя</h3>
                    <button wire:click="$set('showAssignModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5 space-y-3">
                    <input type="text" wire:model.live.debounce.300ms="teacherSearch"
                        placeholder="Поиск по фамилии..."
                        autofocus
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm focus:border-gray-500 outline-none transition-colors">
                    <div class="max-h-60 overflow-y-auto space-y-0.5 pr-1" style="-webkit-overflow-scrolling: touch; scrollbar-width: thin;">
                        @forelse($searchableTeachers as $teacher)
                            <button type="button" wire:click="selectAndAssign({{ $teacher->id }})"
                                class="w-full text-left px-4 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all flex items-center justify-between group">
                                <span class="text-sm font-medium text-gray-800 dark:text-white">{{ $teacher->last_name }} {{ $teacher->first_name }}</span>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @empty
                            <p class="py-8 text-center text-sm text-gray-400">Преподаватели не найдены</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
