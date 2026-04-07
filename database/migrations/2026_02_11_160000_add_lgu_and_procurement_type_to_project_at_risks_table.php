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
            $table->string('lgu')->nullable()->after('project_code');
            $table->string('procurement_type')->nullable()->after('project_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->dropColumn(['lgu', 'procurement_type']);
        });
    }
};
