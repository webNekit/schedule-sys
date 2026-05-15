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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->unsignedInteger('year_start');
            $table->unsignedInteger('year_end');
            $table->date('date_start');
            $table->date('date_end');
            $table->date('first_semester_start')->nullable();
            $table->date('first_semester_end')->nullable();
            $table->date('second_semester_start')->nullable();
            $table->date('second_semester_end')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
