<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_discipline_semesters', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete()->after('curriculum_semester_id');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_discipline_semesters', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
