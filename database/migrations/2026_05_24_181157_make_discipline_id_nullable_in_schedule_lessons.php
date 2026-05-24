<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->foreignId('discipline_id')->nullable()->change();
            $table->foreignId('lesson_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->foreignId('discipline_id')->nullable(false)->change();
            $table->foreignId('lesson_type_id')->nullable(false)->change();
        });
    }
};
