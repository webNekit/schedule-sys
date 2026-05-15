<div class="space-y-6">
    <h2 class="text-2xl font-bold">Просмотр групп и курсов</h2>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium mb-1">Кафедра</label>
                <select wire:model.live="selectedDepartmentId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="">Выберите кафедру</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Группа</label>
                <select wire:model.live="selectedGroupId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="">Выберите группу</option>
                    @foreach ($this->groupsByDepartment as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Курс</label>
                <select wire:model.live="selectedCourse" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="">Выберите курс</option>
                    @foreach ($this->coursesForGroup as $course)
                        <option value="{{ $course }}">{{ $course }} курс</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($this->disciplinesForCourse->isNotEmpty())
            <div>
                <h3 class="font-semibold mb-3">Дисциплины на курсе</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Дисциплина</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Всего часов</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Лекции</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Практики</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Лаб.</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Форма контроля</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->disciplinesForCourse as $discipline)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $discipline->name }}</td>
                                    <td class="px-4 py-3">{{ $discipline->semesters->sum('hours_total') }}</td>
                                    <td class="px-4 py-3">{{ $discipline->semesters->sum('hours_lecture') }}</td>
                                    <td class="px-4 py-3">{{ $discipline->semesters->sum('hours_practice') }}</td>
                                    <td class="px-4 py-3">{{ $discipline->semesters->sum('hours_lab') }}</td>
                                    <td class="px-4 py-3">{{ $discipline->control_form ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif ($selectedGroupId && $selectedCourse)
            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-gray-500 text-center">
                Нет дисциплин для отображения.
            </div>
        @endif
    </div>
</div>
