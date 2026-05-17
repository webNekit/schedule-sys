<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_curriculum_assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('course_number')->nullable()->after('curriculum_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('group_curriculum_assignments', function (Blueprint $table) {
            $table->dropColumn('course_number');
        });
    }
};
