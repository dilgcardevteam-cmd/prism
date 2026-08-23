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
        if (Schema::hasTable('pisat_assessments')) {
            return;
        }

        Schema::create('pisat_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('status')->default('draft'); // draft, submitted, approved, returned
            $table->string('office')->nullable();

            // I. Project Profile
            $table->string('project_title')->nullable();
            $table->string('location')->nullable();
            $table->string('implementing_lgu')->nullable();
            $table->date('date_of_turnover')->nullable();
            $table->date('date_of_assessment')->nullable();
            $table->string('respondent_group')->nullable();

            // II. The "Most Significant Change" (MSC) Story
            $table->string('msc_title')->nullable();
            $table->text('msc_situation_before')->nullable();
            $table->text('msc_situation_now')->nullable();
            $table->text('msc_why_significant')->nullable();
            $table->string('msc_category')->nullable();

            // III. Project Impact Assessment
            $table->string('travel_time_savings_current')->nullable();
            $table->string('travel_time_savings_previous')->nullable();
            $table->integer('travel_time_savings_rating')->nullable();
            $table->text('asset_value_findings')->nullable();
            $table->integer('asset_value_rating')->nullable();
            $table->text('new_economic_activities_findings')->nullable();
            $table->integer('new_economic_activities_rating')->nullable();
            $table->text('agricultural_output_findings')->nullable();
            $table->integer('agricultural_output_rating')->nullable();

            $table->text('access_to_services_findings')->nullable();
            $table->integer('access_to_services_rating')->nullable();
            $table->text('health_outcomes_findings')->nullable();
            $table->integer('health_outcomes_rating')->nullable();
            $table->text('safety_security_findings')->nullable();
            $table->integer('safety_security_rating')->nullable();
            $table->text('community_pride_findings')->nullable();
            $table->integer('community_pride_rating')->nullable();

            $table->text('unexpected_positive')->nullable();
            $table->text('unexpected_negative')->nullable();

            // IV. Sustainability and Operational Assessment
            $table->string('manager_maintainer')->nullable();
            $table->string('organized_user_group')->nullable();
            $table->string('organization_recognized')->nullable();
            $table->string('has_om_fund')->nullable();
            $table->boolean('source_of_funds_user_fees')->default(false);
            $table->string('source_of_funds_user_fees_rate')->nullable();
            $table->boolean('source_of_funds_barangay')->default(false);
            $table->boolean('source_of_funds_municipal')->default(false);
            $table->boolean('source_of_funds_other')->default(false);
            $table->string('source_of_funds_other_desc')->nullable();
            $table->decimal('available_funds', 15, 2)->nullable();
            $table->string('functional_status')->nullable();
            $table->boolean('defect_structural_cracks')->default(false);
            $table->boolean('defect_drainage_leakage')->default(false);
            $table->boolean('defect_electrical_plumbing')->default(false);
            $table->boolean('defect_vandalism_wear')->default(false);
            $table->boolean('defect_other')->default(false);
            $table->string('defect_other_desc')->nullable();

            // V. Evaluation Findings and Proposed Actions
            $table->string('impact_classification')->nullable();
            $table->text('key_findings')->nullable();
            $table->text('proposed_actions')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('position')->nullable();
            $table->date('date_prepared')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('idno')->on('tbusers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pisat_assessments');
    }
};
