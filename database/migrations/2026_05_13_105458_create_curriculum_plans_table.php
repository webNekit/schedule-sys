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
        Schema::create('curriculum_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained();
            $table->foreignId('academic_year_id')->constrained();
            $table->string('name', 255);
            $table->string('version', 50)->nullable();
            $table->string('xml_file_path', 255)->nullable();
            $table->longText('xml_original')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->integer('total_hours')->default(0);
            $table->integer('contact_hours')->default(0);
            $table->integer('self_study_hours')->default(0);
            $table->integer('practice_hours')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_plans');
    }
};
