<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_impersonation_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('admin_name');
            $table->unsignedBigInteger('target_user_id');
            $table->string('target_username');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('expires_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->char('session_reference_hash', 64)->unique();
            $table->string('reason', 1000)->nullable();
            $table->string('status', 16)->default('active');

            $table->index(['admin_user_id', 'started_at']);
            $table->index(['target_user_id', 'started_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_impersonation_audit_logs');
    }
};
