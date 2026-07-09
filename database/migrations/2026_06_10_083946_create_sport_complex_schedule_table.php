<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Недельное расписание спорткомплекса: какая группа в какой день и на какой
     * паре приезжает на физкультуру. Источник истины для генератора.
     */
    public function up(): void
    {
        Schema::create('sport_complex_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday'); // 1=Пн ... 5=Пт
            $table->unsignedTinyInteger('lesson_number'); // первая пара сдвоенного блока
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->timestamps();

            // Группа ездит в спорткомплекс не чаще одного раза в неделю.
            $table->unique('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_complex_schedule');
    }
};
