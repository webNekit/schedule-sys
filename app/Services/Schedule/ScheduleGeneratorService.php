<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\DTOs\GenerationResult;
use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\Group;
use App\Models\GroupBuilding;
use App\Models\Holiday;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\SystemSetting;
use App\Models\Teacher;
use App\Models\TeacherDisciplineSemester;
use App\Models\Vacation;
use Carbon\Carbon;

class ScheduleGeneratorService
{
    private array $groupDayBuildings = [];

    private array $teacherDayBuildings = [];

    private array $teacherDayLessons = [];

    /** Количество занятий по каждой дисциплине, размещённых в текущем сеансе генерации. */
    private array $sessionDisciplineHours = [];

    /** Сколько раз дисциплина поставлена группе в текущую неделю. */
    private array $weekDisciplineCount = [];

    public function __construct(
        private readonly ConflictCheckerService $conflictChecker,
        private readonly HoursTrackingService $hoursTracking,
    ) {}

    // ── Публичный API ──────────────────────────────────────────────────────

    public function generateForWeek(Carbon $weekStart, array $groupIds = []): GenerationResult
    {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $this->resetSession();

        $version = $this->createVersion('week', $weekStart, $weekEnd, $groupIds);
        $groups = $this->getGroups($groupIds);

        [$totalLessons, $conflicts] = $this->generateWeeksIntoVersion($version, $weekStart, $weekEnd, $groups);

        return $this->finalizeVersion($version, $totalLessons, $conflicts);
    }

    public function generateForDay(Carbon $date, array $groupIds = []): GenerationResult
    {
        return $this->generateForWeek($date->copy()->startOfWeek(), $groupIds);
    }

    public function generateForMonth(int $year, int $month, array $groupIds = []): GenerationResult
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $this->resetSession();

        $version = $this->createVersion('month', $monthStart, $monthEnd, $groupIds);
        $groups = $this->getGroups($groupIds);

        $totalLessons = 0;
        $conflictsRaw = 0;

        $weekCursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        while ($weekCursor->lessThanOrEqualTo($monthEnd)) {
            $weekEnd = $weekCursor->copy()->endOfWeek(Carbon::SUNDAY);
            [$wLessons, $wConflicts] = $this->generateWeeksIntoVersion($version, $weekCursor->copy(), $weekEnd, $groups);
            $totalLessons += $wLessons;
            $conflictsRaw += $wConflicts;
            $weekCursor->addWeek();
        }

        return $this->finalizeVersion($version, $totalLessons, $conflictsRaw);
    }

    public function generateForSemester(int $semester, array $groupIds = []): GenerationResult
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (! $academicYear) {
            return GenerationResult::failure('Не найден текущий учебный год');
        }

        if ($semester === 1) {
            $start = $academicYear->first_semester_start ?? $academicYear->date_start;
            $end = $academicYear->first_semester_end ?? $academicYear->date_start?->copy()->addMonths(5);
        } else {
            $start = $academicYear->second_semester_start ?? $academicYear->date_start?->copy()->addMonths(6);
            $end = $academicYear->second_semester_end ?? $academicYear->date_end;
        }

        if (! $start || ! $end) {
            return GenerationResult::failure('Не заданы даты семестра в учебном году');
        }

        $periodStart = Carbon::parse($start)->startOfDay();
        $periodEnd = Carbon::parse($end)->endOfDay();

        $this->resetSession();

        $version = $this->createVersion(
            'semester',
            $periodStart,
            $periodEnd,
            $groupIds,
            "Семестр {$semester} ({$periodStart->format('d.m.Y')} — {$periodEnd->format('d.m.Y')})"
        );
        $groups = $this->getGroups($groupIds);

        $totalLessons = 0;
        $conflictsRaw = 0;

        $weekCursor = $periodStart->copy()->startOfWeek(Carbon::MONDAY);
        while ($weekCursor->lessThanOrEqualTo($periodEnd)) {
            $weekEnd = $weekCursor->copy()->endOfWeek(Carbon::SUNDAY);
            [$wLessons, $wConflicts] = $this->generateWeeksIntoVersion($version, $weekCursor->copy(), $weekEnd, $groups);
            $totalLessons += $wLessons;
            $conflictsRaw += $wConflicts;
            $weekCursor->addWeek();
        }

        return $this->finalizeVersion($version, $totalLessons, $conflictsRaw);
    }

    // ── Ядро генерации ─────────────────────────────────────────────────────

    /**
     * Генерирует занятия для всех групп на диапазон недели в существующую версию.
     * Возвращает [totalLessons, conflictCount].
     */
    private function generateWeeksIntoVersion(ScheduleVersion $version, Carbon $weekStart, Carbon $weekEnd, array $groups): array
    {
        $totalLessons = 0;
        $conflicts = 0;

        foreach ($groups as $group) {
            // Сбрасываем счётчик дисциплин за неделю для этой группы
            $this->weekDisciplineCount[$group->id] = [];

            $workingDays = $group->getWorkingDays();
            $dailyQuotas = $this->distributeQuota($group->getWeeklyPairs(), count($workingDays));

            $current = $weekStart->copy();
            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                if (! in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current)) {
                    $current->addDay();

                    continue;
                }

                if ($this->groupOnPracticeOrExam($group, $current, $version->id)) {
                    $block = $group->getCalendarBlock($current);
                    $typeCode = $block?->type === 'exam_session' ? 'exam' : ($block?->type ?? 'prod_practice');
                    $lessonType = LessonType::where('code', $typeCode)->first()
                        ?? LessonType::where('code', 'practice')->first()
                        ?? LessonType::first();

                    $discId = null;
                    if ($block?->type === 'exam_session') {
                        $assignment = $group->getCurriculumAssignmentForDate($current);
                        $discId = CurriculumDiscipline::where('curriculum_plan_id', $assignment?->curriculum_plan_id)
                            ->where('name', 'like', '%сессия%')
                            ->first()?->id;
                    }
                    if (! $discId) {
                        $discId = $this->getPracticeDisciplineId($group, $current);
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

                if ($this->hasExamOnDay($group, $current, $version->id)) {
                    $current->addDay();
                    $dayIndex++;

                    continue;
                }

                $allDayLessons = [];
                $usedDayDisciplines = [];
                $dayUsedTeachers = [];
                $teacherPlannedSlots = [];
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

                    foreach ($windowData['lessons'] as $ld) {
                        if (isset($ld['sub_lessons'])) {
                            foreach ($ld['sub_lessons'] as $sub) {
                                $allDayLessons[] = $sub;
                                $teacherPlannedSlots[$sub['teacher_id']][] = $sub['lesson_number'];
                            }
                        } else {
                            $allDayLessons[] = $ld;
                            $teacherPlannedSlots[$ld['teacher_id']][] = $ld['lesson_number'];
                        }
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

                    // Считаем уникальные дисциплины дня для учёта частоты
                    $uniqueDisciplineIds = collect($allDayLessons)
                        ->pluck('discipline_id')
                        ->filter()
                        ->unique();

                    foreach ($uniqueDisciplineIds as $discId) {
                        $this->sessionDisciplineHours[$group->id][$discId] = ($this->sessionDisciplineHours[$group->id][$discId] ?? 0) + 1;
                        $this->weekDisciplineCount[$group->id][$discId] = ($this->weekDisciplineCount[$group->id][$discId] ?? 0) + 1;
                    }

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

        return [$totalLessons, $conflicts];
    }

    private function finalizeVersion(ScheduleVersion $version, int $totalLessons, int $conflictsRaw): GenerationResult
    {
        $version->update(['status' => 'draft', 'generated_at' => now()]);

        $actualConflicts = $this->conflictChecker->checkVersion($version->id);
        $conflictCount = count($actualConflicts);

        return GenerationResult::success(
            totalLessons: $totalLessons,
            conflicts: $conflictCount,
            conflictDetails: $actualConflicts,
            version: $version,
        );
    }

    private function resetSession(): void
    {
        $this->groupDayBuildings = [];
        $this->teacherDayBuildings = [];
        $this->teacherDayLessons = [];
        $this->sessionDisciplineHours = [];
        $this->weekDisciplineCount = [];
    }

    // ── Распределение квоты ────────────────────────────────────────────────

    private function distributeQuota(int $totalPairs, int $daysCount): array
    {
        if ($daysCount <= 0) {
            return [];
        }
        $quotas = array_fill(0, $daysCount, 3);
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

    // ── Проверки на конфликты ──────────────────────────────────────────────

    private function hasExamOnDay(Group $group, Carbon $date, int $versionId): bool
    {
        $dateStr = $date->toDateString();

        $inVersion = ScheduleLesson::where('group_id', $group->id)
            ->where('date', $dateStr)
            ->where('version_id', $versionId)
            ->where(function ($q) {
                $q->whereHas('lessonType', fn ($sub) => $sub->whereIn('code', ['exam', 'test', 'diff_test']))
                    ->orWhereHas('discipline', fn ($sub) => $sub->where('name', 'like', '%экзамен%'));
            })
            ->exists();

        if ($inVersion) {
            return true;
        }

        return ScheduleLesson::where('group_id', $group->id)
            ->where('date', $dateStr)
            ->whereHas('version', fn ($q) => $q->where('status', 'published'))
            ->where(function ($q) {
                $q->whereHas('lessonType', fn ($sub) => $sub->whereIn('code', ['exam', 'test', 'diff_test']))
                    ->orWhereHas('discipline', fn ($sub) => $sub->where('name', 'like', '%экзамен%'));
            })
            ->exists();
    }

    private function groupOnPracticeOrExam(Group $group, Carbon $date, int $versionId): bool
    {
        if ($group->isOnPractice($date)) {
            return true;
        }

        return $this->hasExamOnDay($group, $date, $versionId);
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

    // ── Окна ───────────────────────────────────────────────────────────────

    /**
     * Strict Sliding Window: ищет только непрерывные окна без дыр.
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

                    if ($this->shouldGeneratePE($group, $date) && isset($windowSlots[$index + 1])) {
                        $peData = $this->simulatePELessons($version, $group, $date, $slot, $group->shift, $usedTeachers, $currentWindowTeacherSlots);
                        if ($peData) {
                            $isSportComplex = $this->isSportComplexRoom($peData[0]['room_id']);
                            $isValidPlacement = true;

                            if ($isSportComplex) {
                                if (! in_array($slot, [1, 3], true)) {
                                    $isValidPlacement = false;
                                }
                            } else {
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

                        if ($candidate->requires_subgroup) {
                            $subgroups = $group->subgroups()->where('is_active', true)->get();
                            if ($subgroups->count() > 1) {
                                $subLessons = [];
                                $allSubSucceed = true;
                                $tempUsedTeachers = $usedTeachers;
                                $tempTeacherSlots = $currentWindowTeacherSlots;

                                foreach ($subgroups as $subgroup) {
                                    $subTeacher = $this->pickTeacherForDiscipline($candidate->id, $group->id, $date, $slot, $localBuilding, $tempUsedTeachers, $tempTeacherSlots, $subgroup->id);
                                    if (! $subTeacher) {
                                        $allSubSucceed = false;
                                        break;
                                    }

                                    $subRoom = $this->pickRoomForLesson($localBuilding->id, $subgroup->students_count, $candidate, $date, $slot, $subTeacher);
                                    if (! $subRoom) {
                                        $allSubSucceed = false;
                                        break;
                                    }

                                    $subLessons[] = [
                                        'lesson_number' => $slot,
                                        'shift' => $group->shift,
                                        'discipline_id' => $candidate->id,
                                        'teacher_id' => $subTeacher->id,
                                        'room_id' => $subRoom->id,
                                        'building_id' => $subRoom->building_id,
                                        'subgroup_id' => $subgroup->id,
                                        'lesson_type_id' => $typeId,
                                    ];

                                    $tempUsedTeachers[$subTeacher->id] = ($tempUsedTeachers[$subTeacher->id] ?? 0) + 1;
                                    $tempTeacherSlots[$subTeacher->id][] = $slot;
                                }

                                if ($allSubSucceed) {
                                    $lessonsData[] = ['sub_lessons' => $subLessons];
                                    $usedDisciplines[] = $candidate->id;
                                    $usedTeachers = $tempUsedTeachers;
                                    $currentWindowTeacherSlots = $tempTeacherSlots;

                                    continue 2;
                                } else {
                                    $triedDisciplineIds[] = $candidate->id;

                                    continue;
                                }
                            }
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

                    if ($isExamDiscipline) {
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

    // ── Физкультура ────────────────────────────────────────────────────────

    /**
     * Проверяет нужно ли поставить физкультуру на этот день, исходя из остатка часов
     * и доступности спортивного комплекса по расписанию.
     */
    private function shouldGeneratePE(Group $group, Carbon $date): bool
    {
        $currentSemester = $group->getCurrentSemester($date);

        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('name', 'like', '%Физическая культура%')
            ->where('is_schedulable', true)
            ->first();

        if (! $peDiscipline) {
            return false;
        }

        // Проверяем рабочие дни спорткомплекса
        $sportComplexDays = $this->getSportComplexWorkingDays();
        $dayOfWeek = (int) $date->format('N');
        if (! empty($sportComplexDays) && ! in_array($dayOfWeek, $sportComplexDays, true)) {
            return false;
        }

        // Нужно не менее 4 часов остатка, чтобы поставить сдвоенную пару
        $sessionPlaced = ($this->sessionDisciplineHours[$group->id][$peDiscipline->id] ?? 0) * 2;
        $dbRemaining = $this->hoursTracking->getRemainingHours($group, $peDiscipline);
        $remaining = max(0, $dbRemaining - $sessionPlaced);

        return $remaining >= 4;
    }

    private function getSportComplexWorkingDays(): array
    {
        $setting = SystemSetting::where('key', 'sport_complex_working_days')->first();
        if ($setting && $setting->value) {
            $days = json_decode($setting->value, true);
            if (is_array($days)) {
                return array_map('intval', $days);
            }
        }

        return [];
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

    private function isSportComplexRoom(int $roomId): bool
    {
        $room = Room::find($roomId);

        return $room && $room->roomType && $room->roomType->name === 'Спорт.комплекс';
    }

    // ── Выбор дисциплины ───────────────────────────────────────────────────

    /**
     * Выбирает дисциплину для группы с учётом:
     * - остатка часов по учебному плану (сначала самые «должные»)
     * - ограничения 2 раза в неделю на одну дисциплину
     * - часов, уже поставленных в текущем сеансе генерации
     */
    private function pickDisciplineForGroup(Group $group, array $excludeIds = [], ?Carbon $date = null): ?CurriculumDiscipline
    {
        $assignment = $group->getCurriculumAssignmentForDate($date);
        if (! $assignment) {
            return null;
        }

        $currentSemester = $group->getCurrentSemester($date);
        $dayOfWeek = $date ? (int) $date->format('N') : null;

        $query = CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('name', 'not like', '%Физическая культура%')
            ->where('is_schedulable', true);

        $isExamDay = $group->getCalendarBlock($date)?->type === 'exam_session';
        if (! $isExamDay) {
            $query->where('name', 'not like', '%Экзамен%');
        }

        if ($dayOfWeek !== null) {
            $query->whereHas('teacherDisciplines.teacher', function ($q) use ($dayOfWeek) {
                $q->where(function ($sub) use ($dayOfWeek) {
                    $sub->whereJsonContains('working_days', $dayOfWeek)
                        ->orWhereNull('working_days')
                        ->orWhere('working_days', '[]');
                });
            });
        }

        // Исключаем дисциплины, уже использованные в этот день
        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        // Исключаем дисциплины, поставленные 2+ раз за эту неделю
        $overusedIds = array_keys(array_filter(
            $this->weekDisciplineCount[$group->id] ?? [],
            fn ($count) => $count >= 2
        ));
        if (! empty($overusedIds)) {
            $query->whereNotIn('id', $overusedIds);
        }

        $disciplines = $query->get();

        if ($disciplines->isEmpty()) {
            // Fallback без ограничений по частоте — чтобы не оставлять пустых дней
            return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
                ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
                ->where('name', 'not like', '%Физическая культура%')
                ->where('is_schedulable', true)
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->first();
        }

        // Сортируем по убыванию остатка часов (с учётом уже поставленных в сеансе)
        $groupId = $group->id;
        $ranked = $disciplines->map(function (CurriculumDiscipline $disc) use ($groupId, $currentSemester) {
            $dbRemaining = $this->hoursTracking->getRemainingHoursByIds($groupId, $disc->id, $currentSemester);
            $sessionPlaced = ($this->sessionDisciplineHours[$groupId][$disc->id] ?? 0) * 2;
            $remaining = max(0, $dbRemaining - $sessionPlaced);

            return ['disc' => $disc, 'remaining' => $remaining];
        })->filter(fn ($item) => $item['remaining'] > 0)
            ->sortByDesc('remaining')
            ->values();

        if ($ranked->isEmpty()) {
            // Все часы закрыты — возвращаем случайную (не должно мешать)
            return $disciplines->random();
        }

        // Берём из топ-3 случайно, чтобы расписание не было детерминированным
        return $ranked->take(3)->random()['disc'];
    }

    // ── Выбор преподавателя ────────────────────────────────────────────────

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding, array $simulatedTeacherLoads = [], array $simulatedWindowSlotsMap = [], ?int $subgroupId = null): ?Teacher
    {
        $currentSemesterNum = Group::find($groupId)?->getCurrentSemester($date);
        $dateStr = $date->format('Y-m-d');

        $assignments = TeacherDisciplineSemester::whereHas('teacherDiscipline', function ($q) use ($disciplineId, $groupId, $subgroupId) {
            $q->where('discipline_id', $disciplineId)
                ->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id'))
                ->when($subgroupId, fn ($q3) => $q3->where('subgroup_id', $subgroupId));
        })->whereHas('curriculumSemester', function ($q) use ($currentSemesterNum) {
            $q->where('semester_number', $currentSemesterNum);
        })->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($assignments as $assignment) {
            $teacher = $assignment->teacherDiscipline->teacher;

            if ($this->hoursTracking->getTeacherRemainingHoursForDiscipline($teacher, $groupId, $disciplineId, $currentSemesterNum) > 0) {
                if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                    return null;
                }
                if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                    return null;
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
        }

        return null;
    }

    private function pickTeacherForDisciplineFallback(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, array $simulatedTeacherLoads = [], array $simulatedWindowSlotsMap = []): ?Teacher
    {
        return $this->pickTeacherForDiscipline($disciplineId, $groupId, $date, $lessonNumber, Building::where('is_active', true)->first(), $simulatedTeacherLoads, $simulatedWindowSlotsMap);
    }

    private function canAssignToTeacherWithoutWindow(int $teacherId, string $date, int $lessonNumber, array $additionalSlots = []): bool
    {
        $existing = array_merge($this->teacherDayLessons[$teacherId][$date] ?? [], $additionalSlots);
        if (empty($existing)) {
            return true;
        }

        foreach ($existing as $ex) {
            if (abs($ex - $lessonNumber) === 1) {
                return true;
            }
        }

        return false;
    }

    // ── Выбор аудитории ────────────────────────────────────────────────────

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

    // ── Вспомогательные ────────────────────────────────────────────────────

    private function isExam(CurriculumDiscipline $discipline, int $typeId): bool
    {
        $lt = LessonType::find($typeId);
        if ($lt && in_array($lt->code, ['exam', 'test', 'diff_test'])) {
            return true;
        }

        return (bool) preg_match('/экзамен|зачет|аттестация/ui', $discipline->name);
    }

    private function getPracticeDisciplineId(Group $group, ?Carbon $date = null): ?int
    {
        $assignment = $group->getCurriculumAssignmentForDate($date);
        if (! $assignment) {
            return null;
        }

        return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('name', 'like', '%практика%')
            ->first()?->id;
    }

    private function createVersion(string $periodType, Carbon $dateFrom, Carbon $dateTo, array $groupIds, ?string $name = null): ScheduleVersion
    {
        $academicYear = AcademicYear::where('is_current', true)->first();

        return ScheduleVersion::create([
            'name' => $name ?? 'Автогенерация ('.$dateFrom->format('d.m').' - '.$dateTo->format('d.m').')',
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
