<div class="space-y-6" x-data="{
    scrollToHl() {
        setTimeout(() => {
            const el = document.querySelector('[data-hl=true]');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ring-4', 'ring-red-500', 'scale-105', 'z-10');
                setTimeout(() => el.classList.remove('ring-4', 'ring-red-500', 'scale-105', 'z-10'), 2000);
            }
        }, 100);
    }
}"
    x-init="$wire.on('scroll-to-highlight', () => scrollToHl())">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Просмотр расписания</h2>
        <div class="flex gap-2">
            <button wire:click="openShareModal"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                Ссылка
            </button>
            <button wire:click="exportExcel" wire:loading.attr="disabled"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition">
                Экспорт Excel
            </button>
            <button wire:click="checkConflictsForWeek"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition shadow-sm">
                Проверить конфликты
            </button>
        </div>
    </div>
    @if (session('message'))
        <div
            class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session('error'))
        <div
            class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif
    @if (session('info'))
        <div
            class="p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-700 dark:text-blue-300">
            {{ session('info') }}
        </div>
    @endif
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <select wire:model.live="viewMode"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="group">По группам</option>
                    <option value="teacher">По преподавателям</option>
                    <option value="room">По аудиториям</option>
                    <option value="department">По кафедре</option>
                </select>
                @if ($viewMode === 'group')
                    <select wire:model.live="viewId"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="0">Все группы</option>
                        @foreach ($this->groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                @elseif ($viewMode === 'teacher')
                    <select wire:model.live="viewId"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="0">Все преподаватели</option>
                        @foreach ($this->teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $teacher->first_name }}</option>
                        @endforeach
                    </select>
                @elseif ($viewMode === 'room')
                    <select wire:model.live="viewId"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="0">Все аудитории</option>
                        @foreach ($this->rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                        @endforeach
                    </select>
                @elseif ($viewMode === 'department')
                    <select wire:model.live="viewId"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="0">Все кафедры</option>
                        @foreach ($this->departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            @if ($shareLink)
                <div class="flex items-center gap-2">
                    <input type="text" value="{{ $shareLink }}" readonly
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm w-80"
                        onclick="this.select()">
                    <button onclick="navigator.clipboard.writeText('{{ $shareLink }}')"
                        class="px-3 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg text-sm transition">Копировать</button>
                    <button wire:click="$set('shareLink', null)" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
            @endif
        </div>
        <div class="flex items-center justify-between">
            <button wire:click="previousWeek"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">←
                Пред.</button>
            <span class="font-medium">{{ Carbon\Carbon::parse($weekStart)->translatedFormat('d F Y') }} —
                {{ Carbon\Carbon::parse($weekStart)->endOfWeek(Carbon\Carbon::SUNDAY)->translatedFormat('d F Y') }}</span>
            <button wire:click="nextWeek"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">След.
                →</button>
            @if (!empty($highlightedLessonIds))
                <button wire:click="clearHighlights"
                    class="px-2 py-1 text-xs text-red-600 hover:text-red-800 transition">Снять подсветку</button>
            @endif
        </div>
    </div>
    @php
        $dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $dayOffsets = [0, 1, 2, 3, 4, 5];
        $lessonNumbers = range(1, 7);
        $gk = $viewMode === 'group' || $viewMode === 'department' ? 'group_id' : ($viewMode === 'teacher' ? 'teacher_id' : 'room_id');
        $grouped = collect($scheduleData)->groupBy($gk);
    @endphp
    @if ($viewMode === 'group' && $viewId === 0)
        @php $entityIds = $grouped->keys(); @endphp
    @else
        @php $entityIds = $grouped->keys()->take(50); @endphp
    @endif
    @forelse ($entityIds as $entityId)
        @php
            $lessons = $grouped->get($entityId, collect());
            $entity = null;
            if ($viewMode === 'group') {
                $groupModel = $this->groups->firstWhere('id', $entityId);
                $entity = $groupModel;
            } elseif ($viewMode === 'teacher') {
                $entity = $this->teachers->firstWhere('id', $entityId);
            } elseif ($viewMode === 'department') {
                $dept = $this->departments->firstWhere('id', $this->viewId);
                $grp = $this->groups->firstWhere('id', $entityId);
                $entity = $grp;
            } else {
                $entity = $this->rooms->firstWhere('id', $entityId);
            }
        @endphp
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div
                class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 font-semibold flex items-center justify-between">
                <div>
                    @if ($viewMode === 'group' || $viewMode === 'department')
                        @php
                            $targetHours = $entity instanceof \App\Models\Group ? $entity->getWeeklyHours() : 0;
                            $actualHours = $lessons->count() * 2;
                            $hourStatusClass = $actualHours === $targetHours ? 'text-emerald-600' : ($actualHours > $targetHours ? 'text-amber-600' : 'text-red-600');
                        @endphp
                        <div class="flex items-center gap-3">
                            <span>{{ $entity?->name ?? 'Группа #' . $entityId }}</span>
                            @if ($targetHours > 0)
                                <span class="text-xs font-medium px-2 py-0.5 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 shadow-sm">
                                    Нагрузка: <span class="{{ $hourStatusClass }} font-bold">{{ $actualHours }}</span> / {{ $targetHours }} ч.
                                </span>
                            @endif
                        </div>
                    @elseif ($viewMode === 'teacher')
                        {{ $entity?->last_name ?? 'Преподаватель' }} {{ $entity?->first_name ?? '' }}
                    @else
                        {{ $entity?->name ?? 'Аудитория #' . $entityId }}
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50">
                            <th class="px-3 py-2 text-left font-medium text-gray-500 w-12">Пара</th>
                            @foreach ($dayNames as $i => $day)
                                <th
                                    class="px-3 py-2 text-left font-medium text-gray-500 border-l border-gray-100 dark:border-gray-700">
                                    {{ $day }}<br><span
                                        class="text-xs font-normal">{{ Carbon\Carbon::parse($weekStart)->addDays($i)->format('d.m') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($lessonNumbers as $lessonNum)
                            <tr>
                                <td class="px-3 py-2 text-gray-500 font-medium">{{ $lessonNum }}</td>
                                @foreach ($dayOffsets as $dayOffset)
                                    @php
                                        $date = Carbon\Carbon::parse($weekStart)->addDays($dayOffset)->format('Y-m-d');
                                        $cellLessons = array_filter($lessons->toArray(), fn($l) => $l['date'] === $date && $l['lesson_number'] === $lessonNum);
                                        
                                        $practiceInfo = null;
                                        if (($viewMode === 'group' || $viewMode === 'department') && isset($practiceData[$entityId])) {
                                            $practiceInfo = collect($practiceData[$entityId])->first(function($p) use ($date) {
                                                return $p['date'] === $date;
                                            });
                                        }
                                    @endphp
                                    <td
                                        class="px-3 py-2 border-l border-gray-100 dark:border-gray-700 align-top min-w-[140px] relative {{ $practiceInfo ? 'bg-indigo-50/20 dark:bg-indigo-900/10' : '' }}">
                                        @if ($practiceInfo && $lessonNum === 1)
                                            @php
                                                $pSym = mb_strtolower(trim($practiceInfo['symbol']));
                                                $pType = $practiceInfo['type'];
                                                $pLabel = match(true) {
                                                    $pType === 'edu_practice' || $pSym === 'у' => 'Учебная практика',
                                                    $pType === 'prod_practice' || $pSym === 'п' || $pSym === 'пп' => 'Производственная практика',
                                                    $pType === 'pre_diploma' || $pSym === 'пд' => 'Преддипломная практика',
                                                    $pType === 'exam_session' || $pSym === 'э' => 'Экзаменационная сессия',
                                                    $pSym === 'гп' => 'Подготовка к ГИА',
                                                    $pSym === 'дп' => 'Сдача ГИА',
                                                    default => 'Практика (' . mb_strtoupper($pSym) . ')',
                                                };
                                                $pColor = match(true) {
                                                    mb_stripos($pLabel, 'Учебная') !== false => 'text-indigo-600 bg-indigo-100',
                                                    mb_stripos($pLabel, 'Производственная') !== false => 'text-pink-600 bg-pink-100',
                                                    mb_stripos($pLabel, 'Преддипломная') !== false => 'text-amber-600 bg-amber-100',
                                                    mb_stripos($pLabel, 'сессия') !== false => 'text-purple-600 bg-purple-100',
                                                    mb_stripos($pLabel, 'ГИА') !== false => 'text-red-600 bg-red-100',
                                                    default => 'text-gray-600 bg-gray-100',
                                                };
                                            @endphp
                                            <div class="absolute inset-x-0 top-0 z-10 px-1 py-0.5 text-[9px] font-bold text-center uppercase tracking-tighter {{ $pColor }} rounded-b shadow-sm">
                                                {{ $pLabel }}
                                            </div>
                                        @endif

                                        @foreach ($cellLessons as $lesson)
                                            @php
                                                $isHl = in_array($lesson['id'], $highlightedLessonIds);
                                                $conflictType = $lessonConflictMap[$lesson['id']] ?? null;
                                                $conflictClasses = match ($conflictType) {
                                                    'error' => '!bg-red-50 dark:!bg-red-900/20 !border-red-300 dark:!border-red-700 shadow-md',
                                                    'warning' => '!bg-amber-50 dark:!bg-amber-900/20 !border-amber-300 dark:!border-amber-700 shadow-md',
                                                    default => 'hover:bg-gray-100 dark:hover:bg-gray-900',
                                                };
                                                $discCode = trim($lesson['discipline']['code'] ?? '');
                                                $discName = trim($lesson['discipline']['name'] ?? '—');
                                                $ltCode = $lesson['lesson_type']['code'] ?? '';
                                                $isMdk = preg_match('/^МДК/ui', $discCode);
                                                $isPractice = $ltCode === 'edu_practice' || $ltCode === 'prod_practice' || preg_match('/^(УП|ПП|ПДП|ГИА|ГП|ДП)/ui', $discCode);
                                                $isExam = !$isPractice && ($ltCode === 'exam' || $ltCode === 'test' || $ltCode === 'diff_test' || preg_match('/^Э/ui', $discCode) || mb_strpos(mb_strtolower($discName), 'экзамен') !== false);
                                                
                                                $lessonTypeColor = match (true) {
                                                    $ltCode === 'edu_practice' || preg_match('/^УП/ui', $discCode) => 'border-l-indigo-500 bg-indigo-100/50 dark:bg-indigo-900/30 border-l-4',
                                                    $ltCode === 'prod_practice' || preg_match('/^ПП/ui', $discCode) => 'border-l-pink-500 bg-pink-100/50 dark:bg-pink-900/30 border-l-4',
                                                    preg_match('/^ПДП/ui', $discCode) => 'border-l-amber-500 bg-amber-100/50 dark:bg-amber-900/30 border-l-4',
                                                    preg_match('/^(ГИА|ГП|ДП)/ui', $discCode) => 'border-l-red-600 bg-red-100/50 dark:bg-red-900/30 border-l-4',
                                                    $ltCode === 'exam' || $isExam => 'border-l-purple-600 bg-purple-100/50 dark:bg-purple-900/30 border-l-4',
                                                    default => $isMdk ? 'border-l-teal-400 bg-teal-50/40 dark:bg-teal-900/20 border-l-2' : 'bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700',
                                                };
                                            @endphp
                                            <div class="group relative mb-0.5 p-1.5 pl-2 rounded text-xs leading-tight transition-all duration-300 border cursor-pointer {{ $lessonTypeColor }} {{ $isHl ? '!bg-red-100 dark:!bg-red-900/40 !border-red-500 dark:!border-red-600 ring-2 ring-red-400' : $conflictClasses }}"
                                                wire:click="editLesson({{ $lesson['id'] }})" @if($isHl) data-hl="true" @endif
                                                @if($conflictType)
                                                title="Конфликт: {{ $conflictType == 'error' ? 'Ошибка' : 'Предупреждение' }}" @endif>
                                                
                                                @php
                                                    $isIndicator = ($lesson['is_auto_generated'] ?? false) && 
                                                                  ($isPractice || $isExam) && 
                                                                  empty($lesson['teacher_id']) && 
                                                                  empty($lesson['room_id']);
                                                @endphp

                                                @if(!$isIndicator)
                                                    <div class="font-bold text-gray-900 dark:text-white">
                                                        @if($isMdk || $isPractice || $isExam)
                                                            <span title="{{ $discName }}"
                                                                class="border-b border-dashed border-gray-400 cursor-help">{{ $discCode ?: $discName }}</span>
                                                        @else
                                                            <span title="{{ $discCode }}">{{ $discName }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-gray-500 mt-0.5">
                                                        @if ($viewMode === 'teacher')
                                                            {{ $lesson['group']['name'] ?? '' }}
                                                        @else
                                                            @php
                                                                $tLn = $lesson['teacher']['last_name'] ?? '';
                                                                $tFn = $lesson['teacher']['first_name'] ?? '';
                                                                $tMn = $lesson['teacher']['middle_name'] ?? '';
                                                                $tFi = $tFn ? mb_substr($tFn, 0, 1) . '.' : '';
                                                                $tMi = $tMn ? mb_substr($tMn, 0, 1) . '.' : '';
                                                            @endphp
                                                            {{ $tLn }} {{ $tFi }}{{ $tMi }}
                                                        @endif
                                                    </div>
                                                    <div class="text-[10px] text-gray-400">
                                                        @if ($viewMode !== 'room')
                                                            №{{ $lesson['room']['number'] ?? $lesson['room']['name'] ?? '' }}
                                                            @if (!empty($lesson['room']['building']))
                                                                · {{ $lesson['room']['building']['short_name'] ?? $lesson['room']['building']['name'] ?? '' }}
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="h-4"></div>
                                                @endif
                                                <button wire:click.stop="deleteLesson({{ $lesson['id'] }})"
                                                    wire:confirm="Удалить занятие?"
                                                    class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-500 text-white text-[8px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20">✕</button>
                                            </div>
                                        @endforeach
                                        @if (empty($cellLessons))
                                            @php
                                                $cellGroup = $entity ?? null;
                                                $cellDayOfWeek = (int) \Carbon\Carbon::parse($weekStart)->addDays($dayOffset)->format('N');
                                                $cellCanAdd = true;
                                                if ($cellGroup && method_exists($cellGroup, 'getWorkingDays')) {
                                                    $cellCanAdd = in_array($cellDayOfWeek, $cellGroup->getWorkingDays());
                                                }
                                                if ($cellGroup && method_exists($cellGroup, 'getAllowedLessonNumbersForDay')) {
                                                    $allowedForDay = $cellGroup->getAllowedLessonNumbersForDay($cellDayOfWeek);
                                                    if (!empty($allowedForDay)) {
                                                        $cellCanAdd = $cellCanAdd && in_array($lessonNum, $allowedForDay);
                                                    }
                                                }
                                            @endphp
                                            @if ($cellCanAdd)
                                                <button wire:click="addLesson('{{ $date }}', {{ $lessonNum }}, {{ $entityId }})"
                                                    class="w-full py-2 rounded border-2 border-dashed border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-900/10 text-emerald-500 hover:text-white hover:bg-emerald-500 hover:border-emerald-500 text-sm font-bold transition-all cursor-pointer">
                                                    +
                                                </button>
                                            @else
                                                <div
                                                    class="w-full py-2 rounded border border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/30 text-gray-200 dark:text-gray-700 text-xs text-center">
                                                    —
                                                </div>
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
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-400">
            Нет занятий в этом периоде
        </div>
    @endforelse
    @if ($editing)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            wire:click.self="$set('editing', false)">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 w-full max-w-lg">
                <h3 class="text-lg font-semibold mb-4">
                    Редактирование занятия
                    @if ($editIsPublished)
                        <span class="ml-2 text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-normal">Замена</span>
                    @endif
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Дата</label>
                        <input type="date" wire:model="editDate"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @error('editDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Дисциплина</label>
                        <select wire:model.live="editDisciplineId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="0">Не выбрана</option>
                            @foreach ($this->disciplines as $disc)
                                <option value="{{ $disc->id }}">
                                    {{ $disc->code ? $disc->code . ' — ' . $disc->name : $disc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Номер пары</label>
                        <input type="number" wire:model="editLessonNumber" min="1" max="8"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @error('editLessonNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Преподаватель</label>
                        <select wire:model="editTeacherId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="0">Не назначен</option>
                            @foreach ($this->teachers as $teacher)
                                @php
                                    $teachesDisc = !$this->editDisciplineId || \App\Models\TeacherDiscipline::where('teacher_id', $teacher->id)->where('discipline_id', $this->editDisciplineId)->where(function ($q) {
                                        $q->where('group_id', $this->editGroupId)->orWhereNull('group_id');
                                    })->exists();
                                    $workingDays = $teacher->working_days ?? [];
                                    $workingNums = $teacher->working_lesson_numbers ?? [];
                                    $editDayOfWeek = $this->editDate ? (int) \Carbon\Carbon::parse($this->editDate)->format('N') : 0;
                                    $dayOk = empty($workingDays) || in_array($editDayOfWeek, $workingDays);
                                    $numOk = empty($workingNums) || in_array($this->editLessonNumber, $workingNums);
                                    $tFi = $teacher->first_name ? mb_substr($teacher->first_name, 0, 1) . '.' : '';
                                    $tMi = $teacher->middle_name ? mb_substr($teacher->middle_name, 0, 1) . '.' : '';
                                @endphp
                                @if ($teachesDisc && $dayOk && $numOk)
                                    <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $tFi }}{{ $tMi }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Аудитория</label>
                        <select wire:model="editRoomId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="0">Не назначена</option>
                            @foreach ($this->roomsForTeacher as $room)
                                <option value="{{ $room->id }}">
                                    №{{ $room->number }}{{ $room->building ? ' · ' . ($room->building->short_name ?? $room->building->name) : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Заметки</label>
                        <textarea wire:model="editNotes" rows="3"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2"></textarea>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <button wire:click="deleteLesson({{ $editingLessonId }})" wire:confirm="Удалить занятие?"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Удалить
                    </button>
                    <div class="flex gap-2">
                        <button wire:click="$set('editing', false)"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Отмена</button>
                        <button wire:click="saveLesson"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">{{ $editIsPublished ? 'Сохранить замену' : 'Сохранить' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($showConflictModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            wire:click.self="$set('showConflictModal', false)">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 w-full max-w-4xl max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Конфликты в расписании</h3>
                    <button wire:click="autoFixConflicts"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                        Автоисправление
                    </button>
                </div>
                @if (empty($conflicts))
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg text-emerald-700 dark:text-emerald-300">
                        Конфликтов не найдено.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($conflicts as $conflict)
                            @php
                                $sevClass = match ($conflict['severity'] ?? 'warning') {
                                    'error' => 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10',
                                    'warning' => 'border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10',
                                    default => 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/10',
                                };
                                $typeLabel = match ($conflict['conflict_type']) {
                                    'teacher_window' => 'Окно у препода',
                                    'teacher_min_lessons' => 'Мало пар',
                                    'teacher_parallel' => 'Параллельные пары',
                                    'saturday_lesson_limit' => 'Суббота',
                                    'group_min_lessons' => 'Мало пар у группы',
                                    'group_window' => 'Окно у группы',
                                    'group_shift_mismatch' => 'Смена',
                                    'pe_grouping' => 'Физ-ра',
                                    'room_multi_group' => 'Аудитория',
                                    default => $conflict['conflict_type'],
                                };
                            @endphp
                            <div
                                class="p-3 rounded-lg border text-sm {{ $conflict['is_resolved'] ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10' : $sevClass }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-semibold text-xs px-2 py-0.5 rounded {{ $conflict['severity'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $typeLabel }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $conflict['date'] ?? '' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button
                                            wire:click="highlightConflict('{{ $conflict['date'] }}', {{ $conflict['teacher_id'] ?? 'null' }}, {{ $conflict['group_id'] ?? 'null' }}, {{ $conflict['room_id'] ?? 'null' }}, {{ $conflict['lesson_number'] ?? 'null' }})"
                                            class="text-xs font-bold text-amber-600 hover:text-amber-800 underline decoration-dashed">Подробнее
                                            →</button>
                                        @if (!$conflict['is_resolved'])
                                            <button wire:click="startResolve({{ $conflict['id'] }})"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 ml-2">Разрешить ручками</button>
                                        @endif
                                    </div>
                                </div>
                                <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $conflict['description'] }}</p>
                                @if (!empty($conflict['suggestion']) && !$conflict['is_resolved'])
                                    <p class="mt-1 text-xs text-emerald-600">Совет: {{ $conflict['suggestion'] }}</p>
                                @endif
                                @if ($conflict['is_resolved'])
                                    <p class="mt-1 text-xs text-emerald-600">Разрешено: {{ $conflict['resolution_notes'] ?? '—' }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="flex justify-end mt-4">
                    <button wire:click="$set('showConflictModal', false)"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Закрыть</button>
                </div>
            </div>
        </div>
    @endif
    @if ($resolvingConflictId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            wire:click.self="$set('resolvingConflictId', null)">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Разрешение конфликта</h3>
                <div>
                    <label class="block text-sm font-medium mb-1">Примечание к разрешению</label>
                    <textarea wire:model="resolutionNote" rows="3"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2"
                        placeholder="Опишите, как разрешён конфликт..."></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button wire:click="$set('resolvingConflictId', null)"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Отмена</button>
                    <button wire:click="resolveConflict"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">Подтвердить</button>
                </div>
            </div>
        </div>
    @endif
    @if ($showShareModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            wire:click.self="$set('showShareModal', false)">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Поделиться расписанием</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Тип ссылки</label>
                        <select wire:model.live="shareType"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="week">На неделю (группа/преподаватель)</option>
                            <option value="day">На день (вся кафедра)</option>
                        </select>
                    </div>
                    @if ($shareType === 'week')
                        <div>
                            <label class="block text-sm font-medium mb-1">Просмотр</label>
                            <select wire:model.live="shareViewMode"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <option value="group">По группе</option>
                                <option value="teacher">По преподавателю</option>
                                <option value="room">По аудитории</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Выберите</label>
                            <select wire:model="shareViewId"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
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
                                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium mb-1">Кафедра</label>
                            <select wire:model="shareViewId"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                @foreach ($this->departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Дата</label>
                            <input type="date" wire:model="shareDate"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                    @endif
                    <button wire:click="generateShareLink"
                        class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        Сгенерировать ссылку
                    </button>
                </div>
                <div class="flex justify-end mt-4">
                    <button wire:click="$set('showShareModal', false)"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Закрыть</button>
                </div>
            </div>
        </div>
    @endif
    <span class="hidden border-l-indigo-400 bg-indigo-50/40 dark:bg-indigo-900/15 border-l-pink-400 bg-pink-50/40 dark:bg-pink-900/15 border-l-amber-400 bg-amber-50/40 dark:bg-amber-900/15 border-l-red-400 bg-red-50/40 dark:bg-red-900/15 border-l-purple-400 bg-purple-50/40 dark:bg-purple-900/15 border-l-teal-300 bg-teal-50/30 dark:bg-teal-900/10 bg-gray-50 dark:bg-gray-900/50 !bg-red-100 dark:!bg-red-900/40 !border-red-500 dark:!border-red-600 !bg-red-50 dark:!bg-red-900/20 !border-red-300 dark:!border-red-700 !bg-amber-50 dark:!bg-amber-900/20 !border-amber-300 dark:!border-amber-700 hover:bg-gray-100 dark:hover:bg-gray-900 ring-2 ring-red-400 shadow-md"></span>
</div>