<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs') && !Schema::hasIndex('activity_logs', 'activity_logs_created_at_idx')) {
            Schema::table('activity_logs', function (Blueprint $table): void {
                $table->index('created_at', 'activity_logs_created_at_idx');
            });
        }

        if (!Schema::hasTable('tbusers')) {
            return;
        }

        Schema::table('tbusers', function (Blueprint $table): void {
            if (!Schema::hasColumn('tbusers', 'role_key')) {
                $table->string('role_key')->virtualAs("LOWER(TRIM(COALESCE(role, '')))");
            }
            if (!Schema::hasColumn('tbusers', 'status_key')) {
                $table->string('status_key')->virtualAs("LOWER(TRIM(COALESCE(status, '')))");
            }
            if (!Schema::hasColumn('tbusers', 'province_key')) {
                $table->string('province_key')->virtualAs("LOWER(TRIM(COALESCE(province, '')))");
            }
            if (!Schema::hasColumn('tbusers', 'agency_key')) {
                $table->string('agency_key')->virtualAs("LOWER(TRIM(COALESCE(agency, '')))");
            }
            if (!Schema::hasColumn('tbusers', 'office_key')) {
                $table->string('office_key')->virtualAs("LOWER(TRIM(COALESCE(office, '')))");
            }
        });

        Schema::table('tbusers', function (Blueprint $table): void {
            if (!Schema::hasIndex('tbusers', 'tbusers_role_key_idx')) {
                $table->index('role_key', 'tbusers_role_key_idx');
            }
            if (!Schema::hasIndex('tbusers', 'tbusers_status_created_idx')) {
                $table->index(['status_key', 'created_at', 'idno'], 'tbusers_status_created_idx');
            }
            if (!Schema::hasIndex('tbusers', 'tbusers_province_key_idx')) {
                $table->index('province_key', 'tbusers_province_key_idx');
            }
            if (!Schema::hasIndex('tbusers', 'tbusers_agency_office_idx')) {
                $table->index(['agency_key', 'office_key'], 'tbusers_agency_office_idx');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_logs') && Schema::hasIndex('activity_logs', 'activity_logs_created_at_idx')) {
            Schema::table('activity_logs', function (Blueprint $table): void {
                $table->dropIndex('activity_logs_created_at_idx');
            });
        }

        if (!Schema::hasTable('tbusers')) {
            return;
        }

        Schema::table('tbusers', function (Blueprint $table): void {
            foreach ([
                'tbusers_role_key_idx',
                'tbusers_status_created_idx',
                'tbusers_province_key_idx',
                'tbusers_agency_office_idx',
            ] as $index) {
                if (Schema::hasIndex('tbusers', $index)) {
                    $table->dropIndex($index);
                }
            }

            $columns = array_values(array_filter(
                ['role_key', 'status_key', 'province_key', 'agency_key', 'office_key'],
                static fn (string $column): bool => Schema::hasColumn('tbusers', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
