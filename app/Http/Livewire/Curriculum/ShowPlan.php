<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\AcademicYear;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
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
            if (!$this->assignDisciplineId) {
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

    public function openExamModal(int $course, int $semester): void
    {
        $this->examCourse = $course;
        $this->examSemester = $semester;
        
        $existing = \App\Models\CurriculumPractice::where('curriculum_plan_id', $this->plan->id)
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

        \App\Models\CurriculumPractice::updateOrCreate([
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
        $practice = \App\Models\CurriculumPractice::find($practiceId);
        $this->practiceTeacherId = $practice?->teacher_id;
        $this->showPracticeTeacherModal = true;
    }

    public function savePracticeTeacher(): void
    {
        $this->validate([
            'practiceTeacherId' => 'nullable|integer|exists:teachers,id',
        ]);

        \App\Models\CurriculumPractice::where('id', $this->selectedPracticeId)
            ->update(['teacher_id' => $this->practiceTeacherId]);

        $this->showPracticeTeacherModal = false;
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Преподаватель на практику назначен.');
    }

    public function isNonSchedulable(bool $isSchedulable): bool
    {
        return ! $isSchedulable;
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
            $semesters = $semesters->filter(function($s) use ($teacherId) {
                return TeacherDisciplineSemester::where('curriculum_semester_id', $s->id)
                    ->whereHas('teacherDiscipline', fn($q) => $q->where('teacher_id', $teacherId)->where('academic_year_id', $this->plan->academic_year_id))
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
            'assignedTeachers' => Teacher::whereHas('disciplines', fn($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
                ->orderBy('last_name')
                ->get(),
        ]);
    }
}
