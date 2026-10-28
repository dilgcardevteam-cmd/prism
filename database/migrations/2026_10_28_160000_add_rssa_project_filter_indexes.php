<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rssa_project_profiles')) {
            return;
        }

        $indexes = [
            'rssa_profiles_province_city_idx' => ['province', 'city_municipality'],
            'rssa_profiles_funding_year_idx' => ['funding_year'],
            'rssa_profiles_type_idx' => ['type_of_project'],
            'rssa_profiles_status_idx' => ['status'],
            'rssa_profiles_region_idx' => ['region'],
        ];

        foreach ($indexes as $name => $columns) {
            if (!Schema::hasColumns('rssa_project_profiles', $columns) || Schema::hasIndex('rssa_project_profiles', $name)) {
                continue;
            }

            if (DB::getDriverName() === 'mysql') {
                $indexedColumns = implode(', ', array_map(
                    static fn (string $column): string => "`{$column}`(191)",
                    $columns,
                ));
                DB::statement("CREATE INDEX `{$name}` ON `rssa_project_profiles` ({$indexedColumns})");
                continue;
            }

            Schema::table('rssa_project_profiles', function ($table) use ($columns, $name): void {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rssa_project_profiles')) {
            return;
        }

        foreach ([
            'rssa_profiles_province_city_idx',
            'rssa_profiles_funding_year_idx',
            'rssa_profiles_type_idx',
            'rssa_profiles_status_idx',
            'rssa_profiles_region_idx',
        ] as $index) {
            if (!Schema::hasIndex('rssa_project_profiles', $index)) {
                continue;
            }

            if (DB::getDriverName() === 'mysql') {
                DB::statement("DROP INDEX `{$index}` ON `rssa_project_profiles`");
                continue;
            }

            Schema::table('rssa_project_profiles', function ($table) use ($index): void {
                $table->dropIndex($index);
            });
        }
    }
};
