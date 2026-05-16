<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            if (! Schema::hasColumn('specialties', 'budget_places')) {
                $table->integer('budget_places')->nullable();
            }
            if (! Schema::hasColumn('specialties', 'commercial_places')) {
                $table->integer('commercial_places')->nullable();
            }
            if (! Schema::hasColumn('specialties', 'study_years_9')) {
                $table->string('study_years_9')->nullable();
            }
            if (! Schema::hasColumn('specialties', 'study_years_11')) {
                $table->string('study_years_11')->nullable();
            }

            $table->string('base_education')->nullable()->change();
            $table->foreignId('education_level_id')->nullable()->change();
            $table->unsignedInteger('study_years')->nullable()->change();
            $table->integer('study_months')->nullable()->change();
            $table->string('form_of_study')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropColumn(['budget_places', 'commercial_places', 'study_years_9', 'study_years_11']);
            $table->string('base_education')->nullable(false)->change();
            $table->foreignId('education_level_id')->nullable(false)->change();
            $table->unsignedInteger('study_years')->nullable(false)->change();
            $table->integer('study_months')->nullable(false)->change();
            $table->string('form_of_study')->nullable(false)->change();
        });
    }
};
