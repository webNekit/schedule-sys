<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_practices', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('curriculum_plan_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_practices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
