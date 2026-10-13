<?php

namespace App\Services;

use App\Models\User;
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

        // LGU users cannot validate submissions
        if ($user->isLguUser()) {
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
            if ($isDilgDeliverable && ($user->isLguUser() || $user->isMlgooUser() || $user->isLguScopedUser())) {
                continue;
            }

            $query = DB::table($source['table'])
                ->whereNotNull('file_path')
                ->where('file_path', '<>', '');

            if (!empty($source['doc_types'])) {
                $query->whereIn('doc_type', $source['doc_types']);
            }

            // Status scoping based on user role
            if ($user->isSuperAdmin()) {
                $query->where(function ($q) {
                    $q->whereIn(DB::raw("LOWER(TRIM(COALESCE(status, '')))"), [
                        'pending_mlgoo',
                        'pending',
                        'pending_po',
                        'pending_ro',
                    ]);
                })->whereNull('approved_at_dilg_ro');
            } elseif ($user->isRegionalUser()) {
                $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_ro'")
                    ->whereNull('approved_at_dilg_ro');
            } elseif ($user->isProvincialUser()) {
                if ($isDilgDeliverable) {
                    $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_ro'")
                        ->whereNull('approved_at_dilg_ro');
                } else {
                    $hasOfficeCol = \Illuminate\Support\Facades\Schema::hasColumn($source['table'], 'office');
                    $query->where(function ($q) use ($hasOfficeCol) {
                        $q->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending_po'");
                        if ($hasOfficeCol) {
                            $q->orWhere(function ($legacy) {
                                $legacy->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending'")
                                    ->where(function ($plgu) {
                                        $plgu->whereRaw("LOWER(TRIM(COALESCE(office, ''))) LIKE 'plgu%'")
                                            ->orWhereRaw("LOWER(TRIM(COALESCE(office, ''))) LIKE 'provincial government%'");
                                    });
                            });
                        } else {
                            $q->orWhereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'pending'");
                        }
                    })->whereNull('approved_at_dilg_po');
                }
            } elseif ($user->isMlgooUser()) {
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

                // Enforce jurisdiction scope
                if ($user->isProvincialUser()) {
                    if ($recordProvince !== '' && Str::lower($recordProvince) !== $user->normalizedProvince()) {
                        continue;
                    }
                } elseif ($user->isMlgooUser()) {
                    if (!$user->matchesAssignedOffice($recordOffice)) {
                        continue;
                    }
                }

                $uploader = $this->resolveUploader($record->uploaded_by ?? null);
                $taskUrl = $this->buildTaskUrl($source, $record, $officeOrProject, $year, $period, $label);

                $statusStr = Str::lower(trim((string) ($record->status ?? '')));
                $queueKey = match ($statusStr) {
                    'pending_mlgoo' => 'pending_c_mlgoo',
                    'pending_po' => 'pending_provincial',
                    'pending_ro' => 'pending_regional',
                    'pending' => ($this->isPlguOffice($recordOffice) ? 'pending_provincial' : 'pending_c_mlgoo'),
                    default => 'pending_regional',
                };

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

            if ($user->isRegionalUser()) {
                $workflowQuery->where('current_approval_level', '>=', 2);
            } elseif ($user->isProvincialUser()) {
                $workflowQuery->where('current_approval_level', 1);
            } elseif ($user->isMlgooUser()) {
                $workflowQuery->where('current_approval_level', 1);
            }

            foreach ($workflowQuery->orderByDesc('updated_at')->get() as $workflow) {
                $projectCode = trim((string) $workflow->project_code);
                $quarter = trim((string) $workflow->quarter);
                $documentType = trim((string) $workflow->document_type);
                $uploader = $this->resolveUploader($workflow->uploader_id ?? null);
                $displayDocumentType = $this->documentDisplayLabel($documentType);
                $location = $this->resolveProjectLocation($projectCode);

                if ($user->isProvincialUser()) {
                    if ($location['province'] !== '' && Str::lower($location['province']) !== $user->normalizedProvince()) {
                        continue;
                    }
                } elseif ($user->isMlgooUser()) {
                    if (!$user->matchesAssignedOffice($location['city_municipality'])) {
                        continue;
                    }
                }

                $tasks->push([
                    'id' => 'status-fund-utilization-' . $workflow->id,
                    'task_title' => $displayDocumentType !== '' ? $displayDocumentType : 'Fund Utilization Report',
                    'message' => ($documentType !== '' ? $documentType . ' - ' : '') . 'Awaiting validation.',
                    'url' => '/fund-utilization/' . rawurlencode($projectCode) . '?quarter=' . rawurlencode($quarter) . '&document=' . rawurlencode($documentType),
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

                // Scope to user's jurisdiction
                if ($user->isLguUser() || $user->isMlgooUser()) {
                    if (!$user->matchesAssignedOffice($recordOffice)) {
                        continue;
                    }
                } elseif ($user->isProvincialUser()) {
                    if ($recordProvince !== '' && Str::lower($recordProvince) !== $user->normalizedProvince()) {
                        continue;
                    }
                }

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
                    'sender_name' => $uploader['name'],
                    'uploader_province' => $uploader['province'],
                    'created_at' => $record->uploaded_at ?? $record->created_at,
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

                if ($user->isLguUser() || $user->isMlgooUser()) {
                    if (!$user->matchesAssignedOffice($location['city_municipality'])) {
                        continue;
                    }
                } elseif ($user->isProvincialUser()) {
                    if ($location['province'] !== '' && Str::lower($location['province']) !== $user->normalizedProvince()) {
                        continue;
                    }
                }

                $tasks->push([
                    'id' => 'returned-fund-utilization-' . $workflow->id,
                    'task_title' => $displayDocumentType !== '' ? $displayDocumentType : 'Fund Utilization Report',
                    'message' => 'Returned: ' . $workflow->status,
                    'url' => '/fund-utilization/' . rawurlencode($projectCode) . '?quarter=' . rawurlencode($quarter) . '&document=' . rawurlencode($documentType),
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

    private function isPlguOffice(?string $officeName): bool
    {
        $normalized = Str::lower(trim((string) $officeName));
        return str_starts_with($normalized, 'plgu')
            || str_starts_with($normalized, 'provincial government');
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
}
