<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Группа может занимать несколько пар в спорткомплексе (например 1-ю и 2-ю).
     * Снимаем уникальность по группе, оставляем уникальность по ячейке.
     */
    public function up(): void
    {
        Schema::table('sport_complex_schedule', function (Blueprint $table) {
            $table->dropUnique(['group_id']);
            $table->unique(['group_id', 'weekday', 'lesson_number']);
        });
    }

    public function down(): void
    {
        Schema::table('sport_complex_schedule', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'weekday', 'lesson_number']);
            $table->unique('group_id');
        });
    }
};
