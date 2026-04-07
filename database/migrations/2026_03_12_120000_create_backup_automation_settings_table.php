<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_automation_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('frequency')->default('daily');
            $table->unsignedTinyInteger('weekly_day')->nullable();
            $table->time('run_time')->default('18:00:00');
            $table->json('recipient_emails')->nullable();
            $table->unsignedSmallInteger('retention_days')->nullable();
            $table->boolean('encrypt_backup')->default(false);
            $table->text('encryption_password')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status')->nullable();
            $table->text('last_error')->nullable();
            $table->string('last_backup_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_automation_settings');
    }
};
