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
            ['key' => 'college_name', 'value' => 'Государственное бюджетное профессиональное образовательное учреждение', 'type' => 'string', 'group' => 'general', 'label' => 'Название учебного заведения', 'description' => 'Полное название колледжа', 'is_editable' => true],
            ['key' => 'college_short_name', 'value' => 'ГБПОУ Колледж', 'type' => 'string', 'group' => 'general', 'label' => 'Краткое название', 'description' => 'Сокращенное название', 'is_editable' => true],
            ['key' => 'college_address', 'value' => 'г. Москва, ул. Примерная, д. 1', 'type' => 'string', 'group' => 'general', 'label' => 'Адрес', 'description' => 'Юридический адрес', 'is_editable' => true],
            ['key' => 'schedule_generation_max_retries', 'value' => '100', 'type' => 'integer', 'group' => 'generation', 'label' => 'Максимум попыток генерации', 'description' => '', 'is_editable' => false],
            ['key' => 'schedule_min_lessons_per_day', 'value' => '3', 'type' => 'integer', 'group' => 'generation', 'label' => 'Минимум пар в день', 'description' => '', 'is_editable' => false],
            ['key' => 'schedule_max_lessons_per_day', 'value' => '5', 'type' => 'integer', 'group' => 'generation', 'label' => 'Максимум пар в день', 'description' => '', 'is_editable' => false],
            ['key' => 'schedule_hours_per_lesson', 'value' => '2', 'type' => 'integer', 'group' => 'generation', 'label' => 'Часов на одну пару', 'description' => '', 'is_editable' => false],

        ]);
    }
}
