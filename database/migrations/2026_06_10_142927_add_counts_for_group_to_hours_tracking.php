<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Флаг «учитывать в часах группы». У параллельных занятий несколько уроков
     * на одном слоте (по преподавателю на аудиторию): часы преподавателей считаем
     * каждому, а часы группы по дисциплине — один раз (иначе задвоение).
     */
    public function up(): void
    {
        Schema::table('hours_tracking', function (Blueprint $table) {
            $table->boolean('counts_for_group')->default(true)->after('hours_conducted');
        });
    }

    public function down(): void
    {
        Schema::table('hours_tracking', function (Blueprint $table) {
            $table->dropColumn('counts_for_group');
        });
    }
};
