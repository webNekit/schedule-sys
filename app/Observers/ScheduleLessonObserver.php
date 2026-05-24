<?php

namespace App\Observers;

use App\Models\CurriculumSemester;
use App\Models\Group;
use App\Models\HoursTracking;
use App\Models\ScheduleLesson;
use Carbon\Carbon;

class ScheduleLessonObserver
{
    public function updated(ScheduleLesson $lesson): void
    {
        $version = $lesson->version;
        if (! $version || $version->status !== 'published') {
            return;
        }

        // Если урок был отменен или заменен
        $isCancelled = $lesson->status === 'cancelled';

        $tracking = HoursTracking::where('schedule_lesson_id', $lesson->id)->first();

        // 9. ВОЗВРАТ ЧАСОВ ПРИ ЗАМЕНЕ ИЛИ ОТМЕНЕ
        if ($tracking) {
            // Если поменяли препода, обнуляем старую запись
            if ($tracking->teacher_id !== $lesson->teacher_id || $isCancelled) {
                $tracking->update(['is_cancelled' => true, 'notes' => 'Замена или отмена']);
            }
        }

        // Если назначили нового препода, создаем ему часы
        if (! $isCancelled && $lesson->teacher_id && $lesson->group_id && $lesson->discipline_id && $lesson->lesson_type_id && $lesson->date && (! $tracking || $tracking->teacher_id !== $lesson->teacher_id)) {
            $semesterId = $this->resolveSemesterId($lesson->group_id, $lesson->discipline_id, $lesson->date);
            if ($semesterId) {
                HoursTracking::create([
                    'group_id' => $lesson->group_id,
                    'discipline_id' => $lesson->discipline_id,
                    'teacher_id' => $lesson->teacher_id,
                    'semester_id' => $semesterId,
                    'academic_year_id' => $version->academic_year_id,
                    'lesson_type_id' => $lesson->lesson_type_id,
                    'date' => $lesson->date,
                    'hours_conducted' => 2, // 1 пара = 2 часа
                    'schedule_lesson_id' => $lesson->id,
                    'is_cancelled' => false,
                ]);
            }
        }
    }

    public function deleted(ScheduleLesson $lesson): void
    {
        // Возвращаем часы при удалении
        HoursTracking::where('schedule_lesson_id', $lesson->id)->update(['is_cancelled' => true, 'notes' => 'Пара удалена']);
    }

    private function resolveSemesterId(?int $groupId, ?int $disciplineId, mixed $date): ?int
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
            ->first()?->id;
    }
}
