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
        Schema::create('hours_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained();
            $table->foreignId('discipline_id')->constrained('curriculum_disciplines')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers');
            $table->foreignId('semester_id')->constrained('curriculum_semesters')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained();
            $table->foreignId('lesson_type_id')->constrained('lesson_types');
            $table->date('date');
            $table->decimal('hours_conducted', 5, 2);
            $table->foreignId('schedule_lesson_id')->nullable()->constrained('schedule_lessons')->nullOnDelete();
            $table->boolean('is_cancelled')->default(false);
            $table->text('notes')->nullable();
            $table->index(['group_id', 'discipline_id', 'academic_year_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hours_tracking');
    }
};
