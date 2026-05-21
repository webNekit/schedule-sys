<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\DTOs\GenerationResult;
use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPractice;
use App\Models\Group;
use App\Models\GroupBuilding;
use App\Models\GroupCurriculumAssignment;
use App\Models\Holiday;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherDisciplineSemester;
use App\Models\Vacation;
use Carbon\Carbon;

class ScheduleGeneratorService
{
    private array $groupDayBuildings = [];

    private array $teacherDayBuildings = [];

    private array $teacherDayLessons = [];

    public function __construct(
        private readonly ConflictCheckerService $conflictChecker,
        private readonly HoursTrackingService $hoursTracking,
    ) {}

    public function generateForWeek(Carbon $weekStart, array $groupIds = []): GenerationResult
    {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $version = $this->createVersion('week', $weekStart, $weekEnd, $groupIds);
        $groups = $this->getGroups($groupIds);

        $totalLessons = 0;
        $conflicts = 0;

        $this->teacherDayLessons = [];
        $this->teacherDayBuildings = [];

        foreach ($groups as $group) {
            $workingDays = $group->getWorkingDays();
            $dailyQuotas = $this->distributeQuota($group->getWeeklyPairs(), count($workingDays));

            $current = $weekStart->copy();
            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                // 1. Сначала проверяем, является ли день рабочим для группы (учитываем праздники и график 5/6 дней)
                if (! in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current)) {
                    $current->addDay();
                    continue;
                }

                // 2. Только если день рабочий - проверяем на практику или экзамен
                if ($this->groupOnPracticeOrExam($group, $current, $version->id)) {
                    $block = $group->getCalendarBlock($current);
                    
                    // Ищем тип занятия
                    $typeCode = $block?->type === 'exam_session' ? 'exam' : ($block?->type ?? 'prod_practice');
                    $lessonType = LessonType::where('code', $typeCode)->first() 
                                 ?? LessonType::where('code', 'practice')->first()
                                 ?? LessonType::first();

                    // Ищем дисциплину для отображения
                    $discId = null;
                    if ($block?->type === 'exam_session') {
                         $discId = CurriculumDiscipline::where('curriculum_plan_id', $group->curriculumAssignments()->where('is_active', true)->first()?->curriculum_plan_id)
                            ->where('name', 'like', '%сессия%')
                            ->first()?->id;
                    }
                    
                    if (!$discId) {
                        $discId = $this->getPracticeDisciplineId($group);
                    }

                    if ($discId) {
                        ScheduleLesson::create([
                            'version_id' => $version->id,
                            'date' => $current->toDateString(),
                            'group_id' => $group->id,
                            'lesson_number' => 1,
                            'discipline_id' => $discId,
                            'room_id' => null,
                            'teacher_id' => null,
                            'shift' => $group->shift,
                            'lesson_type_id' => $lessonType->id,
                            'building_id' => null,
                            'is_auto_generated' => true,
                            'status' => 'draft',
                        ]);
                    }

                    $current->addDay();
                    $dayIndex++;
                    continue;
                }

                $pairsCountToGenerate = $dailyQuotas[$dayIndex] ?? 3;
                $perDaySlots = $group->getAllowedLessonNumbersForDay($dayOfWeek);
                if (empty($perDaySlots)) {
                    $perDaySlots = $group->shift === 1 ? [1, 2, 3, 4, 5] : [3, 4, 5, 6, 7];
                }

                // Проверка на экзамен: если в этот день у группы уже стоит экзамен (в этой версии или в базе)
                if ($this->hasExamOnDay($group, $current, $version->id)) {
                    $current->addDay();
                    $dayIndex++;

                    continue;
                }

                // Множественные окна в день: заполняем все возможные непрерывные окна
                $allDayLessons = [];
                $usedDayDisciplines = [];
                $dayUsedTeachers = [];
                $teacherPlannedSlots = []; // Track slots for each teacher locally for this group
                $remainingSlots = $perDaySlots;
                $remainingTarget = $pairsCountToGenerate;

                while ($remainingTarget > 0 && ! empty($remainingSlots)) {
                    $isFirstWindow = empty($allDayLessons);
                    $windowData = $this->findBestStrictWindow(
                        $version, $group, $current, array_values($remainingSlots),
                        $remainingTarget, $usedDayDisciplines, $dayUsedTeachers,
                        $teacherPlannedSlots,
                        anchorStart: ! $isFirstWindow
                    );

                    if (empty($windowData['lessons'])) {
                        break;
                    }

                    $allDayLessons = array_merge($allDayLessons, $windowData['lessons']);
                    foreach ($windowData['lessons'] as $ld) {
                        $teacherPlannedSlots[$ld['teacher_id']][] = $ld['lesson_number'];
                    }
                    $remainingSlots = array_values(array_diff($remainingSlots, $windowData['slots']));
                    $remainingTarget -= count($windowData['lessons']);

                    if ($windowData['is_exam'] ?? false) {
                        break;
                    }
                }

                if (! empty($allDayLessons)) {
                    $dayBuildingId = $allDayLessons[0]['building_id'];
                    $this->groupDayBuildings[$group->id][$current->format('Y-m-d')] = $dayBuildingId;

                    foreach ($allDayLessons as $lessonData) {
                        ScheduleLesson::create(array_merge($lessonData, [
                            'version_id' => $version->id,
                            'date' => $current->toDateString(),
                            'group_id' => $group->id,
                            'is_auto_generated' => true,
                            'status' => 'draft',
                        ]));
                        $totalLessons++;
                        $this->teacherDayBuildings[$lessonData['teacher_id']][$current->format('Y-m-d')] = $dayBuildingId;
                        $this->teacherDayLessons[$lessonData['teacher_id']][$current->format('Y-m-d')][] = $lessonData['lesson_number'];
                    }

                    $dayConflict = $pairsCountToGenerate - count($allDayLessons);
                    if ($dayConflict > 0) {
                        $conflicts += $dayConflict;
                    }
                } else {
                    $conflicts += $pairsCountToGenerate;
                }

                $dayIndex++;
                $current->addDay();
            }
        }

        $version->update(['status' => 'draft', 'generated_at' => now()]);

        // Run real conflict check to get accurate numbers
        $actualConflicts = $this->conflictChecker->checkVersion($version->id);
        $conflictCount = count($actualConflicts);

        return GenerationResult::success(
            totalLessons: $totalLessons,
            conflicts: $conflictCount,
            conflictDetails: $actualConflicts,
            version: $version,
        );
    }

    public function generateForDay(Carbon $date, array $groupIds = []): GenerationResult
    {
        return $this->generateForWeek($date->copy()->startOfWeek(), $groupIds);
    }

    public function generateForMonth(int $year, int $month, array $groupIds = []): GenerationResult
    {
        return $this->generateForWeek(Carbon::create($year, $month, 1), $groupIds);
    }

    private function distributeQuota(int $totalPairs, int $daysCount): array
    {
        if ($daysCount <= 0) {
            return [];
        }
        $quotas = array_fill(0, $daysCount, 3); // База 3 пары в день
        $remaining = $totalPairs - ($daysCount * 3);

        $i = 0;
        while ($remaining > 0 && $i < $daysCount) {
            if ($quotas[$i] < 5) {
                $quotas[$i]++;
                $remaining--;
            }
            $i = ($i + 1) % $daysCount;
        }
        rsort($quotas);

        return $quotas;
    }

    private function hasExamOnDay(Group $group, Carbon $date, int $versionId): bool
    {
        $dateStr = $date->toDateString();

        // 1. Проверяем в текущей версии (если уже добавили вручную)
        $inVersion = ScheduleLesson::where('group_id', $group->id)
            ->where('date', $dateStr)
            ->where('version_id', $versionId)
            ->where(function($q) {
                $q->whereHas('lessonType', fn ($sub) => $sub->whereIn('code', ['exam', 'test', 'diff_test']))
                  ->orWhereHas('discipline', fn ($sub) => $sub->where('name', 'like', '%экзамен%'));
            })
            ->exists();

        if ($inVersion) {
            return true;
        }

        // 2. Проверяем опубликованные версии на эту дату
        return ScheduleLesson::where('group_id', $group->id)
            ->where('date', $dateStr)
            ->whereHas('version', fn ($q) => $q->where('status', 'published'))
            ->where(function($q) {
                $q->whereHas('lessonType', fn ($sub) => $sub->whereIn('code', ['exam', 'test', 'diff_test']))
                  ->orWhereHas('discipline', fn ($sub) => $sub->where('name', 'like', '%экзамен%'));
            })
            ->exists();
    }

    /**
     * Strict Sliding Window: Ищет только НЕПРЕРЫВНЫЕ окна без дыр.
     * Заполняет одно окно и возвращает его. Может быть вызван несколько раз
     * для одного дня, чтобы заполнить несколько окон.
     */
    private function findBestStrictWindow(ScheduleVersion $version, Group $group, Carbon $date, array $allowedSlots, int $targetPairs, array &$usedDisciplines = [], array &$usedTeachers = [], array $existingTeacherPlannedSlots = [], bool $anchorStart = false): array
    {
        $building = $this->pickBuildingForGroupDay($group, $date) ?? Building::where('is_active', true)->first();
        if (! $building) {
            return ['slots' => [], 'lessons' => [], 'building' => $building];
        }
        $n = count($allowedSlots);

        for ($currentLength = min($targetPairs, $n); $currentLength >= 1; $currentLength--) {
            $startIdx = 0;
            $endIdx = $anchorStart ? 0 : ($n - $currentLength);
            for ($i = $startIdx; $i <= $endIdx; $i++) {
                $windowSlots = array_slice($allowedSlots, $i, $currentLength);

                $isContinuous = true;
                for ($j = 1; $j < count($windowSlots); $j++) {
                    if ($windowSlots[$j] !== $windowSlots[$j - 1] + 1) {
                        $isContinuous = false;
                        break;
                    }
                }
                if (! $isContinuous) {
                    continue;
                }

                $lessonsData = [];
                $windowSuccess = true;
                $skipNext = false;
                $localBuilding = $building;
                $currentWindowTeacherSlots = $existingTeacherPlannedSlots;
                $typeId = LessonType::where('code', 'lecture')->first()?->id ?? 1;

                foreach ($windowSlots as $index => $slot) {
                    if ($skipNext) {
                        $skipNext = false;

                        continue;
                    }

                    if ($this->shouldGeneratePE($group) && isset($windowSlots[$index + 1])) {
                        $peData = $this->simulatePELessons($version, $group, $date, $slot, $group->shift, $usedTeachers, $currentWindowTeacherSlots);
                        if ($peData) {
                            $isSportComplex = $this->isSportComplexRoom($peData[0]['room_id']);
                            $isValidPlacement = true;

                            if ($isSportComplex) {
                                // Спорт.комплекс: строго 1-2 или 3-4 пары
                                if (! in_array($slot, [1, 3], true)) {
                                    $isValidPlacement = false;
                                }
                            } else {
                                // Обычный спортзал: не позже 4 пары (т.е. начало не позже 3 или 4, уточним: не позже 4 пары всего)
                                if ($slot > 3) {
                                    $isValidPlacement = false;
                                }
                            }

                            if ($isValidPlacement) {
                                $lessonsData[] = $peData[0];
                                $lessonsData[] = $peData[1];
                                $currentWindowTeacherSlots[$peData[0]['teacher_id']][] = $peData[0]['lesson_number'];
                                $currentWindowTeacherSlots[$peData[1]['teacher_id']][] = $peData[1]['lesson_number'];
                                $skipNext = true;

                                continue;
                            }
                        }
                    }

                    // Пробуем до 10 разных дисциплин для этого слота
                    $discipline = null;
                    $teacher = null;
                    $triedDisciplineIds = [];

                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        $candidate = $this->pickDisciplineForGroup(
                            $group,
                            array_merge($usedDisciplines, $triedDisciplineIds),
                            $date
                        );

                        if (! $candidate) {
                            break;
                        }

                        $candidateTeacher = $this->pickTeacherForDiscipline($candidate->id, $group->id, $date, $slot, $localBuilding, $usedTeachers, $currentWindowTeacherSlots);
                        if (! $candidateTeacher) {
                            $candidateTeacher = $this->pickTeacherForDisciplineFallback($candidate->id, $group->id, $date, $slot, $usedTeachers, $currentWindowTeacherSlots);
                        }

                        if (! $candidateTeacher) {
                            $triedDisciplineIds[] = $candidate->id;

                            continue;
                        }

                        $candidateRoom = $this->pickRoomForLesson($localBuilding->id, $group->students_count, $candidate, $date, $slot, $candidateTeacher);
                        if (! $candidateRoom) {
                            $candidateRoom = $this->pickRoomForLessonFallback($group->students_count, $candidate, $date, $slot, $candidateTeacher);
                        }

                        if (! $candidateRoom) {
                            $triedDisciplineIds[] = $candidate->id;

                            continue;
                        }

                        $discipline = $candidate;
                        $teacher = $candidateTeacher;
                        $room = $candidateRoom;
                        $localBuilding = Building::find($room->building_id);
                        break;
                    }

                    if (! $discipline || ! $teacher) {
                        $windowSuccess = false;
                        break;
                    }

                    $isExamDiscipline = $this->isExam($discipline, $typeId);

                    $lessonsData[] = [
                        'lesson_number' => $slot,
                        'shift' => $group->shift,
                        'discipline_id' => $discipline->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $room->id,
                        'building_id' => $localBuilding->id,
                        'lesson_type_id' => $isExamDiscipline ? (LessonType::where('code', 'exam')->first()?->id ?? $typeId) : $typeId,
                        'notes' => $isExamDiscipline ? ($discipline->code ?: null) : null,
                    ];

                    $usedDisciplines[] = $discipline->id;
                    $usedTeachers[$teacher->id] = ($usedTeachers[$teacher->id] ?? 0) + 1;
                    $currentWindowTeacherSlots[$teacher->id][] = $slot;

                    // Если это экзамен, то это должна быть единственная пара в этот день для группы
                    if ($isExamDiscipline) {
                        // Очищаем другие уже добавленные в это окно пары (если были)
                        // Но лучше просто прервать цикл и вернуть только этот экзамен
                        return ['slots' => [$slot], 'lessons' => [end($lessonsData)], 'building' => $localBuilding, 'is_exam' => true];
                    }
                }

                if ($windowSuccess && count($lessonsData) > 0) {
                    return ['slots' => $windowSlots, 'lessons' => $lessonsData, 'building' => $localBuilding];
                }
            }
        }

        return ['slots' => [], 'lessons' => [], 'building' => $building];
    }

    private function groupOnPracticeOrExam(Group $group, Carbon $date, int $versionId): bool
    {
        // 1. Проверяем график практик (УП, ПП, ПДП, ГИА) через модель группы
        if ($group->isOnPractice($date)) {
            return true;
        }

        // 2. Проверяем наличие экзаменов в текущей или опубликованных версиях
        return $this->hasExamOnDay($group, $date, $versionId);
    }

    private function simulatePELessons(ScheduleVersion $version, Group $group, Carbon $date, int $startLessonNumber, int $shift, array &$usedTeachers, array $simulatedWindowSlots = []): ?array
    {
        $currentSemester = $group->getCurrentSemester($date);
        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('name', 'like', '%Физическая культура%')
            ->where('is_schedulable', true)
            ->first();
        if (! $peDiscipline) {
            return null;
        }

        // Поиск подходящих спортзалов
        $sportRooms = Room::whereHas('roomType', fn ($q) => $q->where('name', 'like', '%Спорт%'))
            ->where('is_active', true)
            ->get();

        foreach ($sportRooms as $sportRoom) {
            $teacher = $this->pickTeacherForDiscipline($peDiscipline->id, $group->id, $date, $startLessonNumber, $sportRoom->building, $usedTeachers, $simulatedWindowSlots);
            if (! $teacher) {
                continue;
            }

            if (! $teacher->isAvailableOn($date, $startLessonNumber + 1)) {
                continue;
            }

            if (
                ! $this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber)
                && ! $this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber + 1)
            ) {
                $typeId = LessonType::where('code', 'practice')->first()?->id ?? 2;
                $usedTeachers[$teacher->id] = ($usedTeachers[$teacher->id] ?? 0) + 2;

                return [
                    [
                        'lesson_number' => $startLessonNumber,
                        'shift' => $shift,
                        'discipline_id' => $peDiscipline->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $sportRoom->id,
                        'building_id' => $sportRoom->building_id,
                        'lesson_type_id' => $typeId,
                    ],
                    [
                        'lesson_number' => $startLessonNumber + 1,
                        'shift' => $shift,
                        'discipline_id' => $peDiscipline->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $sportRoom->id,
                        'building_id' => $sportRoom->building_id,
                        'lesson_type_id' => $typeId,
                    ],
                ];
            }
        }

        return null;
    }

    private function isExam(CurriculumDiscipline $discipline, int $typeId): bool
    {
        $lt = LessonType::find($typeId);
        if ($lt && in_array($lt->code, ['exam', 'test', 'diff_test'])) {
            return true;
        }

        return (bool) preg_match('/экзамен|зачет|аттестация/ui', $discipline->name);
    }

    private function shouldGeneratePE(Group $group): bool
    {
        return rand(1, 10) === 1; // 10% шанс
    }

    private function getPracticeDisciplineId(Group $group): ?int
    {
        return CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->where('name', 'like', '%практика%')
            ->first()?->id;
    }

    private function pickDisciplineForGroup(Group $group, array $excludeIds = [], ?Carbon $date = null): ?CurriculumDiscipline
    {
        $assignment = GroupCurriculumAssignment::where('group_id', $group->id)->where('is_active', true)->first();
        if (! $assignment) {
            return null;
        }

        $currentSemester = $group->getCurrentSemester($date);
        $dayOfWeek = $date ? (int) $date->format('N') : null;

        $query = CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('name', 'not like', '%Физическая культура%')
            ->where('is_schedulable', true);

        // Фильтр: у дисциплины должен быть преподаватель, работающий в этот день
        if ($dayOfWeek !== null) {
            $query->whereHas('teacherDisciplines.teacher', function ($q) use ($dayOfWeek) {
                $q->where(function ($sub) use ($dayOfWeek) {
                    $sub->whereJsonContains('working_days', $dayOfWeek)
                        ->orWhereNull('working_days')
                        ->orWhere('working_days', '[]');
                });
            });
        }

        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $discipline = $query->inRandomOrder()->first();

        // Если не нашли с учетом исключенных (например, все уже были в этот день),
        // то пробуем найти любую подходящую для этого семестра
        if (! $discipline && ! empty($excludeIds)) {
            return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
                ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
                ->where('name', 'not like', '%Физическая культура%')
                ->where('is_schedulable', true)
                ->inRandomOrder()
                ->first();
        }

        return $discipline;
    }

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding, array $simulatedTeacherLoads = [], array $simulatedWindowSlotsMap = []): ?Teacher
    {
        $currentSemesterNum = Group::find($groupId)?->getCurrentSemester($date);
        $dateStr = $date->format('Y-m-d');

        // Находим всех преподавателей, назначенных на этот семестр, отсортированных по sort_order
        $assignments = TeacherDisciplineSemester::whereHas('teacherDiscipline', function ($q) use ($disciplineId, $groupId) {
            $q->where('discipline_id', $disciplineId)
              ->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id'));
        })->whereHas('curriculumSemester', function ($q) use ($currentSemesterNum) {
            $q->where('semester_number', $currentSemesterNum);
        })->where('is_active', true)
          ->orderBy('sort_order', 'asc')
          ->get();

        foreach ($assignments as $assignment) {
            $teacher = $assignment->teacherDiscipline->teacher;

            // Проверяем, остались ли часы у этого преподавателя (с учетом sort_order)
            // Если у текущего преподавателя в очереди еще есть часы, мы ОБЯЗАНЫ выбрать его или никого.
            if ($this->hoursTracking->getTeacherRemainingHoursForDiscipline($teacher, $groupId, $disciplineId, $currentSemesterNum) > 0) {
                
                // Проверяем доступность выбранного по очереди преподавателя
                if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                    return null; // Ждем этого преподавателя, других не ставим
                }
                if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                    return null; // Конфликт, ждем
                }

                $teacherSimSlots = $simulatedWindowSlotsMap[$teacher->id] ?? [];
                if (! $this->canAssignToTeacherWithoutWindow($teacher->id, $dateStr, $lessonNumber, $teacherSimSlots)) {
                    return null;
                }

                $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$dateStr] ?? null;
                if ($teacherDayBuildingId && $teacherDayBuildingId !== $targetBuilding->id) {
                    return null;
                }

                $dbLoad = count($this->teacherDayLessons[$teacher->id][$dateStr] ?? []);
                $simLoad = $simulatedTeacherLoads[$teacher->id] ?? 0;
                $windowLoad = count($teacherSimSlots);
                if (($dbLoad + $simLoad + $windowLoad) >= ($teacher->max_lessons_per_day ?? 5)) {
                    return null;
                }

                return $teacher;
            }
            // Если у текущего преподавателя часы кончились, цикл перейдет к следующему в sort_order
        }

        return null;
    }

    private function pickTeacherForDisciplineFallback(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, array $simulatedTeacherLoads = [], array $simulatedWindowSlotsMap = []): ?Teacher
    {
        // В последовательной системе fallback должен работать так же - строго по очереди
        return $this->pickTeacherForDiscipline($disciplineId, $groupId, $date, $lessonNumber, Building::where('is_active', true)->first(), $simulatedTeacherLoads, $simulatedWindowSlotsMap);
    }

    private function isSportComplexRoom(int $roomId): bool
    {
        $room = Room::find($roomId);
        return $room && $room->roomType && $room->roomType->name === 'Спорт.комплекс';
    }

    private function canAssignToTeacherWithoutWindow(int $teacherId, string $date, int $lessonNumber, array $additionalSlots = []): bool
    {
        $existing = array_merge($this->teacherDayLessons[$teacherId][$date] ?? [], $additionalSlots);
        if (empty($existing)) {
            return true;
        }

        // Check if $lessonNumber is adjacent to any existing lesson
        foreach ($existing as $ex) {
            if (abs($ex - $lessonNumber) === 1) {
                return true;
            }
        }

        return false;
    }

    private function pickRoomForLesson(int $buildingId, int $studentsCount, CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, ?Teacher $teacher): ?Room
    {
        $query = Room::where('building_id', $buildingId)->where('is_active', true)->where('is_available_for_booking', true)->where('capacity', '>=', $studentsCount);
        if ($discipline->requires_lab) {
            $query->whereHas('roomType', fn ($q) => $q->where('requires_lab', true));
        }

        if ($teacher) {
            $personalRoomIds = $teacher->rooms()->pluck('room_id')->toArray();
            if (! empty($personalRoomIds)) {
                $caseWhen = 'CASE id';
                foreach ($personalRoomIds as $index => $roomId) {
                    $caseWhen .= " WHEN {$roomId} THEN ".(count($personalRoomIds) - $index);
                }
                $caseWhen .= ' ELSE 0 END DESC';
                $query->orderByRaw($caseWhen);
            }
        }

        foreach ($query->get() as $room) {
            if (! $this->conflictChecker->checkRoomConflict($room->id, $date->format('Y-m-d'), $lessonNumber)) {
                return $room;
            }
        }

        return null;
    }

    private function pickRoomForLessonFallback(int $studentsCount, CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, ?Teacher $teacher): ?Room
    {
        $query = Room::where('is_active', true)->where('is_available_for_booking', true)->where('capacity', '>=', $studentsCount);
        if ($discipline->requires_lab) {
            $query->whereHas('roomType', fn ($q) => $q->where('requires_lab', true));
        }

        foreach ($query->get() as $room) {
            if (! $this->conflictChecker->checkRoomConflict($room->id, $date->format('Y-m-d'), $lessonNumber)) {
                return $room;
            }
        }

        return null;
    }

    private function pickBuildingForGroupDay(Group $group, Carbon $date): Building
    {
        $gb = GroupBuilding::where('group_id', $group->id)->orderBy('is_primary', 'desc')->first();

        return $gb ? $gb->building : Building::where('is_active', true)->first();
    }

    private function isNonWorkingDay(Carbon $date): bool
    {
        if ($date->isSunday()) {
            return true;
        }
        if (Holiday::where('date', $date->toDateString())->exists()) {
            return true;
        }

        return Vacation::where('start_date', '<=', $date->toDateString())->where('end_date', '>=', $date->toDateString())->exists();
    }

    private function createVersion(string $periodType, Carbon $dateFrom, Carbon $dateTo, array $groupIds): ScheduleVersion
    {
        $academicYear = AcademicYear::where('is_current', true)->first();

        return ScheduleVersion::create([
            'name' => 'Автогенерация ('.$dateFrom->format('d.m').' - '.$dateTo->format('d.m').')',
            'academic_year_id' => $academicYear?->id,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'period_type' => $periodType,
            'status' => 'generating',
            'generation_type' => 'auto',
            'created_by' => auth()->id(),
        ]);
    }

    private function getGroups(array $groupIds): array
    {
        if (empty($groupIds)) {
            return Group::where('is_active', true)->where('status', 'active')->get()->all();
        }

        return Group::whereIn('id', $groupIds)->get()->all();
    }
}
