<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $table->index(['province', 'city_municipality', 'status'], 'idx_prov_city_status');
            $table->index('project_code', 'idx_project_code')->limit(20);
            $table->index('funding_year', 'idx_funding_year');
            $table->index('program', 'idx_program');
            $table->index('type_of_project', 'idx_type_of_project');
        });

        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->index('project_code', 'idx_par_project_code')->limit(20);
            $table->index('date_of_extraction', 'idx_par_extraction');
            $table->index('risk_level', 'idx_par_risk_level');
        });

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->index('subaybayan_project_code', 'idx_lfp_subay_code')->limit(20);
            $table->index(['province', 'city_municipality'], 'idx_lfp_prov_city');
        });
    }

    public function down(): void
    {
        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $table->dropIndex(['province', 'city_municipality', 'status']);
            $table->dropIndex(['project_code']);
            $table->dropIndex(['funding_year']);
            $table->dropIndex(['program']);
            $table->dropIndex(['type_of_project']);
        });

        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->dropIndex(['project_code']);
            $table->dropIndex(['date_of_extraction']);
            $table->dropIndex(['risk_level']);
        });

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->dropIndex(['subaybayan_project_code']);
            $table->dropIndex(['province', 'city_municipality']);
        });
    }
};

