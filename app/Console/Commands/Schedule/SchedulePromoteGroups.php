<?php

declare(strict_types=1);

namespace App\Console\Commands\Schedule;

use App\Models\Group;
use App\Services\GroupPromotionService;
use Illuminate\Console\Command;

class SchedulePromoteGroups extends Command
{
    protected $signature = 'schedule:promote-groups
        {--dry-run : Показать без применения}
        {--force : Без подтверждения}';

    protected $description = 'Переводит группы на следующий курс';

    public function handle(GroupPromotionService $promotionService): int
    {
        $toPromote = $promotionService->getGroupsForPromotion();
        $toGraduate = $promotionService->getGroupsForGraduation();

        if ($toPromote->isEmpty() && $toGraduate->isEmpty()) {
            $this->components->info('Нет групп для перевода или выпуска.');

            return Command::SUCCESS;
        }

        $this->components->twoColumnDetail('Групп к переводу', (string) $toPromote->count());
        $this->components->twoColumnDetail('Групп к выпуску', (string) $toGraduate->count());

        if ($toPromote->isNotEmpty()) {
            $this->components->info('Группы для перевода:');
            $this->table(
                ['ID', 'Группа', 'Курс', 'Специальность'],
                $toPromote->map(fn (Group $g) => [
                    $g->id,
                    $g->name,
                    $g->current_course,
                    $g->specialty?->name ?? '—',
                ]),
            );
        }

        if ($toGraduate->isNotEmpty()) {
            $this->components->info('Группы для выпуска:');
            $this->table(
                ['ID', 'Группа', 'Курс', 'Специальность'],
                $toGraduate->map(fn (Group $g) => [
                    $g->id,
                    $g->name,
                    $g->current_course,
                    $g->specialty?->name ?? '—',
                ]),
            );
        }

        if ($this->option('dry-run')) {
            $this->components->info('Режим просмотра. Никаких изменений не применено.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->components->confirm('Применить перевод и выпуск групп?')) {
            $this->components->info('Операция отменена.');

            return Command::SUCCESS;
        }

        $results = $promotionService->promoteAllGroups();

        $this->components->info('Результаты:');
        $this->table(
            ['Операция', 'Количество'],
            [
                ['Переведено', $results['promoted']],
                ['Выпущено', $results['graduated']],
            ],
        );

        return Command::SUCCESS;
    }
}
