<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\CurriculumPractice;
use App\Models\Group;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class NotifyUpcomingPractices extends Command
{
    protected $signature = 'schedule:notify-practices';

    protected $description = 'За 7 дней уведомляет об уходе групп на практику (УП/ПП) и о возвращении с неё';

    /** За сколько дней предупреждать. */
    private const LEAD_DAYS = 7;

    public function handle(): int
    {
        $target = Carbon::today()->addDays(self::LEAD_DAYS);

        $usersToNotify = User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['admin', 'superadmin', 'dispatcher']);
        })->get();

        if ($usersToNotify->isEmpty()) {
            $this->warn('Нет пользователей с ролями admin/dispatcher для уведомлений.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach (CurriculumPractice::with('curriculumPlan.specialty')->get() as $practice) {
            // Практики хранятся в году плана, а применяются к текущему курсу
            // ежегодно — нормализуем даты к учебному году, в который попадает target.
            [$normStart, $normEnd] = $this->normalizeToAcademicYear($practice, $target);
            $returnDate = $normEnd->copy()->addDay();

            if ($normStart->isSameDay($target)) {
                $event = 'leave';
                $eventDate = $normStart;
            } elseif ($returnDate->isSameDay($target)) {
                $event = 'return';
                $eventDate = $returnDate;
            } else {
                continue;
            }

            $groups = $this->groupsForPractice($practice);
            if ($groups->isEmpty()) {
                continue;
            }

            $dedupKey = "practice:{$practice->id}:{$event}:{$eventDate->format('Y-m-d')}";

            // Уже уведомляли об этом событии — пропускаем (защита от повторных запусков).
            if (Notification::where('type', 'practice_reminder')->where('data->dedup_key', $dedupKey)->exists()) {
                continue;
            }

            [$title, $message] = $this->buildContent($practice, $event, $eventDate, $groups);

            foreach ($usersToNotify as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'practice_reminder',
                    'title' => $title,
                    'message' => $message,
                    'data' => [
                        'dedup_key' => $dedupKey,
                        'practice_id' => $practice->id,
                        'event' => $event,
                        'date' => $eventDate->format('Y-m-d'),
                        'group_ids' => $groups->pluck('id')->all(),
                    ],
                    'is_read' => false,
                    'created_at' => now(),
                ]);
                $sent++;
            }
        }

        $this->info("Готово. Создано уведомлений: {$sent} (целевая дата {$target->format('d.m.Y')}).");

        return self::SUCCESS;
    }

    /**
     * Нормализует даты практики к учебному году, в который попадает $reference.
     *
     * @return array{0: Carbon, 1: Carbon} [начало, конец]
     */
    private function normalizeToAcademicYear(CurriculumPractice $practice, Carbon $reference): array
    {
        $start = Carbon::parse($practice->start_date);
        $end = Carbon::parse($practice->end_date);

        // Учебный год начинается 1 сентября.
        $practiceYear = $start->month < 9 ? $start->year - 1 : $start->year;
        $referenceYear = $reference->month < 9 ? $reference->year - 1 : $reference->year;
        $yearDiff = $referenceYear - $practiceYear;

        return [$start->copy()->addYears($yearDiff), $end->copy()->addYears($yearDiff)];
    }

    /**
     * Активные группы, идущие на эту практику (нужный план и курс).
     *
     * @return Collection<int, Group>
     */
    private function groupsForPractice(CurriculumPractice $practice): Collection
    {
        return Group::active()
            ->where('current_course', $practice->course_number)
            ->whereHas('curriculumAssignments', function ($q) use ($practice) {
                $q->where('curriculum_plan_id', $practice->curriculum_plan_id)
                    ->where('is_active', true);
            })->get();
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @return array{0: string, 1: string}
     */
    private function buildContent(CurriculumPractice $practice, string $event, Carbon $eventDate, Collection $groups): array
    {
        $typeLabel = $practice->type === 'edu_practice' ? 'Учебная практика' : 'Производственная практика';
        $symbol = $practice->symbol ? " ({$practice->symbol})" : '';
        $groupNames = $groups->pluck('name')->implode(', ');
        $dateStr = $eventDate->format('d.m.Y');

        if ($event === 'leave') {
            return [
                '⚠️ Скоро практика',
                'Через '.self::LEAD_DAYS." дней ({$dateStr}) группы {$groupNames} уходят на: {$typeLabel}{$symbol}. Не забудьте скорректировать расписание.",
            ];
        }

        return [
            '🔔 Возвращение с практики',
            'Через '.self::LEAD_DAYS." дней ({$dateStr}) группы {$groupNames} возвращаются с практики ({$typeLabel}{$symbol}) к обычным занятиям.",
        ];
    }
}
