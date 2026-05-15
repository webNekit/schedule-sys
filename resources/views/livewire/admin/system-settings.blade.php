<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Настройки системы</h2>
            <p class="text-sm text-gray-500">Управление учебным годом, рабочими днями и парами по курсам</p>
        </div>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Academic Year --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-4">Учебный год</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-1">Текущий учебный год</label>
                <div class="flex gap-2">
                    <select wire:model.live="currentAcademicYearId" wire:change="setCurrentAcademicYear($event.target.value)" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @foreach ($this->academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-gray-500 mt-1">При смене года меняются все связанные данные</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Перевод групп</label>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Активных групп: <strong>{{ $groupsCount }}</strong></p>
                <button wire:click="promoteGroups" wire:confirm="Перевести все группы на следующий курс?" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                    Перевести на следующий курс
                </button>
                @if ($promotionResult)
                    <p class="text-xs text-emerald-600 mt-1">{{ $promotionResult }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Working Days --}}
    @php
        $dayLabels = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-4">Рабочие дни</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Для 1-2 курсов (5-дневка)</label>
                <div class="flex flex-wrap gap-2">
                    @php $wd12 = $settings['working_days_course_1_2']['value'] ?? []; @endphp
                    @foreach ($dayLabels as $dayNum => $dayLabel)
                        <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer transition
                            {{ in_array($dayNum, $wd12) ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                            <input type="checkbox" value="{{ $dayNum }}"
                                {{ in_array($dayNum, $wd12) ? 'checked' : '' }}
                                wire:change="toggleWorkingDay('working_days_course_1_2', {{ $dayNum }}, $event.target.checked)"
                                class="rounded border-gray-300 text-emerald-600">
                            {{ $dayLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Для 3-4 курсов (6-дневка)</label>
                <div class="flex flex-wrap gap-2">
                    @php $wd34 = $settings['working_days_course_3_4']['value'] ?? []; @endphp
                    @foreach ($dayLabels as $dayNum => $dayLabel)
                        <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer transition
                            {{ in_array($dayNum, $wd34) ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                            <input type="checkbox" value="{{ $dayNum }}"
                                {{ in_array($dayNum, $wd34) ? 'checked' : '' }}
                                wire:change="toggleWorkingDay('working_days_course_3_4', {{ $dayNum }}, $event.target.checked)"
                                class="rounded border-gray-300 text-emerald-600">
                            {{ $dayLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Lesson Numbers per Course --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-4">Номера пар по курсам</h3>
        <p class="text-sm text-gray-500 mb-4">Выберите, какие пары может ставить система для каждого курса</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach (['lesson_numbers_course_1' => '1 курс (1 смена)', 'lesson_numbers_course_2' => '2 курс (1 смена)', 'lesson_numbers_course_3' => '3 курс (2 смена)', 'lesson_numbers_course_4' => '4 курс (2 смена)'] as $key => $label)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                    <div class="flex flex-wrap gap-1.5">
                        @php $nums = $settings[$key]['value'] ?? []; @endphp
                        @foreach (range(1, 7) as $num)
                            <label class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs cursor-pointer transition
                                {{ in_array($num, $nums) ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                                <input type="checkbox" value="{{ $num }}"
                                    {{ in_array($num, $nums) ? 'checked' : '' }}
                                    wire:change="toggleLessonNumber('{{ $key }}', {{ $num }}, $event.target.checked)"
                                    class="rounded border-gray-300 text-emerald-600">
                                {{ $num }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Other settings --}}
    <form wire:submit="save" class="space-y-6">
        @foreach ($groups as $groupKey => $groupSettings)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-lg">
                        @switch($groupKey)
                            @case('general') Общие настройки @break
                            @default {{ $groupKey }}
                        @endswitch
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    @foreach ($groupSettings as $key => $data)
                        @continue(in_array($key, ['working_days_course_1_2', 'working_days_course_3_4', 'lesson_numbers_course_1', 'lesson_numbers_course_2', 'lesson_numbers_course_3', 'lesson_numbers_course_4', 'schedule_generation_max_retries']))

                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $data['label'] ?? $key }}</label>
                            @if ($data['description'])
                                <p class="text-xs text-gray-500 mb-1">{{ $data['description'] }}</p>
                            @endif

                            @if ($data['type'] === 'boolean')
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="settings.{{ $key }}.value" class="rounded border-gray-300 text-emerald-600">
                                    <span class="text-sm">Включено</span>
                                </label>
                            @elseif ($data['type'] === 'integer')
                                <input type="number" wire:model="settings.{{ $key }}.value" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            @else
                                <input type="text" wire:model="settings.{{ $key }}.value" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">
                Сохранить настройки
            </button>
        </div>
    </form>
</div>
