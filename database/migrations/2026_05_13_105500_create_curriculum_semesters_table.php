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
        Schema::create('curriculum_semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipline_id')->constrained('curriculum_disciplines')->cascadeOnDelete();
            $table->tinyInteger('course_number');
            $table->tinyInteger('semester_number');
            $table->tinyInteger('semester_in_course');
            $table->integer('hours_total')->default(0);
            $table->integer('hours_lecture')->default(0);
            $table->integer('hours_practice')->default(0);
            $table->integer('hours_lab')->default(0);
            $table->integer('hours_self_study')->default(0);
            $table->integer('hours_consultation')->default(0);
            $table->foreignId('control_form_id')->nullable()->constrained('control_forms')->nullOnDelete();
            $table->integer('exam_hours')->default(0);
            $table->integer('course_project_hours')->default(0);
            $table->integer('weeks_count')->default(0);
            $table->decimal('hours_per_week', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_semesters');
    }
};
