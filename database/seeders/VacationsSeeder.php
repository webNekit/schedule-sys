<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VacationsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('vacations')->exists()) {
            return;
        }

        $vacations = [
            // 2024-2025 (academic_year_id = 1)
            ['academic_year_id' => 1, 'name' => 'Зимние каникулы', 'start_date' => '2024-12-30', 'end_date' => '2025-01-12', 'duration_days' => 14],
            ['academic_year_id' => 1, 'name' => 'Весенние каникулы', 'start_date' => '2025-03-24', 'end_date' => '2025-03-30', 'duration_days' => 7],
            ['academic_year_id' => 1, 'name' => 'Майские каникулы', 'start_date' => '2025-05-01', 'end_date' => '2025-05-10', 'duration_days' => 10],
            ['academic_year_id' => 1, 'name' => 'Летние каникулы', 'start_date' => '2025-07-01', 'end_date' => '2025-08-31', 'duration_days' => 62],

            // 2025-2026 (academic_year_id = 2)
            ['academic_year_id' => 2, 'name' => 'Зимние каникулы', 'start_date' => '2025-12-29', 'end_date' => '2026-01-11', 'duration_days' => 14],
            ['academic_year_id' => 2, 'name' => 'Весенние каникулы', 'start_date' => '2026-03-23', 'end_date' => '2026-03-29', 'duration_days' => 7],
            ['academic_year_id' => 2, 'name' => 'Майские каникулы', 'start_date' => '2026-05-01', 'end_date' => '2026-05-10', 'duration_days' => 10],
            ['academic_year_id' => 2, 'name' => 'Летние каникулы', 'start_date' => '2026-07-01', 'end_date' => '2026-08-31', 'duration_days' => 62],

            // 2026-2027 (academic_year_id = 3)
            ['academic_year_id' => 3, 'name' => 'Зимние каникулы', 'start_date' => '2026-12-28', 'end_date' => '2027-01-10', 'duration_days' => 14],
            ['academic_year_id' => 3, 'name' => 'Весенние каникулы', 'start_date' => '2027-03-22', 'end_date' => '2027-03-28', 'duration_days' => 7],
            ['academic_year_id' => 3, 'name' => 'Майские каникулы', 'start_date' => '2027-05-01', 'end_date' => '2027-05-10', 'duration_days' => 10],
            ['academic_year_id' => 3, 'name' => 'Летние каникулы', 'start_date' => '2027-07-01', 'end_date' => '2027-08-31', 'duration_days' => 62],
        ];

        DB::table('vacations')->insert($vacations);
    }
}
