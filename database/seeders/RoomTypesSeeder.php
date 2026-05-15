<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('room_types')->exists()) {
            return;
        }

        DB::table('room_types')->insert([
            ['name' => 'Учебная аудитория', 'color' => '#94A3B8', 'can_be_shared' => true],
            ['name' => 'Лекционный зал', 'color' => '#3B82F6', 'can_be_shared' => false],
            ['name' => 'Компьютерный класс', 'color' => '#8B5CF6', 'can_be_shared' => true],
            ['name' => 'Лаборатория', 'color' => '#F59E0B', 'can_be_shared' => true],
            ['name' => 'Спортивный зал', 'color' => '#10B981', 'can_be_shared' => false],
            ['name' => 'Мастерская', 'color' => '#D97706', 'can_be_shared' => true],
            ['name' => 'Актовый зал', 'color' => '#EC4899', 'can_be_shared' => false],
            ['name' => 'Библиотека/Читальный зал', 'color' => '#6B7280', 'can_be_shared' => false],
        ]);
    }
}
