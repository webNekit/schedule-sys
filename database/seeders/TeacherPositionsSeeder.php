<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherPositionsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('teacher_positions')->exists()) {
            return;
        }

        DB::table('teacher_positions')->insert([
            ['name' => 'Преподаватель', 'max_hours_per_week' => 36],
            ['name' => 'Старший преподаватель', 'max_hours_per_week' => 36],
            ['name' => 'Мастер производственного обучения', 'max_hours_per_week' => 40],
            ['name' => 'Заведующий кафедрой', 'max_hours_per_week' => 24],
            ['name' => 'Методист', 'max_hours_per_week' => 40],
            ['name' => 'Педагог дополнительного образования', 'max_hours_per_week' => 36],
        ]);
    }
}
