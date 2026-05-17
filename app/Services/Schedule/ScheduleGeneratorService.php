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
use App\Models\Vacation;
use Carbon\Carbon;

class ScheduleGeneratorService
{
    private array $groupDayBuildings = [];

    private array $teacherDayBuildings = [];

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

        foreach ($groups as $group) {
            $workingDays = $group->getWorkingDays();
            $dailyQuotas = $this->distributeQuota(18, count($workingDays));

            $current = $weekStart->copy();
            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                // Если выходной или группа на практике/сессии - полностью пропускаем день
                if (! in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current) || $this->groupOnPracticeOrExam($group, $current)) {
                    $current->addDay();

                    continue;
                }

                $pairsCountToGenerate = $dailyQuotas[$dayIndex] ?? 3;
                $perDaySlots = $group->getAllowedLessonNumbersForDay($dayOfWeek);
                if (empty($perDaySlots)) {
                    $perDaySlots = $group->shift === 1 ? [1, 2, 3, 4, 5] : [3, 4, 5, 6, 7];
                }

                // Множественные окна в день: заполняем все возможные непрерывные окна
                $allDayLessons = [];
                $usedDayDisciplines = [];
                $dayUsedTeachers = [];
                $remainingSlots = $perDaySlots;
                $remainingTarget = $pairsCountToGenerate;

                while ($remainingTarget > 0 && ! empty($remainingSlots)) {
                    $isFirstWindow = empty($allDayLessons);
                    $windowData = $this->findBestStrictWindow(
                        $version, $group, $current, array_values($remainingSlots),
                        $remainingTarget, $usedDayDisciplines, $dayUsedTeachers,
                        anchorStart: ! $isFirstWindow
                    );

                    if (empty($windowData['lessons'])) {
                        break;
                    }

                    $allDayLessons = array_merge($allDayLessons, $windowData['lessons']);
                    $remainingSlots = array_values(array_diff($remainingSlots, $windowData['slots']));
                    $remainingTarget -= count($windowData['lessons']);
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

        return GenerationResult::success(
            totalLessons: $totalLessons,
            conflicts: $conflicts,
            conflictDetails: [],
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

    /**
     * Strict Sliding Window: Ищет только НЕПРЕРЫВНЫЕ окна без дыр.
     * Заполняет одно окно и возвращает его. Может быть вызван несколько раз
     * для одного дня, чтобы заполнить несколько окон.
     */
    private function findBestStrictWindow(ScheduleVersion $version, Group $group, Carbon $date, array $allowedSlots, int $targetPairs, array &$usedDisciplines = [], array &$usedTeachers = [], bool $anchorStart = false): array
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

                foreach ($windowSlots as $index => $slot) {
                    if ($skipNext) {
                        $skipNext = false;

                        continue;
                    }

                    if ($this->shouldGeneratePE($group) && isset($windowSlots[$index + 1])) {
                        $peData = $this->simulatePELessons($version, $group, $date, $slot, $group->shift, $usedTeachers);
                        if ($peData) {
                            $lessonsData[] = $peData[0];
                            $lessonsData[] = $peData[1];
                            $skipNext = true;

                            continue;
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
                            (int) $date->format('N')
                        );

                        if (! $candidate) {
                            break;
                        }

                        $candidateTeacher = $this->pickTeacherForDiscipline($candidate->id, $group->id, $date, $slot, $localBuilding, $usedTeachers);
                        if (! $candidateTeacher) {
                            $candidateTeacher = $this->pickTeacherForDisciplineFallback($candidate->id, $group->id, $date, $slot, $usedTeachers);
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

                    $lessonsData[] = [
                        'lesson_number' => $slot,
                        'shift' => $group->shift,
                        'discipline_id' => $discipline->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $room->id,
                        'building_id' => $localBuilding->id,
                        'lesson_type_id' => LessonType::where('code', 'lecture')->first()?->id ?? 1,
                    ];

                    $usedDisciplines[] = $discipline->id;
                    $usedTeachers[$teacher->id] = ($usedTeachers[$teacher->id] ?? 0) + 1;
                }

                if ($windowSuccess && count($lessonsData) > 0) {
                    return ['slots' => $windowSlots, 'lessons' => $lessonsData, 'building' => $localBuilding];
                }
            }
        }

        return ['slots' => [], 'lessons' => [], 'building' => $building];
    }

    private function groupOnPracticeOrExam(Group $group, Carbon $date): bool
    {
        $assignment = $group->curriculumAssignments()->where('is_active', true)->first();
        if (! $assignment) {
            return false;
        }

        return CurriculumPractice::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('course_number', $group->current_course)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->exists();
    }

    private function simulatePELessons(ScheduleVersion $version, Group $group, Carbon $date, int $startLessonNumber, int $shift, array &$usedTeachers): ?array
    {
        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->where('name', 'like', '%Физическая культура%')
            ->where('is_schedulable', true)
            ->first();
        if (! $peDiscipline) {
            return null;
        }

        // Поиск именно спортзала
        $sportRoom = Room::whereHas('roomType', fn ($q) => $q->where('name', 'like', '%Спорт%'))->first();
        if (! $sportRoom) {
            return null;
        }

        $teacher = $this->pickTeacherForDiscipline($peDiscipline->id, $group->id, $date, $startLessonNumber, $sportRoom->building, $usedTeachers);
        if (! $teacher) {
            return null;
        }

        if (! $teacher->isAvailableOn($date, $startLessonNumber + 1)) {
            return null;
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

        return null;
    }

    private function shouldGeneratePE(Group $group): bool
    {
        return rand(1, 5) === 1; // 20% шанс
    }

    private function pickDisciplineForGroup(Group $group, array $excludeIds = [], ?int $dayOfWeek = null): ?CurriculumDiscipline
    {
        $assignment = GroupCurriculumAssignment::where('group_id', $group->id)->where('is_active', true)->first();
        if (! $assignment) {
            return null;
        }

        $query = CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
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

        if (! $discipline && ! empty($excludeIds)) {
            return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
                ->where('name', 'not like', '%Физическая культура%')
                ->where('is_schedulable', true)
                ->inRandomOrder()
                ->first();
        }

        return $discipline;
    }

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding, array $simulatedTeacherLoads = []): ?Teacher
    {
        $candidates = Teacher::whereHas('disciplines', fn ($q) => $q->where('discipline_id', $disciplineId)->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id')))->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        $dateStr = $date->format('Y-m-d');
        $scored = [];

        foreach ($candidates as $teacher) {
            if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                continue;
            }
            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                continue;
            }

            $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$dateStr] ?? null;
            if ($teacherDayBuildingId && $teacherDayBuildingId !== $targetBuilding->id) {
                continue;
            }

            $dbLoad = ScheduleLesson::where('teacher_id', $teacher->id)->where('date', $dateStr)->count();
            $simLoad = $simulatedTeacherLoads[$teacher->id] ?? 0;
            if (($dbLoad + $simLoad) >= ($teacher->max_lessons_per_day ?? 5)) {
                continue;
            }

            $scored[] = ['teacher' => $teacher, 'score' => 100 - ($dbLoad + $simLoad) * 10]; // Чем меньше пар, тем выше скор
        }

        if (empty($scored)) {
            return null;
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scored[0]['teacher'];
    }

    private function pickTeacherForDisciplineFallback(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, array $simulatedTeacherLoads = []): ?Teacher
    {
        $candidates = Teacher::whereHas('disciplines', fn ($q) => $q->where('discipline_id', $disciplineId)->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id')))->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        $dateStr = $date->format('Y-m-d');
        foreach ($candidates as $teacher) {
            if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                continue;
            }
            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                continue;
            }

            $dbLoad = ScheduleLesson::where('teacher_id', $teacher->id)->where('date', $dateStr)->count();
            $simLoad = $simulatedTeacherLoads[$teacher->id] ?? 0;
            if (($dbLoad + $simLoad) >= ($teacher->max_lessons_per_day ?? 5)) {
                continue;
            }

            return $teacher;
        }

        return null;
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
