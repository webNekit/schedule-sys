<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'key' => 'weekly_hours_course_1',
                'value' => '36',
                'type' => 'integer',
                'group' => '1 курс',
                'label' => 'Нагрузка в неделю (часов)',
                'description' => 'Необходимое количество часов в неделю для 1 курса',
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'weekly_hours_course_2',
                'value' => '36',
                'type' => 'integer',
                'group' => '2 курс',
                'label' => 'Нагрузка в неделю (часов)',
                'description' => 'Необходимое количество часов в неделю для 2 курса',
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'weekly_hours_course_3',
                'value' => '36',
                'type' => 'integer',
                'group' => '3 курс',
                'label' => 'Нагрузка в неделю (часов)',
                'description' => 'Необходимое количество часов в неделю для 3 курса',
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'weekly_hours_course_4',
                'value' => '36',
                'type' => 'integer',
                'group' => '4 курс',
                'label' => 'Нагрузка в неделю (часов)',
                'description' => 'Необходимое количество часов в неделю для 4 курса',
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'weekly_hours_course_1',
                'weekly_hours_course_2',
                'weekly_hours_course_3',
                'weekly_hours_course_4',
            ])
            ->delete();
    }
};
