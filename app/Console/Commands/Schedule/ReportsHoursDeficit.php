<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Department;
use App\Services\Schedule\HoursTrackingService;
use Illuminate\Console\Command;

class ReportsHoursDeficit extends Command
{
    protected $signature = 'reports:hours-deficit
        {--department= : ID кафедры}
        {--academic-year= : ID учебного года}';

    protected $description = 'Отчёт по дефициту часов по кафедре';

    public function handle(HoursTrackingService $hoursTracking): int
    {
        $departmentId = $this->option('department');
        $academicYearId = $this->option('academic-year');

        if ($departmentId === null) {
            $this->components->error('Укажите ID кафедры через --department.');

            return Command::FAILURE;
        }

        $department = Department::find((int) $departmentId);

        if ($department === null) {
            $this->components->error("Кафедра #{$departmentId} не найдена.");

            return Command::FAILURE;
        }

        $report = $hoursTracking->getHoursDeficitReport((int) $departmentId);

        if ($report === []) {
            $this->components->info("Дефицит часов по кафедре {$department->name} не найден.");

            return Command::SUCCESS;
        }

        $this->components->info("Отчёт по дефициту часов: {$department->name}");

        $this->table(
            ['Группа', 'Дисциплина', 'Осталось часов'],
            array_map(fn (array $row) => [
                $row['group_name'],
                $row['discipline_name'],
                (string) $row['remaining_hours'],
            ], $report),
        );

        $totalDeficit = array_sum(array_column($report, 'remaining_hours'));
        $this->components->twoColumnDetail('Всего групп', (string) count(array_unique(array_column($report, 'group_id'))));
        $this->components->twoColumnDetail('Всего дисциплин с дефицитом', (string) count($report));
        $this->components->twoColumnDetail('Общий дефицит часов', (string) $totalDeficit);

        return Command::SUCCESS;
    }
}
