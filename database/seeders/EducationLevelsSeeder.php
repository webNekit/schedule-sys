<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationLevelsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('education_levels')->exists()) {
            return;
        }

        DB::table('education_levels')->insert([
            ['name' => 'СПО базовой подготовки', 'study_years' => 4],
            ['name' => 'СПО углублённой подготовки', 'study_years' => 4],
            ['name' => 'ВПО бакалавриат', 'study_years' => 4],
        ]);
    }
}
