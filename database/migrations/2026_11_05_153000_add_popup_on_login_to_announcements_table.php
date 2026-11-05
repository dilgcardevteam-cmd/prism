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
        if (Schema::hasTable('announcements') && !Schema::hasColumn('announcements', 'popup_on_login')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->boolean('popup_on_login')->default(true)->after('is_pinned');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'popup_on_login')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('popup_on_login');
            });
        }
    }
};

