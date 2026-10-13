<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reportorial tables that require C/MLGOO validation columns.
     */
    protected array $tables = [
        'tblpmc_documents',
        'tblroad_maintenance_status_documents',
        'tblrbis_annual_certification_documents',
        'tblannual_maintenance_work_program_documents',
        'tblpd_no_pbbm_2025_1572_1573_documents',
        'tblswa_annex_f_documents',
        'tbfur_batch_documents',
        'pre_implementation_document_files',
        'lgsf_project_completion_report_files',
        'sglgif_project_completion_report_files',
        'annual_rpmes_form4_uploads',
        'quarterly_rpmes_form2_uploads',
        'quarterly_rpmes_form5_uploads',
        'quarterly_rpmes_form6_uploads',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'approved_at_c_mlgoo')) {
                    $table->timestamp('approved_at_c_mlgoo')->nullable()->after('status');
                }
                if (!Schema::hasColumn($tableName, 'approved_by_c_mlgoo')) {
                    $table->unsignedBigInteger('approved_by_c_mlgoo')->nullable()->after('approved_at_c_mlgoo');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columnsToDrop = [];
                if (Schema::hasColumn($tableName, 'approved_at_c_mlgoo')) {
                    $columnsToDrop[] = 'approved_at_c_mlgoo';
                }
                if (Schema::hasColumn($tableName, 'approved_by_c_mlgoo')) {
                    $columnsToDrop[] = 'approved_by_c_mlgoo';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};

