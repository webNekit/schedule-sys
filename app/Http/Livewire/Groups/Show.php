<?php

declare(strict_types=1);

namespace App\Http\Livewire\Groups;

use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumPlan;
use App\Models\Department;
use App\Models\Group;
use App\Models\GroupBuilding;
use App\Models\GroupCurriculumAssignment;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    public Group $group;

    public ?int $selectedCourse = null;

    public ?int $selectedSemester = null;

    public ?int $semesterFilter = null;

    public bool $showGroupForm = false;

    public string $groupFormName = '';

    public ?int $groupFormSpecialtyId = null;

    public ?int $groupFormDepartmentId = null;

    public ?int $groupFormAcademicYearId = null;

    public int $groupFormShift = 1;

    public int $groupFormStudentsCount = 0;

    public int $groupFormCourse = 1;

    public bool $showCurriculumForm = false;

    public ?int $selectedSpecialtyId = null;

    public ?int $selectedPlanIdForForm = null;

    public ?int $selectedYearIdForForm = null;

    public bool $showBuildingForm = false;

    public ?int $selectedBuildingId = null;

    public string $disciplineSearch = '';

    public ?int $disciplineTeacherFilter = null;

    public function mount(Group $group): void
    {
        $this->group = $group->load([
            'specialty.educationLevel',
            'department.faculty',
            'academicYear',
            'buildings',
            'subgroups',
            'curriculumAssignments.curriculumPlan',
        ]);

        $this->selectedSpecialtyId = $group->specialty_id;
    }

    public function openGroupForm(): void
    {
        $this->groupFormName = $this->group->name;
        $this->groupFormSpecialtyId = $this->group->specialty_id;
        $this->groupFormDepartmentId = $this->group->department_id;
        $this->groupFormAcademicYearId = $this->group->academic_year_id;
        $this->groupFormShift = $this->group->shift;
        $this->groupFormStudentsCount = $this->group->students_count;
        $this->groupFormCourse = $this->group->current_course;
        $this->showGroupForm = true;
    }

    public function saveGroup(): void
    {
        $this->validate([
            'groupFormName' => 'required|string|max:255',
            'groupFormSpecialtyId' => 'nullable|integer|exists:specialties,id',
            'groupFormDepartmentId' => 'nullable|integer|exists:departments,id',
            'groupFormAcademicYearId' => 'nullable|integer|exists:academic_years,id',
            'groupFormShift' => 'nullable|integer|in:1,2',
            'groupFormStudentsCount' => 'nullable|integer|min:0',
            'groupFormCourse' => 'nullable|integer|min:1',
        ]);

        $this->group->update([
            'name' => $this->groupFormName,
            'specialty_id' => $this->groupFormSpecialtyId,
            'department_id' => $this->groupFormDepartmentId,
            'academic_year_id' => $this->groupFormAcademicYearId,
            'shift' => $this->groupFormShift,
            'students_count' => $this->groupFormStudentsCount,
            'current_course' => $this->groupFormCourse,
        ]);

        $this->showGroupForm = false;
        $this->group->refresh();
    }

    public function openCurriculumForm(): void
    {
        $this->selectedSpecialtyId = $this->group->specialty_id;
        $this->selectedPlanIdForForm = null;
        $this->selectedYearIdForForm = AcademicYear::where('is_current', true)->first()?->id;
        $this->showCurriculumForm = true;
    }

    public function assignCurriculum(): void
    {
        $this->validate([
            'selectedPlanIdForForm' => 'required|integer|exists:curriculum_plans,id',
            'selectedYearIdForForm' => 'required|integer|exists:academic_years,id',
        ]);

        GroupCurriculumAssignment::create([
            'group_id' => $this->group->id,
            'academic_year_id' => $this->selectedYearIdForForm,
            'curriculum_plan_id' => $this->selectedPlanIdForForm,
            'course_number' => $this->group->current_course,
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
            'is_active' => true,
        ]);

        $this->showCurriculumForm = false;
        $this->selectedPlanIdForForm = null;
        $this->selectedYearIdForForm = null;
        $this->group->load('curriculumAssignments.curriculumPlan');
    }

    public function removeCurriculum(int $assignmentId): void
    {
        GroupCurriculumAssignment::where('group_id', $this->group->id)
            ->where('id', $assignmentId)
            ->delete();

        $this->group->load('curriculumAssignments.curriculumPlan');
    }

    public function openBuildingForm(): void
    {
        $this->selectedBuildingId = null;
        $this->showBuildingForm = true;
    }

    public function assignBuilding(): void
    {
        $this->validate([
            'selectedBuildingId' => 'required|integer|exists:buildings,id',
        ]);

        $exists = GroupBuilding::where('group_id', $this->group->id)
            ->where('building_id', $this->selectedBuildingId)
            ->exists();

        if (! $exists) {
            GroupBuilding::create([
                'group_id' => $this->group->id,
                'building_id' => $this->selectedBuildingId,
                'is_primary' => $this->group->buildings()->count() === 0,
            ]);
        }

        $this->showBuildingForm = false;
        $this->group->load('buildings');
    }

    public function removeBuilding(int $buildingId): void
    {
        $wasPrimary = GroupBuilding::where('group_id', $this->group->id)
            ->where('building_id', $buildingId)
            ->value('is_primary');

        GroupBuilding::where('group_id', $this->group->id)
            ->where('building_id', $buildingId)
            ->delete();

        // Если удалили основной — назначаем основным первый из оставшихся
        if ($wasPrimary) {
            $next = GroupBuilding::where('group_id', $this->group->id)->first();
            $next?->update(['is_primary' => true]);
        }

        $this->group->load('buildings');
    }

    public function setPrimaryBuilding(int $buildingId): void
    {
        GroupBuilding::where('group_id', $this->group->id)->update(['is_primary' => false]);
        GroupBuilding::where('group_id', $this->group->id)
            ->where('building_id', $buildingId)
            ->update(['is_primary' => true]);

        $this->group->load('buildings');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();

        $assignments = $this->group->curriculumAssignments()
            ->with(['curriculumPlan.disciplines.semesters', 'academicYear'])
            ->get();

        // Фильтруем дисциплины и семестры: показываем только те, что относятся к ТЕКУЩЕМУ учебному году
        $activeAssignment = $assignments->where('academic_year_id', $currentYear?->id)->first()
                          ?? $assignments->where('is_active', true)->first();

        $disciplines = collect();
        $semesters = collect();

        if ($activeAssignment) {
            $plan = $activeAssignment->curriculumPlan;
            foreach ($plan->disciplines as $discipline) {
                foreach ($discipline->semesters as $semester) {
                    $semesters->push($semester);
                }
                $disciplines->push($discipline);
            }
        }

        $courses = $semesters->pluck('course_number')->unique()->sort()->values();
        $calculatedCourse = $this->group->calculateCurrentCourse();
        $selectedCourse = $this->selectedCourse ?? ($courses->contains($calculatedCourse) ? $calculatedCourse : $courses->first());
        $semestersForCourse = $semesters->where('course_number', $selectedCourse)->sortBy('semester_number');

        $availableSemesters = $semestersForCourse->pluck('semester_number')->unique()->sort()->values();

        if ($this->semesterFilter) {
            $semestersForCourse = $semestersForCourse->where('semester_number', $this->semesterFilter);
        }

        $teacherAssignments = TeacherDiscipline::whereIn(
            'discipline_id',
            $disciplines->pluck('id')
        )->with(['teacher', 'discipline', 'group'])->get()->groupBy('discipline_id');

        $disciplineIds = $disciplines->pluck('id');

        $filteredTeacherAssignments = $teacherAssignments;
        if ($this->disciplineTeacherFilter) {
            $filteredTeacherAssignments = $filteredTeacherAssignments->filter(function ($assignments) {
                return $assignments->contains('teacher_id', $this->disciplineTeacherFilter);
            });

            $filteredDisciplineIds = $filteredTeacherAssignments->keys();
            $semestersForCourse = $semestersForCourse->filter(fn ($s) => $filteredDisciplineIds->contains($s->discipline_id));
        }

        if ($this->disciplineSearch) {
            $search = mb_strtolower($this->disciplineSearch);
            $semestersForCourse = $semestersForCourse->filter(function ($s) use ($search) {
                $discipline = $s->discipline;

                return $discipline && mb_strpos(mb_strtolower($discipline->name), $search) !== false;
            });
        }

        $plansForSpecialty = CurriculumPlan::where('specialty_id', $this->group->specialty_id)
            ->with('specialty')
            ->orderBy('name')
            ->get();

        $teachersByDiscipline = Teacher::active()
            ->whereHas('disciplines', fn ($q) => $q->whereIn('discipline_id', $disciplineIds))
            ->orderBy('last_name')
            ->get();

        return view('livewire.groups.show', [
            'assignments' => $assignments,
            'activeAssignment' => $activeAssignment,
            'currentYear' => $currentYear,
            'disciplines' => $disciplines,
            'semesters' => $semesters,
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'semestersForCourse' => $semestersForCourse,
            'availableSemesters' => $availableSemesters,
            'teacherAssignments' => $teacherAssignments,
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(),
            'academicYears' => AcademicYear::orderBy('year_start', 'desc')->get(),
            'availablePlans' => CurriculumPlan::with('specialty')->orderBy('name')->get(),
            'buildings' => Building::active()->orderBy('name')->get(),
            'plansForSpecialty' => $plansForSpecialty,
            'teachersByDiscipline' => $teachersByDiscipline,
        ]);
    }

    public function selectCourse(int $course): void
    {
        $this->selectedCourse = $course;
        $this->selectedSemester = null;
        $this->semesterFilter = null;
    }

    public function getBuildingsProperty()
    {
        return $this->group->buildings;
    }
}
