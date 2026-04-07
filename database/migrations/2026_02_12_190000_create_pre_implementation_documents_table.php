<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pre_implementation_documents')) {
            return;
        }

        Schema::create('pre_implementation_documents', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('project_title')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('funding_year', 16)->nullable();
            $table->string('mode_of_contract')->nullable();

            $table->string('signed_lgu_letter_path')->nullable();
            $table->string('signed_lgu_contact_details_path')->nullable();
            $table->string('nadai_path')->nullable();
            $table->string('confirmation_receipt_fund_path')->nullable();
            $table->string('proof_transfer_trust_fund_path')->nullable();
            $table->string('approved_ldip_path')->nullable();
            $table->string('approved_aip_path')->nullable();
            $table->string('approved_dtp_path')->nullable();
            $table->string('ecc_or_cnc_path')->nullable();
            $table->string('water_permit_or_application_path')->nullable();
            $table->string('fpic_or_ncip_certification_path')->nullable();
            $table->string('itb_posting_philgeps_path')->nullable();
            $table->string('noa_path')->nullable();
            $table->string('contract_path')->nullable();
            $table->string('ntp_path')->nullable();
            $table->string('land_ownership_path')->nullable();
            $table->string('right_of_way_path')->nullable();
            $table->string('moa_rural_electrification_path')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_implementation_documents');
    }
};

