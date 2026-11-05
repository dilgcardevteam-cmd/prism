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
        if (Schema::hasTable('announcements') && !Schema::hasColumn('announcements', 'target_regions')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->json('target_regions')->nullable()->after('target_roles');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'target_regions')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('target_regions');
            });
        }
    }
};

