<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbnotifications')) {
            return;
        }

        Schema::table('tbnotifications', function (Blueprint $table) {
            if (!Schema::hasColumn('tbnotifications', 'sender_user_id')) {
                $table->unsignedBigInteger('sender_user_id')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('tbnotifications', 'sender_name')) {
                $table->string('sender_name', 255)->nullable()->after('sender_user_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbnotifications')) {
            return;
        }

        Schema::table('tbnotifications', function (Blueprint $table) {
            if (Schema::hasColumn('tbnotifications', 'sender_name')) {
                $table->dropColumn('sender_name');
            }

            if (Schema::hasColumn('tbnotifications', 'sender_user_id')) {
                $table->dropColumn('sender_user_id');
            }
        });
    }
};
