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
        Schema::create('schedule_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('schedule_versions')->cascadeOnDelete();
            $table->string('conflict_type', 100);
            $table->string('severity', 20)->default('error');
            $table->date('date')->nullable();
            $table->tinyInteger('lesson_number')->nullable();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained('curriculum_disciplines')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_conflicts');
    }
};
