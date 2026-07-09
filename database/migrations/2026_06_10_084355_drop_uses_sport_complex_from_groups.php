<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Принадлежность к спорткомплексу теперь определяется наличием группы в
     * недельном расписании (таблица sport_complex_schedule), флаг больше не нужен.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('uses_sport_complex');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('uses_sport_complex')->default(true)->after('shift');
        });
    }
};
