<div>
    <div class="flex items-center justify-between mb-4 no-print">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Расписание занятий</h1>
            @if ($version)
                <p class="text-sm text-gray-500">{{ $version->name }}</p>
            @endif
        </div>
        <button onclick="window.print()"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">
            Печать
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4 no-print">
        <select wire:model.live="viewMode" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="group">По группам</option>
            <option value="teacher">По преподавателям</option>
            <option value="room">По аудиториям</option>
        </select>

        @if ($viewMode === 'group')
            <select wire:model.live="viewId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="0">Все группы</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        @elseif ($viewMode === 'teacher')
            <select wire:model.live="viewId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="0">Все преподаватели</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $teacher->first_name }}</option>
                @endforeach
            </select>
        @elseif ($viewMode === 'room')
            <select wire:model.live="viewId" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="0">Все аудитории</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->number ?? $room->name }}{{ $room->building ? ' — '.($room->building->short_name ?? $room->building->name) : '' }}</option>
                @endforeach
            </select>
        @endif

        <span class="text-sm text-gray-500 ml-auto">{{ Carbon\Carbon::parse($weekStart)->format('d.m') }} —
            {{ Carbon\Carbon::parse($weekStart)->endOfWeek(Carbon\Carbon::SUNDAY)->format('d.m.Y') }}</span>
    </div>

    @php
        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayOffsets = [0, 1, 2, 3, 4, 5];
        $lessonNumbers = range(1, 7);
    @endphp

    @forelse ($grouped as $entityId => $lessons)
        @php
            $entity = match ($viewMode) {
                'group' => $groups->firstWhere('id', $entityId),
                'teacher' => $teachers->firstWhere('id', $entityId),
                'room' => $rooms->firstWhere('id', $entityId),
                default => null,
            };
            $entityName = match ($viewMode) {
                'group' => $entity?->name ?? 'Группа',
                'teacher' => $entity ? $entity->last_name . ' ' . $entity->first_name : 'Преподаватель',
                'room' => $entity ? (($entity->number ?? $entity->name).($entity->building ? ' — '.($entity->building->short_name ?? $entity->building->name) : '')) : 'Аудитория',
                default => '',
            };
            $lessonsArr = $lessons instanceof \Illuminate\Support\Collection ? $lessons->toArray() : $lessons;
        @endphp

        <div class="mb-6">
            <h2 class="text-lg font-bold mb-2 px-1">{{ $entityName }}</h2>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-sm border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-2 py-1.5 text-left font-medium text-gray-500 text-xs w-10">Пара</th>
                            @foreach ($dayNames as $i => $day)
                                @php
                                    $headerDate = Carbon\Carbon::parse($weekStart)->addDays($i)->format('Y-m-d');
                                    // Каникулы показываем только если у группы в этот день нет пар — иначе она учится.
                                    $headerHasLessons = collect($lessonsArr)->contains(fn($l) => $l['date'] === $headerDate);
                                    $headerVacation = (!$headerHasLessons && !empty($vacationData[$headerDate])) ? $vacationData[$headerDate] : null;
                                @endphp
                                <th
                                    class="px-2 py-1.5 text-left font-medium text-gray-500 text-xs border-l border-gray-200 min-w-[110px]">
                                    {{ $day }}<br><span
                                        class="font-normal">{{ Carbon\Carbon::parse($weekStart)->addDays($i)->format('d.m') }}</span>
                                    @if ($headerVacation)
                                        <span class="block mt-0.5 font-semibold {{ $headerVacation['type'] === 'holiday' ? 'text-rose-500' : 'text-sky-600' }}">
                                            {{ $headerVacation['label'] }}
                                        </span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($lessonNumbers as $lessonNum)
                            <tr>
                                <td class="px-2 py-1.5 text-gray-500 font-medium text-xs">{{ $lessonNum }}</td>
                                @foreach ($dayOffsets as $dayOffset)
                                    @php
                                        $date = Carbon\Carbon::parse($weekStart)->addDays($dayOffset)->format('Y-m-d');
                                        $cellLessons = array_filter($lessonsArr, fn($l) => $l['date'] === $date && $l['lesson_number'] === $lessonNum);
                                        $dayHasLessons = collect($lessonsArr)->contains(fn($l) => $l['date'] === $date);
                                        // Каникулы — только когда группа в этот день реально не учится.
                                        $cellVacation = (!$dayHasLessons && isset($vacationData[$date])) ? $vacationData[$date] : null;
                                    @endphp
                                    <td class="px-2 py-1 border-l border-gray-100 align-top {{ $cellVacation ? 'bg-sky-50/50' : '' }}">
                                        @if ($cellVacation && $lessonNum === 1)
                                            <div class="text-[11px] italic text-gray-400">{{ $cellVacation['label'] }}</div>
                                        @endif
                                        @foreach ($cellLessons as $lesson)
                                            @php
                                                $pubDiscCode = trim($lesson['discipline']['code'] ?? '');
                                                $pubDiscName = trim($lesson['discipline']['name'] ?? '—');
                                                $pubIsMdk = preg_match('/^МДК/ui', $pubDiscCode);
                                                $pubIsPractice = preg_match('/^(УП|ПП|ПДП|ГИА)/ui', $pubDiscCode);
                                                $pubIsExam = !$pubIsPractice && (preg_match('/^Э/ui', $pubDiscCode) || mb_strpos(mb_strtolower($pubDiscName), 'экзамен') !== false);
                                                $pubTypeColor = match (true) {
                                                    preg_match('/^УП/ui', $pubDiscCode) => 'border-l-indigo-400 bg-indigo-50/60',
                                                    preg_match('/^ПП/ui', $pubDiscCode) => 'border-l-pink-400 bg-pink-50/60',
                                                    preg_match('/^ПДП/ui', $pubDiscCode) => 'border-l-amber-400 bg-amber-50/60',
                                                    preg_match('/^ГИА/ui', $pubDiscCode) => 'border-l-red-400 bg-red-50/60',
                                                    $pubIsExam => 'border-l-purple-400 bg-purple-50/60',
                                                    $pubIsMdk => 'border-l-teal-300 bg-teal-50/40',
                                                    default => 'bg-gray-50 border-gray-100',
                                                };
                                            @endphp
                                            <div class="mb-0.5 p-1.5 pl-2 rounded border {{ $pubTypeColor }}">
                                                <div class="font-medium text-xs leading-tight">
                                                    @if($pubIsMdk || $pubIsPractice)
                                                        <span title="{{ $pubDiscName }}"
                                                            class="border-b border-dashed border-gray-400 cursor-help">{{ $pubDiscCode }}</span>
                                                    @else
                                                        <span title="{{ $pubDiscCode }}">{{ $pubDiscName }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-gray-500 mt-0.5">
                                                    @if ($viewMode !== 'teacher')
                                                        {{ $lesson['teacher']['last_name'] ?? '' }}
                                                        {{ $lesson['teacher']['first_name'] ?? '' }}
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-gray-400">
                                                    {{ $lesson['room']['number'] ?? '' }}
                                                    @if (!empty($lesson['room']['building']))
                                                        {{ $lesson['room']['building']['short_name'] ?? '' }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="p-12 text-center text-gray-400">Нет занятий в выбранном периоде</div>
    @endforelse

    <span class="hidden border-l-indigo-400 bg-indigo-50/60 border-l-pink-400 bg-pink-50/60 border-l-amber-400 bg-amber-50/60 border-l-red-400 bg-red-50/60 border-l-purple-400 bg-purple-50/60 border-l-teal-300 bg-teal-50/40 bg-gray-50 border-gray-100 text-sky-600 text-rose-500 bg-sky-50/50"></span>
    <p class="text-center text-xs text-gray-400 mt-6 no-print">Расписание занятий — данные актуальны на момент генерации
    </p>
</div>