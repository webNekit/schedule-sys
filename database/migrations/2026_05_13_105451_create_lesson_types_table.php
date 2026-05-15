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
        Schema::create('lesson_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('short_name', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('requires_lab')->default(false);
            $table->boolean('is_control_form')->default(false);
            $table->decimal('hours_coefficient', 5, 2)->default(1.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_types');
    }
};
