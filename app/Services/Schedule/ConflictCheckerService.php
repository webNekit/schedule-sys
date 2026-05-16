<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\Group;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\ScheduleConflict;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConflictCheckerService
{
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
            ->orderBy('group_id')
            ->orderBy('lesson_number')
            ->get();
        $conflicts = [];
        // ── Проверки по каждому дню ─────────────────────────────────────────
        $groupedByDate = $lessons->groupBy('date');
        foreach ($groupedByDate as $date => $dayLessonsRaw) {
            $dayLessons = collect($dayLessonsRaw);
            // Преподаватели
            foreach ($dayLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
                $teacher = $teacherLessons->first()->teacher;
                if (! $teacher) {
                    continue;
                }
                $this->checkTeacherWindows($teacherLessons, $date, $teacher, $versionId, $conflicts);
                $this->checkTeacherMinPairs($teacherLessons, $date, $teacher, $versionId, $conflicts);
                $this->checkTeacherParallelLessons($teacherLessons, $date, $teacher, $versionId, $conflicts);
                $this->checkSaturdayTeacher($teacherLessons, $date, $teacher, $versionId, $conflicts);
                $this->checkTeacherDisciplineMatch($teacherLessons, $date, $teacher, $versionId, $conflicts);
            }
            // Группы
            foreach ($dayLessons->groupBy('group_id') as $groupId => $groupLessons) {
                $group = $groupLessons->first()->group;
                if (! $group) {
                    continue;
                }
                $this->checkGroupMinPairs($groupLessons, $date, $group, $versionId, $conflicts);
                $this->checkGroupWindows($groupLessons, $date, $group, $versionId, $conflicts);
                $this->checkGroupShift($groupLessons, $date, $group, $versionId, $conflicts);
                $this->checkPhysicalEducation($groupLessons, $date, $group, $versionId, $conflicts);
            }
            // Аудитории
            foreach ($dayLessons->groupBy('room_id') as $roomId => $roomLessons) {
                $this->checkRoomMultiGroup($roomLessons, $date, (int) $roomId, $versionId, $conflicts);
            }
            // ── НОВОЕ: конфликты корпусов ──
            $this->checkBuildingConflicts($dayLessons, $date, $versionId, $conflicts);
        }
        // ── НОВОЕ: недельная нагрузка ────────────────────────────────────────
        $groupedByWeek = $lessons->groupBy(
            fn ($l) => Carbon::parse($l->date)->startOfWeek(Carbon::MONDAY)->format('Y-m-d')
        );
        foreach ($groupedByWeek as $weekStart => $weekLessons) {
            $this->checkWeeklyHoursLimit(collect($weekLessons), $weekStart, $versionId, $conflicts);
        }
        $this->saveConflicts($versionId, $conflicts, $dateFrom, $dateTo);

        return ScheduleConflict::where('version_id', $versionId)
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->orderBy('date')
            ->orderBy('lesson_number')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // ПРОВЕРКИ ПРЕПОДАВАТЕЛЕЙ
    // =========================================================================
    private function checkTeacherWindows(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        $numbers = $lessons->pluck('lesson_number')->sort()->values()->toArray();
        if (count($numbers) < 2) {
            return;
        }
        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] > 1) {
                $second = $lessons->firstWhere('lesson_number', $numbers[$i]);
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'teacher_window',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => $numbers[$i],
                    'teacher_id' => $teacher->id,
                    'description' => "Окно у преподавателя {$teacher->last_name} {$teacher->first_name}: между {$numbers[$i - 1]}-й и {$numbers[$i]}-й парой",
                    'suggestion' => "Перенести {$this->detail($second)} на ".($numbers[$i - 1] + 1).'-ю пару',
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkTeacherMinPairs(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        $count = $lessons->pluck('lesson_number')->unique()->count();
        if ($count === 1) {
            $lesson = $lessons->first();
            $conflicts[] = [
                'version_id' => $versionId,
                'conflict_type' => 'teacher_min_lessons',
                'severity' => 'warning',
                'date' => $date,
                'lesson_number' => $lesson->lesson_number,
                'teacher_id' => $teacher->id,
                'description' => "Мало пар у преподавателя {$teacher->last_name} {$teacher->first_name}: всего 1 пара в день",
                'suggestion' => "Добавить ещё 1–2 пары для {$teacher->last_name} в этот день",
                'is_resolved' => false,
            ];
        }
    }

    private function checkTeacherParallelLessons(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        foreach ($lessons->groupBy('lesson_number') as $number => $sameTimeLessons) {
            if ($sameTimeLessons->count() > 1 && ! $this->isPeOrForeignTeacher($sameTimeLessons)) {
                $groups = $sameTimeLessons->pluck('group.name')->implode(', ');
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'teacher_parallel',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => (int) $number,
                    'teacher_id' => $teacher->id,
                    'description' => "Параллельные пары у {$teacher->last_name} {$teacher->first_name}: {$number}-я пара, группы: {$groups}",
                    'suggestion' => "Перенести одну из групп ({$groups}) на другое время",
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkSaturdayTeacher(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        if ((int) date('N', strtotime($date)) !== 6) {
            return;
        }
        foreach ($lessons as $lesson) {
            if ($lesson->lesson_number > 5) {
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'saturday_lesson_limit',
                    'severity' => 'warning',
                    'date' => $date,
                    'lesson_number' => $lesson->lesson_number,
                    'teacher_id' => $teacher->id,
                    'description' => "Суббота: {$teacher->last_name} — {$lesson->lesson_number}-я пара",
                    'suggestion' => 'Перенести на утро или на другой день',
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkTeacherDisciplineMatch(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        $teacherDisciplineIds = TeacherDiscipline::where('teacher_id', $teacher->id)
            ->pluck('discipline_id')
            ->unique()
            ->toArray();
        foreach ($lessons as $lesson) {
            if ($lesson->discipline_id && ! in_array($lesson->discipline_id, $teacherDisciplineIds, true)) {
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'teacher_discipline_mismatch',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => $lesson->lesson_number,
                    'teacher_id' => $teacher->id,
                    'description' => "Преподаватель {$teacher->last_name} {$teacher->first_name} не привязан к дисциплине «{$lesson->discipline?->name}»",
                    'suggestion' => "Назначить другого преподавателя или добавить дисциплину {$teacher->last_name}",
                    'is_resolved' => false,
                ];
            }
        }
    }

    // =========================================================================
    // ПРОВЕРКИ ГРУПП
    // =========================================================================
    private function checkGroupMinPairs(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $count = $lessons->pluck('lesson_number')->unique()->count();
        if ($count > 0 && $count < 3) {
            $conflicts[] = [
                'version_id' => $versionId,
                'conflict_type' => 'group_min_lessons',
                'severity' => 'warning',
                'date' => $date,
                'group_id' => $group->id,
                'description' => "Мало пар у группы {$group->name}: всего {$count} в день (минимум 3)",
                'suggestion' => 'Добавить ещё '.(3 - $count)." пары для группы {$group->name}",
                'is_resolved' => false,
            ];
        }
    }

    private function checkGroupWindows(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $numbers = $lessons->pluck('lesson_number')->sort()->values()->toArray();
        if (count($numbers) < 2) {
            return;
        }
        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] > 1) {
                $lesson = $lessons->firstWhere('lesson_number', $numbers[$i]);
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'group_window',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => $numbers[$i],
                    'group_id' => $group->id,
                    'description' => "Окно у группы {$group->name}: между {$numbers[$i - 1]}-й и {$numbers[$i]}-й парой",
                    'suggestion' => "Перенести {$this->detail($lesson)} на ".($numbers[$i - 1] + 1).'-ю пару',
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkGroupShift(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $allowedNumbers = $group->shift === 1 ? range(1, 5) : range(3, 7);
        $shiftLabel = $group->shift === 1 ? '1-я смена (пары 1–5)' : '2-я смена (пары 3–7)';
        foreach ($lessons as $lesson) {
            if (! in_array($lesson->lesson_number, $allowedNumbers, true)) {
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'group_shift_mismatch',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => $lesson->lesson_number,
                    'group_id' => $group->id,
                    'description' => "Несоответствие смены: группа {$group->name} ({$shiftLabel}) стоит на {$lesson->lesson_number}-й паре",
                    'suggestion' => "Перенести {$this->detail($lesson)} в допустимый слот ({$shiftLabel})",
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkPhysicalEducation(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $peLessonTypeIds = LessonType::whereIn('code', ['physical_education', 'swimming'])->pluck('id');
        $isPe = fn ($l) => ($peLessonTypeIds->isNotEmpty() && in_array($l->lesson_type_id ?? 0, $peLessonTypeIds->toArray(), true))
            || preg_match('/физ|спорт|бассейн/ui', $l->discipline?->name ?? '');
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
                    'version_id' => $versionId,
                    'conflict_type' => 'pe_grouping',
                    'severity' => 'warning',
                    'date' => $date,
                    'group_id' => $group->id,
                    'description' => "Физкультура не сдвоена у группы {$group->name}: пары {$peNumbers->implode(', ')} разбиты другими занятиями",
                    'suggestion' => "Сдвоить физкультуру (пары {$min}–{$max}) в начале или конце дня",
                    'is_resolved' => false,
                ];

                return;
            }
        }
    }

    // =========================================================================
    // ПРОВЕРКИ АУДИТОРИЙ
    // =========================================================================
    private function checkRoomMultiGroup(Collection $lessons, string $date, int $roomId, int $versionId, array &$conflicts): void
    {
        foreach ($lessons->groupBy('lesson_number') as $number => $sameTimeLessons) {
            $uniqueGroups = $sameTimeLessons->pluck('group.name')->filter()->unique();
            if ($uniqueGroups->count() > 1) {
                $room = $sameTimeLessons->first()->room;
                $roomName = $room?->number ?? (string) $roomId;
                $groupsStr = $uniqueGroups->implode(', ');
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'room_multi_group',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => (int) $number,
                    'room_id' => $roomId,
                    'description' => "Несколько групп в аудитории {$roomName}: {$number}-я пара, группы: {$groupsStr}",
                    'suggestion' => "Перенести одну из групп ({$groupsStr}) в другую аудиторию",
                    'is_resolved' => false,
                ];
            }
        }
    }

    // =========================================================================
    // НОВЫЕ ПРОВЕРКИ: КОРПУСА И НЕДЕЛЬНАЯ НАГРУЗКА
    // =========================================================================
    /**
     * Группа и преподаватель не должны быть в двух корпусах за один день.
     * Исключение: спортивный зал.
     */
    private function checkBuildingConflicts(Collection $dayLessons, string $date, int $versionId, array &$conflicts): void
    {
        // Группы
        foreach ($dayLessons->groupBy('group_id') as $groupId => $groupLessons) {
            $buildings = $groupLessons
                ->filter(fn ($l) => ! $this->isSportRoom($l))
                ->pluck('building_id')
                ->filter()
                ->unique();
            if ($buildings->count() > 1) {
                $group = $groupLessons->first()->group;
                $buildingNames = $groupLessons
                    ->filter(fn ($l) => ! $this->isSportRoom($l))
                    ->map(fn ($l) => $l->room?->building?->short_name ?? '?')
                    ->unique()
                    ->implode(', ');
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'group_building_conflict',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => null,
                    'group_id' => (int) $groupId,
                    'description' => "Группа {$group?->name} в {$date} находится в нескольких корпусах: {$buildingNames}",
                    'suggestion' => "Перенести все пары группы {$group?->name} в один корпус на этот день",
                    'is_resolved' => false,
                ];
            }
        }
        // Преподаватели
        foreach ($dayLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
            if (! $teacherId) {
                continue;
            }
            $buildings = $teacherLessons
                ->filter(fn ($l) => ! $this->isSportRoom($l))
                ->pluck('building_id')
                ->filter()
                ->unique();
            if ($buildings->count() > 1) {
                $teacher = $teacherLessons->first()->teacher;
                $buildingNames = $teacherLessons
                    ->filter(fn ($l) => ! $this->isSportRoom($l))
                    ->map(fn ($l) => $l->room?->building?->short_name ?? '?')
                    ->unique()
                    ->implode(', ');
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'teacher_building_conflict',
                    'severity' => 'error',
                    'date' => $date,
                    'lesson_number' => null,
                    'teacher_id' => (int) $teacherId,
                    'description' => "Преподаватель {$teacher?->last_name} {$teacher?->first_name} в {$date} ведёт пары в нескольких корпусах: {$buildingNames}",
                    'suggestion' => "Перенести все пары {$teacher?->last_name} в один корпус на этот день",
                    'is_resolved' => false,
                ];
            }
        }
    }

    /**
     * Недельная нагрузка групп и преподавателей не должна превышать 36 ч (18 пар).
     */
    private function checkWeeklyHoursLimit(Collection $weekLessons, string $weekStartDate, int $versionId, array &$conflicts): void
    {
        $maxLessonsGroup = 18; // 36 ч / 2 ч на пару
        // Группы
        foreach ($weekLessons->groupBy('group_id') as $groupId => $groupLessons) {
            $count = $groupLessons->where('status', '!=', 'cancelled')->count();
            if ($count > $maxLessonsGroup) {
                $group = $groupLessons->first()->group;
                $excess = $count - $maxLessonsGroup;
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'group_overload',
                    'severity' => 'warning',
                    'date' => $weekStartDate,
                    'lesson_number' => null,
                    'group_id' => (int) $groupId,
                    'description' => "Группа {$group?->name}: превышена недельная нагрузка — ".($count * 2).' ч (максимум 36)',
                    'suggestion' => "Убрать {$excess} пар у группы {$group?->name} на неделе с {$weekStartDate}",
                    'is_resolved' => false,
                ];
            }
        }
        // Преподаватели
        foreach ($weekLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
            if (! $teacherId) {
                continue;
            }
            $teacher = $teacherLessons->first()->teacher;
            $maxWeekHours = $teacher?->max_hours_per_week ?? 36;
            $maxLessonsForTeacher = (int) ($maxWeekHours / 2);
            $count = $teacherLessons->where('status', '!=', 'cancelled')->count();
            if ($count > $maxLessonsForTeacher) {
                $excess = $count - $maxLessonsForTeacher;
                $conflicts[] = [
                    'version_id' => $versionId,
                    'conflict_type' => 'teacher_overload',
                    'severity' => 'warning',
                    'date' => $weekStartDate,
                    'lesson_number' => null,
                    'teacher_id' => (int) $teacherId,
                    'description' => "Преподаватель {$teacher?->last_name}: превышена нагрузка — ".($count * 2)." ч/нед (максимум {$maxWeekHours})",
                    'suggestion' => "Убрать {$excess} пар у {$teacher?->last_name} на неделе с {$weekStartDate}",
                    'is_resolved' => false,
                ];
            }
        }
    }

    // =========================================================================
    // AUTO-FIX
    // =========================================================================
    /**
     * Автоматически исправляет конфликты, поддающиеся автоматическому решению.
     *
     * @return array{fixed: int, skipped: int, message: string, details: array<string>}
     */
    public function autoFix(int $versionId): array
    {
        $fixed = 0;
        $skipped = 0;
        $messages = [];
        $conflicts = ScheduleConflict::where('version_id', $versionId)
            ->where('is_resolved', false)
            ->orderByRaw("CASE WHEN severity = 'error' THEN 0 ELSE 1 END")
            ->get();
        foreach ($conflicts as $conflict) {
            $result = match ($conflict->conflict_type) {
                'room_multi_group' => $this->fixRoomConflict($conflict, $versionId),
                'teacher_window' => $this->fixTeacherWindow($conflict, $versionId),
                'group_window' => $this->fixGroupWindow($conflict, $versionId),
                'group_shift_mismatch' => $this->fixGroupShift($conflict, $versionId),
                'teacher_parallel' => ['fixed' => false, 'reason' => 'требует ручного назначения замены'],
                default => ['fixed' => false, 'reason' => 'требует ручного вмешательства'],
            };
            if ($result['fixed']) {
                $fixed++;
                $conflict->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                    'resolution_notes' => 'Автоматически: '.($result['reason'] ?? ''),
                ]);
                $messages[] = "✓ [{$conflict->conflict_type}] {$result['reason']}";
            } else {
                $skipped++;
            }
        }

        return [
            'fixed' => $fixed,
            'skipped' => $skipped,
            'message' => $fixed > 0
                ? "Исправлено {$fixed} конфликтов, пропущено {$skipped} (требуют ручного решения)"
                : 'Автоматически исправляемых конфликтов не найдено',
            'details' => $messages,
        ];
    }

    // ── Фиксеры ──────────────────────────────────────────────────────────────
    private function fixRoomConflict(ScheduleConflict $conflict, int $versionId): array
    {
        if (! $conflict->room_id || ! $conflict->lesson_number) {
            return ['fixed' => false, 'reason' => 'нет данных'];
        }
        $lessons = ScheduleLesson::where('version_id', $versionId)
            ->where('room_id', $conflict->room_id)
            ->where('date', $conflict->date)
            ->where('lesson_number', $conflict->lesson_number)
            ->where('status', '!=', 'cancelled')
            ->get();
        if ($lessons->count() < 2) {
            return ['fixed' => false, 'reason' => 'конфликт уже устранён'];
        }
        $lessonToMove = $lessons->skip(1)->first();
        $busyRoomIds = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $conflict->date)
            ->where('lesson_number', $conflict->lesson_number)
            ->where('status', '!=', 'cancelled')
            ->pluck('room_id')
            ->toArray();
        $group = $lessonToMove->group;
        $capacity = $group?->students_count ?? 1;
        $freeRoom = Room::where('is_active', true)
            ->where('is_available_for_booking', true)
            ->whereNotIn('id', $busyRoomIds)
            ->where('capacity', '>=', $capacity)
            ->when($lessonToMove->building_id, fn ($q) => $q->where('building_id', $lessonToMove->building_id))
            ->orderBy('capacity')
            ->first();
        if (! $freeRoom) {
            return ['fixed' => false, 'reason' => 'нет свободной подходящей аудитории'];
        }
        $lessonToMove->update(['room_id' => $freeRoom->id]);

        return ['fixed' => true, 'reason' => "Группа {$group?->name} перемещена в ауд. {$freeRoom->number}"];
    }

    private function fixTeacherWindow(ScheduleConflict $conflict, int $versionId): array
    {
        if (! $conflict->teacher_id || ! $conflict->lesson_number) {
            return ['fixed' => false, 'reason' => 'нет данных'];
        }
        $date = is_string($conflict->date) ? $conflict->date : $conflict->date->format('Y-m-d');
        $lessonToMove = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('teacher_id', $conflict->teacher_id)
            ->where('lesson_number', $conflict->lesson_number)
            ->where('status', '!=', 'cancelled')
            ->first();
        if (! $lessonToMove) {
            return ['fixed' => false, 'reason' => 'урок не найден'];
        }
        for ($slot = $conflict->lesson_number - 1; $slot >= 1; $slot--) {
            if ($this->slotIsFree($versionId, $date, $slot, $conflict->teacher_id, $lessonToMove->group_id, $lessonToMove->room_id, $lessonToMove->id)) {
                $lessonToMove->update(['lesson_number' => $slot]);

                return ['fixed' => true, 'reason' => "Пара перемещена на {$slot}-й слот (окно устранено)"];
            }
        }

        return ['fixed' => false, 'reason' => 'нет свободного слота для устранения окна'];
    }

    private function fixGroupWindow(ScheduleConflict $conflict, int $versionId): array
    {
        if (! $conflict->group_id || ! $conflict->lesson_number) {
            return ['fixed' => false, 'reason' => 'нет данных'];
        }
        $date = is_string($conflict->date) ? $conflict->date : $conflict->date->format('Y-m-d');
        $lessonToMove = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('group_id', $conflict->group_id)
            ->where('lesson_number', $conflict->lesson_number)
            ->where('status', '!=', 'cancelled')
            ->first();
        if (! $lessonToMove) {
            return ['fixed' => false, 'reason' => 'урок не найден'];
        }
        $group = $lessonToMove->group;
        $allowedSlots = $group?->getAllowedLessonNumbers() ?: range(1, 7);
        for ($slot = $conflict->lesson_number - 1; $slot >= 1; $slot--) {
            if (! in_array($slot, $allowedSlots, true)) {
                continue;
            }
            if ($this->slotIsFree($versionId, $date, $slot, $lessonToMove->teacher_id, $conflict->group_id, $lessonToMove->room_id, $lessonToMove->id)) {
                $lessonToMove->update(['lesson_number' => $slot]);

                return ['fixed' => true, 'reason' => "Пара группы {$group?->name} перемещена на {$slot}-й слот"];
            }
        }

        return ['fixed' => false, 'reason' => 'нет свободного слота'];
    }

    private function fixGroupShift(ScheduleConflict $conflict, int $versionId): array
    {
        if (! $conflict->group_id || ! $conflict->lesson_number) {
            return ['fixed' => false, 'reason' => 'нет данных'];
        }
        $date = is_string($conflict->date) ? $conflict->date : $conflict->date->format('Y-m-d');
        $lesson = ScheduleLesson::where('version_id', $versionId)
            ->where('date', $date)
            ->where('group_id', $conflict->group_id)
            ->where('lesson_number', $conflict->lesson_number)
            ->where('status', '!=', 'cancelled')
            ->first();
        if (! $lesson) {
            return ['fixed' => false, 'reason' => 'урок не найден'];
        }
        $group = $lesson->group;
        $allowedSlots = $group?->getAllowedLessonNumbers() ?: [];
        if (empty($allowedSlots)) {
            return ['fixed' => false, 'reason' => 'не определены допустимые слоты смены'];
        }
        foreach ($allowedSlots as $slot) {
            if ($slot === $lesson->lesson_number) {
                continue;
            }
            if ($this->slotIsFree($versionId, $date, $slot, $lesson->teacher_id, $conflict->group_id, $lesson->room_id, $lesson->id)) {
                $lesson->update(['lesson_number' => $slot, 'shift' => $group->shift]);

                return ['fixed' => true, 'reason' => "Группа {$group?->name}: пара перемещена на {$slot}-й слот нужной смены"];
            }
        }

        return ['fixed' => false, 'reason' => 'нет свободного допустимого слота этой смены'];
    }

    // =========================================================================
    // ПУБЛИЧНЫЕ МЕТОДЫ ПРОВЕРКИ СЛОТОВ (используются в генераторе)
    // =========================================================================
    public function checkTeacherConflict(int $teacherId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        return $this->buildSlotQuery('teacher_id', $teacherId, $date, $lessonNumber, $excludeLessonId)->exists();
    }

    public function checkRoomConflict(int $roomId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        return $this->buildSlotQuery('room_id', $roomId, $date, $lessonNumber, $excludeLessonId)->exists();
    }

    public function checkGroupConflict(int $groupId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        return $this->buildSlotQuery('group_id', $groupId, $date, $lessonNumber, $excludeLessonId)->exists();
    }

    public function resolveConflict(int $conflictId, int $userId, string $resolution): void
    {
        ScheduleConflict::where('id', $conflictId)->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $resolution,
        ]);
    }

    // =========================================================================
    // ВСПОМОГАТЕЛЬНЫЕ ПРИВАТНЫЕ МЕТОДЫ
    // =========================================================================
    /**
     * Проверяет, что слот свободен для всех трёх участников.
     */
    private function slotIsFree(int $versionId, string $date, int $slot, ?int $teacherId, ?int $groupId, ?int $roomId, ?int $excludeId = null): bool
    {
        if ($teacherId && $this->buildSlotQuery('teacher_id', $teacherId, $date, $slot, $excludeId)->exists()) {
            return false;
        }
        if ($groupId && $this->buildSlotQuery('group_id', $groupId, $date, $slot, $excludeId)->exists()) {
            return false;
        }
        if ($roomId && $this->buildSlotQuery('room_id', $roomId, $date, $slot, $excludeId)->exists()) {
            return false;
        }

        return true;
    }

    private function buildSlotQuery(string $field, int $value, string $date, int $lessonNumber, ?int $excludeId = null)
    {
        $q = ScheduleLesson::where($field, $value)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled');
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }

        return $q;
    }

    private function isSportRoom($lesson): bool
    {
        $typeName = $lesson->room?->roomType?->name ?? '';

        return mb_stripos($typeName, 'спорт') !== false;
    }

    private function isPeOrForeignTeacher(Collection $lessons): bool
    {
        if ($lessons->isEmpty()) {
            return false;
        }
        $lesson = $lessons->first();
        $code = $lesson->lessonType?->code ?? '';
        if (in_array($code, ['physical_education', 'swimming', 'foreign_language'], true)) {
            return true;
        }
        $name = $lesson->discipline?->name ?? '';

        return (bool) preg_match('/физ|спорт|бассейн|иностран|английск|немецк|француз/ui', $name);
    }

    private function saveConflicts(int $versionId, array $conflicts, ?string $dateFrom = null, ?string $dateTo = null): void
    {
        $query = ScheduleConflict::where('version_id', $versionId);
        // Удаляем конфликты ТОЛЬКО за проверяемый период, не трогая остальные недели
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

    private function detail($lesson): string
    {
        $date = $lesson->date instanceof Carbon
            ? $lesson->date->format('Y-m-d')
            : (string) $lesson->date;
        $ts = strtotime($date);
        $dayName = match ((int) date('N', $ts)) {
            1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт',
            5 => 'Пт', 6 => 'Сб', 7 => 'Вс', default => ''
        };
        $g = $lesson->group?->name ?? '?';
        $t = $lesson->teacher?->last_name ?? '?';
        $d = $lesson->discipline?->name ?? '?';
        $r = $lesson->room?->number ?? '?';
        $b = $lesson->room?->building?->short_name ?? '';
        $n = $lesson->lesson_number;
        $loc = $b ? "{$r} ({$b})" : $r;

        return "{$date} ({$dayName}), {$n}-я пара, гр.{$g}, «{$d}», {$t}, ауд.{$loc}";
    }
}
