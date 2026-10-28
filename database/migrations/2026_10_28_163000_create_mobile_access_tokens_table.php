<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_access_tokens')) {
            return;
        }

        Schema::create('mobile_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'expires_at'], 'mobile_access_tokens_user_expiry_idx');
            $table->foreign('user_id')
                ->references('idno')
                ->on('tbusers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_access_tokens');
    }
};
