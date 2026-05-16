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
            $workingDays = $group->getWorkingDays();
            $workingDaysCount = count($workingDays);

            // Распределяем квоту: максимум 18 пар (36 часов), минимум 3 в день, максимум 5 в день
            $dailyQuotas = $this->distributeQuota(18, $workingDaysCount);

            $current = $weekStart->copy();
            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                if (! in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current) || $group->isOnPractice($current)) {
                    $current->addDay();

                    continue;
                }

                $pairsCountToGenerate = $dailyQuotas[$dayIndex] ?? 3;

                // Получаем разрешенные номера пар из настроек системы/группы
                $perDaySlots = $group->getAllowedLessonNumbersForDay($dayOfWeek);
                if (empty($perDaySlots)) {
                    // Фоллбэк: если не задано, берем по смене
                    $perDaySlots = $group->shift === 1 ? [1, 2, 3, 4, 5] : [3, 4, 5, 6, 7];
                }

                // Ищем лучшее "плавающее окно" слотов для этого дня
                $bestData = $this->findBestWindow($version, $group, $current, $perDaySlots, $pairsCountToGenerate);

                if (! empty($bestData['lessons'])) {
                    $buildingId = $bestData['building']->id;
                    $this->groupDayBuildings[$group->id][$current->format('Y-m-d')] = $buildingId;

                    foreach ($bestData['lessons'] as $lessonData) {
                        ScheduleLesson::create(array_merge($lessonData, [
                            'version_id' => $version->id,
                            'date' => $current->toDateString(),
                            'group_id' => $group->id,
                            'is_auto_generated' => true,
                            'status' => 'draft',
                        ]));
                        $totalLessons++;
                        $this->teacherDayBuildings[$lessonData['teacher_id']][$current->format('Y-m-d')] = $buildingId;
                    }

                    // Если смогли сгенерировать меньше пар, чем планировалось (нет преподавателей/кабинетов)
                    if (count($bestData['lessons']) < $pairsCountToGenerate) {
                        $conflicts += ($pairsCountToGenerate - count($bestData['lessons']));
                    }
                } else {
                    // Полностью не удалось составить день
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

    /**
     * Распределяет общую недельную квоту пар по рабочим дням (от 3 до 5 пар в день)
     */
    private function distributeQuota(int $totalPairs, int $daysCount): array
    {
        if ($daysCount <= 0) {
            return [];
        }
        $quotas = array_fill(0, $daysCount, 3); // База: минимум 3 пары в день
        $remaining = $totalPairs - ($daysCount * 3);

        $i = 0;
        while ($remaining > 0 && $i < $daysCount) {
            if ($quotas[$i] < 5) { // Ограничение: максимум 5 пар в день
                $quotas[$i]++;
                $remaining--;
            }
            $i = ($i + 1) % $daysCount;
        }

        rsort($quotas); // Сортируем по убыванию (самые тяжелые дни в начало недели)

        return $quotas;
    }

    /**
     * Алгоритм "Плавающего окна": проверяет все возможные последовательности пар
     * и выбирает ту, которая заполняется с наименьшим количеством "окон"
     */
    private function findBestWindow(ScheduleVersion $version, Group $group, Carbon $date, array $allowedSlots, int $targetPairs): array
    {
        $windows = [];
        $n = count($allowedSlots);

        // Формируем все непрерывные окна нужной длины
        for ($i = 0; $i <= $n - $targetPairs; $i++) {
            $windowSlots = array_slice($allowedSlots, $i, $targetPairs);
            $isContinuous = true;
            for ($j = 1; $j < count($windowSlots); $j++) {
                if ($windowSlots[$j] !== $windowSlots[$j - 1] + 1) {
                    $isContinuous = false;
                    break;
                }
            }
            if ($isContinuous) {
                $windows[] = $windowSlots;
            }
        }

        // Если непрерывных окон нет (настроены разорванные пары), берем любые куски
        if (empty($windows)) {
            for ($i = 0; $i <= $n - $targetPairs; $i++) {
                $windows[] = array_slice($allowedSlots, $i, $targetPairs);
            }
        }

        $bestWindow = null;
        $bestScore = -1;
        $bestLessonsData = [];

        $building = $this->pickBuildingForGroupDay($group, $date);

        // Симулируем заполнение каждого окна
        foreach ($windows as $windowSlots) {
            $score = 0;
            $lessonsData = [];
            $usedDisciplines = [];
            $usedTeachers = [];

            $skipNext = false;
            foreach ($windowSlots as $index => $slot) {
                if ($skipNext) {
                    $skipNext = false;

                    continue;
                }

                // Пытаемся поставить Физкультуру (она спаренная, забирает 2 слота)
                if ($this->shouldGeneratePE($group, $date) && isset($windowSlots[$index + 1])) {
                    $peData = $this->simulatePELessons($version, $group, $date, $slot, $group->shift, $usedTeachers);
                    if ($peData) {
                        $lessonsData[] = $peData[0];
                        $lessonsData[] = $peData[1];
                        $score += 2;
                        $skipNext = true;

                        continue;
                    }
                }

                // Стандартная пара
                $discipline = $this->pickDisciplineForGroup($group, $usedDisciplines);
                if (! $discipline) {
                    continue;
                }

                $teacher = $this->pickTeacherForDiscipline($discipline->id, $group->id, $date, $slot, $building, $usedTeachers);
                if (! $teacher) {
                    continue;
                }

                $room = $this->pickRoomForLesson($building->id, $group->students_count, $discipline, $date, $slot, $teacher);
                if (! $room) {
                    continue;
                }

                $lessonsData[] = [
                    'lesson_number' => $slot,
                    'shift' => $group->shift,
                    'discipline_id' => $discipline->id,
                    'teacher_id' => $teacher->id,
                    'room_id' => $room->id,
                    'building_id' => $building->id,
                    'lesson_type_id' => LessonType::where('code', 'lecture')->first()?->id ?? 1,
                ];

                $score++;
                $usedDisciplines[] = $discipline->id;
                $usedTeachers[$teacher->id] = ($usedTeachers[$teacher->id] ?? 0) + 1;
            }

            // Выигрывает окно, в которое мы смогли вставить больше всего пар (без окон)
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLessonsData = $lessonsData;
                $bestWindow = $windowSlots;
            }
        }

        return ['slots' => $bestWindow, 'lessons' => $bestLessonsData, 'building' => $building];
    }

    private function simulatePELessons(ScheduleVersion $version, Group $group, Carbon $date, int $startLessonNumber, int $shift, array &$usedTeachers): ?array
    {
        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->where('name', 'like', '%Физическая культура%')
            ->first();

        if (! $peDiscipline) {
            return null;
        }

        $sportRoom = Room::whereHas('roomType', fn ($q) => $q->where('name', 'like', '%Спорт%'))->first();
        if (! $sportRoom) {
            return null;
        }

        $teacher = $this->pickTeacherForDiscipline($peDiscipline->id, $group->id, $date, $startLessonNumber, $sportRoom->building, $usedTeachers);
        if (! $teacher) {
            return null;
        }

        // Для спаренной физ-ры преподаватель должен быть доступен и на следующую пару
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

    private function shouldGeneratePE(Group $group, Carbon $date): bool
    {
        return rand(1, 5) === 1;
    }

    private function pickDisciplineForGroup(Group $group, array $excludeIds = []): ?CurriculumDiscipline
    {
        $assignment = GroupCurriculumAssignment::where('group_id', $group->id)
            ->where('is_active', true)
            ->first();

        if (! $assignment) {
            return null;
        }

        $query = CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('name', 'not like', '%Физическая культура%');

        // Стараемся не ставить одну и ту же дисциплину 4 раза в день
        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $discipline = $query->inRandomOrder()->first();

        // Если все предметы исчерпаны, разрешаем повторять (снимаем фильтр)
        if (! $discipline && ! empty($excludeIds)) {
            return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
                ->where('name', 'not like', '%Физическая культура%')
                ->inRandomOrder()
                ->first();
        }

        return $discipline;
    }

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding, array $simulatedTeacherLoads = []): ?Teacher
    {
        $candidates = Teacher::whereHas(
            'disciplines',
            fn ($q) => $q
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
            // ИСПОЛЬЗУЕМ ВАШ НОВЫЙ МЕТОД из модели Teacher! (учитывает окна, больничные и рабочие дни)
            if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                continue;
            }

            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                continue;
            }

            // Проверка здания (преподаватель не может бегать между зданиями в один день)
            $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$dateStr] ?? null;
            if ($teacherDayBuildingId && $teacherDayBuildingId !== $targetBuilding->id) {
                continue;
            }

            $score = 100;

            $existingNumbers = ScheduleLesson::where('teacher_id', $teacher->id)
                ->where('date', $dateString)
                ->where('version_id', '!=', 0)
                ->pluck('lesson_number')
                ->toArray();

            // Скоринг для склейки пар (минимизация окон)
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

            // Учет нагрузки в БД + симулированной нагрузки (чтобы не превысить 5 пар в день)
            $dbLoad = ScheduleLesson::where('teacher_id', $teacher->id)->where('date', $dateString)->count();
            $simLoad = $simulatedTeacherLoads[$teacher->id] ?? 0;
            $dayLoad = $dbLoad + $simLoad;

            if ($dayLoad >= 4) {
                $score -= 20; // Пенальти за высокую нагрузку
            }
            if ($dayLoad >= ($teacher->max_lessons_per_day ?? 5)) {
                continue; // Жесткое ограничение
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

        return $gb ? $gb->building : Building::where('is_active', true)->first();
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
