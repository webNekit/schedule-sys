<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_conflicts', function (Blueprint $table) {
            // Добавляем поле suggestion если его ещё нет
            if (! Schema::hasColumn('schedule_conflicts', 'suggestion')) {
                $table->text('suggestion')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_conflicts', function (Blueprint $table) {
            $table->dropColumn('suggestion');
        });
    }
};
