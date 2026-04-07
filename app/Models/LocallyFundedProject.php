<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocallyFundedProject extends Model
{
    use HasFactory;

    protected $table = 'locally_funded_projects';

    protected $fillable = [
        // Project Profile
        'province',
        'office',
        'region',
        'city_municipality',
        'barangay',
        'project_name',
        'funding_year',
        'fund_source',
        'subaybayan_project_code',
        'project_description',
        'project_type',
        'date_nadai',
        'lgsf_allocation',
        'lgu_counterpart',
        'no_of_beneficiaries',
        'rainwater_collection_system',
        'date_confirmation_fund_receipt',

        // Contract Information
        'mode_of_procurement',
        'implementing_unit',
        'date_posting_itb',
        'date_bid_opening',
        'date_noa',
        'date_ntp',
        'contractor',
        'contract_amount',
        'project_duration',
        'actual_start_date',
        'target_date_completion',
        'revised_target_date_completion',
        'actual_date_completion',
        'actual_date_completion_updated_by',

        // Financial Accomplishment
        'disbursed_amount',
        'obligation',
        'reverted_amount',
        'balance',
        'utilization_rate',
        'financial_remarks',
        'financial_remarks_updated_at',
        'financial_remarks_updated_by',
        'financial_remarks_encoded_by',

        // Physical Accomplishment
        'physical_remarks',
        'physical_remarks_updated_at',
        'physical_remarks_updated_by',
        'physical_remarks_encoded_by',

        // Monitoring Fields
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

        // Post Implementation Requirements
        'pcr_submission_deadline',
        'pcr_submission_deadline_updated_at',
        'pcr_submission_deadline_updated_by',
        'pcr_date_submitted_to_po',
        'pcr_date_submitted_to_po_updated_at',
        'pcr_date_submitted_to_po_updated_by',
        'pcr_mov_file_path',
        'pcr_mov_uploaded_at',
        'pcr_mov_uploaded_by',
        'pcr_date_received_by_ro',
        'pcr_date_received_by_ro_updated_at',
        'pcr_date_received_by_ro_updated_by',
        'pcr_remarks',
        'pcr_remarks_updated_at',
        'pcr_remarks_updated_by',
        'rssa_report_deadline',
        'rssa_report_deadline_updated_at',
        'rssa_report_deadline_updated_by',
        'rssa_submission_status',
        'rssa_submission_status_updated_at',
        'rssa_submission_status_updated_by',
        'rssa_date_submitted_to_po',
        'rssa_date_submitted_to_po_updated_at',
        'rssa_date_submitted_to_po_updated_by',
        'rssa_date_received_by_ro',
        'rssa_date_received_by_ro_updated_at',
        'rssa_date_received_by_ro_updated_by',
        'rssa_date_submitted_to_co',
        'rssa_date_submitted_to_co_updated_at',
        'rssa_date_submitted_to_co_updated_by',
        'rssa_remarks',
        'rssa_remarks_updated_at',
        'rssa_remarks_updated_by',

        'user_id',
    ];

    protected $casts = [
        'date_nadai' => 'date',
        'date_confirmation_fund_receipt' => 'date',
        'date_posting_itb' => 'date',
        'date_bid_opening' => 'date',
        'date_noa' => 'date',
        'date_ntp' => 'date',
        'actual_start_date' => 'date',
        'target_date_completion' => 'date',
        'revised_target_date_completion' => 'date',
        'actual_date_completion' => 'date',
        'lgsf_allocation' => 'decimal:2',
        'lgu_counterpart' => 'decimal:2',
        'contract_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'obligation' => 'decimal:2',
        'reverted_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'utilization_rate' => 'decimal:2',
        'financial_remarks_updated_at' => 'datetime',
        'physical_remarks_updated_at' => 'datetime',
        'po_monitoring_date' => 'date',
        'po_monitoring_date_updated_at' => 'datetime',
        'po_final_inspection_updated_at' => 'datetime',
        'po_remarks_updated_at' => 'datetime',
        'ro_monitoring_date' => 'date',
        'ro_monitoring_date_updated_at' => 'datetime',
        'ro_final_inspection_updated_at' => 'datetime',
        'ro_remarks_updated_at' => 'datetime',
        'pcr_submission_deadline' => 'date',
        'pcr_submission_deadline_updated_at' => 'datetime',
        'pcr_date_submitted_to_po' => 'date',
        'pcr_date_submitted_to_po_updated_at' => 'datetime',
        'pcr_mov_uploaded_at' => 'datetime',
        'pcr_date_received_by_ro' => 'date',
        'pcr_date_received_by_ro_updated_at' => 'datetime',
        'pcr_remarks_updated_at' => 'datetime',
        'rssa_report_deadline' => 'date',
        'rssa_report_deadline_updated_at' => 'datetime',
        'rssa_submission_status_updated_at' => 'datetime',
        'rssa_date_submitted_to_po' => 'date',
        'rssa_date_submitted_to_po_updated_at' => 'datetime',
        'rssa_date_received_by_ro' => 'date',
        'rssa_date_received_by_ro_updated_at' => 'datetime',
        'rssa_date_submitted_to_co' => 'date',
        'rssa_date_submitted_to_co_updated_at' => 'datetime',
        'rssa_remarks_updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the project
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'idno');
    }
}
