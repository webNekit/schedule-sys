<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\DTOs\GenerationResult;
use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
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
            $current = $weekStart->copy();
            $workingDays = $group->getWorkingDays();
            $template = [4, 4, 4, 3, 3];

            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                if (! in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current) || $group->isOnPractice($current)) {
                    $current->addDay();

                    continue;
                }

                $pairsCountToGenerate = $template[$dayIndex] ?? 3;

                if ($dayOfWeek === 6 && $group->shift === 2) {
                    $slotsForDay = array_slice([1, 2, 3, 4], 0, $pairsCountToGenerate);
                    $actualShift = 1;
                } else {
                    $perDaySlots = $group->getAllowedLessonNumbersForDay($dayOfWeek);
                    if (empty($perDaySlots)) {
                        $perDaySlots = $group->shift === 1 ? [1, 2, 3, 4, 5] : [3, 4, 5, 6, 7];
                    }
                    $slotsForDay = array_slice($perDaySlots, 0, $pairsCountToGenerate);
                    $actualShift = $group->shift;
                }

                $building = $this->pickBuildingForGroupDay($group, $current);
                $this->groupDayBuildings[$group->id][$current->format('Y-m-d')] = $building->id;

                $generatedForDay = 0;

                foreach ($slotsForDay as $lessonNumber) {
                    if ($generatedForDay >= $pairsCountToGenerate) {
                        break;
                    }

                    if ($this->shouldGeneratePE($group, $current)) {
                        if ($lessonNumber === $slotsForDay[0] || $lessonNumber === ($slotsForDay[1] ?? $slotsForDay[0])) {
                            $peSuccess = $this->generatePELessons($version, $group, $current, $lessonNumber, $actualShift);
                            if ($peSuccess) {
                                $generatedForDay += 2;
                                $totalLessons += 2;

                                continue;
                            }
                        }
                    }

                    $lesson = $this->createLesson($version, $group, $current, $lessonNumber, $actualShift, $building);
                    if ($lesson) {
                        $generatedForDay++;
                        $totalLessons++;
                    } else {
                        $conflicts++;
                    }
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

    private function createLesson(ScheduleVersion $version, Group $group, Carbon $date, int $lessonNumber, int $shift, Building $building): ?ScheduleLesson
    {
        $discipline = $this->pickDisciplineForGroup($group);
        if (! $discipline) {
            return null;
        }

        $teacher = $this->pickTeacherForDiscipline($discipline->id, $group->id, $date, $lessonNumber, $building);

        $room = $this->pickRoomForLesson($building->id, $group->students_count, $discipline, $date, $lessonNumber, $teacher);

        $typeId = LessonType::where('code', 'lecture')->first()?->id ?? 1;

        if (! $teacher || ! $room) {
            return null;
        }

        $this->teacherDayBuildings[$teacher->id][$date->format('Y-m-d')] = $building->id;

        return ScheduleLesson::create([
            'version_id' => $version->id,
            'date' => $date->toDateString(),
            'lesson_number' => $lessonNumber,
            'shift' => $shift,
            'group_id' => $group->id,
            'discipline_id' => $discipline->id,
            'lesson_type_id' => $typeId,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'building_id' => $building->id,
            'is_auto_generated' => true,
            'status' => 'draft',
        ]);
    }

    private function generatePELessons(ScheduleVersion $version, Group $group, Carbon $date, int $startLessonNumber, int $shift): bool
    {
        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->where('name', 'like', '%Физическая культура%')
            ->first();

        if (! $peDiscipline) {
            return false;
        }

        $sportRoom = Room::whereHas('roomType', fn ($q) => $q->where('name', 'like', '%Спорт%'))->first();
        if (! $sportRoom) {
            return false;
        }

        $teacher = $this->pickTeacherForDiscipline($peDiscipline->id, $group->id, $date, $startLessonNumber, $sportRoom->building);

        if (! $teacher) {
            return false;
        }

        if (
            ! $this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber)
            && ! $this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber + 1)
        ) {
            for ($i = 0; $i <= 1; $i++) {
                ScheduleLesson::create([
                    'version_id' => $version->id,
                    'date' => $date->toDateString(),
                    'lesson_number' => $startLessonNumber + $i,
                    'shift' => $shift,
                    'group_id' => $group->id,
                    'discipline_id' => $peDiscipline->id,
                    'lesson_type_id' => LessonType::where('code', 'practice')->first()?->id ?? 2,
                    'teacher_id' => $teacher->id,
                    'room_id' => $sportRoom->id,
                    'building_id' => $sportRoom->building_id,
                    'is_auto_generated' => true,
                    'status' => 'draft',
                ]);
            }

            return true;
        }

        return false;
    }

    private function shouldGeneratePE(Group $group, Carbon $date): bool
    {
        return rand(1, 5) === 1;
    }

    private function pickDisciplineForGroup(Group $group): ?CurriculumDiscipline
    {
        $assignment = GroupCurriculumAssignment::where('group_id', $group->id)
            ->where('is_active', true)
            ->first();

        if (! $assignment) {
            return null;
        }

        return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('name', 'not like', '%Физическая культура%')
            ->inRandomOrder()
            ->first();
    }

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding): ?Teacher
    {
        $candidates = Teacher::whereHas('disciplines', fn ($q) => $q
            ->where('discipline_id', $disciplineId)
            ->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id')),
        )->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $dateStr = $date->format('Y-m-d');
        $dateString = $date->toDateString();

        $scored = [];

        foreach ($candidates as $teacher) {
            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                continue;
            }

            $dayOfWeek = (int) $date->format('N');

            $teacherWorkingLessonNumbers = $teacher->working_lesson_numbers ?? [];
            if (! empty($teacherWorkingLessonNumbers)) {
                $firstKey = array_key_first($teacherWorkingLessonNumbers);
                if (is_array($teacherWorkingLessonNumbers[$firstKey])) {
                    $allowedForDay = $teacherWorkingLessonNumbers[$dayOfWeek] ?? [];
                    if (! empty($allowedForDay) && ! in_array($lessonNumber, $allowedForDay, true)) {
                        continue;
                    }
                } elseif (! in_array($lessonNumber, $teacherWorkingLessonNumbers, true)) {
                    continue;
                }
            }

            $teacherWorkingDays = $teacher->working_days ?? [];
            if (! empty($teacherWorkingDays) && ! in_array($dayOfWeek, $teacherWorkingDays, true)) {
                continue;
            }

            $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$dateStr] ?? null;

            if ($teacherDayBuildingId && $teacherDayBuildingId !== $targetBuilding->id) {
                continue;
            }

            $existingNumbers = ScheduleLesson::where('teacher_id', $teacher->id)
                ->where('date', $dateString)
                ->where('version_id', '!=', 0)
                ->pluck('lesson_number')
                ->toArray();

            $score = 100;

            if (empty($existingNumbers)) {
                $score += 50;
            } else {
                $minExisting = min($existingNumbers);
                $maxExisting = max($existingNumbers);

                $isAdjacentAfter = $lessonNumber === $maxExisting + 1;
                $isAdjacentBefore = $lessonNumber === $minExisting - 1;

                if ($isAdjacentAfter || $isAdjacentBefore) {
                    $score += 30;
                } else {
                    $gapAfter = $lessonNumber - $maxExisting;
                    $gapBefore = $minExisting - $lessonNumber;

                    if ($gapAfter > 0 && $gapAfter <= 2) {
                        $score += 15 - $gapAfter * 5;
                    } elseif ($gapBefore > 0 && $gapBefore <= 2) {
                        $score += 15 - $gapBefore * 5;
                    }
                }
            }

            $dayLoad = ScheduleLesson::where('teacher_id', $teacher->id)
                ->where('date', $dateString)
                ->count();

            if ($dayLoad >= 4) {
                $score -= 20;
            }

            $scored[] = ['teacher' => $teacher, 'score' => $score];
        }

        if (empty($scored)) {
            return null;
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scored[0]['teacher'];
    }

    private function pickBuildingForGroupDay(Group $group, Carbon $date): Building
    {
        $gb = GroupBuilding::where('group_id', $group->id)
            ->orderBy('is_primary', 'desc')
            ->first();

        if ($gb) {
            return $gb->building;
        }

        return Building::where('is_active', true)->first();
    }

    private function pickRoomForLesson(int $buildingId, int $studentsCount, CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, ?Teacher $teacher): ?Room
    {
        $query = Room::where('building_id', $buildingId)
            ->where('is_active', true)
            ->where('is_available_for_booking', true)
            ->where('capacity', '>=', $studentsCount);

        if ($discipline->requires_lab) {
            $query->whereHas('roomType', fn ($q) => $q->where('requires_lab', true));
        }

        if ($teacher) {
            $personalRoomIds = $teacher->rooms()->pluck('room_id')->toArray();
            if (! empty($personalRoomIds)) {
                $query->orderByRaw('FIELD(id, '.implode(',', $personalRoomIds).') DESC');
            }
        }

        $rooms = $query->get();

        foreach ($rooms as $room) {
            if (! $this->conflictChecker->checkRoomConflict($room->id, $date->format('Y-m-d'), $lessonNumber)) {
                return $room;
            }
        }

        return null;
    }

    private function isNonWorkingDay(Carbon $date): bool
    {
        if ($date->isSunday()) {
            return true;
        }

        if (Holiday::where('date', $date->toDateString())->exists()) {
            return true;
        }

        return Vacation::where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
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
            return Group::where('is_active', true)
                ->where('status', 'active')
                ->get()
                ->all();
        }

        return Group::whereIn('id', $groupIds)->get()->all();
    }
}
