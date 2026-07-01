<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_logs')) {
            return;
        }

        Schema::table('approval_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_logs', 'returned_to_id')) {
                $table->unsignedBigInteger('returned_to_id')->nullable()->after('new_status');
            }

            if (!Schema::hasColumn('approval_logs', 'forwarded_to_id')) {
                $table->unsignedBigInteger('forwarded_to_id')->nullable()->after('returned_to_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_logs')) {
            return;
        }

        Schema::table('approval_logs', function (Blueprint $table) {
            if (Schema::hasColumn('approval_logs', 'forwarded_to_id')) {
                $table->dropColumn('forwarded_to_id');
            }

            if (Schema::hasColumn('approval_logs', 'returned_to_id')) {
                $table->dropColumn('returned_to_id');
            }
        });
    }
};