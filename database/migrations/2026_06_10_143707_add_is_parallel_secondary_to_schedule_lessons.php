<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Вторичный урок одного слота: при подгруппах и параллельных занятиях на
     * один слот приходится несколько уроков (по преподавателю/подгруппе на
     * аудиторию). Часы преподавателей считаем каждому, а часы группы по
     * дисциплине — только по первому (основному), чтобы не задваивать.
     */
    public function up(): void
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->boolean('is_parallel_secondary')->default(false)->after('subgroup_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->dropColumn('is_parallel_secondary');
        });
    }
};
