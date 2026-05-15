<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Holiday;
use App\Services\GroupPromotionService;
use Carbon\Carbon;
use Livewire\Component;

class ActionsWidget extends Component
{
    public function render()
    {
        return view('livewire.admin.actions-widget');
    }

    public function promoteGroups()
    {
        $service = app(GroupPromotionService::class);
        $toPromote = $service->getGroupsForPromotion();
        $toGraduate = $service->getGroupsForGraduation();

        if ($toPromote->isEmpty() && $toGraduate->isEmpty()) {
            session()->flash('info', 'Нет групп для перевода или выпуска.');

            return;
        }

        $result = $service->promoteAllGroups();
        session()->flash('success', "Переведено: {$result['promoted']} групп, Выпущено: {$result['graduated']} групп.");

        $this->dispatch('promotion-complete');
    }

    public function importHolidays()
    {
        $year = (int) now()->year;

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

        session()->flash('success', "Импортировано праздников на {$year} год: {$imported} добавлено, {$skipped} пропущено.");

        $this->dispatch('holidays-imported');
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
