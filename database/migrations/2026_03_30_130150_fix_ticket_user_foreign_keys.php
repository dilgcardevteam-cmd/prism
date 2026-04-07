<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        DB::statement('ALTER TABLE `tickets`
            MODIFY `submitted_by` INT UNSIGNED NOT NULL,
            MODIFY `assigned_to` INT UNSIGNED NULL,
            MODIFY `escalated_by` INT UNSIGNED NULL,
            MODIFY `forwarded_by` INT UNSIGNED NULL,
            MODIFY `resolved_by` INT UNSIGNED NULL');

        $this->ensureIndex('tickets', 'tickets_submitted_by_index', 'submitted_by');
        $this->ensureIndex('tickets', 'tickets_assigned_to_index', 'assigned_to');
        $this->ensureIndex('tickets', 'tickets_escalated_by_index', 'escalated_by');
        $this->ensureIndex('tickets', 'tickets_forwarded_by_index', 'forwarded_by');
        $this->ensureIndex('tickets', 'tickets_resolved_by_index', 'resolved_by');

        $this->ensureForeignKey(
            'tickets',
            'tickets_submitted_by_foreign',
            'submitted_by',
            'tbusers',
            'idno',
            'CASCADE'
        );
        $this->ensureForeignKey(
            'tickets',
            'tickets_assigned_to_foreign',
            'assigned_to',
            'tbusers',
            'idno',
            'SET NULL'
        );
        $this->ensureForeignKey(
            'tickets',
            'tickets_escalated_by_foreign',
            'escalated_by',
            'tbusers',
            'idno',
            'SET NULL'
        );
        $this->ensureForeignKey(
            'tickets',
            'tickets_forwarded_by_foreign',
            'forwarded_by',
            'tbusers',
            'idno',
            'SET NULL'
        );
        $this->ensureForeignKey(
            'tickets',
            'tickets_resolved_by_foreign',
            'resolved_by',
            'tbusers',
            'idno',
            'SET NULL'
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        $this->dropForeignKeyIfExists('tickets', 'tickets_resolved_by_foreign');
        $this->dropForeignKeyIfExists('tickets', 'tickets_forwarded_by_foreign');
        $this->dropForeignKeyIfExists('tickets', 'tickets_escalated_by_foreign');
        $this->dropForeignKeyIfExists('tickets', 'tickets_assigned_to_foreign');
        $this->dropForeignKeyIfExists('tickets', 'tickets_submitted_by_foreign');
    }

    private function ensureIndex(string $table, string $indexName, string $column): void
    {
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if (! $exists) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD INDEX `%s` (`%s`)',
                $table,
                $indexName,
                $column
            ));
        }
    }

    private function ensureForeignKey(
        string $table,
        string $constraintName,
        string $column,
        string $referencesTable,
        string $referencesColumn,
        string $onDelete
    ): void {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (! $exists) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s',
                $table,
                $constraintName,
                $column,
                $referencesTable,
                $referencesColumn,
                $onDelete
            ));
        }
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $constraintName
            ));
        }
    }
};
