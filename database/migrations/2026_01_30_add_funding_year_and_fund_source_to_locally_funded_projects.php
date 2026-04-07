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
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->year('funding_year')->after('project_name');
            $table->string('fund_source')->after('funding_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->dropColumn(['funding_year', 'fund_source']);
        });
    }
};
