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
        // Remove old per-course settings
        DB::table('system_settings')
            ->whereIn('key', [
                'weekly_hours_course_1',
                'weekly_hours_course_2',
                'weekly_hours_course_3',
                'weekly_hours_course_4',
            ])
            ->delete();

        // Add single global setting
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'weekly_hours_total'],
            [
                'value' => '36',
                'type' => 'integer',
                'group' => 'schedule',
                'label' => 'Общая нагрузка в неделю (часов)',
                'description' => 'Необходимое количество часов в неделю для всех групп',
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->where('key', 'weekly_hours_total')->delete();
    }
};
