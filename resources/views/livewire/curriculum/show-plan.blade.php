<div class="space-y-6">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

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
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Специальность</p>
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $plan->specialty?->short_name ?? '—' }}</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Учебный год</p>
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $plan->academicYear?->name ?? '—' }}</p>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Всего часов</p>
            <p class="mt-1 text-base font-semibold text-emerald-600">{{ number_format((float)$plan->total_hours, 0, '.', ' ') }}</p>
        </div>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- График учебного процесса --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white">Календарный график учебного процесса</h3>
            <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest">
                <span class="flex items-center gap-1.5 text-indigo-600"><span class="w-2 h-2 rounded-full bg-indigo-500"></span>Учебная</span>
                <span class="flex items-center gap-1.5 text-pink-600"><span class="w-2 h-2 rounded-full bg-pink-500"></span>Производственная</span>
                <span class="flex items-center gap-1.5 text-amber-600"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Преддипломная</span>
                <span class="flex items-center gap-1.5 text-purple-600"><span class="w-2 h-2 rounded-full bg-purple-500"></span>Сессия</span>
                <span class="flex items-center gap-1.5 text-red-600"><span class="w-2 h-2 rounded-full bg-red-500"></span>ГИА</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50/80 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-gray-500 font-medium">
                        <th class="px-5 py-3 text-left w-10">#</th>
                        <th class="px-2 py-3 text-center w-20">Курс</th>
                        <th class="px-4 py-3 text-left min-w-[200px]">Вид деятельности</th>
                        <th class="px-4 py-3 text-center w-48">Период</th>
                        <th class="px-4 py-3 text-center w-24">Символ</th>
                        <th class="px-4 py-3 text-right w-32">Действие</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @php
                        $calendarRows = collect();
                        
                        // Добавляем практики
                        foreach($plan->practices as $p) {
                            if ($p->type === 'exam_session') continue; // Обрабатываем сессии отдельно
                            
                            $type = $p->type;
                            $sym = mb_strtolower(trim((string)$p->symbol));
                            $color = 'text-gray-600';
                            $label = $p->name ?: 'Практика';
                            
                            if ($type === 'edu_practice' || $sym === 'у') {
                                $color = 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20';
                                $label = 'Учебная практика';
                            } elseif ($type === 'prod_practice' || $sym === 'п' || $sym === 'пп') {
                                $color = 'text-pink-600 bg-pink-50 dark:bg-pink-900/20';
                                $label = 'Производственная практика';
                            } elseif ($type === 'pre_diploma' || $sym === 'пд') {
                                $color = 'text-amber-600 bg-amber-50 dark:bg-amber-900/20';
                                $label = 'Преддипломная практика';
                            } elseif ($sym === 'гп' || $sym === 'дп') {
                                $color = 'text-red-600 bg-red-50 dark:bg-red-900/20';
                                $label = 'Государственная итоговая аттестация';
                            }
                            
                            $calendarRows->push([
                                'id' => $p->id,
                                'course' => $p->course_number,
                                'semester' => null,
                                'label' => $label,
                                'start' => $p->start_date,
                                'end' => $p->end_date,
                                'symbol' => mb_strtoupper($p->symbol),
                                'color' => $color,
                                'type' => 'practice',
                                'sort' => $p->course_number . '_' . $p->start_date
                            ]);
                        }
                        
                        // Добавляем сессии
                        $examSemesters = $plan->disciplines
                            ->flatMap(fn($d) => $d->semesters)
                            ->filter(fn($s) => $s->exam_hours > 0)
                            ->groupBy(fn($s) => $s->course_number . '_' . $s->semester_number);
                            
                        foreach($examSemesters as $key => $sems) {
                            $first = $sems->first();
                            $existingExam = $plan->practices->where('type', 'exam_session')
                                ->where('course_number', $first->course_number)
                                ->where('symbol', 'Э') // Можно добавить семестр в модель практики если нужно, но пока по курсу/символу
                                ->first();

                            $calendarRows->push([
                                'id' => $existingExam?->id,
                                'course' => $first->course_number,
                                'semester' => $first->semester_number,
                                'label' => 'Экзаменационная сессия (' . $first->semester_number . ' семестр)',
                                'start' => $existingExam?->start_date,
                                'end' => $existingExam?->end_date,
                                'symbol' => 'Э',
                                'color' => 'text-purple-600 bg-purple-50 dark:bg-purple-900/20',
                                'hours' => $sems->sum('exam_hours'),
                                'type' => 'exam',
                                'sort' => $first->course_number . '_exam_' . $first->semester_number
                            ]);
                        }
                        
                        $sortedRows = $calendarRows->sortBy('sort');
                    @endphp
                    
                    @forelse($sortedRows as $row)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-5 py-3 text-gray-400 font-mono">{{ $loop->index + 1 }}</td>
                            <td class="px-2 py-3 text-center">
                                <span class="px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 font-bold text-gray-700 dark:text-gray-300">
                                    {{ $row['course'] }} курс
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest {{ $row['color'] }}">
                                        {{ $row['label'] }}
                                    </span>
                                    @if(isset($row['hours']))
                                        <span class="text-[10px] text-gray-400 font-bold">({{ $row['hours'] }} ч.)</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 font-medium">
                                @if($row['start'] && $row['end'])
                                    {{ \Carbon\Carbon::parse($row['start'])->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($row['end'])->format('d.m.Y') }}
                                @else
                                    <span class="text-gray-300 italic">даты не назначены</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-black text-gray-900 dark:text-white">{{ $row['symbol'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($row['type'] === 'exam')
                                    <button wire:click="openExamModal({{ $row['course'] }}, {{ $row['semester'] }})" 
                                        class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-bold uppercase hover:bg-emerald-600 hover:text-white transition-all">
                                        {{ $row['start'] ? 'Изменить' : 'Назначить даты' }}
                                    </button>
                                @elseif($row['type'] === 'practice')
                                    <div class="flex flex-col items-end gap-1">
                                        @php 
                                            $practice = $plan->practices->find($row['id']);
                                            $pTeacher = $practice?->teacher;
                                        @endphp
                                        @if($pTeacher)
                                            <span class="text-[10px] font-bold text-emerald-600 mb-1">{{ $pTeacher->short_name }}</span>
                                        @endif
                                        <button wire:click="openPracticeTeacherModal({{ $row['id'] }})" 
                                            class="px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg text-[10px] font-bold uppercase hover:bg-indigo-600 hover:text-white transition-all">
                                            {{ $pTeacher ? 'Сменить преп.' : 'Назначить преп.' }}
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-400 italic">График учебного процесса не заполнен</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Список дисциплин --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50">
            <div class="flex flex-col lg:flex-row gap-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <select wire:model.live="courseFilter" class="w-full sm:w-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                        <option value="">Все курсы</option>
                        @foreach($this->availableCourses as $course)
                            <option value="{{ $course }}">{{ $course }} курс</option>
                        @endforeach
                    </select>
                    <select wire:model.live="semesterFilter" class="w-full sm:w-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                        <option value="">Все семестры</option>
                        @foreach($this->availableSemesters as $semester)
                            <option value="{{ $semester }}">{{ $semester }} семестр</option>
                        @endforeach
                    </select>
                    <select wire:model.live="teacherFilter" class="w-full sm:w-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                        <option value="">Все преподаватели</option>
                        @foreach($assignedTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->short_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 relative">
                    <input type="text" wire:model.live="disciplineSearch" placeholder="Поиск по названию или коду..." class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 pl-4 pr-4 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500 w-10">#</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 min-w-[300px]">Дисциплина</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-16">Курс/Сем</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-16">Всего (Дисц)</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12 text-[10px]">Лекц</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12 text-[10px]">Прак</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12 text-[10px]">Лаб</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-12 text-[10px]">СРС</th>
                        <th class="text-center px-2 py-3 font-medium text-gray-500 w-16 font-bold">Семестр</th>
                        <th class="text-center px-3 py-3 font-medium text-gray-500 w-24">Контроль</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500 min-w-[220px]">Нагрузка (Преподаватели)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($semesters as $semester)
                        @php
                            $d = $semester->discipline;
                            $totalDisciplineHours = $d->semesters->sum('hours_total');
                            $semesterAssignments = \App\Models\TeacherDisciplineSemester::where('curriculum_semester_id', $semester->id)
                                ->whereHas('teacherDiscipline', fn($q) => $q->where('academic_year_id', $plan->academic_year_id))
                                ->with('teacherDiscipline.teacher')
                                ->orderBy('sort_order')
                                ->get();
                            $isNonSchedulable = $this->isNonSchedulable((bool)$d->is_schedulable);
                        @endphp
                        <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-700/30 transition-colors {{ $isNonSchedulable ? 'bg-gray-50/20' : '' }}">
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $loop->index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-gray-900 dark:text-white font-medium">{{ $d->name }}</p>
                                    @if($d->code)
                                        <p class="text-[10px] text-gray-400 mt-0.5 font-mono uppercase tracking-widest">{{ $d->code }}</p>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="px-2 py-3 text-center text-gray-500 text-xs">{{ $semester->course_number }} / {{ $semester->semester_number }}</td>
                            <td class="px-2 py-3 text-center font-bold text-gray-900 dark:text-white">{{ $totalDisciplineHours }}</td>
                            
                            @if($isNonSchedulable)
                                <td colspan="4" class="text-center text-[10px] text-gray-400 uppercase italic">вне сетки</td>
                                <td class="px-2 py-3 text-center font-bold text-gray-700">{{ $semester->hours_total }}</td>
                            @else
                                <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_lecture ?: '—' }}</td>
                                <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_practice ?: '—' }}</td>
                                <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_lab ?: '—' }}</td>
                                <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">{{ $semester->hours_self_study ?: '—' }}</td>
                                <td class="px-2 py-3 text-center font-bold text-emerald-600">{{ $semester->hours_total }}</td>
                            @endif

                            <td class="px-3 py-3 text-center">
                                @if($semester->controlForm)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tight {{ $semester->controlForm->is_exam_session ? 'bg-red-100 text-red-700' : 'bg-emerald-50 text-emerald-600' }}">
                                        {{ $semester->controlForm->short_name ?? $semester->controlForm->name }}
                                    </span>
                                @endif
                            </td>
                            
                            <td class="px-4 py-3">
                                @if(!$isNonSchedulable)
                                    <div class="flex items-center gap-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($semesterAssignments as $a)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-100 dark:border-emerald-800 text-[10px] text-emerald-700 dark:text-emerald-300 font-bold" title="{{ $a->teacherDiscipline->teacher?->full_name }}">
                                                    {{ $a->teacherDiscipline->teacher?->short_name }} ({{ $a->conducted_hours }}/{{ $a->planned_hours }}ч)
                                                </span>
                                            @endforeach
                                        </div>
                                        
                                        <button wire:click="openWorkloadManager({{ $d->id }}, '{{ addslashes($d->name) }}')" 
                                            class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" 
                                            title="Управление нагрузкой">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                        </button>
                                    </div>
                                @else
                                    <div class="text-[10px] text-gray-400 uppercase italic font-medium">назначение не требуется</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-6 py-12 text-center text-gray-400 italic font-medium">Дисциплины не найдены</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Exam Dates Modal --}}
    @if($showExamModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showExamModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Назначение дат сессии</h3>
                    <button wire:click="$set('showExamModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-100 dark:border-emerald-800 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                        {{ $examCourse }} курс • {{ $examSemester }} семестр
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Начало</label>
                            <input type="date" wire:model="examStartDate" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Конец</label>
                            <input type="date" wire:model="examEndDate" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                        </div>
                    </div>
                    @error('examEndDate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button wire:click="$set('showExamModal', false)" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Отмена</button>
                    <button wire:click="saveExamDates" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">Сохранить</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Practice Teacher Modal --}}
    @if($showPracticeTeacherModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showPracticeTeacherModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Назначение на практику</h3>
                    <button wire:click="$set('showPracticeTeacherModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    @php 
                        $selectedP = $plan->practices->find($selectedPracticeId);
                    @endphp
                    @if($selectedP)
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-100 dark:border-emerald-800 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                            {{ $selectedP->course_number }} курс • {{ $selectedP->name ?: 'Практика' }}
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Выберите преподавателя</label>
                        <select wire:model="practiceTeacherId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                            <option value="">Без преподавателя</option>
                            @foreach($allTeachers as $t)
                                <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button wire:click="$set('showPracticeTeacherModal', false)" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Отмена</button>
                    <button wire:click="savePracticeTeacher" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">Сохранить</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Workload Manager Modal --}}
    @if($showWorkloadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeAssignModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $assignDisciplineName }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Управление нагрузкой по семестрам</p>
                    </div>
                    <button wire:click="closeAssignModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 flex overflow-hidden">
                    <div class="w-56 border-r border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 p-3 space-y-1 overflow-y-auto">
                        @foreach($plan->disciplines->find($assignDisciplineId)->semesters->sortBy('semester_number') as $sem)
                            <button wire:click="selectSemester({{ $sem->id }})" 
                                class="w-full text-left px-4 py-2.5 rounded-lg transition-colors text-sm font-medium
                                {{ $activeSemesterId === $sem->id ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800' }}">
                                {{ $sem->semester_number }} семестр
                            </button>
                        @endforeach
                    </div>

                    <div class="flex-1 bg-white dark:bg-gray-800 p-6 overflow-y-auto">
                        @if($activeSemesterId)
                            @php
                                $curSem = \App\Models\CurriculumSemester::find($activeSemesterId);
                                $assignments = $this->workloadState[$activeSemesterId] ?? [];
                                $totalAssigned = 0;
                                foreach($assignments as $a) $totalAssigned += (int)($a['hours'] ?? 0);
                                $remaining = $curSem->hours_total - $totalAssigned;
                            @endphp

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">План семестра</p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $curSem->hours_total }}</span>
                                            <span class="text-xs text-gray-500">час.</span>
                                        </div>
                                        <div class="mt-2 grid grid-cols-2 gap-x-2 gap-y-1 text-[10px] text-gray-500">
                                            <span>Лекции:</span> <span class="font-bold">{{ $curSem->hours_lecture ?: 0 }}</span>
                                            <span>Практ:</span> <span class="font-bold">{{ $curSem->hours_practice ?: 0 }}</span>
                                            <span>Лаб:</span> <span class="font-bold">{{ $curSem->hours_lab ?: 0 }}</span>
                                            <span>СРС:</span> <span class="font-bold">{{ $curSem->hours_self_study ?: 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-emerald-50 dark:bg-emerald-900/10 p-4 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                                        <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Распределено</p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $totalAssigned }}</span>
                                            <span class="text-xs text-emerald-500/70">час.</span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Остаток</p>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-bold {{ $remaining < 0 ? 'text-red-500' : ($remaining == 0 ? 'text-emerald-500' : 'text-gray-900 dark:text-white') }}">
                                                {{ $remaining }}
                                            </span>
                                            <span class="text-xs text-gray-500">час.</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end">
                                        <button wire:click="openAssignModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                                            + Преподаватель
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-3" x-data="{ init() { 
                                    new Sortable($refs.list, { 
                                        animation: 150, handle: '.drag', ghostClass: 'bg-indigo-50', 
                                        onEnd: (e) => { 
                                            let ids = Array.from($refs.list.children).map(el => el.getAttribute('data-index'));
                                            @this.updateSortOrder({{ $activeSemesterId }}, ids);
                                        } 
                                    }); 
                                }}">
                                    <div x-ref="list" class="space-y-2">
                                        @foreach($assignments as $idx => $data)
                                            <div wire:key="teacher-row-{{ $activeSemesterId }}-{{ $idx }}" 
                                                data-index="{{ $idx }}" 
                                                class="flex items-center gap-4 p-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 hover:border-emerald-500/30 transition-all group">
                                                <div class="drag cursor-grab active:cursor-grabbing text-gray-300 hover:text-indigo-500 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $data['teacher_name'] }}</p>
                                                </div>
                                                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 focus-within:border-emerald-500 transition-colors">
                                                    <input type="number" 
                                                        wire:model.live.debounce.300ms="workloadState.{{ $activeSemesterId }}.{{ $idx }}.hours" 
                                                        class="w-16 bg-transparent border-none text-right font-bold text-sm p-0 focus:ring-0 text-emerald-600"
                                                        min="0" step="1">
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">час.</span>
                                                </div>
                                                <button wire:click="removeTeacherFromSemester({{ $activeSemesterId }}, {{ $idx }})" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if(empty($assignments))
                                        <div class="py-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
                                            <p class="text-gray-400 text-sm">Нагрузка не распределена</p>
                                            <button wire:click="openAssignModal" class="mt-2 text-emerald-600 text-sm font-medium hover:underline">+ Добавить преподавателя</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                    <button wire:click="closeAssignModal" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 uppercase">Отмена</button>
                    <button wire:click="saveWorkload" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">Сохранить изменения</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Teacher Selection Overlay --}}
    @if($showAssignModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showAssignModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Выбор преподавателя</h3>
                    <button wire:click="$set('showAssignModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="text" wire:model.live.debounce.300ms="teacherSearch" placeholder="Поиск по фамилии..." autofocus
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm focus:border-emerald-500 outline-none transition-colors">
                    
                    <div class="max-h-64 overflow-y-auto space-y-1 custom-scrollbar pr-2">
                        @forelse($searchableTeachers as $teacher)
                            <button type="button" wire:click="selectAndAssign({{ $teacher->id }})" 
                                class="w-full text-left px-4 py-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all flex items-center justify-between group">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $teacher->last_name }} {{ $teacher->first_name }}</div>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @empty
                            <div class="py-10 text-center text-gray-400 text-sm">Преподаватели не найдены</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #10b981; border-radius: 20px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .drag { touch-action: none; }
    </style>
</div>
