<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Дни работы спорткомплекса теперь выводятся из недельного расписания
     * (sport_complex_schedule), отдельная настройка не нужна.
     */
    public function up(): void
    {
        DB::table('system_settings')->where('key', 'sport_complex_working_days')->delete();
    }

    public function down(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'sport_complex_working_days'],
            [
                'key' => 'sport_complex_working_days',
                'value' => json_encode([1, 2, 3, 4, 5]),
                'type' => 'json',
                'group' => 'schedule',
                'label' => 'Дни работы спортивного комплекса',
                'description' => 'Дни недели (1=Пн ... 6=Сб), когда доступен спорткомплекс для выезда групп',
                'is_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
