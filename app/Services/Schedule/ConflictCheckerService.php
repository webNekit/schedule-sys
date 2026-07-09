<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\Group;
use App\Models\LessonType;
use App\Models\RoomUnavailability;
use App\Models\ScheduleConflict;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\SportComplexSlot;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConflictCheckerService
{
    public function __construct(
        private readonly SchedulingRuleResolver $rules,
        private readonly CustomRuleEvaluator $customRules,
    ) {}

    /**
     * Уровень конфликта из жёсткости правила: hard → error, иначе warning.
     */
    private function severityLevel(string $key, ?Group $group = null): string
    {
        return $this->rules->severity($key, $group) === 'hard' ? 'error' : 'warning';
    }

    public function checkVersion(int $versionId): array
    {
        return $this->checkVersionForRange($versionId);
    }

    public function checkVersionForRange(
        int $versionId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        ScheduleVersion::findOrFail($versionId);
        $this->rules->load();
        $query = ScheduleLesson::with([
            'group',
            'teacher',
            'room.building',
            'room.roomType',
            'discipline',
            'lessonType',
        ])
            ->where('version_id', $versionId)
            ->where('status', '!=', 'cancelled');

        if ($dateFrom !== null) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where('date', '<=', $dateTo);
        }

        $lessons = $query
            ->orderBy('date')
            ->orderBy('lesson_number')
            ->get();

        $conflicts = [];

        // ── Группировка по дате для ежедневных проверок ──────────────────────
        $groupedByDate = $lessons->groupBy('date');

        foreach ($groupedByDate as $date => $dayLessons) {
            $dateStr = is_string($date) ? $date : $date->format('Y-m-d');

            // 1. Проверка пересечений (Коллизии ресурсов)
            $this->checkCollisions($dayLessons, $dateStr, $versionId, $conflicts);

            // 2. Проверки преподавателей
            foreach ($dayLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
                if ($teacherId) {
                    $this->checkTeacherRules($teacherLessons, $dateStr, $versionId, $conflicts);
                }
            }

            // 3. Проверки групп
            foreach ($dayLessons->groupBy('group_id') as $groupId => $groupLessons) {
                if ($groupId) {
                    $this->checkGroupRules($groupLessons, $dateStr, $versionId, $conflicts);
                }
            }

            // 4. Проверки аудиторий
            foreach ($dayLessons->groupBy('room_id') as $roomId => $roomLessons) {
                if ($roomId) {
                    $this->checkRoomRules($roomLessons, $dateStr, $versionId, $conflicts);
                }
            }

            // 5. Конфликты корпусов
            $this->checkBuildingConflicts($dayLessons, $dateStr, $versionId, $conflicts);
        }

        // 6. Недельные проверки
        $groupedByWeek = $lessons->groupBy(
            fn ($l) => Carbon::parse($l->date)->startOfWeek(Carbon::MONDAY)->format('Y-m-d')
        );
        foreach ($groupedByWeek as $weekStart => $weekLessons) {
            $this->checkWeeklyHoursLimit(collect($weekLessons), $weekStart, $versionId, $conflicts);
        }

        // 7. Авторские (пользовательские) правила
        $this->customRules->evaluate($lessons, $versionId, $conflicts);

        // 8. Резерв пар под выезд в спорткомплекс
        $this->checkSportComplexReservation($lessons, $versionId, $conflicts);

        $this->saveConflicts($versionId, $conflicts, $dateFrom, $dateTo);

        return ScheduleConflict::where('version_id', $versionId)
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->orderBy('date')
            ->orderBy('lesson_number')
            ->get()
            ->toArray();
    }

    /**
     * Проверка коллизий: один ресурс в одно время.
     */
    private function checkCollisions(Collection $dayLessons, string $date, int $versionId, array &$conflicts): void
    {
        foreach ($dayLessons->groupBy('lesson_number') as $num => $lessons) {
            // а) Преподаватель в разных местах
            foreach ($lessons->groupBy('teacher_id') as $tId => $tLessons) {
                if (! $tId || $tLessons->count() <= 1) {
                    continue;
                }

                $uniqueCombo = $tLessons->map(fn ($l) => "{$l->discipline_id}-{$l->room_id}")->unique();
                if ($uniqueCombo->count() > 1) {
                    $teacher = $tLessons->first()->teacher;
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'teacher_parallel', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => (int) $num, 'teacher_id' => (int) $tId,
                        'description' => "Преподаватель {$teacher?->short_name} ведет разные пары одновременно ({$num}-я пара)",
                        'suggestion' => 'Перенести одну из пар на другое время',
                        'is_resolved' => false,
                    ];
                }
            }

            // б) Группа на разных парах
            foreach ($lessons->groupBy('group_id') as $gId => $gLessons) {
                if (! $gId || $gLessons->count() <= 1) {
                    continue;
                }

                $hasDifferentSubgroups = $gLessons->pluck('subgroup_id')->filter()->unique()->count() > 1;
                $hasNullSubgroup = $gLessons->contains(fn ($l) => is_null($l->subgroup_id));

                if (! ($hasDifferentSubgroups && ! $hasNullSubgroup)) {
                    $group = $gLessons->first()->group;
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'group_parallel', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => (int) $num, 'group_id' => (int) $gId,
                        'description' => "Группа {$group->name} стоит на нескольких парах одновременно ({$num}-я пара)",
                        'suggestion' => 'Проверьте подгруппы или перенесите одну из пар',
                        'is_resolved' => false,
                    ];
                }
            }

            // в) Аудитория занята разными преподами
            foreach ($lessons->groupBy('room_id') as $rId => $rLessons) {
                if (! $rId || $rLessons->count() <= 1) {
                    continue;
                }

                $uniqueTeachers = $rLessons->pluck('teacher_id')->unique();
                if ($uniqueTeachers->count() > 1) {
                    $room = $rLessons->first()->room;
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'room_multi_group', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => (int) $num, 'room_id' => (int) $rId,
                        'description' => "Аудитория {$room?->number} занята разными преподавателями ({$num}-я пара)",
                        'suggestion' => 'Перенести одну из пар в другую аудиторию',
                        'is_resolved' => false,
                    ];
                }
            }
        }
    }

    /**
     * Проверка правил для преподавателя.
     */
    private function checkTeacherRules(Collection $lessons, string $date, int $versionId, array &$conflicts): void
    {
        $teacher = $lessons->first()->teacher;
        if (! $teacher) {
            return;
        }

        // 1. Доступность (график, отгулы, метод.день)
        foreach ($lessons as $lesson) {
            if (! $teacher->isAvailableOn($date, $lesson->lesson_number)) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_unavailability', 'severity' => 'error',
                    'date' => $date, 'lesson_number' => $lesson->lesson_number, 'teacher_id' => $teacher->id,
                    'description' => "Преподаватель {$teacher->short_name} недоступен на {$lesson->lesson_number}-й паре (согласно графику или заявкам)",
                    'suggestion' => 'Перенести пару или изменить настройки графика преподавателя',
                    'is_resolved' => false,
                ];
            }
        }

        // 2. Соответствие дисциплине
        $this->checkTeacherDisciplineMatch($lessons, $date, $teacher, $versionId, $conflicts);

        // 3. Окна
        $this->checkTeacherWindows($lessons, $date, $teacher, $versionId, $conflicts);

        // 4. Минимум пар
        $this->checkTeacherMinPairs($lessons, $date, $teacher, $versionId, $conflicts);
    }

    /**
     * Проверка правил для группы.
     */
    private function checkGroupRules(Collection $lessons, string $date, int $versionId, array &$conflicts): void
    {
        $group = $lessons->first()->group;
        if (! $group) {
            return;
        }

        // 1. Соответствие графику (смена, разрешенные пары)
        $this->checkGroupShift($lessons, $date, $group, $versionId, $conflicts);

        // 2. Проверка практики (календарный график)
        $this->checkGroupPracticeOverlap($lessons, $date, $group, $versionId, $conflicts);

        // 3. Окна
        $this->checkGroupWindows($lessons, $date, $group, $versionId, $conflicts);

        // 4. Физкультура
        $this->checkPhysicalEducation($lessons, $date, $group, $versionId, $conflicts);

        // 5. Минимум пар
        $this->checkGroupMinPairs($lessons, $date, $group, $versionId, $conflicts);
    }

    /**
     * Проверка правил для аудитории.
     */
    private function checkRoomRules(Collection $lessons, string $date, int $versionId, array &$conflicts): void
    {
        $room = $lessons->first()->room;
        if (! $room) {
            return;
        }

        foreach ($lessons as $lesson) {
            $group = $lesson->group;
            if (! $this->rules->isEnabled('room_capacity', $group)) {
                continue;
            }
            $studentsCount = $group?->students_count ?? 0;
            if ($room->capacity > 0 && $studentsCount > $room->capacity) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'room_capacity', 'severity' => $this->severityLevel('room_capacity', $group),
                    'date' => $date, 'lesson_number' => $lesson->lesson_number, 'room_id' => $room->id,
                    'description' => "Аудитория {$room->number} (вмест. {$room->capacity}) мала для группы {$lesson->group?->name} ({$studentsCount} чел.)",
                    'suggestion' => 'Подобрать более просторную аудиторию',
                    'is_resolved' => false,
                ];
            }
        }
    }

    // ── Детальные методы проверок ──────────────────────────────────────────

    private function checkTeacherDisciplineMatch(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        if (! $this->rules->isEnabled('teacher_discipline_match')) {
            return;
        }
        $teacherDisciplineIds = TeacherDiscipline::where('teacher_id', $teacher->id)->pluck('discipline_id')->unique()->toArray();
        foreach ($lessons as $lesson) {
            if ($lesson->discipline_id && ! in_array($lesson->discipline_id, $teacherDisciplineIds, true)) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_discipline_mismatch', 'severity' => $this->severityLevel('teacher_discipline_match'),
                    'date' => $date, 'lesson_number' => $lesson->lesson_number, 'teacher_id' => $teacher->id,
                    'description' => "Преподаватель {$teacher->short_name} не привязан к дисциплине «{$lesson->discipline?->name}»",
                    'suggestion' => "Назначить другого преподавателя или добавить дисциплину {$teacher->last_name}",
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkTeacherWindows(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        if (! $this->rules->isEnabled('teacher_no_windows')) {
            return;
        }
        $numbers = $lessons->pluck('lesson_number')->unique()->sort()->values()->toArray();
        if (count($numbers) < 2) {
            return;
        }

        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] > 1) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_window', 'severity' => $this->severityLevel('teacher_no_windows'),
                    'date' => $date, 'lesson_number' => $numbers[$i], 'teacher_id' => $teacher->id,
                    'description' => "Окно у преподавателя {$teacher->short_name}: между {$numbers[$i - 1]}-й и {$numbers[$i]}-й парой",
                    'suggestion' => 'Уплотнить расписание преподавателя',
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkTeacherMinPairs(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        if (! $this->rules->isEnabled('teacher_min_lessons')) {
            return;
        }

        // Минимум проверяем только в выбранные дни недели
        if (! in_array((int) date('N', strtotime($date)), $this->rules->arrayParam('min_lessons_check_weekdays', 'days', [1, 2, 3, 4, 5]), true)) {
            return;
        }

        // Если есть практика — не проверяем минимум
        if ($lessons->contains(fn ($l) => $this->isPracticeLesson($l))) {
            return;
        }

        $min = $this->rules->intParam('teacher_min_lessons', 'min', 2);
        $count = $lessons->pluck('lesson_number')->unique()->count();
        if ($count > 0 && $count < $min) {
            $conflicts[] = [
                'version_id' => $versionId, 'conflict_type' => 'teacher_min_lessons', 'severity' => $this->severityLevel('teacher_min_lessons'),
                'date' => $date, 'lesson_number' => $lessons->first()->lesson_number, 'teacher_id' => $teacher->id,
                'description' => "Мало пар у преподавателя {$teacher->short_name}: всего {$count} (минимум {$min})",
                'suggestion' => 'Добавить еще пары или перенести на другой день',
                'is_resolved' => false,
            ];
        }
    }

    private function checkGroupShift(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $shiftLabel = $group->shift === 1 ? '1-я смена' : '2-я смена';
        foreach ($lessons as $lesson) {
            if (! $group->isAvailableOn($date, $lesson->lesson_number)) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_shift_mismatch', 'severity' => 'error',
                    'date' => $date, 'lesson_number' => $lesson->lesson_number, 'group_id' => $group->id,
                    'description' => "Несоответствие графику: группа {$group->name} ({$shiftLabel}) не может заниматься на {$lesson->lesson_number}-й паре",
                    'suggestion' => 'Перенести в разрешенный слот или изменить настройки графиков пар/курсов',
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkGroupPracticeOverlap(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $carbonDate = Carbon::parse($date);
        $isOnPractice = $group->isOnPractice($carbonDate);

        if ($isOnPractice) {
            foreach ($lessons as $lesson) {
                if (! $this->isPracticeLesson($lesson)) {
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'group_practice_overlap', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => $lesson->lesson_number, 'group_id' => $group->id,
                        'description' => "Группа {$group->name} находится на практике по графику, но ей поставлено теоретическое занятие",
                        'suggestion' => 'Удалите теорию или проверьте даты практики в учебном плане',
                        'is_resolved' => false,
                    ];
                }
            }
        }
    }

    private function checkGroupWindows(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        if (! $this->rules->isEnabled('group_no_windows', $group)) {
            return;
        }
        $numbers = $lessons->pluck('lesson_number')->unique()->sort()->values()->toArray();
        if (count($numbers) < 2) {
            return;
        }

        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] > 1) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_window', 'severity' => $this->severityLevel('group_no_windows', $group),
                    'date' => $date, 'lesson_number' => $numbers[$i], 'group_id' => $group->id,
                    'description' => "Окно у группы {$group->name}: между {$numbers[$i - 1]}-й и {$numbers[$i]}-й парой",
                    'suggestion' => 'Перенести одну из пар для устранения окна',
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkGroupMinPairs(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        if (! $this->rules->isEnabled('group_min_lessons', $group)) {
            return;
        }

        // Минимум проверяем только в выбранные дни недели
        if (! in_array((int) date('N', strtotime($date)), $this->rules->arrayParam('min_lessons_check_weekdays', 'days', [1, 2, 3, 4, 5], $group), true)) {
            return;
        }

        // Если в этот день есть ПРАКТИКА (либо в расписании, либо по календарю) — не проверяем минимум
        if ($lessons->contains(fn ($l) => $this->isPracticeLesson($l)) || $group->isOnPractice(Carbon::parse($date))) {
            return;
        }

        $min = $this->rules->intParam('group_min_lessons', 'min', 3, $group);
        $count = $lessons->pluck('lesson_number')->unique()->count();
        if ($count > 0 && $count < $min) {
            $conflicts[] = [
                'version_id' => $versionId, 'conflict_type' => 'group_min_lessons', 'severity' => $this->severityLevel('group_min_lessons', $group),
                'date' => $date, 'group_id' => $group->id,
                'description' => "Мало пар у группы {$group->name}: всего {$count} в день (минимум {$min})",
                'suggestion' => 'Добавить еще пары',
                'is_resolved' => false,
            ];
        }
    }

    private function checkPhysicalEducation(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        if (! $this->rules->isEnabled('pe_grouping', $group)) {
            return;
        }
        $peLessonTypeIds = LessonType::whereIn('code', ['physical_education', 'swimming'])->pluck('id');
        $isPe = fn ($l) => ($peLessonTypeIds->isNotEmpty() && in_array($l->lesson_type_id ?? 0, $peLessonTypeIds->toArray(), true))
            || (bool) $l->discipline?->isPhysicalEducation();
        $peNumbers = $lessons->filter($isPe)->pluck('lesson_number')->sort()->values();
        $nonPeNumbers = $lessons->reject($isPe)->pluck('lesson_number')->toArray();
        if ($peNumbers->count() < 2) {
            return;
        }

        $min = $peNumbers->min();
        $max = $peNumbers->max();
        foreach ($nonPeNumbers as $nonPe) {
            if ($nonPe > $min && $nonPe < $max) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'pe_grouping', 'severity' => $this->severityLevel('pe_grouping', $group),
                    'date' => $date, 'group_id' => $group->id,
                    'description' => "Физкультура не сдвоена у группы {$group->name}: разбита другими занятиями",
                    'suggestion' => 'Сдвоить физкультуру в начале или конце дня',
                    'is_resolved' => false,
                ];

                return;
            }
        }
    }

    private function checkBuildingConflicts(Collection $dayLessons, string $date, int $versionId, array &$conflicts): void
    {
        // Группы
        foreach ($dayLessons->groupBy('group_id') as $groupId => $groupLessons) {
            $group = $groupLessons->first()->group;
            if (! $this->rules->isEnabled('one_building_per_day_group', $group)) {
                continue;
            }
            $buildings = $groupLessons->filter(fn ($l) => ! $this->isSportRoom($l))->pluck('building_id')->filter()->unique();
            if ($buildings->count() > 1) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_building_conflict', 'severity' => $this->severityLevel('one_building_per_day_group', $group),
                    'date' => $date, 'group_id' => (int) $groupId,
                    'description' => "Группа {$group?->name} в течение дня находится в нескольких корпусах",
                    'suggestion' => 'Перенести все пары группы в один корпус',
                    'is_resolved' => false,
                ];
            }
        }
        // Преподаватели
        if (! $this->rules->isEnabled('one_building_per_day_teacher')) {
            return;
        }
        foreach ($dayLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
            if (! $teacherId) {
                continue;
            }
            $buildings = $teacherLessons->filter(fn ($l) => ! $this->isSportRoom($l))->pluck('building_id')->filter()->unique();
            if ($buildings->count() > 1) {
                $teacher = $teacherLessons->first()->teacher;
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_building_conflict', 'severity' => $this->severityLevel('one_building_per_day_teacher'),
                    'date' => $date, 'teacher_id' => (int) $teacherId,
                    'description' => "Преподаватель {$teacher?->short_name} в течение дня ведет пары в разных корпусах",
                    'suggestion' => 'Перенести все пары преподавателя в один корпус',
                    'is_resolved' => false,
                ];
            }
        }
    }

    /**
     * Пары, отмеченные в «Расписании спорткомплекса», зарезервированы под выезд:
     * на них не должно стоять обычное (не спортивное) занятие.
     *
     * @param  Collection<int, ScheduleLesson>  $lessons
     * @param  array<int, array<string, mixed>>  $conflicts
     */
    private function checkSportComplexReservation(Collection $lessons, int $versionId, array &$conflicts): void
    {
        // Физкультура допускается только на парах 1–4 — спортивное занятие
        // (выезд или зал) после 4-й пары запрещено.
        foreach ($lessons as $lesson) {
            if ($this->isSportRoom($lesson) && (int) $lesson->lesson_number > 4) {
                $dateStr = $lesson->date instanceof Carbon ? $lesson->date->format('Y-m-d') : (string) $lesson->date;
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'pe_after_fourth_pair',
                    'severity' => 'error',
                    'date' => $dateStr,
                    'lesson_number' => $lesson->lesson_number,
                    'group_id' => $lesson->group_id,
                    'discipline_id' => $lesson->discipline_id,
                    'description' => "Физкультура стоит на {$lesson->lesson_number}-й паре — допускаются только пары 1–4 (группа {$lesson->group?->name})",
                    'suggestion' => 'Перенесите физкультуру на пары 1–4.',
                    'is_resolved' => false,
                ];
            }
        }

        $sportSchedule = SportComplexSlot::get()->groupBy('group_id');
        if ($sportSchedule->isEmpty()) {
            return;
        }

        foreach ($lessons->groupBy('group_id') as $groupId => $groupLessons) {
            $slots = $sportSchedule->get($groupId);
            if (! $slots) {
                continue;
            }
            $group = $groupLessons->first()->group;

            foreach ($groupLessons->groupBy('date') as $date => $dayLessons) {
                $dateStr = is_string($date) ? $date : Carbon::parse($date)->format('Y-m-d');
                $weekday = (int) Carbon::parse($dateStr)->format('N');
                $reservedPairs = $slots->where('weekday', $weekday)->pluck('lesson_number')->all();
                if ($reservedPairs === []) {
                    continue;
                }

                foreach ($dayLessons as $lesson) {
                    if (in_array($lesson->lesson_number, $reservedPairs, true) && ! $this->isSportRoom($lesson)) {
                        $conflicts[] = [
                            'version_id' => $versionId,
                            'conflict_type' => 'sport_complex_reserved',
                            'severity' => 'error',
                            'date' => $dateStr,
                            'lesson_number' => $lesson->lesson_number,
                            'group_id' => (int) $groupId,
                            'discipline_id' => $lesson->discipline_id,
                            'description' => "Пара {$lesson->lesson_number} зарезервирована под выезд в спорткомплекс, но занята обычным занятием (группа {$group?->name})",
                            'suggestion' => 'Освободите пару под физкультуру в спорткомплексе или снимите отметку в расписании спорткомплекса.',
                            'is_resolved' => false,
                        ];
                    }
                }
            }
        }
    }

    private function checkWeeklyHoursLimit(Collection $weekLessons, string $weekStartDate, int $versionId, array &$conflicts): void
    {
        foreach ($weekLessons->groupBy('group_id') as $groupId => $groupLessons) {
            $group = $groupLessons->first()->group;
            if (! $this->rules->isEnabled('group_weekly_overload', $group)) {
                continue;
            }
            $maxHoursGroup = $this->rules->intParam('group_weekly_overload', 'max_hours', 36, $group);
            $maxLessonsGroup = (int) ($maxHoursGroup / 2);
            $count = $groupLessons->count();
            if ($count > $maxLessonsGroup) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_overload', 'severity' => $this->severityLevel('group_weekly_overload', $group),
                    'date' => $weekStartDate, 'group_id' => (int) $groupId,
                    'description' => "Группа {$group?->name}: превышена недельная нагрузка — ".($count * 2)." ч (макс {$maxHoursGroup})",
                    'suggestion' => 'Убрать лишние пары',
                    'is_resolved' => false,
                ];
            }
        }
        if (! $this->rules->isEnabled('teacher_weekly_overload')) {
            return;
        }
        foreach ($weekLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
            if (! $teacherId) {
                continue;
            }
            $teacher = $teacherLessons->first()->teacher;
            // Лимит преподавателя: личное поле, иначе значение из правила.
            $maxWeekHours = $teacher?->max_hours_per_week ?? $this->rules->intParam('teacher_weekly_overload', 'max_hours', 36);
            $maxLessons = (int) ($maxWeekHours / 2);
            $count = $teacherLessons->count();
            if ($count > $maxLessons) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_overload', 'severity' => $this->severityLevel('teacher_weekly_overload'),
                    'date' => $weekStartDate, 'teacher_id' => (int) $teacherId,
                    'description' => "Преподаватель {$teacher?->short_name}: превышена нагрузка — ".($count * 2)." ч/нед (макс {$maxWeekHours})",
                    'suggestion' => 'Убрать лишние пары',
                    'is_resolved' => false,
                ];
            }
        }
    }

    // ── Вспомогательные методы ──────────────────────────────────────────────

    private function isSportRoom($lesson): bool
    {
        $typeName = $lesson->room?->roomType?->name ?? '';

        return mb_stripos($typeName, 'спорт') !== false;
    }

    private function isPracticeLesson($lesson): bool
    {
        $code = $lesson->lessonType?->code ?? '';
        if ($code === 'practice') {
            return true;
        }

        return (bool) $lesson->discipline?->isPracticeCategory();
    }

    private function saveConflicts(int $versionId, array $conflicts, ?string $dateFrom = null, ?string $dateTo = null): void
    {
        $query = ScheduleConflict::where('version_id', $versionId);
        if ($dateFrom && $dateTo) {
            $query->whereBetween('date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('date', $dateFrom);
        }
        $query->delete();
        foreach ($conflicts as $conflict) {
            ScheduleConflict::create($conflict);
        }
    }

    public function autoFix(int $versionId): array
    {
        ScheduleVersion::findOrFail($versionId);

        $conflicts = collect($this->checkVersion($versionId));
        $fixed = 0;
        $skipped = 0;
        $details = [];

        // Сначала ошибки, потом предупреждения
        foreach ($conflicts->sortBy(fn ($c) => $c['severity'] === 'error' ? 0 : 1) as $conflict) {
            $result = match ($conflict['conflict_type']) {
                'room_multi_group' => $this->fixRoomMultiGroup($conflict, $versionId),
                'room_capacity' => $this->fixRoomCapacity($conflict, $versionId),
                'group_building_conflict' => $this->fixGroupBuildingConflict($conflict, $versionId),
                'teacher_building_conflict' => $this->fixTeacherBuildingConflict($conflict, $versionId),
                'teacher_window', 'group_window' => $this->fixWindow($conflict, $versionId),
                default => false,
            };

            if ($result) {
                $fixed++;
                $details[] = "Исправлено: {$conflict['conflict_type']} ({$conflict['date']})";
            } else {
                $skipped++;
            }
        }

        $remaining = count($this->checkVersion($versionId));

        return [
            'fixed' => $fixed,
            'skipped' => $skipped,
            'message' => "Исправлено: {$fixed}, пропущено: {$skipped}, осталось конфликтов: {$remaining}",
            'details' => $details,
        ];
    }

    // ── AutoFix: конкретные исправления ───────────────────────────────────

    /**
     * Находит свободную аудиторию для второго занятия в той же паре.
     */
    private function fixRoomMultiGroup(array $conflict, int $versionId): bool
    {
        $date = $this->conflictDate($conflict);
        $roomId = $conflict['room_id'] ?? null;
        $lessonNumber = $conflict['lesson_number'] ?? null;

        if (! $roomId || ! $lessonNumber) {
            return false;
        }

        $lessons = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('room_id', $roomId)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($lessons->count() <= 1) {
            return false;
        }

        foreach ($lessons->skip(1) as $lesson) {
            $studentsCount = $lesson->group?->students_count ?? 0;
            $buildingId = $lesson->building_id;

            $newRoom = $this->findFreeRoom($studentsCount, $lesson->discipline, $date, $lessonNumber, $lesson->id, $buildingId);

            if ($newRoom) {
                $lesson->update(['room_id' => $newRoom->id, 'building_id' => $newRoom->building_id]);

                return true;
            }
        }

        return false;
    }

    /**
     * Заменяет аудиторию на более вместительную.
     */
    private function fixRoomCapacity(array $conflict, int $versionId): bool
    {
        $date = $this->conflictDate($conflict);
        $roomId = $conflict['room_id'] ?? null;
        $lessonNumber = $conflict['lesson_number'] ?? null;

        if (! $roomId || ! $lessonNumber) {
            return false;
        }

        $lesson = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('room_id', $roomId)
            ->where('lesson_number', $lessonNumber)
            ->first();

        if (! $lesson) {
            return false;
        }

        $studentsCount = $lesson->group?->students_count ?? 0;
        $newRoom = $this->findFreeRoom($studentsCount, $lesson->discipline, $date, $lessonNumber, $lesson->id);

        if ($newRoom) {
            $lesson->update(['room_id' => $newRoom->id, 'building_id' => $newRoom->building_id]);

            return true;
        }

        return false;
    }

    /**
     * Переносит все пары группы в её основной корпус.
     */
    private function fixGroupBuildingConflict(array $conflict, int $versionId): bool
    {
        $date = $this->conflictDate($conflict);
        $groupId = $conflict['group_id'] ?? null;

        if (! $groupId) {
            return false;
        }

        $group = Group::find($groupId);
        if (! $group) {
            return false;
        }

        $primaryBuildingId = $group->groupBuildings()->where('is_primary', true)->first()?->building_id
            ?? $group->groupBuildings()->first()?->building_id;

        if (! $primaryBuildingId) {
            return false;
        }

        $lessons = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('group_id', $groupId)
            ->where('building_id', '!=', $primaryBuildingId)
            ->where('status', '!=', 'cancelled')
            ->whereHas('room', fn ($q) => $q->whereDoesntHave('roomType', fn ($rt) => $rt->where('name', 'like', '%спорт%')))
            ->get();

        $anyFixed = false;
        foreach ($lessons as $lesson) {
            $newRoom = $this->findFreeRoom(
                $group->students_count,
                $lesson->discipline,
                $date,
                $lesson->lesson_number,
                $lesson->id,
                $primaryBuildingId
            );

            if ($newRoom) {
                $lesson->update(['room_id' => $newRoom->id, 'building_id' => $newRoom->building_id]);
                $anyFixed = true;
            }
        }

        return $anyFixed;
    }

    /**
     * Переносит пары преподавателя в один корпус (в тот, где их больше).
     */
    private function fixTeacherBuildingConflict(array $conflict, int $versionId): bool
    {
        $date = $this->conflictDate($conflict);
        $teacherId = $conflict['teacher_id'] ?? null;

        if (! $teacherId) {
            return false;
        }

        $lessons = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('teacher_id', $teacherId)
            ->where('status', '!=', 'cancelled')
            ->get();

        // Определяем «победный» корпус (наибольшее количество пар)
        $buildingCounts = $lessons->filter(fn ($l) => ! $this->isSportRoom($l))
            ->groupBy('building_id')
            ->map->count();

        if ($buildingCounts->isEmpty()) {
            return false;
        }

        $targetBuildingId = $buildingCounts->sortDesc()->keys()->first();
        $anyFixed = false;

        foreach ($lessons->filter(fn ($l) => ! $this->isSportRoom($l) && $l->building_id !== $targetBuildingId) as $lesson) {
            $studentsCount = $lesson->group?->students_count ?? 0;
            $newRoom = $this->findFreeRoom($studentsCount, $lesson->discipline, $date, $lesson->lesson_number, $lesson->id, $targetBuildingId);

            if ($newRoom) {
                $lesson->update(['room_id' => $newRoom->id, 'building_id' => $newRoom->building_id]);
                $anyFixed = true;
            }
        }

        return $anyFixed;
    }

    /**
     * Устраняет окно: пытается сдвинуть «изолированную» пару ближе к основному блоку.
     */
    private function fixWindow(array $conflict, int $versionId): bool
    {
        $date = $this->conflictDate($conflict);
        $lessonNumber = $conflict['lesson_number'] ?? null;

        if (! $lessonNumber) {
            return false;
        }

        // Работаем с полем group_id или teacher_id в зависимости от типа конфликта
        $isGroup = $conflict['conflict_type'] === 'group_window';
        $entityId = $isGroup ? ($conflict['group_id'] ?? null) : ($conflict['teacher_id'] ?? null);

        if (! $entityId) {
            return false;
        }

        $query = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('status', '!=', 'cancelled');

        if ($isGroup) {
            $query->where('group_id', $entityId);
        } else {
            $query->where('teacher_id', $entityId);
        }

        $lessons = $query->orderBy('lesson_number')->get();
        $numbers = $lessons->pluck('lesson_number')->sort()->values()->toArray();

        if (count($numbers) < 2) {
            return false;
        }

        // Ищем первое окно и пытаемся сдвинуть пару после него на слот до окна
        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] <= 1) {
                continue;
            }

            $targetSlot = $numbers[$i - 1] + 1; // Слот, закрывающий окно
            $lessonToMove = $lessons->firstWhere('lesson_number', $numbers[$i]);

            if (! $lessonToMove) {
                continue;
            }

            // Проверяем, что целевой слот свободен для преподавателя, группы и аудитории
            $teacherBusy = $this->checkTeacherConflict($lessonToMove->teacher_id, $date, $targetSlot, $lessonToMove->id);
            $groupBusy = $this->checkGroupConflict($lessonToMove->group_id, $date, $targetSlot, $lessonToMove->id);
            $roomBusy = $lessonToMove->room_id
                ? $this->checkRoomConflict($lessonToMove->room_id, $date, $targetSlot, $lessonToMove->id)
                : false;

            if (! $teacherBusy && ! $groupBusy && ! $roomBusy) {
                $lessonToMove->update(['lesson_number' => $targetSlot]);

                return true;
            }
        }

        return false;
    }

    // ── Вспомогательные для autoFix ───────────────────────────────────────

    private function findFreeRoom(int $studentsCount, ?object $discipline, string $date, int $lessonNumber, int $excludeLessonId, ?int $preferBuildingId = null): ?Room
    {
        $query = Room::where('is_active', true)
            ->where('is_available_for_booking', true)
            ->where('capacity', '>=', $studentsCount);

        if ($discipline?->requires_lab) {
            $query->whereHas('roomType', fn ($q) => $q->where('requires_lab', true));
        }

        if ($preferBuildingId) {
            $query->where('building_id', $preferBuildingId);
        }

        foreach ($query->get() as $room) {
            if (! $this->checkRoomConflict($room->id, $date, $lessonNumber, $excludeLessonId)) {
                return $room;
            }
        }

        // Если не нашли в предпочтительном корпусе — ищем в любом
        if ($preferBuildingId) {
            return $this->findFreeRoom($studentsCount, $discipline, $date, $lessonNumber, $excludeLessonId);
        }

        return null;
    }

    private function conflictDate(array $conflict): string
    {
        $date = $conflict['date'];

        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
    }

    // Публичные методы для генератора
    public function checkTeacherConflict(int $teacherId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        $teacher = Teacher::find($teacherId);
        if ($teacher && ! $teacher->isAvailableOn($date, $lessonNumber)) {
            return true;
        }

        return ScheduleLesson::where('teacher_id', $teacherId)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->when($excludeLessonId, fn ($q) => $q->where('id', '!=', $excludeLessonId))
            ->exists();
    }

    public function checkGroupConflict(int $groupId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        $group = Group::find($groupId);
        // Тут проверяем ТОЛЬКО смену/график, не практику
        if ($group && ! $group->isAvailableOn($date, $lessonNumber)) {
            return true;
        }

        return ScheduleLesson::where('group_id', $groupId)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->when($excludeLessonId, fn ($q) => $q->where('id', '!=', $excludeLessonId))
            ->exists();
    }

    public function checkRoomConflict(int $roomId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        // Проверяем плановую недоступность аудитории
        if ($this->isRoomUnavailable($roomId, $date)) {
            return true;
        }

        return ScheduleLesson::where('room_id', $roomId)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->when($excludeLessonId, fn ($q) => $q->where('id', '!=', $excludeLessonId))
            ->exists();
    }

    private function isRoomUnavailable(int $roomId, string $date): bool
    {
        return RoomUnavailability::where('room_id', $roomId)
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->exists();
    }
}
