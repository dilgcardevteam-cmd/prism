<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_login_challenges')) {
            return;
        }

        Schema::create('mobile_login_challenges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('challenge_hash', 64)->unique();
            $table->text('encrypted_token')->nullable();
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'expires_at'], 'mobile_login_challenges_user_expiry_idx');
            $table->foreign('user_id')
                ->references('idno')
                ->on('tbusers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_login_challenges');
    }
};
