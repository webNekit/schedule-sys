<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaysSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('holidays')->exists()) {
            return;
        }

        $holidays = [];

        $baseHolidays = [
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Новый год', 'type' => 'national'],
            ['name' => 'Рождество', 'type' => 'national'],
            ['name' => 'День защитника Отечества', 'type' => 'national'],
            ['name' => 'Международный женский день', 'type' => 'national'],
            ['name' => 'Праздник Весны и Труда', 'type' => 'national'],
            ['name' => 'День Победы', 'type' => 'national'],
            ['name' => 'День России', 'type' => 'national'],
            ['name' => 'День народного единства', 'type' => 'national'],
        ];

        $datesByYear = [
            2024 => ['2024-01-01', '2024-01-02', '2024-01-03', '2024-01-04', '2024-01-05', '2024-01-06', '2024-01-08', '2024-01-07', '2024-02-23', '2024-03-08', '2024-05-01', '2024-05-09', '2024-06-12', '2024-11-04'],
            2025 => ['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04', '2025-01-05', '2025-01-06', '2025-01-08', '2025-01-07', '2025-02-23', '2025-03-08', '2025-05-01', '2025-05-09', '2025-06-12', '2025-11-04'],
            2026 => ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04', '2026-01-05', '2026-01-06', '2026-01-08', '2026-01-07', '2026-02-23', '2026-03-08', '2026-05-01', '2026-05-09', '2026-06-12', '2026-11-04'],
            2027 => ['2027-01-01', '2027-01-02', '2027-01-03', '2027-01-04', '2027-01-05', '2027-01-06', '2027-01-08', '2027-01-07', '2027-02-23', '2027-03-08', '2027-05-01', '2027-05-09', '2027-06-12', '2027-11-04'],
        ];

        foreach ($datesByYear as $year => $dates) {
            foreach ($dates as $index => $date) {
                $holidays[] = [
                    'date' => $date,
                    'name' => $baseHolidays[$index]['name'],
                    'type' => $baseHolidays[$index]['type'],
                    'year' => $year,
                ];
            }
        }

        DB::table('holidays')->insert($holidays);
    }
}
