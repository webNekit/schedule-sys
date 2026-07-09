<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('groups.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700 mb-1 inline-block">← Назад к группам</a>
            <h2 class="text-2xl font-bold">{{ $group->name }}</h2>
            <p class="text-gray-500 dark:text-gray-400">{{ $group->specialty?->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $group->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-600' }}">
                {{ $group->status === 'active' ? 'Активна' : $group->status }}
            </span>
            <button wire:click="openGroupForm" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">Редактировать</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Год поступления</div>
            <div class="text-lg font-semibold mt-1">{{ $group->academicYear?->name ?? '—' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Кафедра</div>
            <div class="text-lg font-semibold mt-1">{{ $group->department?->name ?? '—' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Текущий курс / Смена</div>
            <div class="text-lg font-semibold mt-1">{{ $group->calculateCurrentCourse() }} курс · {{ $group->shift }}-я смена</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Кол-во студентов</div>
            <div class="text-lg font-semibold mt-1">{{ $group->students_count }} чел.</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Корпуса обучения
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">В каких корпусах группа учится. Выезд в спорткомплекс на физкультуру настраивается в системных настройках.</p>
            </div>
            <button wire:click="openBuildingForm" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Привязать корпус
            </button>
        </div>

        <div class="p-4">
            @if($group->buildings->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @foreach($group->buildings as $building)
                        @php $isPrimary = (bool) ($building->pivot->is_primary ?? false); @endphp
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl border transition
                            {{ $isPrimary
                                ? 'border-emerald-300 bg-emerald-50/60 dark:border-emerald-700/50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30' }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">{{ $building->name }}</span>
                                    @if($isPrimary)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">основной</span>
                                    @endif
                                </div>
                                @if($building->short_name)
                                    <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $building->short_name }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @if(! $isPrimary)
                                    <button wire:click="setPrimaryBuilding({{ $building->id }})" title="Сделать основным"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    </button>
                                @endif
                                <button wire:click="removeBuilding({{ $building->id }})" wire:confirm="Отвязать корпус «{{ $building->name }}»?" title="Отвязать"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($group->buildings->count() > 1)
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3">
                        Несколько корпусов — генератор чередует их по дням недели (один корпус на день).
                    </p>
                @endif
            @else
                <div class="text-center py-6 text-gray-400 dark:text-gray-500">
                    <p class="text-sm">Корпуса не назначены</p>
                    <p class="text-xs mt-0.5">Привяжите хотя бы один — иначе генератор поставит занятия в любой доступный корпус</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Учебные планы в стиле Аккордеона (Always Open) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/30 dark:bg-gray-900/30">
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-lg">Учебные планы по годам</h3>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest font-black italic">Режим аккордеона (развернут)</p>
            </div>
            <button wire:click="openCurriculumForm" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Привязать план
            </button>
        </div>

        @if($assignments->isNotEmpty())
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($assignments->sortByDesc('academicYear.year_start')->groupBy(fn($a) => $a->academicYear?->name ?? 'Без года') as $yearName => $yearAssignments)
                    @php $isCurrentYear = $yearAssignments->first()->academicYear?->is_current; @endphp
                    <div class="group">
                        <div class="px-6 py-4 flex items-center justify-between bg-white dark:bg-gray-800 border-l-4 {{ $isCurrentYear ? 'border-l-emerald-500 bg-emerald-50/10' : 'border-l-transparent' }}">
                            <div class="flex items-center gap-3">
                                <h4 class="text-sm font-black uppercase tracking-widest {{ $isCurrentYear ? 'text-emerald-600' : 'text-gray-500' }}">{{ $yearName }}</h4>
                                @if($isCurrentYear)
                                    <span class="px-2 py-0.5 rounded text-[8px] font-black bg-emerald-100 text-emerald-700 uppercase tracking-tighter">Активный год</span>
                                @endif
                            </div>
                            <svg class="w-5 h-5 text-gray-300 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        
                        <div class="px-6 pb-6 pt-2 space-y-2">
                            @foreach($yearAssignments as $assignment)
                                @php $plan = $assignment->curriculumPlan; @endphp
                                <a href="{{ route('curriculum.show', $plan) }}" wire:navigate class="relative flex items-center gap-4 p-4 rounded-2xl border {{ $isCurrentYear && ($activeAssignment->id === $assignment->id) ? 'bg-emerald-50/30 border-emerald-200' : 'bg-gray-50/50 border-gray-100 dark:bg-gray-900/50 dark:border-gray-800' }} hover:border-emerald-500/50 transition-all group/plan">
                                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex items-center justify-center {{ $isCurrentYear ? 'text-emerald-600' : 'text-gray-400' }} shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-900 dark:text-white truncate text-sm group-hover/plan:text-emerald-600 transition-colors">{{ $plan->name }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Курс: {{ $assignment->course_number ?? '—' }}</span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Версия: {{ $plan->version ?? '1.0' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button wire:click.prevent.stop="removeCurriculum({{ $assignment->id }})"
                                                wire:confirm="Отвязать учебный план?"
                                                class="p-2 rounded-xl text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center text-gray-400 italic font-medium">Планы не назначены</div>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="font-semibold mb-3">Подгруппы</h3>
        @if($group->subgroups->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($group->subgroups as $subgroup)
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-sm">
                        {{ $subgroup->name }} ({{ $subgroup->students_count }} чел.)
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Нет подгрупп</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/50">
            <h3 class="font-bold text-gray-900 dark:text-white uppercase tracking-widest text-xs">Дисциплины из плана на {{ $currentYear?->name }} год</h3>
            @if($activeAssignment)
                <span class="text-[10px] font-black bg-emerald-600 text-white px-2 py-1 rounded uppercase tracking-tighter">Актуально: {{ $activeAssignment->curriculumPlan->name }}</span>
            @endif
        </div>

        @if($disciplines->isNotEmpty())
            <div class="flex flex-wrap gap-2 px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                @php $currentCourse = $group->calculateCurrentCourse(); @endphp
                @foreach($courses as $course)
                    @php $isCurrent = $course === $currentCourse; $isSelected = $selectedCourse === $course; @endphp
                    <button wire:click="selectCourse({{ $course }})"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                                   {{ $isCurrent ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-300 dark:ring-emerald-700' : ($isSelected ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700') }}">
                        {{ $course }} курс
                        @if($isCurrent)
                            <span class="ml-1 text-[10px] opacity-70">· текущий</span>
                        @endif
                    </button>
                @endforeach
                <span class="w-px h-6 bg-gray-200 dark:bg-gray-700 self-center mx-1"></span>
                <select wire:model.live="semesterFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                    <option value="">Все семестры</option>
                    @foreach($availableSemesters as $sem)
                        <option value="{{ $sem }}">{{ $sem }} семестр</option>
                    @endforeach
                </select>
                <div class="flex-1 min-w-[160px]">
                    <input type="text" wire:model.live="disciplineSearch" placeholder="Поиск дисциплины..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm">
                </div>
                <select wire:model.live="disciplineTeacherFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                    <option value="">Все преподаватели</option>
                    @foreach($teachersByDiscipline as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->short_name }}</option>
                    @endforeach
                </select>
                @if($disciplineSearch || $disciplineTeacherFilter || $semesterFilter)
                    <button wire:click="$set('disciplineSearch', ''); $set('disciplineTeacherFilter', null); $set('semesterFilter', null)"
                        class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        Сбросить
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80">
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400">Дисциплина</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-16">Сем</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-14">Лекц</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-14">Прак</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-14">Лаб</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-14">СРС</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-16">Всего</th>
                            <th class="text-center px-3 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-28">Контроль</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 min-w-[140px]">Преподаватель</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($semestersForCourse as $semester)
                            @php
                                $discipline = $semester->discipline;
                                $teacherAssignmentsForDiscipline = $teacherAssignments->get($semester->discipline_id);
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-4 py-2.5">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $discipline->name }}</p>
                                        @if($discipline->code)
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-px">{{ $discipline->code }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ $semester->semester_number }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_lecture ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_practice ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_lab ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-700 dark:text-gray-300">{{ $semester->hours_self_study ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-center font-semibold text-gray-900 dark:text-white">{{ $semester->hours_total ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    @if($semester->controlForm)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $semester->controlForm->is_exam_session ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                            {{ $semester->controlForm->short_name ?? $semester->controlForm->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($teacherAssignmentsForDiscipline && $teacherAssignmentsForDiscipline->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($teacherAssignmentsForDiscipline as $ta)
                                                <a href="{{ route('teachers.show', $ta->teacher) }}"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                                    {{ $ta->teacher->short_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                    Нет дисциплин по выбранным фильтрам
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center text-gray-400 dark:text-gray-500">Нет дисциплин для выбранного учебного плана</div>
        @endif
    </div>

    @if ($showGroupForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showGroupForm', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Редактировать группу</h3>
                </div>
                <form wire:submit="saveGroup" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Название <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="groupFormName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        @error('groupFormName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Специальность</label>
                        <select wire:model="groupFormSpecialtyId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбрана</option>
                            @foreach($specialties as $spec)
                                <option value="{{ $spec->id }}">{{ $spec->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Кафедра</label>
                        <select wire:model="groupFormDepartmentId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбрана</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Учебный год</label>
                        <select wire:model="groupFormAcademicYearId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбран</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Курс</label>
                            <input type="number" wire:model="groupFormCourse" min="1" max="6" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Смена</label>
                            <select wire:model="groupFormShift" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <option value="1">1-я смена</option>
                                <option value="2">2-я смена</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Кол-во студентов</label>
                        <input type="number" wire:model="groupFormStudentsCount" min="0" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <p class="text-xs text-gray-400 mt-1">Введите число</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showGroupForm', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCurriculumForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showCurriculumForm', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Привязать учебный план</h3>
                </div>
            <form wire:submit="assignCurriculum" class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                        Специальность: <span class="font-medium text-gray-900 dark:text-white">{{ $group->specialty?->name ?? '—' }}</span>
                    </p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Учебный год <span class="text-red-500">*</span></label>
                            <select wire:model="selectedYearIdForForm" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="">Выберите год</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_current ? '(Текущий)' : '' }}</option>
                                @endforeach
                            </select>
                            @error('selectedYearIdForForm') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Учебный план <span class="text-red-500">*</span></label>
                            <select wire:model="selectedPlanIdForForm" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="">Выберите план</option>
                                @foreach($plansForSpecialty as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите план для специальности</p>
                            @error('selectedPlanIdForForm') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showCurriculumForm', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Привязать</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showBuildingForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showBuildingForm', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-visible">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Привязать корпус</h3>
                </div>
                <form wire:submit="assignBuilding" class="p-6 space-y-4">
                    <div>
                        @php
                            $buildingOptions = $buildings->map(fn ($b) => [
                                'id' => $b->id,
                                'label' => $b->name.($b->short_name ? ' — '.$b->short_name : ''),
                            ])->toArray();
                        @endphp
                        <x-searchable-select
                            label="Корпус"
                            model="selectedBuildingId"
                            :options="$buildingOptions"
                            none-label="Выберите корпус"
                            none-value=""
                            placeholder="Поиск корпуса..."
                            :error="$errors->first('selectedBuildingId')"
                        />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showBuildingForm', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Привязать</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
