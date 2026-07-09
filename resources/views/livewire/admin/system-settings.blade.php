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
                    <button wire:click="openYearForm" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition whitespace-nowrap">
                        + Добавить
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">При смене года меняются все связанные данные</p>

                @if ($showYearForm)
                    <div class="mt-4 p-4 border border-emerald-200 dark:border-emerald-800 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 space-y-3">
                        <p class="text-sm font-medium">Новый учебный год</p>
                        <div>
                            <label class="block text-xs font-medium mb-1">Название <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newYearName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Год начала <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="newYearStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <p class="text-xs text-gray-400 mt-1">Введите число</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Год конца <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="newYearEnd" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <p class="text-xs text-gray-400 mt-1">Введите число</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Дата начала <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="newYearDateStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Дата конца <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="newYearDateEnd" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button wire:click="$set('showYearForm', false)" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                            <button wire:click="saveAcademicYear" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Сохранить</button>
                        </div>
                    </div>
                @endif
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

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-4">Настройки нагрузки</h3>
        <div class="max-w-xs">
            @if (isset($settings['weekly_hours_total']))
                <label class="block text-sm font-medium mb-1">{{ $settings['weekly_hours_total']['label'] }}</label>
                <p class="text-xs text-gray-500 mb-2">{{ $settings['weekly_hours_total']['description'] }}</p>
                <div class="flex items-center gap-3">
                    <input type="number" wire:model.live="settings.weekly_hours_total.value" 
                        wire:change="save"
                        class="w-24 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <span class="text-sm text-gray-500">часов в неделю</span>
                </div>
            @endif
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
                <label class="block text-sm font-medium mb-2">Для 1-2 курсов</label>
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
                <label class="block text-sm font-medium mb-2">Для 3-4 курсов</label>
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

    {{-- Sport Complex Weekly Schedule --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-1">Расписание спорткомплекса</h3>
        <p class="text-sm text-gray-500 mb-4">
            Отметьте, какие группы и на какие пары приезжают в спорткомплекс на физкультуру. Одна группа может стоять на нескольких парах.
            Дни без групп спорткомплексом не используются.
        </p>
        <div>
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="w-16 p-2 text-left text-xs font-medium text-gray-500 uppercase">Пара</th>
                        @foreach ([1, 2, 3, 4, 5] as $wd)
                            <th class="p-2 text-center text-xs font-medium text-gray-500 uppercase">{{ $dayLabels[$wd] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sportComplexPairs as $pair)
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="p-2 align-top font-medium text-gray-500">{{ $pair }}</td>
                            @foreach ([1, 2, 3, 4, 5] as $wd)
                                @php $cellSlots = $sportSlotsByCell[$wd.'-'.$pair] ?? collect(); @endphp
                                <td class="p-2 align-top border-l border-gray-100 dark:border-gray-700 min-w-[160px]">
                                    <div class="space-y-1.5">
                                        @foreach ($cellSlots as $slot)
                                            <div class="flex items-center justify-between gap-1 px-2 py-1 rounded-md bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs">
                                                <span class="truncate">{{ $slot->group?->name ?? '—' }}</span>
                                                <button type="button" wire:click="removeSportGroup({{ $slot->id }})"
                                                    class="shrink-0 text-emerald-500 hover:text-red-500" title="Убрать">&times;</button>
                                            </div>
                                        @endforeach
                                        @php $assignedIds = $cellSlots->pluck('group_id')->all(); @endphp
                                        <div x-data="{ open: false, search: '' }" @click.outside="open = false; search = ''" class="relative">
                                            <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between gap-1 px-2 py-1 text-xs rounded-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                                                <span>+ группа</span>
                                                <svg class="w-3.5 h-3.5 opacity-50 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak x-transition.opacity
                                                class="absolute z-50 mt-1 w-full min-w-[160px] rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xl">
                                                <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                                    <input type="text" x-model="search" placeholder="Поиск группы..."
                                                        class="w-full px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 outline-none">
                                                </div>
                                                <ul class="max-h-48 overflow-y-auto py-1">
                                                    @foreach ($sportGroupOptions as $opt)
                                                        @continue(in_array($opt['id'], $assignedIds, true))
                                                        <li x-show="!search || @js(mb_strtolower($opt['label'])).includes(search.toLowerCase())"
                                                            @click="$wire.addSportGroup({{ $wd }}, {{ $pair }}, {{ $opt['id'] }}); open = false; search = ''"
                                                            class="px-3 py-1.5 text-xs cursor-pointer text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-gray-700">{{ $opt['label'] }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex items-center justify-end gap-3">
            <span class="text-xs text-gray-400">Изменения сохраняются автоматически</span>
            <button type="button" wire:click="saveSportSchedule"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                Сохранить
            </button>
        </div>
    </div>

    {{-- Lesson Numbers per Course / per Day --}}
    @php
        $courseKeys = ['lesson_numbers_course_1' => '1 курс', 'lesson_numbers_course_2' => '2 курс', 'lesson_numbers_course_3' => '3 курс', 'lesson_numbers_course_4' => '4 курс'];
        $courseWorkingDays = ['lesson_numbers_course_1' => 'working_days_course_1_2', 'lesson_numbers_course_2' => 'working_days_course_1_2', 'lesson_numbers_course_3' => 'working_days_course_3_4', 'lesson_numbers_course_4' => 'working_days_course_3_4'];
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-4">Номера пар по курсам и дням</h3>
        <p class="text-sm text-gray-500 mb-4">Для каждого рабочего дня выберите, какие пары может ставить система</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($courseKeys as $key => $label)
                @php
                    $wdKey = $courseWorkingDays[$key];
                    $workingDays = $settings[$wdKey]['value'] ?? [1, 2, 3, 4, 5];
                    $perDayData = $settings[$key]['value'] ?? [];
                @endphp
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <label class="block text-sm font-semibold mb-3">{{ $label }}</label>
                    <div class="space-y-3">
                        @foreach ($workingDays as $wd)
                            @php
                                $daySlots = $perDayData[$wd] ?? [];
                            @endphp
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">{{ $dayLabels[$wd] ?? $wd }}</span>
                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    @foreach (range(1, 7) as $num)
                                        <label class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs cursor-pointer transition
                                            {{ in_array($num, $daySlots) ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                                            <input type="checkbox" value="{{ $num }}"
                                                {{ in_array($num, $daySlots) ? 'checked' : '' }}
                                                wire:change="toggleLessonNumberForDay('{{ $key }}', {{ $wd }}, {{ $num }}, $event.target.checked)"
                                                class="rounded border-gray-300 text-emerald-600">
                                            {{ $num }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
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
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $data['label'] ?? $key }}</label>
                            @if (($data['description'] ?? ''))
                                <p class="text-xs text-gray-500 mb-1">{{ $data['description'] }}</p>
                            @endif

                            @if (($data['type'] ?? '') === 'boolean')
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="settings.{{ $key }}.value" class="rounded border-gray-300 text-emerald-600">
                                    <span class="text-sm">Включено</span>
                                </label>
                            @elseif (($data['type'] ?? '') === 'integer')
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
