<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Services\Schedule\ScheduleGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleGenerate extends Command
{
    protected $signature = 'schedule:generate
        {period : day|week|month}
        {date : Дата в формате Y-m-d}
        {--groups=* : ID групп}
        {--department= : ID кафедры}
        {--publish : Сразу опубликовать}';

    protected $description = 'Генерирует расписание на указанный период';

    public function handle(ScheduleGeneratorService $generator): int
    {
        $period = $this->argument('period');
        $date = $this->argument('date');
        $groupIds = $this->option('groups');

        if (! in_array($period, ['day', 'week', 'month'], true)) {
            $this->components->error('Период должен быть: day, week или month.');

            return Command::FAILURE;
        }

        try {
            $carbonDate = Carbon::parse($date);
        } catch (\Exception $e) {
            $this->components->error("Некорректная дата: {$date}. Используйте формат Y-m-d.");

            return Command::FAILURE;
        }

        $result = match ($period) {
            'day' => $generator->generateForDay($carbonDate, $groupIds),
            'week' => $generator->generateForWeek($carbonDate, $groupIds),
            'month' => $generator->generateForMonth((int) $carbonDate->year, (int) $carbonDate->month, $groupIds),
        };

        if (! $result->success) {
            $this->components->error($result->message);

            return Command::FAILURE;
        }

        $this->components->info('Генерация завершена:');
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Всего занятий', (string) $result->totalLessons],
                ['Конфликтов', (string) $result->conflicts],
                ['Версия', $result->version !== null ? "#{$result->version->id}" : '—'],
                ['Статус', $result->version?->status ?? '—'],
            ],
        );

        if ($this->option('publish') && $result->version !== null) {
            $result->version->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            $this->components->info("Версия #{$result->version->id} опубликована.");
        }

        return Command::SUCCESS;
    }
}
