<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            // Тип аудитории относится к выездному спорткомплексу (а не к спортзалу в корпусе).
            $table->boolean('is_sport_complex')->default(false)->after('can_be_shared');
        });

        // Бэкафилл: ранее спорткомплекс определялся по точному имени «Спорт.комплекс».
        DB::table('room_types')
            ->where('name', 'Спорт.комплекс')
            ->update(['is_sport_complex' => true]);
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('is_sport_complex');
        });
    }
};
