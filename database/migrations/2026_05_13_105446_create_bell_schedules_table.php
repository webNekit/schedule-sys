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
        Schema::create('bell_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->tinyInteger('shift_number')->default(1);
            $table->tinyInteger('lesson_number');
            $table->time('time_start');
            $table->time('time_end');
            $table->integer('break_after_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bell_schedules');
    }
};
