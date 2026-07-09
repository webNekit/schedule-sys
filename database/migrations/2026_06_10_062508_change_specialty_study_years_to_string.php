<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Храним срок обучения как строку "годы,месяцы" (например "2,9" или "3,10"),
     * потому что дробная часть — это месяцы, и float тут недопустим (2,10 != 2,1).
     */
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->string('study_years_9', 10)->nullable()->change();
            $table->string('study_years_11', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->integer('study_years_9')->nullable()->change();
            $table->integer('study_years_11')->nullable()->change();
        });
    }
};
