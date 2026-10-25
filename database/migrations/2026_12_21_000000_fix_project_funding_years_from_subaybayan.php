<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            Schema::hasTable('locally_funded_projects')
            && Schema::hasTable('subay_project_profiles')
            && Schema::hasColumn('locally_funded_projects', 'funding_year')
            && Schema::hasColumn('subay_project_profiles', 'funding_year')
        ) {
            DB::statement("
                UPDATE locally_funded_projects
                SET funding_year = (
                    SELECT spp.funding_year
                    FROM subay_project_profiles spp
                    WHERE spp.project_code = locally_funded_projects.subaybayan_project_code
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM subay_project_profiles spp
                    WHERE spp.project_code = locally_funded_projects.subaybayan_project_code
                      AND spp.funding_year IS NOT NULL
                      AND TRIM(spp.funding_year) <> ''
                      AND CAST(locally_funded_projects.funding_year AS CHAR) <> CAST(spp.funding_year AS CHAR)
                )
            ");
        }

        if (
            Schema::hasTable('tbfur')
            && Schema::hasTable('subay_project_profiles')
            && Schema::hasColumn('tbfur', 'funding_year')
            && Schema::hasColumn('subay_project_profiles', 'funding_year')
        ) {
            DB::statement("
                UPDATE tbfur
                SET funding_year = (
                    SELECT spp.funding_year
                    FROM subay_project_profiles spp
                    WHERE spp.project_code = tbfur.project_code
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM subay_project_profiles spp
                    WHERE spp.project_code = tbfur.project_code
                      AND spp.funding_year IS NOT NULL
                      AND TRIM(spp.funding_year) <> ''
                      AND CAST(tbfur.funding_year AS CHAR) <> CAST(spp.funding_year AS CHAR)
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
