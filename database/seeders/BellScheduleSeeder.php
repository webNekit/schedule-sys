<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BellScheduleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('bell_schedules')->exists()) {
            return;
        }

        DB::table('bell_schedules')->insert([
            // First shift (1-2 courses)
            ['name' => '1 пара (1 смена)', 'shift_number' => 1, 'lesson_number' => 1, 'time_start' => '08:00', 'time_end' => '09:30', 'break_after_minutes' => 10, 'is_active' => true, 'sort_order' => 1],
            ['name' => '2 пара (1 смена)', 'shift_number' => 1, 'lesson_number' => 2, 'time_start' => '09:40', 'time_end' => '11:10', 'break_after_minutes' => 20, 'is_active' => true, 'sort_order' => 2],
            ['name' => '3 пара (1 смена)', 'shift_number' => 1, 'lesson_number' => 3, 'time_start' => '11:30', 'time_end' => '13:00', 'break_after_minutes' => 40, 'is_active' => true, 'sort_order' => 3],
            ['name' => '4 пара (1 смена)', 'shift_number' => 1, 'lesson_number' => 4, 'time_start' => '13:40', 'time_end' => '15:10', 'break_after_minutes' => 10, 'is_active' => true, 'sort_order' => 4],
            ['name' => '5 пара (1 смена)', 'shift_number' => 1, 'lesson_number' => 5, 'time_start' => '15:20', 'time_end' => '16:50', 'break_after_minutes' => 0, 'is_active' => true, 'sort_order' => 5],

            // Second shift (3-4 courses)
            ['name' => '1 пара (2 смена)', 'shift_number' => 2, 'lesson_number' => 1, 'time_start' => '13:40', 'time_end' => '15:10', 'break_after_minutes' => 10, 'is_active' => true, 'sort_order' => 6],
            ['name' => '2 пара (2 смена)', 'shift_number' => 2, 'lesson_number' => 2, 'time_start' => '15:20', 'time_end' => '16:50', 'break_after_minutes' => 10, 'is_active' => true, 'sort_order' => 7],
            ['name' => '3 пара (2 смена)', 'shift_number' => 2, 'lesson_number' => 3, 'time_start' => '17:00', 'time_end' => '18:30', 'break_after_minutes' => 10, 'is_active' => true, 'sort_order' => 8],
            ['name' => '4 пара (2 смена)', 'shift_number' => 2, 'lesson_number' => 4, 'time_start' => '18:40', 'time_end' => '20:10', 'break_after_minutes' => 10, 'is_active' => true, 'sort_order' => 9],
            ['name' => '5 пара (2 смена)', 'shift_number' => 2, 'lesson_number' => 5, 'time_start' => '20:20', 'time_end' => '21:50', 'break_after_minutes' => 0, 'is_active' => true, 'sort_order' => 10],
        ]);
    }
}
