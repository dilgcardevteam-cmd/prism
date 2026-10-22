<?php

namespace App\Modules\TaskManagement\Services;

use App\Models\User;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RegionalApprovalPoolService
{
    /**
     * Build pending tasks for a specific user based on role and assigned jurisdiction.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pendingTasksForUser(User $user): Collection
    {
        $tasks = collect();

        // LGU users or users without approval level cannot validate submissions
        $approvalLevel = $user->approvalLevel();
        if ($approvalLevel === null) {
            return $tasks;
        }

        $sources = $this->documentSources();

        foreach ($sources as $source) {
            if (!Schema::hasTable($source['table'])) {
                continue;
            }

            $isDilgDeliverable = in_array($source['table'], [
                'tblmonitoring_evaluation_monthly_documents',
            ], true);

            // DILG deliverables are not accessible to C/MLGOO or LGU users
            if ($isDilgDeliverable && ($approvalLevel === 'cmlgoo' || $user->isLguUser() || $user->isMlgooUser() || $user->isLguScopedUser())) {
                continue;
            }

            $query = DB::table($source['table'])
                ->whereNotNull('file_path')
                ->where('file_path', '<>', '');

            if (!empty($source['doc_types'])) {
                $query->whereIn('doc_type', $source['doc_types']);
            }

            // Status scoping based on user approval level
            if ($approvalLevel === 'regional') {
                $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_ro'")
                    ->whereNull('approved_at_dilg_ro');
            } elseif ($approvalLevel === 'provincial') {
                $hasOfficeCol = \Illuminate\Support\Facades\Schema::hasColumn($source['table'], 'office');
                $hasApprovedAtMlgooCol = \Illuminate\Support\Facades\Schema::hasColumn($source['table'], 'approved_at_c_mlgoo');
                $query->where(function ($q) use ($hasOfficeCol, $hasApprovedAtMlgooCol) {
                    $q->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_po'");
                    if ($hasOfficeCol) {
                        $q->orWhere(function ($legacy) {
                            $legacy->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending'")
                                ->where(function ($plgu) {
                                    $plgu->whereRaw("LOWER(TRIM(COALESCE(office, ''))) LIKE 'plgu%'")
                                        ->orWhereRaw("LOWER(TRIM(COALESCE(office, ''))) LIKE 'provincial government%'");
                                });
                        });
                    }
                    if ($hasApprovedAtMlgooCol) {
                        $q->orWhere(function ($approvedMlgoo) {
                            $approvedMlgoo->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending'")
                                ->whereNotNull('approved_at_c_mlgoo');
                        });
                    }
                })->whereNull('approved_at_dilg_po');
            } elseif ($approvalLevel === 'cmlgoo') {
                if ($isDilgDeliverable) {
                    continue;
                }
                $hasOfficeCol = \Illuminate\Support\Facades\Schema::hasColumn($source['table'], 'office');
                $query->where(function ($q) use ($hasOfficeCol) {
                    $q->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_mlgoo'");
                    if ($hasOfficeCol) {
                        $q->orWhere(function ($legacy) {
                            $legacy->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending'")
                                ->whereRaw("LOWER(TRIM(COALESCE(office, ''))) NOT LIKE 'plgu%'")
                                ->whereNull('approved_at_c_mlgoo');
                        });
                    }
                });
            }

            $records = $query->orderByDesc('uploaded_at')->get();

            foreach ($records as $record) {
                $officeOrProject = trim((string) ($record->{$source['project'] ?? 'office'} ?? ''));
                $period = $source['period'] ? trim((string) ($record->{$source['period']} ?? '')) : '';
                $year = trim((string) ($record->year ?? $record->document_year ?? ''));
                $label = trim((string) ($record->{$source['document'] ?? 'doc_type' ?? 'document_name'} ?? ''));
                $displayLabel = $this->documentDisplayLabel($label);

                $projectLocation = in_array($source['table'], [
                    'pre_implementation_document_files',
                    'lgsf_project_completion_report_files',
                    'sglgif_project_completion_report_files',
                    'quarterly_rpmes_form2_uploads',
                    'annual_rpmes_form4_uploads',
                    'quarterly_rpmes_form5_uploads',
                    'quarterly_rpmes_form6_uploads',
                ], true) ? $this->resolveProjectLocation($officeOrProject) : null;

                $recordProvince = trim((string) ($projectLocation['province'] ?? $record->province ?? ''));
                $recordOffice = trim((string) ($projectLocation['city_municipality'] ?? $record->office ?? ''));

                if ($recordProvince === '' && $recordOffice !== '') {
                    $recordProvince = $this->resolveProvinceFromOffice($recordOffice);
                }

                // Enforce jurisdiction scope based on approval level office
                if ($approvalLevel === 'provincial') {
                    if ($recordProvince === '' || !$this->matchesProvince($recordProvince, $user->province)) {
                        continue;
                    }
                } elseif ($approvalLevel === 'cmlgoo') {
                    if (!$user->matchesAssignedOffice($recordOffice)) {
                        continue;
                    }
                    if ($recordProvince !== '' && $user->normalizedProvince() !== '') {
                        if (!$this->matchesProvince($recordProvince, $user->province)) {
                            continue;
                        }
                    }
                }

                $uploader = $this->resolveUploader($record->uploaded_by ?? null);
                $taskUrl = $this->buildTaskUrl($source, $record, $officeOrProject, $year, $period, $label);

                $statusStr = Str::lower(trim((string) ($record->status ?? '')));
                $hasApprovedAtPo = !empty($record->approved_at_dilg_po);
                $hasApprovedAtRo = !empty($record->approved_at_dilg_ro);
                $hasApprovedAtMlgoo = !empty($record->approved_at_c_mlgoo);

                $queueKey = match ($statusStr) {
                    'pending_mlgoo' => 'pending_c_mlgoo',
                    'pending_po' => 'pending_provincial',
                    'pending_ro' => 'pending_regional',
                    'pending' => ($this->isPlguOffice($recordOffice, $recordProvince) || $hasApprovedAtMlgoo ? 'pending_provincial' : 'pending_c_mlgoo'),
                    default => 'pending_regional',
                };

                // STRICT LEVEL CHECK:
                // Provincial Pool: only show tasks where approval/validation is FOR PROVINCIAL LEVEL
                if ($approvalLevel === 'provincial') {
                    if ($queueKey !== 'pending_provincial' || $hasApprovedAtPo) {
                        continue;
                    }
                } elseif ($approvalLevel === 'cmlgoo') {
                    if ($queueKey !== 'pending_c_mlgoo' || $hasApprovedAtMlgoo) {
                        continue;
                    }
                } elseif ($approvalLevel === 'regional') {
                    if ($queueKey !== 'pending_regional' || $hasApprovedAtRo) {
                        continue;
                    }
                }

                $message = match ($queueKey) {
                    'pending_c_mlgoo' => 'Awaiting C/MLGOO Validation',
                    'pending_provincial' => 'Awaiting DILG Provincial Office Validation',
                    default => 'Awaiting DILG Regional Office Validation',
                };

                $tasks->push([
                    'id' => 'status-' . $source['table'] . '-' . $record->id,
                    'task_title' => $source['task_title'] ?? ($displayLabel !== '' ? $displayLabel : $source['module_label']),
                    'message' => $message,
                    'url' => $taskUrl,
                    'document_type' => $label,
                    'document_label' => $displayLabel,
                    'quarter' => $period,
                    'period' => $period,
                    'year' => $year,
                    'sender_name' => $uploader['name'],
                    'uploader_province' => $uploader['province'],
                    'created_at' => $record->uploaded_at ?? $record->created_at,
                    'project_code' => $officeOrProject,
                    'province' => $recordProvince,
                    'city_municipality' => $recordOffice,
                    'module_key' => $source['module_key'],
                    'module_label' => $source['module_label'],
                    'queue_key' => $queueKey,
                ]);
            }
        }

        // Fund Utilization Workflows
        if (Schema::hasTable('fund_utilization_approval_workflows')) {
            $workflowQuery = DB::table('fund_utilization_approval_workflows')
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) LIKE 'pending level %'");

            if ($approvalLevel === 'regional') {
                $workflowQuery->where('current_approval_level', '>=', 2);
            } elseif ($approvalLevel === 'provincial') {
                $workflowQuery->where('current_approval_level', 1);
            } else {
                $workflowQuery = null;
            }

            if ($workflowQuery) {
                foreach ($workflowQuery->orderByDesc('updated_at')->get() as $workflow) {
                    $projectCode = trim((string) $workflow->project_code);
                    $quarter = trim((string) $workflow->quarter);
                    $documentType = trim((string) $workflow->document_type);
                    $uploader = $this->resolveUploader($workflow->uploader_id ?? null);
                    $displayDocumentType = $this->documentDisplayLabel($documentType);
                    $location = $this->resolveProjectLocation($projectCode);

                    $workflowProvince = trim((string) ($location['province'] ?? ''));
                    $workflowOffice = trim((string) ($location['city_municipality'] ?? ''));
                    if ($workflowProvince === '' && $workflowOffice !== '') {
                        $workflowProvince = $this->resolveProvinceFromOffice($workflowOffice);
                    }

                    if ($approvalLevel === 'provincial') {
                        if ($workflowProvince === '' || !$this->matchesProvince($workflowProvince, $user->province)) {
                            continue;
                        }
                    }

                    $workflowQueueKey = ((int) $workflow->current_approval_level >= 2) ? 'pending_regional' : 'pending_provincial';
                    if ($approvalLevel === 'provincial' && $workflowQueueKey !== 'pending_provincial') {
                        continue;
                    }
                    if ($approvalLevel === 'regional' && $workflowQueueKey !== 'pending_regional') {
                        continue;
                    }

                $tasks->push([
                    'id' => 'status-fund-utilization-' . $workflow->id,
                    'task_title' => $displayDocumentType !== '' ? $displayDocumentType : 'Fund Utilization Report',
                    'message' => ($documentType !== '' ? $documentType . ' - ' : '') . 'Awaiting validation.',
                    'url' => route('fund-utilization.show', [
                        'projectCode' => $projectCode,
                        'quarter' => $quarter,
                        'document' => $documentType,
                    ]),
                    'document_type' => $documentType,
                    'quarter' => $quarter,
                    'period' => $quarter,
                    'year' => '',
                    'sender_name' => $uploader['name'],
                    'uploader_province' => $uploader['province'],
                    'created_at' => $workflow->updated_at,
                    'project_code' => $projectCode,
                    'province' => $location['province'],
                    'city_municipality' => $location['city_municipality'],
                    'module_key' => 'fund-utilization',
                    'module_label' => 'Fund Utilization Report',
                    'queue_key' => ((int) $workflow->current_approval_level >= 2) ? 'pending_regional' : 'pending_provincial',
                ]);
            }
        }
    }

        return $tasks->unique(fn (array $task): string => implode('|', [
            $task['module_key'],
            $task['project_code'],
            $task['document_type'],
            $task['quarter'],
        ]))->values();
    }

    /**
     * Build returned tasks for an uploader based on their jurisdiction.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function returnedTasksForUser(User $user): Collection
    {
        $tasks = collect();

        // Regional and Superadmin users do not have reports returned to them to resubmit
        if ($user->isRegionalUser() || $user->isSuperAdmin()) {
            return $tasks;
        }

        $sources = $this->documentSources();

        foreach ($sources as $source) {
            if (!Schema::hasTable($source['table'])) {
                continue;
            }

            $isDilgDeliverable = in_array($source['table'], [
                'tblmonitoring_evaluation_monthly_documents',
            ], true);

            if ($isDilgDeliverable && ($user->isLguUser() || $user->isMlgooUser() || $user->isLguScopedUser())) {
                continue;
            }

            $query = DB::table($source['table'])
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'returned'")
                ->whereNotNull('file_path')
                ->where('file_path', '<>', '');

            if (!empty($source['doc_types'])) {
                $query->whereIn('doc_type', $source['doc_types']);
            }

            $records = $query->orderByDesc('uploaded_at')->get();

            foreach ($records as $record) {
                $officeOrProject = trim((string) ($record->{$source['project'] ?? 'office'} ?? ''));
                $period = $source['period'] ? trim((string) ($record->{$source['period']} ?? '')) : '';
                $year = trim((string) ($record->year ?? $record->document_year ?? ''));
                $label = trim((string) ($record->{$source['document'] ?? 'doc_type' ?? 'document_name'} ?? ''));
                $displayLabel = $this->documentDisplayLabel($label);

                $projectLocation = in_array($source['table'], [
                    'pre_implementation_document_files',
                    'lgsf_project_completion_report_files',
                    'sglgif_project_completion_report_files',
                    'quarterly_rpmes_form2_uploads',
                    'annual_rpmes_form4_uploads',
                    'quarterly_rpmes_form5_uploads',
                    'quarterly_rpmes_form6_uploads',
                ], true) ? $this->resolveProjectLocation($officeOrProject) : null;

                $recordProvince = trim((string) ($projectLocation['province'] ?? $record->province ?? ''));
                $recordOffice = trim((string) ($projectLocation['city_municipality'] ?? $record->office ?? ''));

                if ($recordProvince === '' && $recordOffice !== '') {
                    $recordProvince = $this->resolveProvinceFromOffice($recordOffice);
                }

                $isUploader = ((int) ($record->uploaded_by ?? 0) === (int) ($user->idno ?? $user->getKey()));
                $isProvincialDeliverable = $isDilgDeliverable || $this->isPlguOffice($recordOffice, $recordProvince);

                // Scope to user's office and level
                if ($user->isLguUser() || $user->isMlgooUser()) {
                    // LGU and C/MLGOO only act on municipal deliverables matching their assigned office
                    if ($isProvincialDeliverable) {
                        continue;
                    }
                    if (!$user->matchesAssignedOffice($recordOffice)) {
                        continue;
                    }
                    if ($recordProvince !== '' && $user->normalizedProvince() !== '') {
                        if (!$this->matchesProvince($recordProvince, $user->province)) {
                            continue;
                        }
                    }
                } elseif ($user->isProvincialUser()) {
                    // Provincial users only act on provincial/PLGU deliverables or submissions they uploaded for their province
                    if ($recordProvince === '' || !$this->matchesProvince($recordProvince, $user->province)) {
                        continue;
                    }
                    if (!$isProvincialDeliverable && !$isUploader) {
                        continue;
                    }
                } else {
                    continue;
                }

                $returner = $this->resolveReturner($source, $record);
                $uploader = $this->resolveUploader($record->uploaded_by ?? null);
                $taskUrl = $this->buildTaskUrl($source, $record, $officeOrProject, $year, $period, $label);

                $tasks->push([
                    'id' => 'returned-' . $source['table'] . '-' . $record->id,
                    'task_title' => $source['task_title'] ?? ($displayLabel !== '' ? $displayLabel : $source['module_label']),
                    'message' => 'Returned: ' . ($record->approval_remarks ?? 'Returned for revision'),
                    'url' => $taskUrl,
                    'document_type' => $label,
                    'document_label' => $displayLabel,
                    'quarter' => $period,
                    'period' => $period,
                    'year' => $year,
                    'sender_name' => $returner['name'],
                    'returned_by_name' => $returner['name'],
                    'returned_by_role' => $returner['role'],
                    'returned_by_province' => $returner['province'],
                    'returned_at' => $returner['returned_at'] ?: ($record->updated_at ?? $record->uploaded_at ?? $record->created_at),
                    'uploader_name' => $uploader['name'],
                    'uploader_province' => $uploader['province'],
                    'created_at' => $returner['returned_at'] ?: ($record->uploaded_at ?? $record->created_at),
                    'project_code' => $officeOrProject,
                    'province' => $recordProvince,
                    'city_municipality' => $recordOffice,
                    'module_key' => $source['module_key'],
                    'module_label' => $source['module_label'],
                    'queue_key' => 'returned',
                ]);
            }
        }

        // Returned Fund Utilization Workflows
        if (Schema::hasTable('fund_utilization_approval_workflows')) {
            $workflowQuery = DB::table('fund_utilization_approval_workflows')
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) LIKE 'returned by %'");

            foreach ($workflowQuery->orderByDesc('updated_at')->get() as $workflow) {
                $projectCode = trim((string) $workflow->project_code);
                $quarter = trim((string) $workflow->quarter);
                $documentType = trim((string) $workflow->document_type);
                $uploader = $this->resolveUploader($workflow->uploader_id ?? null);
                $displayDocumentType = $this->documentDisplayLabel($documentType);
                $location = $this->resolveProjectLocation($projectCode);

                $workflowProvince = trim((string) ($location['province'] ?? ''));
                $workflowOffice = trim((string) ($location['city_municipality'] ?? ''));
                if ($workflowProvince === '' && $workflowOffice !== '') {
                    $workflowProvince = $this->resolveProvinceFromOffice($workflowOffice);
                }

                $isUploader = ((int) ($workflow->uploader_id ?? 0) === (int) ($user->idno ?? $user->getKey()));
                $projectOwner = trim((string) ($location['project_owner'] ?? ''));
                $isProvincialDeliverable = $this->isPlguOffice($workflowOffice, $workflowProvince)
                    || strtolower($projectOwner) === 'province';

                // Scope to user's office and level
                if ($user->isLguUser() || $user->isMlgooUser()) {
                    // LGU and C/MLGOO users only receive returned fund utilization reports for their assigned municipality
                    if ($isProvincialDeliverable) {
                        continue;
                    }
                    if (!$user->matchesAssignedOffice($workflowOffice)) {
                        continue;
                    }
                    if ($workflowProvince !== '' && $user->normalizedProvince() !== '') {
                        if (!$this->matchesProvince($workflowProvince, $user->province)) {
                            continue;
                        }
                    }
                } elseif ($user->isProvincialUser()) {
                    // Provincial users only receive returned provincial/PLGU reports or submissions they uploaded for their province
                    if ($workflowProvince === '' || !$this->matchesProvince($workflowProvince, $user->province)) {
                        continue;
                    }
                    if (!$isProvincialDeliverable && !$isUploader) {
                        continue;
                    }
                } else {
                    continue;
                }

                $returner = $this->resolveWorkflowReturner($workflow);

                $tasks->push([
                    'id' => 'returned-fund-utilization-' . $workflow->id,
                    'task_title' => $displayDocumentType !== '' ? $displayDocumentType : 'Fund Utilization Report',
                    'message' => 'Returned: ' . $workflow->status,
                    'url' => route('fund-utilization.show', [
                        'projectCode' => $projectCode,
                        'quarter' => $quarter,
                        'document' => $documentType,
                    ]),
                    'document_type' => $documentType,
                    'quarter' => $quarter,
                    'period' => $quarter,
                    'year' => '',
                    'sender_name' => $returner['name'],
                    'returned_by_name' => $returner['name'],
                    'returned_by_role' => $returner['role'],
                    'returned_by_province' => $returner['province'],
                    'returned_at' => $returner['returned_at'] ?: $workflow->updated_at,
                    'uploader_name' => $uploader['name'],
                    'uploader_province' => $uploader['province'],
                    'created_at' => $returner['returned_at'] ?: $workflow->updated_at,
                    'project_code' => $projectCode,
                    'province' => $location['province'],
                    'city_municipality' => $location['city_municipality'],
                    'module_key' => 'fund-utilization',
                    'module_label' => 'Fund Utilization Report',
                    'queue_key' => 'returned',
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

    /**
     * Backwards-compatible pending tasks method.
     */
    public function pendingTasks(bool $includeProvincial = false): Collection
    {
        $user = auth()->user();
        if ($user) {
            return $this->pendingTasksForUser($user);
        }

        return collect();
    }

    /**
     * Document sources configuration for approval pools.
     */
    private function documentSources(): array
    {
        return [
            ['table' => 'tblmonitoring_evaluation_monthly_documents', 'doc_types' => ['monitoring_evaluation_monthly', 'monitoring_evaluation_monthly_lfp'], 'module_key' => 'monitoring-evaluation-lfp', 'module_label' => 'Monitoring Evaluation Monthly', 'task_title' => 'Monthly Monitoring and Evaluation Report - LFP', 'url' => '/reports/dilg-deliverables/monitoring-and-evaluation-reports/lfp', 'period' => 'month'],
            ['table' => 'tblmonitoring_evaluation_monthly_documents', 'doc_types' => ['monitoring_evaluation_monthly_rlip_lime'], 'module_key' => 'monitoring-evaluation-lfp', 'module_label' => 'Monitoring Evaluation Monthly', 'task_title' => 'Monthly Monitoring and Evaluation Report - RLIP/LIME', 'url' => '/reports/dilg-deliverables/monitoring-and-evaluation-reports/rlip-lime', 'period' => 'month'],
            ['table' => 'tblpd_no_pbbm_2025_1572_1573_documents', 'module_key' => 'pd-no-pbbm-2025-1572-1573', 'module_label' => 'PD No. PBBM-2025-1572-1573', 'url' => '/reports/monthly/pd-no-pbbm-2025-1572-1573', 'period' => 'month'],
            ['table' => 'tblpmc_documents', 'module_key' => 'local-project-monitoring-committee', 'module_label' => 'Local Project Monitoring Committee', 'url' => '/local-project-monitoring-committee', 'period' => 'quarter'],
            ['table' => 'tblroad_maintenance_status_documents', 'module_key' => 'road-maintenance-status', 'module_label' => 'Road Maintenance Status', 'url' => '/road-maintenance-status', 'period' => 'quarter'],
            ['table' => 'tblrbis_annual_certification_documents', 'module_key' => 'rbis-annual-certification', 'module_label' => 'RBIS Annual Certification', 'url' => '/reports/rbis-annual-certification', 'period' => null, 'year' => 'document_year', 'document' => 'document_name'],
            ['table' => 'tblannual_maintenance_work_program_documents', 'module_key' => 'annual-maintenance-work-program', 'module_label' => 'Annual Maintenance Work Program', 'url' => '/reports/annual/amwp', 'period' => null, 'year' => 'document_year', 'document' => 'document_name'],
            ['table' => 'tblswa_annex_f_documents', 'module_key' => 'swa-annex-f', 'module_label' => 'SWA Annex F', 'url' => '/reports/monthly/swa-annex-f', 'period' => 'month'],
            ['table' => 'pre_implementation_document_files', 'module_key' => 'pre-implementation', 'module_label' => 'Pre-Implementation Documents', 'url' => '/pre-implementation-documents/projects', 'period' => null, 'project' => 'project_code', 'document' => 'document_type'],
            ['table' => 'lgsf_project_completion_report_files', 'module_key' => 'lgsf-pcr', 'module_label' => 'LGSF Project Completion Reports', 'url' => '/reports/one-time/project-completion-reports/falgu-gef-sbdp', 'period' => null, 'project' => 'project_code', 'document' => 'document_type'],
            ['table' => 'sglgif_project_completion_report_files', 'module_key' => 'sglgif-pcr', 'module_label' => 'SGLGIF Project Completion Reports', 'url' => '/reports/one-time/project-completion-reports/sglgif', 'period' => null, 'project' => 'project_code', 'document' => 'document_type'],
            ['table' => 'quarterly_rpmes_form2_uploads', 'module_key' => 'rpmes-form-2', 'module_label' => 'RPMES Form 2', 'url' => '/reports/quarterly/rpmes/form-2', 'period' => 'quarter', 'project' => 'project_code'],
            ['table' => 'annual_rpmes_form4_uploads', 'module_key' => 'rpmes-form-4', 'module_label' => 'RPMES Form 4', 'url' => '/reports/annual/rpmes/form-4', 'period' => 'quarter', 'project' => 'project_code'],
            ['table' => 'quarterly_rpmes_form5_uploads', 'module_key' => 'rpmes-form-5', 'module_label' => 'RPMES Form 5', 'url' => '/reports/quarterly/rpmes/form-5', 'period' => 'quarter', 'project' => 'project_code'],
            ['table' => 'quarterly_rpmes_form6_uploads', 'module_key' => 'rpmes-form-6', 'module_label' => 'RPMES Form 6', 'url' => '/reports/quarterly/rpmes/form-6', 'period' => 'quarter', 'project' => 'project_code'],
        ];
    }

    private function isPlguOffice(?string $officeName, ?string $provinceName = null): bool
    {
        $normalizedOffice = ProjectLocationFilterHelper::normalizeComparableLocationLabel($officeName);
        if ($normalizedOffice === '') {
            return false;
        }

        if ($normalizedOffice === 'province'
            || str_starts_with($normalizedOffice, 'province')
            || str_starts_with($normalizedOffice, 'plgu')
            || str_starts_with($normalizedOffice, 'provincial government')
            || str_contains($normalizedOffice, 'provincial office')) {
            return true;
        }

        if ($provinceName !== null) {
            $normalizedProvince = ProjectLocationFilterHelper::normalizeComparableLocationLabel($provinceName);
            if ($normalizedProvince !== '' && $normalizedOffice === $normalizedProvince) {
                return true;
            }
        }

        return false;
    }

    private function buildTaskUrl(array $source, object $record, string $officeOrProject, string $year, string $period, string $label): string
    {
        $taskUrl = $source['url'];

        if (in_array($source['table'], [
            'pre_implementation_document_files',
            'lgsf_project_completion_report_files',
            'sglgif_project_completion_report_files',
        ], true) && $officeOrProject !== '') {
            return $taskUrl . '/' . rawurlencode($officeOrProject);
        }

        if (in_array($source['table'], [
            'quarterly_rpmes_form2_uploads',
            'annual_rpmes_form4_uploads',
            'quarterly_rpmes_form5_uploads',
            'quarterly_rpmes_form6_uploads',
        ], true) && $officeOrProject !== '') {
            $url = $taskUrl . '/' . rawurlencode($officeOrProject);
            if ($period !== '') {
                $url .= '?quarter=' . rawurlencode($period);
            }
            return $url;
        }

        if (in_array($source['table'], [
            'tblmonitoring_evaluation_monthly_documents',
            'tblpd_no_pbbm_2025_1572_1573_documents',
            'tblswa_annex_f_documents',
            'tblrbis_annual_certification_documents',
            'tblannual_maintenance_work_program_documents',
        ], true) && $officeOrProject !== '') {
            $taskUrl .= '/' . rawurlencode($officeOrProject) . '/edit';

            if ($year !== '') {
                $taskUrl .= '?year=' . rawurlencode($year);
            }

            if ($source['table'] === 'tblmonitoring_evaluation_monthly_documents' && $period !== '') {
                $taskUrl .= '&month=' . rawurlencode($period) . '#me-monthly-card-' . rawurlencode($period);
            }

            if ($source['table'] === 'tblpd_no_pbbm_2025_1572_1573_documents' && $period !== '') {
                $taskUrl .= '&month=' . rawurlencode($period) . '#road-maintenance-' . rawurlencode($period);
            }

            if ($source['table'] === 'tblswa_annex_f_documents' && $period !== '') {
                $taskUrl .= '&month=' . rawurlencode($period);
            }

            return $taskUrl;
        }

        if (in_array($source['table'], [
            'tblpmc_documents',
            'tblroad_maintenance_status_documents',
        ], true) && $officeOrProject !== '') {
            $taskUrl .= '/' . rawurlencode($officeOrProject) . '/edit';

            if ($year !== '') {
                $taskUrl .= '?year=' . rawurlencode($year);
            }

            if ($source['period'] === 'quarter' && $period !== '') {
                $taskUrl .= ($year === '' ? '?' : '&') . 'quarter=' . rawurlencode($period) . '#road-maintenance-' . rawurlencode($period);
            }

            if ($source['table'] === 'tblpmc_documents') {
                if ($period !== '') {
                    $taskUrl .= '#lpmc-quarter-' . rawurlencode($period);
                } elseif ($label !== '') {
                    $taskUrl .= ($year === '' ? '?' : '&')
                        . 'document=' . rawurlencode($label)
                        . '#lpmc-document-' . rawurlencode($label) . '-' . rawurlencode($year);
                }
            }
        }

        return $taskUrl;
    }

    /**
     * @return array{name: string, province: string}
     */
    private function resolveUploader($userId): array
    {
        $user = User::query()
            ->select(['idno', 'fname', 'lname', 'province'])
            ->where('idno', $userId)
            ->first();

        return [
            'name' => $user?->fullName() ?: 'Unknown uploader',
            'province' => trim((string) ($user?->province ?? '')),
        ];
    }

    /**
     * Resolve the validator who returned the reportorial document.
     *
     * @param array<string, mixed> $source
     * @return array{name: string, role: string, province: string, returned_at: mixed}
     */
    private function resolveReturner(array $source, object $record): array
    {
        $table = $source['table'] ?? '';
        $recordId = (int) ($record->id ?? 0);

        if ($table !== '' && $recordId > 0 && Schema::hasTable('activity_logs')) {
            try {
                $log = DB::table('activity_logs')
                    ->where('properties->report_table', $table)
                    ->where('properties->report_id', $recordId)
                    ->where(function ($q) {
                        $q->where('action', 'REPORT_RETURNED')
                            ->orWhereRaw("LOWER(TRIM(COALESCE(action, ''))) LIKE '%return%'");
                    })
                    ->orderByDesc('id')
                    ->first();

                if ($log && !empty($log->user_id)) {
                    $user = $this->resolveUser($log->user_id);
                    if ($user['name'] !== 'Unknown') {
                        $user['returned_at'] = $log->created_at;
                        return $user;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback gracefully if JSON querying fails
            }
        }

        $returnerId = $record->approved_by_dilg_ro
            ?? $record->approved_by_dilg_po
            ?? $record->approved_by_c_mlgoo
            ?? $record->returned_by
            ?? null;

        if ($returnerId) {
            $user = $this->resolveUser($returnerId);
            if ($user['name'] !== 'Unknown') {
                $user['returned_at'] = $record->updated_at ?? null;
                return $user;
            }
        }

        return [
            'name' => 'Validator',
            'role' => 'Reviewer',
            'province' => '',
            'returned_at' => null,
        ];
    }

    /**
     * Resolve the validator who returned the Fund Utilization workflow submission.
     *
     * @return array{name: string, role: string, province: string, returned_at: mixed}
     */
    private function resolveWorkflowReturner(object $workflow): array
    {
        $workflowId = (int) ($workflow->id ?? 0);

        if ($workflowId > 0 && Schema::hasTable('approval_logs')) {
            try {
                $log = DB::table('approval_logs')
                    ->where('submission_id', $workflowId)
                    ->whereRaw("LOWER(TRIM(COALESCE(action, ''))) LIKE '%return%'")
                    ->orderByDesc('id')
                    ->first();

                if ($log && !empty($log->approver_id)) {
                    $user = $this->resolveUser($log->approver_id);
                    if ($user['name'] !== 'Unknown') {
                        $user['returned_at'] = $log->created_at;
                        return $user;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback gracefully
            }
        }

        if ($workflowId > 0 && Schema::hasTable('activity_logs')) {
            try {
                $log = DB::table('activity_logs')
                    ->where(function ($q) use ($workflow, $workflowId) {
                        $q->where('properties->workflow_id', $workflowId)
                            ->orWhere(function ($sub) use ($workflow) {
                                $sub->where('properties->project_code', $workflow->project_code)
                                    ->where('properties->quarter', $workflow->quarter);
                            });
                    })
                    ->whereRaw("LOWER(TRIM(COALESCE(action, ''))) LIKE '%return%'")
                    ->orderByDesc('id')
                    ->first();

                if ($log && !empty($log->user_id)) {
                    $user = $this->resolveUser($log->user_id);
                    if ($user['name'] !== 'Unknown') {
                        $user['returned_at'] = $log->created_at;
                        return $user;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback gracefully
            }
        }

        $status = strtolower((string) ($workflow->status ?? ''));
        $role = '';
        if (str_contains($status, 'regional')) {
            $role = 'Regional Validator';
        } elseif (str_contains($status, 'provincial')) {
            $role = 'Provincial Validator';
        }

        return [
            'name' => $role ?: 'Validator',
            'role' => $role ?: 'Reviewer',
            'province' => '',
            'returned_at' => null,
        ];
    }

    /**
     * @return array{name: string, role: string, province: string, returned_at: mixed}
     */
    private function resolveUser($userId): array
    {
        if (empty($userId)) {
            return ['name' => 'Unknown', 'role' => '', 'province' => '', 'returned_at' => null];
        }

        $user = User::query()
            ->select(['idno', 'fname', 'lname', 'role', 'province'])
            ->where('idno', $userId)
            ->first();

        if (!$user) {
            return ['name' => 'Unknown', 'role' => '', 'province' => '', 'returned_at' => null];
        }

        $roleLabel = match (strtolower((string) $user->role)) {
            User::ROLE_REGIONAL => 'Regional Validator',
            User::ROLE_PROVINCIAL => 'Provincial Validator',
            User::ROLE_MLGOO => 'C/MLGOO Validator',
            User::ROLE_LGU => 'LGU User',
            User::ROLE_SUPERADMIN => 'Super Administrator',
            default => ucwords(str_replace(['_', '-'], ' ', (string) $user->role)),
        };

        return [
            'name' => $user->fullName() ?: 'Unknown',
            'role' => $roleLabel,
            'province' => trim((string) ($user->province ?? '')),
            'returned_at' => null,
        ];
    }

    private function documentDisplayLabel(string $documentType): string
    {
        $labels = [
            'mep' => 'Monitoring and Evaluation Plan',
            'eo' => 'Executive Order',
            'awfp' => 'Annual Work and Financial Plan',
            'signed_lgu_letter_path' => 'Signed LGU Letter',
            'variation_orders_path' => 'Variation Orders',
            'noa_path' => 'Notice of Award',
            'itb_posting_philgeps_path' => 'Invitation to Bid Posting on PhilGEPS',
            'proof_transfer_trust_fund_path' => 'Proof of Transfer of Trust Fund',
        ];
        $normalizedType = strtolower(trim($documentType));

        if (isset($labels[$normalizedType])) {
            return $labels[$normalizedType];
        }

        $displayType = preg_replace('/_path$/i', '', $normalizedType) ?? $normalizedType;

        return $displayType !== ''
            ? ucwords(str_replace(['_', '-'], ' ', $displayType))
            : 'Document';
    }

    /**
     * @return array{province: string, city_municipality: string}
     */
    private function resolveProjectLocation(string $projectCode): array
    {
        if ($projectCode === '' || !Schema::hasTable('subay_project_profiles')) {
            return ['province' => '', 'city_municipality' => ''];
        }

        $profile = DB::table('subay_project_profiles')
            ->where('project_code', $projectCode)
            ->first(['province', 'city_municipality', 'project_owner']);

        if (!$profile) {
            return ['province' => '', 'city_municipality' => ''];
        }

        $province = trim((string) ($profile->province ?? ''));
        $cityMunicipality = trim((string) ($profile->city_municipality ?? ''));
        $projectOwner = trim((string) ($profile->project_owner ?? ''));

        if ($cityMunicipality === '' && ($province !== '' || strtolower($projectOwner) === 'province')) {
            $cityMunicipality = 'Province';
        }

        return [
            'province' => $province,
            'city_municipality' => $cityMunicipality,
        ];
    }

    /**
     * Resolve province name from an office or municipality label.
     */
    public function resolveProvinceFromOffice(?string $office): string
    {
        $office = trim((string) $office);
        if ($office === '') {
            return '';
        }

        if (preg_match('/^(?:plgu|provincial\s+government(?:\s+of)?)\s+(.+)$/i', $office, $matches)) {
            return trim($matches[1]);
        }

        $normalizedOffice = ProjectLocationFilterHelper::normalizeComparableLocationLabel($office);

        $carProvinces = [
            'Abra' => [
                'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas', 'Dolores',
                'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Luba', 'Malibcong',
                'Manabo', 'Peñarrubia', 'Pidigan', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan',
                'San Quintin', 'Tayum', 'Tineg', 'Tubo', 'Villaviciosa',
            ],
            'Apayao' => [
                'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela',
            ],
            'Benguet' => [
                'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan',
                'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay', 'Baguio City', 'City of Baguio',
            ],
            'Ifugao' => [
                'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan',
                'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc',
            ],
            'Kalinga' => [
                'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk', 'Tabuk City', 'Tanudan',
            ],
            'Mountain Province' => [
                'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis',
                'Sabangan', 'Sadanga', 'Sagada', 'Tadian',
            ],
        ];

        foreach ($carProvinces as $province => $municipalities) {
            if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($province) === $normalizedOffice) {
                return $province;
            }

            foreach ($municipalities as $municipality) {
                if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($municipality) === $normalizedOffice) {
                    return $province;
                }
            }
        }

        $hierarchy = ProjectLocationFilterHelper::buildConfiguredLocationHierarchy();
        foreach ($hierarchy as $province => $cities) {
            if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($province) === $normalizedOffice) {
                return $province;
            }
            foreach ($cities as $city => $barangays) {
                if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($city) === $normalizedOffice) {
                    return $province;
                }
            }
        }

        return '';
    }

    /**
     * Check if two province names match, with handling for comparable labels and Baguio/Benguet.
     */
    public function matchesProvince(?string $province1, ?string $province2): bool
    {
        $p1 = ProjectLocationFilterHelper::normalizeComparableLocationLabel($province1);
        $p2 = ProjectLocationFilterHelper::normalizeComparableLocationLabel($province2);

        if ($p1 === '' || $p2 === '') {
            return false;
        }

        if ($p1 === $p2) {
            return true;
        }

        if (($p1 === 'baguio' && $p2 === 'benguet') || ($p1 === 'benguet' && $p2 === 'baguio')) {
            return true;
        }

        return false;
    }
}
