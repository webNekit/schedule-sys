<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ControlFormsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('control_forms')->exists()) {
            return;
        }

        DB::table('control_forms')->insert([
            ['name' => 'Зачёт'],
            ['name' => 'Экзамен'],
            ['name' => 'Дифференцированный зачёт'],
            ['name' => 'Курсовой проект'],
            ['name' => 'Курсовая работа'],
            ['name' => 'Контрольная работа'],
        ]);
    }
}
