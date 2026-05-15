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
        Schema::create('teacher_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('short_name', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('max_hours_per_week')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_positions');
    }
};
