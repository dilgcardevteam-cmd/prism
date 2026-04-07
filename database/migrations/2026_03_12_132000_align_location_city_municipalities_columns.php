<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_city_municipalities')) {
            return;
        }

        Schema::table('location_city_municipalities', function (Blueprint $table) {
            if (!Schema::hasColumn('location_city_municipalities', 'province_id')) {
                $table->unsignedBigInteger('province_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'citymun_code')) {
                $table->string('citymun_code', 60)->nullable()->after('province_id')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'citymun_name')) {
                $table->string('citymun_name', 150)->nullable()->after('citymun_code')->index();
            }
        });

        if (Schema::hasTable('location_provinces')) {
            DB::statement("
                UPDATE location_city_municipalities cm
                LEFT JOIN location_provinces p
                    ON (
                        (cm.province_id IS NULL OR cm.province_id = 0)
                        AND (
                            (
                                " . (Schema::hasColumn('location_provinces', 'code') && Schema::hasColumn('location_city_municipalities', 'province_code')
                                    ? "LOWER(TRIM(COALESCE(p.code, ''))) = LOWER(TRIM(COALESCE(cm.province_code, '')))"
                                    : "0 = 1") . "
                            )
                            OR
                            (
                                " . (Schema::hasColumn('location_provinces', 'name') && Schema::hasColumn('location_city_municipalities', 'province_name')
                                    ? "LOWER(TRIM(COALESCE(p.name, ''))) = LOWER(TRIM(COALESCE(cm.province_name, '')))"
                                    : "0 = 1") . "
                            )
                        )
                    )
                SET cm.province_id = COALESCE(cm.province_id, p.id)
            ");
        }

        if (Schema::hasColumn('location_city_municipalities', 'code')) {
            DB::table('location_city_municipalities')->update([
                'citymun_code' => DB::raw('COALESCE(citymun_code, code)'),
            ]);
        }

        if (Schema::hasColumn('location_city_municipalities', 'name')) {
            DB::table('location_city_municipalities')->update([
                'citymun_name' => DB::raw('COALESCE(citymun_name, name)'),
            ]);
        }

        $columnsToDrop = [];
        foreach (['region_code', 'region_name', 'province_code', 'province_name', 'code', 'name', 'type', 'psgc_code'] as $column) {
            if (Schema::hasColumn('location_city_municipalities', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('location_city_municipalities', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('location_city_municipalities')) {
            return;
        }

        Schema::table('location_city_municipalities', function (Blueprint $table) {
            if (!Schema::hasColumn('location_city_municipalities', 'region_code')) {
                $table->string('region_code', 60)->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'region_name')) {
                $table->string('region_name', 150)->nullable()->after('region_code')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'province_code')) {
                $table->string('province_code', 60)->nullable()->after('region_name')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'province_name')) {
                $table->string('province_name', 150)->nullable()->after('province_code')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'code')) {
                $table->string('code', 60)->nullable()->after('province_name')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'name')) {
                $table->string('name', 150)->nullable()->after('code')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'type')) {
                $table->string('type', 60)->nullable()->after('name')->index();
            }

            if (!Schema::hasColumn('location_city_municipalities', 'psgc_code')) {
                $table->string('psgc_code', 60)->nullable()->after('type')->index();
            }
        });

        if (Schema::hasColumn('location_city_municipalities', 'citymun_code')) {
            DB::table('location_city_municipalities')->update([
                'code' => DB::raw('COALESCE(code, citymun_code)'),
            ]);
        }

        if (Schema::hasColumn('location_city_municipalities', 'citymun_name')) {
            DB::table('location_city_municipalities')->update([
                'name' => DB::raw('COALESCE(name, citymun_name)'),
            ]);
        }

        $columnsToDrop = [];
        foreach (['province_id', 'citymun_code', 'citymun_name'] as $column) {
            if (Schema::hasColumn('location_city_municipalities', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('location_city_municipalities', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
