<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduling_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100);
            // Уровень действия правила: на весь колледж, на курс (1-4) или на конкретную группу.
            $table->string('scope', 20)->default('global');
            // Номер курса или group_id; null для global.
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            // Жёсткость ограничения: hard (ошибка/блок) или soft (предупреждение). null для параметров.
            $table->string('severity', 10)->nullable();
            // Числовые пороги, веса и списки.
            $table->json('params')->nullable();
            $table->timestamps();

            $table->unique(['key', 'scope', 'scope_id']);
            $table->index(['scope', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduling_rules');
    }
};
