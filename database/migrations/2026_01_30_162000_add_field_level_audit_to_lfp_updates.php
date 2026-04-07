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
            $table->unsignedInteger('status_project_fou_updated_by')->nullable()->after('status_project_fou');
            $table->timestamp('status_project_fou_updated_at')->nullable()->after('status_project_fou_updated_by');

            $table->unsignedInteger('status_project_ro_updated_by')->nullable()->after('status_project_ro');
            $table->timestamp('status_project_ro_updated_at')->nullable()->after('status_project_ro_updated_by');

            $table->unsignedInteger('accomplishment_pct_updated_by')->nullable()->after('accomplishment_pct');
            $table->timestamp('accomplishment_pct_updated_at')->nullable()->after('accomplishment_pct_updated_by');

            $table->unsignedInteger('slippage_updated_by')->nullable()->after('slippage');
            $table->timestamp('slippage_updated_at')->nullable()->after('slippage_updated_by');

            $table->unsignedInteger('risk_aging_updated_by')->nullable()->after('risk_aging');
            $table->timestamp('risk_aging_updated_at')->nullable()->after('risk_aging_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->dropColumn([
                'status_project_fou_updated_by',
                'status_project_fou_updated_at',
                'status_project_ro_updated_by',
                'status_project_ro_updated_at',
                'accomplishment_pct_updated_by',
                'accomplishment_pct_updated_at',
                'slippage_updated_by',
                'slippage_updated_at',
                'risk_aging_updated_by',
                'risk_aging_updated_at',
            ]);
        });
    }
};
