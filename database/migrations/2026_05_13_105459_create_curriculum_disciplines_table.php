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
        Schema::create('curriculum_disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('short_name', 100)->nullable();
            $table->string('code', 50)->nullable();
            $table->string('cycle', 50)->nullable();
            $table->string('discipline_type', 50)->default('theoretical');
            $table->boolean('is_federal')->default(false);
            $table->boolean('requires_subgroup')->default(false);
            $table->string('subgroup_type', 50)->nullable();
            $table->boolean('requires_lab')->default(false);
            $table->foreignId('required_room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_disciplines');
    }
};
