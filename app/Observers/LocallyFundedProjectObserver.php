<?php

namespace App\Observers;

use App\Models\LocallyFundedProject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LocallyFundedProjectObserver
{
    /**
     * Track project updates as append-only activity log entries.
     */
    public function updated(LocallyFundedProject $project): void
    {
        $changes = $project->getChanges();
        if (empty($changes)) {
            return;
        }

        $fieldMap = $this->fieldMap();
        $userId = Auth::id() ?: ($project->user_id ?: null);
        $timestamp = Carbon::now();

        foreach ($changes as $field => $value) {
            if (!array_key_exists($field, $fieldMap)) {
                continue;
            }

            $meta = $fieldMap[$field];
            $details = $this->formatDetails($field, $value);

            Log::channel('upload_timestamps')->info('Document action', [
                'module' => 'locally_funded',
                'project_id' => $project->id,
                'project_code' => $project->subaybayan_project_code,
                'action' => $meta['action'],
                'action_label' => ucfirst($meta['action']),
                'section' => $meta['section'],
                'field' => $meta['field'],
                'details' => $details,
                'action_timestamp' => $timestamp->format('Y-m-d H:i:s'),
                'user_id' => $userId,
            ]);
        }
    }

    private function fieldMap(): array
    {
        return [
            // Project profile
            'province' => ['section' => 'Project Profile', 'field' => 'Province', 'action' => 'update'],
            'city_municipality' => ['section' => 'Project Profile', 'field' => 'City/Municipality', 'action' => 'update'],
            'barangay' => ['section' => 'Project Profile', 'field' => 'Barangay', 'action' => 'update'],
            'project_name' => ['section' => 'Project Profile', 'field' => 'Project Name', 'action' => 'update'],
            'funding_year' => ['section' => 'Project Profile', 'field' => 'Funding Year', 'action' => 'update'],
            'fund_source' => ['section' => 'Project Profile', 'field' => 'Fund Source', 'action' => 'update'],
            'subaybayan_project_code' => ['section' => 'Project Profile', 'field' => 'SubayBAYAN Project Code', 'action' => 'update'],
            'project_description' => ['section' => 'Project Profile', 'field' => 'Project Description', 'action' => 'update'],
            'project_type' => ['section' => 'Project Profile', 'field' => 'Project Type', 'action' => 'update'],
            'date_nadai' => ['section' => 'Project Profile', 'field' => 'Date NADAI', 'action' => 'update'],
            'lgsf_allocation' => ['section' => 'Project Profile', 'field' => 'LGSF Allocation', 'action' => 'update'],
            'lgu_counterpart' => ['section' => 'Project Profile', 'field' => 'LGU Counterpart', 'action' => 'update'],
            'no_of_beneficiaries' => ['section' => 'Project Profile', 'field' => 'No. of Beneficiaries', 'action' => 'update'],
            'rainwater_collection_system' => ['section' => 'Project Profile', 'field' => 'Rainwater Collection System', 'action' => 'update'],
            'date_confirmation_fund_receipt' => ['section' => 'Project Profile', 'field' => 'Date Confirmation Fund Receipt', 'action' => 'update'],

            // Contract info
            'mode_of_procurement' => ['section' => 'Contract Information', 'field' => 'Mode of Procurement', 'action' => 'update'],
            'implementing_unit' => ['section' => 'Contract Information', 'field' => 'Implementing Unit', 'action' => 'update'],
            'date_posting_itb' => ['section' => 'Contract Information', 'field' => 'Date Posting ITB', 'action' => 'update'],
            'date_bid_opening' => ['section' => 'Contract Information', 'field' => 'Date Bid Opening', 'action' => 'update'],
            'date_noa' => ['section' => 'Contract Information', 'field' => 'Date NOA', 'action' => 'update'],
            'date_ntp' => ['section' => 'Contract Information', 'field' => 'Date NTP', 'action' => 'update'],
            'contractor' => ['section' => 'Contract Information', 'field' => 'Contractor', 'action' => 'update'],
            'contract_amount' => ['section' => 'Contract Information', 'field' => 'Contract Amount', 'action' => 'update'],
            'project_duration' => ['section' => 'Contract Information', 'field' => 'Project Duration', 'action' => 'update'],
            'actual_start_date' => ['section' => 'Contract Information', 'field' => 'Actual Start Date', 'action' => 'update'],
            'target_date_completion' => ['section' => 'Contract Information', 'field' => 'Target Date Completion', 'action' => 'update'],
            'revised_target_date_completion' => ['section' => 'Contract Information', 'field' => 'Revised Target Date Completion', 'action' => 'update'],
            'actual_date_completion' => ['section' => 'Physical', 'field' => 'Actual Date of Completion', 'action' => 'update'],

            // Physical / financial remarks on project row
            'physical_remarks' => ['section' => 'Physical', 'field' => 'Physical Remarks', 'action' => 'remarks'],
            'financial_remarks' => ['section' => 'Financial', 'field' => 'Financial Remarks', 'action' => 'remarks'],

            // Monitoring
            'po_monitoring_date' => ['section' => 'Monitoring', 'field' => 'PO Monitoring Date', 'action' => 'update'],
            'po_final_inspection' => ['section' => 'Monitoring', 'field' => 'PO Final Inspection', 'action' => 'update'],
            'po_remarks' => ['section' => 'Monitoring', 'field' => 'PO Remarks', 'action' => 'remarks'],
            'ro_monitoring_date' => ['section' => 'Monitoring', 'field' => 'RO Monitoring Date', 'action' => 'update'],
            'ro_final_inspection' => ['section' => 'Monitoring', 'field' => 'RO Final Inspection', 'action' => 'update'],
            'ro_remarks' => ['section' => 'Monitoring', 'field' => 'RO Remarks', 'action' => 'remarks'],

            // Post implementation
            'pcr_submission_deadline' => ['section' => 'Post Implementation', 'field' => 'PCR Submission Deadline', 'action' => 'update'],
            'pcr_date_submitted_to_po' => ['section' => 'Post Implementation', 'field' => 'PCR Date Submitted to PO', 'action' => 'update'],
            'pcr_mov_file_path' => ['section' => 'Post Implementation', 'field' => 'PCR MOV Upload', 'action' => 'upload'],
            'pcr_date_received_by_ro' => ['section' => 'Post Implementation', 'field' => 'PCR Date Received by RO', 'action' => 'update'],
            'pcr_remarks' => ['section' => 'Post Implementation', 'field' => 'PCR Remarks', 'action' => 'remarks'],
            'rssa_report_deadline' => ['section' => 'Post Implementation', 'field' => 'RSSA Report Deadline', 'action' => 'update'],
            'rssa_submission_status' => ['section' => 'Post Implementation', 'field' => 'RSSA Submission Status', 'action' => 'update'],
            'rssa_date_submitted_to_po' => ['section' => 'Post Implementation', 'field' => 'RSSA Date Submitted to PO', 'action' => 'update'],
            'rssa_date_received_by_ro' => ['section' => 'Post Implementation', 'field' => 'RSSA Date Received by RO', 'action' => 'update'],
            'rssa_date_submitted_to_co' => ['section' => 'Post Implementation', 'field' => 'RSSA Date Submitted to CO', 'action' => 'update'],
            'rssa_remarks' => ['section' => 'Post Implementation', 'field' => 'RSSA Remarks', 'action' => 'remarks'],
        ];
    }

    private function formatDetails(string $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($field, ['lgsf_allocation', 'lgu_counterpart', 'contract_amount', 'obligation', 'disbursed_amount', 'reverted_amount', 'balance'], true)) {
            return '₱ ' . number_format((float) $value, 2);
        }

        if ($field === 'utilization_rate') {
            return number_format((float) $value, 2) . '%';
        }

        if (str_ends_with($field, '_date') || str_starts_with($field, 'date_') || str_contains($field, '_deadline') || str_contains($field, '_submitted_') || str_contains($field, '_received_')) {
            try {
                return Carbon::parse((string) $value)->format('M d, Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        if ($field === 'pcr_mov_file_path') {
            $path = (string) $value;
            $fileName = basename($path);
            return $fileName !== '' ? $fileName : $path;
        }

        return (string) $value;
    }
}
