<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_regions')) {
            return;
        }

        Schema::table('location_regions', function (Blueprint $table) {
            if (!Schema::hasColumn('location_regions', 'region_code')) {
                $table->string('region_code', 60)->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('location_regions', 'region_name')) {
                $table->string('region_name', 150)->nullable()->after('region_code')->index();
            }
        });

        if (Schema::hasColumn('location_regions', 'code')) {
            DB::table('location_regions')->update([
                'region_code' => DB::raw('COALESCE(region_code, code)'),
            ]);
        }

        if (Schema::hasColumn('location_regions', 'name')) {
            DB::table('location_regions')->update([
                'region_name' => DB::raw('COALESCE(region_name, name)'),
            ]);
        }

        $columnsToDrop = [];
        foreach (['code', 'name', 'psgc_code', 'island_group', 'sort_order'] as $column) {
            if (Schema::hasColumn('location_regions', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('location_regions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('location_regions')) {
            return;
        }

        Schema::table('location_regions', function (Blueprint $table) {
            if (!Schema::hasColumn('location_regions', 'code')) {
                $table->string('code', 60)->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('location_regions', 'name')) {
                $table->string('name', 150)->nullable()->after('code')->index();
            }

            if (!Schema::hasColumn('location_regions', 'psgc_code')) {
                $table->string('psgc_code', 60)->nullable()->after('name')->index();
            }

            if (!Schema::hasColumn('location_regions', 'island_group')) {
                $table->string('island_group', 80)->nullable()->after('psgc_code');
            }

            if (!Schema::hasColumn('location_regions', 'sort_order')) {
                $table->unsignedInteger('sort_order')->nullable()->after('island_group');
            }
        });

        if (Schema::hasColumn('location_regions', 'region_code')) {
            DB::table('location_regions')->update([
                'code' => DB::raw('COALESCE(code, region_code)'),
            ]);
        }

        if (Schema::hasColumn('location_regions', 'region_name')) {
            DB::table('location_regions')->update([
                'name' => DB::raw('COALESCE(name, region_name)'),
            ]);
        }

        $columnsToDrop = [];
        foreach (['region_code', 'region_name'] as $column) {
            if (Schema::hasColumn('location_regions', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('location_regions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
