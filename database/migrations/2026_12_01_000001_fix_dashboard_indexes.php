<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // These columns are TEXT in this table; use prefix lengths to stay within MySQL index limits.
        DB::statement('CREATE INDEX idx_prov_city_status ON subay_project_profiles (province(100), city_municipality(100), status(40))');
        DB::statement('CREATE INDEX idx_project_code ON subay_project_profiles (project_code(120))');
        DB::statement('CREATE INDEX idx_funding_year ON subay_project_profiles (funding_year(20))');
        DB::statement('CREATE INDEX idx_program ON subay_project_profiles (program(100))');
        DB::statement('CREATE INDEX idx_type_of_project ON subay_project_profiles (type_of_project(100))');

        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->index('project_code', 'idx_par_project_code');
            $table->index('date_of_extraction', 'idx_par_extraction');
            $table->index('risk_level', 'idx_par_risk_level');
        });

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->index('subaybayan_project_code', 'idx_lfp_subay_code');
            $table->index(['province', 'city_municipality'], 'idx_lfp_prov_city');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX idx_prov_city_status ON subay_project_profiles');
        DB::statement('DROP INDEX idx_project_code ON subay_project_profiles');
        DB::statement('DROP INDEX idx_funding_year ON subay_project_profiles');
        DB::statement('DROP INDEX idx_program ON subay_project_profiles');
        DB::statement('DROP INDEX idx_type_of_project ON subay_project_profiles');

        Schema::table('project_at_risks', function (Blueprint $table) {
            $table->dropIndex('idx_par_project_code');
            $table->dropIndex('idx_par_extraction');
            $table->dropIndex('idx_par_risk_level');
        });

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->dropIndex('idx_lfp_subay_code');
            $table->dropIndex('idx_lfp_prov_city');
        });
    }
};

