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
        // Add approval columns to tbfur_mov_uploads
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_mov_uploads', 'status')) {
                    $table->enum('status', ['pending', 'approved', 'returned'])->default('pending')->after('mov_file_path');
                }
                if (!Schema::hasColumn('tbfur_mov_uploads', 'approval_remarks')) {
                    $table->text('approval_remarks')->nullable()->after('status');
                }
                if (!Schema::hasColumn('tbfur_mov_uploads', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('approval_remarks');
                }
                if (!Schema::hasColumn('tbfur_mov_uploads', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }

        // Add approval columns to tbfur_written_notice
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_written_notice', 'status')) {
                    $table->enum('status', ['pending', 'approved', 'returned'])->default('pending')->after('senate_committee_path');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'approval_remarks')) {
                    $table->text('approval_remarks')->nullable()->after('status');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('approval_remarks');
                }
                if (!Schema::hasColumn('tbfur_written_notice', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }

        // Add approval columns to tbfur_fdp
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_fdp', 'status')) {
                    $table->enum('status', ['pending', 'approved', 'returned'])->default('pending')->after('fdp_file_path');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'approval_remarks')) {
                    $table->text('approval_remarks')->nullable()->after('status');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('approval_remarks');
                }
                if (!Schema::hasColumn('tbfur_fdp', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove approval columns from tbfur_mov_uploads
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                $table->dropColumn(['status', 'approval_remarks', 'approved_by', 'approved_at']);
            });
        }

        // Remove approval columns from tbfur_written_notice
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                $table->dropColumn(['status', 'approval_remarks', 'approved_by', 'approved_at']);
            });
        }

        // Remove approval columns from tbfur_fdp
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                $table->dropColumn(['status', 'approval_remarks', 'approved_by', 'approved_at']);
            });
        }
    }
};
