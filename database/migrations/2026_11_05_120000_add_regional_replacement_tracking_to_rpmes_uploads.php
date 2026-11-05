<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'lgsf_project_completion_report_files',
            'sglgif_project_completion_report_files',
            'tblpmc_documents',
            'tblpmc_document_files',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'regional_replacement_used_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('regional_replacement_used_at')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'lgsf_project_completion_report_files',
            'sglgif_project_completion_report_files',
            'tblpmc_documents',
            'tblpmc_document_files',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'regional_replacement_used_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('regional_replacement_used_at');
                });
            }
        }
    }
};
