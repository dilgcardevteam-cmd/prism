<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_password_reset_challenges')) {
            return;
        }

        Schema::create('mobile_password_reset_challenges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('challenge_hash', 64)->unique();
            $table->text('encrypted_token');
            $table->string('otp_hash')->nullable();
            $table->string('pending_password_hash')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at'], 'mobile_password_reset_user_expiry_idx');
            $table->foreign('user_id')
                ->references('idno')
                ->on('tbusers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_password_reset_challenges');
    }
};
