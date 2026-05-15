<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Department;
use App\Models\ScheduleVersion;
use App\Services\Export\ExcelExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleExportExcel extends Command
{
    protected $signature = 'schedule:export-excel
        {version_id : ID версии расписания}
        {department_id : ID кафедры}
        {--output= : Путь для сохранения файла}';

    protected $description = 'Экспортирует расписание в Excel';

    public function handle(ExcelExportService $exportService): int
    {
        $versionId = (int) $this->argument('version_id');
        $departmentId = (int) $this->argument('department_id');

        $version = ScheduleVersion::find($versionId);

        if ($version === null) {
            $this->components->error("Версия #{$versionId} не найдена.");

            return Command::FAILURE;
        }

        $department = Department::find($departmentId);

        if ($department === null) {
            $this->components->error("Кафедра #{$departmentId} не найдена.");

            return Command::FAILURE;
        }

        $dateFrom = $version->date_from instanceof Carbon
            ? $version->date_from
            : Carbon::parse($version->date_from);

        $dateTo = $version->date_to instanceof Carbon
            ? $version->date_to
            : Carbon::parse($version->date_to);

        try {
            $filePath = $exportService->exportScheduleByDepartment(
                departmentId: $departmentId,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                versionId: $versionId,
            );
        } catch (\Exception $e) {
            $this->components->error("Ошибка экспорта: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $this->components->info('Экспорт выполнен успешно.');
        $this->components->twoColumnDetail('Файл', $filePath);

        return Command::SUCCESS;
    }
}
