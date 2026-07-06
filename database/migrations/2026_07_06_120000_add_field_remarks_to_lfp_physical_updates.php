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
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->text('status_project_fou_remarks')->nullable()->after('status_project_fou_updated_at');
            $table->unsignedInteger('status_project_fou_remarks_updated_by')->nullable()->after('status_project_fou_remarks');
            $table->timestamp('status_project_fou_remarks_updated_at')->nullable()->after('status_project_fou_remarks_updated_by');

            $table->text('status_project_ro_remarks')->nullable()->after('status_project_ro_updated_at');
            $table->unsignedInteger('status_project_ro_remarks_updated_by')->nullable()->after('status_project_ro_remarks');
            $table->timestamp('status_project_ro_remarks_updated_at')->nullable()->after('status_project_ro_remarks_updated_by');

            $table->text('accomplishment_pct_remarks')->nullable()->after('accomplishment_pct_updated_at');
            $table->unsignedInteger('accomplishment_pct_remarks_updated_by')->nullable()->after('accomplishment_pct_remarks');
            $table->timestamp('accomplishment_pct_remarks_updated_at')->nullable()->after('accomplishment_pct_remarks_updated_by');

            $table->text('accomplishment_pct_ro_remarks')->nullable()->after('accomplishment_pct_ro_updated_at');
            $table->unsignedInteger('accomplishment_pct_ro_remarks_updated_by')->nullable()->after('accomplishment_pct_ro_remarks');
            $table->timestamp('accomplishment_pct_ro_remarks_updated_at')->nullable()->after('accomplishment_pct_ro_remarks_updated_by');

            $table->text('slippage_remarks')->nullable()->after('slippage_updated_at');
            $table->unsignedInteger('slippage_remarks_updated_by')->nullable()->after('slippage_remarks');
            $table->timestamp('slippage_remarks_updated_at')->nullable()->after('slippage_remarks_updated_by');

            $table->text('slippage_ro_remarks')->nullable()->after('slippage_ro_updated_at');
            $table->unsignedInteger('slippage_ro_remarks_updated_by')->nullable()->after('slippage_ro_remarks');
            $table->timestamp('slippage_ro_remarks_updated_at')->nullable()->after('slippage_ro_remarks_updated_by');

            $table->text('risk_aging_remarks')->nullable()->after('risk_aging_updated_at');
            $table->unsignedInteger('risk_aging_remarks_updated_by')->nullable()->after('risk_aging_remarks');
            $table->timestamp('risk_aging_remarks_updated_at')->nullable()->after('risk_aging_remarks_updated_by');

            $table->text('nc_letters_remarks')->nullable()->after('nc_letters_updated_at');
            $table->unsignedInteger('nc_letters_remarks_updated_by')->nullable()->after('nc_letters_remarks');
            $table->timestamp('nc_letters_remarks_updated_at')->nullable()->after('nc_letters_remarks_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->dropColumn([
                'status_project_fou_remarks',
                'status_project_fou_remarks_updated_by',
                'status_project_fou_remarks_updated_at',
                'status_project_ro_remarks',
                'status_project_ro_remarks_updated_by',
                'status_project_ro_remarks_updated_at',
                'accomplishment_pct_remarks',
                'accomplishment_pct_remarks_updated_by',
                'accomplishment_pct_remarks_updated_at',
                'accomplishment_pct_ro_remarks',
                'accomplishment_pct_ro_remarks_updated_by',
                'accomplishment_pct_ro_remarks_updated_at',
                'slippage_remarks',
                'slippage_remarks_updated_by',
                'slippage_remarks_updated_at',
                'slippage_ro_remarks',
                'slippage_ro_remarks_updated_by',
                'slippage_ro_remarks_updated_at',
                'risk_aging_remarks',
                'risk_aging_remarks_updated_by',
                'risk_aging_remarks_updated_at',
                'nc_letters_remarks',
                'nc_letters_remarks_updated_by',
                'nc_letters_remarks_updated_at',
            ]);
        });
    }
};
