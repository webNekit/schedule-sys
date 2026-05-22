<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_curriculum_assignments', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('group_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_curriculum_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
        });
    }
};
