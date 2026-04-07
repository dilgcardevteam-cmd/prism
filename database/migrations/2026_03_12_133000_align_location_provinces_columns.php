<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_provinces')) {
            return;
        }

        Schema::table('location_provinces', function (Blueprint $table) {
            if (!Schema::hasColumn('location_provinces', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('location_provinces', 'province_code')) {
                $table->string('province_code', 60)->nullable()->after('region_id')->index();
            }

            if (!Schema::hasColumn('location_provinces', 'province_name')) {
                $table->string('province_name', 150)->nullable()->after('province_code')->index();
            }
        });

        if (Schema::hasTable('location_regions')) {
            DB::statement("
                UPDATE location_provinces p
                LEFT JOIN location_regions r
                    ON (
                        (p.region_id IS NULL OR p.region_id = 0)
                        AND (
                            (
                                " . (Schema::hasColumn('location_regions', 'region_code') && Schema::hasColumn('location_provinces', 'region_code')
                                    ? "LOWER(TRIM(COALESCE(r.region_code, ''))) = LOWER(TRIM(COALESCE(p.region_code, '')))"
                                    : "0 = 1") . "
                            )
                            OR
                            (
                                " . (Schema::hasColumn('location_regions', 'region_name') && Schema::hasColumn('location_provinces', 'region_name')
                                    ? "LOWER(TRIM(COALESCE(r.region_name, ''))) = LOWER(TRIM(COALESCE(p.region_name, '')))"
                                    : "0 = 1") . "
                            )
                        )
                    )
                SET p.region_id = COALESCE(p.region_id, r.id)
            ");
        }

        if (Schema::hasColumn('location_provinces', 'code')) {
            DB::table('location_provinces')->update([
                'province_code' => DB::raw('COALESCE(province_code, code)'),
            ]);
        }

        if (Schema::hasColumn('location_provinces', 'name')) {
            DB::table('location_provinces')->update([
                'province_name' => DB::raw('COALESCE(province_name, name)'),
            ]);
        }

        $columnsToDrop = [];
        foreach (['region_code', 'region_name', 'code', 'name', 'psgc_code'] as $column) {
            if (Schema::hasColumn('location_provinces', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('location_provinces', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('location_provinces')) {
            return;
        }

        Schema::table('location_provinces', function (Blueprint $table) {
            if (!Schema::hasColumn('location_provinces', 'region_code')) {
                $table->string('region_code', 60)->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('location_provinces', 'region_name')) {
                $table->string('region_name', 150)->nullable()->after('region_code')->index();
            }

            if (!Schema::hasColumn('location_provinces', 'code')) {
                $table->string('code', 60)->nullable()->after('region_name')->index();
            }

            if (!Schema::hasColumn('location_provinces', 'name')) {
                $table->string('name', 150)->nullable()->after('code')->index();
            }

            if (!Schema::hasColumn('location_provinces', 'psgc_code')) {
                $table->string('psgc_code', 60)->nullable()->after('name')->index();
            }
        });

        if (Schema::hasColumn('location_provinces', 'province_code')) {
            DB::table('location_provinces')->update([
                'code' => DB::raw('COALESCE(code, province_code)'),
            ]);
        }

        if (Schema::hasColumn('location_provinces', 'province_name')) {
            DB::table('location_provinces')->update([
                'name' => DB::raw('COALESCE(name, province_name)'),
            ]);
        }

        $columnsToDrop = [];
        foreach (['region_id', 'province_code', 'province_name'] as $column) {
            if (Schema::hasColumn('location_provinces', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('location_provinces', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
