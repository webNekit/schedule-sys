<?php

namespace App\Observers;

use App\Models\HoursTracking;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Services\Schedule\HoursTrackingService;
use Carbon\Carbon;

class ScheduleLessonObserver
{
    public function __construct(private readonly HoursTrackingService $hoursTracking) {}

    /**
     * Новый урок в опубликованной версии (например, назначенная замена) —
     * начисляем часы преподавателю. Без этого заменяющий «отводил бы пару бесплатно».
     */
    public function created(ScheduleLesson $lesson): void
    {
        $version = $this->resolveVersion($lesson);
        if (! $version || $version->status !== 'published') {
            return;
        }

        $this->hoursTracking->trackLesson($lesson, $version->academic_year_id);
    }

    public function updated(ScheduleLesson $lesson): void
    {
        $version = $this->resolveVersion($lesson);
        if (! $version || $version->status !== 'published') {
            return;
        }

        // Если урок был отменен или заменен
        $isCancelled = $lesson->status === 'cancelled';

        $tracking = HoursTracking::where('schedule_lesson_id', $lesson->id)->first();

        // ВОЗВРАТ ЧАСОВ ПРИ ЗАМЕНЕ ИЛИ ОТМЕНЕ:
        // если поменяли препода или урок отменён — гасим старую запись.
        // Но только для ещё не проведённых пар (дата сегодня/в будущем):
        // прошедшую пару преподаватель реально отвёл — его часы не отнимаем.
        if ($tracking && ! $tracking->is_cancelled && $this->isNotYetConducted($lesson)) {
            if ((int) $tracking->teacher_id !== (int) $lesson->teacher_id || $isCancelled) {
                $tracking->update(['is_cancelled' => true, 'notes' => 'Замена или отмена']);
            }
        }

        // Если назначили нового препода — начисляем ему часы (единая точка — сервис).
        if (! $isCancelled) {
            $this->hoursTracking->trackLesson($lesson, $version->academic_year_id);
        }
    }

    public function deleted(ScheduleLesson $lesson): void
    {
        // Возвращаем часы при удалении — только для ещё не проведённых пар.
        // Прошедшую пару преподаватель отвёл, удаление из расписания не отнимает часы.
        if (! $this->isNotYetConducted($lesson)) {
            return;
        }

        HoursTracking::where('schedule_lesson_id', $lesson->id)
            ->update(['is_cancelled' => true, 'notes' => 'Пара удалена']);
    }

    /**
     * Версия урока свежим запросом: связь на модели могла закэшироваться ещё
     * черновиком (до публикации), и тогда обсервер ошибочно считал бы версию
     * неопубликованной и пропускал учёт часов.
     */
    private function resolveVersion(ScheduleLesson $lesson): ?ScheduleVersion
    {
        return $lesson->version_id
            ? ScheduleVersion::find($lesson->version_id)
            : null;
    }

    /**
     * Пара ещё не проведена, если её дата сегодня или в будущем.
     * Прошедшие пары считаются отведёнными — их часы не возвращаются.
     */
    private function isNotYetConducted(ScheduleLesson $lesson): bool
    {
        if (! $lesson->date) {
            return true;
        }

        $date = $lesson->date instanceof Carbon ? $lesson->date : Carbon::parse($lesson->date);

        return $date->startOfDay()->greaterThanOrEqualTo(Carbon::today());
    }
}
