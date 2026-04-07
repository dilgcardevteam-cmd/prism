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
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            // PO Monitoring Fields
            $table->date('po_monitoring_date')->nullable();
            $table->timestamp('po_monitoring_date_updated_at')->nullable();
            $table->unsignedBigInteger('po_monitoring_date_updated_by')->nullable();

            $table->string('po_final_inspection')->nullable();
            $table->timestamp('po_final_inspection_updated_at')->nullable();
            $table->unsignedBigInteger('po_final_inspection_updated_by')->nullable();

            $table->longText('po_remarks')->nullable();
            $table->timestamp('po_remarks_updated_at')->nullable();
            $table->unsignedBigInteger('po_remarks_updated_by')->nullable();

            // RO Monitoring Fields
            $table->date('ro_monitoring_date')->nullable();
            $table->timestamp('ro_monitoring_date_updated_at')->nullable();
            $table->unsignedBigInteger('ro_monitoring_date_updated_by')->nullable();

            $table->string('ro_final_inspection')->nullable();
            $table->timestamp('ro_final_inspection_updated_at')->nullable();
            $table->unsignedBigInteger('ro_final_inspection_updated_by')->nullable();

            $table->longText('ro_remarks')->nullable();
            $table->timestamp('ro_remarks_updated_at')->nullable();
            $table->unsignedBigInteger('ro_remarks_updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->dropColumn([
                'po_monitoring_date',
                'po_monitoring_date_updated_at',
                'po_monitoring_date_updated_by',
                'po_final_inspection',
                'po_final_inspection_updated_at',
                'po_final_inspection_updated_by',
                'po_remarks',
                'po_remarks_updated_at',
                'po_remarks_updated_by',
                'ro_monitoring_date',
                'ro_monitoring_date_updated_at',
                'ro_monitoring_date_updated_by',
                'ro_final_inspection',
                'ro_final_inspection_updated_at',
                'ro_final_inspection_updated_by',
                'ro_remarks',
                'ro_remarks_updated_at',
                'ro_remarks_updated_by',
            ]);
        });
    }
};
