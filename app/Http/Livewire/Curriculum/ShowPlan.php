<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\AcademicYear;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumPractice;
use App\Models\CurriculumSemester;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShowPlan extends Component
{
    public CurriculumPlan $plan;

    public string $disciplineSearch = '';

    public ?int $courseFilter = null;

    public ?int $semesterFilter = null;

    public ?int $teacherFilter = null;

    public int $activeCourse = 1;

    public int $activeSemesterInCourse = 1;

    public string $activeTab = 'plan';

    public int $assignDisciplineId = 0;

    public string $assignDisciplineName = '';

    public bool $showAssignModal = false;

    public bool $showWorkloadModal = false;

    public array $workloadState = [];

    public bool $disciplineParallel = false;

    public string $disciplineCategory = 'general';

    public ?int $activeSemesterId = null;

    public string $teacherSearch = '';

    public bool $editingPlanName = false;

    public string $editedPlanName = '';

    public bool $showExamModal = false;

    public ?int $examCourse = null;

    public ?int $examSemester = null;

    public ?string $examStartDate = null;

    public ?string $examEndDate = null;

    public bool $showPracticeTeacherModal = false;

    public ?int $selectedPracticeId = null;

    public ?int $practiceTeacherId = null;

    // ── Вкладка «Экзамены»: назначение точных дат ──────────────────────────
    public int $examTabCourse = 1;

    /** Черновик дат экзаменов по curriculum_semester_id: [semId => 'Y-m-d'] */
    public array $examDates = [];

    /** Черновик аудиторий: [semId => roomId] */
    public array $examRooms = [];

    public function mount(CurriculumPlan $plan): void
    {
        $this->loadPlanData($plan);
        if ($this->plan->total_hours === 0) {
            $total = CurriculumSemester::whereHas('discipline', fn ($q) => $q->where('curriculum_plan_id', $this->plan->id))->sum('hours_total');
            if ($total > 0) {
                $this->plan->update(['total_hours' => $total]);
            }
        }

        $firstAvailableCourse = $this->availableCourses->first();
        if ($firstAvailableCourse) {
            $this->activeCourse = $firstAvailableCourse;
            $this->activeSemesterInCourse = 1;
            $this->courseFilter = $this->activeCourse;
            $this->semesterFilter = $this->getFirstSemesterForCourse($this->activeCourse);
        }
    }

    public function loadPlanData(CurriculumPlan $plan): void
    {
        $this->plan = $plan->load([
            'specialty',
            'academicYear',
            'disciplines.semesters.controlForm',
            'practices.teacher',
        ]);
        $this->editedPlanName = $this->plan->name;
    }

    public function editPlanName(): void
    {
        $this->editingPlanName = true;
    }

    public function savePlanName(): void
    {
        $this->validate([
            'editedPlanName' => 'required|string|max:255',
        ]);

        $this->plan->update(['name' => $this->editedPlanName]);
        $this->editingPlanName = false;
        session()->flash('message', 'Название плана обновлено.');
    }

    public function cancelEditPlanName(): void
    {
        $this->editingPlanName = false;
        $this->editedPlanName = $this->plan->name;
    }

    public function openWorkloadManager(int $disciplineId, string $disciplineName): void
    {
        $this->assignDisciplineId = $disciplineId;
        $this->assignDisciplineName = $disciplineName;
        $this->showWorkloadModal = true;

        $disciplines = $this->plan->disciplines()->find($disciplineId);
        $this->disciplineParallel = (bool) $disciplines?->is_parallel;
        $this->disciplineCategory = $disciplines?->category ?? 'general';
        $this->workloadState = [];

        foreach ($disciplines->semesters as $sem) {
            $this->workloadState[$sem->id] = TeacherDisciplineSemester::where('curriculum_semester_id', $sem->id)
                ->whereHas('teacherDiscipline', fn ($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
                ->with('teacherDiscipline.teacher')
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'teacher_id' => $a->teacherDiscipline->teacher_id,
                    'teacher_name' => $a->teacherDiscipline->teacher->short_name,
                    'hours' => $a->planned_hours,
                    'teacher_discipline_id' => $a->teacher_discipline_id,
                ])->toArray();
        }

        if ($disciplines->semesters->isNotEmpty()) {
            $this->activeSemesterId = $disciplines->semesters->sortBy('semester_number')->first()->id;
        }
    }

    public function selectSemester(int $semesterId): void
    {
        $this->activeSemesterId = $semesterId;
    }

    /**
     * Параллельные занятия: преподаватели ведут дисциплину одновременно,
     * не деля часы (ин.язык по подгруппам, УП и т.п.).
     */
    public function updatedDisciplineParallel(bool $value): void
    {
        if ($this->assignDisciplineId) {
            CurriculumDiscipline::whereKey($this->assignDisciplineId)->update(['is_parallel' => $value]);
        }
    }

    /**
     * Категория дисциплины для генератора (физкультура / практика / экзамен / обычная).
     */
    public function updatedDisciplineCategory(string $value): void
    {
        if ($this->assignDisciplineId && in_array($value, ['general', 'pe', 'practice', 'exam'], true)) {
            CurriculumDiscipline::whereKey($this->assignDisciplineId)->update(['category' => $value]);
        }
    }

    public function openAssignModal(): void
    {
        $this->showAssignModal = true;
    }

    public function selectAndAssign(int $teacherId): void
    {
        if ($this->showWorkloadModal) {
            $this->addTeacherToSemester($teacherId);
        } else {
            if (! $this->assignDisciplineId) {
                session()->flash('error', 'Дисциплина не выбрана');

                return;
            }

            $existingCount = TeacherDiscipline::where('discipline_id', $this->assignDisciplineId)
                ->where('academic_year_id', $this->plan->academic_year_id)
                ->count();

            if ($existingCount === 0) {
                $td = TeacherDiscipline::create([
                    'teacher_id' => $teacherId,
                    'discipline_id' => $this->assignDisciplineId,
                    'academic_year_id' => $this->plan->academic_year_id,
                    'planned_hours' => 0,
                    'is_primary' => true,
                ]);

                $semesters = CurriculumSemester::where('discipline_id', $this->assignDisciplineId)->get();
                foreach ($semesters as $semester) {
                    TeacherDisciplineSemester::create([
                        'teacher_discipline_id' => $td->id,
                        'curriculum_semester_id' => $semester->id,
                        'planned_hours' => $semester->hours_total,
                        'sort_order' => 1,
                    ]);
                }
            }
            $this->showAssignModal = false;
            $this->loadPlanData($this->plan);
        }
    }

    public function addTeacherToSemester(int $teacherId): void
    {
        $teacher = Teacher::find($teacherId);
        if (! $teacher) {
            return;
        }

        // Check if already in this semester's state
        $exists = collect($this->workloadState[$this->activeSemesterId] ?? [])
            ->contains('teacher_id', $teacherId);

        if ($exists) {
            $this->showAssignModal = false;

            return;
        }

        // Get or create TeacherDiscipline for this year/discipline
        $td = TeacherDiscipline::firstOrCreate([
            'teacher_id' => $teacherId,
            'discipline_id' => $this->assignDisciplineId,
            'academic_year_id' => $this->plan->academic_year_id,
        ], [
            'planned_hours' => 0,
            'is_primary' => false,
        ]);

        $this->workloadState[$this->activeSemesterId][] = [
            'id' => null, // New
            'teacher_id' => $teacherId,
            'teacher_name' => $teacher->short_name,
            'hours' => 0,
            'teacher_discipline_id' => $td->id,
        ];

        $this->showAssignModal = false;
    }

    public function removeTeacherFromSemester(int $semesterId, int $index): void
    {
        if (isset($this->workloadState[$semesterId][$index])) {
            array_splice($this->workloadState[$semesterId], $index, 1);
        }
    }

    public function updateSortOrder(int $semesterId, array $indices): void
    {
        $newState = [];
        foreach ($indices as $idx) {
            $newState[] = $this->workloadState[$semesterId][$idx];
        }
        $this->workloadState[$semesterId] = $newState;
    }

    public function saveWorkload(): void
    {
        foreach ($this->workloadState as $semId => $assignments) {
            // Delete current assignments for this semester that are NOT in the new state
            $keepTdIds = collect($assignments)->pluck('teacher_discipline_id')->filter()->toArray();

            TeacherDisciplineSemester::where('curriculum_semester_id', $semId)
                ->whereHas('teacherDiscipline', fn ($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
                ->whereNotIn('teacher_discipline_id', $keepTdIds)
                ->delete();

            foreach ($assignments as $idx => $data) {
                TeacherDisciplineSemester::updateOrCreate([
                    'curriculum_semester_id' => $semId,
                    'teacher_discipline_id' => $data['teacher_discipline_id'],
                ], [
                    'planned_hours' => (int) $data['hours'],
                    'sort_order' => $idx + 1,
                ]);
            }
        }

        $this->showWorkloadModal = false;
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Нагрузка успешно обновлена.');
    }

    public function closeAssignModal(): void
    {
        $this->showWorkloadModal = false;
    }

    public function removeAssignment(int $teacherDisciplineId): void
    {
        TeacherDisciplineSemester::where('teacher_discipline_id', $teacherDisciplineId)->delete();
        TeacherDiscipline::where('id', $teacherDisciplineId)->delete();
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Назначение удалено.');
    }

    public function moveTeacherInSemester(int $semesterId, int $assignmentId, string $direction): void
    {
        $assignments = TeacherDisciplineSemester::where('curriculum_semester_id', $semesterId)
            ->whereHas('teacherDiscipline', fn ($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Normalize sort_orders to ensure they are sequential
        foreach ($assignments as $i => $a) {
            $a->update(['sort_order' => $i + 1]);
        }

        $assignments = $assignments->sortBy('sort_order')->values();
        $index = $assignments->search(fn ($a) => $a->id === $assignmentId);

        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $assignments->count()) {
            return;
        }

        $assignments[$index]->update(['sort_order' => $swapIndex + 1]);
        $assignments[$swapIndex]->update(['sort_order' => $index + 1]);

        $this->loadPlanData($this->plan);
    }

    public function setActiveCourse(int $course): void
    {
        $this->activeCourse = $course;
        $this->activeSemesterInCourse = 1;
        $this->courseFilter = $course;
        $this->semesterFilter = $this->getFirstSemesterForCourse($course);
    }

    public function setActiveSemester(int $semesterInCourse): void
    {
        $this->activeSemesterInCourse = $semesterInCourse;
        $this->semesterFilter = $this->getSemesterNumber($this->activeCourse, $semesterInCourse);
    }

    private function getFirstSemesterForCourse(int $course): int
    {
        return ($course - 1) * 2 + 1;
    }

    private function getSemesterNumber(int $course, int $semesterInCourse): int
    {
        return ($course - 1) * 2 + $semesterInCourse;
    }

    public function openExamModal(int $course, int $semester): void
    {
        $this->examCourse = $course;
        $this->examSemester = $semester;

        $existing = CurriculumPractice::where('curriculum_plan_id', $this->plan->id)
            ->where('course_number', $course)
            ->where('symbol', 'Э')
            ->where('type', 'exam_session')
            ->first();

        $this->examStartDate = $existing?->start_date?->format('Y-m-d');
        $this->examEndDate = $existing?->end_date?->format('Y-m-d');
        $this->showExamModal = true;
    }

    public function saveExamDates(): void
    {
        $this->validate([
            'examStartDate' => 'required|date',
            'examEndDate' => 'required|date|after_or_equal:examStartDate',
        ]);

        CurriculumPractice::updateOrCreate([
            'curriculum_plan_id' => $this->plan->id,
            'course_number' => $this->examCourse,
            'symbol' => 'Э',
            'type' => 'exam_session',
        ], [
            'start_date' => $this->examStartDate,
            'end_date' => $this->examEndDate,
        ]);

        $this->showExamModal = false;
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Даты сессии успешно сохранены.');
    }

    public function openPracticeTeacherModal(int $practiceId): void
    {
        $this->selectedPracticeId = $practiceId;
        $practice = CurriculumPractice::find($practiceId);
        $this->practiceTeacherId = $practice?->teacher_id;
        $this->showPracticeTeacherModal = true;
    }

    public function savePracticeTeacher(): void
    {
        $this->validate([
            'practiceTeacherId' => 'nullable|integer|exists:teachers,id',
        ]);

        CurriculumPractice::where('id', $this->selectedPracticeId)
            ->update(['teacher_id' => $this->practiceTeacherId]);

        $this->showPracticeTeacherModal = false;
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Преподаватель на практику назначен.');
    }

    public function isNonSchedulable(bool $isSchedulable): bool
    {
        return ! $isSchedulable;
    }

    // ── Вкладка «Экзамены» ─────────────────────────────────────────────────

    /**
     * Список экзаменов плана: дисциплины с exam_hours > 0, сгруппированные по семестрам.
     * Для каждой подтягивается уже назначенная дата/аудитория из exam_schedules.
     *
     * @return array<int, array{semester:int, course:int, items:array}>
     */
    #[Computed]
    public function examScheduleRows(): array
    {
        $semesterIds = $this->plan->disciplines->flatMap->semesters
            ->where('exam_hours', '>', 0)
            ->pluck('id');

        $scheduled = ExamSchedule::whereIn('curriculum_semester_id', $semesterIds)
            ->whereNull('group_id')
            ->get()
            ->keyBy('curriculum_semester_id');

        // Импортированные блоки экзаменационных сессий — задают допустимый диапазон дат
        $examSessions = $this->plan->practices->where('type', 'exam_session');

        $rows = [];
        foreach ($this->plan->disciplines as $discipline) {
            // Экзамен модуля (ПМ): квалификационный/демонстрационный/«по модулю».
            // Ставится ПОСЛЕ всех МДК, УП и ПП модуля.
            $isModuleExam = $discipline->isModuleExam();

            // Пропускаем разделы-заголовки и контейнеры (циклы, модули ПМ.xx, ГИА).
            if ($discipline->isSectionHeader()) {
                continue;
            }

            foreach ($discipline->semesters as $sem) {
                if ($sem->exam_hours <= 0) {
                    continue;
                }

                $sched = $scheduled->get($sem->id);

                $prereqDate = null;
                if ($isModuleExam) {
                    $window = $this->moduleExamWindow($sem->course_number, $sem->semester_number);
                    $minDate = $window['min'];
                    $maxDate = $window['max'];
                    $hasWindow = $window['has'];
                    $prereqDate = $window['prereq'];
                } else {
                    $session = $this->examSessionFor($examSessions, $sem->course_number, $sem->semester_number);
                    $minDate = $session?->start_date?->format('Y-m-d');
                    $maxDate = $session?->end_date?->format('Y-m-d');
                    $hasWindow = $session !== null;
                }

                $rows[] = [
                    'semester_id' => $sem->id,
                    'course' => $sem->course_number,
                    'semester' => $sem->semester_number,
                    'discipline_name' => ($discipline->code ? $discipline->code.' ' : '').$discipline->name,
                    'is_module' => $isModuleExam,
                    'exam_date' => $this->examDates[$sem->id] ?? $sched?->exam_date?->format('Y-m-d') ?? '',
                    'room_id' => $this->examRooms[$sem->id] ?? $sched?->room_id ?? '',
                    'saved' => $sched !== null && $sched->exam_date !== null,
                    'saved_date' => $sched?->exam_date?->format('d.m.Y'),
                    'min_date' => $minDate,
                    'max_date' => $maxDate,
                    'has_session' => $hasWindow,
                    'prereq_date' => $prereqDate,
                ];
            }
        }

        // Сортировка по курсу, семестру, названию
        usort($rows, function ($a, $b) {
            return [$a['course'], $a['semester'], $a['discipline_name']]
                <=> [$b['course'], $b['semester'], $b['discipline_name']];
        });

        return $rows;
    }

    /**
     * Подбирает блок экзаменационной сессии для курса/семестра.
     * Нечётный семестр → зимняя сессия (ноя–фев), чётный → летняя (май–июль).
     */
    private function examSessionFor(Collection $examSessions, int $course, int $semester): ?CurriculumPractice
    {
        $courseSessions = $examSessions->where('course_number', $course);
        if ($courseSessions->isEmpty()) {
            return null;
        }

        $isOdd = $semester % 2 === 1;
        foreach ($courseSessions as $s) {
            $month = (int) Carbon::parse($s->start_date)->format('n');
            $isWinter = in_array($month, [11, 12, 1, 2], true);
            if ($isOdd === $isWinter) {
                return $s;
            }
        }

        return $courseSessions->first();
    }

    /**
     * Окно дат для экзамена модуля (ПМ): пользователь выбирает дату в пределах
     * полугодия. Дополнительно возвращается дата завершения практик (УП/ПП) —
     * по ней в интерфейсе показывается индикатор, пройдены ли МДК, УП и ПП.
     *
     * @return array{min: ?string, max: ?string, has: bool, prereq: ?string}
     */
    private function moduleExamWindow(int $course, int $semester): array
    {
        $entryYear = $this->deriveEntryYear();
        $courseStartYear = $entryYear + $course - 1;
        $isOdd = $semester % 2 === 1;

        // Границы полугодия
        if ($isOdd) {
            $halfStart = Carbon::create($courseStartYear, 9, 1);
            $halfEnd = Carbon::create($courseStartYear + 1, 1, 31)->endOfDay();
        } else {
            $halfStart = Carbon::create($courseStartYear + 1, 2, 1);
            $halfEnd = Carbon::create($courseStartYear + 1, 8, 31)->endOfDay();
        }

        // Практики (учебная/производственная, включая ПД/ГП/ДП) этого курса в полугодии
        $practices = $this->plan->practices
            ->where('course_number', $course)
            ->whereIn('type', ['edu_practice', 'prod_practice'])
            ->filter(function ($p) use ($halfStart, $halfEnd) {
                $end = Carbon::parse($p->end_date);

                return $end->betweenIncluded($halfStart, $halfEnd);
            });

        $prereq = $practices->isEmpty()
            ? null
            : $practices->map(fn ($p) => Carbon::parse($p->end_date))->max()->format('Y-m-d');

        // Окно — всё полугодие: дату выбирает пользователь сам
        return [
            'min' => $halfStart->format('Y-m-d'),
            'max' => $halfEnd->format('Y-m-d'),
            'has' => true,
            'prereq' => $prereq,
        ];
    }

    /**
     * Курсы, по которым есть экзамены (для фильтра вкладки).
     *
     * @return array<int>
     */
    #[Computed]
    public function examCourses(): array
    {
        return $this->plan->disciplines->flatMap->semesters
            ->where('exam_hours', '>', 0)
            ->pluck('course_number')
            ->unique()->sort()->values()->toArray();
    }

    public function saveExamSchedule(int $semesterId): void
    {
        $date = $this->examDates[$semesterId] ?? null;
        $roomId = $this->examRooms[$semesterId] ?? null;

        if (! $date) {
            session()->flash('error', 'Укажите дату экзамена.');

            return;
        }

        // Запрет двух экзаменов в один день у одного курса/семестра этого плана.
        $sem = CurriculumSemester::find($semesterId);
        if ($sem) {
            $siblingIds = CurriculumSemester::where('course_number', $sem->course_number)
                ->where('semester_number', $sem->semester_number)
                ->whereHas('discipline', fn ($q) => $q->where('curriculum_plan_id', $this->plan->id))
                ->where('id', '!=', $semesterId)
                ->pluck('id');

            $clash = ExamSchedule::whereIn('curriculum_semester_id', $siblingIds)
                ->whereNull('group_id')
                ->whereDate('exam_date', $date)
                ->with('curriculumSemester.discipline')
                ->first();

            if ($clash) {
                $name = $clash->curriculumSemester?->discipline?->name ?? 'другая дисциплина';
                session()->flash('error', "На {$date} уже назначен экзамен: «{$name}». Выберите другой день.");

                return;
            }
        }

        ExamSchedule::updateOrCreate(
            ['curriculum_semester_id' => $semesterId, 'group_id' => null],
            ['exam_date' => $date, 'room_id' => $roomId ?: null],
        );

        unset($this->examScheduleRows);
        session()->flash('message', 'Дата экзамена сохранена.');
    }

    public function clearExamSchedule(int $semesterId): void
    {
        ExamSchedule::where('curriculum_semester_id', $semesterId)
            ->whereNull('group_id')
            ->delete();

        unset($this->examDates[$semesterId], $this->examRooms[$semesterId]);
        unset($this->examScheduleRows);
        session()->flash('message', 'Дата экзамена удалена.');
    }

    #[Computed]
    public function examRoomOptions(): array
    {
        return Room::with('building')
            ->where('is_active', true)
            ->orderBy('number')
            ->get()
            ->sortBy(fn ($r) => ($r->building?->short_name ?? $r->building?->name ?? 'я').' '.$r->number)
            ->map(function ($r) {
                $building = $r->building?->short_name ?? $r->building?->name;

                return [
                    'id' => $r->id,
                    'label' => $r->number.($building ? ' — '.$building : ''),
                ];
            })
            ->values()
            ->toArray();
    }

    private function deriveEntryYear(): int
    {
        $practices = $this->plan->practices->filter(fn ($p) => $p->type !== 'exam_session');

        foreach ($practices->sortBy('course_number') as $practice) {
            $course = $practice->course_number;
            $month = (int) $practice->start_date->format('n');
            $year = (int) $practice->start_date->format('Y');
            $courseSepYear = $month >= 9 ? $year : $year - 1;

            return $courseSepYear - ($course - 1);
        }

        return $this->plan->academicYear?->year_start ?? (int) date('Y');
    }

    private function russianMonthName(int $month): string
    {
        return match ($month) {
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
            default => '',
        };
    }

    #[Computed]
    public function calendarGrid(): array
    {
        $entryYear = $this->deriveEntryYear();
        $courses = $this->availableCourses;
        $totalWeeks = 52;

        // Недели — 7-дневные отрезки от 1 сентября, как в Excel-графике (1-7, 8-14, 29-5)
        $baseDate = Carbon::create($entryYear, 9, 1);
        $weeks = [];
        $months = [];

        for ($w = 1; $w <= $totalWeeks; $w++) {
            $weekStart = $baseDate->copy()->addWeeks($w - 1);
            $weekEnd = $weekStart->copy()->addDays(6);
            $monthNum = (int) $weekEnd->format('n');
            $monthName = $this->russianMonthName($monthNum);

            $weeks[$w] = [
                'label' => $weekStart->format('j').'–'.$weekEnd->format('j'),
                'month_num' => $monthNum,
                'month_name' => $monthName,
            ];

            if (! isset($months[$monthName])) {
                $months[$monthName] = ['name' => $monthName, 'count' => 0, 'num' => $monthNum];
            }
            $months[$monthName]['count']++;
        }

        // Load all practices and group by course
        $practices = $this->plan->practices;

        // Load vacations for each course's academic year
        $allAcademicYears = AcademicYear::with('vacations')->get()->keyBy('year_start');

        $grid = [];
        foreach ($courses as $course) {
            $courseYear = $entryYear + $course - 1;
            $courseStart = Carbon::create($courseYear, 9, 1);

            $coursePractices = $practices->where('course_number', $course)
                ->filter(fn ($p) => $p->type !== 'exam_session');
            $examSessions = $practices->where('course_number', $course)
                ->where('type', 'exam_session');

            $courseAcademicYear = $allAcademicYears->get($courseYear);
            $courseVacations = $courseAcademicYear?->vacations ?? collect();

            $grid[$course] = [];
            for ($w = 1; $w <= $totalWeeks; $w++) {
                $weekStart = $courseStart->copy()->addWeeks($w - 1);
                $weekEnd = $weekStart->copy()->addDays(6);

                $symbol = null;
                $type = null;

                foreach ($coursePractices as $practice) {
                    if ($practice->start_date <= $weekEnd && $practice->end_date >= $weekStart) {
                        $symbol = mb_strtoupper(trim((string) $practice->symbol));
                        $type = 'practice';
                        break;
                    }
                }

                if (! $symbol) {
                    foreach ($examSessions as $exam) {
                        if ($exam->start_date <= $weekEnd && $exam->end_date >= $weekStart) {
                            $symbol = 'Э';
                            $type = 'exam';
                            break;
                        }
                    }
                }

                if (! $symbol) {
                    foreach ($courseVacations as $vacation) {
                        $vs = Carbon::parse($vacation->start_date);
                        $ve = Carbon::parse($vacation->end_date);
                        if ($vs <= $weekEnd && $ve >= $weekStart) {
                            $symbol = 'К';
                            $type = 'vacation';
                            break;
                        }
                    }
                }

                $grid[$course][$w] = $symbol !== null ? ['symbol' => $symbol, 'type' => $type] : null;
            }
        }

        return [
            'weeks' => $weeks,
            'months' => $months,
            'grid' => $grid,
            'courses' => $courses->toArray(),
        ];
    }

    #[Computed]
    public function teacherWorkloadSummary(): Collection
    {
        return Teacher::whereHas('disciplines', fn ($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
            ->with([
                'position',
                'disciplines' => fn ($q) => $q->where('academic_year_id', $this->plan->academic_year_id)
                    ->with([
                        'semesters' => fn ($sq) => $sq->whereHas('curriculumSemester.discipline', fn ($dq) => $dq->where('curriculum_plan_id', $this->plan->id)),
                        'discipline',
                    ]),
            ])
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function availableCourses(): Collection
    {
        return $this->plan->disciplines->flatMap->semesters->pluck('course_number')->unique()->sort()->values();
    }

    #[Computed]
    public function availableSemesters(): Collection
    {
        $query = $this->plan->disciplines->flatMap->semesters;
        if ($this->courseFilter) {
            $query = $query->where('course_number', (int) $this->courseFilter);
        }

        return $query->pluck('semester_number')->unique()->sort()->values();
    }

    #[Computed]
    public function semestersByCourse(): array
    {
        $result = [];
        $semesters = $this->plan->disciplines->flatMap->semesters;

        for ($course = 1; $course <= 4; $course++) {
            $courseSemesters = $semesters->where('course_number', $course)->sortBy('semester_number');
            $result[$course] = $courseSemesters->map(function ($semester) {
                return [
                    'id' => $semester->id,
                    'semester_number' => $semester->semester_number,
                    'semester_in_course' => $semester->semester_number - (($semester->course_number - 1) * 2),
                    'hours_total' => $semester->hours_total,
                    'hours_lecture' => $semester->hours_lecture,
                    'hours_practice' => $semester->hours_practice,
                    'hours_lab' => $semester->hours_lab,
                    'hours_self_study' => $semester->hours_self_study,
                    'discipline_name' => $semester->discipline->name,
                ];
            })->values()->toArray();
        }

        return $result;
    }

    #[Computed]
    public function totalHoursByCourse(): array
    {
        $result = [];
        foreach ($this->semestersByCourse as $course => $semesters) {
            $result[$course] = collect($semesters)->sum('hours_total');
        }

        return $result;
    }

    #[Computed]
    public function isCurrentYearPlan(): bool
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        return $currentYear !== null && $this->plan->academic_year_id === $currentYear->id;
    }

    #[Computed]
    public function currentAcademicYear(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)->first();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $semesters = $this->plan->disciplines->flatMap->semesters;

        if ($this->courseFilter) {
            $semesters = $semesters->where('course_number', (int) $this->courseFilter);
        }

        if ($this->semesterFilter) {
            $semesters = $semesters->where('semester_number', (int) $this->semesterFilter);
        }

        if ($this->disciplineSearch) {
            $search = mb_strtolower($this->disciplineSearch);
            $semesters = $semesters->filter(fn ($s) => mb_strpos(mb_strtolower($s->discipline->name), $search) !== false);
        }

        if ($this->teacherFilter) {
            $teacherId = (int) $this->teacherFilter;
            $semesters = $semesters->filter(function ($s) use ($teacherId) {
                return TeacherDisciplineSemester::where('curriculum_semester_id', $s->id)
                    ->whereHas('teacherDiscipline', fn ($q) => $q->where('teacher_id', $teacherId)->where('academic_year_id', $this->plan->academic_year_id))
                    ->exists();
            });
        }

        $semesters = $semesters->sortBy(['course_number', 'semester_number']);

        $teachers = collect();
        if ($this->teacherSearch) {
            $search = '%'.$this->teacherSearch.'%';
            $teachers = Teacher::where('is_active', true)
                ->where(function ($q) use ($search) {
                    $q->where('last_name', 'like', $search)
                        ->orWhere('first_name', 'like', $search);
                })
                ->orderBy('last_name')
                ->get();
        } else {
            $teachers = Teacher::where('is_active', true)->orderBy('last_name')->limit(10)->get();
        }

        return view('livewire.curriculum.show-plan', [
            'plan' => $this->plan,
            'semesters' => $semesters,
            'searchableTeachers' => $teachers,
            'allTeachers' => Teacher::where('is_active', true)->orderBy('last_name')->get(),
            'assignedTeachers' => Teacher::whereHas('disciplines', fn ($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
                ->orderBy('last_name')
                ->get(),
        ]);
    }
}
