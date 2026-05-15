<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (DB::table('system_settings')->exists()) {
            return;
        }

        DB::table('system_settings')->insert([
            ['key' => 'college_name', 'value' => 'Государственное бюджетное профессиональное образовательное учреждение', 'type' => 'string', 'group' => 'general', 'label' => 'Название учебного заведения'],
            ['key' => 'college_short_name', 'value' => 'ГБПОУ Колледж', 'type' => 'string', 'group' => 'general', 'label' => 'Краткое название'],
            ['key' => 'college_address', 'value' => 'г. Москва, ул. Примерная, д. 1', 'type' => 'string', 'group' => 'general', 'label' => 'Адрес'],
            ['key' => 'schedule_generation_max_retries', 'value' => '100', 'type' => 'integer', 'group' => 'generation', 'label' => 'Максимум попыток генерации'],
            ['key' => 'schedule_min_lessons_per_day', 'value' => '3', 'type' => 'integer', 'group' => 'generation', 'label' => 'Минимум пар в день'],
            ['key' => 'schedule_max_lessons_per_day', 'value' => '5', 'type' => 'integer', 'group' => 'generation', 'label' => 'Максимум пар в день'],
            ['key' => 'schedule_hours_per_lesson', 'value' => '2', 'type' => 'integer', 'group' => 'generation', 'label' => 'Часов на одну пару'],
            ['key' => 'working_days_course_1_2', 'value' => '[1,2,3,4,5]', 'type' => 'json', 'group' => 'generation', 'label' => 'Рабочие дни для 1-2 курсов'],
            ['key' => 'working_days_course_3_4', 'value' => '[2,3,4,5,6]', 'type' => 'json', 'group' => 'generation', 'label' => 'Рабочие дни для 3-4 курсов'],
        ]);
    }
}
