<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_disciplines', function (Blueprint $table) {
            $table->boolean('is_schedulable')->default(true)->after('requires_lab');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_disciplines', function (Blueprint $table) {
            $table->dropColumn('is_schedulable');
        });
    }
};
