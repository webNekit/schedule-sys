<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Переименовывает правило «дни без проверки минимума» в «дни с обязательной проверкой»
     * и инвертирует список дней: было — какие дни пропускать; стало — в какие проверять.
     */
    public function up(): void
    {
        $rows = DB::table('scheduling_rules')->where('key', 'min_lessons_exempt_weekdays')->get();

        foreach ($rows as $row) {
            $exempt = json_decode($row->params ?? '[]', true);
            $exemptDays = is_array($exempt['days'] ?? null) ? array_map('intval', $exempt['days']) : [6];

            // Проверять во все будни и субботу, кроме ранее исключённых дней.
            $checkDays = array_values(array_diff([1, 2, 3, 4, 5, 6], $exemptDays));

            DB::table('scheduling_rules')->where('id', $row->id)->update([
                'key' => 'min_lessons_check_weekdays',
                'params' => json_encode(['days' => $checkDays], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('scheduling_rules')->where('key', 'min_lessons_check_weekdays')->get();

        foreach ($rows as $row) {
            $check = json_decode($row->params ?? '[]', true);
            $checkDays = is_array($check['days'] ?? null) ? array_map('intval', $check['days']) : [1, 2, 3, 4, 5];

            $exemptDays = array_values(array_diff([1, 2, 3, 4, 5, 6], $checkDays));

            DB::table('scheduling_rules')->where('id', $row->id)->update([
                'key' => 'min_lessons_exempt_weekdays',
                'params' => json_encode(['days' => $exemptDays], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }
};
