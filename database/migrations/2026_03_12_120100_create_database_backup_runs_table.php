<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backup_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_automation_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('backup_type', 20);
            $table->string('status', 20);
            $table->string('filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->json('mailed_to')->nullable();
            $table->boolean('was_encrypted')->default(false);
            $table->unsignedInteger('retention_deleted_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backup_runs');
    }
};
