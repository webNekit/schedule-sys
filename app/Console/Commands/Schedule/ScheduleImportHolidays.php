<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleImportHolidays extends Command
{
    protected $signature = 'schedule:import-holidays {year : Год для импорта праздников}';

    protected $description = 'Импортирует государственные праздники России на указанный год';

    public function handle(): int
    {
        $year = (int) $this->argument('year');

        if ($year < 2000 || $year > 2100) {
            $this->components->error('Год должен быть в диапазоне 2000–2100.');

            return Command::FAILURE;
        }

        $holidays = $this->getRussianHolidays($year);

        $imported = 0;
        $skipped = 0;

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date'], 'year' => $year],
                [
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                    'type' => 'public',
                    'year' => $year,
                    'description' => $holiday['description'] ?? null,
                ],
            )->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        $this->components->info("Импорт праздников на {$year} год завершён:");
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Всего праздников', (string) count($holidays)],
                ['Импортировано', (string) $imported],
                ['Пропущено (уже есть)', (string) $skipped],
            ],
        );

        if ($imported > 0) {
            $this->components->info('Добавленные праздники:');
            $this->table(
                ['Дата', 'Название'],
                array_map(fn (array $h) => [$h['date'], $h['name']], $holidays),
            );
        }

        return Command::SUCCESS;
    }

    private function getRussianHolidays(int $year): array
    {
        $holidays = [
            ['date' => "{$year}-01-01", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-02", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-03", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-04", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-05", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-06", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-07", 'name' => 'Рождество Христово', 'description' => 'Рождество Христово'],
            ['date' => "{$year}-01-08", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-02-23", 'name' => 'День защитника Отечества', 'description' => 'День защитника Отечества'],
            ['date' => "{$year}-03-08", 'name' => 'Международный женский день', 'description' => 'Международный женский день'],
            ['date' => "{$year}-05-01", 'name' => 'Праздник Весны и Труда', 'description' => 'Праздник Весны и Труда'],
            ['date' => "{$year}-05-09", 'name' => 'День Победы', 'description' => 'День Победы'],
            ['date' => "{$year}-06-12", 'name' => 'День России', 'description' => 'День России'],
            ['date' => "{$year}-11-04", 'name' => 'День народного единства', 'description' => 'День народного единства'],
        ];

        $this->addWeekendTransfers($holidays, $year);

        usort($holidays, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $holidays;
    }

    private function addWeekendTransfers(array &$holidays, int $year): void
    {
        $dateMap = [];

        foreach ($holidays as $h) {
            $dateMap[$h['date']] = true;
        }

        foreach ($holidays as $h) {
            $date = Carbon::parse($h['date']);
            $dayOfWeek = (int) $date->format('N');

            if ($dayOfWeek === 6 || $dayOfWeek === 7) {
                $nextMonday = $date->copy()->next(Carbon::MONDAY)->toDateString();

                if (! isset($dateMap[$nextMonday])) {
                    $holidays[] = [
                        'date' => $nextMonday,
                        'name' => 'Выходной (перенос)',
                        'description' => "Перенос с {$h['date']} ({$h['name']})",
                    ];

                    $dateMap[$nextMonday] = true;
                }
            }
        }
    }
}
