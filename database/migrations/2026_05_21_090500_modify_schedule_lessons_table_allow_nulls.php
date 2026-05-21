<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->change();
            $table->foreignId('room_id')->nullable()->change();
            $table->foreignId('building_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_lessons', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable(false)->change();
            $table->foreignId('room_id')->nullable(false)->change();
            $table->foreignId('building_id')->nullable(false)->change();
        });
    }
};
