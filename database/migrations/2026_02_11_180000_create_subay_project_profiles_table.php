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
        Schema::create('subay_project_profiles', function (Blueprint $table) {
            $table->id();
            $table->text('program')->nullable();
            $table->text('project_code')->nullable();
            $table->text('project_title')->nullable();
            $table->text('region')->nullable();
            $table->text('province')->nullable();
            $table->text('city_municipality')->nullable();
            $table->text('barangay')->nullable();
            $table->text('exact_location')->nullable();
            $table->text('type')->nullable();
            $table->text('project_description')->nullable();
            $table->text('road_length_in_km')->nullable();
            $table->text('funding_year')->nullable();
            $table->text('type_of_project')->nullable();
            $table->text('sub_type_of_project')->nullable();
            $table->text('procurement_type')->nullable();
            $table->text('procurement')->nullable();
            $table->text('beneficiaries')->nullable();
            $table->text('status')->nullable();
            $table->text('remarks')->nullable();
            $table->text('profile_approval_status')->nullable();
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
            $table->text('total_estimated_cost_of_project')->nullable();
            $table->text('duration')->nullable();
            $table->text('intended_completion_date')->nullable();
            $table->text('actual_start_of_construction')->nullable();
            $table->text('unit_implementing_the_project')->nullable();
            $table->text('name_of_contractor')->nullable();
            $table->text('contract_price')->nullable();
            $table->text('contract_duration')->nullable();
            $table->text('office_address')->nullable();
            $table->text('date_of_perfection_of_contract')->nullable();
            $table->text('intended_completion_date_2')->nullable();
            $table->text('date_of_receipt_of_ntp')->nullable();
            $table->text('date_of_expiration_of_contract')->nullable();
            $table->text('total_accomplishment')->nullable();
            $table->text('date')->nullable();
            $table->text('obligation')->nullable();
            $table->text('disbursement')->nullable();
            $table->text('liquidations')->nullable();
            $table->text('bid_opening_bid_evaluation')->nullable();
            $table->text('bid_opening_evaluation')->nullable();
            $table->text('date_of_nadai')->nullable();
            $table->text('date_of_receipt_of_notice_to_proceed')->nullable();
            $table->text('ded_pow_preparation')->nullable();
            $table->text('ded_pow_prep_notarized_lce_cert')->nullable();
            $table->text('ded_pow_review_and_approval')->nullable();
            $table->text('ded_pow_review_and_approval_2')->nullable();
            $table->text('endorsement_of_projects_to_dbm_for_the_release_of_saro')->nullable();
            $table->text('fs_technical_specification_and_ded_pow_preparation')->nullable();
            $table->text('fs_technical_specification_and_ded_pow_review_approval')->nullable();
            $table->text('fs_technical_specification_preparation')->nullable();
            $table->text('installation_of_community_billboard')->nullable();
            $table->text('installation_of_community_billboard_2')->nullable();
            $table->text('invitation_to_bid_ib_posted')->nullable();
            $table->text('moa_signing')->nullable();
            $table->text('no_objection_1')->nullable();
            $table->text('no_objection_2')->nullable();
            $table->text('no_objection_3')->nullable();
            $table->text('noa_issuance')->nullable();
            $table->text('project_billboard')->nullable();
            $table->text('submission_of_certificate_on_the_receipt_of_funds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subay_project_profiles');
    }
};
