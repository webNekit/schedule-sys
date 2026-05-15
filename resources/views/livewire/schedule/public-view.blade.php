<div>
    <div class="flex items-center justify-between mb-4 no-print">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Расписание занятий</h1>
            @if ($version)
                <p class="text-sm text-gray-500">{{ $version->name }}</p>
            @endif
        </div>
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">
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
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </select>
        @endif

        <span class="text-sm text-gray-500 ml-auto">{{ Carbon\Carbon::parse($weekStart)->format('d.m') }} — {{ Carbon\Carbon::parse($weekStart)->endOfWeek(Carbon\Carbon::SUNDAY)->format('d.m.Y') }}</span>
    </div>

    @php
        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayOffsets = [0, 1, 2, 3, 4, 5];
        $lessonNumbers = range(1, 7);
    @endphp

    @forelse ($grouped as $entityId => $lessons)
        @php
            $entity = match($viewMode) {
                'group' => $groups->firstWhere('id', $entityId),
                'teacher' => $teachers->firstWhere('id', $entityId),
                'room' => $rooms->firstWhere('id', $entityId),
                default => null,
            };
            $entityName = match($viewMode) {
                'group' => $entity?->name ?? 'Группа',
                'teacher' => $entity ? $entity->last_name.' '.$entity->first_name : 'Преподаватель',
                'room' => $entity?->name ?? 'Аудитория',
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
                                <th class="px-2 py-1.5 text-left font-medium text-gray-500 text-xs border-l border-gray-200 min-w-[110px]">
                                    {{ $day }}<br><span class="font-normal">{{ Carbon\Carbon::parse($weekStart)->addDays($i)->format('d.m') }}</span>
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
                                    @endphp
                                    <td class="px-2 py-1 border-l border-gray-100 align-top">
                                        @foreach ($cellLessons as $lesson)
                                            <div class="mb-0.5 p-1.5 rounded bg-gray-50 border border-gray-100">
                                                <div class="font-medium text-xs leading-tight">{{ $lesson['discipline']['name'] ?? '' }}</div>
                                                <div class="text-[11px] text-gray-500">
                                                    @if ($viewMode !== 'teacher')
                                                        {{ $lesson['teacher']['last_name'] ?? '' }} {{ $lesson['teacher']['first_name'] ?? '' }}
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-gray-400">
                                                    {{ $lesson['room']['number'] ?? '' }}
                                                    @if (!empty($lesson['building']))
                                                        {{ $lesson['building']['short_name'] ?? '' }}
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

    <p class="text-center text-xs text-gray-400 mt-6 no-print">Расписание занятий — данные актуальны на момент генерации</p>
</div>
