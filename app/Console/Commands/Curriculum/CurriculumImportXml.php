<?php

declare(strict_types=1);

namespace App\Console\Commands\Curriculum;

use App\Models\AcademicYear;
use App\Models\Specialty;
use App\Services\Curriculum\CurriculumImportService;
use Illuminate\Console\Command;

class CurriculumImportXml extends Command
{
    protected $signature = 'curriculum:import-xml
        {file : Путь к XML файлу}
        {specialty_id : ID специальности}
        {academic_year_id : ID учебного года}
        {--dry-run : Проверить без импорта}';

    protected $description = 'Импортирует учебный план из XML';

    public function handle(CurriculumImportService $importService): int
    {
        $file = $this->argument('file');
        $specialtyId = (int) $this->argument('specialty_id');
        $academicYearId = (int) $this->argument('academic_year_id');

        if (! file_exists($file) || ! is_readable($file)) {
            $this->components->error("Файл не найден или недоступен: {$file}");

            return Command::FAILURE;
        }

        $specialty = Specialty::find($specialtyId);

        if ($specialty === null) {
            $this->components->error("Специальность #{$specialtyId} не найдена.");

            return Command::FAILURE;
        }

        $academicYear = AcademicYear::find($academicYearId);

        if ($academicYear === null) {
            $this->components->error("Учебный год #{$academicYearId} не найден.");

            return Command::FAILURE;
        }

        if ($this->option('dry-run')) {
            $preview = $importService->preview($file);

            if ($preview->hasErrors()) {
                $this->components->error('Ошибки валидации:');
                foreach ($preview->errors as $error) {
                    $this->components->twoColumnDetail('Ошибка', $error);
                }

                return Command::FAILURE;
            }

            $this->components->info('Предварительный просмотр:');
            $this->components->twoColumnDetail('Специальность', $preview->specialty['name'] ?? '—');
            $this->components->twoColumnDetail('Дисциплин', (string) $preview->getDisciplineCount());
            $this->components->twoColumnDetail('Семестров', (string) $preview->getSemesterCount());

            if ($preview->disciplines !== []) {
                $this->table(
                    ['Название', 'Часы', 'Форма контроля'],
                    array_map(fn (array $d) => [
                        $d['name'] ?? '—',
                        $d['total_hours'] ?? '—',
                        $d['control_form'] ?? '—',
                    ], $preview->disciplines),
                );
            }

            $this->components->info('Режим просмотра. Никаких изменений не применено.');

            return Command::SUCCESS;
        }

        $result = $importService->import($file, $specialtyId, $academicYearId);

        if (! $result->success) {
            $this->components->error('Ошибка импорта:');
            foreach ($result->errors as $error) {
                $this->components->twoColumnDetail('Ошибка', $error);
            }

            return Command::FAILURE;
        }

        $this->components->info('Импорт завершён:');
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Импортировано дисциплин', (string) $result->disciplinesImported],
                ['Импортировано семестров', (string) $result->semestersImported],
                ['ID учебного плана', $result->curriculumPlanId !== null ? "#{$result->curriculumPlanId}" : '—'],
            ],
        );

        return Command::SUCCESS;
    }
}
