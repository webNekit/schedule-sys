<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\CurriculumPractice;
use App\Models\Group;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyUpcomingPractices extends Command
{
    protected $signature = 'schedule:notify-practices';

    protected $description = 'Отправляет уведомления за 7 дней до начала практик у групп';

    public function handle(): int
    {
        // Дата ровно через 7 дней от сегодня
        $targetDate = Carbon::today()->addDays(7)->format('Y-m-d');

        // Находим все практики, которые начинаются в этот день
        $practices = CurriculumPractice::where('start_date', $targetDate)->with('curriculumPlan.specialty')->get();

        if ($practices->isEmpty()) {
            $this->info("На {$targetDate} начало новых практик не запланировано.");

            return self::SUCCESS;
        }

        // Собираем тех, кого нужно уведомить (Диспетчеры и Админы)
        $usersToNotify = User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['admin', 'superadmin', 'dispatcher']);
        })->get();

        if ($usersToNotify->isEmpty()) {
            $this->warn('Нет пользователей с ролями admin/dispatcher для получения уведомлений.');

            return self::SUCCESS;
        }

        $notificationsSent = 0;

        foreach ($practices as $practice) {
            // Находим группы, у которых этот учебный план и соответствующий курс
            $groups = Group::active()
                ->where('current_course', $practice->course_number)
                ->whereHas('curriculumAssignments', function ($q) use ($practice) {
                    $q->where('curriculum_plan_id', $practice->curriculum_plan_id)
                        ->where('is_active', true);
                })->get();

            if ($groups->isEmpty()) {
                continue;
            }

            $groupNames = $groups->pluck('name')->implode(', ');
            $typeLabel = $practice->type === 'edu_practice' ? 'Учебная практика' : 'Производственная практика';
            $title = '⚠️ Внимание: Скоро практика!';
            $message = "Через 7 дней ({$targetDate}) начинается {$typeLabel} ({$practice->symbol}) у групп: {$groupNames}. Не забудьте скорректировать расписание.";

            foreach ($usersToNotify as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'practice_reminder',
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false,
                    'created_at' => now(),
                ]);
                $notificationsSent++;
            }
        }

        $this->info("Сгенерировано {$notificationsSent} уведомлений о практиках на {$targetDate}.");

        return self::SUCCESS;
    }
}
