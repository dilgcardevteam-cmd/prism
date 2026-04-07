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
        // Add DILG PO and RO approval timestamp columns to MOV uploads
        Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_mov_uploads', 'approved_at_dilg_po')) {
                $table->datetime('approved_at_dilg_po')->nullable()->after('approved_at')->comment('DILG PO (Province Office) Level Approval Time');
            }
            if (!Schema::hasColumn('tbfur_mov_uploads', 'approved_at_dilg_ro')) {
                $table->datetime('approved_at_dilg_ro')->nullable()->after('approved_at_dilg_po')->comment('DILG RO (Regional Office) Level Approval Time');
            }
        });

        // Add DILG PO and RO approval timestamp columns to Written Notices
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_written_notice', 'approved_at_dilg_po')) {
                $table->datetime('approved_at_dilg_po')->nullable()->after('approved_at')->comment('DILG PO (Province Office) Level Approval Time');
            }
            if (!Schema::hasColumn('tbfur_written_notice', 'approved_at_dilg_ro')) {
                $table->datetime('approved_at_dilg_ro')->nullable()->after('approved_at_dilg_po')->comment('DILG RO (Regional Office) Level Approval Time');
            }
        });

        // Add DILG PO and RO approval timestamp columns to FDP
        Schema::table('tbfur_fdp', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_fdp', 'approved_at_dilg_po')) {
                $table->datetime('approved_at_dilg_po')->nullable()->after('approved_at')->comment('DILG PO (Province Office) Level Approval Time');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'approved_at_dilg_ro')) {
                $table->datetime('approved_at_dilg_ro')->nullable()->after('approved_at_dilg_po')->comment('DILG RO (Regional Office) Level Approval Time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
            $table->dropColumn(['approved_at_dilg_po', 'approved_at_dilg_ro']);
        });

        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->dropColumn(['approved_at_dilg_po', 'approved_at_dilg_ro']);
        });

        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->dropColumn(['approved_at_dilg_po', 'approved_at_dilg_ro']);
        });
    }
};
