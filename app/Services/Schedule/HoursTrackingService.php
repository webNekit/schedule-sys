<?php

declare(strict_types=1);

namespace App\Services\Schedule;

use App\Models\AcademicYear;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumSemester;
use App\Models\Group;
use App\Models\HoursTracking;
use App\Models\LessonType;
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
            ->where('counts_for_group', true)
            ->sum('hours_conducted');

        return max(0, $totalHours - (int) $conductedHours);
    }

    /**
     * Возвращает остаток часов конкретного типа (lecture / practice / lab).
     * Маппинг: lecture → hours_lecture, practice → hours_practice, lab → hours_lab.
     */
    public function getRemainingHoursByType(int $groupId, int $disciplineId, int $semesterNum, string $typeCode): int
    {
        $column = match ($typeCode) {
            'practice' => 'hours_practice',
            'lab' => 'hours_lab',
            default => 'hours_lecture',
        };

        $totalHours = (int) CurriculumSemester::where('discipline_id', $disciplineId)
            ->where('semester_number', $semesterNum)
            ->sum($column);

        if ($totalHours <= 0) {
            return 0;
        }

        // Считаем проведённые часы данного типа из hours_tracking
        $lessonTypeId = LessonType::where('code', $typeCode)->value('id');

        $conductedHours = HoursTracking::where('group_id', $groupId)
            ->where('discipline_id', $disciplineId)
            ->where('semester_id', function ($q) use ($disciplineId, $semesterNum) {
                $q->select('id')->from('curriculum_semesters')
                    ->where('discipline_id', $disciplineId)
                    ->where('semester_number', $semesterNum);
            })
            ->when($lessonTypeId, fn ($q) => $q->where('lesson_type_id', $lessonTypeId))
            ->where('is_cancelled', false)
            ->where('counts_for_group', true)
            ->sum('hours_conducted');

        return max(0, $totalHours - (int) $conductedHours);
    }

    public function getRemainingHoursByIds(int $groupId, int $disciplineId, int $semesterNum): int
    {
        $totalHours = CurriculumSemester::where('discipline_id', $disciplineId)
            ->where('semester_number', $semesterNum)
            ->sum('hours_total');

        $conductedHours = HoursTracking::where('group_id', $groupId)
            ->where('discipline_id', $disciplineId)
            ->where('semester_id', function ($q) use ($disciplineId, $semesterNum) {
                $q->select('id')->from('curriculum_semesters')
                    ->where('discipline_id', $disciplineId)
                    ->where('semester_number', $semesterNum);
            })
            ->where('is_cancelled', false)
            ->where('counts_for_group', true)
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
            ->where('counts_for_group', true)
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
            $this->trackLesson($lesson, $version->academic_year_id);
        }
    }

    /**
     * Создаёт запись учёта часов по уроку (если её ещё нет активной).
     *
     * Единая точка начисления часов: используется при публикации версии,
     * в обсервере уроков (создание/замена) и при пересчёте из расписания —
     * чтобы расчёт семестра и значения полей были одинаковыми во всех путях.
     */
    public function trackLesson(ScheduleLesson $lesson, ?int $academicYearId = null): ?HoursTracking
    {
        if ($lesson->status === 'cancelled') {
            return null;
        }
        if (! $lesson->teacher_id || ! $lesson->group_id || ! $lesson->discipline_id || ! $lesson->lesson_type_id || ! $lesson->date) {
            return null;
        }

        // Уже есть активная (непогашенная) запись по этому уроку — не дублируем.
        $hasActive = HoursTracking::where('schedule_lesson_id', $lesson->id)
            ->where('is_cancelled', false)
            ->exists();
        if ($hasActive) {
            return null;
        }

        $semesterId = $this->resolveSemesterId($lesson->group_id, $lesson->discipline_id, $lesson->date);
        if (! $semesterId) {
            return null;
        }

        return HoursTracking::create([
            'group_id' => $lesson->group_id,
            'discipline_id' => $lesson->discipline_id,
            'teacher_id' => $lesson->teacher_id,
            'semester_id' => $semesterId,
            'academic_year_id' => $academicYearId
                ?? $lesson->version?->academic_year_id
                ?? AcademicYear::where('is_current', true)->value('id'),
            'lesson_type_id' => $lesson->lesson_type_id,
            'date' => $lesson->date,
            'hours_conducted' => 2, // Каждая пара — 2 часа
            // Часы группы по дисциплине — только по основному уроку слота,
            // часы каждого преподавателя — отдельно (подгруппы/параллель).
            'counts_for_group' => ! $lesson->is_parallel_secondary,
            'schedule_lesson_id' => $lesson->id,
            'is_cancelled' => false,
        ]);
    }

    /**
     * Единый расчёт семестра урока: по абсолютному номеру семестра группы
     * (Group::getCurrentSemester), а не по делению года пополам.
     */
    public function resolveSemesterId(?int $groupId, ?int $disciplineId, mixed $date): ?int
    {
        if (! $groupId || ! $disciplineId) {
            return null;
        }

        $group = Group::find($groupId);
        if (! $group) {
            return null;
        }

        $semesterNum = $group->getCurrentSemester($date instanceof Carbon ? $date : Carbon::parse($date));

        return CurriculumSemester::where('discipline_id', $disciplineId)
            ->where('semester_number', $semesterNum)
            ->value('id');
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
}
