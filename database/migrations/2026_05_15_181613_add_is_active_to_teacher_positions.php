<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_positions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('max_hours_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_positions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
