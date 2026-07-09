<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            // Привязка к конкретной дисциплине семестра (курс/семестр/дисциплина уже в нём)
            $table->foreignId('curriculum_semester_id')->constrained('curriculum_semesters')->cascadeOnDelete();
            // Опциональная привязка к группе: null = для всех групп этого плана/курса
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->date('exam_date')->nullable();
            $table->unsignedTinyInteger('lesson_number')->default(1);
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['curriculum_semester_id', 'group_id'], 'exam_sched_sem_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};
