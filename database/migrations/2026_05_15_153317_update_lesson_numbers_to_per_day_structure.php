<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $courses = [1, 2, 3, 4];

        foreach ($courses as $course) {
            $setting = DB::table('system_settings')
                ->where('key', "lesson_numbers_course_{$course}")
                ->first();

            if (! $setting) {
                continue;
            }

            $currentValue = json_decode($setting->value, true);

            if (is_array($currentValue) && isset($currentValue[1]) && is_array($currentValue[1])) {
                continue;
            }

            $workingDaysKey = $course <= 2 ? 'working_days_course_1_2' : 'working_days_course_3_4';
            $wdSetting = DB::table('system_settings')
                ->where('key', $workingDaysKey)
                ->first();

            $workingDays = $wdSetting ? json_decode($wdSetting->value, true) : ($course <= 2 ? [1, 2, 3, 4, 5] : [2, 3, 4, 5, 6]);

            if (! is_array($currentValue) || $currentValue === []) {
                $currentValue = $course <= 2 ? [1, 2, 3, 4, 5] : [3, 4, 5, 6, 7];
            }

            $perDay = [];
            foreach ($workingDays as $day) {
                $perDay[(string) $day] = array_values($currentValue);
            }

            DB::table('system_settings')
                ->where('key', "lesson_numbers_course_{$course}")
                ->update(['value' => json_encode($perDay, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        $courses = [1, 2, 3, 4];

        foreach ($courses as $course) {
            $setting = DB::table('system_settings')
                ->where('key', "lesson_numbers_course_{$course}")
                ->first();

            if (! $setting) {
                continue;
            }

            $currentValue = json_decode($setting->value, true);

            if (! is_array($currentValue)) {
                continue;
            }

            $firstKey = array_key_first($currentValue);
            $flatArray = is_array($currentValue[$firstKey]) ? $currentValue[$firstKey] : [];

            DB::table('system_settings')
                ->where('key', "lesson_numbers_course_{$course}")
                ->update(['value' => json_encode($flatArray, JSON_UNESCAPED_UNICODE)]);
        }
    }
};
