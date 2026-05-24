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
                if (! $tId || $tLessons->count() <= 1) continue;
                
                $uniqueCombo = $tLessons->map(fn($l) => "{$l->discipline_id}-{$l->room_id}")->unique();
                if ($uniqueCombo->count() > 1) {
                    $teacher = $tLessons->first()->teacher;
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'teacher_parallel', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => (int) $num, 'teacher_id' => (int) $tId,
                        'description' => "Преподаватель {$teacher?->short_name} ведет разные пары одновременно ({$num}-я пара)",
                        'suggestion' => "Перенести одну из пар на другое время",
                        'is_resolved' => false,
                    ];
                }
            }

            // б) Группа на разных парах
            foreach ($lessons->groupBy('group_id') as $gId => $gLessons) {
                if (! $gId || $gLessons->count() <= 1) continue;
                
                $hasDifferentSubgroups = $gLessons->pluck('subgroup_id')->filter()->unique()->count() > 1;
                $hasNullSubgroup = $gLessons->contains(fn($l) => is_null($l->subgroup_id));

                if (! ($hasDifferentSubgroups && ! $hasNullSubgroup)) {
                    $group = $gLessons->first()->group;
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'group_parallel', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => (int) $num, 'group_id' => (int) $gId,
                        'description' => "Группа {$group->name} стоит на нескольких парах одновременно ({$num}-я пара)",
                        'suggestion' => "Проверьте подгруппы или перенесите одну из пар",
                        'is_resolved' => false,
                    ];
                }
            }

            // в) Аудитория занята разными преподами
            foreach ($lessons->groupBy('room_id') as $rId => $rLessons) {
                if (! $rId || $rLessons->count() <= 1) continue;

                $uniqueTeachers = $rLessons->pluck('teacher_id')->unique();
                if ($uniqueTeachers->count() > 1) {
                    $room = $rLessons->first()->room;
                    $conflicts[] = [
                        'version_id' => $versionId, 'conflict_type' => 'room_multi_group', 'severity' => 'error',
                        'date' => $date, 'lesson_number' => (int) $num, 'room_id' => (int) $rId,
                        'description' => "Аудитория {$room?->number} занята разными преподавателями ({$num}-я пара)",
                        'suggestion' => "Перенести одну из пар в другую аудиторию",
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
        if (! $teacher) return;

        // 1. Доступность (график, отгулы, метод.день)
        foreach ($lessons as $lesson) {
            if (! $teacher->isAvailableOn($date, $lesson->lesson_number)) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_unavailability', 'severity' => 'error',
                    'date' => $date, 'lesson_number' => $lesson->lesson_number, 'teacher_id' => $teacher->id,
                    'description' => "Преподаватель {$teacher->short_name} недоступен на {$lesson->lesson_number}-й паре (согласно графику или заявкам)",
                    'suggestion' => "Перенести пару или изменить настройки графика преподавателя",
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
        if (! $group) return;

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
        if (! $room) return;

        foreach ($lessons as $lesson) {
            $studentsCount = $lesson->group?->students_count ?? 0;
            if ($room->capacity > 0 && $studentsCount > $room->capacity) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'room_capacity', 'severity' => 'warning',
                    'date' => $date, 'lesson_number' => $lesson->lesson_number, 'room_id' => $room->id,
                    'description' => "Аудитория {$room->number} (вмест. {$room->capacity}) мала для группы {$lesson->group?->name} ({$studentsCount} чел.)",
                    'suggestion' => "Подобрать более просторную аудиторию",
                    'is_resolved' => false,
                ];
            }
        }
    }

    // ── Детальные методы проверок ──────────────────────────────────────────

    private function checkTeacherDisciplineMatch(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        $teacherDisciplineIds = TeacherDiscipline::where('teacher_id', $teacher->id)->pluck('discipline_id')->unique()->toArray();
        foreach ($lessons as $lesson) {
            if ($lesson->discipline_id && ! in_array($lesson->discipline_id, $teacherDisciplineIds, true)) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_discipline_mismatch', 'severity' => 'error',
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
        $numbers = $lessons->pluck('lesson_number')->unique()->sort()->values()->toArray();
        if (count($numbers) < 2) return;

        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] > 1) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_window', 'severity' => 'warning',
                    'date' => $date, 'lesson_number' => $numbers[$i], 'teacher_id' => $teacher->id,
                    'description' => "Окно у преподавателя {$teacher->short_name}: между {$numbers[$i - 1]}-й и {$numbers[$i]}-й парой",
                    'suggestion' => "Уплотнить расписание преподавателя",
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkTeacherMinPairs(Collection $lessons, string $date, Teacher $teacher, int $versionId, array &$conflicts): void
    {
        // В субботу не проверяем минимум пар
        if (date('N', strtotime($date)) == 6) return;

        // Если есть практика — не проверяем минимум
        if ($lessons->contains(fn($l) => $this->isPracticeLesson($l))) return;

        $count = $lessons->pluck('lesson_number')->unique()->count();
        if ($count === 1) {
            $conflicts[] = [
                'version_id' => $versionId, 'conflict_type' => 'teacher_min_lessons', 'severity' => 'warning',
                'date' => $date, 'lesson_number' => $lessons->first()->lesson_number, 'teacher_id' => $teacher->id,
                'description' => "Мало пар у преподавателя {$teacher->short_name}: всего 1 пара в день",
                'suggestion' => "Добавить еще пары или перенести на другой день",
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
                    'suggestion' => "Перенести в разрешенный слот или изменить настройки графиков пар/курсов",
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
                        'suggestion' => "Удалите теорию или проверьте даты практики в учебном плане",
                        'is_resolved' => false,
                    ];
                }
            }
        }
    }

    private function checkGroupWindows(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $numbers = $lessons->pluck('lesson_number')->unique()->sort()->values()->toArray();
        if (count($numbers) < 2) return;

        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $numbers[$i - 1] > 1) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_window', 'severity' => 'error',
                    'date' => $date, 'lesson_number' => $numbers[$i], 'group_id' => $group->id,
                    'description' => "Окно у группы {$group->name}: между {$numbers[$i - 1]}-й и {$numbers[$i]}-й парой",
                    'suggestion' => "Перенести одну из пар для устранения окна",
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkGroupMinPairs(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        // Если суббота — не проверяем минимум
        if (date('N', strtotime($date)) == 6) return;

        // Если в этот день есть ПРАКТИКА (либо в расписании, либо по календарю) — не проверяем минимум
        if ($lessons->contains(fn($l) => $this->isPracticeLesson($l)) || $group->isOnPractice(Carbon::parse($date))) {
            return;
        }

        $count = $lessons->pluck('lesson_number')->unique()->count();
        if ($count > 0 && $count < 3) {
            $conflicts[] = [
                'version_id' => $versionId, 'conflict_type' => 'group_min_lessons', 'severity' => 'warning',
                'date' => $date, 'group_id' => $group->id,
                'description' => "Мало пар у группы {$group->name}: всего {$count} в день (минимум 3)",
                'suggestion' => "Добавить еще пары",
                'is_resolved' => false,
            ];
        }
    }

    private function checkPhysicalEducation(Collection $lessons, string $date, Group $group, int $versionId, array &$conflicts): void
    {
        $peLessonTypeIds = LessonType::whereIn('code', ['physical_education', 'swimming'])->pluck('id');
        $isPe = fn ($l) => ($peLessonTypeIds->isNotEmpty() && in_array($l->lesson_type_id ?? 0, $peLessonTypeIds->toArray(), true))
            || preg_match('/физ|спорт|бассейн/ui', $l->discipline?->name ?? '');
        $peNumbers = $lessons->filter($isPe)->pluck('lesson_number')->sort()->values();
        $nonPeNumbers = $lessons->reject($isPe)->pluck('lesson_number')->toArray();
        if ($peNumbers->count() < 2) return;

        $min = $peNumbers->min();
        $max = $peNumbers->max();
        foreach ($nonPeNumbers as $nonPe) {
            if ($nonPe > $min && $nonPe < $max) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'pe_grouping', 'severity' => 'warning',
                    'date' => $date, 'group_id' => $group->id,
                    'description' => "Физкультура не сдвоена у группы {$group->name}: разбита другими занятиями",
                    'suggestion' => "Сдвоить физкультуру в начале или конце дня",
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
            $buildings = $groupLessons->filter(fn ($l) => ! $this->isSportRoom($l))->pluck('building_id')->filter()->unique();
            if ($buildings->count() > 1) {
                $group = $groupLessons->first()->group;
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_building_conflict', 'severity' => 'error',
                    'date' => $date, 'group_id' => (int) $groupId,
                    'description' => "Группа {$group?->name} в течение дня находится в нескольких корпусах",
                    'suggestion' => "Перенести все пары группы в один корпус",
                    'is_resolved' => false,
                ];
            }
        }
        // Преподаватели
        foreach ($dayLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
            if (! $teacherId) continue;
            $buildings = $teacherLessons->filter(fn ($l) => ! $this->isSportRoom($l))->pluck('building_id')->filter()->unique();
            if ($buildings->count() > 1) {
                $teacher = $teacherLessons->first()->teacher;
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_building_conflict', 'severity' => 'error',
                    'date' => $date, 'teacher_id' => (int) $teacherId,
                    'description' => "Преподаватель {$teacher?->short_name} в течение дня ведет пары в разных корпусах",
                    'suggestion' => "Перенести все пары преподавателя в один корпус",
                    'is_resolved' => false,
                ];
            }
        }
    }

    private function checkWeeklyHoursLimit(Collection $weekLessons, string $weekStartDate, int $versionId, array &$conflicts): void
    {
        $maxLessonsGroup = 18; // 36 ч
        foreach ($weekLessons->groupBy('group_id') as $groupId => $groupLessons) {
            $count = $groupLessons->count();
            if ($count > $maxLessonsGroup) {
                $group = $groupLessons->first()->group;
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'group_overload', 'severity' => 'warning',
                    'date' => $weekStartDate, 'group_id' => (int) $groupId,
                    'description' => "Группа {$group?->name}: превышена недельная нагрузка — ".($count * 2).' ч (макс 36)',
                    'suggestion' => "Убрать лишние пары",
                    'is_resolved' => false,
                ];
            }
        }
        foreach ($weekLessons->groupBy('teacher_id') as $teacherId => $teacherLessons) {
            if (! $teacherId) continue;
            $teacher = $teacherLessons->first()->teacher;
            $maxWeekHours = $teacher?->max_hours_per_week ?? 36;
            $maxLessons = (int) ($maxWeekHours / 2);
            $count = $teacherLessons->count();
            if ($count > $maxLessons) {
                $conflicts[] = [
                    'version_id' => $versionId, 'conflict_type' => 'teacher_overload', 'severity' => 'warning',
                    'date' => $weekStartDate, 'teacher_id' => (int) $teacherId,
                    'description' => "Преподаватель {$teacher?->short_name}: превышена нагрузка — ".($count * 2)." ч/нед (макс {$maxWeekHours})",
                    'suggestion' => "Убрать лишние пары",
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
        if ($code === 'practice') return true;
        
        $name = $lesson->discipline?->name ?? '';
        return (bool) preg_match('/практика/ui', $name);
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
        return [
            'fixed' => 0,
            'skipped' => 0,
            'message' => 'Автоисправление временно отключено после рефакторинга алгоритма',
            'details' => [],
        ];
    }

    // Публичные методы для генератора
    public function checkTeacherConflict(int $teacherId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        $teacher = Teacher::find($teacherId);
        if ($teacher && ! $teacher->isAvailableOn($date, $lessonNumber)) return true;

        return ScheduleLesson::where('teacher_id', $teacherId)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->when($excludeLessonId, fn($q) => $q->where('id', '!=', $excludeLessonId))
            ->exists();
    }

    public function checkGroupConflict(int $groupId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        $group = Group::find($groupId);
        // Тут проверяем ТОЛЬКО смену/график, не практику
        if ($group && ! $group->isAvailableOn($date, $lessonNumber)) return true;

        return ScheduleLesson::where('group_id', $groupId)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->when($excludeLessonId, fn($q) => $q->where('id', '!=', $excludeLessonId))
            ->exists();
    }

    public function checkRoomConflict(int $roomId, string $date, int $lessonNumber, ?int $excludeLessonId = null): bool
    {
        return ScheduleLesson::where('room_id', $roomId)
            ->where('date', $date)
            ->where('lesson_number', $lessonNumber)
            ->where('status', '!=', 'cancelled')
            ->when($excludeLessonId, fn($q) => $q->where('id', '!=', $excludeLessonId))
            ->exists();
    }
}
