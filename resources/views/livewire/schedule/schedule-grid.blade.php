<div class="space-y-5" x-data="{
    scrollToHl() {
        setTimeout(() => {
            const el = document.querySelector('[data-hl=true]');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ring-2', 'ring-red-500');
                setTimeout(() => el.classList.remove('ring-2', 'ring-red-500'), 2000);
            }
        }, 100);
    }
}" x-init="$wire.on('scroll-to-highlight', () => scrollToHl())">

    @php
        // Bell schedule times (fallback when DB is empty)
        $bellTimes = [
            1 => '08:30 – 10:00',
            2 => '10:15 – 11:45',
            3 => '12:00 – 13:30',
            4 => '14:00 – 15:30',
            5 => '15:45 – 17:15',
            6 => '17:30 – 19:00',
            7 => '19:15 – 20:45',
        ];
        $bellSchedules = \App\Models\BellSchedule::where('is_active', true)->orderBy('lesson_number')->get()->keyBy('lesson_number');
        if ($bellSchedules->isNotEmpty()) {
            foreach ($bellSchedules as $num => $bs) {
                $bellTimes[$num] = substr($bs->time_start, 0, 5) . ' – ' . substr($bs->time_end, 0, 5);
            }
        }
        $dayFullNames = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
        $dayOffsets   = [0, 1, 2, 3, 4, 5];
        $lessonNumbers = range(1, 7);
        $gk = $viewMode === 'group' || $viewMode === 'department' ? 'group_id' : ($viewMode === 'teacher' ? 'teacher_id' : 'room_id');
        $grouped = collect($scheduleData)->groupBy($gk);

        $practiceLabels = [
            'гп' => 'Подготовка к ГИА', 'дп' => 'Сдача ГИА',
            'у'  => 'Учебная практика',  'пд' => 'Преддипломная практика',
            'п'  => 'Производственная практика', 'пп' => 'Производственная практика',
            'э'  => 'Экзаменационная сессия',
        ];
        $practiceColors = [
            'Подготовка к ГИА' => 'bg-red-500/15 text-red-400 border-red-500/30',
            'Сдача ГИА'        => 'bg-red-500/15 text-red-400 border-red-500/30',
            'Учебная практика' => 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
            'Производственная практика' => 'bg-pink-500/15 text-pink-400 border-pink-500/30',
            'Преддипломная практика'    => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            'Экзаменационная сессия'    => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
        ];
    @endphp

    @if (session('message'))
        <div class="px-4 py-2.5 bg-emerald-900/20 border border-emerald-700/40 rounded-lg text-sm text-emerald-400">{{ session('message') }}</div>
    @endif
    @if (session('error'))
        <div class="px-4 py-2.5 bg-red-900/20 border border-red-700/40 rounded-lg text-sm text-red-400">{{ session('error') }}</div>
    @endif

    {{-- ===== CONTROLS ===== --}}
    <div class="flex flex-wrap items-end justify-between gap-6">
        {{-- View mode buttons --}}
        <div>
            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Режим отображения сетки</p>
            <div class="flex items-center gap-1 bg-zinc-900 border border-zinc-800 p-1 rounded-lg">
                <button wire:click="$set('viewMode', 'group')"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium transition-all
                    {{ $viewMode === 'group' ? 'bg-zinc-700 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    По группам
                </button>
                <button wire:click="$set('viewMode', 'teacher')"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium transition-all
                    {{ $viewMode === 'teacher' ? 'bg-zinc-700 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    По преподавателям
                </button>
                <button wire:click="$set('viewMode', 'room')"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium transition-all
                    {{ $viewMode === 'room' ? 'bg-zinc-700 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    По аудиториям
                </button>
            </div>
        </div>

        {{-- Entity selector --}}
        <div class="flex-1 min-w-[240px] max-w-sm">
            @if ($viewMode === 'group')
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Выберите студенческую группу</p>
                <select wire:model.live="viewId"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-4 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition-colors">
                    <option value="0">Все группы</option>
                    @foreach ($this->groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->students_count }} студентов)</option>
                    @endforeach
                </select>
            @elseif ($viewMode === 'teacher')
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Выберите преподавателя</p>
                @php
                    $teacherOptions = $this->teachers->map(fn ($t) => [
                        'id' => (string) $t->id,
                        'label' => "{$t->last_name} {$t->first_name}",
                    ])->toArray();
                @endphp
                <x-searchable-select
                    model="viewId"
                    :options="$teacherOptions"
                    :dark="true"
                    none-label="Все преподаватели"
                    none-value="0"
                    placeholder="Поиск преподавателя..."
                />
            @elseif ($viewMode === 'room')
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Выберите аудиторию</p>
                <select wire:model.live="viewId"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-4 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition-colors">
                    <option value="0">Все аудитории</option>
                    @foreach ($this->rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->number ?? $room->name }}{{ $room->building ? ' — '.($room->building->short_name ?? $room->building->name) : '' }}</option>
                    @endforeach
                </select>
            @elseif ($viewMode === 'department')
                <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">Выберите кафедру</p>
                <select wire:model.live="viewId"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-4 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition-colors">
                    <option value="0">Все кафедры</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2 shrink-0">
            @if ($versionId)
                @php $v = \App\Models\ScheduleVersion::find($versionId); @endphp
                @if ($v && $v->status === 'published')
                    <button wire:click="revertToDraft({{ $versionId }})" wire:confirm="Перевести расписание в черновик?"
                        class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-sm font-medium transition">В черновик</button>
                @elseif ($v && in_array($v->status, ['draft', 'generating']))
                    <button wire:click="publish({{ $versionId }})"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition">Опубликовать</button>
                @endif
            @endif
            <button wire:click="checkConflictsForWeek"
                class="px-4 py-2.5 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-zinc-300 hover:text-white rounded-lg text-sm font-medium transition">
                Конфликты
            </button>
            <button wire:click="openExportModal"
                class="px-4 py-2.5 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-zinc-300 hover:text-white rounded-lg text-sm font-medium transition">
                Экспорт расписания
            </button>
            <button wire:click="openShareModal"
                class="px-4 py-2.5 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-zinc-300 hover:text-white rounded-lg text-sm font-medium transition">
                Ссылка
            </button>
        </div>
    </div>

    {{-- ===== WEEK NAVIGATION ===== --}}
    <div class="flex items-center justify-between gap-4">
        <button wire:click="previousWeek"
            class="flex items-center gap-1.5 px-4 py-2 bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-zinc-400 hover:text-white rounded-lg text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Пред. неделя
        </button>
        <span class="text-sm font-semibold text-zinc-200">
            {{ \Carbon\Carbon::parse($weekStart)->translatedFormat('d F Y') }}
            — {{ \Carbon\Carbon::parse($weekStart)->endOfWeek(\Carbon\Carbon::SUNDAY)->translatedFormat('d F Y') }}
        </span>
        <button wire:click="nextWeek"
            class="flex items-center gap-1.5 px-4 py-2 bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-zinc-400 hover:text-white rounded-lg text-sm transition">
            След. неделя
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    @if ($shareLink)
        <div class="flex items-center gap-2 p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
            <input type="text" value="{{ $shareLink }}" readonly
                class="flex-1 bg-transparent border-none text-sm text-zinc-300 outline-none" onclick="this.select()">
            <button onclick="navigator.clipboard.writeText('{{ $shareLink }}')"
                class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-xs text-zinc-300 rounded-md transition">Копировать</button>
            <button wire:click="$set('shareLink', null)" class="text-zinc-500 hover:text-zinc-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- ===== SCHEDULE TABLES ===== --}}
    @php
        if ($viewMode === 'group' && $viewId === 0) {
            $entityIds = $grouped->keys();
        } else {
            $entityIds = $grouped->keys()->take(50);
        }
    @endphp

    @forelse ($entityIds as $entityId)
        @php
            $lessons = $grouped->get($entityId, collect());
            $entity = null;
            if ($viewMode === 'group' || $viewMode === 'department') {
                $entity = $this->groups->firstWhere('id', $entityId);
            } elseif ($viewMode === 'teacher') {
                $entity = $this->teachers->firstWhere('id', $entityId);
            } else {
                $entity = $this->rooms->firstWhere('id', $entityId);
            }
        @endphp

        <div class="rounded-xl overflow-hidden border border-zinc-800 bg-zinc-950 shadow-lg">
            {{-- Entity header --}}
            <div class="px-5 py-3 bg-zinc-900/60 border-b border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if ($viewMode === 'group' || $viewMode === 'department')
                        @php
                            $targetHours = $entity instanceof \App\Models\Group ? $entity->getWeeklyHours() : 0;
                            $actualHours = $lessons->count() * 2;
                        @endphp
                        <span class="font-bold text-white text-sm">{{ $entity?->name ?? 'Группа #' . $entityId }}</span>
                        @if ($targetHours > 0)
                            <span class="text-xs font-medium px-2 py-0.5 rounded bg-zinc-800 border border-zinc-700">
                                Нагрузка:
                                <span class="{{ $actualHours === $targetHours ? 'text-emerald-400' : ($actualHours > $targetHours ? 'text-amber-400' : 'text-red-400') }} font-bold">
                                    {{ $actualHours }}
                                </span>
                                <span class="text-zinc-500">/ {{ $targetHours }} ч.</span>
                            </span>
                        @endif
                    @elseif ($viewMode === 'teacher')
                        <span class="font-bold text-white text-sm">{{ $entity?->last_name }} {{ $entity?->first_name }}</span>
                    @else
                        <span class="font-bold text-white text-sm">{{ $entity ? (($entity->number ?? $entity->name).($entity->building ? ' — '.($entity->building->short_name ?? $entity->building->name) : '')) : 'Аудитория #' . $entityId }}</span>
                    @endif
                </div>
                @if (!empty($highlightedLessonIds))
                    <button wire:click="clearHighlights" class="text-xs text-zinc-500 hover:text-zinc-300 transition">Снять подсветку</button>
                @endif
            </div>

            {{-- Grid --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-800">
                            <th class="px-4 py-3 text-left w-[120px] shrink-0">
                                <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Время / День</span>
                            </th>
                            @foreach ($dayFullNames as $i => $dayName)
                                @php $dayDate = \Carbon\Carbon::parse($weekStart)->addDays($i); @endphp
                                <th class="px-4 py-3 text-left border-l border-zinc-800 min-w-[180px]">
                                    <div class="font-semibold text-zinc-200 text-sm">{{ $dayName }}</div>
                                    <div class="text-[10px] font-bold text-zinc-600 uppercase tracking-widest mt-0.5">
                                        {{ $dayDate->format('d.m') }} · Занятия
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lessonNumbers as $lessonNum)
                            <tr class="border-b border-zinc-800/60 hover:bg-zinc-900/20 transition-colors">
                                {{-- Time column --}}
                                <td class="px-4 py-3 align-top shrink-0 border-r border-zinc-800/60">
                                    <div class="text-emerald-400 font-semibold text-sm">{{ $lessonNum }}-я пара</div>
                                    @if(isset($bellTimes[$lessonNum]))
                                        <div class="text-[11px] text-zinc-500 mt-0.5">{{ $bellTimes[$lessonNum] }}</div>
                                    @endif
                                </td>

                                @foreach ($dayOffsets as $dayOffset)
                                    @php
                                        $date = \Carbon\Carbon::parse($weekStart)->addDays($dayOffset)->format('Y-m-d');
                                        $allCellLessons = array_filter($lessons->toArray(), fn($l) =>
                                            $l['date'] === $date && $l['lesson_number'] === $lessonNum
                                        );

                                        // Каникулы — информационная пометка из таблицы. Они НЕ отменяют учёбу:
                                        // если группе выданы пары или стоит практика, группа учится и пометка уступает.
                                        $vacationInfo = $vacationData[$date] ?? null;

                                        $practiceInfo = null;
                                        if (($viewMode === 'group' || $viewMode === 'department') && isset($practiceData[$entityId])) {
                                            $practiceInfo = collect($practiceData[$entityId])->first(fn($p) => $p['date'] === $date);
                                        }

                                        $dayHasLessons = collect($lessons->toArray())->contains(fn($l) => $l['date'] === $date);
                                        // Показываем «каникулы» только когда группа в этот день реально не занята.
                                        $showVacation = $vacationInfo && !$practiceInfo && !$dayHasLessons;

                                        // On practice days: only show manually-added lessons (not auto-generated)
                                        $cellLessons = $practiceInfo
                                            ? array_filter($allCellLessons, fn($l) => !($l['is_auto_generated'] ?? false))
                                            : $allCellLessons;

                                        $pSym = $practiceInfo ? mb_strtolower(trim($practiceInfo['symbol'])) : '';
                                        $pLabel = $practiceLabels[$pSym] ?? ($practiceInfo ? 'Практика (' . mb_strtoupper($pSym) . ')' : null);
                                        $pColorClass = $practiceInfo ? ($practiceColors[$pLabel] ?? 'bg-zinc-800/60 text-zinc-400 border-zinc-700/50') : '';
                                    @endphp

                                    <td class="px-3 py-2 border-l border-zinc-800/60 align-top min-w-[180px] relative">

                                        {{-- Vacation / holiday overlay (only on lesson 1, when group is not studying) --}}
                                        @if($showVacation && $lessonNum === 1)
                                            <div class="absolute inset-x-0 -top-px z-10">
                                                <div class="mx-1 px-2 py-0.5 rounded-b text-[9px] font-bold uppercase tracking-wider text-center border {{ $vacationInfo['type'] === 'holiday' ? 'bg-rose-500/15 text-rose-400 border-rose-500/30' : 'bg-sky-500/15 text-sky-400 border-sky-500/30' }}">
                                                    {{ $vacationInfo['label'] }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Practice day overlay (only on lesson 1) --}}
                                        @if($practiceInfo && $lessonNum === 1)
                                            <div class="absolute inset-x-0 -top-px z-10">
                                                <div class="mx-1 px-2 py-0.5 rounded-b text-[9px] font-bold uppercase tracking-wider text-center border {{ $pColorClass }}">
                                                    {{ $pLabel }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Lessons (manual on practice days, all on normal days) --}}
                                        @foreach ($cellLessons as $lesson)
                                            @php
                                                $isHl = in_array($lesson['id'], $highlightedLessonIds);
                                                $conflictType = $lessonConflictMap[$lesson['id']] ?? null;
                                                $discCode = trim($lesson['discipline']['code'] ?? '');
                                                $discName = trim($lesson['discipline']['name'] ?? '—');
                                                $ltCode   = $lesson['lesson_type']['code'] ?? '';
                                                $ltShort  = $lesson['lesson_type']['short_name'] ?? '';
                                                $ltColor  = $lesson['lesson_type']['color'] ?? '#6b7280';
                                                $isMdk    = (bool) preg_match('/^МДК/ui', $discCode);
                                                $isPractice = $ltCode === 'edu_practice' || $ltCode === 'prod_practice' || (bool) preg_match('/^(УП|ПП|ПДП|ГИА|ГП|ДП)/ui', $discCode);
                                                $isExam   = !$isPractice && ($ltCode === 'exam' || $ltCode === 'test' || $ltCode === 'diff_test');

                                                $tLn = $lesson['teacher']['last_name'] ?? '';
                                                $tFn = $lesson['teacher']['first_name'] ?? '';
                                                $tMn = $lesson['teacher']['middle_name'] ?? '';
                                                $tFi = $tFn ? mb_substr($tFn, 0, 1) . '.' : '';
                                                $tMi = $tMn ? mb_substr($tMn, 0, 1) . '.' : '';
                                                $tName = trim("$tLn $tFi$tMi");

                                                $roomNum  = $lesson['room']['number'] ?? ($lesson['room']['name'] ?? '');
                                                $buildShort = $lesson['room']['building']['short_name'] ?? ($lesson['room']['building']['name'] ?? '');

                                                $conflictBorder = match($conflictType) {
                                                    'error'   => 'border-red-600/60',
                                                    'warning' => 'border-amber-500/60',
                                                    default   => 'border-zinc-700/60',
                                                };
                                            @endphp
                                            <div class="group relative mb-1.5 p-2.5 rounded-lg border bg-zinc-900 transition-all cursor-pointer
                                                {{ $conflictBorder }}
                                                {{ $isHl ? 'border-red-500! ring-1 ring-red-500/40' : 'hover:border-zinc-600' }}"
                                                wire:click="editLesson({{ $lesson['id'] }})"
                                                @if($isHl) data-hl="true" @endif>

                                                {{-- Lesson type badge + title --}}
                                                <div class="flex items-start gap-2 mb-1.5">
                                                    @if($ltShort)
                                                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                                                            style="background-color: {{ $ltColor }}22; color: {{ $ltColor }}; border: 1px solid {{ $ltColor }}44;">
                                                            {{ $ltShort }}
                                                        </span>
                                                    @endif
                                                    <span class="font-semibold text-zinc-100 text-xs leading-snug flex-1"
                                                        title="{{ $discCode ? $discCode . ' – ' . $discName : $discName }}">
                                                        @if($isMdk || $isPractice)
                                                            {{ $discCode ?: $discName }}
                                                        @else
                                                            {{ $discName }}
                                                        @endif
                                                    </span>
                                                </div>

                                                {{-- Teacher --}}
                                                @if($tName)
                                                    <div class="flex items-center gap-1.5 text-[11px] text-zinc-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        @if ($viewMode === 'teacher')
                                                            {{ $lesson['group']['name'] ?? '' }}
                                                        @else
                                                            {{ $tName }}
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Room --}}
                                                @if($roomNum && $viewMode !== 'room')
                                                    <div class="flex items-center gap-1 text-[11px] text-zinc-500 mt-0.5">
                                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <span>Ауд. {{ $roomNum }}{{ $buildShort ? ' (' . $buildShort . ')' : '' }}</span>
                                                    </div>
                                                @endif

                                                {{-- Delete button --}}
                                                <button wire:click.stop="deleteLesson({{ $lesson['id'] }})"
                                                    wire:confirm="Удалить занятие?"
                                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-[9px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 shadow-md">
                                                    ✕
                                                </button>
                                            </div>
                                        @endforeach

                                        {{-- Empty cell: show add button or practice placeholder --}}
                                        @if(empty($cellLessons))
                                            @if($practiceInfo)
                                                {{-- Practice day - muted placeholder --}}
                                                <div class="py-2 text-center">
                                                    <span class="text-[10px] text-zinc-700">—</span>
                                                </div>
                                            @else
                                                @php
                                                    $cellDayOfWeek = (int) \Carbon\Carbon::parse($weekStart)->addDays($dayOffset)->format('N');
                                                    $cellCanAdd = true;
                                                    if ($entity && method_exists($entity, 'getWorkingDays')) {
                                                        $cellCanAdd = in_array($cellDayOfWeek, $entity->getWorkingDays());
                                                    }
                                                    if ($entity && method_exists($entity, 'getAllowedLessonNumbersForDay')) {
                                                        $allowed = $entity->getAllowedLessonNumbersForDay($cellDayOfWeek);
                                                        if (!empty($allowed)) {
                                                            $cellCanAdd = $cellCanAdd && in_array($lessonNum, $allowed);
                                                        }
                                                    }
                                                @endphp
                                                @if($cellCanAdd)
                                                    <button wire:click="addLesson('{{ $date }}', {{ $lessonNum }}, {{ $entityId }})"
                                                        class="w-full py-2 rounded-lg border border-dashed border-zinc-800 text-zinc-700 hover:border-emerald-600/50 hover:text-emerald-500 hover:bg-emerald-950/20 text-xs font-medium transition-all group/add">
                                                        <span class="opacity-0 group-hover/add:opacity-100 transition-opacity">+ Добавить</span>
                                                        <span class="group-hover/add:hidden">—</span>
                                                    </button>
                                                @else
                                                    <div class="py-2 text-center text-zinc-800 text-xs">—</div>
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-16 text-center">
            <p class="text-zinc-500 text-sm">Нет занятий на эту неделю</p>
        </div>
    @endforelse

    {{-- ===== MODALS (unchanged logic, restyled) ===== --}}

    {{-- Edit / Add lesson modal --}}
    @if ($editing)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50"
            wire:click.self="cancelEdit">
            <div class="bg-zinc-900 border border-zinc-700 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-white">
                        {{ $editDisciplineId > 0 ? 'Редактирование занятия' : 'Добавление занятия' }}
                        @if ($editIsPublished)
                            <span class="ml-2 text-[10px] px-2 py-0.5 rounded bg-amber-900/40 text-amber-400 border border-amber-700/40">Замена</span>
                        @endif
                    </h3>
                    <button wire:click="cancelEdit" class="text-zinc-500 hover:text-zinc-300 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Дата</label>
                        <input type="date" wire:model="editDate"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                        @error('editDate') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Дисциплина</label>
                        <div x-data="{ open: false }" class="relative">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="open = !open"
                                    class="flex-1 flex justify-between items-center rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-left text-sm text-zinc-100 hover:border-zinc-600 transition">
                                    <span class="truncate">{{ $disciplineSearch ?: 'Выберите дисциплину...' }}</span>
                                    <svg class="w-4 h-4 text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                @if($editDisciplineId > 0)
                                    <button type="button" wire:click="selectDiscipline(0, '')" class="p-2 text-zinc-500 hover:text-red-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                            </div>
                            <div x-show="open" @click.outside="open = false"
                                class="absolute z-50 w-full mt-1 bg-zinc-900 border border-zinc-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto">
                                <input type="text" wire:model.live.debounce.300ms="disciplineSearch" placeholder="Поиск..."
                                    class="w-full p-3 border-b border-zinc-800 bg-transparent outline-none text-sm text-zinc-100 placeholder-zinc-600">
                                @foreach ($this->filteredDisciplines as $disc)
                                    <button type="button" @click="open = false" wire:click="selectDiscipline({{ $disc->id }}, '{{ addslashes($disc->name) }}')"
                                        class="w-full text-left px-3 py-2.5 text-sm hover:bg-zinc-800 flex justify-between items-center gap-2 text-zinc-300 hover:text-white transition">
                                        <span class="truncate">
                                            @if($disc->code)
                                                <span class="font-mono text-[10px] text-zinc-500 mr-1">[{{ $disc->code }}]</span>
                                            @endif
                                            {{ $disc->name }}
                                        </span>
                                        <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-500">
                                            {{ $disc->remaining_hours }}/{{ $disc->total_hours }} ч
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Номер пары</label>
                        <input type="number" wire:model="editLessonNumber" min="1" max="8"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                        @error('editLessonNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Преподаватель</label>
                        <div x-data="{ open: false }" class="relative">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="open = !open"
                                    class="flex-1 flex justify-between items-center rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-left text-sm text-zinc-100 hover:border-zinc-600 transition">
                                    <span class="truncate">{{ $teacherSearchInput ?: 'Выберите преподавателя...' }}</span>
                                    <svg class="w-4 h-4 text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                @if($editTeacherId > 0)
                                    <button type="button" wire:click="selectTeacher(0, '')" class="p-2 text-zinc-500 hover:text-red-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                            </div>
                            <div x-show="open" @click.outside="open = false"
                                class="absolute z-50 w-full mt-1 bg-zinc-900 border border-zinc-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto">
                                <input type="text" wire:model.live.debounce.300ms="teacherSearchInput" placeholder="Поиск..."
                                    class="w-full p-3 border-b border-zinc-800 bg-transparent outline-none text-sm text-zinc-100 placeholder-zinc-600">
                                @foreach ($this->filteredTeachers as $teacher)
                                    <button type="button" @click="open = false" wire:click="selectTeacher({{ $teacher->id }}, '{{ addslashes($teacher->full_name) }}')"
                                        class="w-full text-left px-3 py-2.5 text-sm hover:bg-zinc-800 text-zinc-300 hover:text-white transition">
                                        {{ $teacher->full_name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Аудитория</label>
                        <div x-data="{ open: false }" class="relative">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="open = !open"
                                    class="flex-1 flex justify-between items-center rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-left text-sm text-zinc-100 hover:border-zinc-600 transition">
                                    <span class="truncate">{{ $roomSearch ?: 'Выберите аудиторию...' }}</span>
                                    <svg class="w-4 h-4 text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                @if($editRoomId > 0)
                                    <button type="button" wire:click="selectRoom(0, '')" class="p-2 text-zinc-500 hover:text-red-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                            </div>
                            <div x-show="open" @click.outside="open = false"
                                class="absolute z-50 w-full mt-1 bg-zinc-900 border border-zinc-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto">
                                <input type="text" wire:model.live.debounce.300ms="roomSearch" placeholder="Поиск по номеру..."
                                    class="w-full p-3 border-b border-zinc-800 bg-transparent outline-none text-sm text-zinc-100 placeholder-zinc-600">
                                @foreach ($this->filteredRooms as $room)
                                    <button type="button" @click="open = false" wire:click="selectRoom({{ $room->id }}, '№{{ $room->number }}')"
                                        class="w-full text-left px-3 py-2.5 text-sm hover:bg-zinc-800 flex justify-between items-center gap-2 text-zinc-300 hover:text-white transition">
                                        <div>
                                            <span class="font-medium">{{ $room->display_name }}</span>
                                            <div class="text-[10px] {{ $room->suitability === 'perfect' ? 'text-emerald-500' : ($room->suitability === 'preferred' ? 'text-indigo-400' : 'text-zinc-500') }}">
                                                {{ $room->suitability_label }}
                                            </div>
                                        </div>
                                        @if($room->suitability === 'perfect')
                                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Заметки</label>
                        <textarea wire:model="editNotes" rows="2"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition resize-none placeholder-zinc-600"
                            placeholder="Необязательно..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-zinc-950/50 border-t border-zinc-800 flex justify-between items-center">
                    <button wire:click="deleteLesson({{ $editingLessonId }})" wire:confirm="Удалить занятие?"
                        class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 border border-red-700/50 text-red-400 rounded-lg text-sm font-medium transition">
                        Удалить
                    </button>
                    <div class="flex gap-2">
                        <button wire:click="cancelEdit"
                            class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-sm transition">Отмена</button>
                        <button wire:click="saveLesson"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-semibold transition shadow-sm">
                            {{ $editIsPublished ? 'Сохранить замену' : 'Сохранить' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Conflict modal --}}
    @if ($showConflictModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50"
            wire:click.self="$set('showConflictModal', false)">
            <div class="bg-zinc-900 border border-zinc-700 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-zinc-800 flex items-center justify-between shrink-0">
                    <h3 class="text-base font-semibold text-white">Конфликты в расписании</h3>
                    <button wire:click="autoFixConflicts"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium transition">
                        Автоисправление
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    @if (empty($conflicts))
                        <div class="p-4 bg-emerald-900/20 border border-emerald-700/40 rounded-lg text-sm text-emerald-400">
                            Конфликтов не найдено.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($conflicts as $conflict)
                                @php
                                    $sevClass = match ($conflict['severity'] ?? 'warning') {
                                        'error'   => 'border-red-700/50 bg-red-950/30',
                                        'warning' => 'border-amber-700/50 bg-amber-950/30',
                                        default   => 'border-zinc-700 bg-zinc-900/50',
                                    };
                                    $typeLabel = match ($conflict['conflict_type']) {
                                        'teacher_window'          => 'Окно у препода',
                                        'teacher_min_lessons'     => 'Мало пар',
                                        'teacher_parallel'        => 'Параллельные пары',
                                        'teacher_overload'        => 'Перегрузка препода',
                                        'teacher_building_conflict' => 'Корпус препода',
                                        'teacher_discipline_mismatch' => 'Чужая дисциплина',
                                        'teacher_unavailability'  => 'Препод недоступен',
                                        'saturday_lesson_limit'   => 'Суббота',
                                        'group_min_lessons'       => 'Мало пар у группы',
                                        'group_window'            => 'Окно у группы',
                                        'group_shift_mismatch'    => 'Смена',
                                        'group_parallel'          => 'Параллельные пары',
                                        'group_overload'          => 'Перегрузка группы',
                                        'group_building_conflict' => 'Корпус группы',
                                        'group_practice_overlap'  => 'Практика',
                                        'pe_grouping'             => 'Физ-ра',
                                        'pe_after_fourth_pair'    => 'Физ-ра после 4-й',
                                        'room_multi_group'        => 'Аудитория',
                                        'room_capacity'           => 'Вместимость',
                                        'sport_complex_reserved'  => 'Спорткомплекс',
                                        'custom_rule'             => 'Авторское правило',
                                        default                   => $conflict['conflict_type'],
                                    };
                                @endphp
                                <div class="p-3 rounded-lg border text-sm {{ $conflict['is_resolved'] ? 'border-emerald-700/50 bg-emerald-950/20' : $sevClass }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase
                                                {{ $conflict['severity'] === 'error' ? 'bg-red-900/40 text-red-400 border border-red-700/40' : 'bg-amber-900/40 text-amber-400 border border-amber-700/40' }}">
                                                {{ $typeLabel }}
                                            </span>
                                            <span class="text-xs text-zinc-500">{{ $conflict['date'] ?? '' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button wire:click="highlightConflict('{{ $conflict['date'] }}', {{ $conflict['teacher_id'] ?? 'null' }}, {{ $conflict['group_id'] ?? 'null' }}, {{ $conflict['room_id'] ?? 'null' }}, {{ $conflict['lesson_number'] ?? 'null' }})"
                                                class="text-xs text-amber-500 hover:text-amber-400 underline decoration-dashed transition">Подробнее →</button>
                                            @if (!$conflict['is_resolved'])
                                                <button wire:click="startResolve({{ $conflict['id'] }})"
                                                    class="text-xs text-indigo-400 hover:text-indigo-300 transition">Разрешить</button>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="mt-1.5 text-zinc-300">{{ $conflict['description'] }}</p>
                                    @if (!empty($conflict['suggestion']) && !$conflict['is_resolved'])
                                        <p class="mt-1 text-xs text-emerald-500">💡 {{ $conflict['suggestion'] }}</p>
                                    @endif
                                    @if ($conflict['is_resolved'])
                                        <p class="mt-1 text-xs text-emerald-500">✓ {{ $conflict['resolution_notes'] ?? 'Разрешено' }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="px-6 py-4 border-t border-zinc-800 flex justify-end shrink-0">
                    <button wire:click="$set('showConflictModal', false)"
                        class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-sm transition">Закрыть</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Resolve conflict modal --}}
    @if ($resolvingConflictId)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50"
            wire:click.self="$set('resolvingConflictId', null)">
            <div class="bg-zinc-900 border border-zinc-700 rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-base font-semibold text-white mb-4">Разрешение конфликта</h3>
                <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Примечание</label>
                <textarea wire:model="resolutionNote" rows="3"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition resize-none"
                    placeholder="Опишите, как разрешён конфликт..."></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('resolvingConflictId', null)"
                        class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-sm transition">Отмена</button>
                    <button wire:click="resolveConflict"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-semibold transition">Подтвердить</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Share modal --}}
    @if ($showShareModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50"
            wire:click.self="$set('showShareModal', false)">
            <div class="bg-zinc-900 border border-zinc-700 rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-base font-semibold text-white mb-4">Поделиться расписанием</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Тип ссылки</label>
                        <select wire:model.live="shareType"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                            <option value="week">На неделю (группа/преподаватель)</option>
                            <option value="day">На день (вся кафедра)</option>
                        </select>
                    </div>
                    @if ($shareType === 'week')
                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Просмотр</label>
                            <select wire:model.live="shareViewMode"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                                <option value="group">По группе</option>
                                <option value="teacher">По преподавателю</option>
                                <option value="room">По аудитории</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Выберите</label>
                            <select wire:model="shareViewId"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                                @if ($shareViewMode === 'group')
                                    @foreach ($this->groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                @elseif ($shareViewMode === 'teacher')
                                    @foreach ($this->teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $teacher->first_name }}</option>
                                    @endforeach
                                @elseif ($shareViewMode === 'room')
                                    @foreach ($this->rooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->number ?? $room->name }}{{ $room->building ? ' — '.($room->building->short_name ?? $room->building->name) : '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Кафедра</label>
                            <select wire:model="shareViewId"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                                @foreach ($this->departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Дата</label>
                            <input type="date" wire:model="shareDate"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 focus:border-zinc-500 outline-none transition">
                        </div>
                    @endif
                    <button wire:click="generateShareLink"
                        class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-semibold transition">
                        Сгенерировать ссылку
                    </button>
                </div>
                <div class="flex justify-end mt-4">
                    <button wire:click="$set('showShareModal', false)"
                        class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-sm transition">Закрыть</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Export Date Picker Modal --}}
    @if($showExportModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
            wire:click.self="closeExportModal"
        >
            <div class="bg-zinc-900 border border-zinc-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-700 bg-indigo-950/60">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Экспорт расписания</h3>
                        @php $v = \App\Models\ScheduleVersion::find($versionId); @endphp
                        @if($v)
                            <p class="text-sm text-zinc-400 mt-0.5">
                                {{ $v->name }}
                                &nbsp;·&nbsp;
                                {{ $v->date_from?->format('d.m.Y') }} — {{ $v->date_to?->format('d.m.Y') }}
                            </p>
                        @endif
                    </div>
                    <button wire:click="closeExportModal" class="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5">
                    <p class="text-sm text-zinc-400 mb-4">
                        Выберите дату для формирования расписания по кафедрам.
                        Формат: <strong class="text-white">1 лист = 1 кафедра</strong>, группы в 3 колонки.
                    </p>

                    @php $dates = $this->availableExportDates; @endphp

                    @if(empty($dates))
                        <div class="text-center py-8 text-zinc-500">
                            <p class="text-sm">Нет доступных дат в данной версии</p>
                        </div>
                    @else
                        <div class="grid grid-cols-3 gap-2 max-h-72 overflow-y-auto pr-1">
                            @foreach($dates as $date)
                                <a
                                    href="{{ route('schedule.export.download', [$versionId, $date['value']]) }}"
                                    target="_blank"
                                    class="flex flex-col items-center justify-center gap-1 px-3 py-3 rounded-xl border text-center transition-all
                                        {{ $date['hasLessons']
                                            ? 'border-indigo-500/50 bg-indigo-950/60 hover:bg-indigo-900/60 text-indigo-300'
                                            : 'border-zinc-700 bg-zinc-800/50 hover:bg-zinc-700/50 text-zinc-400' }}"
                                >
                                    <span class="text-xs font-semibold uppercase tracking-wide opacity-60">
                                        {{ $date['dayLabel'] }}
                                    </span>
                                    <span class="text-base font-bold leading-tight">
                                        {{ $date['dateLabel'] }}
                                    </span>
                                    @if($date['hasLessons'])
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 mt-0.5"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <p class="text-xs text-zinc-500 mt-3 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 inline-block"></span>
                            Дни с занятиями выделены синим
                        </p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-zinc-700 flex justify-end">
                    <button wire:click="closeExportModal"
                        class="px-4 py-2 text-sm text-zinc-400 hover:text-white transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
