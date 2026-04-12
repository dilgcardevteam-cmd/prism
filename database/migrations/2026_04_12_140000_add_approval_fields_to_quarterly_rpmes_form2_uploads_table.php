<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quarterly_rpmes_form2_uploads')) {
            return;
        }

        Schema::table('quarterly_rpmes_form2_uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'status')) {
                $table->string('status')->default('pending')->after('uploaded_at');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approved_at_dilg_po')) {
                $table->timestamp('approved_at_dilg_po')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approved_at_dilg_ro')) {
                $table->timestamp('approved_at_dilg_ro')->nullable()->after('approved_at_dilg_po');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approved_by_dilg_po')) {
                $table->unsignedBigInteger('approved_by_dilg_po')->nullable()->after('approved_at_dilg_ro');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approved_by_dilg_ro')) {
                $table->unsignedBigInteger('approved_by_dilg_ro')->nullable()->after('approved_by_dilg_po');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'approval_remarks')) {
                $table->text('approval_remarks')->nullable()->after('approved_by_dilg_ro');
            }

            if (!Schema::hasColumn('quarterly_rpmes_form2_uploads', 'user_remarks')) {
                $table->text('user_remarks')->nullable()->after('approval_remarks');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quarterly_rpmes_form2_uploads')) {
            return;
        }

        Schema::table('quarterly_rpmes_form2_uploads', function (Blueprint $table) {
            $columns = [
                'status',
                'approved_by',
                'approved_at',
                'approved_at_dilg_po',
                'approved_at_dilg_ro',
                'approved_by_dilg_po',
                'approved_by_dilg_ro',
                'approval_remarks',
                'user_remarks',
            ];

            $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('quarterly_rpmes_form2_uploads', $column)));
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
