<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Поток — набор групп, которые посещают лекции совместно
        Schema::create('group_streams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('discipline_id')->constrained('curriculum_disciplines')->cascadeOnDelete();
            $table->integer('semester_number');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('group_stream_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('group_streams')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->unique(['stream_id', 'group_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_stream_members');
        Schema::dropIfExists('group_streams');
    }
};
