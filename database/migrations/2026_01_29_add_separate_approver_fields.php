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
        // Add separate approver fields for MOV uploads
        Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_mov_uploads', 'approved_by_dilg_po')) {
                $table->unsignedBigInteger('approved_by_dilg_po')->nullable()->after('approved_by')->comment('DILG PO (Province Office) Approver ID');
            }
            if (!Schema::hasColumn('tbfur_mov_uploads', 'approved_by_dilg_ro')) {
                $table->unsignedBigInteger('approved_by_dilg_ro')->nullable()->after('approved_by_dilg_po')->comment('DILG RO (Regional Office) Approver ID');
            }
        });

        // Add separate approver fields for Written Notices
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_written_notice', 'approved_by_dilg_po')) {
                $table->unsignedBigInteger('approved_by_dilg_po')->nullable()->after('approved_by')->comment('DILG PO (Province Office) Approver ID');
            }
            if (!Schema::hasColumn('tbfur_written_notice', 'approved_by_dilg_ro')) {
                $table->unsignedBigInteger('approved_by_dilg_ro')->nullable()->after('approved_by_dilg_po')->comment('DILG RO (Regional Office) Approver ID');
            }
        });

        // Add separate approver fields for FDP
        Schema::table('tbfur_fdp', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_fdp', 'approved_by_dilg_po')) {
                $table->unsignedBigInteger('approved_by_dilg_po')->nullable()->after('approved_by')->comment('DILG PO (Province Office) Approver ID');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'approved_by_dilg_ro')) {
                $table->unsignedBigInteger('approved_by_dilg_ro')->nullable()->after('approved_by_dilg_po')->comment('DILG RO (Regional Office) Approver ID');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
            $table->dropColumn(['approved_by_dilg_po', 'approved_by_dilg_ro']);
        });

        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->dropColumn(['approved_by_dilg_po', 'approved_by_dilg_ro']);
        });

        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->dropColumn(['approved_by_dilg_po', 'approved_by_dilg_ro']);
        });
    }
};
