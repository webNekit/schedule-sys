<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

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

    public ?int $courseFilter = null;

    public ?int $semesterFilter = null;

    public string $disciplineSearch = '';

    public ?int $teacherFilter = null;

    public bool $showAssignModal = false;

    public bool $showWorkloadModal = false;

    public ?int $activeSemesterId = null;

    // Стейт нагрузки: semester_id => [ {teacher_id, teacher_name, hours}, ... ]
    public array $workloadState = []; 

    public ?int $assignDisciplineId = null;

    public string $assignDisciplineName = '';

    public string $teacherSearch = '';

    public bool $editingPlanName = false;

    public string $editedPlanName = '';

    public bool $showExamModal = false;

    public ?int $examCourse = null;

    public ?int $examSemester = null;

    public ?string $examStartDate = null;

    public ?string $examEndDate = null;

    public function mount(CurriculumPlan $plan): void
    {
        $this->loadPlanData($plan);
        if ($this->plan->total_hours === 0) {
            $total = CurriculumSemester::whereHas('discipline', fn ($q) => $q->where('curriculum_plan_id', $this->plan->id))->sum('hours_total');
            $this->plan->update(['total_hours' => $total]);
        }
    }

    private function loadPlanData(CurriculumPlan $plan): void
    {
        $this->plan = $plan->fresh()->load([
            'specialty',
            'academicYear',
            'creator',
            'practices',
            'disciplines' => fn ($q) => $q->orderBy('sort_order')->orderBy('name'),
            'disciplines.semesters' => fn ($q) => $q->orderBy('course_number')->orderBy('semester_number'),
            'disciplines.semesters.controlForm',
            'disciplines.teacherDisciplines' => fn ($q) => $q->where('academic_year_id', $plan->academic_year_id),
            'disciplines.teacherDisciplines.teacher',
            'disciplines.teacherDisciplines.group',
        ]);
    }

    public function openWorkloadManager(int $disciplineId, string $disciplineName): void
    {
        $this->assignDisciplineId = $disciplineId;
        $this->assignDisciplineName = $disciplineName;
        
        $semesters = CurriculumSemester::where('discipline_id', $disciplineId)->orderBy('semester_number')->get();
        $this->workloadState = [];
        
        foreach ($semesters as $sem) {
            $assignments = TeacherDisciplineSemester::where('curriculum_semester_id', $sem->id)
                ->whereHas('teacherDiscipline', fn($q) => $q->where('academic_year_id', $this->plan->academic_year_id))
                ->with('teacherDiscipline.teacher')
                ->orderBy('sort_order')
                ->get();
                
            $this->workloadState[$sem->id] = $assignments->map(fn($a) => [
                'teacher_id' => $a->teacherDiscipline->teacher_id,
                'teacher_name' => $a->teacherDiscipline->teacher->short_name,
                'hours' => $a->planned_hours,
            ])->toArray();
        }
        
        $this->activeSemesterId = $semesters->first()?->id;
        $this->showWorkloadModal = true;
    }

    public function selectSemester(int $semesterId): void
    {
        $this->activeSemesterId = $semesterId;
    }

    public function openAssignModal(): void
    {
        $this->teacherSearch = '';
        $this->showAssignModal = true;
    }

    public function addTeacherToSemester(int $teacherId): void
    {
        if (!$this->activeSemesterId) return;
        
        $teacher = Teacher::find($teacherId);
        if (!$teacher) return;
        
        foreach ($this->workloadState[$this->activeSemesterId] as $assignment) {
            if ($assignment['teacher_id'] == $teacherId) {
                session()->flash('error', 'Преподаватель уже назначен на этот семестр');
                $this->showAssignModal = false;
                return;
            }
        }
        
        $sem = CurriculumSemester::find($this->activeSemesterId);
        $currentSum = 0;
        foreach ($this->workloadState[$this->activeSemesterId] as $item) {
            $currentSum += (int)($item['hours'] ?? 0);
        }
        $remaining = $sem->hours_total - $currentSum;
        
        $this->workloadState[$this->activeSemesterId][] = [
            'teacher_id' => $teacher->id,
            'teacher_name' => $teacher->short_name,
            'hours' => (string)max(0, $remaining),
        ];
        
        $this->showAssignModal = false;
        $this->teacherSearch = '';
    }

    public function removeTeacherFromSemester(int $semesterId, int $index): void
    {
        if (isset($this->workloadState[$semesterId][$index])) {
            unset($this->workloadState[$semesterId][$index]);
            $this->workloadState[$semesterId] = array_values($this->workloadState[$semesterId]);
        }
    }

    public function updateSortOrder(int $semesterId, array $orderedIndices): void
    {
        $newState = [];
        foreach ($orderedIndices as $index) {
            if (isset($this->workloadState[$semesterId][$index])) {
                $newState[] = $this->workloadState[$semesterId][$index];
            }
        }
        $this->workloadState[$semesterId] = $newState;
    }

    public function saveWorkload(): void
    {
        $tdIds = TeacherDiscipline::where('discipline_id', $this->assignDisciplineId)
            ->where('academic_year_id', $this->plan->academic_year_id)
            ->whereNull('group_id')
            ->pluck('id');
            
        TeacherDisciplineSemester::whereIn('teacher_discipline_id', $tdIds)->delete();
        TeacherDiscipline::whereIn('id', $tdIds)->delete();
        
        foreach ($this->workloadState as $semId => $assignments) {
            foreach ($assignments as $index => $data) {
                $td = TeacherDiscipline::firstOrCreate([
                    'teacher_id' => $data['teacher_id'],
                    'discipline_id' => $this->assignDisciplineId,
                    'academic_year_id' => $this->plan->academic_year_id,
                    'group_id' => null,
                ], ['is_primary' => $index === 0, 'planned_hours' => 0]);
                
                TeacherDisciplineSemester::create([
                    'teacher_discipline_id' => $td->id,
                    'curriculum_semester_id' => $semId,
                    'planned_hours' => (int)$data['hours'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }
        
        $newTdIds = TeacherDiscipline::where('discipline_id', $this->assignDisciplineId)
            ->where('academic_year_id', $this->plan->academic_year_id)
            ->pluck('id');
            
        foreach ($newTdIds as $id) {
            $sum = TeacherDisciplineSemester::where('teacher_discipline_id', $id)->sum('planned_hours');
            TeacherDiscipline::where('id', $id)->update(['planned_hours' => $sum]);
        }
        
        $this->closeAssignModal();
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Нагрузка успешно сохранена.');
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->showWorkloadModal = false;
        $this->assignDisciplineId = null;
        $this->activeSemesterId = null;
        $this->teacherSearch = '';
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
                $semesters = CurriculumSemester::where('discipline_id', $this->assignDisciplineId)->get();
                $td = TeacherDiscipline::create([
                    'teacher_id' => $teacherId,
                    'discipline_id' => $this->assignDisciplineId,
                    'academic_year_id' => $this->plan->academic_year_id,
                    'group_id' => null,
                    'is_primary' => true,
                    'planned_hours' => $semesters->sum('hours_total')
                ]);
                
                foreach ($semesters as $sem) {
                    TeacherDisciplineSemester::create([
                        'teacher_discipline_id' => $td->id,
                        'curriculum_semester_id' => $sem->id,
                        'planned_hours' => $sem->hours_total,
                        'sort_order' => 1,
                        'is_active' => true
                    ]);
                }
                $this->showAssignModal = false;
                $this->loadPlanData($this->plan);
            } else {
                $this->openWorkloadManager($this->assignDisciplineId, $this->assignDisciplineName);
                $this->addTeacherToSemester($teacherId);
            }
        }
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

    public function isNonSchedulable(bool $isSchedulable): bool
    {
        return ! $isSchedulable;
    }

    #[Computed]
    public function availableCourses(): Collection
    {
        return $this->plan->disciplines->flatMap(fn ($d) => $d->semesters)->pluck('course_number')->unique()->sort()->values();
    }

    #[Computed]
    public function availableSemesters(): Collection
    {
        $semesters = $this->plan->disciplines->flatMap(fn ($d) => $d->semesters);
        if ($this->courseFilter) {
            $semesters = $semesters->where('course_number', $this->courseFilter);
        }

        return $semesters->pluck('semester_number')->unique()->sort()->values();
    }

    #[Computed]
    public function teachersForFilter(): Collection
    {
        $teacherIds = TeacherDiscipline::where('academic_year_id', $this->plan->academic_year_id)
            ->whereIn('discipline_id', $this->plan->disciplines->pluck('id'))->pluck('teacher_id')->unique();

        return Teacher::whereIn('id', $teacherIds)->orderBy('last_name')->get();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $semesters = $this->plan->disciplines->flatMap(fn ($d) => $d->semesters);
        if ($this->courseFilter) {
            $semesters = $semesters->where('course_number', $this->courseFilter);
        }
        if ($this->semesterFilter) {
            $semesters = $semesters->where('semester_number', $this->semesterFilter);
        }

        if ($this->teacherFilter) {
            $semesters = $semesters->filter(function ($semester) {
                return $semester->discipline->teacherDisciplines->where('academic_year_id', $this->plan->academic_year_id)->where('teacher_id', $this->teacherFilter)->isNotEmpty();
            });
        }

        if ($this->disciplineSearch) {
            $search = mb_strtolower($this->disciplineSearch);
            $semesters = $semesters->filter(function ($semester) use ($search) {
                return str_contains(mb_strtolower($semester->discipline->name), $search) || str_contains(mb_strtolower((string) $semester->discipline->code), $search);
            });
        }

        $semesters = $semesters->sortBy(function ($s) {
            return [$s->course_number, $s->semester_number, $s->discipline->sort_order];
        });

        $teachers = Teacher::active()->with(['position', 'department'])->orderBy('last_name')->get();
        if ($this->teacherSearch !== '') {
            $search = mb_strtolower($this->teacherSearch);
            $teachers = $teachers->filter(function ($teacher) use ($search) {
                return str_contains(mb_strtolower("{$teacher->last_name} {$teacher->first_name} {$teacher->middle_name}"), $search);
            });
        }

        return view('livewire.curriculum.show-plan', [
            'plan' => $this->plan,
            'semesters' => $semesters,
            'searchableTeachers' => $teachers,
        ]);
    }
}
