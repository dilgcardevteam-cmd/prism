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
        if (!Schema::hasTable('locally_funded_projects')) {
            return;
        }

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('locally_funded_projects', 'funding_year')) {
                $table->year('funding_year')->after('project_name');
            }

            if (!Schema::hasColumn('locally_funded_projects', 'fund_source')) {
                $table->string('fund_source')->after('funding_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('locally_funded_projects')) {
            return;
        }

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (Schema::hasColumn('locally_funded_projects', 'fund_source')) {
                $table->dropColumn('fund_source');
            }

            if (Schema::hasColumn('locally_funded_projects', 'funding_year')) {
                $table->dropColumn('funding_year');
            }
        });
    }
};
