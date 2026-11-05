<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'tblpmc_documents',
            'tblpmc_document_files',
            'tblmonitoring_evaluation_monthly_documents',
            'tblroad_maintenance_status_documents',
            'tblrbis_annual_certification_documents',
            'tblpd_no_pbbm_2025_1572_1573_documents',
            'tblswa_annex_f_documents',
            'tblannual_maintenance_work_program_documents',
            'lgsf_project_completion_report_files',
            'sglgif_project_completion_report_files',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'regional_replacement_used_at')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->timestamp('regional_replacement_used_at')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'tblpmc_documents',
            'tblpmc_document_files',
            'tblmonitoring_evaluation_monthly_documents',
            'tblroad_maintenance_status_documents',
            'tblrbis_annual_certification_documents',
            'tblpd_no_pbbm_2025_1572_1573_documents',
            'tblswa_annex_f_documents',
            'tblannual_maintenance_work_program_documents',
            'lgsf_project_completion_report_files',
            'sglgif_project_completion_report_files',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'regional_replacement_used_at')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->dropColumn('regional_replacement_used_at');
                });
            }
        }
    }
};

