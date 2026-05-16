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

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Корпуса</h3>
            <button wire:click="openBuildingForm" class="text-sm text-emerald-600 hover:text-emerald-800">+ Привязать корпус</button>
        </div>
        @if($group->buildings->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($group->buildings as $building)
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm">
                        {{ $building->short_name ?? $building->name }}
                        <button wire:click="removeBuilding({{ $building->id }})" wire:confirm="Отвязать корпус?" class="text-red-500 hover:text-red-700">&times;</button>
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Не назначены</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Учебные планы</h3>
            <button wire:click="openCurriculumForm" class="text-sm text-emerald-600 hover:text-emerald-800">+ Привязать план</button>
        </div>
        @if($curriculumPlans->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($curriculumPlans as $plan)
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm">
                        {{ $plan->name }}
                        <button wire:click="removeCurriculum({{ $plan->id }})" wire:confirm="Отвязать учебный план?" class="text-red-500 hover:text-red-700">&times;</button>
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Не привязаны</p>
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
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold">Дисциплины по семестрам</h3>
        </div>

        @if($courses->isNotEmpty())
            <div class="flex flex-wrap gap-2 px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                @foreach($courses as $course)
                    <button wire:click="selectCourse({{ $course }})"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                                   {{ $selectedCourse === $course ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $course }} курс
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
                                $assignments = $teacherAssignments->get($semester->discipline_id);
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
                                    @if($assignments && $assignments->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($assignments as $ta)
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
            <div class="p-8 text-center text-gray-400 dark:text-gray-500">Нет учебных планов для этой группы</div>
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
                    <h3 class="text-lg font-semibold">Привязать учебный план</h3>
                </div>
            <form wire:submit="assignCurriculum" class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                        Специальность: <span class="font-medium text-gray-900 dark:text-white">{{ $group->specialty?->name ?? '—' }}</span>
                    </p>
                    <label class="block text-sm font-medium mb-1">Учебный план</label>
                    <select wire:model="selectedPlanId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="">Выберите план</option>
                        @foreach($plansForSpecialty as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->version ?? '—' }})</option>
                        @endforeach
                    </select>
                    @error('selectedPlanId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Привязать корпус</h3>
                </div>
                <form wire:submit="assignBuilding" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Корпус</label>
                        <select wire:model="selectedBuildingId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Выберите корпус</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }} ({{ $building->short_name ?? '—' }})</option>
                            @endforeach
                        </select>
                        @error('selectedBuildingId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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
