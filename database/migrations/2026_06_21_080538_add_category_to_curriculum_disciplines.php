<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_disciplines', function (Blueprint $table) {
            // Категория дисциплины для генератора: general | pe | practice | exam.
            // Заменяет распознавание по названию (regex).
            $table->string('category', 20)->default('general')->after('discipline_type');
        });

        // Бэкафилл по текущей regex-эвристике, чтобы не сломать существующие данные.
        foreach (DB::table('curriculum_disciplines')->select('id', 'name')->get() as $disc) {
            $name = (string) $disc->name;
            $category = 'general';
            if (preg_match('/физическая\s+культура|физкультур/iu', $name)) {
                $category = 'pe';
            } elseif (preg_match('/экзамен|зач[её]т|аттестац|сессия/iu', $name)) {
                $category = 'exam';
            } elseif (preg_match('/практика/iu', $name)) {
                $category = 'practice';
            }

            if ($category !== 'general') {
                DB::table('curriculum_disciplines')->where('id', $disc->id)->update(['category' => $category]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('curriculum_disciplines', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
