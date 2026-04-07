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
        if (!Schema::hasTable('tbusers') || Schema::hasColumn('tbusers', 'registration_ip_address')) {
            return;
        }

        Schema::table('tbusers', function (Blueprint $table) {
            $table->string('registration_ip_address', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tbusers') || !Schema::hasColumn('tbusers', 'registration_ip_address')) {
            return;
        }

        Schema::table('tbusers', function (Blueprint $table) {
            $table->dropColumn('registration_ip_address');
        });
    }
};
