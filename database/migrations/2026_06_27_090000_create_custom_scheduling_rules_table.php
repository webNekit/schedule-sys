<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_scheduling_rules', function (Blueprint $table) {
            $table->id();
            // Человекочитаемое имя и пояснение правила.
            $table->string('name');
            $table->text('description')->nullable();
            // Уровень действия: global | course | group.
            $table->string('scope', 20)->default('global');
            // Номер курса или group_id; null для global.
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            // Жёсткость: hard → ошибка, soft → предупреждение.
            $table->string('severity', 10)->default('soft');
            // Декларативное определение правила (тип + условия) в JSON.
            $table->json('definition');
            $table->timestamps();

            $table->index(['scope', 'scope_id']);
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_scheduling_rules');
    }
};
