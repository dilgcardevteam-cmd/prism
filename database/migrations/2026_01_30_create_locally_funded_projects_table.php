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
        Schema::create('locally_funded_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            
            // Project Profile
            $table->string('province');
            $table->string('city_municipality');
            $table->string('barangay');
            $table->string('project_name');
            $table->string('subaybayan_project_code')->unique();
            $table->longText('project_description');
            $table->string('project_type');
            $table->date('date_nadai');
            $table->decimal('lgsf_allocation', 15, 2);
            $table->decimal('lgu_counterpart', 15, 2);
            $table->integer('no_of_beneficiaries');
            $table->string('rainwater_collection_system'); // Yes/No
            $table->date('date_confirmation_fund_receipt');
            
            // Contract Information
            $table->string('mode_of_procurement');
            $table->string('implementing_unit');
            $table->date('date_posting_itb');
            $table->date('date_bid_opening');
            $table->date('date_noa');
            $table->date('date_ntp');
            $table->string('contractor');
            $table->decimal('contract_amount', 15, 2);
            $table->string('project_duration');
            $table->date('actual_start_date');
            $table->date('target_date_completion');
            $table->date('revised_target_date_completion')->nullable();
            $table->date('actual_date_completion')->nullable();
            $table->unsignedBigInteger('actual_date_completion_updated_by')->nullable();
            
            $table->timestamps();
            
            // Add foreign key
            $table->foreign('user_id')->references('idno')->on('tbusers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locally_funded_projects');
    }
};
