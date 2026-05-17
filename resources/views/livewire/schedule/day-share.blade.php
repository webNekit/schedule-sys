<div>
    <div class="flex items-center justify-between mb-6 no-print">
        <div>
            <h1 class="text-3xl font-bold">{{ $department?->name ?? 'Расписание' }}</h1>
            <p class="text-base text-gray-500 mt-1">{{ Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</p>
        </div>
        <button onclick="window.print()"
            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-base transition">
            Печать
        </button>
    </div>

    @php
        $allGroups = \App\Models\Group::active()
            ->where('department_id', $department?->id)
            ->orderBy('name')
            ->get();
        $lessonNumbers = range(1, 7);
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        @forelse ($allGroups as $group)
            @php
                $groupLessons = collect($this->scheduleData)
                    ->filter(fn($l) => ($l['group_id'] ?? null) === $group->id)
                    ->keyBy('lesson_number');
            @endphp
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                <div class="px-4 py-3 bg-gray-100 border-b border-gray-200 text-center">
                    <div class="font-bold text-lg">{{ $group->name }}</div>
                    <div class="text-sm text-gray-500">{{ $group->current_course }} курс</div>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($lessonNumbers as $num)
                        @php $lesson = $groupLessons->get($num); @endphp
                        <div class="px-4 py-2.5 flex items-start gap-3 min-h-[60px]">
                            <span class="text-gray-400 font-mono text-sm w-6 shrink-0 mt-0.5">{{ $num }}.</span>
                            @if ($lesson)
                                @php
                                    $dayDiscCode = trim($lesson['discipline']['code'] ?? '');
                                    $dayDiscName = trim($lesson['discipline']['name'] ?? '—');
                                    $dayIsMdk = preg_match('/^МДК/ui', $dayDiscCode);
                                    $dayIsPractice = preg_match('/^(УП|ПП|ПДП|ГИА)/ui', $dayDiscCode);
                                    $dayIsExam = !$dayIsPractice && (preg_match('/^Э/ui', $dayDiscCode) || mb_strpos(mb_strtolower($dayDiscName), 'экзамен') !== false);
                                    $dayTypeColor = match (true) {
                                        preg_match('/^УП/ui', $dayDiscCode) => 'border-l-4 border-l-indigo-400 bg-indigo-50/60',
                                        preg_match('/^ПП/ui', $dayDiscCode) => 'border-l-4 border-l-pink-400 bg-pink-50/60',
                                        preg_match('/^ПДП/ui', $dayDiscCode) => 'border-l-4 border-l-amber-400 bg-amber-50/60',
                                        preg_match('/^ГИА/ui', $dayDiscCode) => 'border-l-4 border-l-red-400 bg-red-50/60',
                                        $dayIsExam => 'border-l-4 border-l-purple-400 bg-purple-50/60',
                                        $dayIsMdk => 'border-l-4 border-l-teal-300 bg-teal-50/40',
                                        default => 'border-l-4 border-l-transparent bg-gray-50',
                                    };
                                @endphp
                                <div class="text-sm leading-snug min-w-0 pl-2 {{ $dayTypeColor }}">
                                    <div class="font-semibold text-gray-800">
                                        @if($dayIsMdk || $dayIsPractice)
                                            <span title="{{ $dayDiscName }}"
                                                class="border-b border-dashed border-gray-400 cursor-help">{{ $dayDiscCode }}</span>
                                        @else
                                            <span title="{{ $dayDiscCode }}">{{ $dayDiscName }}</span>
                                        @endif
                                    </div>
                                    <div class="text-gray-500 mt-1">{{ $lesson['teacher']['last_name'] ?? '' }}
                                        {{ mb_substr($lesson['teacher']['first_name'] ?? '', 0, 1) }}.</div>
                                    <div class="text-gray-400 text-xs mt-1">
                                        {{ $lesson['room']['number'] ?? '' }}
                                        @if (!empty($lesson['room']['building']))
                                            , {{ $lesson['room']['building']['short_name'] ?? '' }}
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-300 text-sm mt-0.5">—</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-gray-400">Нет данных для отображения</div>
        @endforelse
    <span class="hidden border-l-indigo-400 bg-indigo-50/60 border-l-pink-400 bg-pink-50/60 border-l-amber-400 bg-amber-50/60 border-l-red-400 bg-red-50/60 border-l-purple-400 bg-purple-50/60 border-l-teal-300 bg-teal-50/40 border-l-transparent bg-gray-50"></span>
    </div>

    <p class="text-center text-sm text-gray-400 mt-8 no-print">Расписание на
        {{ Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p>
</div>