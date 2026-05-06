<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreImplementationDocument extends Model
{
    use HasFactory;

    protected $table = 'pre_implementation_documents';

    protected $fillable = [
        'project_code',
        'project_title',
        'province',
        'city_municipality',
        'funding_year',
        'mode_of_contract',
        'signed_lgu_letter_path',
        'nadai_path',
        'confirmation_receipt_fund_path',
        'proof_transfer_trust_fund_path',
        'approved_ldip_path',
        'approved_aip_path',
        'approved_dtp_path',
        'ecc_or_cnc_path',
        'water_permit_or_application_path',
        'fpic_or_ncip_certification_path',
        'itb_posting_philgeps_path',
        'noa_path',
        'contract_path',
        'ntp_path',
        'land_ownership_path',
        'right_of_way_path',
        'moa_rural_electrification_path',
        'program_of_works_path',
        'design_and_engineering_documents_path',
        'variation_orders_path',
        'suspensions_path',
        'work_resumptions_path',
        'time_extensions_path',
        'cancellation_termination_path',
        'updated_by',
    ];
}
