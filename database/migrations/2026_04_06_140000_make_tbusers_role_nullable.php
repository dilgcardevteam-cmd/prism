<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tbusers') || !Schema::hasColumn('tbusers', 'role')) {
            return;
        }

        Schema::table('tbusers', function (Blueprint $table) {
            $table->string('role')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tbusers') || !Schema::hasColumn('tbusers', 'role')) {
            return;
        }

        DB::table('tbusers')
            ->whereNull('role')
            ->update(['role' => 'user']);

        Schema::table('tbusers', function (Blueprint $table) {
            $table->string('role')->default('user')->nullable(false)->change();
        });
    }
};
