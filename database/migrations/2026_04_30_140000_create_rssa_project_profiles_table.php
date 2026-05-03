<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rssa_project_profiles')) {
            return;
        }

        Schema::create('rssa_project_profiles', function (Blueprint $table) {
            $table->id();
            $table->text('program')->nullable();
            $table->text('project_code')->nullable();
            $table->text('project_title')->nullable();
            $table->text('region')->nullable();
            $table->text('province')->nullable();
            $table->text('city_municipality')->nullable();
            $table->text('funding_year')->nullable();
            $table->text('type_of_project')->nullable();
            $table->text('status')->nullable();
            $table->text('national_subsidy_original_allocation')->nullable();
            $table->text('lgu_counterpart_original_allocation')->nullable();
            $table->text('national_subsidy_cancelled_allocation')->nullable();
            $table->text('lgu_counterpart_cancelled_allocation')->nullable();
            $table->text('national_subsidy_reverted_amount')->nullable();
            $table->text('lgu_counterpart_reverted_amount')->nullable();
            $table->text('national_subsidy_revised_allocation')->nullable();
            $table->text('lgu_counterpart_revised_allocation')->nullable();
            $table->text('total_project_cost')->nullable();
            $table->text('implementing_unit')->nullable();
            $table->text('moi')->nullable();
            $table->text('date_of_project_completion')->nullable();
            $table->text('one_year')->nullable();
            $table->text('date_assessed')->nullable();
            $table->text('project_booked_as_asset')->nullable();
            $table->text('project_insured')->nullable();
            $table->text('project_is_functional')->nullable();
            $table->text('if_functional_yes')->nullable();
            $table->text('encoded_improvements')->nullable();
            $table->text('if_non_functional_state_the_reasons')->nullable();
            $table->text('no_of_months_non_functional')->nullable();
            $table->text('category_of_non_functionality')->nullable();
            $table->text('is_project_operational')->nullable();
            $table->text('if_operational_yes')->nullable();
            $table->text('who_maintains_the_facility')->nullable();
            $table->text('is_regularly_maintained')->nullable();
            $table->text('annual_maintenance_budget')->nullable();
            $table->text('if_no_state_the_reason')->nullable();
            $table->text('no_of_months_non_operational')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rssa_project_profiles');
    }
};
