<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\CurriculumDiscipline;
use App\Models\CurriculumSemester;
use App\Models\Group;
use App\Models\HoursTracking;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherDisciplineSemester;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HoursTrackingService
{
    public function getRemainingHours(Group $group, CurriculumDiscipline $discipline): int
    {
        $currentSemesterNum = $group->getCurrentSemester();

        $totalHours = CurriculumSemester::where('discipline_id', $discipline->id)
            ->where('semester_number', $currentSemesterNum)
            ->sum('hours_total');

        $conductedHours = HoursTracking::where('group_id', $group->id)
            ->where('discipline_id', $discipline->id)
            ->where('semester_id', function ($q) use ($discipline, $currentSemesterNum) {
                $q->select('id')->from('curriculum_semesters')
                    ->where('discipline_id', $discipline->id)
                    ->where('semester_number', $currentSemesterNum);
            })
            ->where('is_cancelled', false)
            ->sum('hours_conducted');

        return max(0, $totalHours - (int) $conductedHours);
    }

    public function getTeacherRemainingHoursForDiscipline(Teacher $teacher, int $groupId, int $disciplineId, int $semesterNum): int
    {
        $assignment = $this->getTeacherAssignmentForDiscipline($teacher, $groupId, $disciplineId, $semesterNum);
        if (! $assignment) {
            return 0;
        }

        $conducted = HoursTracking::where('teacher_id', $teacher->id)
            ->where('group_id', $groupId)
            ->where('discipline_id', $disciplineId)
            ->where('semester_id', $assignment->curriculum_semester_id)
            ->where('is_cancelled', false)
            ->sum('hours_conducted');

        return max(0, $assignment->planned_hours - (int) $conducted);
    }

    public function getTeacherAssignmentForDiscipline(Teacher $teacher, int $groupId, int $disciplineId, int $semesterNum): ?TeacherDisciplineSemester
    {
        return TeacherDisciplineSemester::whereHas('teacherDiscipline', function ($q) use ($teacher, $groupId, $disciplineId) {
            $q->where('teacher_id', $teacher->id)
                ->where('discipline_id', $disciplineId)
                ->where(fn ($sq) => $sq->where('group_id', $groupId)->orWhereNull('group_id'));
        })->whereHas('curriculumSemester', function ($q) use ($semesterNum) {
            $q->where('semester_number', $semesterNum);
        })->where('is_active', true)
            ->first();
    }

    public function getWeeklyLoad(Group $group, Carbon $weekStart): int
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return (int) HoursTracking::where('group_id', $group->id)
            ->where('is_cancelled', false)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('hours_conducted');
    }

    public function getTeacherWeeklyLoad(Teacher $teacher, Carbon $weekStart): int
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return (int) HoursTracking::where('teacher_id', $teacher->id)
            ->where('is_cancelled', false)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('hours_conducted');
    }

    public function recalculateFromSchedule(ScheduleVersion $version): void
    {
        HoursTracking::whereIn('schedule_lesson_id', function ($q) use ($version) {
            $q->select('id')
                ->from('schedule_lessons')
                ->where('version_id', $version->id);
        })->delete();

        $lessons = ScheduleLesson::where('version_id', $version->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($lessons as $lesson) {
            if (! $lesson->teacher_id || ! $lesson->group_id || ! $lesson->discipline_id || ! $lesson->lesson_type_id || ! $lesson->date) {
                continue;
            }

            $semester = CurriculumSemester::where('discipline_id', $lesson->discipline_id)
                ->where('semester_number', $this->getSemesterNumberForDate($lesson->date, $version))
                ->first();

            if (! $semester) {
                continue;
            }

            HoursTracking::create([
                'group_id' => $lesson->group_id,
                'discipline_id' => $lesson->discipline_id,
                'teacher_id' => $lesson->teacher_id,
                'semester_id' => $semester->id,
                'lesson_type_id' => $lesson->lesson_type_id,
                'date' => $lesson->date,
                'hours_conducted' => 2, // Каждая пара - 2 часа
                'schedule_lesson_id' => $lesson->id,
                'is_cancelled' => false,
            ]);
        }
    }

    public function getDisciplinesWithDebt(Group $group): Collection
    {
        $disciplines = $group->curriculumPlans()
            ->with('disciplines.semesters')
            ->get()
            ->flatMap(fn ($plan) => $plan->disciplines);

        return $disciplines->filter(function (CurriculumDiscipline $discipline) use ($group) {
            return $this->getRemainingHours($group, $discipline) > 0;
        })->values();
    }

    public function getHoursDeficitReport(int $departmentId): array
    {
        $groups = Group::where('department_id', $departmentId)
            ->where('is_active', true)
            ->get();

        $report = [];

        foreach ($groups as $group) {
            $disciplines = $group->curriculumPlans()
                ->with('disciplines.semesters')
                ->get()
                ->flatMap(fn ($plan) => $plan->disciplines);

            foreach ($disciplines as $discipline) {
                $remaining = $this->getRemainingHours($group, $discipline);

                if ($remaining <= 0) {
                    continue;
                }

                $report[] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'discipline_id' => $discipline->id,
                    'discipline_name' => $discipline->name,
                    'remaining_hours' => $remaining,
                ];
            }
        }

        return $report;
    }

    private function getSemesterNumberForDate(string $date, ScheduleVersion $version): int
    {
        $lessonDate = Carbon::parse($date);
        $yearStart = $version->date_from
            ? Carbon::parse($version->date_from)
            : Carbon::createFromDate($lessonDate->year, 9, 1);

        return $lessonDate->lessThan($yearStart->copy()->addMonths(6)) ? 1 : 2;
    }
}
