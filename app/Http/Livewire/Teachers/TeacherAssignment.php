<?php

declare(strict_types=1);

namespace App\Http\Livewire\Teachers;

use App\Models\AcademicYear;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
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

        $academicYear = AcademicYear::where('is_current', true)->first();

        foreach ($this->assignments as $disciplineId => $teacherId) {
            $td = TeacherDiscipline::updateOrCreate(
                ['discipline_id' => $disciplineId],
                [
                    'teacher_id' => $teacherId ?: null,
                    'academic_year_id' => $academicYear?->id,
                    'is_primary' => true,
                ],
            );

            // Если преподаватель назначен, создаем записи для семестров
            if ($teacherId) {
                $discipline = CurriculumDiscipline::with('semesters')->find($disciplineId);
                if ($discipline) {
                    foreach ($discipline->semesters as $semester) {
                        TeacherDisciplineSemester::updateOrCreate(
                            [
                                'teacher_discipline_id' => $td->id,
                                'curriculum_semester_id' => $semester->id,
                            ],
                            [
                                'planned_hours' => $semester->hours_total,
                            ]
                        );
                    }
                }
            } else {
                // Если преподаватель снят, удаляем записи семестров
                $td->semesters()->delete();
            }
        }

        session()->flash('message', 'Назначения успешно сохранены.');
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
