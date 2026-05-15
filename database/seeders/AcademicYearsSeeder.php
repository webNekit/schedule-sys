<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicYearsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('academic_years')->exists()) {
            return;
        }

        DB::table('academic_years')->insert([
            [
                'name' => '2024-2025',
                'year_start' => 2024,
                'year_end' => 2025,
                'date_start' => '2024-09-01',
                'date_end' => '2025-08-31',
                'first_semester_start' => '2024-09-01',
                'first_semester_end' => '2024-12-31',
                'second_semester_start' => '2025-01-13',
                'second_semester_end' => '2025-06-30',
                'is_current' => false,
            ],
            [
                'name' => '2025-2026',
                'year_start' => 2025,
                'year_end' => 2026,
                'date_start' => '2025-09-01',
                'date_end' => '2026-08-31',
                'first_semester_start' => '2025-09-01',
                'first_semester_end' => '2025-12-31',
                'second_semester_start' => '2026-01-12',
                'second_semester_end' => '2026-06-30',
                'is_current' => true,
            ],
            [
                'name' => '2026-2027',
                'year_start' => 2026,
                'year_end' => 2027,
                'date_start' => '2026-09-01',
                'date_end' => '2027-08-31',
                'first_semester_start' => '2026-09-01',
                'first_semester_end' => '2026-12-31',
                'second_semester_start' => '2027-01-11',
                'second_semester_end' => '2027-06-30',
                'is_current' => false,
            ],
        ]);
    }
}
