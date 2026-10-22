<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfPossible('tbfur', ['province', 'fund_source', 'funding_year'], 'tbfur_listing_scope_idx');
        $this->addIndexIfPossible('tbfur_mov_uploads', ['project_code', 'quarter'], 'tbfur_mov_project_quarter_idx');
        $this->addIndexIfPossible('tbfur_batch_documents', ['project_code', 'quarter'], 'tbfur_batch_project_quarter_idx');
        $this->addIndexIfPossible('tbfur_written_notice', ['project_code', 'quarter'], 'tbfur_notice_project_quarter_idx');
        $this->addIndexIfPossible('tbfur_fdp', ['project_code', 'quarter'], 'tbfur_fdp_project_quarter_idx');
        $this->addIndexIfPossible('fund_utilization_approval_workflows', ['status', 'current_approval_level'], 'fur_workflows_status_level_idx');
    }

    public function down(): void
    {
        $this->dropIndexIfPresent('tbfur', 'tbfur_listing_scope_idx');
        $this->dropIndexIfPresent('tbfur_mov_uploads', 'tbfur_mov_project_quarter_idx');
        $this->dropIndexIfPresent('tbfur_batch_documents', 'tbfur_batch_project_quarter_idx');
        $this->dropIndexIfPresent('tbfur_written_notice', 'tbfur_notice_project_quarter_idx');
        $this->dropIndexIfPresent('tbfur_fdp', 'tbfur_fdp_project_quarter_idx');
        $this->dropIndexIfPresent('fund_utilization_approval_workflows', 'fur_workflows_status_level_idx');
    }

    private function addIndexIfPossible(string $table, array $columns, string $index): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if (!method_exists(Schema::getFacadeRoot(), 'getIndexes') || collect(Schema::getIndexes($table))->contains('name', $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndexIfPresent(string $table, string $index): void
    {
        if (!Schema::hasTable($table) || !method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return;
        }

        if (!collect(Schema::getIndexes($table))->contains('name', $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }
};
