<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->integer('study_years_9')->nullable()->after('study_years');
            $table->integer('study_years_11')->nullable()->after('study_years_9');
            $table->integer('budget_places')->nullable()->after('form_of_study');
            $table->integer('contract_places')->nullable()->after('budget_places');
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropColumn(['study_years_9', 'study_years_11', 'budget_places', 'contract_places']);
        });
    }
};
