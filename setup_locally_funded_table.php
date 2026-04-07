<?php

/**
 * Setup Script for Locally Funded Projects Table
 * Run this script to create the locally_funded_projects table
 */

require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Check if table already exists
    if (Schema::hasTable('locally_funded_projects')) {
        echo "✓ Table 'locally_funded_projects' already exists.\n";
        exit(0);
    }

    // Create the table
    Schema::create('locally_funded_projects', function ($table) {
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
        $table->string('rainwater_collection_system');
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

        // Financial Accomplishment
        $table->decimal('disbursed_amount', 15, 2)->nullable();
        $table->decimal('obligation', 15, 2)->nullable();
        $table->decimal('reverted_amount', 15, 2)->nullable();
        $table->decimal('balance', 15, 2)->nullable();
        $table->decimal('utilization_rate', 5, 2)->nullable();
        $table->longText('financial_remarks')->nullable();
        
        $table->timestamps();
        
        // Foreign key
        $table->foreign('user_id')->references('idno')->on('tbusers')->onDelete('cascade');
        
        // Indexes
        $table->index('user_id');
        $table->index('province');
        $table->index('city_municipality');
        $table->index('subaybayan_project_code');
    });

    echo "✓ Table 'locally_funded_projects' created successfully!\n";
    
} catch (\Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
?>
