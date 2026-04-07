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
        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->string('risk_level_aging')->nullable()->after('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->dropColumn('risk_level_aging');
        });
    }
};
