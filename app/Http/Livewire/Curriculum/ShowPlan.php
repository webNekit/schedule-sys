<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\AcademicYear;
use App\Models\CurriculumPlan;
use App\Models\CurriculumPractice;
use App\Models\CurriculumSemester;
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

        // Build 52-week reference structure (week grouping by end-date month)
        $baseDate = Carbon::create($entryYear, 9, 1)->startOfWeek(Carbon::MONDAY);
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
            $courseStart = Carbon::create($courseYear, 9, 1)->startOfWeek(Carbon::MONDAY);

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
