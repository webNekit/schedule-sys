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
        Schema::create('schedule_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('schedule_versions')->cascadeOnDelete();
            $table->date('date');
            $table->tinyInteger('lesson_number');
            $table->tinyInteger('shift');
            $table->foreignId('group_id')->constrained();
            $table->foreignId('subgroup_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('discipline_id')->constrained('curriculum_disciplines')->cascadeOnDelete();
            $table->foreignId('lesson_type_id')->constrained('lesson_types');
            $table->foreignId('teacher_id')->constrained('teachers');
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('building_id')->constrained('buildings');
            $table->foreignId('week_type_id')->nullable()->constrained('week_types')->nullOnDelete();
            $table->boolean('is_auto_generated')->default(true);
            $table->boolean('is_replacement')->default(false);
            $table->foreignId('original_lesson_id')->nullable()->constrained('schedule_lessons')->nullOnDelete();
            $table->string('status', 50)->default('scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->index(['date', 'version_id']);
            $table->index(['group_id', 'date']);
            $table->index(['teacher_id', 'date']);
            $table->index(['room_id', 'date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_lessons');
    }
};
