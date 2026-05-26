<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('subay_project_profiles', 'project_code_key')) {
                $table->string('project_code_key', 191)->nullable()->after('project_code');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'province_key')) {
                $table->string('province_key', 120)->nullable()->after('province');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'city_municipality_key')) {
                $table->string('city_municipality_key', 120)->nullable()->after('city_municipality');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'funding_year_key')) {
                $table->string('funding_year_key', 20)->nullable()->after('funding_year');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'funding_year_num')) {
                $table->unsignedInteger('funding_year_num')->nullable()->after('funding_year_key');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'program_key')) {
                $table->string('program_key', 120)->nullable()->after('program');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'fund_source_key')) {
                $table->string('fund_source_key', 40)->nullable()->after('program_key');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'procurement_key')) {
                $table->string('procurement_key', 120)->nullable()->after('procurement');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'status_key')) {
                $table->string('status_key', 120)->nullable()->after('status');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'date_parsed')) {
                $table->date('date_parsed')->nullable()->after('date');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'allocation_num')) {
                $table->decimal('allocation_num', 18, 4)->nullable()->after('national_subsidy_original_allocation');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'obligation_num')) {
                $table->decimal('obligation_num', 18, 4)->nullable()->after('obligation');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'disbursement_num')) {
                $table->decimal('disbursement_num', 18, 4)->nullable()->after('disbursement');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'liquidations_num')) {
                $table->decimal('liquidations_num', 18, 4)->nullable()->after('liquidations');
            }
            if (!Schema::hasColumn('subay_project_profiles', 'accomplishment_num')) {
                $table->decimal('accomplishment_num', 10, 4)->nullable()->after('total_accomplishment');
            }
        });

        $this->refreshDerivedColumns();

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $table->index('project_code_key', 'subay_project_profiles_project_code_key_idx');
            $table->index(['project_code_key', 'date_parsed'], 'subay_project_profiles_project_code_date_idx');
            $table->index(['province_key', 'city_municipality_key', 'status_key'], 'subay_project_profiles_province_city_status_key_idx');
            $table->index('fund_source_key', 'subay_project_profiles_fund_source_key_idx');
            $table->index(['funding_year_num', 'fund_source_key'], 'subay_project_profiles_year_fund_source_key_idx');
            $table->index('procurement_key', 'subay_project_profiles_procurement_key_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $table->dropIndex('subay_project_profiles_project_code_key_idx');
            $table->dropIndex('subay_project_profiles_project_code_date_idx');
            $table->dropIndex('subay_project_profiles_province_city_status_key_idx');
            $table->dropIndex('subay_project_profiles_fund_source_key_idx');
            $table->dropIndex('subay_project_profiles_year_fund_source_key_idx');
            $table->dropIndex('subay_project_profiles_procurement_key_idx');

            $table->dropColumn([
                'project_code_key',
                'province_key',
                'city_municipality_key',
                'funding_year_key',
                'funding_year_num',
                'program_key',
                'fund_source_key',
                'procurement_key',
                'status_key',
                'date_parsed',
                'allocation_num',
                'obligation_num',
                'disbursement_num',
                'liquidations_num',
                'accomplishment_num',
            ]);
        });
    }

    private function refreshDerivedColumns(): void
    {
        $dateExpression = $this->parsedDateExpression('date');

        DB::statement("
            UPDATE subay_project_profiles
            SET
                project_code_key = LOWER(TRIM(COALESCE(project_code, ''))),
                province_key = LOWER(TRIM(COALESCE(province, ''))),
                city_municipality_key = LOWER(TRIM(COALESCE(city_municipality, ''))),
                funding_year_key = TRIM(COALESCE(funding_year, '')),
                funding_year_num = CASE
                    WHEN TRIM(COALESCE(funding_year, '')) REGEXP '^[0-9]+$' THEN CAST(TRIM(COALESCE(funding_year, '')) AS UNSIGNED)
                    ELSE NULL
                END,
                program_key = UPPER(TRIM(COALESCE(program, ''))),
                fund_source_key = {$this->fundSourceExpression()},
                procurement_key = LOWER(TRIM(COALESCE(procurement_type, procurement, ''))),
                status_key = LOWER(TRIM(COALESCE(status, ''))),
                date_parsed = {$dateExpression},
                allocation_num = {$this->numericExpression('national_subsidy_original_allocation')},
                obligation_num = {$this->numericExpression('obligation')},
                disbursement_num = {$this->numericExpression('disbursement')},
                liquidations_num = {$this->numericExpression('liquidations')},
                accomplishment_num = {$this->numericExpression('total_accomplishment')}
        ");
    }

    private function parsedDateExpression(string $column): string
    {
        $trimmed = "NULLIF(TRIM(COALESCE({$column}, '')), '')";

        return "
            COALESCE(
                IF(
                    TRIM(COALESCE({$column}, '')) REGEXP '^[0-9]+(\\.[0-9]+)?$',
                    DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE({$column}, '')) AS DECIMAL(12,4))) DAY),
                    NULL
                ),
                STR_TO_DATE({$trimmed}, '%Y-%m-%d'),
                STR_TO_DATE({$trimmed}, '%Y-%m-%d %H:%i:%s'),
                STR_TO_DATE({$trimmed}, '%m/%d/%Y'),
                STR_TO_DATE({$trimmed}, '%m/%d/%Y %H:%i'),
                STR_TO_DATE({$trimmed}, '%m/%d/%Y %H:%i:%s'),
                STR_TO_DATE({$trimmed}, '%m/%d/%Y %h:%i:%s %p'),
                STR_TO_DATE({$trimmed}, '%m/%d/%y'),
                STR_TO_DATE({$trimmed}, '%d/%m/%Y'),
                STR_TO_DATE({$trimmed}, '%d-%m-%Y'),
                STR_TO_DATE({$trimmed}, '%d-%b-%Y'),
                STR_TO_DATE({$trimmed}, '%b %e, %Y'),
                STR_TO_DATE({$trimmed}, '%M %e, %Y')
            )
        ";
    }

    private function numericExpression(string $column): string
    {
        $sanitized = "REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE({$column}, '')), ',', ''), '%', ''), '₱', ''), ' ', '')";

        return "
            CASE
                WHEN {$sanitized} REGEXP '^-?[0-9]+(\\.[0-9]+)?$' THEN CAST({$sanitized} AS DECIMAL(18,4))
                ELSE NULL
            END
        ";
    }

    private function fundSourceExpression(): string
    {
        return "
            CASE
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'SBDP%' THEN 'SBDP'
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'FA-%' THEN 'FALGU'
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'FALGU%' THEN 'FALGU'
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'CMGP%' THEN 'CMGP'
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'GEF%' THEN 'GEF'
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'SAFPB%' THEN 'SAFPB'
                WHEN UPPER(TRIM(COALESCE(project_code, ''))) LIKE 'SGLGIF%' THEN 'SGLGIF'
                WHEN UPPER(TRIM(COALESCE(program, ''))) LIKE '%FALGU%' THEN 'FALGU'
                WHEN UPPER(TRIM(COALESCE(program, ''))) IN ('GROWTH EQUITY FUND', 'GEF') THEN 'GEF'
                WHEN UPPER(TRIM(COALESCE(program, ''))) IN ('SUPPORT TO THE BARANGAY DEVELOPMENT PROGRAM', 'SBDP') THEN 'SBDP'
                ELSE UPPER(TRIM(COALESCE(program, '')))
            END
        ";
    }
};
