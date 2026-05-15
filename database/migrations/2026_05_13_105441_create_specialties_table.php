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
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('short_name', 100)->nullable();
            $table->string('qualification', 255)->nullable();
            $table->foreignId('education_level_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('study_years');
            $table->integer('study_months')->default(0);
            $table->string('base_education', 50)->nullable();
            $table->string('form_of_study', 50)->default('очная');
            $table->integer('max_courses')->default(4);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};
