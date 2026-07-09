<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\DTOs\GenerationResult;
use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumSemester;
use App\Models\ExamSchedule;
use App\Models\Group;
use App\Models\GroupBuilding;
use App\Models\GroupDayBuilding;
use App\Models\GroupStream;
use App\Models\Holiday;
use App\Models\LessonType;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\SportComplexSlot;
use App\Models\Teacher;
use App\Models\TeacherBuilding;
use App\Models\TeacherDisciplineSemester;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleGeneratorService
{
    // ── Состояние сеанса генерации ─────────────────────────────────────────

    /** Физкультура (и выезд в спорткомплекс, и зал корпуса) допускается только на парах 1–4. */
    private const MAX_PE_PAIR = 4;

    private array $groupDayBuildings = [];

    private array $teacherDayBuildings = [];

    private array $teacherDayLessons = [];

    /** Кол-во занятий по дисциплине+типу за сеанс: [groupId][discId][typeCode] => count */
    private array $sessionDisciplineTypeHours = [];

    /** Кол-во занятий дисциплиной в текущей неделе группы: [groupId][discId] => count */
    private array $weekDisciplineCount = [];

    /** Последняя дата, когда дисциплина ставилась группе: [groupId][discId] => 'Y-m-d' */
    private array $disciplineLastDate = [];

    /**
     * Шаблон «стабильной недели»: [groupId][dayOfWeek] => [[discId, typeCode], ...]
     * Строится при первой неделе, повторяется в последующих.
     */
    private array $weekTemplate = [];

    /** true — идёт первая (шаблонная) неделя семестровой генерации */
    private bool $isTemplateWeek = false;

    /** Недельное расписание спорткомплекса: [groupId] => SportComplexSlot. Лениво загружается. */
    private ?Collection $sportSchedule = null;

    /** Предупреждения сеанса (например, не поставленный параллельный преподаватель): [key => текст] */
    private array $generationWarnings = [];

    /** Кэш корпусов преподавателя: [teacherId => [buildingId, ...]]. Пусто = без ограничений. */
    private array $teacherBuildingIds = [];

    /** Кэш типов занятий за сеанс: code => id. */
    private array $lessonTypeIds = [];

    /** Кэш типов занятий за сеанс: id => code. */
    private array $lessonTypeCodesById = [];

    public function __construct(
        private readonly ConflictCheckerService $conflictChecker,
        private readonly HoursTrackingService $hoursTracking,
        private readonly SchedulingRuleResolver $rules,
        private readonly CustomRuleEvaluator $customRules,
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
            [$wLessons, $wConflicts] = $this->generateWeeksIntoVersion(
                $version, $weekCursor->copy(), $weekCursor->copy()->endOfWeek(Carbon::SUNDAY), $groups
            );
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
            return GenerationResult::fail('Не найден текущий учебный год');
        }

        $baseStart = Carbon::parse($academicYear->date_start ?? now());

        $start = $semester === 1
            ? ($academicYear->first_semester_start ?? $academicYear->date_start)
            : ($academicYear->second_semester_start ?? $baseStart->copy()->addMonths(6)->toDateString());

        $end = $semester === 1
            ? ($academicYear->first_semester_end ?? $baseStart->copy()->addMonths(5)->toDateString())
            : ($academicYear->second_semester_end ?? $academicYear->date_end);

        if (! $start || ! $end) {
            return GenerationResult::fail('Не заданы даты семестра в учебном году');
        }

        $periodStart = Carbon::parse($start)->startOfDay();
        $periodEnd = Carbon::parse($end)->endOfDay();

        $this->resetSession();

        $version = $this->createVersion(
            'semester', $periodStart, $periodEnd, $groupIds,
            "Семестр {$semester} ({$periodStart->format('d.m.Y')} — {$periodEnd->format('d.m.Y')})"
        );
        $groups = $this->getGroups($groupIds);

        $totalLessons = 0;
        $conflictsRaw = 0;
        $weekCursor = $periodStart->copy()->startOfWeek(Carbon::MONDAY);
        $firstWeek = true;

        while ($weekCursor->lessThanOrEqualTo($periodEnd)) {
            // Первая неделя строит шаблон, последующие его повторяют
            $this->isTemplateWeek = $firstWeek;

            [$wLessons, $wConflicts] = $this->generateWeeksIntoVersion(
                $version, $weekCursor->copy(), $weekCursor->copy()->endOfWeek(Carbon::SUNDAY), $groups
            );
            $totalLessons += $wLessons;
            $conflictsRaw += $wConflicts;
            $weekCursor->addWeek();
            $firstWeek = false;
        }

        return $this->finalizeVersion($version, $totalLessons, $conflictsRaw);
    }

    // ── Ядро генерации ─────────────────────────────────────────────────────

    private function generateWeeksIntoVersion(ScheduleVersion $version, Carbon $weekStart, Carbon $weekEnd, array $groups): array
    {
        $totalLessons = 0;
        $conflicts = 0;

        foreach ($groups as $group) {
            $this->weekDisciplineCount[$group->id] = [];

            $workingDays = $group->getWorkingDays();
            $dailyQuotas = $this->distributeQuota($group->getWeeklyPairs(), count($workingDays), $group);

            $current = $weekStart->copy();
            $dayIndex = 0;

            while ($current->lessThanOrEqualTo($weekEnd)) {
                $dayOfWeek = (int) $current->format('N');

                if (! in_array($dayOfWeek, $workingDays, true) || $this->isNonWorkingDay($current)) {
                    $current->addDay();

                    continue;
                }

                if ($this->groupOnPracticeOrExam($group, $current, $version->id)) {
                    $this->placePracticeMarker($group, $current, $version);
                    $current->addDay();
                    $dayIndex++;

                    continue;
                }

                // Неделя экзаменационной сессии: ставим только назначенные экзамены
                // на их точные даты, обычные пары не генерируем.
                $calendarBlock = $group->getCalendarBlock($current);
                if ($calendarBlock && $calendarBlock->type === 'exam_session') {
                    $this->placeScheduledExams($group, $current, $version);
                    $current->addDay();
                    $dayIndex++;

                    continue;
                }

                // Экзамен, назначенный на эту дату вне формального блока сессии —
                // ставим строго и больше ничего в этот день не генерируем.
                if ($this->placeScheduledExams($group, $current, $version)) {
                    $current->addDay();
                    $dayIndex++;

                    continue;
                }

                $pairsCountToGenerate = $dailyQuotas[$dayIndex] ?? $this->rules->intParam('pairs_per_day', 'base', 3, $group);
                $perDaySlots = $group->getAllowedLessonNumbersForDay($dayOfWeek);
                if (empty($perDaySlots)) {
                    $perDaySlots = $group->shift === 1
                        ? $this->rules->arrayParam('default_slots', 'shift1', [1, 2, 3, 4, 5], $group)
                        : $this->rules->arrayParam('default_slots', 'shift2', [3, 4, 5, 6, 7], $group);
                }

                // День выезда в спорткомплекс: физра стоит на отмеченных диспетчером
                // парах, а обычные пары примыкают к ним ВПЛОТНУЮ (без окон) — перед
                // блоком выезда или после него. Переезд приходится на обеденный разрыв.
                $sportPairs = $this->getSportPairsForGroupDay($group, $current);
                if (! empty($sportPairs) && $this->hasRemainingPE($group, $current)) {
                    $perDaySlots = PairSlotPlanner::sportComplexDaySlots($perDaySlots, $sportPairs, $pairsCountToGenerate);
                }

                if ($this->hasExamOnDay($group, $current, $version->id)) {
                    $current->addDay();
                    $dayIndex++;

                    continue;
                }

                // Шаблон для этого дня (если не первая неделя семестра)
                $templateForDay = (! $this->isTemplateWeek && ! empty($this->weekTemplate[$group->id][$dayOfWeek]))
                    ? $this->weekTemplate[$group->id][$dayOfWeek]
                    : null;

                $allDayLessons = [];
                $usedDayDisciplines = [];
                $dayUsedTeachers = [];
                $teacherPlannedSlots = [];
                $remainingSlots = $perDaySlots;
                $remainingTarget = $pairsCountToGenerate;

                while ($remainingTarget > 0 && ! empty($remainingSlots)) {
                    $isFirstWindow = empty($allDayLessons);
                    $templateHint = $templateForDay ? array_shift($templateForDay) : null;

                    $windowData = $this->findBestStrictWindow(
                        $version, $group, $current, array_values($remainingSlots),
                        $remainingTarget, $usedDayDisciplines, $dayUsedTeachers,
                        $teacherPlannedSlots,
                        anchorStart: ! $isFirstWindow,
                        templateHint: $templateHint,
                    );

                    if (empty($windowData['lessons'])) {
                        // Backtracking: попробуем с перемешанным порядком слотов
                        $shuffled = $remainingSlots;
                        shuffle($shuffled);
                        $windowData = $this->findBestStrictWindow(
                            $version, $group, $current, array_values($shuffled),
                            $remainingTarget, $usedDayDisciplines, $dayUsedTeachers,
                            $teacherPlannedSlots,
                            anchorStart: false,
                            templateHint: null,
                        );

                        if (empty($windowData['lessons'])) {
                            break;
                        }
                    }

                    foreach ($windowData['lessons'] as $ld) {
                        if (isset($ld['sub_lessons'])) {
                            // Несколько уроков на одном слоте (подгруппы/параллель):
                            // первый — основной, остальные помечаем вторичными,
                            // чтобы часы группы по дисциплине не задваивались.
                            foreach ($ld['sub_lessons'] as $subIndex => $sub) {
                                $sub['is_parallel_secondary'] = $subIndex > 0;
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

                    $uniqueDiscs = collect($allDayLessons)->pluck('discipline_id')->filter()->unique();
                    foreach ($uniqueDiscs as $discId) {
                        $typeCode = collect($allDayLessons)->firstWhere('discipline_id', $discId)['lesson_type_code'] ?? 'lecture';
                        $this->sessionDisciplineTypeHours[$group->id][$discId][$typeCode] =
                            ($this->sessionDisciplineTypeHours[$group->id][$discId][$typeCode] ?? 0) + 1;
                        $this->weekDisciplineCount[$group->id][$discId] =
                            ($this->weekDisciplineCount[$group->id][$discId] ?? 0) + 1;
                        $this->disciplineLastDate[$group->id][$discId] = $current->format('Y-m-d');

                        // Записываем шаблон первой недели семестра
                        if ($this->isTemplateWeek) {
                            $this->weekTemplate[$group->id][$dayOfWeek][] = ['discId' => $discId, 'typeCode' => $typeCode];
                        }
                    }

                    foreach ($allDayLessons as $lessonData) {
                        $data = $lessonData;
                        unset($data['lesson_type_code']); // служебное поле, не в БД

                        ScheduleLesson::create(array_merge($data, [
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

                    // Распространяем лекции на другие группы потока
                    $this->propagateStreamLessons($version, $group->id, $allDayLessons, $current);

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

        return GenerationResult::success(
            totalLessons: $totalLessons,
            conflicts: count($actualConflicts),
            conflictDetails: $actualConflicts,
            version: $version,
            warnings: array_values($this->generationWarnings),
        );
    }

    private function resetSession(): void
    {
        $this->groupDayBuildings = [];
        $this->teacherDayBuildings = [];
        $this->teacherDayLessons = [];
        $this->sessionDisciplineTypeHours = [];
        $this->weekDisciplineCount = [];
        $this->disciplineLastDate = [];
        $this->weekTemplate = [];
        $this->isTemplateWeek = false;
        $this->generationWarnings = [];
        $this->teacherBuildingIds = [];

        // Типы занятий не меняются в ходе генерации — грузим один раз за сеанс.
        $types = LessonType::pluck('code', 'id');
        $this->lessonTypeCodesById = $types->map(fn ($code) => (string) $code)->all();
        $this->lessonTypeIds = $types->flip()->map(fn ($id) => (int) $id)->all();

        $this->rules->load();
        $this->customRules->loadPlacementRules();
    }

    /** Id типа занятия по коду из кэша сеанса. */
    private function lessonTypeId(string $code, ?int $default = null): ?int
    {
        return $this->lessonTypeIds[$code] ?? $default;
    }

    /**
     * Корпуса, в которых работает преподаватель (из teacher_buildings).
     * Пустой массив — ограничений нет, преподаватель доступен в любом корпусе.
     *
     * @return array<int, int>
     */
    private function getTeacherBuildingIds(int $teacherId): array
    {
        if (! array_key_exists($teacherId, $this->teacherBuildingIds)) {
            $this->teacherBuildingIds[$teacherId] = TeacherBuilding::where('teacher_id', $teacherId)
                ->pluck('building_id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->teacherBuildingIds[$teacherId];
    }

    // ── Потоки (совместные лекции нескольких групп) ────────────────────────

    /**
     * После размещения лекций для группы проверяет потоки и
     * добавляет занятия другим группам того же потока.
     */
    private function propagateStreamLessons(ScheduleVersion $version, int $sourceGroupId, array $allDayLessons, Carbon $date): void
    {
        foreach ($allDayLessons as $lessonData) {
            $discId = $lessonData['discipline_id'] ?? null;
            $typeCode = $lessonData['lesson_type_code'] ?? 'lecture';

            if (! $discId || $typeCode !== 'lecture') {
                continue;
            }

            $stream = GroupStream::where('discipline_id', $discId)
                ->where('is_active', true)
                ->whereHas('members', fn ($q) => $q->where('group_id', $sourceGroupId))
                ->first();

            if (! $stream) {
                continue;
            }

            foreach ($stream->groups as $streamGroup) {
                if ($streamGroup->id === $sourceGroupId) {
                    continue;
                }

                $teacherConflict = $this->conflictChecker->checkTeacherConflict(
                    $lessonData['teacher_id'], $date->format('Y-m-d'), $lessonData['lesson_number']
                );
                $groupConflict = $this->conflictChecker->checkGroupConflict(
                    $streamGroup->id, $date->format('Y-m-d'), $lessonData['lesson_number']
                );

                if (! $teacherConflict && ! $groupConflict) {
                    $data = $lessonData;
                    unset($data['lesson_type_code']);

                    ScheduleLesson::create(array_merge($data, [
                        'version_id' => $version->id,
                        'date' => $date->toDateString(),
                        'group_id' => $streamGroup->id,
                        'is_auto_generated' => true,
                        'status' => 'draft',
                    ]));
                }
            }
        }
    }

    // ── Экзамены по назначенным датам ──────────────────────────────────────

    /**
     * Ставит экзамены, которым диспетчер назначил эту точную дату (таблица exam_schedules).
     * Возвращает true, если был поставлен хотя бы один экзамен — тогда обычная
     * генерация на этот день пропускается (день полностью отдан под экзамен).
     */
    private function placeScheduledExams(Group $group, Carbon $date, ScheduleVersion $version): bool
    {
        $assignment = $group->getCurriculumAssignmentForDate($date);
        if (! $assignment) {
            return false;
        }

        $semester = $group->getCurrentSemester($date);

        $exams = ExamSchedule::with('curriculumSemester')
            ->whereDate('exam_date', $date->toDateString())
            ->where(function ($q) use ($group) {
                $q->whereNull('group_id')->orWhere('group_id', $group->id);
            })
            ->whereHas('curriculumSemester', function ($q) use ($assignment, $group, $semester) {
                $q->where('semester_number', $semester)
                    ->where('course_number', $group->current_course)
                    ->whereHas('discipline', fn ($d) => $d->where('curriculum_plan_id', $assignment->curriculum_plan_id));
            })
            ->get();

        if ($exams->isEmpty()) {
            return false;
        }

        $examTypeId = $this->lessonTypeId('exam') ?? LessonType::first()?->id;
        $building = $this->pickBuildingForGroupDay($group, $date);
        $autoSlot = 1;

        foreach ($exams as $exam) {
            $cs = $exam->curriculumSemester;
            if (! $cs) {
                continue;
            }

            // Преподаватель: назначенный вручную, иначе приоритетный по sort_order
            $teacherId = $exam->teacher_id;
            if (! $teacherId) {
                $tds = TeacherDisciplineSemester::whereHas('teacherDiscipline', function ($q) use ($cs, $group) {
                    $q->where('discipline_id', $cs->discipline_id)
                        ->where(fn ($x) => $x->where('group_id', $group->id)->orWhereNull('group_id'));
                })->where('curriculum_semester_id', $cs->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();
                $teacherId = $tds?->teacherDiscipline?->teacher_id;
            }

            // Аудитория: назначенная вручную определяет корпус
            $roomId = $exam->room_id;
            $buildingId = $building?->id;
            if ($roomId) {
                $buildingId = Room::find($roomId)?->building_id ?? $buildingId;
            }

            ScheduleLesson::create([
                'version_id' => $version->id,
                'date' => $date->toDateString(),
                'group_id' => $group->id,
                'lesson_number' => $exam->lesson_number ?: $autoSlot,
                'discipline_id' => $cs->discipline_id,
                'room_id' => $roomId,
                'teacher_id' => $teacherId,
                'shift' => $group->shift,
                'lesson_type_id' => $examTypeId,
                'building_id' => $buildingId,
                'is_auto_generated' => true,
                'status' => 'draft',
            ]);
            $autoSlot++;
        }

        return true;
    }

    // ── Квота пар ──────────────────────────────────────────────────────────

    private function distributeQuota(int $totalPairs, int $daysCount, ?Group $group = null): array
    {
        $base = $this->rules->intParam('pairs_per_day', 'base', 3, $group);
        $max = $this->rules->intParam('pairs_per_day', 'max', 5, $group);

        return PairSlotPlanner::distributeQuota($totalPairs, $daysCount, $base, $max);
    }

    // ── Проверки на конфликты ──────────────────────────────────────────────

    private function hasExamOnDay(Group $group, Carbon $date, int $versionId): bool
    {
        $dateStr = $date->toDateString();

        $inVersion = ScheduleLesson::where('group_id', $group->id)
            ->where('date', $dateStr)->where('version_id', $versionId)
            ->where(function ($q) {
                $q->whereHas('lessonType', fn ($s) => $s->whereIn('code', ['exam', 'test', 'diff_test']))
                    ->orWhereHas('discipline', fn ($s) => $s->where('category', 'exam'));
            })->exists();

        if ($inVersion) {
            return true;
        }

        return ScheduleLesson::where('group_id', $group->id)
            ->where('date', $dateStr)
            ->whereHas('version', fn ($q) => $q->where('status', 'published'))
            ->where(function ($q) {
                $q->whereHas('lessonType', fn ($s) => $s->whereIn('code', ['exam', 'test', 'diff_test']))
                    ->orWhereHas('discipline', fn ($s) => $s->where('category', 'exam'));
            })->exists();
    }

    private function groupOnPracticeOrExam(Group $group, Carbon $date, int $versionId): bool
    {
        return $group->isOnPractice($date) || $this->hasExamOnDay($group, $date, $versionId);
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

    private function placePracticeMarker(Group $group, Carbon $date, ScheduleVersion $version): void
    {
        $block = $group->getCalendarBlock($date);
        $typeCode = $block?->type === 'exam_session' ? 'exam' : ($block?->type ?? 'prod_practice');
        $lessonType = LessonType::where('code', $typeCode)->first()
            ?? LessonType::where('code', 'practice')->first()
            ?? LessonType::first();

        $discId = null;
        if ($block?->type === 'exam_session') {
            $assignment = $group->getCurriculumAssignmentForDate($date);
            $discId = CurriculumDiscipline::where('curriculum_plan_id', $assignment?->curriculum_plan_id)
                ->where('name', 'like', '%сессия%')->first()?->id;
        }
        if (! $discId) {
            $discId = $this->getPracticeDisciplineId($group, $date);
        }

        if ($discId) {
            ScheduleLesson::create([
                'version_id' => $version->id,
                'date' => $date->toDateString(),
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
    }

    // ── Окна (Strict Sliding Window) ───────────────────────────────────────

    private function findBestStrictWindow(
        ScheduleVersion $version,
        Group $group,
        Carbon $date,
        array $allowedSlots,
        int $targetPairs,
        array &$usedDisciplines = [],
        array &$usedTeachers = [],
        array $existingTeacherPlannedSlots = [],
        bool $anchorStart = false,
        ?array $templateHint = null,
    ): array {
        $building = $this->pickBuildingForGroupDay($group, $date) ?? Building::where('is_active', true)->first();
        if (! $building) {
            return ['slots' => [], 'lessons' => [], 'building' => $building];
        }
        $n = count($allowedSlots);

        for ($currentLength = min($targetPairs, $n); $currentLength >= 1; $currentLength--) {
            $endIdx = $anchorStart ? 0 : ($n - $currentLength);
            for ($i = 0; $i <= $endIdx; $i++) {
                $windowSlots = array_slice($allowedSlots, $i, $currentLength);

                // Только непрерывные окна
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
                $typeId = $this->lessonTypeId('lecture', 1);

                // Локальные копии — пополняются по ходу окна и коммитятся в
                // переданные по ссылке массивы ТОЛЬКО при успехе всего окна.
                // Иначе неудачная попытка окна засоряла бы excludeIds для следующих.
                $winUsedDisciplines = $usedDisciplines;
                $winUsedTeachers = $usedTeachers;

                foreach ($windowSlots as $index => $slot) {
                    if ($skipNext) {
                        $skipNext = false;

                        continue;
                    }

                    // Физкультура
                    if ($this->getSportSchedule()->has($group->id)) {
                        // Спорткомплекс: каждая отмеченная пара — отдельное занятие.
                        $sportPairs = $this->getSportPairsForGroupDay($group, $date);
                        if (in_array($slot, $sportPairs, true)) {
                            if ($this->hasRemainingPE($group, $date)) {
                                $peLesson = $this->simulateSportPELesson($group, $date, $slot, $group->shift, $usedTeachers, $currentWindowTeacherSlots);
                                if ($peLesson) {
                                    $lessonsData[] = $peLesson;
                                    $currentWindowTeacherSlots[$peLesson['teacher_id']][] = $peLesson['lesson_number'];

                                    continue;
                                }
                            }

                            // Пара отмечена диспетчером под выезд в спорткомплекс — она
                            // зарезервирована: обычное занятие сюда не ставим (даже если
                            // физру поставить не удалось — пусть слот останется пустым).
                            continue;
                        }
                    } elseif ($this->rules->boolParam('pe_block', 'doubled', true, $group) && $this->shouldGeneratePE($group, $date) && isset($windowSlots[$index + 1]) && $slot <= min($this->rules->intParam('pe_block', 'max_start_slot', 3, $group), self::MAX_PE_PAIR - 1)) {
                        // Обычная группа — сдвоенная физра в спортзале своего корпуса в начале дня.
                        $peData = $this->simulatePELessons($version, $group, $date, $slot, $group->shift, $usedTeachers, $currentWindowTeacherSlots);
                        if ($peData) {
                            $lessonsData[] = $peData[0];
                            $lessonsData[] = $peData[1];
                            $currentWindowTeacherSlots[$peData[0]['teacher_id']][] = $peData[0]['lesson_number'];
                            $currentWindowTeacherSlots[$peData[1]['teacher_id']][] = $peData[1]['lesson_number'];
                            $skipNext = true;

                            continue;
                        }
                    }

                    // Выбор дисциплины
                    $discipline = null;
                    $teacher = null;
                    $room = null;
                    $chosenTypeCode = 'lecture';
                    $triedDisciplineIds = [];

                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        $candidate = $this->pickDisciplineForGroup(
                            $group,
                            array_merge($winUsedDisciplines, $triedDisciplineIds),
                            $date,
                            $attempt === 0 ? $templateHint : null,
                        );

                        if (! $candidate) {
                            break;
                        }

                        $candidateDisc = $candidate['discipline'];
                        $candidateTypeCode = $candidate['typeCode'];
                        $candidateLessonTypeId = $this->getLessonTypeId($candidateTypeCode);

                        $candidateTeacher = $this->pickTeacherForDiscipline(
                            $candidateDisc->id, $group->id, $date, $slot, $localBuilding,
                            $winUsedTeachers, $currentWindowTeacherSlots
                        );
                        if (! $candidateTeacher) {
                            $candidateTeacher = $this->pickTeacherForDisciplineFallback(
                                $candidateDisc->id, $group->id, $date, $slot,
                                $winUsedTeachers, $currentWindowTeacherSlots
                            );
                        }

                        if (! $candidateTeacher) {
                            $triedDisciplineIds[] = $candidateDisc->id;

                            continue;
                        }

                        // Подгруппы
                        if ($candidateDisc->requires_subgroup) {
                            $subgroups = $group->subgroups()->where('is_active', true)->get();
                            if ($subgroups->count() > 1) {
                                $subLessons = [];
                                $allSubSucceed = true;
                                $tempTeachers = $winUsedTeachers;
                                $tempSlots = $currentWindowTeacherSlots;

                                foreach ($subgroups as $subgroup) {
                                    $st = $this->pickTeacherForDiscipline(
                                        $candidateDisc->id, $group->id, $date, $slot, $localBuilding,
                                        $tempTeachers, $tempSlots, $subgroup->id
                                    );
                                    $sr = $st ? $this->pickRoomForLesson(
                                        $localBuilding->id, $subgroup->students_count,
                                        $candidateDisc, $date, $slot, $st, $candidateTypeCode
                                    ) : null;

                                    if (! $st || ! $sr) {
                                        $allSubSucceed = false;
                                        break;
                                    }

                                    $subLessons[] = [
                                        'lesson_number' => $slot,
                                        'shift' => $group->shift,
                                        'discipline_id' => $candidateDisc->id,
                                        'teacher_id' => $st->id,
                                        'room_id' => $sr->id,
                                        'building_id' => $sr->building_id,
                                        'subgroup_id' => $subgroup->id,
                                        'lesson_type_id' => $candidateLessonTypeId,
                                        'lesson_type_code' => $candidateTypeCode,
                                    ];
                                    $tempTeachers[$st->id] = ($tempTeachers[$st->id] ?? 0) + 1;
                                    $tempSlots[$st->id][] = $slot;
                                }

                                if ($allSubSucceed) {
                                    $lessonsData[] = ['sub_lessons' => $subLessons];
                                    $winUsedDisciplines[] = $candidateDisc->id;
                                    $winUsedTeachers = $tempTeachers;
                                    $currentWindowTeacherSlots = $tempSlots;

                                    continue 2;
                                } else {
                                    $triedDisciplineIds[] = $candidateDisc->id;

                                    continue;
                                }
                            }
                        }

                        $candidateRoom = $this->pickRoomForLesson(
                            $localBuilding->id, $group->students_count,
                            $candidateDisc, $date, $slot, $candidateTeacher, $candidateTypeCode
                        );
                        if (! $candidateRoom) {
                            // Fallback ищет аудиторию ТОЛЬКО в разрешённых корпусах группы
                            $candidateRoom = $this->pickRoomForLessonFallback(
                                $group->students_count, $candidateDisc, $date, $slot, $candidateTeacher, $candidateTypeCode,
                                $this->allowedBuildingIds($group)
                            );
                        }

                        if (! $candidateRoom) {
                            $triedDisciplineIds[] = $candidateDisc->id;

                            continue;
                        }

                        // Жёсткие авторские правила: не принимаем размещение, нарушающее запрет/привязку.
                        if (! $this->customRules->allowsPlacement([
                            'group_id' => $group->id,
                            'teacher_id' => $candidateTeacher->id,
                            'room_id' => $candidateRoom->id,
                            'building_id' => $candidateRoom->building_id,
                            'room_type_id' => $candidateRoom->room_type_id,
                            'discipline_id' => $candidateDisc->id,
                            'lesson_type' => $candidateTypeCode,
                            'lesson_number' => $slot,
                            'weekday' => $date->dayOfWeekIso,
                            'date' => $date->toDateString(),
                            'course' => $group->current_course,
                            'shift' => $group->shift,
                        ])) {
                            $triedDisciplineIds[] = $candidateDisc->id;

                            continue;
                        }

                        $discipline = $candidateDisc;
                        $teacher = $candidateTeacher;
                        $room = $candidateRoom;
                        $chosenTypeCode = $candidateTypeCode;
                        $localBuilding = Building::find($room->building_id);
                        break;
                    }

                    if (! $discipline || ! $teacher) {
                        $windowSuccess = false;
                        break;
                    }

                    $isExamDiscipline = $this->isExam($discipline, $typeId);
                    $finalTypeId = $isExamDiscipline
                        ? $this->lessonTypeId('exam', $typeId)
                        : $this->getLessonTypeId($chosenTypeCode);

                    $primaryLesson = [
                        'lesson_number' => $slot,
                        'shift' => $group->shift,
                        'discipline_id' => $discipline->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $room->id,
                        'building_id' => $localBuilding->id,
                        'lesson_type_id' => $finalTypeId,
                        'lesson_type_code' => $isExamDiscipline ? 'exam' : $chosenTypeCode,
                        'notes' => $isExamDiscipline ? ($discipline->code ?: null) : null,
                    ];

                    // Параллельные занятия: остальные назначенные преподаватели
                    // ведут пару одновременно, но каждый в своей аудитории.
                    $coTeachers = ($discipline->is_parallel && ! $isExamDiscipline)
                        ? $this->pickParallelCoTeachers($discipline->id, $group->id, $date, $slot, $teacher->id, $localBuilding, $winUsedTeachers, $currentWindowTeacherSlots)
                        : [];

                    if (! empty($coTeachers)) {
                        $bundle = [$primaryLesson];
                        $usedRoomIds = [$room->id];
                        // Группа делится между параллельными преподавателями.
                        $splitCount = (int) ceil($group->students_count / (count($coTeachers) + 1));

                        foreach ($coTeachers as $ct) {
                            $ctRoom = $this->pickRoomForLesson($localBuilding->id, $splitCount, $discipline, $date, $slot, $ct, $chosenTypeCode, $usedRoomIds)
                                ?? $this->pickRoomForLessonFallback($splitCount, $discipline, $date, $slot, $ct, $chosenTypeCode, $this->allowedBuildingIds($group), $usedRoomIds);

                            if (! $ctRoom) {
                                // Нет свободной отдельной аудитории — со-преподаватель не поставлен.
                                $wKey = "{$discipline->id}-{$ct->id}-{$date->format('Y-m-d')}-{$slot}";
                                $this->generationWarnings[$wKey] = "Параллель «{$discipline->name}»: преподаватель {$ct->short_name} не поставлен {$date->format('d.m.Y')}, пара {$slot} — нет свободной аудитории.";

                                continue;
                            }

                            $bundle[] = array_merge($primaryLesson, [
                                'teacher_id' => $ct->id,
                                'room_id' => $ctRoom->id,
                                'building_id' => $ctRoom->building_id,
                            ]);
                            $usedRoomIds[] = $ctRoom->id;
                            $winUsedTeachers[$ct->id] = ($winUsedTeachers[$ct->id] ?? 0) + 1;
                            $currentWindowTeacherSlots[$ct->id][] = $slot;
                        }

                        $lessonsData[] = count($bundle) > 1 ? ['sub_lessons' => $bundle] : $primaryLesson;
                    } else {
                        $lessonsData[] = $primaryLesson;
                    }

                    $winUsedDisciplines[] = $discipline->id;
                    $winUsedTeachers[$teacher->id] = ($winUsedTeachers[$teacher->id] ?? 0) + 1;
                    $currentWindowTeacherSlots[$teacher->id][] = $slot;

                    if ($isExamDiscipline) {
                        // Экзамен — единственная пара дня: коммитим и выходим
                        $usedDisciplines = $winUsedDisciplines;
                        $usedTeachers = $winUsedTeachers;

                        return ['slots' => [$slot], 'lessons' => [end($lessonsData)], 'building' => $localBuilding, 'is_exam' => true];
                    }
                }

                if ($windowSuccess && count($lessonsData) > 0) {
                    // Окно успешно — коммитим использованные дисциплины и преподавателей
                    $usedDisciplines = $winUsedDisciplines;
                    $usedTeachers = $winUsedTeachers;

                    return ['slots' => $windowSlots, 'lessons' => $lessonsData, 'building' => $localBuilding];
                }
            }
        }

        return ['slots' => [], 'lessons' => [], 'building' => $building];
    }

    // ── Физкультура ────────────────────────────────────────────────────────

    /**
     * Авто-физкультура для обычных групп (не ездящих в спорткомплекс):
     * один сдвоенный блок в неделю в спортзале своего корпуса.
     */
    private function shouldGeneratePE(Group $group, Carbon $date): bool
    {
        $peDiscipline = $this->findPEDiscipline($group, $date);

        if (! $peDiscipline) {
            return false;
        }

        // Физкультуру ставим не чаще заданного числа сдвоенных занятий в неделю.
        $maxPerWeek = $this->rules->intParam('pe_block', 'max_per_week', 1, $group);
        if (($this->weekDisciplineCount[$group->id][$peDiscipline->id] ?? 0) >= $maxPerWeek) {
            return false;
        }

        $sessionPlaced = ($this->sessionDisciplineTypeHours[$group->id][$peDiscipline->id]['practice'] ?? 0) * 2;
        $dbRemaining = $this->hoursTracking->getRemainingHours($group, $peDiscipline);

        return max(0, $dbRemaining - $sessionPlaced) >= 4;
    }

    /**
     * Недельное расписание спорткомплекса, сгруппированное по группе.
     * Одна группа может занимать несколько пар. Кэшируется на сеанс генерации.
     *
     * @return Collection<int, Collection<int, SportComplexSlot>>
     */
    private function getSportSchedule(): Collection
    {
        if ($this->sportSchedule === null) {
            $this->sportSchedule = SportComplexSlot::get()->groupBy('group_id');
        }

        return $this->sportSchedule;
    }

    /**
     * Пары, на которые группа едет в спорткомплекс в указанный день недели.
     *
     * @return array<int, int>
     */
    private function getSportPairsForGroupDay(Group $group, Carbon $date): array
    {
        $slots = $this->getSportSchedule()->get($group->id);
        if (! $slots) {
            return [];
        }

        return $slots->where('weekday', (int) $date->format('N'))
            ->pluck('lesson_number')
            ->filter(fn ($n) => $n >= 1 && $n <= self::MAX_PE_PAIR) // физра только 1–4 пары
            ->sort()->values()->all();
    }

    /** Есть ли у группы непоставленные часы физкультуры (минимум на одну пару). */
    private function hasRemainingPE(Group $group, Carbon $date): bool
    {
        $pe = $this->findPEDiscipline($group, $date);
        if (! $pe) {
            return false;
        }

        $placed = ($this->sessionDisciplineTypeHours[$group->id][$pe->id]['practice'] ?? 0) * 2;

        return max(0, $this->hoursTracking->getRemainingHours($group, $pe) - $placed) >= 2;
    }

    private function findPEDiscipline(Group $group, Carbon $date): ?CurriculumDiscipline
    {
        $currentSemester = $group->getCurrentSemester($date);

        return CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('category', 'pe')
            ->where('is_schedulable', true)
            ->first();
    }

    /** Одиночное занятие физкультуры в спорткомплексе на конкретной паре. */
    private function simulateSportPELesson(Group $group, Carbon $date, int $lessonNumber, int $shift, array &$usedTeachers, array $simulatedWindowSlots = []): ?array
    {
        $pe = $this->findPEDiscipline($group, $date);
        if (! $pe) {
            return null;
        }

        $sportRooms = Room::whereHas('roomType', fn ($q) => $q->where('name', 'like', '%Спорт%'))
            ->where('is_active', true)->get()
            ->filter(fn ($r) => $this->isSportComplexRoom($r->id))->values();

        $typeId = $this->lessonTypeId('practice', 2);

        foreach ($sportRooms as $room) {
            $teacher = $this->pickTeacherForDiscipline($pe->id, $group->id, $date, $lessonNumber, $room->building, $usedTeachers, $simulatedWindowSlots);
            if (! $teacher) {
                continue;
            }
            if ($this->conflictChecker->checkRoomConflict($room->id, $date->format('Y-m-d'), $lessonNumber)) {
                continue;
            }

            $usedTeachers[$teacher->id] = ($usedTeachers[$teacher->id] ?? 0) + 1;

            return [
                'lesson_number' => $lessonNumber, 'shift' => $shift, 'discipline_id' => $pe->id,
                'teacher_id' => $teacher->id, 'room_id' => $room->id, 'building_id' => $room->building_id,
                'lesson_type_id' => $typeId, 'lesson_type_code' => 'practice',
            ];
        }

        return null;
    }

    private function simulatePELessons(ScheduleVersion $version, Group $group, Carbon $date, int $startLessonNumber, int $shift, array &$usedTeachers, array $simulatedWindowSlots = []): ?array
    {
        $currentSemester = $group->getCurrentSemester($date);
        $peDiscipline = CurriculumDiscipline::whereHas('curriculumPlan.groupAssignments', fn ($q) => $q->where('group_id', $group->id))
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('category', 'pe')
            ->where('is_schedulable', true)
            ->first();
        if (! $peDiscipline) {
            return null;
        }

        // Обычная группа: только спортзалы обычных корпусов, без спорткомплекса.
        $sportRooms = Room::whereHas('roomType', fn ($q) => $q->where('name', 'like', '%Спорт%'))
            ->where('is_active', true)->get()
            ->reject(fn ($r) => $this->isSportComplexRoom($r->id))->values();

        $typeId = $this->lessonTypeId('practice', 2);

        foreach ($sportRooms as $sportRoom) {
            $teacher = $this->pickTeacherForDiscipline($peDiscipline->id, $group->id, $date, $startLessonNumber, $sportRoom->building, $usedTeachers, $simulatedWindowSlots);
            if (! $teacher || ! $teacher->isAvailableOn($date, $startLessonNumber + 1)) {
                continue;
            }

            if (
                ! $this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber)
                && ! $this->conflictChecker->checkRoomConflict($sportRoom->id, $date->format('Y-m-d'), $startLessonNumber + 1)
            ) {
                $usedTeachers[$teacher->id] = ($usedTeachers[$teacher->id] ?? 0) + 2;

                return [
                    ['lesson_number' => $startLessonNumber, 'shift' => $shift, 'discipline_id' => $peDiscipline->id, 'teacher_id' => $teacher->id, 'room_id' => $sportRoom->id, 'building_id' => $sportRoom->building_id, 'lesson_type_id' => $typeId, 'lesson_type_code' => 'practice'],
                    ['lesson_number' => $startLessonNumber + 1, 'shift' => $shift, 'discipline_id' => $peDiscipline->id, 'teacher_id' => $teacher->id, 'room_id' => $sportRoom->id, 'building_id' => $sportRoom->building_id, 'lesson_type_id' => $typeId, 'lesson_type_code' => 'practice'],
                ];
            }
        }

        return null;
    }

    private function isSportComplexRoom(int $roomId): bool
    {
        $room = Room::find($roomId);

        return $room && $room->roomType && $room->roomType->is_sport_complex;
    }

    // ── Выбор дисциплины ───────────────────────────────────────────────────

    /**
     * Возвращает ['discipline' => CurriculumDiscipline, 'typeCode' => string] или null.
     *
     * Порядок приоритетов:
     * 1. Дисциплины из шаблона недели (templateHint)
     * 2. Малочасовые дисциплины (≤10 ч остатка) — всегда в топ
     * 3. Остальные — по убыванию остатка часов
     * 4. Ограничение: не более 2 раз в неделю
     * 5. Ограничение: минимум 2 дня с последнего занятия
     */
    private function pickDisciplineForGroup(Group $group, array $excludeIds = [], ?Carbon $date = null, ?array $templateHint = null): ?array
    {
        $assignment = $group->getCurriculumAssignmentForDate($date);
        if (! $assignment) {
            return null;
        }

        $currentSemester = $group->getCurrentSemester($date);
        $dayOfWeek = $date ? (int) $date->format('N') : null;
        $groupId = $group->id;

        // Если есть подсказка от шаблона — попробуем её в первую очередь
        if ($templateHint) {
            $hintDisc = CurriculumDiscipline::find($templateHint['discId']);
            if ($hintDisc && ! in_array($hintDisc->id, $excludeIds, true)) {
                $hintType = $this->pickLessonTypeForDiscipline($groupId, $hintDisc->id, $currentSemester);
                if ($hintType) {
                    return ['discipline' => $hintDisc, 'typeCode' => $hintType];
                }
            }
        }

        // ── Жёсткие фильтры ───────────────────────────────────────────────
        // Дисциплина должна иметь преподавателя, работающего в этот день недели —
        // иначе её невозможно поставить и попытки тратятся впустую.
        $query = CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $currentSemester))
            ->where('category', '!=', 'pe')
            ->where('is_schedulable', true);

        $isExamDay = $group->getCalendarBlock($date)?->type === 'exam_session';

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

        $disciplines = $query->get();

        // Экзамены/зачёты/аттестации не ставим в обычные недели — только в дни сессии.
        if (! $isExamDay) {
            $disciplines = $disciplines
                ->reject(fn (CurriculumDiscipline $d) => $d->isExamCategory())
                ->values();
        }

        if ($disciplines->isEmpty()) {
            return null;
        }

        // ── Мягкое ранжирование ───────────────────────────────────────────
        // Частота за неделю и интервал между занятиями — это ПРЕДПОЧТЕНИЯ (штрафы),
        // а не жёсткие фильтры. Иначе в дни с малым числом доступных преподавателей
        // (например суббота) расписание оставалось бы пустым.
        $lowHoursThreshold = $this->rules->intParam('discipline_ranking', 'low_hours_threshold', 10, $group);
        $lowHoursBoost = $this->rules->intParam('discipline_ranking', 'low_hours_boost', 1_000_000, $group);
        $repeatWeekPenalty = $this->rules->intParam('discipline_ranking', 'repeat_week_penalty', 5000, $group);
        $minDaysGap = $this->rules->intParam('discipline_ranking', 'min_days_gap', 2, $group);
        $recentGapPenalty = $this->rules->intParam('discipline_ranking', 'recent_gap_penalty', 10000, $group);
        $topNRandom = max(1, $this->rules->intParam('discipline_ranking', 'top_n_random', 3, $group));

        $ranked = $disciplines->map(function (CurriculumDiscipline $disc) use ($groupId, $currentSemester, $date, $lowHoursThreshold, $lowHoursBoost, $repeatWeekPenalty, $minDaysGap, $recentGapPenalty) {
            $dbRemaining = $this->hoursTracking->getRemainingHoursByIds($groupId, $disc->id, $currentSemester);
            $sessionPlaced = array_sum(array_map(
                fn ($t) => ($this->sessionDisciplineTypeHours[$groupId][$disc->id][$t] ?? 0) * 2,
                ['lecture', 'practice', 'lab']
            ));
            $remaining = max(0, $dbRemaining - $sessionPlaced);

            // Базовый счёт: малочасовые — максимальный приоритет
            $score = $remaining <= $lowHoursThreshold && $remaining > 0 ? $lowHoursBoost : $remaining;

            // Штраф за повтор на этой неделе
            $weekCount = $this->weekDisciplineCount[$groupId][$disc->id] ?? 0;
            $score -= $weekCount * $repeatWeekPenalty;

            // Штраф, если дисциплину ставили недавно
            $lastDateStr = $this->disciplineLastDate[$groupId][$disc->id] ?? null;
            if ($lastDateStr && $date && Carbon::parse($lastDateStr)->diffInDays($date) < $minDaysGap) {
                $score -= $recentGapPenalty;
            }

            return ['disc' => $disc, 'remaining' => $remaining, 'score' => $score];
        })->filter(fn ($item) => $item['remaining'] > 0)
            ->sortByDesc('score')
            ->values();

        if ($ranked->isEmpty()) {
            // Часы кончились у всех — берём любую доступную (с преподавателем на этот день)
            $best = $disciplines->random();

            return ['discipline' => $best, 'typeCode' => $this->pickLessonTypeForDiscipline($groupId, $best->id, $currentSemester) ?? 'lecture'];
        }

        // Топ-N с небольшой случайностью, чтобы расписание не было детерминированным
        $picked = $ranked->take($topNRandom)->random();
        $typeCode = $this->pickLessonTypeForDiscipline($groupId, $picked['disc']->id, $currentSemester) ?? 'lecture';

        return ['discipline' => $picked['disc'], 'typeCode' => $typeCode];
    }

    /**
     * Определяет тип занятия для дисциплины исходя из остатка часов по типам.
     * Порядок приоритета: лекции → практики → лабораторные.
     */
    private function pickLessonTypeForDiscipline(int $groupId, int $disciplineId, int $semesterNum): ?string
    {
        $sem = CurriculumSemester::where('discipline_id', $disciplineId)
            ->where('semester_number', $semesterNum)
            ->first();

        if (! $sem) {
            return 'lecture';
        }

        $order = $this->rules->arrayParam('lesson_type_order', 'order', ['lecture', 'practice', 'lab']);
        foreach ($order as $typeCode) {
            $total = match ($typeCode) {
                'practice' => $sem->hours_practice ?? 0,
                'lab' => $sem->hours_lab ?? 0,
                default => $sem->hours_lecture ?? 0,
            };

            if ($total <= 0) {
                continue;
            }

            $dbRemaining = $this->hoursTracking->getRemainingHoursByType($groupId, $disciplineId, $semesterNum, $typeCode);
            $sessionPlaced = ($this->sessionDisciplineTypeHours[$groupId][$disciplineId][$typeCode] ?? 0) * 2;

            if (max(0, $dbRemaining - $sessionPlaced) > 0) {
                return $typeCode;
            }
        }

        // Все часы исчерпаны — вернём лекцию (дисциплина будет отфильтрована позже)
        return null;
    }

    private function getLessonTypeId(string $typeCode): int
    {
        return $this->lessonTypeId($typeCode)
            ?? $this->lessonTypeId('lecture')
            ?? 1;
    }

    // ── Выбор преподавателя ────────────────────────────────────────────────

    private function pickTeacherForDiscipline(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, Building $targetBuilding, array $simulatedTeacherLoads = [], array $simulatedWindowSlotsMap = [], ?int $subgroupId = null, bool $enforceBuilding = true): ?Teacher
    {
        $currentSemesterNum = Group::find($groupId)?->getCurrentSemester($date);
        $dateStr = $date->format('Y-m-d');

        $assignments = TeacherDisciplineSemester::whereHas('teacherDiscipline', function ($q) use ($disciplineId, $groupId, $subgroupId) {
            $q->where('discipline_id', $disciplineId)
                ->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id'))
                ->when($subgroupId, fn ($q3) => $q3->where('subgroup_id', $subgroupId));
        })->whereHas('curriculumSemester', fn ($q) => $q->where('semester_number', $currentSemesterNum))
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        // Преподаватели идут по sort_order — приоритетный первым.
        // Если приоритетный не может взять конкретный слот (не работает в этот день,
        // конфликт, окно, другой корпус, перегрузка) — пробуем следующего по очереди,
        // а не бросаем дисциплину целиком. Это позволяет заполнять дни (например субботу),
        // когда основной преподаватель не работает.
        foreach ($assignments as $assignment) {
            $teacher = $assignment->teacherDiscipline->teacher;

            // У преподавателя должны оставаться часы по дисциплине
            if ($this->hoursTracking->getTeacherRemainingHoursForDiscipline($teacher, $groupId, $disciplineId, $currentSemesterNum) <= 0) {
                continue;
            }

            if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                continue;
            }
            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                continue;
            }

            $teacherSimSlots = $simulatedWindowSlotsMap[$teacher->id] ?? [];
            if (! $this->canAssignToTeacherWithoutWindow($teacher->id, $dateStr, $lessonNumber, $teacherSimSlots)) {
                continue;
            }

            $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$dateStr] ?? null;
            if ($teacherDayBuildingId && $teacherDayBuildingId !== $targetBuilding->id) {
                continue;
            }

            // Преподаватель должен работать в этом корпусе (если корпуса заданы).
            if ($enforceBuilding) {
                $allowed = $this->getTeacherBuildingIds($teacher->id);
                if (! empty($allowed) && ! in_array($targetBuilding->id, $allowed, true)) {
                    continue;
                }
            }

            $dbLoad = count($this->teacherDayLessons[$teacher->id][$dateStr] ?? []);
            $simLoad = $simulatedTeacherLoads[$teacher->id] ?? 0;
            $windowLoad = count($teacherSimSlots);
            if (($dbLoad + $simLoad + $windowLoad) >= ($teacher->max_lessons_per_day ?? 5)) {
                continue;
            }

            return $teacher;
        }

        return null;
    }

    private function pickTeacherForDisciplineFallback(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, array $simulatedTeacherLoads = [], array $simulatedWindowSlotsMap = []): ?Teacher
    {
        // Последний шанс: корпус подбирается отдельно, поэтому привязку к корпусам не проверяем.
        return $this->pickTeacherForDiscipline(
            $disciplineId, $groupId, $date, $lessonNumber,
            Building::where('is_active', true)->first(),
            $simulatedTeacherLoads, $simulatedWindowSlotsMap,
            enforceBuilding: false,
        );
    }

    /**
     * Параллельные занятия: помимо основного преподавателя, возвращает остальных
     * назначенных на дисциплину преподавателей, свободных на этом слоте. Они ведут
     * пару одновременно, каждый в своей аудитории (не делят часы — каждому полная нагрузка).
     *
     * @return array<int, Teacher>
     */
    private function pickParallelCoTeachers(int $disciplineId, int $groupId, Carbon $date, int $lessonNumber, int $primaryTeacherId, Building $building, array $simulatedTeacherLoads, array $simulatedWindowSlotsMap): array
    {
        $currentSemesterNum = Group::find($groupId)?->getCurrentSemester($date);
        $dateStr = $date->format('Y-m-d');

        $assignments = TeacherDisciplineSemester::whereHas('teacherDiscipline', function ($q) use ($disciplineId, $groupId) {
            $q->where('discipline_id', $disciplineId)
                ->where(fn ($q2) => $q2->where('group_id', $groupId)->orWhereNull('group_id'));
        })->whereHas('curriculumSemester', fn ($q) => $q->where('semester_number', $currentSemesterNum))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with('teacherDiscipline.teacher')
            ->get();

        $result = [];
        $seen = [$primaryTeacherId => true];

        foreach ($assignments as $assignment) {
            $teacher = $assignment->teacherDiscipline->teacher;
            if (! $teacher || isset($seen[$teacher->id])) {
                continue;
            }
            $seen[$teacher->id] = true;

            if (! $teacher->isAvailableOn($date, $lessonNumber)) {
                continue;
            }
            if ($this->conflictChecker->checkTeacherConflict($teacher->id, $dateStr, $lessonNumber)) {
                continue;
            }
            // Корпус дня преподавателя должен совпадать (параллель — в одном корпусе).
            $teacherDayBuildingId = $this->teacherDayBuildings[$teacher->id][$dateStr] ?? null;
            if ($teacherDayBuildingId && $teacherDayBuildingId !== $building->id) {
                continue;
            }
            $load = count($this->teacherDayLessons[$teacher->id][$dateStr] ?? [])
                + ($simulatedTeacherLoads[$teacher->id] ?? 0)
                + count($simulatedWindowSlotsMap[$teacher->id] ?? []);
            if ($load >= ($teacher->max_lessons_per_day ?? 5)) {
                continue;
            }

            $result[] = $teacher;
        }

        return $result;
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

    private function pickRoomForLesson(int $buildingId, int $studentsCount, CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, ?Teacher $teacher, string $typeCode = 'lecture', array $excludeRoomIds = []): ?Room
    {
        $query = Room::where('building_id', $buildingId)
            ->where('is_active', true)
            ->where('is_available_for_booking', true)
            ->where('capacity', '>=', $studentsCount)
            ->when($excludeRoomIds, fn ($q) => $q->whereNotIn('id', $excludeRoomIds));

        // Лабораторные → аудитория с лабораторным оборудованием
        if ($discipline->requires_lab || $typeCode === 'lab') {
            $query->whereHas('roomType', fn ($q) => $q->where('requires_lab', true));
        }

        if ($teacher) {
            $personalRoomIds = $teacher->rooms()->pluck('room_id')->toArray();
            if (! empty($personalRoomIds)) {
                $caseWhen = 'CASE id';
                foreach ($personalRoomIds as $idx => $roomId) {
                    $caseWhen .= " WHEN {$roomId} THEN ".(count($personalRoomIds) - $idx);
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

    private function pickRoomForLessonFallback(int $studentsCount, CurriculumDiscipline $discipline, Carbon $date, int $lessonNumber, ?Teacher $teacher, string $typeCode = 'lecture', array $allowedBuildingIds = [], array $excludeRoomIds = []): ?Room
    {
        $query = Room::where('is_active', true)
            ->where('is_available_for_booking', true)
            ->where('capacity', '>=', $studentsCount)
            ->when($excludeRoomIds, fn ($q) => $q->whereNotIn('id', $excludeRoomIds));

        // Не выходим за пределы разрешённых корпусов группы
        if (! empty($allowedBuildingIds)) {
            $query->whereIn('building_id', $allowedBuildingIds);
        }

        if ($discipline->requires_lab || $typeCode === 'lab') {
            $query->whereHas('roomType', fn ($q) => $q->where('requires_lab', true));
        }

        foreach ($query->get() as $room) {
            if (! $this->conflictChecker->checkRoomConflict($room->id, $date->format('Y-m-d'), $lessonNumber)) {
                return $room;
            }
        }

        return null;
    }

    /**
     * Разрешённые группе корпуса (из group_buildings, primary первым).
     * Если не заданы — все активные корпуса, кроме спортивных (они для физкультуры).
     *
     * @return array<int>
     */
    private function allowedBuildingIds(Group $group): array
    {
        $ids = GroupBuilding::where('group_id', $group->id)
            ->orderBy('is_primary', 'desc')
            ->pluck('building_id')
            ->all();

        if (! empty($ids)) {
            return $ids;
        }

        return Building::where('is_active', true)
            ->where('name', 'not like', '%спорт%')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * Определяет корпус для группы на конкретную дату.
     * Порядок:
     *   1) GroupDayBuilding (назначение на конкретную дату вручную/авто),
     *   2) из разрешённых корпусов группы — если их несколько, распределяем по дням недели
     *      (один корпус на день, неделя за неделей одинаково),
     *   3) первый активный корпус.
     */
    private function pickBuildingForGroupDay(Group $group, Carbon $date): Building
    {
        $dayBuilding = GroupDayBuilding::where('group_id', $group->id)
            ->where('date', $date->toDateString())
            ->first();
        if ($dayBuilding && $dayBuilding->building_id) {
            return Building::find($dayBuilding->building_id) ?? Building::where('is_active', true)->first();
        }

        $ids = $this->allowedBuildingIds($group);
        if (empty($ids)) {
            return Building::where('is_active', true)->first();
        }

        // Один корпус — всегда он. Несколько — по стратегии: чередуем по дню недели
        // или всегда основной (primary_only).
        $strategy = $this->rules->param('building_rotation', 'strategy', 'by_weekday', $group);
        $idx = (count($ids) === 1 || $strategy === 'primary_only')
            ? 0
            : (((int) $date->format('N') - 1) % count($ids));

        return Building::find($ids[$idx])
            ?? Building::find($ids[0])
            ?? Building::where('is_active', true)->first();
    }

    // ── Вспомогательные ────────────────────────────────────────────────────

    private function isExam(CurriculumDiscipline $discipline, int $typeId): bool
    {
        $code = $this->lessonTypeCodesById[$typeId] ?? null;
        if ($code && in_array($code, ['exam', 'test', 'diff_test'], true)) {
            return true;
        }

        return $discipline->isExamCategory();
    }

    private function getPracticeDisciplineId(Group $group, ?Carbon $date = null): ?int
    {
        $assignment = $group->getCurriculumAssignmentForDate($date);
        if (! $assignment) {
            return null;
        }

        return CurriculumDiscipline::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('category', 'practice')
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
