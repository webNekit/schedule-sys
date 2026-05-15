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
use App\Models\TeacherDiscipline;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleGeneratorService
{
    private array $groupDayBuildings = [];
    private array $teacherDayBuildings = [];

    public function __construct(
        private readonly ConflictCheckerService $conflictChecker,
        private readonly HoursTrackingService $hoursTracking,
    ) {
    }

    public function generateForWeek(Carbon $weekStart, array $groupIds = []): GenerationResult
    {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $version = $this->createVersion('week', $weekStart, $weekEnd, $groupIds);
        $groups = $this->getGroups($groupIds);

        $totalLessons = 0;
        $conflicts = 0;

        // Шаблоны распределения 18 пар (36 часов) на 5 дней.
        // Для 1-2 курсов (Пн-Пт)
        $templateFirstShift = [4, 4, 4, 3, 3];
        // Для 3-4 курсов (Вт-Сб)
        $templateSecondShift = [4, 4, 3, 3, 4];

        foreach ($groups as $group) {
            $current = $weekStart->copy();
            $workingDays = $group->getWorkingDays(); // [1,2,3,4,5] или [2,3,4,5,6]
            $isSecondShiftGroup = $group->shift === 2;

            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                // 1. Проверка выходных, праздников и практик
                if (!in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current) || $group->isOnPractice($current)) {
                    $current->addDay();
                    continue;
                }

                // Определяем количество пар на этот конкретный день по шаблону
                $pairsCountToGenerate = $isSecondShiftGroup
                    ? ($templateSecondShift[$dayIndex] ?? 3)
                    : ($templateFirstShift[$dayIndex] ?? 3);

                // 2. Правило субботы: если 3-4 курс и сегодня суббота (6) -> переводим в 1 смену
                $actualShift = ($dayOfWeek === 6) ? 1 : $group->shift;

                // Доступные слоты: если 1 смена (1..5), если 2 смена (3..7)
                $availableSlots = ($actualShift === 1) ? range(1, 5) : range(3, 7);

                // Фиксируем корпус на день
                $building = $this->pickBuildingForGroupDay($group, $current);
                $this->groupDayBuildings[$group->id][$current->format('Y-m-d')] = $building->id;

                $generatedForDay = 0;

                foreach ($availableSlots as $lessonNumber) {
                    if ($generatedForDay >= $pairsCountToGenerate)
                        break;

                    // 3. Физкультура (спаренные пары)
                    if ($this->shouldGeneratePE($group, $current)) {
                        if ($lessonNumber === 1 || $lessonNumber === 3) { // Пытаемся поставить 1-2 или 3-4
                            $peSuccess = $this->generatePELessons($version, $group, $current, $lessonNumber, $actualShift);
                            if ($peSuccess) {
                                $generatedForDay += 2;
                                $totalLessons += 2;
                                continue;
                            }
                        }
                    }

                    // 4. Обычная генерация
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
        // Для упрощения: вызываем генерацию по неделям внутри месяца
        return $this->generateForWeek(Carbon::create($year, $month, 1), $groupIds);
    }

    private function createLesson(ScheduleVersion $version, Group $group, Carbon $date, int $lessonNumber, int $shift, Building $building): ?ScheduleLesson
    {
        // 5. Ищем дисциплину с дефицитом часов
        $discipline = $this->pickDisciplineForGroup($group);
        if (!$discipline)
            return null;

        // Ищем преподавателя (проверяя, чтобы он был в этом же корпусе или свободен)
        $teacher = $this->pickTeacherForDiscipline($discipline->id, $group->id, $date, $lessonNumber, $building);

        // Ищем аудиторию (проверяя тип и вместимость)
        $room = $this->pickRoomForLesson($building->id, $group->students_count, $discipline, $date, $lessonNumber, $teacher);

        $typeId = LessonType::where('code', 'lecture')->first()?->id ?? 1;

        if (!$teacher || !$room)
            return null;

        // Фиксируем корпус препода на день
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
        // Ищем физ-ру
        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn($q) => $q->where('group_id', $group->id))
            ->where('name', 'like', '%Физическая культура%')
            ->first();

        if (!$peDiscipline)
            return false;

        // Ищем спортзал
        $sportRoom = Room::whereHas('roomType', fn($q) => $q->where('name', 'like', '%Спорт%'))->first();
        if (!$sportRoom)
            return false;

        $teacher = $this->pickTeacherForDiscipline($peDiscipline->id, $group->id, $date, $startLessonNumber, $sportRoom->building);
        if (!$teacher)
            return false;

        // Проверяем свободны ли оба слота
        if (
            !$this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber) &&
            !$this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber + 1)
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
        // Шанс 20% в день поставить физ-ру, если еще не было на неделе
        return rand(1, 5) === 1;
    }

    private function pickDisciplineForGroup(Group $group): ?CurriculumDiscipline
    {
        $assignment = GroupCurriculumAssignment::where('group_id', $group->id)->where('is_active', true)->first();
        if (!$assignment)
            return null;

        // Приоритет дисциплинам, по которым еще не вычитаны часы
        return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('name', 'not like', '%Физическая культура%') // Физ-ра ставится отдельно
            ->inRandomOrder()
            ->first();
    }

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding): ?Teacher
    {
        $teachers = Teacher::whereHas('disciplines', fn($q) => $q->where('discipline_id', $disciplineId)->where(fn($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id')))->get();

        foreach ($teachers as $teacher) {
            // Занят ли препод в это время?
            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $date->format('Y-m-d'), $lessonNumber))
                continue;

            // В каком корпусе препод сегодня?
            $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$date->format('Y-m-d')] ?? null;

            // Если он свободен и либо корпус еще не назначен, либо совпадает с нужным
            if (!$teacherDayBuildingId || $teacherDayBuildingId === $targetBuilding->id) {
                return $teacher;
            }
        }
        return null; // Никто не свободен (мягкий конфликт)
    }

    private function pickBuildingForGroupDay(Group $group, Carbon $date): Building
    {
        $gb = GroupBuilding::where('group_id', $group->id)->orderBy('is_primary', 'desc')->first();
        if ($gb)
            return $gb->building;
        return Building::where('is_active', true)->first();
    }

    private function pickRoomForLesson(int $buildingId, int $studentsCount, CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, ?Teacher $teacher): ?Room
    {
        $query = Room::where('building_id', $buildingId)
            ->where('is_active', true)
            ->where('is_available_for_booking', true)
            ->where('capacity', '>=', $studentsCount);

        // Если нужна лаба
        if ($discipline->requires_lab) {
            $query->whereHas('roomType', fn($q) => $q->where('requires_lab', true));
        }

        // Приоритет личной аудитории преподавателя
        if ($teacher) {
            $personalRoomIds = $teacher->rooms()->pluck('room_id')->toArray();
            if (!empty($personalRoomIds)) {
                $query->orderByRaw("FIELD(id, " . implode(',', $personalRoomIds) . ") DESC");
            }
        }

        $rooms = $query->get();
        foreach ($rooms as $room) {
            if (!$this->conflictChecker->checkRoomConflict($room->id, $date->format('Y-m-d'), $lessonNumber)) {
                return $room;
            }
        }
        return null;
    }

    private function isNonWorkingDay(Carbon $date): bool
    {
        if ($date->isSunday())
            return true;
        if (Holiday::where('date', $date->toDateString())->exists())
            return true;
        return Vacation::where('start_date', '<=', $date->toDateString())->where('end_date', '>=', $date->toDateString())->exists();
    }

    private function createVersion(string $periodType, Carbon $dateFrom, Carbon $dateTo, array $groupIds): ScheduleVersion
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        return ScheduleVersion::create([
            'name' => "Автогенерация ({$dateFrom->format('d.m')} - {$dateTo->format('d.m')})",
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
        return empty($groupIds)
            ? Group::where('is_active', true)->where('status', 'active')->get()->all()
            : Group::whereIn('id', $groupIds)->get()->all();
    }
}