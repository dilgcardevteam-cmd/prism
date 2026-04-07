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
        // Add encoder_id to tbfur_mov_uploads
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_mov_uploads', 'encoder_id')) {
                    $table->unsignedBigInteger('encoder_id')->nullable()->after('updated_at');
                }
            });
        }

        // Add encoder_id to tbfur_written_notice
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_written_notice', 'encoder_id')) {
                    $table->unsignedBigInteger('encoder_id')->nullable()->after('updated_at');
                }
            });
        }

        // Add encoder_id to tbfur_fdp
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_fdp', 'encoder_id')) {
                    $table->unsignedBigInteger('encoder_id')->nullable()->after('updated_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove encoder_id from tbfur_mov_uploads
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                $table->dropColumn('encoder_id');
            });
        }

        // Remove encoder_id from tbfur_written_notice
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                $table->dropColumn('encoder_id');
            });
        }

        // Remove encoder_id from tbfur_fdp
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                $table->dropColumn('encoder_id');
            });
        }
    }
};

