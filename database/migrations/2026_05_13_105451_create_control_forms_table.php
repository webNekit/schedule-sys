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
        Schema::create('control_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('short_name', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->boolean('is_exam_session')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_forms');
    }
};
