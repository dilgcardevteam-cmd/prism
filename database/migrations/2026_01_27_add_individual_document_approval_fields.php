<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add individual approval fields for each document type in written_notice
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                // DBM Document approval fields
                if (!Schema::hasColumn('tbfur_written_notice', 'dbm_status')) {
                    $table->enum('dbm_status', ['pending', 'approved', 'returned'])->default('pending')->after('senate_committee_path');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dbm_approved_by')) {
                    $table->unsignedBigInteger('dbm_approved_by')->nullable()->after('dbm_status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dbm_approved_at')) {
                    $table->timestamp('dbm_approved_at')->nullable()->after('dbm_approved_by');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dbm_remarks')) {
                    $table->text('dbm_remarks')->nullable()->after('dbm_approved_at');
                }

                // DILG Document approval fields
                if (!Schema::hasColumn('tbfur_written_notice', 'dilg_status')) {
                    $table->enum('dilg_status', ['pending', 'approved', 'returned'])->default('pending')->after('dbm_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dilg_approved_by')) {
                    $table->unsignedBigInteger('dilg_approved_by')->nullable()->after('dilg_status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dilg_approved_at')) {
                    $table->timestamp('dilg_approved_at')->nullable()->after('dilg_approved_by');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dilg_remarks')) {
                    $table->text('dilg_remarks')->nullable()->after('dilg_approved_at');
                }

                // Speaker Document approval fields
                if (!Schema::hasColumn('tbfur_written_notice', 'speaker_status')) {
                    $table->enum('speaker_status', ['pending', 'approved', 'returned'])->default('pending')->after('dilg_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'speaker_approved_by')) {
                    $table->unsignedBigInteger('speaker_approved_by')->nullable()->after('speaker_status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'speaker_approved_at')) {
                    $table->timestamp('speaker_approved_at')->nullable()->after('speaker_approved_by');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'speaker_remarks')) {
                    $table->text('speaker_remarks')->nullable()->after('speaker_approved_at');
                }

                // President Document approval fields
                if (!Schema::hasColumn('tbfur_written_notice', 'president_status')) {
                    $table->enum('president_status', ['pending', 'approved', 'returned'])->default('pending')->after('speaker_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'president_approved_by')) {
                    $table->unsignedBigInteger('president_approved_by')->nullable()->after('president_status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'president_approved_at')) {
                    $table->timestamp('president_approved_at')->nullable()->after('president_approved_by');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'president_remarks')) {
                    $table->text('president_remarks')->nullable()->after('president_approved_at');
                }

                // House Document approval fields
                if (!Schema::hasColumn('tbfur_written_notice', 'house_status')) {
                    $table->enum('house_status', ['pending', 'approved', 'returned'])->default('pending')->after('president_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'house_approved_by')) {
                    $table->unsignedBigInteger('house_approved_by')->nullable()->after('house_status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'house_approved_at')) {
                    $table->timestamp('house_approved_at')->nullable()->after('house_approved_by');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'house_remarks')) {
                    $table->text('house_remarks')->nullable()->after('house_approved_at');
                }

                // Senate Document approval fields
                if (!Schema::hasColumn('tbfur_written_notice', 'senate_status')) {
                    $table->enum('senate_status', ['pending', 'approved', 'returned'])->default('pending')->after('house_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'senate_approved_by')) {
                    $table->unsignedBigInteger('senate_approved_by')->nullable()->after('senate_status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'senate_approved_at')) {
                    $table->timestamp('senate_approved_at')->nullable()->after('senate_approved_by');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'senate_remarks')) {
                    $table->text('senate_remarks')->nullable()->after('senate_approved_at');
                }
            });
        }

        // Add individual approval fields for FDP
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                // FDP Document approval fields
                if (!Schema::hasColumn('tbfur_fdp', 'fdp_status')) {
                    $table->enum('fdp_status', ['pending', 'approved', 'returned'])->default('pending')->after('status');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'fdp_approved_by')) {
                    $table->unsignedBigInteger('fdp_approved_by')->nullable()->after('fdp_status');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'fdp_approved_at')) {
                    $table->timestamp('fdp_approved_at')->nullable()->after('fdp_approved_by');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'fdp_remarks')) {
                    $table->text('fdp_remarks')->nullable()->after('fdp_approved_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                $table->dropColumn([
                    'dbm_status', 'dbm_approved_by', 'dbm_approved_at', 'dbm_remarks',
                    'dilg_status', 'dilg_approved_by', 'dilg_approved_at', 'dilg_remarks',
                    'speaker_status', 'speaker_approved_by', 'speaker_approved_at', 'speaker_remarks',
                    'president_status', 'president_approved_by', 'president_approved_at', 'president_remarks',
                    'house_status', 'house_approved_by', 'house_approved_at', 'house_remarks',
                    'senate_status', 'senate_approved_by', 'senate_approved_at', 'senate_remarks',
                ]);
            });
        }

        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                $table->dropColumn([
                    'fdp_status', 'fdp_approved_by', 'fdp_approved_at', 'fdp_remarks',
                ]);
            });
        }
    }
};
