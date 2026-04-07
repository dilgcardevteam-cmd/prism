<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('message_thread_members') || Schema::hasColumn('message_thread_members', 'manual_unread_at')) {
            return;
        }

        Schema::table('message_thread_members', function (Blueprint $table) {
            $table->timestamp('manual_unread_at')->nullable()->after('joined_at');
            $table->index(['user_id', 'manual_unread_at'], 'message_thread_members_user_manual_unread_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('message_thread_members') || !Schema::hasColumn('message_thread_members', 'manual_unread_at')) {
            return;
        }

        Schema::table('message_thread_members', function (Blueprint $table) {
            $table->dropIndex('message_thread_members_user_manual_unread_idx');
            $table->dropColumn('manual_unread_at');
        });
    }
};
