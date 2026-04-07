<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('locally_funded_projects') || !Schema::hasTable('tbusers')) {
            return;
        }
        if (!$this->columnTypesMatch('locally_funded_projects', 'user_id', 'tbusers', 'idno')) {
            return;
        }

        $this->dropForeignKeysForColumn('locally_funded_projects', 'user_id');

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->foreign('user_id')->references('idno')->on('tbusers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('locally_funded_projects') || !Schema::hasTable('users')) {
            return;
        }
        if (!$this->columnTypesMatch('locally_funded_projects', 'user_id', 'users', 'id')) {
            return;
        }

        $this->dropForeignKeysForColumn('locally_funded_projects', 'user_id');

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        $database = DB::getDatabaseName();
        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->unique()
            ->values()
            ->all();

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function columnTypesMatch(string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        $database = DB::getDatabaseName();

        $baseType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('COLUMN_TYPE');

        $refType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $referencedTable)
            ->where('COLUMN_NAME', $referencedColumn)
            ->value('COLUMN_TYPE');

        return is_string($baseType) && is_string($refType) && strtolower($baseType) === strtolower($refType);
    }
};
