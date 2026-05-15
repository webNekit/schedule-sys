<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QualificationTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('qualification_types')->exists()) {
            return;
        }

        $qualifications = [
            'Техник', 'Техник-программист', 'Программист', 'Бухгалтер', 'Юрист',
            'Экономист', 'Менеджер', 'Мастер', 'Технолог', 'Электрик',
            'Механик', 'Строитель', 'Повар-кондитер', 'Парикмахер', 'Медицинская сестра',
            'Фармацевт', 'Дизайнер', 'Архитектор', 'Сварщик', 'Токарь', 'Слесарь',
        ];

        foreach ($qualifications as $name) {
            DB::table('qualification_types')->insert(['name' => $name]);
        }
    }
}
