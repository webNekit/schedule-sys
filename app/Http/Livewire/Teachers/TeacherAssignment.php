<?php

declare(strict_types=1);

namespace App\Http\Livewire\Teachers;

use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TeacherAssignment extends Component
{
    public ?int $departmentId = null;

    public ?int $curriculumPlanId = null;

    public array $assignments = [];

    public function render(): View
    {
        $this->loadAssignments();

        return view('livewire.teachers.teacher-assignment');
    }

    public function loadAssignments(): void
    {
        if ($this->curriculumPlanId === null) {
            $this->assignments = [];

            return;
        }

        $existing = TeacherDiscipline::whereHas('discipline', function ($query) {
            $query->where('curriculum_plan_id', $this->curriculumPlanId);
        })->get()->keyBy('discipline_id');

        $this->assignments = $this->getDisciplinesProperty()->mapWithKeys(
            fn (CurriculumDiscipline $discipline) => [
                $discipline->id => $existing->get($discipline->id)?->teacher_id,
            ]
        )->toArray();
    }

    public function saveAssignments(): void
    {
        if ($this->curriculumPlanId === null) {
            return;
        }

        foreach ($this->assignments as $disciplineId => $teacherId) {
            TeacherDiscipline::updateOrCreate(
                ['discipline_id' => $disciplineId],
                ['teacher_id' => $teacherId ?: null],
            );
        }
    }

    public function assignTeacher(int $disciplineId, int $teacherId): void
    {
        $this->assignments[$disciplineId] = $teacherId;
    }

    #[Computed]
    public function getPlansProperty(): Collection
    {
        return CurriculumPlan::with('specialty')
            ->when($this->departmentId, fn ($query) => $query->whereHas('specialty', fn ($q) => $q->where('department_id', $this->departmentId)))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function getDisciplinesProperty(): Collection
    {
        if ($this->curriculumPlanId === null) {
            return collect();
        }

        return CurriculumDiscipline::where('curriculum_plan_id', $this->curriculumPlanId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function getTeachersProperty(): Collection
    {
        return Teacher::active()
            ->when($this->departmentId, fn ($query) => $query->where('department_id', $this->departmentId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
