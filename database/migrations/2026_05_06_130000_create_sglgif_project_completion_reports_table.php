<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sglgif_project_completion_reports')) {
            return;
        }

        Schema::create('sglgif_project_completion_reports', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('project_title')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('funding_year', 16)->nullable();

            $table->string('corrective_measures_conducted_path')->nullable();
            $table->string('final_sord_with_refund_path')->nullable();
            $table->string('final_swa_path')->nullable();
            $table->string('project_completion_report_path')->nullable();
            $table->string('certificate_of_completion_path')->nullable();
            $table->string('certificate_of_occupancy_path')->nullable();
            $table->string('certificate_of_turnover_and_acceptance_path')->nullable();
            $table->string('warranty_certificate_path')->nullable();
            $table->string('geotagged_photos_path')->nullable();
            $table->string('copy_of_or_cr_under_lgu_name_path')->nullable();
            $table->string('copy_of_official_receipt_heavy_equipment_path')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sglgif_project_completion_reports');
    }
};
