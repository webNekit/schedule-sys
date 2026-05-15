<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 255)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('type', 50)->default('curriculum_xml');
            $table->string('status', 50)->default('pending');
            $table->integer('records_total')->default(0);
            $table->integer('records_imported')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
