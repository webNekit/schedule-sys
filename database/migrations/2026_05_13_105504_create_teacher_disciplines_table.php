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
        Schema::create('teacher_disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained('curriculum_disciplines')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subgroup_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained();
            $table->boolean('is_primary')->default(false);
            $table->integer('planned_hours')->default(0);
            $table->integer('actual_hours')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_disciplines');
    }
};
