<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\ScheduleVersion;
use App\Services\Schedule\ConflictCheckerService;
use Illuminate\Console\Command;

class ScheduleCheckConflicts extends Command
{
    protected $signature = 'schedule:check-conflicts {version_id}';

    protected $description = 'Проверяет конфликты в версии расписания';

    public function handle(ConflictCheckerService $conflictChecker): int
    {
        $versionId = (int) $this->argument('version_id');

        $version = ScheduleVersion::find($versionId);

        if ($version === null) {
            $this->components->error("Версия #{$versionId} не найдена.");

            return Command::FAILURE;
        }

        $conflicts = $conflictChecker->checkVersion($versionId);

        if ($conflicts === []) {
            $this->components->info('Конфликтов не найдено.');

            return Command::SUCCESS;
        }

        $this->components->info('Найдено конфликтов: '.count($conflicts));

        $this->table(
            ['ID', 'Тип', 'Серьёзность', 'Дата', 'Пара', 'Описание'],
            array_map(fn (array $c) => [
                $c['id'],
                $c['conflict_type'],
                $c['severity'],
                $c['date'],
                $c['lesson_number'],
                $c['description'],
            ], $conflicts),
        );

        $resolved = count(array_filter($conflicts, fn (array $c) => $c['is_resolved'] ?? false));
        $unresolved = count($conflicts) - $resolved;

        $this->components->twoColumnDetail('Решено', (string) $resolved);
        $this->components->twoColumnDetail('Не решено', (string) $unresolved);

        return Command::SUCCESS;
    }
}
