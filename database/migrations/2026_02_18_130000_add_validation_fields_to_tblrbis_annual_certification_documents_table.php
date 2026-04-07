<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblrbis_annual_certification_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_at_dilg_po')) {
                $table->timestamp('approved_at_dilg_po')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_at_dilg_ro')) {
                $table->timestamp('approved_at_dilg_ro')->nullable()->after('approved_at_dilg_po');
            }
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_by_dilg_po')) {
                $table->unsignedBigInteger('approved_by_dilg_po')->nullable()->after('approved_at_dilg_ro');
            }
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_by_dilg_ro')) {
                $table->unsignedBigInteger('approved_by_dilg_ro')->nullable()->after('approved_by_dilg_po');
            }
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'approval_remarks')) {
                $table->text('approval_remarks')->nullable()->after('approved_by_dilg_ro');
            }
            if (!Schema::hasColumn('tblrbis_annual_certification_documents', 'user_remarks')) {
                $table->text('user_remarks')->nullable()->after('approval_remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tblrbis_annual_certification_documents', function (Blueprint $table) {
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'user_remarks')) {
                $table->dropColumn('user_remarks');
            }
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'approval_remarks')) {
                $table->dropColumn('approval_remarks');
            }
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_by_dilg_ro')) {
                $table->dropColumn('approved_by_dilg_ro');
            }
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_by_dilg_po')) {
                $table->dropColumn('approved_by_dilg_po');
            }
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_at_dilg_ro')) {
                $table->dropColumn('approved_at_dilg_ro');
            }
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_at_dilg_po')) {
                $table->dropColumn('approved_at_dilg_po');
            }
            if (Schema::hasColumn('tblrbis_annual_certification_documents', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
