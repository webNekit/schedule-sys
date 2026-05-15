<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_plan_id')->constrained('curriculum_plans')->cascadeOnDelete();
            $table->tinyInteger('course_number');
            $table->string('type', 50); // edu_practice, prod_practice
            $table->string('symbol', 10)->nullable(); // У, П, Пд
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['curriculum_plan_id', 'course_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_practices');
    }
};
