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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('position_id')->constrained('teacher_positions');
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('full_name', 300)->nullable();
            $table->string('short_name', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('internal_phone', 50)->nullable();
            $table->string('employment_type', 50)->default('full_time');
            $table->decimal('rate', 4, 2)->default(1.00);
            $table->integer('max_hours_per_week')->default(36);
            $table->integer('min_lessons_per_day')->default(3);
            $table->integer('max_lessons_per_day')->default(5);
            $table->boolean('has_methodical_day')->default(false);
            $table->tinyInteger('methodical_day_of_week')->nullable();
            $table->string('qualification_category', 50)->nullable();
            $table->string('academic_degree', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
