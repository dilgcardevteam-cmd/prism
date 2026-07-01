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

        if (Schema::hasColumn('approval_logs', 'revision_number')) {
            return;
        }

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->unsignedInteger('revision_number')->default(1)->after('new_status');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_logs')) {
            return;
        }

        if (!Schema::hasColumn('approval_logs', 'revision_number')) {
            return;
        }

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropColumn('revision_number');
        });
    }
};