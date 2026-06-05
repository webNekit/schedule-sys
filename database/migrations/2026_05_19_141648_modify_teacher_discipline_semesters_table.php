<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_discipline_semesters', function (Blueprint $table) {
            // Удаляем старый уникальный индекс, так как теперь в одном семестре может быть несколько записей (разные преподаватели)
            $table->dropUnique('td_semester_unique');

            // На самом деле, teacher_discipline_id уже включает teacher_id.
            // Если мы хотим разделить одну дисциплину одного семестра между учителями,
            // нам нужно разрешить несколько записей для одного curriculum_semester_id.
        });
    }

    public function down(): void
    {
        Schema::table('teacher_discipline_semesters', function (Blueprint $table) {
            $table->unique(['teacher_discipline_id', 'curriculum_semester_id'], 'td_semester_unique');
        });
    }
};
