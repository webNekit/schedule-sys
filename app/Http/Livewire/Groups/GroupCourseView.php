<?php

declare(strict_types=1);

namespace App\Http\Livewire\Groups;

use App\Models\CurriculumDiscipline;
use App\Models\CurriculumSemester;
use App\Models\Department;
use App\Models\Group;
use App\Models\GroupCurriculumAssignment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class GroupCourseView extends Component
{
    public ?int $selectedDepartmentId = null;

    public ?int $selectedGroupId = null;

    public ?int $selectedCourse = null;

    public function render(): mixed
    {
        return view('livewire.groups.group-course-view', [
            'departments' => Department::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->selectedCourse = null;
    }

    public function selectCourse(int $course): void
    {
        $this->selectedCourse = $course;
    }

    #[Computed]
    public function getGroupsByDepartment(): mixed
    {
        if ($this->selectedDepartmentId === null) {
            return collect();
        }

        return Group::where('department_id', $this->selectedDepartmentId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function getCoursesForGroup(): array
    {
        if ($this->selectedGroupId === null) {
            return [];
        }

        $group = Group::findOrFail($this->selectedGroupId);
        $maxCourse = $group->specialty?->max_courses ?? 4;

        return range(1, $maxCourse);
    }

    #[Computed]
    public function getDisciplinesForCourse(): mixed
    {
        if ($this->selectedGroupId === null || $this->selectedCourse === null) {
            return collect();
        }

        $assignment = GroupCurriculumAssignment::where('group_id', $this->selectedGroupId)
            ->where('is_active', true)
            ->with('curriculumPlan')
            ->first();

        if ($assignment === null) {
            return collect();
        }

        $plan = $assignment->curriculumPlan;

        $semesterNumbers = [
            $this->selectedCourse * 2 - 1,
            $this->selectedCourse * 2,
        ];

        $disciplineIds = CurriculumSemester::whereIn('semester_number', $semesterNumbers)
            ->whereHas('discipline', fn ($q) => $q->where('curriculum_plan_id', $plan->id))
            ->pluck('discipline_id')
            ->unique();

        return CurriculumDiscipline::whereIn('id', $disciplineIds)
            ->with('semesters')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
