<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Параллельные занятия: несколько преподавателей ведут дисциплину
     * одновременно (не делят часы), например ин.язык по подгруппам или УП.
     */
    public function up(): void
    {
        Schema::table('curriculum_disciplines', function (Blueprint $table) {
            $table->boolean('is_parallel')->default(false)->after('requires_subgroup');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_disciplines', function (Blueprint $table) {
            $table->dropColumn('is_parallel');
        });
    }
};
