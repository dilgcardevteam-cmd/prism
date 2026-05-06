<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lgsf_project_completion_reports')) {
            return;
        }

        Schema::create('lgsf_project_completion_reports', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('project_title')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('funding_year', 16)->nullable();

            $table->string('project_completion_report_path')->nullable();
            $table->string('statement_of_work_accomplished_path')->nullable();
            $table->string('as_built_plans_path')->nullable();
            $table->string('certificate_of_completion_path')->nullable();
            $table->string('statement_of_receipts_and_disbursements_path')->nullable();
            $table->string('photos_path')->nullable();
            $table->string('proof_of_reversion_of_unexpended_funds_path')->nullable();
            $table->string('copy_of_or_cr_for_vehicles_path')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgsf_project_completion_reports');
    }
};
