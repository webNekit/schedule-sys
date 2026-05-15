<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_discipline_semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_discipline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_semester_id')->constrained('curriculum_semesters')->cascadeOnDelete();
            $table->integer('planned_hours'); // auto-filled from curriculum_semester.hours_total
            $table->integer('actual_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['teacher_discipline_id', 'curriculum_semester_id'], 'td_semester_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_discipline_semesters');
    }
};
