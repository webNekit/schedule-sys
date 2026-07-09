<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('teachers.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700 mb-1 inline-block">← Назад к преподавателям</a>
            <h2 class="text-2xl font-bold">{{ $teacher->last_name }} {{ $teacher->first_name }} {{ $teacher->middle_name }}</h2>
            <p class="text-gray-500 dark:text-gray-400">{{ $teacher->position?->name }} · {{ $teacher->department?->name }}</p>
        </div>
        <button wire:click="openTeacherForm" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">Редактировать</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Кафедра</div>
            <div class="text-lg font-semibold mt-1">{{ $teacher->department?->name ?? '—' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Должность</div>
            <div class="text-lg font-semibold mt-1">{{ $teacher->position?->name ?? '—' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ставка</div>
            <div class="text-lg font-semibold mt-1">{{ $teacher->rate }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Тип занятости</div>
            <div class="text-lg font-semibold mt-1">
                @switch($teacher->employment_type)
                    @case('full_time') Штатный @break
                    @case('part_time') Совместитель @break
                    @case('hourly') Почасовик @break
                    @default {{ $teacher->employment_type }}
                @endswitch
            </div>
        </div>
    </div>

    {{-- Рабочие дни и пары преподавателя --}}
    @php
        $wdLabels = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб'];
        $twDays = $teacher->working_days ?? [];
        $twLessonNumbers = $teacher->working_lesson_numbers ?? [];
        $isPerDay = is_array($twLessonNumbers) && !empty($twLessonNumbers) && is_array(reset($twLessonNumbers));
    @endphp
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Рабочие дни и пары</h3>
        </div>
        @if(!empty($twDays))
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach ($twDays as $dayNum)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg border text-sm font-medium bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300">
                        {{ $wdLabels[$dayNum] ?? $dayNum }}
                    </span>
                @endforeach
            </div>
            @if($isPerDay)
                <div class="space-y-2">
                    @foreach ($twDays as $dayNum)
                        @php $daySlots = $twLessonNumbers[$dayNum] ?? []; @endphp
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gray-500 uppercase w-8">{{ $wdLabels[$dayNum] ?? $dayNum }}</span>
                            <div class="flex gap-1">
                                @foreach (range(1, 7) as $num)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded text-xs font-medium
                                        {{ in_array($num, $daySlots) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-300 dark:bg-gray-700 dark:text-gray-600' }}">
                                        {{ $num }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif(!empty($twLessonNumbers))
                <div class="flex flex-wrap gap-1 mb-2">
                    @foreach (range(1, 7) as $num)
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded text-xs font-medium
                            {{ in_array($num, $twLessonNumbers) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-300 dark:bg-gray-700 dark:text-gray-600' }}">
                            {{ $num }}
                        </span>
                    @endforeach
                </div>
            @endif
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Не настроены. <a wire:click="openTeacherForm" class="text-emerald-600 hover:text-emerald-800 cursor-pointer">Настроить в редактировании</a></p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Корпуса и аудитории</h3>
            <button wire:click="openBuildingForm" class="text-sm text-emerald-600 hover:text-emerald-800">+ Привязать корпус</button>
        </div>
        @if($teacher->buildings->isNotEmpty())
            <div class="space-y-3">
                @foreach($allBuildings as $building)
                    @php $tb = $teacher->buildings->firstWhere('building_id', $building->id); @endphp
                    @if($tb)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $building->name }}</span>
                                    @if($tb->is_primary)
                                        <span class="text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded">Основной</span>
                                    @endif
                                </div>
                                <button wire:click="removeBuilding({{ $building->id }})" wire:confirm="Отвязать корпус?" class="text-xs text-red-600 hover:text-red-800">Удалить</button>
                            </div>
                            @php
                                $teacherRooms = $teacher->rooms->where('room.building_id', $building->id);
                            @endphp
                            @if($teacherRooms->isNotEmpty())
                                <div class="mt-2 ml-4 space-y-1">
                                    @foreach($teacherRooms as $tr)
                                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                            <span class="flex items-center gap-2">
                                                <span class="text-gray-400">└</span>
                                                {{ $tr->room?->number }} — {{ $tr->room?->name ?? $tr->room?->roomType?->name ?? '' }}
                                                @if($tr->is_personal)
                                                    <span class="text-xs text-emerald-600">(личная)</span>
                                                @endif
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-400">приор. {{ $tr->priority }}</span>
                                                <button wire:click="removeRoom({{ $tr->room_id }})" wire:confirm="Отвязать аудиторию?" class="text-xs text-red-600 hover:text-red-800">Удалить</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-2 ml-4">
                                <button wire:click="$set('selectedBuildingId', {{ $building->id }}); $set('showBuildingForm', true)" class="text-xs text-emerald-600 hover:text-emerald-800">+ Добавить аудиторию в {{ $building->short_name ?? $building->name }}</button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Не назначены</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button wire:click="switchTab('groups')"
                    class="px-6 py-3 text-sm font-medium transition border-b-2 {{ $activeTab === 'groups' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Группы ({{ $groups->count() }})
            </button>
            <button wire:click="switchTab('info')"
                    class="px-6 py-3 text-sm font-medium transition border-b-2 {{ $activeTab === 'info' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Нагрузка ({{ $workloads->count() }})
            </button>
        </div>

        <div class="p-4">
            @if($activeTab === 'groups')
                @if($groups->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($groups as $g)
                            <a href="{{ route('groups.show', $g) }}"
                               class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-emerald-300 dark:hover:border-emerald-700 transition">
                                <div class="font-medium">{{ $g->name }}</div>
                                <div class="text-sm text-gray-500">{{ $g->specialty?->short_name ?? $g->specialty?->name }}</div>
                                <div class="text-xs text-gray-400 mt-1">{{ $g->current_course }} курс</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Нет назначенных групп</p>
                @endif
            @else
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Нагрузка на {{ $currentAcademicYear?->name ?? 'текущий год' }}</h4>
                        <button wire:click="openDisciplineForm" class="text-sm text-emerald-600 hover:text-emerald-800">+ Назначить дисциплину</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-xl p-3 border border-gray-100 dark:border-gray-700">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">1 семестр</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $totalHoursSem1 }} <span class="text-xs font-normal text-gray-400">ч.</span></p>
                        </div>
                        <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-xl p-3 border border-gray-100 dark:border-gray-700">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">2 семестр</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $totalHoursSem2 }} <span class="text-xs font-normal text-gray-400">ч.</span></p>
                        </div>
                        <div class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl p-3 border border-emerald-100/50 dark:border-emerald-500/20">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Всего за год</p>
                            <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ $totalHoursGrand }} <span class="text-xs font-normal opacity-60">ч.</span></p>
                        </div>
                    </div>

                    @if($workloads->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80">
                                        <th rowspan="2" class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700">Группа</th>
                                        <th rowspan="2" class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700">Дисциплина</th>
                                        <th colspan="3" class="text-center px-2 py-1.5 font-medium text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700">1 полугодие (ч.)</th>
                                        <th colspan="3" class="text-center px-2 py-1.5 font-medium text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700">2 полугодие (ч.)</th>
                                        <th rowspan="2" class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700 w-20">План (ч.)</th>
                                        <th rowspan="2" class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700 w-24">Выдано (ч.)</th>
                                        <th rowspan="2" class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-24">Осталось (ч.)</th>
                                    </tr>
                                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                                        <th class="text-center px-2 py-1 font-bold text-[10px] text-gray-400 uppercase border-r border-gray-100 dark:border-gray-700 w-12">Всего</th>
                                        <th class="text-center px-2 py-1 font-medium text-[10px] text-gray-400 uppercase border-r border-gray-100 dark:border-gray-700 w-10">Лек</th>
                                        <th class="text-center px-2 py-1 font-medium text-[10px] text-gray-400 uppercase border-r border-gray-100 dark:border-gray-700 w-10">Пр</th>
                                        <th class="text-center px-2 py-1 font-bold text-[10px] text-gray-400 uppercase border-r border-gray-100 dark:border-gray-700 w-12">Всего</th>
                                        <th class="text-center px-2 py-1 font-medium text-[10px] text-gray-400 uppercase border-r border-gray-100 dark:border-gray-700 w-10">Лек</th>
                                        <th class="text-center px-2 py-1 font-medium text-[10px] text-gray-400 uppercase border-r border-gray-100 dark:border-gray-700 w-10">Пр</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($workloads as $td)
                                        @php
                                            $s1 = $td->semesters->where('curriculumSemester.semester_in_course', 1);
                                            // Если planned_hours 0 или нет записи, берем из CurriculumSemester
                                            $h1_total = $s1->sum(fn($s) => $s->planned_hours > 0 ? $s->planned_hours : ($s->curriculumSemester->hours_total ?? 0));
                                            $h1_lec = $s1->sum('curriculumSemester.hours_lecture');
                                            $h1_prac = $s1->sum('curriculumSemester.hours_practice');

                                            $s2 = $td->semesters->where('curriculumSemester.semester_in_course', 2);
                                            $h2_total = $s2->sum(fn($s) => $s->planned_hours > 0 ? $s->planned_hours : ($s->curriculumSemester->hours_total ?? 0));
                                            $h2_lec = $s2->sum('curriculumSemester.hours_lecture');
                                            $h2_prac = $s2->sum('curriculumSemester.hours_practice');

                                            $totalPlanned = $h1_total + $h2_total;

                                            $totalConducted = 0;
                                            foreach ($td->semesters as $tdSem) {
                                                $key = $td->discipline_id . '-' . ($td->group_id ?? '') . '-' . $tdSem->curriculum_semester_id;
                                                $totalConducted += (int) ($conductedMap[$key]->total ?? 0);
                                            }
                                            $remaining = $totalPlanned - $totalConducted;
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white border-r border-gray-50 dark:border-gray-700 whitespace-nowrap">{{ $td->resolvedGroup?->name ?? '—' }}</td>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300 border-r border-gray-50 dark:border-gray-700">{{ $td->discipline->name }}</td>
                                            
                                            <td class="px-2 py-2.5 text-center font-bold text-gray-900 dark:text-white border-r border-gray-50 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/10">{{ $h1_total ?: '—' }}</td>
                                            <td class="px-2 py-2.5 text-center text-xs text-gray-500 border-r border-gray-50 dark:border-gray-700">{{ $h1_lec ?: '—' }}</td>
                                            <td class="px-2 py-2.5 text-center text-xs text-gray-500 border-r border-gray-50 dark:border-gray-700">{{ $h1_prac ?: '—' }}</td>

                                            <td class="px-2 py-2.5 text-center font-bold text-gray-900 dark:text-white border-r border-gray-50 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/10">{{ $h2_total ?: '—' }}</td>
                                            <td class="px-2 py-2.5 text-center text-xs text-gray-500 border-r border-gray-50 dark:border-gray-700">{{ $h2_lec ?: '—' }}</td>
                                            <td class="px-2 py-2.5 text-center text-xs text-gray-500 border-r border-gray-50 dark:border-gray-700">{{ $h2_prac ?: '—' }}</td>

                                            <td class="px-3 py-2.5 text-center font-semibold text-gray-900 dark:text-white border-r border-gray-50 dark:border-gray-700">{{ $totalPlanned }}</td>
                                            <td class="px-3 py-2.5 text-center font-medium text-emerald-600 dark:text-emerald-400 border-r border-gray-50 dark:border-gray-700">{{ $totalConducted ?: '0' }}</td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="font-semibold {{ $remaining > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                    {{ $remaining }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 mt-2">Нет назначенных дисциплин</p>
                    @endif
                </div>

                @php
                    $teacherPractices = \App\Models\CurriculumPractice::where('teacher_id', $teacher->id)
                        ->with('curriculumPlan.groupAssignments.group')
                        ->get();
                @endphp

                @if($teacherPractices->isNotEmpty())
                    <div class="mt-6 space-y-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Ведение практик
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($teacherPractices as $tp)
                                @php
                                    $tpGroup = $tp->curriculumPlan->groupAssignments->first()?->group;
                                    $tpLabel = match($tp->type) {
                                        'edu_practice' => 'Учебная практика',
                                        'prod_practice' => 'Производственная практика',
                                        'pre_diploma' => 'Преддипломная практика',
                                        default => 'Практика',
                                    };
                                    $tpColor = $tp->type === 'edu_practice' ? 'border-indigo-200 bg-indigo-50/50 text-indigo-800' : 'border-pink-200 bg-pink-50/50 text-pink-800';
                                @endphp
                                <div class="p-3 rounded-xl border {{ $tpColor }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest opacity-60">{{ $tpLabel }}</p>
                                            <p class="font-bold text-sm mt-0.5">{{ $tpGroup?->name ?? 'Неизвестная группа' }}</p>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/50 border border-current">
                                            {{ $tp->course_number }} курс
                                        </span>
                                    </div>
                                    <p class="text-xs mt-2 font-medium opacity-80">
                                        {{ $tp->start_date->format('d.m.Y') }} — {{ $tp->end_date->format('d.m.Y') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if ($showTeacherForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showTeacherForm', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Редактировать преподавателя</h3>
                </div>
                <form wire:submit="saveTeacher" class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Фамилия <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="teacherLastName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('teacherLastName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Имя <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="teacherFirstName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('teacherFirstName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Отчество</label>
                            <input type="text" wire:model="teacherMiddleName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Кафедра</label>
                        <select wire:model="teacherDepartmentId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбрана</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Должность</label>
                        <select wire:model="teacherPositionId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбрана</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Тип занятости</label>
                            <select wire:model="teacherEmploymentType" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <option value="full_time">Штатный</option>
                                <option value="part_time">Совместитель</option>
                                <option value="hourly">Почасовик</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Ставка <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="teacherRate" step="0.25" min="0" max="3" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                            @error('teacherRate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" wire:model="teacherEmail" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите email</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Телефон</label>
                            <input type="text" wire:model="teacherPhone" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-sm font-medium mb-2">Рабочие дни преподавателя</p>
                        <div class="flex flex-wrap gap-2 mb-4">
                            @php $dayLabels = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб']; @endphp
                            @foreach ($dayLabels as $dayNum => $dayLabel)
                                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer transition
                                    {{ in_array($dayNum, $teacherWorkingDays) ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                                    <input type="checkbox" value="{{ $dayNum }}"
                                        {{ in_array($dayNum, $teacherWorkingDays) ? 'checked' : '' }}
                                        wire:change="toggleWorkingDay({{ $dayNum }}, $event.target.checked)"
                                        class="rounded border-gray-300 text-emerald-600">
                                    {{ $dayLabel }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-sm font-medium mb-2">Номера пар по дням</p>
                        <div class="space-y-3">
                            @php $selectedDays = $teacherWorkingDays ?: [1,2,3,4,5]; @endphp
                            @foreach ($selectedDays as $wd)
                                @php $daySlots = $teacherWorkingLessonNumbers[$wd] ?? []; @endphp
                                <div>
                                    <span class="text-xs font-medium text-gray-500 uppercase">{{ $dayLabels[$wd] ?? $wd }}</span>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        @foreach (range(1, 7) as $num)
                                            <label class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs cursor-pointer transition
                                                {{ in_array($num, $daySlots) ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                                                <input type="checkbox" value="{{ $num }}"
                                                    {{ in_array($num, $daySlots) ? 'checked' : '' }}
                                                    wire:change="toggleWorkingLessonNumberForDay({{ $wd }}, {{ $num }}, $event.target.checked)"
                                                    class="rounded border-gray-300 text-emerald-600">
                                                {{ $num }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showTeacherForm', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showBuildingForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showBuildingForm', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Привязать корпус или аудиторию</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Корпус</label>
                        <select wire:model.live="selectedBuildingId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Выберите корпус</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }} ({{ $building->short_name ?? '—' }})</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedBuildingId)
                        @php
                            $isBuildingBound = $teacher->buildings->contains('building_id', $selectedBuildingId);
                        @endphp
                        @if(!$isBuildingBound)
                            <button wire:click="assignBuilding" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">
                                Привязать корпус
                            </button>
                        @else
                            <div class="text-xs text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg px-3 py-2">Корпус уже привязан</div>
                        @endif

                        @if($roomsByBuilding->isNotEmpty())
                            <div>
                                <label class="block text-sm font-medium mb-1">Аудитория</label>
                                @foreach($roomsByBuilding as $floor => $floorRooms)
                                    <div class="mb-2">
                                        <div class="text-xs text-gray-500 mb-1">Этаж {{ $floor }}</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($floorRooms as $room)
                                                <button type="button" wire:click="selectRoom({{ $room->id }})" wire:key="room-{{ $room->id }}"
                                                    class="text-left p-2 rounded-lg border text-sm transition
                                                    {{ $selectedRoomId === $room->id ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30' : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300' }}">
                                                    <div class="font-medium">{{ $room->number }}</div>
                                                    <div class="text-xs text-gray-500">{{ $room->name ?? $room->roomType?->short_name ?? '' }}</div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Приоритет (1-5)</label>
                                <input type="number" wire:model="roomPriority" min="1" max="5" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            </div>

                            <button wire:click="assignRoom" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">
                                Привязать аудиторию
                            </button>
                        @endif
                    @endif

                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showBuildingForm', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showDisciplineForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showDisciplineForm', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Назначить дисциплину</h3>
                </div>
                <form wire:submit="saveDiscipline" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Учебный план</label>
                        <select wire:model.live="disciplinePlanId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Выберите план</option>
                            @foreach($curriculumPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->specialty?->short_name ?? '—' }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    @if($disciplinePlanId)
                        <div>
                            <label class="block text-sm font-medium mb-1">Дисциплина <span class="text-red-500">*</span></label>
                            <select wire:model="disciplineId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <option value="">Выберите дисциплину</option>
                                @foreach($disciplinesByPlan as $disc)
                                    <option value="{{ $disc->id }}">{{ $disc->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                            @error('disciplineId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium mb-1">Группа</label>
                        <select wire:model="disciplineGroupId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбрана</option>
                            @foreach($allGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Учебный год</label>
                            <select wire:model="disciplineAcademicYearId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <option value="">Не выбран</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Плановые часы <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="disciplinePlannedHours" min="0" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                            @error('disciplinePlannedHours') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showDisciplineForm', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Назначить</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
