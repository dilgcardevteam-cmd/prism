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
        // Add individual upload/update timestamps for MOV
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_mov_uploads', 'mov_uploaded_at')) {
                    $table->timestamp('mov_uploaded_at')->nullable()->after('mov_file_path');
                }
                if (!Schema::hasColumn('tbfur_mov_uploads', 'mov_encoder_id')) {
                    $table->unsignedBigInteger('mov_encoder_id')->nullable()->after('mov_uploaded_at');
                }
            });
        }

        // Add individual upload/update timestamps for each document type in written_notice
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                // DBM Document timestamps
                if (!Schema::hasColumn('tbfur_written_notice', 'dbm_uploaded_at')) {
                    $table->timestamp('dbm_uploaded_at')->nullable()->after('dbm_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dbm_encoder_id')) {
                    $table->unsignedBigInteger('dbm_encoder_id')->nullable()->after('dbm_uploaded_at');
                }

                // DILG Document timestamps
                if (!Schema::hasColumn('tbfur_written_notice', 'dilg_uploaded_at')) {
                    $table->timestamp('dilg_uploaded_at')->nullable()->after('dilg_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'dilg_encoder_id')) {
                    $table->unsignedBigInteger('dilg_encoder_id')->nullable()->after('dilg_uploaded_at');
                }

                // Speaker Document timestamps
                if (!Schema::hasColumn('tbfur_written_notice', 'speaker_uploaded_at')) {
                    $table->timestamp('speaker_uploaded_at')->nullable()->after('speaker_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'speaker_encoder_id')) {
                    $table->unsignedBigInteger('speaker_encoder_id')->nullable()->after('speaker_uploaded_at');
                }

                // President Document timestamps
                if (!Schema::hasColumn('tbfur_written_notice', 'president_uploaded_at')) {
                    $table->timestamp('president_uploaded_at')->nullable()->after('president_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'president_encoder_id')) {
                    $table->unsignedBigInteger('president_encoder_id')->nullable()->after('president_uploaded_at');
                }

                // House Document timestamps
                if (!Schema::hasColumn('tbfur_written_notice', 'house_uploaded_at')) {
                    $table->timestamp('house_uploaded_at')->nullable()->after('house_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'house_encoder_id')) {
                    $table->unsignedBigInteger('house_encoder_id')->nullable()->after('house_uploaded_at');
                }

                // Senate Document timestamps
                if (!Schema::hasColumn('tbfur_written_notice', 'senate_uploaded_at')) {
                    $table->timestamp('senate_uploaded_at')->nullable()->after('senate_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'senate_encoder_id')) {
                    $table->unsignedBigInteger('senate_encoder_id')->nullable()->after('senate_uploaded_at');
                }
            });
        }

        // Add individual upload/update timestamps for FDP
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_fdp', 'fdp_uploaded_at')) {
                    $table->timestamp('fdp_uploaded_at')->nullable()->after('fdp_remarks');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'fdp_encoder_id')) {
                    $table->unsignedBigInteger('fdp_encoder_id')->nullable()->after('fdp_uploaded_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                $table->dropColumn([
                    'mov_uploaded_at', 'mov_encoder_id',
                ]);
            });
        }

        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                $table->dropColumn([
                    'dbm_uploaded_at', 'dbm_encoder_id',
                    'dilg_uploaded_at', 'dilg_encoder_id',
                    'speaker_uploaded_at', 'speaker_encoder_id',
                    'president_uploaded_at', 'president_encoder_id',
                    'house_uploaded_at', 'house_encoder_id',
                    'senate_uploaded_at', 'senate_encoder_id',
                ]);
            });
        }

        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                $table->dropColumn([
                    'fdp_uploaded_at', 'fdp_encoder_id',
                ]);
            });
        }
    }
};
