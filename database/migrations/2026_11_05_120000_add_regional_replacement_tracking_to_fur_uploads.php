<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'tbfur_mov_uploads' => ['regional_replacement_used_at'],
            'tbfur_batch_documents' => ['regional_replacement_used_at'],
            'tbfur_fdp' => ['fdp_regional_replacement_used_at', 'posting_regional_replacement_used_at'],
            'tbfur_written_notice' => [
                'dbm_regional_replacement_used_at',
                'dilg_regional_replacement_used_at',
                'speaker_regional_replacement_used_at',
                'president_regional_replacement_used_at',
                'house_regional_replacement_used_at',
                'senate_regional_replacement_used_at',
            ],
        ];

        foreach ($columns as $tableName => $tableColumns) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $tableColumns): void {
                foreach ($tableColumns as $column) {
                    if (!Schema::hasColumn($tableName, $column)) {
                        $table->timestamp($column)->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'tbfur_mov_uploads' => ['regional_replacement_used_at'],
            'tbfur_batch_documents' => ['regional_replacement_used_at'],
            'tbfur_fdp' => ['fdp_regional_replacement_used_at', 'posting_regional_replacement_used_at'],
            'tbfur_written_notice' => [
                'dbm_regional_replacement_used_at',
                'dilg_regional_replacement_used_at',
                'speaker_regional_replacement_used_at',
                'president_regional_replacement_used_at',
                'house_regional_replacement_used_at',
                'senate_regional_replacement_used_at',
            ],
        ];

        foreach ($columns as $tableName => $tableColumns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($tableColumns));
            }
        }
    }
};
