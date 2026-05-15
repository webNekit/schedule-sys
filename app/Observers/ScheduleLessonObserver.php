<?php

namespace App\Observers;

use App\Models\CurriculumSemester;
use App\Models\HoursTracking;
use App\Models\ScheduleLesson;

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
        if (! $isCancelled && (! $tracking || $tracking->teacher_id !== $lesson->teacher_id)) {
            $semesterId = $this->resolveSemesterId($lesson->discipline_id, $lesson->date, $version->academic_year_id);
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

    private function resolveSemesterId(?int $disciplineId, mixed $date, ?int $academicYearId): ?int
    {
        if (! $disciplineId) {
            return null;
        }
        $query = CurriculumSemester::where('discipline_id', $disciplineId);

        return $query->first()?->id;
    }
}
