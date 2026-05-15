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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('academic_year_id')->constrained();
            $table->string('name', 255);
            $table->string('short_name', 50)->nullable();
            $table->tinyInteger('current_course')->default(1);
            $table->integer('students_count')->default(0);
            $table->tinyInteger('shift')->default(1);
            $table->string('status', 50)->default('active');
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('groups');
    }
};
