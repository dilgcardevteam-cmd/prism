<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegionalApprovalPoolService
{
    /**
     * Build the regional approval pool from current document status, never notifications.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pendingTasks(bool $includeProvincial = false): Collection
    {
        $tasks = collect();

        $sources = [
            ['table' => 'tblmonitoring_evaluation_monthly_documents', 'module_key' => 'monitoring-evaluation', 'module_label' => 'Monitoring Evaluation Monthly', 'url' => '/reports/dilg-deliverables/monitoring-and-evaluation-reports/lfp', 'period' => 'month'],
            ['table' => 'tblpd_no_pbbm_2025_1572_1573_documents', 'module_key' => 'pd-no-pbbm-2025-1572-1573', 'module_label' => 'PD No. PBBM-2025-1572-1573', 'url' => '/reports/monthly/pd-no-pbbm-2025-1572-1573', 'period' => 'month'],
            ['table' => 'tblpmc_documents', 'module_key' => 'local-project-monitoring-committee', 'module_label' => 'Local Project Monitoring Committee', 'url' => '/local-project-monitoring-committee', 'period' => 'quarter'],
            ['table' => 'tblroad_maintenance_status_documents', 'module_key' => 'road-maintenance-status', 'module_label' => 'Road Maintenance Status', 'url' => '/road-maintenance-status', 'period' => 'quarter'],
            ['table' => 'pre_implementation_document_files', 'module_key' => 'pre-implementation', 'module_label' => 'Pre-Implementation Documents', 'url' => '/pre-implementation-documents/projects', 'period' => null, 'project' => 'project_code', 'document' => 'document_type'],
            ['table' => 'lgsf_project_completion_report_files', 'module_key' => 'lgsf-pcr', 'module_label' => 'LGSF Project Completion Reports', 'url' => '/reports/one-time/project-completion-reports/falgu-gef-sbdp', 'period' => null, 'project' => 'project_code', 'document' => 'document_type'],
        ];

        foreach ($sources as $source) {
            if (!Schema::hasTable($source['table'])) {
                continue;
            }

            $query = DB::table($source['table'])
                ->where(function ($statusQuery) use ($includeProvincial): void {
                    $statusQuery->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_ro'");

                    if ($includeProvincial) {
                        $statusQuery->orWhereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending'");
                    }
                })
                ->whereNotNull('file_path')
                ->where('file_path', '<>', '')
                ->whereNull('approved_at_dilg_ro')
                ->orderByDesc('uploaded_at');

            foreach ($query->get() as $record) {
                $projectCode = trim((string) ($record->{$source['project'] ?? 'office'} ?? ''));
                $period = $source['period'] ? trim((string) ($record->{$source['period']} ?? '')) : '';
                $label = trim((string) ($record->{$source['document'] ?? 'doc_type'} ?? ''));
                $taskUrl = $source['url'];

                if ($source['table'] === 'pre_implementation_document_files' && $projectCode !== '') {
                    $taskUrl .= '/' . rawurlencode($projectCode);
                }

                if (in_array($source['table'], [
                    'tblmonitoring_evaluation_monthly_documents',
                    'tblpd_no_pbbm_2025_1572_1573_documents',
                ], true) && $projectCode !== '') {
                    $taskUrl .= '/' . rawurlencode($projectCode) . '/edit';
                }

                if (in_array($source['table'], [
                    'tblpmc_documents',
                    'tblroad_maintenance_status_documents',
                ], true)) {
                    $taskUrl .= '/' . rawurlencode((string) $record->id) . '/edit';
                }

                if ($source['table'] === 'lgsf_project_completion_report_files' && $projectCode !== '') {
                    $taskUrl .= '/' . rawurlencode($projectCode);
                }

                $tasks->push([
                    'id' => 'status-' . $source['table'] . '-' . $record->id,
                    'message' => ($label !== '' ? $label . ' - ' : '') . 'Awaiting DILG Regional Office validation.',
                    'url' => $taskUrl,
                    'document_type' => $label,
                    'quarter' => $period,
                    'sender_name' => 'Current approval status',
                    'created_at' => $record->uploaded_at ?? $record->created_at,
                    'project_code' => $projectCode,
                    'province' => trim((string) ($record->province ?? '')),
                    'city_municipality' => trim((string) ($record->office ?? '')),
                    'module_key' => $source['module_key'],
                    'module_label' => $source['module_label'],
                    'queue_key' => $includeProvincial && strtolower(trim((string) ($record->status ?? ''))) === 'pending'
                        ? 'pending_provincial'
                        : 'pending_regional',
                ]);
            }
        }

        if (Schema::hasTable('fund_utilization_approval_workflows')) {
            $workflows = DB::table('fund_utilization_approval_workflows')
                ->where('current_approval_level', '>=', $includeProvincial ? 1 : 2)
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) LIKE 'pending level %'")
                ->orderByDesc('updated_at')
                ->get();

            foreach ($workflows as $workflow) {
                $projectCode = trim((string) $workflow->project_code);
                $quarter = trim((string) $workflow->quarter);
                $documentType = trim((string) $workflow->document_type);

                $tasks->push([
                    'id' => 'status-fund-utilization-' . $workflow->id,
                    'message' => ($documentType !== '' ? $documentType . ' - ' : '') . 'Awaiting DILG Regional Office validation.',
                    'url' => '/fund-utilization/' . rawurlencode($projectCode) . '?quarter=' . rawurlencode($quarter) . '&document=' . rawurlencode($documentType),
                    'document_type' => $documentType,
                    'quarter' => $quarter,
                    'sender_name' => 'Current approval status',
                    'created_at' => $workflow->updated_at,
                    'project_code' => $projectCode,
                    'province' => '',
                    'city_municipality' => '',
                    'module_key' => 'fund-utilization',
                    'module_label' => 'Fund Utilization Report',
                    'queue_key' => $includeProvincial && (int) $workflow->current_approval_level < 2
                        ? 'pending_provincial'
                        : 'pending_regional',
                ]);
            }
        }

        return $tasks->unique(fn (array $task): string => implode('|', [
            $task['module_key'],
            $task['project_code'],
            $task['document_type'],
            $task['quarter'],
        ]))->values();
    }
}
