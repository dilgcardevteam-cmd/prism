<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('announcements') && !Schema::hasColumn('announcements', 'deadline_at')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->timestamp('deadline_at')->nullable()->after('expires_at')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'deadline_at')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('deadline_at');
            });
        }
    }
};

