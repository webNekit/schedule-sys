<?php

declare(strict_types=1);

namespace App\Http\Livewire\Teachers;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\ScheduleLesson;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TeacherWorkloadDashboard extends Component
{
    public ?int $departmentId = null;

    public ?int $academicYearId = null;

    public ?int $selectedTeacherId = null;

    public array $workloadData = [];

    public function mount(): void
    {
        $current = AcademicYear::where('is_current', true)->first();
        $this->academicYearId = $current?->id;
    }

    public function render(): View
    {
        $this->loadData();

        $teacherDetail = null;

        if ($this->selectedTeacherId) {
            $teacher = Teacher::with('department', 'position')->find($this->selectedTeacherId);

            $disciplinesQuery = TeacherDiscipline::where('teacher_id', $this->selectedTeacherId)
                ->with([
                    'discipline.curriculumPlan',
                    'discipline.semesters.controlForm',
                    'group',
                    'academicYear',
                    'semesters.curriculumSemester.controlForm',
                ]);

            if ($this->academicYearId) {
                $disciplinesQuery->where('academic_year_id', $this->academicYearId);
            }

            $tds = $disciplinesQuery->get();

            $semesters1 = collect();
            $semesters2 = collect();

            foreach ($tds as $td) {
                foreach ($td->semesters as $tdSem) {
                    $cs = $tdSem->curriculumSemester;
                    if (! $cs) {
                        continue;
                    }

                    $publishedLessons = ScheduleLesson::where('teacher_id', $this->selectedTeacherId)
                        ->where('discipline_id', $td->discipline_id)
                        ->where('group_id', $td->group_id)
                        ->where('status', '!=', 'cancelled')
                        ->whereHas('version', fn ($q) => $q
                            ->where('status', 'published')
                            ->when($this->academicYearId, fn ($q2) => $q2->where('academic_year_id', $this->academicYearId))
                        )
                        ->whereBetween('date', [$cs->semester_in_course === 1
                            ? $td->academicYear?->first_semester_start
                            : $td->academicYear?->second_semester_start,
                            $cs->semester_in_course === 1
                            ? $td->academicYear?->first_semester_end
                            : $td->academicYear?->second_semester_end,
                        ])
                        ->count() * 2;

                    $item = [
                        'discipline_name' => $td->discipline?->name,
                        'group_name' => $td->group?->name,
                        'planned' => $tdSem->planned_hours,
                        'conducted' => $publishedLessons,
                        'remaining' => max(0, $tdSem->planned_hours - $publishedLessons),
                        'control_form' => $cs->controlForm?->name,
                        'is_exam' => $cs->controlForm?->is_exam_session,
                    ];

                    if ($cs->semester_in_course === 1) {
                        $semesters1->push($item);
                    } else {
                        $semesters2->push($item);
                    }
                }
            }

            $teacherDetail = [
                'teacher' => $teacher,
                'semester1' => $semesters1,
                'semester2' => $semesters2,
                'semester1_total' => $semesters1->sum('planned'),
                'semester1_conducted' => $semesters1->sum('conducted'),
                'semester2_total' => $semesters2->sum('planned'),
                'semester2_conducted' => $semesters2->sum('conducted'),
            ];
        }

        return view('livewire.teachers.teacher-workload-dashboard', [
            'teacherDetail' => $teacherDetail,
        ]);
    }

    public function loadData(): void
    {
        $query = Teacher::with('department', 'disciplines', 'position');

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        $this->workloadData = $query->get()->map(function (Teacher $teacher) {
            $disciplinesQuery = $teacher->disciplines();

            if ($this->academicYearId) {
                $disciplinesQuery->where('academic_year_id', $this->academicYearId);
            }

            $totalPlanned = (int) $disciplinesQuery->sum('planned_hours');

            $lessonsCount = ScheduleLesson::where('teacher_id', $teacher->id)
                ->where('status', '!=', 'cancelled')
                ->whereHas('version', fn ($q) => $q
                    ->where('status', 'published')
                    ->when($this->academicYearId, fn ($q2) => $q2->where('academic_year_id', $this->academicYearId))
                )
                ->count();

            $semesterHours = TeacherDisciplineSemester::whereIn('teacher_discipline_id', $disciplinesQuery->pluck('id'))
                ->sum('planned_hours');

            return [
                'id' => $teacher->id,
                'name' => $teacher->full_name,
                'short_name' => $teacher->getShortNameAttribute(),
                'department' => $teacher->department?->name,
                'position' => $teacher->position?->name,
                'disciplines_count' => $disciplinesQuery->count(),
                'planned_hours' => $totalPlanned,
                'semester_hours' => $semesterHours,
                'conducted_lessons' => $lessonsCount * 2,
                'rate' => $teacher->rate,
                'max_hours' => $teacher->max_hours_per_week,
            ];
        })->toArray();
    }

    public function selectTeacher(int $teacherId): void
    {
        $this->selectedTeacherId = $teacherId;
    }

    public function backToList(): void
    {
        $this->selectedTeacherId = null;
    }

    public function exportExcel(): void
    {
        //
    }

    #[Computed]
    public function getAcademicYearsProperty(): Collection
    {
        return AcademicYear::orderBy('year_start', 'desc')->get();
    }

    #[Computed]
    public function getDepartmentsProperty(): Collection
    {
        return Department::active()->orderBy('name')->get();
    }
}
