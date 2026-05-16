<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use App\Models\Group;
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

    public ?int $teacherFilter = null; // Восстановлен фильтр по преподавателю

    public bool $showAssignModal = false;

    public ?int $assignDisciplineId = null;

    public ?int $assignTeacherId = null;

    public ?int $assignGroupId = null;

    public string $assignDisciplineName = '';

    public string $teacherSearch = '';

    public bool $editingPlanName = false;

    public string $editedPlanName = '';

    public function mount(CurriculumPlan $plan): void
    {
        $this->loadPlanData($plan);

        // АВТО-ИСПРАВЛЕНИЕ: Если "Всего часов" равно 0, пересчитываем из базы
        if ($this->plan->total_hours === 0) {
            $total = CurriculumSemester::whereHas('discipline', fn ($q) => $q->where('curriculum_plan_id', $this->plan->id))
                ->sum('hours_total');
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

    public function editPlanName(): void
    {
        $this->editedPlanName = $this->plan->name;
        $this->editingPlanName = true;
    }

    public function savePlanName(): void
    {
        $this->validate(['editedPlanName' => 'required|string|max:255']);
        $this->plan->update(['name' => $this->editedPlanName]);
        $this->editingPlanName = false;
        $this->loadPlanData($this->plan);
    }

    public function cancelEditPlanName(): void
    {
        $this->editingPlanName = false;
    }

    public function updatedCourseFilter(): void
    {
        $this->semesterFilter = null;
    }

    public function resetFilters(): void
    {
        $this->courseFilter = null;
        $this->semesterFilter = null;
        $this->disciplineSearch = '';
        $this->teacherFilter = null;
    }

    public function openAssignModal(int $disciplineId, string $disciplineName): void
    {
        $this->assignDisciplineId = $disciplineId;
        $this->assignDisciplineName = $disciplineName;
        $this->assignTeacherId = null;
        $this->assignGroupId = null;
        $this->teacherSearch = '';
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->assignDisciplineId = null;
        $this->assignTeacherId = null;
        $this->assignGroupId = null;
        $this->teacherSearch = '';
    }

    public function selectAndAssign(int $teacherId): void
    {
        $this->assignTeacherId = $teacherId;

        $td = TeacherDiscipline::firstOrCreate([
            'teacher_id' => $this->assignTeacherId,
            'discipline_id' => $this->assignDisciplineId,
            'group_id' => null,
            'academic_year_id' => $this->plan->academic_year_id,
        ], [
            'is_primary' => true,
            'planned_hours' => 0,
        ]);

        $semesters = CurriculumSemester::where('discipline_id', $this->assignDisciplineId)->get();
        $totalHours = 0;

        foreach ($semesters as $sem) {
            TeacherDisciplineSemester::firstOrCreate([
                'teacher_discipline_id' => $td->id,
                'curriculum_semester_id' => $sem->id,
            ], [
                'planned_hours' => $sem->hours_total,
                'is_active' => true,
            ]);
            $totalHours += $sem->hours_total;
        }

        $td->update(['planned_hours' => $totalHours]);

        $this->closeAssignModal();
        $this->loadPlanData($this->plan);

        session()->flash('message', 'Преподаватель успешно назначен на дисциплину.');
    }

    public function saveAssignment(): void
    {
        $this->validate([
            'assignTeacherId' => 'required|integer|exists:teachers,id',
            'assignGroupId' => 'nullable|integer|exists:groups,id',
        ], [
            'assignTeacherId.required' => 'Выберите преподавателя',
        ]);

        $td = TeacherDiscipline::firstOrCreate([
            'teacher_id' => $this->assignTeacherId,
            'discipline_id' => $this->assignDisciplineId,
            'group_id' => $this->assignGroupId ?: null,
            'academic_year_id' => $this->plan->academic_year_id,
        ], [
            'is_primary' => true,
            'planned_hours' => 0,
        ]);

        $semesters = CurriculumSemester::where('discipline_id', $this->assignDisciplineId)->get();
        $totalHours = 0;

        foreach ($semesters as $sem) {
            TeacherDisciplineSemester::firstOrCreate([
                'teacher_discipline_id' => $td->id,
                'curriculum_semester_id' => $sem->id,
            ], [
                'planned_hours' => $sem->hours_total,
                'is_active' => true,
            ]);
            $totalHours += $sem->hours_total;
        }

        $td->update(['planned_hours' => $totalHours]);

        $this->closeAssignModal();
        $this->loadPlanData($this->plan);

        session()->flash('message', 'Преподаватель успешно назначен на дисциплину.');
    }

    public function removeAssignment(int $teacherDisciplineId): void
    {
        TeacherDisciplineSemester::where('teacher_discipline_id', $teacherDisciplineId)->delete();
        TeacherDiscipline::where('id', $teacherDisciplineId)->delete();
        $this->loadPlanData($this->plan);
        session()->flash('message', 'Назначение удалено.');
    }

    /**
     * Проверяет, является ли строка заголовком цикла (ОД, ПМ и т.д. без цифр в коде)
     */
    public function isCycleHeader(string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        // Если в коде нет цифр (например "ОД" или "ПМ") - это заголовок
        return ! preg_match('/\d/', $code);
    }

    #[Computed]
    public function availableCourses(): Collection
    {
        return $this->plan->disciplines
            ->flatMap(fn ($d) => $d->semesters)
            ->pluck('course_number')
            ->unique()
            ->sort()
            ->values();
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
            ->whereIn('discipline_id', $this->plan->disciplines->pluck('id'))
            ->pluck('teacher_id')
            ->unique();

        return Teacher::whereIn('id', $teacherIds)->orderBy('last_name')->get();
    }

    #[Computed]
    public function availableGroups(): Collection
    {
        return Group::active()
            ->where('specialty_id', $this->plan->specialty_id)
            ->orderBy('name')->get()
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]);
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
                return $semester->discipline->teacherDisciplines
                    ->where('academic_year_id', $this->plan->academic_year_id)
                    ->where('teacher_id', $this->teacherFilter)
                    ->isNotEmpty();
            });
        }

        if ($this->disciplineSearch) {
            $semesters = $semesters->filter(function ($semester) {
                $name = mb_strtolower($semester->discipline->name);
                $code = mb_strtolower((string) $semester->discipline->code);

                return str_contains($name, mb_strtolower($this->disciplineSearch))
                    || str_contains($code, mb_strtolower($this->disciplineSearch));
            });
        }

        $semesters = $semesters->sortBy(function ($s) {
            $d = $s->discipline;

            return [$s->course_number, $s->semester_number, $d->sort_order];
        });

        $teachers = Teacher::active()
            ->with(['position', 'department'])
            ->orderBy('last_name')
            ->get();

        if ($this->teacherSearch !== '') {
            $search = mb_strtolower($this->teacherSearch);
            $teachers = $teachers->filter(function ($teacher) use ($search) {
                $fullName = mb_strtolower("{$teacher->last_name} {$teacher->first_name} {$teacher->middle_name}");

                return str_contains($fullName, $search);
            });
        }

        return view('livewire.curriculum.show-plan', [
            'plan' => $this->plan,
            'semesters' => $semesters,
            'searchableTeachers' => $teachers,
        ]);
    }
}
