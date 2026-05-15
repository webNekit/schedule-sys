<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('equipment_types')->exists()) {
            return;
        }

        $equipment = [
            'Проектор', 'Интерактивная доска', 'Компьютеры', 'Маркерная доска',
            'Меловая доска', 'Телевизор', 'МФУ/Принтер', 'Лабораторное оборудование',
            '3D-принтер', 'Видеокамера', 'Микроскопы', 'Швейные машины', 'Токарные станки',
        ];

        foreach ($equipment as $name) {
            DB::table('equipment_types')->insert(['name' => $name]);
        }
    }
}
