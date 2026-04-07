<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_messages')) {
            return;
        }

        Schema::table('user_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('user_messages', 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('thread_id');
                $table->index('batch_uuid');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_messages')) {
            return;
        }

        Schema::table('user_messages', function (Blueprint $table) {
            if (Schema::hasColumn('user_messages', 'batch_uuid')) {
                $table->dropIndex(['batch_uuid']);
                $table->dropColumn('batch_uuid');
            }
        });
    }
};
