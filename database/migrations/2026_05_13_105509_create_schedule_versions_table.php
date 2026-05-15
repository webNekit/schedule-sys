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
        Schema::create('schedule_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('academic_year_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('period_type', 50)->default('week');
            $table->string('status', 50)->default('draft');
            $table->string('generation_type', 50)->default('auto');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_versions');
    }
};
