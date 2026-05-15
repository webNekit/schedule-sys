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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('number', 50);
            $table->string('name', 255)->nullable();
            $table->integer('capacity')->default(0);
            $table->decimal('area', 8, 2)->nullable();
            $table->integer('floor')->default(1);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available_for_booking')->default(true);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
