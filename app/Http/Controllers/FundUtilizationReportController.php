<?php

namespace App\Http\Controllers;

use App\Http\Requests\FundUtilizationApprovalActionRequest;
use App\Http\Requests\FundUtilizationBatchDocumentBulkUploadRequest;
use App\Http\Requests\FundUtilizationBatchDocumentUploadRequest;
use App\Http\Requests\FundUtilizationFdpUploadRequest;
use App\Http\Requests\FundUtilizationIndividualDocumentBulkUploadRequest;
use App\Http\Requests\FundUtilizationMovUploadRequest;
use App\Http\Requests\FundUtilizationPostingLinkRequest;
use App\Http\Requests\FundUtilizationWrittenNoticeUploadRequest;
use App\Models\ApprovalLog;
use App\Models\FundUtilizationReport;
use App\Models\FundUtilizationApprovalWorkflow;
use App\Models\LocallyFundedProject;
use App\Models\FURBatchDocument;
use App\Models\FURMovUpload;
use App\Models\FURWrittenNotice;
use App\Models\FURFDP;
use App\Models\FURAdminRemark;
use App\Support\LguReportorialDeadlineResolver;
use App\Support\InputSanitizer;
use App\Support\NotificationUrl;
use App\Models\User;
use App\Services\FundUtilizationWorkflowService;
use App\Services\SecureTimestampService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class FundUtilizationReportController extends Controller
{
    private array $fundUtilizationUploaderLevelCache = [];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:fund_utilization_reports,view')->only(['index', 'edit', 'show', 'viewDocument']);
        $this->middleware('crud_permission:fund_utilization_reports,add')->only(['create', 'store', 'uploadMOV', 'uploadBatchDocument', 'uploadBatchDocumentsBulk', 'uploadIndividualDocumentsBulk', 'uploadWrittenNotice', 'uploadFDP']);
        $this->middleware('crud_permission:fund_utilization_reports,update')->only(['update', 'approveUpload']);
        $this->middleware('crud_permission:fund_utilization_reports,delete')->only(['deleteDocument']);
    }

    private function fundUtilizationFundSources(): array
    {
        return ['SBDP', 'FALGU', 'CMGP'];
    }

    private function isSglgifFundSource(?string $value): bool
    {
        return strtoupper(trim((string) $value)) === 'SGLGIF';
    }

    private function isSglgifProjectCode(?string $projectCode): bool
    {
        return str_starts_with(strtoupper(trim((string) $projectCode)), 'SGLGIF');
    }

    private function isExcludedSglgifProject(?string $fundSource, ?string $projectCode = null): bool
    {
        return $this->isSglgifFundSource($fundSource) || $this->isSglgifProjectCode($projectCode);
    }

    private function applyNonSglgifSourceScope($query, string $sourceExpression, ?string $projectCodeExpression = null): void
    {
        $query->whereRaw('UPPER(TRIM(COALESCE(' . $sourceExpression . ', ""))) <> ?', ['SGLGIF']);

        if ($projectCodeExpression !== null) {
            $query->whereRaw('UPPER(TRIM(COALESCE(' . $projectCodeExpression . ', ""))) NOT LIKE ?', ['SGLGIF%']);
        }
    }

    private function ensureFundUtilizationSourceAllowed(?string $fundSource, ?string $projectCode = null): void
    {
        if ($this->isExcludedSglgifProject($fundSource, $projectCode)) {
            abort(404);
        }
    }

    private function syncMissingLfpReports(): void
    {
        if (!Schema::hasTable('tbfur')) {
            return;
        }

        $now = now();

        LocallyFundedProject::query()
            ->whereRaw('UPPER(TRIM(COALESCE(fund_source, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbfur')
                    ->whereColumn('tbfur.project_code', 'locally_funded_projects.subaybayan_project_code');
            })
            ->orderBy('id')
            ->chunkById(200, function ($projects) use ($now) {
                $rows = [];

                foreach ($projects as $project) {
                    $projectCode = trim((string) $project->subaybayan_project_code);
                    if ($projectCode === '') {
                        continue;
                    }

                    $rows[] = [
                        'project_code' => $projectCode,
                        'province' => $project->province,
                        'implementing_unit' => $project->implementing_unit,
                        'barangay' => $project->barangay,
                        'fund_source' => $project->fund_source,
                        'funding_year' => $project->funding_year,
                        'project_title' => $project->project_name,
                        'allocation' => $project->lgsf_allocation,
                        'contract_amount' => $project->contract_amount,
                        'project_status' => 'Ongoing',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('tbfur')->insertOrIgnore($rows);
                }
            });

        $this->syncMissingSubayReports();
    }

    private function normalizeText($value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    private function parseNumericValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9\\.-]/', '', $value);
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return null;
        }

        return (float) $clean;
    }

    private function parseYearValue($value, int $fallback): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/(19|20)\\d{2}/', $value, $matches)) {
            return (int) $matches[0];
        }

        $numeric = $this->parseNumericValue($value);
        if ($numeric === null) {
            return $fallback;
        }

        $year = (int) $numeric;
        if ($year < 1900 || $year > 2100) {
            return $fallback;
        }

        return $year;
    }

    private function normalizeFilterValues($rawValues, bool $lowercase = false): array
    {
        $values = is_array($rawValues) ? $rawValues : [$rawValues];

        return collect($values)
            ->map(function ($value) use ($lowercase) {
                $normalized = trim((string) $value);
                if ($normalized === '') {
                    return '';
                }

                return $lowercase ? strtolower($normalized) : $normalized;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function applyFundUtilizationFiltersToQueries($furQuery, $lfpQuery, array $filters, array $expressions, array $exclude = []): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $programs = $filters['program'] ?? [];
        $fundSources = $filters['fund_source'] ?? [];
        $fundingYears = $filters['funding_year'] ?? [];
        $projectStatuses = $filters['project_status'] ?? [];
        $provinces = $filters['province'] ?? [];
        $cities = $filters['city'] ?? [];
        $barangays = $filters['barangay'] ?? [];

        if (!in_array('search', $exclude, true) && $search !== '') {
            $keyword = '%' . strtolower($search) . '%';

                $furQuery->where(function ($query) use ($keyword, $expressions) {
                    $query->whereRaw('LOWER(tbfur.project_code) LIKE ?', [$keyword])
                        ->orWhereRaw('LOWER(tbfur.project_title) LIKE ?', [$keyword])
                        ->orWhereRaw('LOWER(tbfur.implementing_unit) LIKE ?', [$keyword])
                        ->orWhereRaw('LOWER(tbfur.province) LIKE ?', [$keyword])
                        ->orWhereRaw("LOWER({$expressions['fur_city']}) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER({$expressions['fur_barangay']}) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER({$expressions['fur_program']}) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER({$expressions['fur_fund_source']}) LIKE ?", [$keyword]);
                });

                $lfpQuery->where(function ($query) use ($keyword, $expressions) {
                    $query->whereRaw('LOWER(locally_funded_projects.subaybayan_project_code) LIKE ?', [$keyword])
                        ->orWhereRaw('LOWER(locally_funded_projects.project_name) LIKE ?', [$keyword])
                        ->orWhereRaw('LOWER(locally_funded_projects.implementing_unit) LIKE ?', [$keyword])
                        ->orWhereRaw('LOWER(locally_funded_projects.province) LIKE ?', [$keyword])
                        ->orWhereRaw("LOWER({$expressions['lfp_city']}) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER({$expressions['lfp_barangay']}) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER({$expressions['lfp_program']}) LIKE ?", [$keyword])
                        ->orWhereRaw("LOWER({$expressions['lfp_fund_source']}) LIKE ?", [$keyword]);
                });
            }

        if (!in_array('program', $exclude, true) && !empty($programs)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_program']})"), $programs);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_program']})"), $programs);
        }

        if (!in_array('fund_source', $exclude, true) && !empty($fundSources)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_fund_source']})"), $fundSources);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_fund_source']})"), $fundSources);
        }

        if (!in_array('funding_year', $exclude, true) && !empty($fundingYears)) {
            $furQuery->whereIn(DB::raw('TRIM(COALESCE(tbfur.funding_year, ""))'), $fundingYears);
            $lfpQuery->whereIn(DB::raw('TRIM(COALESCE(locally_funded_projects.funding_year, ""))'), $fundingYears);
        }

        if (!in_array('project_status', $exclude, true) && !empty($projectStatuses)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_project_status']})"), $projectStatuses);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_project_status']})"), $projectStatuses);
        }

        if (!in_array('province', $exclude, true) && !empty($provinces)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_province']})"), $provinces);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_province']})"), $provinces);
        }

        if (!in_array('city', $exclude, true) && !empty($cities)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_city']})"), $cities);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_city']})"), $cities);
        }

        if (!in_array('barangay', $exclude, true) && !empty($barangays)) {
            $furQuery->where(function ($query) use ($barangays, $expressions) {
                foreach ($barangays as $barangay) {
                    $query->orWhereRaw("LOWER({$expressions['fur_barangay']}) LIKE ?", ['%' . $barangay . '%']);
                }
            });

            $lfpQuery->where(function ($query) use ($barangays, $expressions) {
                foreach ($barangays as $barangay) {
                    $query->orWhereRaw("LOWER({$expressions['lfp_barangay']}) LIKE ?", ['%' . $barangay . '%']);
                }
            });
        }

        $submissionYear = trim((string) ($filters['submission_year'] ?? ''));
        if (!in_array('submission_year', $exclude, true) && $submissionYear !== '') {
            $furQuery->where(function ($query) use ($submissionYear) {
                $query->whereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_mov_uploads')
                        ->whereColumn('tbfur_mov_uploads.project_code', 'tbfur.project_code')
                        ->whereRaw('YEAR(tbfur_mov_uploads.mov_uploaded_at) = ?', [$submissionYear]);
                })->orWhereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_written_notice')
                        ->whereColumn('tbfur_written_notice.project_code', 'tbfur.project_code')
                        ->where(function ($wn) use ($submissionYear) {
                            $wn->whereRaw('YEAR(dbm_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(dilg_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(speaker_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(president_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(house_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(senate_uploaded_at) = ?', [$submissionYear]);
                        });
                })->orWhereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_fdp')
                        ->whereColumn('tbfur_fdp.project_code', 'tbfur.project_code')
                        ->where(function ($fdp) use ($submissionYear) {
                            $fdp->whereRaw('YEAR(fdp_uploaded_at) = ?', [$submissionYear])
                                ->orWhereRaw('YEAR(posting_uploaded_at) = ?', [$submissionYear]);
                        });
                })->orWhereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_batch_documents')
                        ->whereColumn('tbfur_batch_documents.project_code', 'tbfur.project_code')
                        ->whereRaw('YEAR(tbfur_batch_documents.created_at) = ?', [$submissionYear]);
                });
            });

            $lfpQuery->where(function ($query) use ($submissionYear) {
                $query->whereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_mov_uploads')
                        ->whereColumn('tbfur_mov_uploads.project_code', 'locally_funded_projects.subaybayan_project_code')
                        ->whereRaw('YEAR(tbfur_mov_uploads.mov_uploaded_at) = ?', [$submissionYear]);
                })->orWhereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_written_notice')
                        ->whereColumn('tbfur_written_notice.project_code', 'locally_funded_projects.subaybayan_project_code')
                        ->where(function ($wn) use ($submissionYear) {
                            $wn->whereRaw('YEAR(dbm_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(dilg_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(speaker_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(president_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(house_uploaded_at) = ?', [$submissionYear])
                               ->orWhereRaw('YEAR(senate_uploaded_at) = ?', [$submissionYear]);
                        });
                })->orWhereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_fdp')
                        ->whereColumn('tbfur_fdp.project_code', 'locally_funded_projects.subaybayan_project_code')
                        ->where(function ($fdp) use ($submissionYear) {
                            $fdp->whereRaw('YEAR(fdp_uploaded_at) = ?', [$submissionYear])
                                ->orWhereRaw('YEAR(posting_uploaded_at) = ?', [$submissionYear]);
                        });
                })->orWhereExists(function ($sub) use ($submissionYear) {
                    $sub->select(DB::raw(1))
                        ->from('tbfur_batch_documents')
                        ->whereColumn('tbfur_batch_documents.project_code', 'locally_funded_projects.subaybayan_project_code')
                        ->whereRaw('YEAR(tbfur_batch_documents.created_at) = ?', [$submissionYear]);
                });
            });
        }
    }

    private function buildFundUtilizationOptionQueries($furQuery, $lfpQuery, array $filters, array $expressions, array $exclude = []): array
    {
        $furOptionQuery = clone $furQuery;
        $lfpOptionQuery = clone $lfpQuery;

        $this->applyFundUtilizationFiltersToQueries($furOptionQuery, $lfpOptionQuery, $filters, $expressions, $exclude);

        return [$furOptionQuery, $lfpOptionQuery];
    }

    private function syncMissingSubayReports(): void
    {
        if (!Schema::hasTable('tbfur') || !Schema::hasTable('subay_project_profiles')) {
            return;
        }

        $now = now();

        DB::table('subay_project_profiles')
            ->whereNotNull('project_code')
            ->whereRaw('TRIM(project_code) <> ""')
            ->whereRaw('UPPER(TRIM(COALESCE(program, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbfur')
                    ->whereColumn('tbfur.project_code', 'subay_project_profiles.project_code');
            })
            ->orderBy('id')
            ->chunkById(200, function ($projects) use ($now) {
                $rows = [];
                $fallbackYear = (int) $now->year;

                foreach ($projects as $project) {
                    $projectCode = $this->normalizeText($project->project_code);
                    if ($projectCode === '') {
                        continue;
                    }

                    $fundingYear = $this->parseYearValue($project->funding_year ?? null, $fallbackYear);
                    $allocation = $this->parseNumericValue(
                        $project->national_subsidy_revised_allocation
                        ?? $project->national_subsidy_original_allocation
                        ?? $project->total_project_cost
                        ?? $project->total_estimated_cost_of_project
                        ?? null
                    );
                    $contractAmount = $this->parseNumericValue(
                        $project->contract_price
                        ?? $project->total_project_cost
                        ?? $project->total_estimated_cost_of_project
                        ?? null
                    );

                    $rows[] = [
                        'project_code' => $projectCode,
                        'province' => $this->normalizeText($project->province ?? null, 'Unknown'),
                        'implementing_unit' => $this->normalizeText(
                            $project->implementing_unit ?? $project->unit_implementing_the_project ?? null,
                            'Unknown'
                        ),
                        'barangay' => $this->normalizeText($project->barangay ?? null),
                        'fund_source' => $this->normalizeText($project->program ?? null, 'Unknown'),
                        'funding_year' => $fundingYear,
                        'project_title' => $this->normalizeText($project->project_title ?? null, $projectCode),
                        'allocation' => $allocation,
                        'contract_amount' => $contractAmount,
                        'project_status' => $this->normalizeText($project->status ?? null, 'Ongoing'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('tbfur')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Get report or LFP project by project code
     */
    private function getReportOrLfpProject($projectCode)
    {
        $report = FundUtilizationReport::where('project_code', $projectCode)->first();
        if ($report) {
            $this->ensureFundUtilizationSourceAllowed($report->fund_source, $report->project_code);
            $report->is_lfp = false;
            return $report;
        }

        $lfpProject = LocallyFundedProject::where('subaybayan_project_code', $projectCode)->firstOrFail();
        $this->ensureFundUtilizationSourceAllowed($lfpProject->fund_source, $lfpProject->subaybayan_project_code);

        // Ensure LFP projects have a parent tbfur row so upload FKs can be satisfied.
        $report = FundUtilizationReport::firstOrCreate(
            ['project_code' => $lfpProject->subaybayan_project_code],
            [
                'province' => $lfpProject->province,
                'implementing_unit' => $lfpProject->implementing_unit,
                'barangay' => $lfpProject->barangay,
                'fund_source' => $lfpProject->fund_source,
                'funding_year' => $lfpProject->funding_year,
                'project_title' => $lfpProject->project_name,
                'allocation' => $lfpProject->lgsf_allocation,
                'contract_amount' => $lfpProject->contract_amount,
                'project_status' => 'Ongoing',
            ]
        );

        $report->is_lfp = true;
        $report->lfp_id = $lfpProject->id;

        return $report;
    }

    private function fundUtilizationWorkflowSubmissionKey(string $documentType, string $quarter): string
    {
        return $documentType . '::' . $quarter;
    }

    private function resolveFundUtilizationWorkflowMap(string $projectCode): array
    {
        return FundUtilizationApprovalWorkflow::query()
            ->where('project_code', $projectCode)
            ->with(['uploader', 'logs.approver', 'logs.uploader'])
            ->get()
            ->keyBy(fn (FundUtilizationApprovalWorkflow $workflow) => $this->fundUtilizationWorkflowSubmissionKey($workflow->document_type, $workflow->quarter))
            ->all();
    }

    private function resolveFundUtilizationDocumentLabel(string $uploadType): string
    {
        return match ($uploadType) {
            'mov' => 'MOV file',
            'batch-document' => 'Batch Documents file',
            'written-notice-dbm' => 'DBM document',
            'written-notice-dilg' => 'DILG document',
            'written-notice-speaker' => 'Speaker document',
            'written-notice-president' => 'President document',
            'written-notice-house' => 'House document',
            'written-notice-senate' => 'Senate document',
            'fdp' => 'FDP document',
            'posting-link' => 'Posting link',
            default => 'Document',
        };
    }

    private function resolveFundUtilizationUploadRecord(string $uploadType, string $projectCode, string $quarter)
    {
        $recordQuery = match ($uploadType) {
            'mov' => FURMovUpload::query(),
            'batch-document' => FURBatchDocument::query(),
            'written-notice-dbm',
            'written-notice-dilg',
            'written-notice-speaker',
            'written-notice-president',
            'written-notice-house',
            'written-notice-senate' => FURWrittenNotice::query(),
            'fdp',
            'posting-link' => FURFDP::query(),
            default => null,
        };

        if ($recordQuery === null) {
            return null;
        }

        return $recordQuery
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();
    }

    private function normalizeBatchDocumentFilePaths($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } elseif (trim($value) !== '') {
                $value = [$value];
            } else {
                $value = [];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function getBatchDocumentFilePaths($record): array
    {
        if (!$record) {
            return [];
        }

        $paths = $this->normalizeBatchDocumentFilePaths($record->batch_document_files_json ?? null);
        if (!empty($paths)) {
            return $paths;
        }

        $singlePath = trim((string) ($record->batch_document_file_path ?? ''));
        return $singlePath !== '' ? [$singlePath] : [];
    }

    private function buildBatchDocumentStoragePayload(array $paths): array
    {
        $normalizedPaths = $this->normalizeBatchDocumentFilePaths($paths);

        return [
            'batch_document_file_path' => $normalizedPaths[0] ?? null,
            'batch_document_files_json' => !empty($normalizedPaths) ? $normalizedPaths : null,
        ];
    }

    private function buildBatchDocumentStoredFileName($file): string
    {
        $originalName = trim((string) $file->getClientOriginalName());
        $originalName = basename($originalName);

        if ($originalName !== '') {
            return $originalName;
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf'));

        return 'document.' . $extension;
    }

    private function fundUtilizationDeadlineReportingYear(): int
    {
        // Fund utilization timeliness tracking follows the LGU reportorial
        // deadline configuration for the current reporting cycle, not the
        // project's funding year.
        return (int) now()->year;
    }

    private function resolveFundUtilizationQuarterDeadline($report, string $quarter): ?array
    {
        return app(LguReportorialDeadlineResolver::class)->resolve(
            'fund_utilization_reports',
            $this->fundUtilizationDeadlineReportingYear(),
            $quarter
        );
    }

    private function isProvincialDilgUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $agency = strtoupper(trim((string) $user->agency));
        if ($agency !== 'DILG') {
            return false;
        }

        $provinceLower = strtolower(trim((string) $user->province));
        return $provinceLower !== '' && $provinceLower !== 'regional office';
    }

    private function isRegionalDilgUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $agency = strtoupper(trim((string) $user->agency));
        if ($agency !== 'DILG') {
            return false;
        }

        if ($user->isRegionalOfficeAssignment() || $user->isRegionalUser()) {
            return true;
        }

        $provinceLower = strtolower(trim((string) $user->province));
        $officeLower = strtolower(trim((string) $user->office));

        return $provinceLower === 'regional office' || $officeLower === 'regional office';
    }

    private function canUploadFundUtilizationDocuments(?User $user, $report = null): bool
    {
        if (!$user) {
            return false;
        }

        $baseAllowed = $user->isLguScopedUser() || $user->isProvincialDilgAssignment() || $user->isSuperAdmin();
        if (!$baseAllowed) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($report) {
            $userProvince = strtolower(trim((string) $user->province));
            $reportProvince = strtolower(trim((string) $report->province));

            if ($user->isProvincialDilgAssignment()) {
                return $userProvince === $reportProvince;
            }

            if ($user->isLguScopedUser()) {
                $userOffice = strtolower(trim((string) $user->office));
                $reportUnit = strtolower(trim((string) $report->implementing_unit));
                return $userProvince === $reportProvince 
                    && ($userOffice === '' || str_contains($reportUnit, $userOffice) || str_contains($userOffice, $reportUnit));
            }
        }

        return true;
    }

    private function isFundUtilizationProvincialValidator(?User $user): bool
    {
        return (bool) ($user && $user->isProvincialDilgAssignment());
    }

    private function isFundUtilizationRegionalValidator(?User $user): bool
    {
        return (bool) ($user && ($user->normalizedRole() === User::ROLE_REGIONAL || $user->isRegionalOfficeAssignment()));
    }

    private function resolveFundUtilizationUploaderLevel(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->isProvincialDilgAssignment()) {
            return 'provincial';
        }

        if ($user->normalizedRole() === User::ROLE_LGU) {
            return 'lgu';
        }

        if ($user->normalizedAgency() === 'lgu') {
            return 'lgu';
        }

        if ($user->isDilgUser() && !$this->isRegionalDilgUser($user)) {
            return 'provincial';
        }

        return null;
    }

    private function resolveFundUtilizationUploaderLevelById($userId): ?string
    {
        $normalizedUserId = trim((string) $userId);
        if ($normalizedUserId === '') {
            return null;
        }

        if (array_key_exists($normalizedUserId, $this->fundUtilizationUploaderLevelCache)) {
            return $this->fundUtilizationUploaderLevelCache[$normalizedUserId];
        }

        $uploader = User::query()
            ->select(['idno', 'role', 'agency', 'province', 'office'])
            ->where('idno', $normalizedUserId)
            ->first();

        return $this->fundUtilizationUploaderLevelCache[$normalizedUserId] = $this->resolveFundUtilizationUploaderLevel($uploader);
    }

    private function resolveFundUtilizationUploaderLevelFromRecord($record, ?string $encoderField = null): ?string
    {
        if (!$record) {
            return null;
        }

        $uploaderId = null;
        if ($encoderField && isset($record->{$encoderField})) {
            $uploaderId = $record->{$encoderField};
        }

        if (($uploaderId === null || trim((string) $uploaderId) === '') && isset($record->encoder_id)) {
            $uploaderId = $record->encoder_id;
        }

        return $this->resolveFundUtilizationUploaderLevelById($uploaderId);
    }

    private function resolveFundUtilizationRequiredValidatorLevel(?string $uploaderLevel): string
    {
        return $uploaderLevel === 'provincial' ? 'regional' : 'provincial';
    }

    private function resolveFundUtilizationCurrentValidatorLevel(
        ?string $uploaderLevel,
        ?string $status,
        $poApprovedAt,
        $roApprovedAt
    ): string {
        if ($uploaderLevel === 'provincial') {
            // Provincial uploads are only validated by the DILG Regional Office.
            return 'regional';
        }

        $normalizedStatus = strtolower(trim((string) $status));
        $hasPoApproval = !empty($poApprovedAt);
        $hasRoApproval = !empty($roApprovedAt);

        // LGU uploads are validated by Provincial Office first.
        // After PO approval, the upload remains in pending state and moves to Regional validation.
        if ($normalizedStatus === 'pending' && $hasPoApproval && !$hasRoApproval) {
            return 'regional';
        }

        return 'provincial';
    }

    private function resolveFundUtilizationReturnRecipients(?string $uploaderLevel, string $validatorLevel): array
    {
        if ($uploaderLevel === 'provincial') {
            return $validatorLevel === 'regional'
                ? ['DILG Provincial Office User']
                : [];
        }

        if ($validatorLevel === 'provincial') {
            return ['LGU User'];
        }

        return ['DILG Provincial Office User', 'LGU User'];
    }

    private function resolveFundUtilizationApprovalTimestampFields(?string $statusField = 'status'): array
    {
        $normalizedStatusField = trim((string) $statusField);
        if ($normalizedStatusField === '' || $normalizedStatusField === 'status') {
            return ['approved_at_dilg_po', 'approved_at_dilg_ro'];
        }

        $prefix = preg_replace('/_status$/', '', $normalizedStatusField);
        if (!is_string($prefix) || trim($prefix) === '') {
            return ['approved_at_dilg_po', 'approved_at_dilg_ro'];
        }

        return [
            $prefix . '_approved_at_dilg_po',
            $prefix . '_approved_at_dilg_ro',
        ];
    }

    private function canDeleteFundUtilizationDocument(
        ?User $actor,
        $record,
        string $projectCode,
        string $documentType,
        string $quarter,
        ?string $statusField = 'status',
        ?string $encoderField = null
    ): bool
    {
        if (!$actor || !$record) {
            return false;
        }

        $uploaderId = $encoderField ? ($record->{$encoderField} ?? null) : null;
        if ($uploaderId === null || trim((string) $uploaderId) === '') {
            $uploaderId = $record->encoder_id ?? null;
        }

        $normalizedUploaderId = trim((string) $uploaderId);
        if ($normalizedUploaderId === '') {
            return false;
        }

        $status = strtolower(trim((string) ($statusField ? ($record->{$statusField} ?? '') : '')));
        $uploaderLevel = $this->resolveFundUtilizationUploaderLevelFromRecord($record, $encoderField);
        [$poTimestampField, $roTimestampField] = $this->resolveFundUtilizationApprovalTimestampFields($statusField);
        $poApprovedAt = $record->{$poTimestampField} ?? null;
        $roApprovedAt = $record->{$roTimestampField} ?? null;
        $workflowMap = $this->resolveFundUtilizationWorkflowMap($projectCode);
        $workflow = $workflowMap[$this->fundUtilizationWorkflowSubmissionKey($documentType, $quarter)] ?? null;
        $workflowStatus = trim((string) ($workflow->status ?? ''));

        $requiredValidator = $this->resolveFundUtilizationCurrentValidatorLevel(
            $uploaderLevel,
            $status,
            $poApprovedAt,
            $roApprovedAt
        );

        if ($workflow) {
            $requiredValidator = ((int) ($workflow->current_approval_level ?? 1)) >= 2
                ? 'regional'
                : 'provincial';
        }

        $isReturned = $workflow
            ? str_starts_with($workflowStatus, 'Returned by ')
            : $status === 'returned';
        $isApproved = $workflow
            ? $workflowStatus === 'Approved'
            : $status === 'approved';
        $isPendingValidation = $workflow
            ? str_starts_with($workflowStatus, 'Pending Level ')
            : $status === 'pending';
        if ($isApproved) {
            return false;
        }
        $shouldHideLguDeleteUntilProvincialReturn = $uploaderLevel === 'lgu'
            && $requiredValidator === 'provincial'
            && !$isReturned;

        $actorIsUploader = $normalizedUploaderId === trim((string) $actor->idno);

        if ($actorIsUploader) {
            return !$isPendingValidation
                && !$shouldHideLguDeleteUntilProvincialReturn;
        }

        if ((!$isReturned && !$isApproved) || $isPendingValidation) {
            return false;
        }

        $uploader = User::query()
            ->select(['idno', 'role', 'agency', 'province', 'office'])
            ->where('idno', $normalizedUploaderId)
            ->first();

        if (!$uploader) {
            return false;
        }

        if ($uploader->normalizedRole() === User::ROLE_LGU) {
            if (!$actor->isLguScopedUser()) {
                return false;
            }

            $actorProvince = $actor->normalizedProvince();
            $uploaderProvince = $uploader->normalizedProvince();
            $actorOffice = $actor->normalizedOfficeComparable();
            $uploaderOffice = $uploader->normalizedOfficeComparable();

            return $actorProvince !== ''
                && $uploaderProvince !== ''
                && $actorProvince === $uploaderProvince
                && $actorOffice !== ''
                && $uploaderOffice !== ''
                && $actorOffice === $uploaderOffice;
        }

        if ($uploader->isProvincialDilgAssignment()) {
            if (!$actor->isProvincialDilgAssignment()) {
                return false;
            }

            $actorProvince = $actor->normalizedProvince();
            $uploaderProvince = $uploader->normalizedProvince();

            return $actorProvince !== ''
                && $uploaderProvince !== ''
                && $actorProvince === $uploaderProvince;
        }

        return false;
    }

    private function fundUtilizationValidatorLabel(string $validatorLevel): string
    {
        return $validatorLevel === 'regional'
            ? 'DILG Regional Office'
            : 'DILG Provincial Office';
    }

    private function buildFundUtilizationProvincialUploaderExistsExpression(string $encoderColumn): string
    {
        $regionalRole = strtolower(User::ROLE_REGIONAL);

        return "EXISTS (SELECT 1 FROM tbusers uploader WHERE uploader.idno = {$encoderColumn} AND UPPER(TRIM(COALESCE(uploader.agency, ''))) = 'DILG' AND LOWER(TRIM(COALESCE(uploader.province, ''))) <> 'regional office' AND LOWER(TRIM(COALESCE(uploader.role, ''))) NOT IN ('{$regionalRole}', 'lgu', 'mlgoo') AND TRIM(COALESCE(uploader.province, '')) <> '')";
    }

    private function buildFundUtilizationPoPendingExistsExpression(string $projectCodeColumn): string
    {
        $movProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('mu.mov_encoder_id');
        $batchProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('bd.batch_document_encoder_id');
        $dbmProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.dbm_encoder_id');
        $dilgProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.dilg_encoder_id');
        $speakerProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.speaker_encoder_id');
        $presidentProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.president_encoder_id');
        $houseProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.house_encoder_id');
        $senateProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.senate_encoder_id');
        $fdpProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('fdp.fdp_encoder_id');
        $postingProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('fdp.posting_encoder_id');

        $writtenNoticeConditions = implode(' OR ', [
            "(TRIM(COALESCE(wn.secretary_dbm_path, '')) <> '' AND LOWER(COALESCE(wn.dbm_status, '')) = 'pending' AND NOT {$dbmProvincialUploaderExpression} AND wn.dbm_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.secretary_dilg_path, '')) <> '' AND LOWER(COALESCE(wn.dilg_status, '')) = 'pending' AND NOT {$dilgProvincialUploaderExpression} AND wn.dilg_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.speaker_house_path, '')) <> '' AND LOWER(COALESCE(wn.speaker_status, '')) = 'pending' AND NOT {$speakerProvincialUploaderExpression} AND wn.speaker_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.president_senate_path, '')) <> '' AND LOWER(COALESCE(wn.president_status, '')) = 'pending' AND NOT {$presidentProvincialUploaderExpression} AND wn.president_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.house_committee_path, '')) <> '' AND LOWER(COALESCE(wn.house_status, '')) = 'pending' AND NOT {$houseProvincialUploaderExpression} AND wn.house_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.senate_committee_path, '')) <> '' AND LOWER(COALESCE(wn.senate_status, '')) = 'pending' AND NOT {$senateProvincialUploaderExpression} AND wn.senate_approved_at_dilg_po IS NULL)",
        ]);

        return '('
            . "EXISTS (SELECT 1 FROM tbfur_mov_uploads mu WHERE mu.project_code = {$projectCodeColumn} AND TRIM(COALESCE(mu.mov_file_path, '')) <> '' AND LOWER(COALESCE(mu.status, '')) = 'pending' AND NOT {$movProvincialUploaderExpression} AND mu.approved_at_dilg_po IS NULL)"
            . " OR EXISTS (SELECT 1 FROM tbfur_batch_documents bd WHERE bd.project_code = {$projectCodeColumn} AND TRIM(COALESCE(bd.batch_document_file_path, '')) <> '' AND LOWER(COALESCE(bd.status, '')) = 'pending' AND NOT {$batchProvincialUploaderExpression} AND bd.approved_at_dilg_po IS NULL)"
            . " OR EXISTS (SELECT 1 FROM tbfur_written_notice wn WHERE wn.project_code = {$projectCodeColumn} AND ({$writtenNoticeConditions}))"
            . " OR EXISTS (SELECT 1 FROM tbfur_fdp fdp WHERE fdp.project_code = {$projectCodeColumn} AND ("
                . "(TRIM(COALESCE(fdp.fdp_file_path, '')) <> '' AND LOWER(COALESCE(fdp.fdp_status, '')) = 'pending' AND NOT {$fdpProvincialUploaderExpression} AND fdp.approved_at_dilg_po IS NULL)"
                . " OR (TRIM(COALESCE(fdp.posting_link, '')) <> '' AND LOWER(COALESCE(fdp.posting_status, '')) = 'pending' AND NOT {$postingProvincialUploaderExpression} AND fdp.posting_approved_at_dilg_po IS NULL)"
            . '))'
        . ')';
    }

    private function buildFundUtilizationRoPendingExistsExpression(string $projectCodeColumn): string
    {
        $movProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('mu.mov_encoder_id');
        $batchProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('bd.batch_document_encoder_id');
        $dbmProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.dbm_encoder_id');
        $dilgProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.dilg_encoder_id');
        $speakerProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.speaker_encoder_id');
        $presidentProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.president_encoder_id');
        $houseProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.house_encoder_id');
        $senateProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('wn.senate_encoder_id');
        $fdpProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('fdp.fdp_encoder_id');
        $postingProvincialUploaderExpression = $this->buildFundUtilizationProvincialUploaderExistsExpression('fdp.posting_encoder_id');

        $writtenNoticeConditions = implode(' OR ', [
            "(TRIM(COALESCE(wn.secretary_dbm_path, '')) <> '' AND LOWER(COALESCE(wn.dbm_status, '')) = 'pending' AND ({$dbmProvincialUploaderExpression} OR (NOT {$dbmProvincialUploaderExpression} AND wn.dbm_approved_at_dilg_po IS NOT NULL)) AND wn.dbm_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.secretary_dilg_path, '')) <> '' AND LOWER(COALESCE(wn.dilg_status, '')) = 'pending' AND ({$dilgProvincialUploaderExpression} OR (NOT {$dilgProvincialUploaderExpression} AND wn.dilg_approved_at_dilg_po IS NOT NULL)) AND wn.dilg_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.speaker_house_path, '')) <> '' AND LOWER(COALESCE(wn.speaker_status, '')) = 'pending' AND ({$speakerProvincialUploaderExpression} OR (NOT {$speakerProvincialUploaderExpression} AND wn.speaker_approved_at_dilg_po IS NOT NULL)) AND wn.speaker_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.president_senate_path, '')) <> '' AND LOWER(COALESCE(wn.president_status, '')) = 'pending' AND ({$presidentProvincialUploaderExpression} OR (NOT {$presidentProvincialUploaderExpression} AND wn.president_approved_at_dilg_po IS NOT NULL)) AND wn.president_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.house_committee_path, '')) <> '' AND LOWER(COALESCE(wn.house_status, '')) = 'pending' AND ({$houseProvincialUploaderExpression} OR (NOT {$houseProvincialUploaderExpression} AND wn.house_approved_at_dilg_po IS NOT NULL)) AND wn.house_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.senate_committee_path, '')) <> '' AND LOWER(COALESCE(wn.senate_status, '')) = 'pending' AND ({$senateProvincialUploaderExpression} OR (NOT {$senateProvincialUploaderExpression} AND wn.senate_approved_at_dilg_po IS NOT NULL)) AND wn.senate_approved_at_dilg_ro IS NULL)",
        ]);

        return '('
            . "EXISTS (SELECT 1 FROM tbfur_mov_uploads mu WHERE mu.project_code = {$projectCodeColumn} AND TRIM(COALESCE(mu.mov_file_path, '')) <> '' AND LOWER(COALESCE(mu.status, '')) = 'pending' AND ({$movProvincialUploaderExpression} OR (NOT {$movProvincialUploaderExpression} AND mu.approved_at_dilg_po IS NOT NULL)) AND mu.approved_at_dilg_ro IS NULL)"
            . " OR EXISTS (SELECT 1 FROM tbfur_batch_documents bd WHERE bd.project_code = {$projectCodeColumn} AND TRIM(COALESCE(bd.batch_document_file_path, '')) <> '' AND LOWER(COALESCE(bd.status, '')) = 'pending' AND ({$batchProvincialUploaderExpression} OR (NOT {$batchProvincialUploaderExpression} AND bd.approved_at_dilg_po IS NOT NULL)) AND bd.approved_at_dilg_ro IS NULL)"
            . " OR EXISTS (SELECT 1 FROM tbfur_written_notice wn WHERE wn.project_code = {$projectCodeColumn} AND ({$writtenNoticeConditions}))"
            . " OR EXISTS (SELECT 1 FROM tbfur_fdp fdp WHERE fdp.project_code = {$projectCodeColumn} AND ("
                . "(TRIM(COALESCE(fdp.fdp_file_path, '')) <> '' AND LOWER(COALESCE(fdp.fdp_status, '')) = 'pending' AND ({$fdpProvincialUploaderExpression} OR (NOT {$fdpProvincialUploaderExpression} AND fdp.approved_at_dilg_po IS NOT NULL)) AND fdp.approved_at_dilg_ro IS NULL)"
                . " OR (TRIM(COALESCE(fdp.posting_link, '')) <> '' AND LOWER(COALESCE(fdp.posting_status, '')) = 'pending' AND ({$postingProvincialUploaderExpression} OR (NOT {$postingProvincialUploaderExpression} AND fdp.posting_approved_at_dilg_po IS NOT NULL)) AND fdp.posting_approved_at_dilg_ro IS NULL)"
            . '))'
        . ')';
    }

    private function buildFundUtilizationValidationPriorityExpression(?User $user, string $projectCodeColumn): string
    {
        $poPendingExpression = $this->buildFundUtilizationPoPendingExistsExpression($projectCodeColumn);
        $roPendingExpression = $this->buildFundUtilizationRoPendingExistsExpression($projectCodeColumn);

        if ($this->isFundUtilizationRegionalValidator($user)) {
            return "CASE WHEN {$roPendingExpression} THEN 0 WHEN {$poPendingExpression} THEN 1 ELSE 2 END";
        }

        if ($this->isFundUtilizationProvincialValidator($user)) {
            return "CASE WHEN {$poPendingExpression} THEN 0 WHEN {$roPendingExpression} THEN 1 ELSE 2 END";
        }

        return "CASE WHEN {$poPendingExpression} OR {$roPendingExpression} THEN 0 ELSE 1 END";
    }

    /**
     * Display a listing of the Fund Utilization Reports.
     */
    public function index(Request $request)
    {
        $this->syncMissingLfpReports();
        [$reportsQuery, $filters, $filterOptions] = $this->buildFilteredReportsQuery($request);
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $applyListingOrder = function ($query) {
            return $query
                ->orderBy('validation_priority')
                ->orderByRaw("CASE WHEN project_status IS NULL OR TRIM(project_status) = '' THEN 1 ELSE 0 END")
                ->orderBy('project_status')
                ->orderByRaw('CAST(funding_year AS UNSIGNED) DESC')
                ->orderByRaw("CASE WHEN city_municipality IS NULL OR TRIM(city_municipality) = '' THEN 1 ELSE 0 END")
                ->orderBy('city_municipality')
                ->orderByRaw("CASE WHEN province IS NULL OR TRIM(province) = '' THEN 1 ELSE 0 END")
                ->orderBy('province')
                ->orderBy('project_code');
        };

        $reportsCollection = $this->attachFundUtilizationListingData(
            $applyListingOrder($reportsQuery)->get()
        );

        $sortedReports = $this->sortFundUtilizationReportsForListing($reportsCollection);
        $batchUploadProjects = $sortedReports;
        $quarterlyDashboard = $this->buildQuarterlySubmissionDashboard($sortedReports);

        $currentPage = max(1, (int) $request->query('page', 1));
        $reports = new LengthAwarePaginator(
            $sortedReports->slice(($currentPage - 1) * $perPage, $perPage)->values(),
            $sortedReports->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('reports.fund-utilization.index', compact('reports', 'filters', 'filterOptions', 'perPage', 'batchUploadProjects', 'quarterlyDashboard'));
    }

    /**
     * Build quarterly submission dashboard statistics from the already-computed reports collection.
     * No additional DB queries — uses the per-quarter bg colors and percentages already attached.
     */
    private function buildQuarterlySubmissionDashboard($sortedReports): array
    {
        $totalProjects = $sortedReports->count();
        $quarters = ['q1', 'q2', 'q3', 'q4'];
        $dashboard = [
            'total_projects' => $totalProjects,
            'quarters' => [],
        ];

        // Color-to-status mapping from resolveFundUtilizationQuarterColor():
        //   #f3f4f6 = gray   = no uploads
        //   #fee2e2 = red    = returned
        //   #fef9c3 = yellow = pending PO / incomplete
        //   #ffedd5 = orange = pending RO
        //   #ecfdf5 = green  = fully approved
        foreach ($quarters as $q) {
            $bgField = 'quarter_' . $q . '_bg';
            $pctField = 'quarter_' . $q . '_percentage';

            $withSubmissions = 0;
            $fullyCompliant = 0;
            $pendingValidation = 0;
            $returned = 0;
            $noSubmission = 0;

            foreach ($sortedReports as $report) {
                $bg = $report->{$bgField} ?? '#f3f4f6';
                $pct = (int) ($report->{$pctField} ?? 0);

                if ($bg === '#f3f4f6') {
                    $noSubmission++;
                } else {
                    $withSubmissions++;

                    if ($bg === '#ecfdf5') {
                        $fullyCompliant++;
                    } elseif ($bg === '#fee2e2') {
                        $returned++;
                    } else {
                        // yellow (#fef9c3) or orange (#ffedd5) = pending
                        $pendingValidation++;
                    }
                }
            }

            $submissionRate = $totalProjects > 0
                ? round(($fullyCompliant / $totalProjects) * 100, 2)
                : 0.0;

            $dashboard['quarters'][$q] = [
                'label' => strtoupper($q),
                'total' => $totalProjects,
                'with_submissions' => $withSubmissions,
                'fully_compliant' => $fullyCompliant,
                'pending_validation' => $pendingValidation,
                'returned' => $returned,
                'no_submission' => $noSubmission,
                'submission_rate' => $submissionRate,
            ];
        }

        // Overall stats across all quarters
        $totalSlots = $totalProjects * 4;
        $totalSubmitted = collect($dashboard['quarters'])->sum('with_submissions');
        $totalCompliant = collect($dashboard['quarters'])->sum('fully_compliant');
        $totalPending = collect($dashboard['quarters'])->sum('pending_validation');
        $totalReturned = collect($dashboard['quarters'])->sum('returned');
        $totalNoSubmission = collect($dashboard['quarters'])->sum('no_submission');

        $dashboard['overall_submission_rate'] = $totalSlots > 0
            ? round(($totalCompliant / $totalSlots) * 100)
            : 0;
        $dashboard['overall_compliant'] = $totalCompliant;
        $dashboard['overall_pending'] = $totalPending;
        $dashboard['overall_returned'] = $totalReturned;
        $dashboard['overall_no_submission'] = $totalNoSubmission;
        $dashboard['overall_submitted'] = $totalSubmitted;
        $dashboard['total_slots'] = $totalSlots;

        return $dashboard;
    }

    private function attachFundUtilizationListingData($reportsCollection)
    {
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $projectCodes = $reportsCollection
            ->pluck('project_code')
            ->filter(fn($code) => trim((string) $code) !== '')
            ->map(fn($code) => trim((string) $code))
            ->unique()
            ->values();

        $movUploadsByKey = collect();
        $batchDocumentsByKey = collect();
        $writtenNoticesByKey = collect();
        $fdpDocumentsByKey = collect();
        $workflows = collect();

        if ($projectCodes->isNotEmpty()) {
            $movUploadsByKey = FURMovUpload::query()
                ->whereIn('project_code', $projectCodes)
                ->whereIn('quarter', $quarters)
                ->get()
                ->keyBy(fn($row) => $row->project_code . '|' . strtoupper((string) $row->quarter));

            $batchDocumentsByKey = FURBatchDocument::query()
                ->whereIn('project_code', $projectCodes)
                ->whereIn('quarter', $quarters)
                ->get()
                ->keyBy(fn($row) => $row->project_code . '|' . strtoupper((string) $row->quarter));

            $writtenNoticesByKey = FURWrittenNotice::query()
                ->whereIn('project_code', $projectCodes)
                ->whereIn('quarter', $quarters)
                ->get()
                ->keyBy(fn($row) => $row->project_code . '|' . strtoupper((string) $row->quarter));

            $fdpDocumentsByKey = FURFDP::query()
                ->whereIn('project_code', $projectCodes)
                ->whereIn('quarter', $quarters)
                ->get()
                ->keyBy(fn($row) => $row->project_code . '|' . strtoupper((string) $row->quarter));

            $workflows = FundUtilizationApprovalWorkflow::query()
                ->whereIn('project_code', $projectCodes)
                ->get();
        }

        return $reportsCollection->map(function ($report) use ($quarters, $movUploadsByKey, $batchDocumentsByKey, $writtenNoticesByKey, $fdpDocumentsByKey, $workflows) {
            $projectCode = trim((string) ($report->project_code ?? ''));
            $quarterDocuments = [];

            foreach ($quarters as $quarter) {
                $key = $projectCode . '|' . $quarter;
                $movUpload = $movUploadsByKey->get($key);
                $batchDocument = $batchDocumentsByKey->get($key);
                $writtenNotice = $writtenNoticesByKey->get($key);
                $fdpDocument = $fdpDocumentsByKey->get($key);

                $report->{'quarter_' . strtolower($quarter) . '_percentage'} = $this->calculateAccomplishmentPercentage($movUpload, $writtenNotice, $fdpDocument, $batchDocument);
                $quarterDocuments[$quarter] = [
                    'mov' => $movUpload,
                    'batch_document' => $batchDocument,
                    'written_notice' => $writtenNotice,
                    'fdp' => $fdpDocument,
                ];
            }

            $projectWorkflows = $workflows->filter(fn($w) => $w->project_code === $projectCode);
            $workflowMap = [];
            foreach ($projectWorkflows as $w) {
                $mapKey = $this->fundUtilizationWorkflowSubmissionKey($w->document_type, $w->quarter);
                $workflowMap[$mapKey] = $w;
            }

            foreach ($quarters as $quarter) {
                $key = $projectCode . '|' . $quarter;
                $movUpload = $movUploadsByKey->get($key);
                $batchDocument = $batchDocumentsByKey->get($key);
                $writtenNotice = $writtenNoticesByKey->get($key);
                $fdpDocument = $fdpDocumentsByKey->get($key);

                $style = $this->resolveFundUtilizationQuarterColor(
                    $movUpload,
                    $writtenNotice,
                    $fdpDocument,
                    $batchDocument,
                    $quarter,
                    $workflowMap
                );
                $report->{'quarter_' . strtolower($quarter) . '_bg'} = $style['bg'];
                $report->{'quarter_' . strtolower($quarter) . '_text'} = $style['text'];
                $report->{'quarter_' . strtolower($quarter) . '_border'} = $style['border'];
                $report->{'quarter_' . strtolower($quarter) . '_tooltip'} = $style['tooltip'];
            }

            $report->validation_summary = $this->summarizeFundUtilizationValidation($quarterDocuments);
            $report->validation_listing = $this->summarizeFundUtilizationListing($quarterDocuments, $workflowMap);

            return $report;
        });
    }

    private function resolveFundUtilizationQuarterColor(
        $movUpload,
        $writtenNotice,
        $fdpDocument,
        $batchDocument,
        string $quarter,
        array $workflowMap
    ): array {
        $documents = [];

        if ($movUpload && trim((string) ($movUpload->mov_file_path ?? '')) !== '') {
            $documents[] = [
                'path' => $movUpload->mov_file_path,
                'status' => $movUpload->status ?? null,
                'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($movUpload, 'mov_encoder_id'),
                'approved_at_dilg_po' => $movUpload->approved_at_dilg_po ?? null,
                'approved_at_dilg_ro' => $movUpload->approved_at_dilg_ro ?? null,
            ];
        }

        $batchDocumentPaths = $this->getBatchDocumentFilePaths($batchDocument);
        if ($batchDocument && !empty($batchDocumentPaths)) {
            $documents[] = [
                'path' => $batchDocumentPaths[0],
                'status' => $batchDocument->status ?? null,
                'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($batchDocument, 'batch_document_encoder_id'),
                'approved_at_dilg_po' => $batchDocument->approved_at_dilg_po ?? null,
                'approved_at_dilg_ro' => $batchDocument->approved_at_dilg_ro ?? null,
            ];
        }

        if ($writtenNotice) {
            $wnDocs = [
                ['path' => $writtenNotice->secretary_dbm_path ?? null, 'status' => $writtenNotice->dbm_status ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'dbm_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->dbm_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->dbm_approved_at_dilg_ro ?? null],
                ['path' => $writtenNotice->secretary_dilg_path ?? null, 'status' => $writtenNotice->dilg_status ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'dilg_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->dilg_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->dilg_approved_at_dilg_ro ?? null],
                ['path' => $writtenNotice->speaker_house_path ?? null, 'status' => $writtenNotice->speaker_status ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'speaker_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->speaker_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->speaker_approved_at_dilg_ro ?? null],
                ['path' => $writtenNotice->president_senate_path ?? null, 'status' => $writtenNotice->president_status ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'president_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->president_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->president_approved_at_dilg_ro ?? null],
                ['path' => $writtenNotice->house_committee_path ?? null, 'status' => $writtenNotice->house_status ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'house_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->house_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->house_approved_at_dilg_ro ?? null],
                ['path' => $writtenNotice->senate_committee_path ?? null, 'status' => $writtenNotice->senate_status ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'senate_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->senate_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->senate_approved_at_dilg_ro ?? null],
            ];
            foreach ($wnDocs as $doc) {
                if (trim((string) ($doc['path'] ?? '')) !== '') {
                    $documents[] = $doc;
                }
            }
        }

        if ($fdpDocument && trim((string) ($fdpDocument->fdp_file_path ?? '')) !== '') {
            $documents[] = [
                'path' => $fdpDocument->fdp_file_path,
                'status' => $fdpDocument->fdp_status ?? null,
                'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($fdpDocument, 'fdp_encoder_id'),
                'approved_at_dilg_po' => $fdpDocument->approved_at_dilg_po ?? null,
                'approved_at_dilg_ro' => $fdpDocument->approved_at_dilg_ro ?? null,
            ];
        }

        if ($fdpDocument && trim((string) ($fdpDocument->posting_link ?? '')) !== '') {
            $documents[] = [
                'path' => $fdpDocument->posting_link,
                'status' => $fdpDocument->posting_status ?? null,
                'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($fdpDocument, 'posting_encoder_id'),
                'approved_at_dilg_po' => $fdpDocument->posting_approved_at_dilg_po ?? null,
                'approved_at_dilg_ro' => $fdpDocument->posting_approved_at_dilg_ro ?? null,
            ];
        }

        if (empty($documents)) {
            return [
                'bg' => '#f3f4f6',
                'text' => '#6b7280',
                'border' => '#d1d5db',
                'tooltip' => 'no uploads',
            ]; // gray: no uploads
        }

        // 1. Returned: Red
        foreach ($documents as $doc) {
            if ($this->hasFundUtilizationReturned($doc['path'], $doc['status'])) {
                return [
                    'bg' => '#fee2e2',
                    'text' => '#991b1b',
                    'border' => '#fca5a5',
                    'tooltip' => 'with upload and have returned documents',
                ];
            }
        }

        // 2. Pending PO: Yellow
        foreach ($documents as $doc) {
            if ($this->hasFundUtilizationPendingPo(
                $doc['path'],
                $doc['status'],
                $doc['uploader_level'],
                $doc['approved_at_dilg_po'],
                $doc['approved_at_dilg_ro']
            )) {
                return [
                    'bg' => '#fef9c3',
                    'text' => '#854d0e',
                    'border' => '#fef08a',
                    'tooltip' => 'with upload but not 100%',
                ];
            }
        }

        // 3. Pending RO: Orange
        foreach ($documents as $doc) {
            if ($this->hasFundUtilizationPendingRo(
                $doc['path'],
                $doc['status'],
                $doc['uploader_level'],
                $doc['approved_at_dilg_po'],
                $doc['approved_at_dilg_ro']
            )) {
                return [
                    'bg' => '#ffedd5',
                    'text' => '#9a3412',
                    'border' => '#fed7aa',
                    'tooltip' => 'with new upload and with approval by the regional user',
                ];
            }
        }

        // 4. Incomplete: Yellow
        $pct = $this->calculateAccomplishmentPercentage($movUpload, $writtenNotice, $fdpDocument, $batchDocument);
        if ($pct < 100) {
            return [
                'bg' => '#fef9c3',
                'text' => '#854d0e',
                'border' => '#fef08a',
                'tooltip' => 'with upload but not 100%',
            ];
        }

        // 5. Approved: Green
        return [
            'bg' => '#ecfdf5',
            'text' => '#065f46',
            'border' => '#a7f3d0',
            'tooltip' => 'fully approved',
        ];
    }

    private function sortFundUtilizationReportsForListing($reportsCollection)
    {
        return $reportsCollection
            ->sort(function ($left, $right) {
                // 1. Returned documents first
                $leftReturned = (int) (($left->validation_summary['returned_count'] ?? 0) > 0);
                $rightReturned = (int) (($right->validation_summary['returned_count'] ?? 0) > 0);

                if ($leftReturned !== $rightReturned) {
                    return $rightReturned <=> $leftReturned;
                }

                // 2. Pending validation documents second
                $leftPending = (int) (($left->validation_summary['pending_total'] ?? 0) > 0);
                $rightPending = (int) (($right->validation_summary['pending_total'] ?? 0) > 0);

                if ($leftPending !== $rightPending) {
                    return $rightPending <=> $leftPending;
                }

                $leftApprovalTimestamp = $left->validation_listing['approval_status_sort_timestamp'] ?? PHP_INT_MIN;
                $rightApprovalTimestamp = $right->validation_listing['approval_status_sort_timestamp'] ?? PHP_INT_MIN;

                if ($leftApprovalTimestamp !== $rightApprovalTimestamp) {
                    return $rightApprovalTimestamp <=> $leftApprovalTimestamp;
                }

                $leftSubmittedTimestamp = $left->validation_listing['date_submitted_sort_timestamp'] ?? PHP_INT_MIN;
                $rightSubmittedTimestamp = $right->validation_listing['date_submitted_sort_timestamp'] ?? PHP_INT_MIN;

                if ($leftSubmittedTimestamp !== $rightSubmittedTimestamp) {
                    return $rightSubmittedTimestamp <=> $leftSubmittedTimestamp;
                }

                $leftPriority = (int) ($left->validation_priority ?? PHP_INT_MAX);
                $rightPriority = (int) ($right->validation_priority ?? PHP_INT_MAX);

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                return strnatcasecmp((string) ($left->project_code ?? ''), (string) ($right->project_code ?? ''));
            })
            ->values();
    }

    /**
     * Export Fund Utilization Reports to CSV, Excel, or PDF.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'xls'));
        if ($format === 'excel' || $format === 'xlsx') {
            $format = 'xls';
        }

        if (!in_array($format, ['xls'], true)) {
            return redirect()->route('fund-utilization.index')
                ->with('error', 'Invalid export format.');
        }

        $this->syncMissingLfpReports();
        [$reportsQuery, $filters] = $this->buildFilteredReportsQuery($request);
        $selectedQuarter = trim((string) $request->query('quarter', ''));

        if ($selectedQuarter !== '' && !in_array($selectedQuarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
            return redirect()->route('fund-utilization.index')
                ->with('error', 'Invalid quarter selected.');
        }

        $reports = $reportsQuery
            ->with(['movUploads', 'batchDocuments', 'writtenNotices', 'fdpDocuments'])
            ->orderByRaw("CASE WHEN project_status IS NULL OR TRIM(project_status) = '' THEN 1 ELSE 0 END")
            ->orderBy('project_status')
            ->orderByRaw('CAST(funding_year AS UNSIGNED) DESC')
            ->orderByRaw("CASE WHEN city_municipality IS NULL OR TRIM(city_municipality) = '' THEN 1 ELSE 0 END")
            ->orderBy('city_municipality')
            ->orderByRaw("CASE WHEN province IS NULL OR TRIM(province) = '' THEN 1 ELSE 0 END")
            ->orderBy('province')
            ->orderBy('project_code')
            ->get();

        // Generate title for the export
        $year = now()->year;
        $quarterNumber = str_replace('Q', '', $selectedQuarter ?: 'Q1');
        $quarterOrdinal = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'][$quarterNumber];
        $title = "STATUS ON THE SUBMISSION OF QUARTERLY FUND UTILIZATION REPORTS (FUR) FOR THE {$quarterOrdinal} QUARTER OF CY {$year} FOR LGSF PROJECTS";

        $headers = [
            'Project Code',
            'Province',
            'Implementing Unit',
            'Barangay',
            'Fund Source',
            'Funding Year',
            'Allocation',
            'Contract Amount',
            'Project Status',
            'Project Title',
            'Upload file for Fund Utilization Report (MOV on PDF Format)',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'Upload file for Batch Documents (MOV on PDF Format)',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'WRITTEN NOTICE (MOV SCREENSHOT OF EMAILED NOTICE AND WRITTEN NOTICE PDF FORMAT) Secretary of DBM',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'WRITTEN NOTICE (MOV SCREENSHOT OF EMAILED NOTICE AND WRITTEN NOTICE PDF FORMAT) Speaker of the House',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'WRITTEN NOTICE (MOV SCREENSHOT OF EMAILED NOTICE AND WRITTEN NOTICE PDF FORMAT) House Committee on Appropriation',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'WRITTEN NOTICE (MOV SCREENSHOT OF EMAILED NOTICE AND WRITTEN NOTICE PDF FORMAT) Secretary of DILG',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'WRITTEN NOTICE (MOV SCREENSHOT OF EMAILED NOTICE AND WRITTEN NOTICE PDF FORMAT) President of the Senate',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'WRITTEN NOTICE (MOV SCREENSHOT OF EMAILED NOTICE AND WRITTEN NOTICE PDF FORMAT) Senate Committee on Finance',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'Upload file for Full Disclosure Policy (FDP on PDF Format)',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
            'LGU Website/ Social Media Account (the link of the Posting)',
            'Date Uploaded',
            'Date Valdiated by DILG Provincial Office',
            'Date Valdiated by DILG Regional Office',
        ];

        $rows = [];
        foreach ($reports as $report) {
            $quarter = $selectedQuarter ?: 'Q1'; // Default to Q1 if no quarter selected, but since filtered, it should have data

            $movUpload = $report->movUploads()->where('quarter', $quarter)->first();
            $batchDocument = $report->batchDocuments()->where('quarter', $quarter)->first();
            $writtenNotice = $report->writtenNotices()->where('quarter', $quarter)->first();
            $fdpDocument = $report->fdpDocuments()->where('quarter', $quarter)->first();

            $isBatchApproved = $batchDocument && $batchDocument->approved_at_dilg_ro;
            $batchUploadDate = $batchDocument && $batchDocument->batch_document_uploaded_at ? $batchDocument->batch_document_uploaded_at->format('Y-m-d H:i:s') : '-';
            $batchPoDate = $batchDocument && $batchDocument->approved_at_dilg_po ? $batchDocument->approved_at_dilg_po->format('Y-m-d H:i:s') : '-';
            $batchRoDate = $batchDocument && $batchDocument->approved_at_dilg_ro ? $batchDocument->approved_at_dilg_ro->format('Y-m-d H:i:s') : '-';
            
            $getDocLink = function ($pathField, $docType) use ($report, $quarter) {
                if (empty(trim((string) $pathField))) return '-';
                return route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => $docType, 'quarter' => $quarter]);
            };

            $batchMarker = $getDocLink($batchDocument ? $batchDocument->batch_document_file_path : null, 'batch-document');
            $fdpPostingLink = $fdpDocument ? trim((string) $fdpDocument->posting_link) : '';
            $fdpPostingOutput = $fdpPostingLink !== '' ? $fdpPostingLink : '-';

            $rows[] = [
                $report->project_code,
                $report->province,
                $report->implementing_unit,
                $report->barangay,
                $report->fund_source,
                $report->funding_year,
                $report->allocation !== null ? 'PHP ' . number_format((float) $report->allocation, 2) : '-',
                $report->contract_amount !== null ? 'PHP ' . number_format((float) $report->contract_amount, 2) : '-',
                $report->project_status,
                $report->project_title,
                
                // MOV
                $isBatchApproved ? $batchMarker : $getDocLink($movUpload ? $movUpload->mov_file_path : null, 'mov'),
                $isBatchApproved ? $batchUploadDate : ($movUpload && $movUpload->mov_uploaded_at ? $movUpload->mov_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($movUpload && $movUpload->approved_at_dilg_po ? $movUpload->approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($movUpload && $movUpload->approved_at_dilg_ro ? $movUpload->approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // Batch Document
                $batchMarker,
                $batchUploadDate,
                $batchPoDate,
                $batchRoDate,
                
                // Secretary DBM
                $isBatchApproved ? $batchMarker : $getDocLink($writtenNotice ? $writtenNotice->secretary_dbm_path : null, 'written-notice-dbm'),
                $isBatchApproved ? $batchUploadDate : ($writtenNotice && $writtenNotice->dbm_uploaded_at ? $writtenNotice->dbm_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($writtenNotice && $writtenNotice->dbm_approved_at_dilg_po ? $writtenNotice->dbm_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($writtenNotice && $writtenNotice->dbm_approved_at_dilg_ro ? $writtenNotice->dbm_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // Speaker House
                $isBatchApproved ? $batchMarker : $getDocLink($writtenNotice ? $writtenNotice->speaker_house_path : null, 'written-notice-speaker'),
                $isBatchApproved ? $batchUploadDate : ($writtenNotice && $writtenNotice->speaker_uploaded_at ? $writtenNotice->speaker_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($writtenNotice && $writtenNotice->speaker_approved_at_dilg_po ? $writtenNotice->speaker_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($writtenNotice && $writtenNotice->speaker_approved_at_dilg_ro ? $writtenNotice->speaker_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // House Committee
                $isBatchApproved ? $batchMarker : $getDocLink($writtenNotice ? $writtenNotice->house_committee_path : null, 'written-notice-house'),
                $isBatchApproved ? $batchUploadDate : ($writtenNotice && $writtenNotice->house_uploaded_at ? $writtenNotice->house_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($writtenNotice && $writtenNotice->house_approved_at_dilg_po ? $writtenNotice->house_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($writtenNotice && $writtenNotice->house_approved_at_dilg_ro ? $writtenNotice->house_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // Secretary DILG
                $isBatchApproved ? $batchMarker : $getDocLink($writtenNotice ? $writtenNotice->secretary_dilg_path : null, 'written-notice-dilg'),
                $isBatchApproved ? $batchUploadDate : ($writtenNotice && $writtenNotice->dilg_uploaded_at ? $writtenNotice->dilg_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($writtenNotice && $writtenNotice->dilg_approved_at_dilg_po ? $writtenNotice->dilg_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($writtenNotice && $writtenNotice->dilg_approved_at_dilg_ro ? $writtenNotice->dilg_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // President Senate
                $isBatchApproved ? $batchMarker : $getDocLink($writtenNotice ? $writtenNotice->president_senate_path : null, 'written-notice-president'),
                $isBatchApproved ? $batchUploadDate : ($writtenNotice && $writtenNotice->president_uploaded_at ? $writtenNotice->president_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($writtenNotice && $writtenNotice->president_approved_at_dilg_po ? $writtenNotice->president_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($writtenNotice && $writtenNotice->president_approved_at_dilg_ro ? $writtenNotice->president_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // Senate Committee
                $isBatchApproved ? $batchMarker : $getDocLink($writtenNotice ? $writtenNotice->senate_committee_path : null, 'written-notice-senate'),
                $isBatchApproved ? $batchUploadDate : ($writtenNotice && $writtenNotice->senate_uploaded_at ? $writtenNotice->senate_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($writtenNotice && $writtenNotice->senate_approved_at_dilg_po ? $writtenNotice->senate_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($writtenNotice && $writtenNotice->senate_approved_at_dilg_ro ? $writtenNotice->senate_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // FDP Document
                $isBatchApproved ? $batchMarker : $getDocLink($fdpDocument ? $fdpDocument->fdp_file_path : null, 'fdp'),
                $isBatchApproved ? $batchUploadDate : ($fdpDocument && $fdpDocument->fdp_uploaded_at ? $fdpDocument->fdp_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($fdpDocument && $fdpDocument->approved_at_dilg_po ? $fdpDocument->approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($fdpDocument && $fdpDocument->approved_at_dilg_ro ? $fdpDocument->approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
                
                // FDP Posting
                $isBatchApproved ? $batchMarker : $fdpPostingOutput,
                $isBatchApproved ? $batchUploadDate : ($fdpDocument && $fdpDocument->posting_uploaded_at ? $fdpDocument->posting_uploaded_at->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchPoDate : ($fdpDocument && $fdpDocument->posting_approved_at_dilg_po ? $fdpDocument->posting_approved_at_dilg_po->format('Y-m-d H:i:s') : '-'),
                $isBatchApproved ? $batchRoDate : ($fdpDocument && $fdpDocument->posting_approved_at_dilg_ro ? $fdpDocument->posting_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-'),
            ];
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "fund_utilization_report_{$timestamp}.{$format}";

        return $this->exportExcel($filename, $headers, $rows, $selectedQuarter);
    }

    private function buildFilteredReportsQuery(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $programs = $this->normalizeFilterValues($request->query('program', []), true);
        $fundingYears = $this->normalizeFilterValues($request->query('funding_year', []));
        $projectStatuses = $this->normalizeFilterValues($request->query('project_status', []), true);
        $provinces = $this->normalizeFilterValues($request->query('province', []), true);
        $cities = $this->normalizeFilterValues($request->query('city', []), true);
        $barangays = $this->normalizeFilterValues($request->query('barangay', []), true);

        if (empty($provinces)) {
            $cities = [];
        }

        if (empty($cities)) {
            $barangays = [];
        }

        $user = Auth::user();
        $userProvince = $user ? trim((string) $user->province) : '';
        $userProvinceLower = $user ? $user->normalizedProvince() : '';
        $userOfficeLower = $user ? $user->normalizedOffice() : '';
        $userOfficeComparableLower = $user ? $user->normalizedOfficeComparable() : '';
        $isLguScopedUser = $user ? $user->isLguScopedUser() : false;
        $isDilgUser = $user ? $user->isDilgUser() : false;
        $furValidationPriorityExpression = $this->buildFundUtilizationValidationPriorityExpression($user, 'tbfur.project_code');
        $lfpValidationPriorityExpression = $this->buildFundUtilizationValidationPriorityExpression($user, 'locally_funded_projects.subaybayan_project_code');
        $furProgramExpression = "TRIM(COALESCE(spp.program, locally_funded_projects.fund_source, tbfur.fund_source, ''))";
        $lfpProgramExpression = "TRIM(COALESCE(spp.program, locally_funded_projects.fund_source, ''))";
        $furFundSourceExpression = "TRIM(COALESCE(tbfur.fund_source, locally_funded_projects.fund_source, ''))";
        $lfpFundSourceExpression = "TRIM(COALESCE(locally_funded_projects.fund_source, ''))";
        $furProjectStatusExpression = "TRIM(COALESCE(tbfur.project_status, ''))";
        $lfpProjectStatusExpression = "'Ongoing'";
        $furProvinceExpression = "TRIM(COALESCE(tbfur.province, locally_funded_projects.province, spp.province, ''))";
        $lfpProvinceExpression = "TRIM(COALESCE(locally_funded_projects.province, spp.province, ''))";
        $furCityExpression = "TRIM(COALESCE(locally_funded_projects.city_municipality, spp.city_municipality, ''))";
        $lfpCityExpression = "TRIM(COALESCE(locally_funded_projects.city_municipality, spp.city_municipality, ''))";
        $furBarangayExpression = "TRIM(COALESCE(tbfur.barangay, locally_funded_projects.barangay, ''))";
        $lfpBarangayExpression = "TRIM(COALESCE(locally_funded_projects.barangay, ''))";

        // Build query for Fund Utilization Reports
        $furQuery = FundUtilizationReport::query()
            ->leftJoin('locally_funded_projects', 'locally_funded_projects.subaybayan_project_code', '=', 'tbfur.project_code')
            ->leftJoin('subay_project_profiles as spp', 'spp.project_code', '=', 'tbfur.project_code')
            ->select([
                'tbfur.project_code',
                'tbfur.project_title',
                'tbfur.province',
                'tbfur.implementing_unit',
                'tbfur.barangay',
                'tbfur.funding_year',
                'tbfur.fund_source',
                'tbfur.allocation',
                'tbfur.contract_amount',
                'tbfur.project_status',
                DB::raw("'fur' as source_type"),
                DB::raw('NULL as subaybayan_project_code'),
                DB::raw('COALESCE(locally_funded_projects.city_municipality, spp.city_municipality) as city_municipality'),
                DB::raw('COALESCE(spp.program, locally_funded_projects.fund_source, tbfur.fund_source) as program'),
                DB::raw('NULL as lgsf_allocation'),
                DB::raw('NULL as user_id'),
                DB::raw("{$furValidationPriorityExpression} as validation_priority"),
            ]);

        // Build query for Locally Funded Projects
        $lfpBaseQuery = LocallyFundedProject::query()
            ->leftJoin('tbfur', 'tbfur.project_code', '=', 'locally_funded_projects.subaybayan_project_code')
            ->leftJoin('subay_project_profiles as spp', 'spp.project_code', '=', 'locally_funded_projects.subaybayan_project_code');

        $lfpQuery = (clone $lfpBaseQuery)
            ->whereNull('tbfur.project_code')
            ->select([
                'locally_funded_projects.subaybayan_project_code as project_code',
                'locally_funded_projects.project_name as project_title',
                'locally_funded_projects.province',
                'locally_funded_projects.implementing_unit',
                'locally_funded_projects.barangay',
                'locally_funded_projects.funding_year',
                'locally_funded_projects.fund_source',
                'locally_funded_projects.lgsf_allocation as allocation',
                'locally_funded_projects.contract_amount',
                DB::raw("'Ongoing' as project_status"),
                DB::raw("'lfp' as source_type"),
                'locally_funded_projects.subaybayan_project_code',
                'locally_funded_projects.city_municipality',
                DB::raw('COALESCE(spp.program, locally_funded_projects.fund_source) as program'),
                'locally_funded_projects.lgsf_allocation',
                'locally_funded_projects.user_id',
                DB::raw("{$lfpValidationPriorityExpression} as validation_priority"),
            ]);
        $lfpFundingYearQuery = clone $lfpBaseQuery;

        $this->applyNonSglgifSourceScope(
            $furQuery,
            'COALESCE(locally_funded_projects.fund_source, tbfur.fund_source, spp.program)',
            'tbfur.project_code'
        );
        $this->applyNonSglgifSourceScope(
            $lfpQuery,
            'COALESCE(locally_funded_projects.fund_source, spp.program)',
            'locally_funded_projects.subaybayan_project_code'
        );
        $this->applyNonSglgifSourceScope(
            $lfpFundingYearQuery,
            'COALESCE(locally_funded_projects.fund_source, spp.program)',
            'locally_funded_projects.subaybayan_project_code'
        );

        // Apply user scoping
        if ($isLguScopedUser) {
            if ($userOfficeLower !== '') {
                if ($userProvinceLower !== '') {
                    $furQuery->whereRaw('LOWER(tbfur.province) = ?', [$userProvinceLower]);
                    $lfpQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
                    $lfpFundingYearQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
                }

                $officeNeedle = $userOfficeComparableLower !== '' ? $userOfficeComparableLower : $userOfficeLower;
                $furImplementingUnitComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(tbfur.implementing_unit, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $lfpOfficeComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(locally_funded_projects.office, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $lfpCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(locally_funded_projects.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $sppCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";

                $furQuery->where(function ($subQuery) use (
                    $userOfficeLower,
                    $officeNeedle,
                    $furImplementingUnitComparableExpression,
                    $lfpOfficeComparableExpression,
                    $lfpCityComparableExpression,
                    $sppCityComparableExpression
                ) {
                    $subQuery->whereRaw('LOWER(tbfur.implementing_unit) = ?', [$userOfficeLower])
                        ->orWhereRaw('LOWER(locally_funded_projects.office) = ?', [$userOfficeLower])
                        ->orWhereRaw('LOWER(locally_funded_projects.city_municipality) = ?', [$userOfficeLower])
                        ->orWhereRaw('LOWER(spp.city_municipality) = ?', [$userOfficeLower]);

                    if ($officeNeedle !== '') {
                        $subQuery->orWhereRaw("{$furImplementingUnitComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$lfpOfficeComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$lfpCityComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$sppCityComparableExpression} = ?", [$officeNeedle]);
                    }
                });

                $lfpQuery->where(function ($subQuery) use (
                    $userOfficeLower,
                    $officeNeedle,
                    $lfpOfficeComparableExpression,
                    $lfpCityComparableExpression
                ) {
                    $subQuery->whereRaw('LOWER(locally_funded_projects.office) = ?', [$userOfficeLower])
                        ->orWhereRaw('LOWER(locally_funded_projects.city_municipality) = ?', [$userOfficeLower]);

                    if ($officeNeedle !== '') {
                        $subQuery->orWhereRaw("{$lfpOfficeComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$lfpCityComparableExpression} = ?", [$officeNeedle]);
                    }
                });
                $lfpFundingYearQuery->where(function ($subQuery) use (
                    $userOfficeLower,
                    $officeNeedle,
                    $lfpOfficeComparableExpression,
                    $lfpCityComparableExpression
                ) {
                    $subQuery->whereRaw('LOWER(locally_funded_projects.office) = ?', [$userOfficeLower])
                        ->orWhereRaw('LOWER(locally_funded_projects.city_municipality) = ?', [$userOfficeLower]);

                    if ($officeNeedle !== '') {
                        $subQuery->orWhereRaw("{$lfpOfficeComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$lfpCityComparableExpression} = ?", [$officeNeedle]);
                    }
                });
            } elseif ($userProvinceLower !== '') {
                $furQuery->whereRaw('LOWER(tbfur.province) = ?', [$userProvinceLower]);
                $lfpQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
                $lfpFundingYearQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
            }
        } elseif ($isDilgUser && $userProvinceLower !== '' && $userProvinceLower !== 'regional office') {
            $furQuery->whereRaw('LOWER(tbfur.province) = ?', [$userProvinceLower]);
            $lfpQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
            $lfpFundingYearQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
        }

        $submissionYear = trim((string) $request->query('submission_year', ''));

        $normalizedFilters = [
            'search' => $search,
            'program' => $programs,
            'fund_source' => [],
            'funding_year' => $fundingYears,
            'project_status' => $projectStatuses,
            'province' => $provinces,
            'city' => $cities,
            'barangay' => $barangays,
            'submission_year' => $submissionYear,
        ];

        $activeFilters = [
            'program' => $this->normalizeFilterValues($request->query('program', [])),
            'fund_source' => [],
            'funding_year' => $this->normalizeFilterValues($request->query('funding_year', [])),
            'project_status' => $this->normalizeFilterValues($request->query('project_status', [])),
            'province' => $this->normalizeFilterValues($request->query('province', [])),
            'city' => $this->normalizeFilterValues($request->query('city', [])),
            'barangay' => $this->normalizeFilterValues($request->query('barangay', [])),
            'submission_year' => $submissionYear,
        ];

        $filterOptions = $this->buildFundUtilizationFilterOptions(
            clone $furQuery,
            clone $lfpQuery,
            clone $lfpFundingYearQuery,
            [
                'fur_program' => $furProgramExpression,
                'lfp_program' => $lfpProgramExpression,
                'fur_fund_source' => $furFundSourceExpression,
                'lfp_fund_source' => $lfpFundSourceExpression,
                'fur_project_status' => $furProjectStatusExpression,
                'lfp_project_status' => $lfpProjectStatusExpression,
                'fur_province' => $furProvinceExpression,
                'lfp_province' => $lfpProvinceExpression,
                'fur_city' => $furCityExpression,
                'lfp_city' => $lfpCityExpression,
                'fur_barangay' => $furBarangayExpression,
                'lfp_barangay' => $lfpBarangayExpression,
            ],
            $normalizedFilters,
            $activeFilters
        );

        $this->applyFundUtilizationFiltersToQueries(
            $furQuery,
            $lfpQuery,
            $normalizedFilters,
            [
                'fur_program' => $furProgramExpression,
                'lfp_program' => $lfpProgramExpression,
                'fur_fund_source' => $furFundSourceExpression,
                'lfp_fund_source' => $lfpFundSourceExpression,
                'fur_project_status' => $furProjectStatusExpression,
                'lfp_project_status' => $lfpProjectStatusExpression,
                'fur_province' => $furProvinceExpression,
                'lfp_province' => $lfpProvinceExpression,
                'fur_city' => $furCityExpression,
                'lfp_city' => $lfpCityExpression,
                'fur_barangay' => $furBarangayExpression,
                'lfp_barangay' => $lfpBarangayExpression,
            ]
        );

        // Union the queries
        $reportsQuery = $furQuery->union($lfpQuery);

        $filters = [
            'search' => $search,
            'program' => $activeFilters['program'],
            'fund_source' => $activeFilters['fund_source'],
            'funding_year' => $activeFilters['funding_year'],
            'project_status' => $activeFilters['project_status'],
            'province' => $activeFilters['province'],
            'city' => $activeFilters['city'],
            'barangay' => $activeFilters['barangay'],
            'submission_year' => $activeFilters['submission_year'],
        ];

        return [$reportsQuery, $filters, $filterOptions];
    }

    private function buildFundUtilizationFilterOptions($furQuery, $lfpQuery, $lfpFundingYearQuery, array $expressions, array $filters = [], array $activeFilters = []): array
    {
        [$programFurQuery, $programLfpQuery] = $this->buildFundUtilizationOptionQueries($furQuery, $lfpQuery, $filters, $expressions, ['program']);
        $programs = $programFurQuery
            ->selectRaw($expressions['fur_program'] . ' as program')
            ->whereRaw($expressions['fur_program'] . " <> ''")
            ->distinct()
            ->pluck('program')
            ->concat(
                $programLfpQuery
                    ->selectRaw($expressions['lfp_program'] . ' as program')
                    ->whereRaw($expressions['lfp_program'] . " <> ''")
                    ->distinct()
                    ->pluck('program')
            )
            ->concat(collect($activeFilters['program'] ?? []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        [$fundSourceFurQuery, $fundSourceLfpQuery] = $this->buildFundUtilizationOptionQueries($furQuery, $lfpQuery, $filters, $expressions, ['fund_source']);
        $fundSources = $fundSourceFurQuery
            ->selectRaw($expressions['fur_fund_source'] . ' as fund_source')
            ->whereRaw($expressions['fur_fund_source'] . " <> ''")
            ->distinct()
            ->pluck('fund_source')
            ->concat(
                $fundSourceLfpQuery
                    ->selectRaw($expressions['lfp_fund_source'] . ' as fund_source')
                    ->whereRaw($expressions['lfp_fund_source'] . " <> ''")
                    ->distinct()
                    ->pluck('fund_source')
            )
            ->concat(collect($activeFilters['fund_source'] ?? []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->reject(fn ($value) => $this->isSglgifFundSource($value))
            ->unique()
            ->sort()
            ->values();

        $fundingYearFurQuery = clone $furQuery;
        $fundingYearLfpQuery = clone $lfpFundingYearQuery;
        $this->applyFundUtilizationFiltersToQueries($fundingYearFurQuery, $fundingYearLfpQuery, $filters, $expressions, ['funding_year']);

        $fundingYears = $fundingYearFurQuery
            ->selectRaw("TRIM(COALESCE(spp.funding_year, tbfur.funding_year, locally_funded_projects.funding_year, '')) as funding_year")
            ->whereRaw("TRIM(COALESCE(spp.funding_year, tbfur.funding_year, locally_funded_projects.funding_year, '')) <> ''")
            ->distinct()
            ->pluck('funding_year')
            ->concat(
                $fundingYearLfpQuery
                    ->selectRaw("TRIM(COALESCE(locally_funded_projects.funding_year, '')) as funding_year")
                    ->whereRaw("TRIM(COALESCE(locally_funded_projects.funding_year, '')) <> ''")
                    ->distinct()
                    ->pluck('funding_year')
            )
            ->concat(collect($activeFilters['funding_year'] ?? []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sortByDesc(fn ($value) => (int) $value)
            ->values();

        [$statusFurQuery, $statusLfpQuery] = $this->buildFundUtilizationOptionQueries($furQuery, $lfpQuery, $filters, $expressions, ['project_status']);
        $projectStatuses = $statusFurQuery
            ->selectRaw($expressions['fur_project_status'] . ' as project_status')
            ->whereRaw($expressions['fur_project_status'] . " <> ''")
            ->distinct()
            ->pluck('project_status')
            ->concat(
                $statusLfpQuery
                    ->selectRaw($expressions['lfp_project_status'] . ' as project_status')
                    ->whereRaw($expressions['lfp_project_status'] . " <> ''")
                    ->distinct()
                    ->pluck('project_status')
            )
            ->concat(collect($activeFilters['project_status'] ?? []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        [$locationFurQuery, $locationLfpQuery] = $this->buildFundUtilizationOptionQueries($furQuery, $lfpQuery, $filters, $expressions, ['province', 'city', 'barangay']);
        $locations = $locationFurQuery
            ->selectRaw($expressions['fur_province'] . ' as province')
            ->selectRaw($expressions['fur_city'] . ' as city_municipality')
            ->selectRaw($expressions['fur_barangay'] . ' as barangay')
            ->whereRaw($expressions['fur_province'] . " <> ''")
            ->distinct()
            ->get()
            ->concat(
                $locationLfpQuery
                    ->selectRaw($expressions['lfp_province'] . ' as province')
                    ->selectRaw($expressions['lfp_city'] . ' as city_municipality')
                    ->selectRaw($expressions['lfp_barangay'] . ' as barangay')
                    ->whereRaw($expressions['lfp_province'] . " <> ''")
                    ->distinct()
                    ->get()
            )
            ->map(function ($row) {
                return [
                    'province' => trim((string) ($row->province ?? '')),
                    'city_municipality' => trim((string) ($row->city_municipality ?? '')),
                    'barangay' => trim((string) ($row->barangay ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['province'] !== '')
            ->unique(fn ($row) => $row['province'] . '|' . $row['city_municipality'] . '|' . $row['barangay'])
            ->values();

        $provinces = $locations
            ->pluck('province')
            ->concat(collect($activeFilters['province'] ?? []))
            ->map([ProjectLocationFilterHelper::class, 'normalizeLabel'])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $configuredProvinceMunicipalities = ProjectLocationFilterHelper::buildConfiguredProvinceCityMap($provinces->all());
        $fallbackProvinceMunicipalities = $locations
            ->filter(fn ($row) => $row['city_municipality'] !== '')
            ->groupBy('province')
            ->map(function ($rows) {
                return $rows->pluck('city_municipality')
                    ->map([ProjectLocationFilterHelper::class, 'normalizeLabel'])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            })
            ->toArray();

        $provinceMunicipalities = !empty(array_filter($configuredProvinceMunicipalities))
            ? $configuredProvinceMunicipalities
            : $fallbackProvinceMunicipalities;

        $cityBarangayMap = $locations
            ->filter(fn ($row) => $row['city_municipality'] !== '' && $row['barangay'] !== '')
            ->groupBy('city_municipality')
            ->map(function ($rows) {
                return $rows->pluck('barangay')
                    ->flatMap(fn ($value) => preg_split('/\r\n|\r|\n|,/u', (string) $value) ?: [])
                    ->map([ProjectLocationFilterHelper::class, 'normalizeLabel'])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            })
            ->toArray();

        $selectedBarangays = ProjectLocationFilterHelper::selectedMappedValues(
            $activeFilters['city'] ?? [],
            $cityBarangayMap
        )
            ->concat(collect($activeFilters['barangay'] ?? []))
            ->map([ProjectLocationFilterHelper::class, 'normalizeLabel'])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'programs' => $programs,
            'fund_sources' => $fundSources,
            'funding_years' => $fundingYears,
            'project_statuses' => $projectStatuses,
            'provinces' => $provinces,
            'barangays' => $selectedBarangays,
            'provinceMunicipalities' => $provinceMunicipalities,
            'cityBarangayMap' => $cityBarangayMap,
        ];
    }

    private function formatQuarteredValues($collection, string $field, callable $customFormatter = null): string
    {
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $items = $collection ? $collection->keyBy('quarter') : collect();
        $parts = [];

        foreach ($quarters as $quarter) {
            $item = $items->get($quarter);
            $value = $item ? ($item->$field ?? null) : null;

            if ($customFormatter) {
                $value = $customFormatter($item);
            } else {
                $value = $value ? $this->publicFileUrl($value) : '-';
            }

            $parts[] = $quarter . ': ' . ($value ?: '-');
        }

        return implode('; ', $parts);
    }

    private function hasFundUtilizationPendingPo(
        ?string $path,
        ?string $status,
        ?string $uploaderLevel,
        $poApprovedAt = null,
        $roApprovedAt = null
    ): bool
    {
        $currentValidator = $this->resolveFundUtilizationCurrentValidatorLevel(
            $uploaderLevel,
            $status,
            $poApprovedAt,
            $roApprovedAt
        );

        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'pending'
            && $currentValidator === 'provincial';
    }

    private function hasFundUtilizationPendingRo(
        ?string $path,
        ?string $status,
        ?string $uploaderLevel,
        $poApprovedAt = null,
        $roApprovedAt = null
    ): bool
    {
        $currentValidator = $this->resolveFundUtilizationCurrentValidatorLevel(
            $uploaderLevel,
            $status,
            $poApprovedAt,
            $roApprovedAt
        );

        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'pending'
            && $currentValidator === 'regional';
    }

    private function hasFundUtilizationReturned(?string $path, ?string $status): bool
    {
        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'returned';
    }

    private function hasFundUtilizationApproved(?string $path, ?string $status): bool
    {
        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'approved';
    }

    private function resolveFundUtilizationValidatorLevelForDisplay(array $document): string
    {
        $status = strtolower(trim((string) ($document['status'] ?? '')));
        if ($status === 'returned') {
            return 'lgu';
        }

        $roApprovedAt = $document['approved_at_dilg_ro'] ?? null;
        if (!empty($roApprovedAt)) {
            return 'regional';
        }

        $poApprovedAt = $document['approved_at_dilg_po'] ?? null;
        if (!empty($poApprovedAt)) {
            return 'provincial';
        }

        return $this->resolveFundUtilizationCurrentValidatorLevel(
            $document['uploader_level'] ?? null,
            $document['status'] ?? null,
            $poApprovedAt,
            $roApprovedAt
        );
    }

    private function resolveFundUtilizationValidatedAtLabel(array $document): string
    {
        $validatedAt = $this->resolveFundUtilizationValidatedAtValue($document);

        if (empty($validatedAt)) {
            return '—';
        }

        return Carbon::parse($validatedAt)
            ->setTimezone(config('app.timezone'))
            ->format('M d, Y h:i A');
    }

    private function resolveFundUtilizationValidatedAtValue(array $document)
    {
        $status = strtolower(trim((string) ($document['status'] ?? '')));
        if ($status === 'returned') {
            return $document['approved_at'] ?? null;
        }

        $validatorLevel = $this->resolveFundUtilizationValidatorLevelForDisplay($document);

        $validatedAt = $validatorLevel === 'regional'
            ? ($document['approved_at_dilg_ro'] ?? null)
            : ($validatorLevel === 'provincial' ? ($document['approved_at_dilg_po'] ?? null) : null);

        if (!empty($validatedAt)) {
            return $validatedAt;
        }

        if ($status === 'pending') {
            return $document['uploaded_at'] ?? null;
        }

        return null;
    }

    private function resolveFundUtilizationValidatedAtTimestamp(array $document): ?int
    {
        $validatedAt = $this->resolveFundUtilizationValidatedAtValue($document);

        if (empty($validatedAt)) {
            return null;
        }

        return Carbon::parse($validatedAt)->getTimestamp();
    }

    private function summarizeFundUtilizationListing(array $quarterDocuments, array $workflowMap = []): array
    {
        $summary = [
            'approval_status_label' => 'Awaiting Upload',
            'approval_status_text_color' => '#4b5563',
            'approval_status_background_color' => '#f3f4f6',
            'approval_status_border_color' => '#d1d5db',
            'approval_status_sort_timestamp' => null,
            'date_submitted_label' => '—',
            'date_submitted_sort_timestamp' => null,
            'validation_level_label' => '—',
            'validation_level_text_color' => '#4b5563',
            'validation_level_background_color' => '#f3f4f6',
            'validation_level_border_color' => '#d1d5db',
            'date_validated_label' => '—',
        ];

        $documents = collect();

        foreach ($quarterDocuments as $quarter => $documentGroup) {
            $movUpload = $documentGroup['mov'] ?? null;
            $batchDocument = $documentGroup['batch_document'] ?? null;
            $writtenNotice = $documentGroup['written_notice'] ?? null;
            $fdpDocument = $documentGroup['fdp'] ?? null;

            if ($movUpload && trim((string) ($movUpload->mov_file_path ?? '')) !== '') {
                $documents->push([
                    'type' => 'mov',
                    'quarter' => $quarter,
                    'path' => $movUpload->mov_file_path,
                    'status' => $movUpload->status ?? null,
                    'uploaded_at' => $movUpload->mov_uploaded_at ?? null,
                    'approved_at' => $movUpload->approved_at ?? null,
                    'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($movUpload, 'mov_encoder_id'),
                    'approved_at_dilg_po' => $movUpload->approved_at_dilg_po ?? null,
                    'approved_at_dilg_ro' => $movUpload->approved_at_dilg_ro ?? null,
                ]);
            }

            $batchDocumentPaths = $this->getBatchDocumentFilePaths($batchDocument);
            if ($batchDocument && !empty($batchDocumentPaths)) {
                $documents->push([
                    'type' => 'batch-document',
                    'quarter' => $quarter,
                    'path' => $batchDocumentPaths[0],
                    'status' => $batchDocument->status ?? null,
                    'uploaded_at' => $batchDocument->batch_document_uploaded_at ?? null,
                    'approved_at' => $batchDocument->approved_at ?? null,
                    'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($batchDocument, 'batch_document_encoder_id'),
                    'approved_at_dilg_po' => $batchDocument->approved_at_dilg_po ?? null,
                    'approved_at_dilg_ro' => $batchDocument->approved_at_dilg_ro ?? null,
                ]);
            }

            if ($writtenNotice) {
                foreach ([
                    ['type' => 'written-notice-dbm', 'quarter' => $quarter, 'path' => $writtenNotice->secretary_dbm_path ?? null, 'status' => $writtenNotice->dbm_status ?? null, 'uploaded_at' => $writtenNotice->dbm_uploaded_at ?? null, 'approved_at' => $writtenNotice->dbm_approved_at ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'dbm_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->dbm_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->dbm_approved_at_dilg_ro ?? null],
                    ['type' => 'written-notice-dilg', 'quarter' => $quarter, 'path' => $writtenNotice->secretary_dilg_path ?? null, 'status' => $writtenNotice->dilg_status ?? null, 'uploaded_at' => $writtenNotice->dilg_uploaded_at ?? null, 'approved_at' => $writtenNotice->dilg_approved_at ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'dilg_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->dilg_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->dilg_approved_at_dilg_ro ?? null],
                    ['type' => 'written-notice-speaker', 'quarter' => $quarter, 'path' => $writtenNotice->speaker_house_path ?? null, 'status' => $writtenNotice->speaker_status ?? null, 'uploaded_at' => $writtenNotice->speaker_uploaded_at ?? null, 'approved_at' => $writtenNotice->speaker_approved_at ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'speaker_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->speaker_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->speaker_approved_at_dilg_ro ?? null],
                    ['type' => 'written-notice-president', 'quarter' => $quarter, 'path' => $writtenNotice->president_senate_path ?? null, 'status' => $writtenNotice->president_status ?? null, 'uploaded_at' => $writtenNotice->president_uploaded_at ?? null, 'approved_at' => $writtenNotice->president_approved_at ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'president_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->president_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->president_approved_at_dilg_ro ?? null],
                    ['type' => 'written-notice-house', 'quarter' => $quarter, 'path' => $writtenNotice->house_committee_path ?? null, 'status' => $writtenNotice->house_status ?? null, 'uploaded_at' => $writtenNotice->house_uploaded_at ?? null, 'approved_at' => $writtenNotice->house_approved_at ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'house_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->house_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->house_approved_at_dilg_ro ?? null],
                    ['type' => 'written-notice-senate', 'quarter' => $quarter, 'path' => $writtenNotice->senate_committee_path ?? null, 'status' => $writtenNotice->senate_status ?? null, 'uploaded_at' => $writtenNotice->senate_uploaded_at ?? null, 'approved_at' => $writtenNotice->senate_approved_at ?? null, 'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'senate_encoder_id'), 'approved_at_dilg_po' => $writtenNotice->senate_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->senate_approved_at_dilg_ro ?? null],
                ] as $writtenNoticeDocument) {
                    if (trim((string) ($writtenNoticeDocument['path'] ?? '')) === '') {
                        continue;
                    }

                    $documents->push($writtenNoticeDocument);
                }
            }

            if ($fdpDocument && trim((string) ($fdpDocument->fdp_file_path ?? '')) !== '') {
                $documents->push([
                    'type' => 'fdp',
                    'quarter' => $quarter,
                    'path' => $fdpDocument->fdp_file_path,
                    'status' => $fdpDocument->fdp_status ?? null,
                    'uploaded_at' => $fdpDocument->fdp_uploaded_at ?? null,
                    'approved_at' => $fdpDocument->fdp_approved_at ?? null,
                    'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($fdpDocument, 'fdp_encoder_id'),
                    'approved_at_dilg_po' => $fdpDocument->approved_at_dilg_po ?? null,
                    'approved_at_dilg_ro' => $fdpDocument->approved_at_dilg_ro ?? null,
                ]);
            }

            if ($fdpDocument && trim((string) ($fdpDocument->posting_link ?? '')) !== '') {
                $documents->push([
                    'type' => 'posting-link',
                    'quarter' => $quarter,
                    'path' => $fdpDocument->posting_link,
                    'status' => $fdpDocument->posting_status ?? null,
                    'uploaded_at' => $fdpDocument->posting_uploaded_at ?? null,
                    'approved_at' => $fdpDocument->posting_approved_at ?? null,
                    'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($fdpDocument, 'posting_encoder_id'),
                    'approved_at_dilg_po' => $fdpDocument->posting_approved_at_dilg_po ?? null,
                    'approved_at_dilg_ro' => $fdpDocument->posting_approved_at_dilg_ro ?? null,
                ]);
            }
        }

        $selectedDocument = $documents
            ->sort(function (array $left, array $right) {
                $leftPriority = $this->resolveFundUtilizationListingPriority($left);
                $rightPriority = $this->resolveFundUtilizationListingPriority($right);
                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                $leftUploadedAt = $left['uploaded_at'] ? Carbon::parse($left['uploaded_at'])->getTimestamp() : 0;
                $rightUploadedAt = $right['uploaded_at'] ? Carbon::parse($right['uploaded_at'])->getTimestamp() : 0;

                return $rightUploadedAt <=> $leftUploadedAt;
            })
            ->first();

        if (!$selectedDocument) {
            return $summary;
        }

        if (!empty($selectedDocument['uploaded_at'])) {
            $summary['date_submitted_label'] = Carbon::parse($selectedDocument['uploaded_at'])
                ->setTimezone(config('app.timezone'))
                ->format('M d, Y h:i A');
            $summary['date_submitted_sort_timestamp'] = Carbon::parse($selectedDocument['uploaded_at'])->getTimestamp();
        }

        $summary['approval_status_sort_timestamp'] = $this->resolveFundUtilizationValidatedAtTimestamp($selectedDocument);

        if ($this->hasFundUtilizationReturned($selectedDocument['path'] ?? null, $selectedDocument['status'] ?? null)) {
            $type = $selectedDocument['type'] ?? '';
            $q = $selectedDocument['quarter'] ?? '';
            $key = $this->fundUtilizationWorkflowSubmissionKey($type, $q);
            $workflow = $workflowMap[$key] ?? null;
            $workflowStatus = $workflow ? trim((string) $workflow->status) : '';

            $returnedByText = 'Returned';
            $returnedLevelText = 'Returned to LGU';

            if ($workflowStatus === 'Returned by Regional Officer') {
                $returnedByText = 'Returned by DILG Regional Office';
                $returnedLevelText = 'Returned by DILG Regional Office';
            } elseif ($workflowStatus === 'Returned by Provincial Officer') {
                $returnedByText = 'Returned by DILG Provincial Office';
                $returnedLevelText = 'Returned by DILG Provincial Office';
            }

            $summary['approval_status_label'] = $returnedByText;
            $summary['approval_status_text_color'] = '#b91c1c';
            $summary['approval_status_background_color'] = '#fef2f2';
            $summary['approval_status_border_color'] = '#fca5a5';
            $summary['validation_level_label'] = $returnedLevelText;
            $summary['validation_level_text_color'] = '#b91c1c';
            $summary['validation_level_background_color'] = '#fef2f2';
            $summary['validation_level_border_color'] = '#fca5a5';
            $summary['date_validated_label'] = $this->resolveFundUtilizationValidatedAtLabel($selectedDocument);

            return $summary;
        }

        if ($this->hasFundUtilizationPendingRo(
            $selectedDocument['path'] ?? null,
            $selectedDocument['status'] ?? null,
            $selectedDocument['uploader_level'] ?? null,
            $selectedDocument['approved_at_dilg_po'] ?? null,
            $selectedDocument['approved_at_dilg_ro'] ?? null
        )) {
            $summary['approval_status_label'] = 'For DILG Regional Office Validation';
            $summary['approval_status_text_color'] = '#1d4ed8';
            $summary['approval_status_background_color'] = '#dbeafe';
            $summary['approval_status_border_color'] = '#60a5fa';
            $summary['validation_level_label'] = 'DILG Regional Office';
            $summary['validation_level_text_color'] = '#1d4ed8';
            $summary['validation_level_background_color'] = '#dbeafe';
            $summary['validation_level_border_color'] = '#60a5fa';
            $summary['date_validated_label'] = $this->resolveFundUtilizationValidatedAtLabel($selectedDocument);

            return $summary;
        }

        if ($this->hasFundUtilizationPendingPo(
            $selectedDocument['path'] ?? null,
            $selectedDocument['status'] ?? null,
            $selectedDocument['uploader_level'] ?? null,
            $selectedDocument['approved_at_dilg_po'] ?? null,
            $selectedDocument['approved_at_dilg_ro'] ?? null
        )) {
            $summary['approval_status_label'] = 'For DILG Provincial Office Validation';
            $summary['approval_status_text_color'] = '#1d4ed8';
            $summary['approval_status_background_color'] = '#eff6ff';
            $summary['approval_status_border_color'] = '#93c5fd';
            $summary['validation_level_label'] = 'DILG Provincial Office';
            $summary['validation_level_text_color'] = '#1d4ed8';
            $summary['validation_level_background_color'] = '#eff6ff';
            $summary['validation_level_border_color'] = '#93c5fd';
            $summary['date_validated_label'] = $this->resolveFundUtilizationValidatedAtLabel($selectedDocument);

            return $summary;
        }

        if ($this->hasFundUtilizationApproved($selectedDocument['path'] ?? null, $selectedDocument['status'] ?? null)) {
            $validatorLevel = $this->resolveFundUtilizationValidatorLevelForDisplay($selectedDocument);
            $summary['approval_status_label'] = 'Approved';
            $summary['approval_status_text_color'] = '#047857';
            $summary['approval_status_background_color'] = '#ecfdf5';
            $summary['approval_status_border_color'] = '#6ee7b7';
            $summary['validation_level_label'] = $this->fundUtilizationValidatorLabel($validatorLevel);
            $summary['validation_level_text_color'] = '#047857';
            $summary['validation_level_background_color'] = '#ecfdf5';
            $summary['validation_level_border_color'] = '#6ee7b7';
            $summary['date_validated_label'] = $this->resolveFundUtilizationValidatedAtLabel($selectedDocument);
        }

        return $summary;
    }

    private function resolveFundUtilizationListingPriority(array $document): int
    {
        if ($this->hasFundUtilizationReturned($document['path'] ?? null, $document['status'] ?? null)) {
            return 0;
        }

        if ($this->hasFundUtilizationPendingPo(
            $document['path'] ?? null,
            $document['status'] ?? null,
            $document['uploader_level'] ?? null,
            $document['approved_at_dilg_po'] ?? null,
            $document['approved_at_dilg_ro'] ?? null
        )) {
            return 1;
        }

        if ($this->hasFundUtilizationPendingRo(
            $document['path'] ?? null,
            $document['status'] ?? null,
            $document['uploader_level'] ?? null,
            $document['approved_at_dilg_po'] ?? null,
            $document['approved_at_dilg_ro'] ?? null
        )) {
            return 2;
        }

        if (!empty($document['path'])) {
            return 3;
        }

        return 4;
    }

    private function summarizeFundUtilizationValidation(array $quarterDocuments): array
    {
        $summary = [
            'po_count' => 0,
            'ro_count' => 0,
            'returned_count' => 0,
            'approved_count' => 0,
            'uploaded_count' => 0,
            'pending_total' => 0,
            'label' => 'No Upload',
            'detail' => 'No uploaded documents yet',
            'icon' => 'fa-minus-circle',
            'text_color' => '#4b5563',
            'background_color' => '#f3f4f6',
            'border_color' => '#d1d5db',
        ];

        foreach ($quarterDocuments as $documents) {
            $movUpload = $documents['mov'] ?? null;
            $batchDocument = $documents['batch_document'] ?? null;
            $writtenNotice = $documents['written_notice'] ?? null;
            $fdpDocument = $documents['fdp'] ?? null;

            if ($movUpload && trim((string) ($movUpload->mov_file_path ?? '')) !== '') {
                $summary['uploaded_count']++;
                $uploaderLevel = $this->resolveFundUtilizationUploaderLevelFromRecord($movUpload, 'mov_encoder_id');

                if ($this->hasFundUtilizationPendingPo($movUpload->mov_file_path, $movUpload->status ?? null, $uploaderLevel, $movUpload->approved_at_dilg_po ?? null, $movUpload->approved_at_dilg_ro ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($movUpload->mov_file_path, $movUpload->status ?? null, $uploaderLevel, $movUpload->approved_at_dilg_po ?? null, $movUpload->approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($movUpload->mov_file_path, $movUpload->status ?? null)) {
                    $summary['returned_count']++;
                } elseif ($this->hasFundUtilizationApproved($movUpload->mov_file_path, $movUpload->status ?? null)) {
                    $summary['approved_count']++;
                }
            }

            $batchDocumentPaths = $this->getBatchDocumentFilePaths($batchDocument);
            if ($batchDocument && !empty($batchDocumentPaths)) {
                $summary['uploaded_count']++;
                $uploaderLevel = $this->resolveFundUtilizationUploaderLevelFromRecord($batchDocument, 'batch_document_encoder_id');

                if ($this->hasFundUtilizationPendingPo($batchDocumentPaths[0], $batchDocument->status ?? null, $uploaderLevel, $batchDocument->approved_at_dilg_po ?? null, $batchDocument->approved_at_dilg_ro ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($batchDocumentPaths[0], $batchDocument->status ?? null, $uploaderLevel, $batchDocument->approved_at_dilg_po ?? null, $batchDocument->approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($batchDocumentPaths[0], $batchDocument->status ?? null)) {
                    $summary['returned_count']++;
                } elseif ($this->hasFundUtilizationApproved($batchDocumentPaths[0], $batchDocument->status ?? null)) {
                    $summary['approved_count']++;
                }
            }

            if ($writtenNotice) {
                $writtenNoticeDocuments = [
                    [
                        'path' => $writtenNotice->secretary_dbm_path ?? null,
                        'status' => $writtenNotice->dbm_status ?? null,
                        'po_timestamp' => $writtenNotice->dbm_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->dbm_approved_at_dilg_ro ?? null,
                        'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'dbm_encoder_id'),
                    ],
                    [
                        'path' => $writtenNotice->secretary_dilg_path ?? null,
                        'status' => $writtenNotice->dilg_status ?? null,
                        'po_timestamp' => $writtenNotice->dilg_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->dilg_approved_at_dilg_ro ?? null,
                        'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'dilg_encoder_id'),
                    ],
                    [
                        'path' => $writtenNotice->speaker_house_path ?? null,
                        'status' => $writtenNotice->speaker_status ?? null,
                        'po_timestamp' => $writtenNotice->speaker_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->speaker_approved_at_dilg_ro ?? null,
                        'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'speaker_encoder_id'),
                    ],
                    [
                        'path' => $writtenNotice->president_senate_path ?? null,
                        'status' => $writtenNotice->president_status ?? null,
                        'po_timestamp' => $writtenNotice->president_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->president_approved_at_dilg_ro ?? null,
                        'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'president_encoder_id'),
                    ],
                    [
                        'path' => $writtenNotice->house_committee_path ?? null,
                        'status' => $writtenNotice->house_status ?? null,
                        'po_timestamp' => $writtenNotice->house_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->house_approved_at_dilg_ro ?? null,
                        'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'house_encoder_id'),
                    ],
                    [
                        'path' => $writtenNotice->senate_committee_path ?? null,
                        'status' => $writtenNotice->senate_status ?? null,
                        'po_timestamp' => $writtenNotice->senate_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->senate_approved_at_dilg_ro ?? null,
                        'uploader_level' => $this->resolveFundUtilizationUploaderLevelFromRecord($writtenNotice, 'senate_encoder_id'),
                    ],
                ];

                foreach ($writtenNoticeDocuments as $document) {
                    if (trim((string) ($document['path'] ?? '')) === '') {
                        continue;
                    }

                    $summary['uploaded_count']++;

                    if ($this->hasFundUtilizationPendingPo($document['path'], $document['status'] ?? null, $document['uploader_level'] ?? null, $document['po_timestamp'] ?? null, $document['ro_timestamp'] ?? null)) {
                        $summary['po_count']++;
                    } elseif ($this->hasFundUtilizationPendingRo($document['path'], $document['status'] ?? null, $document['uploader_level'] ?? null, $document['po_timestamp'] ?? null, $document['ro_timestamp'] ?? null)) {
                        $summary['ro_count']++;
                    } elseif ($this->hasFundUtilizationReturned($document['path'], $document['status'] ?? null)) {
                        $summary['returned_count']++;
                    } elseif ($this->hasFundUtilizationApproved($document['path'], $document['status'] ?? null)) {
                        $summary['approved_count']++;
                    }
                }
            }

            if ($fdpDocument && trim((string) ($fdpDocument->fdp_file_path ?? '')) !== '') {
                $summary['uploaded_count']++;
                $uploaderLevel = $this->resolveFundUtilizationUploaderLevelFromRecord($fdpDocument, 'fdp_encoder_id');

                if ($this->hasFundUtilizationPendingPo($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null, $uploaderLevel, $fdpDocument->approved_at_dilg_po ?? null, $fdpDocument->approved_at_dilg_ro ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null, $uploaderLevel, $fdpDocument->approved_at_dilg_po ?? null, $fdpDocument->approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null)) {
                    $summary['returned_count']++;
                } elseif ($this->hasFundUtilizationApproved($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null)) {
                    $summary['approved_count']++;
                }
            }

            if ($fdpDocument && trim((string) ($fdpDocument->posting_link ?? '')) !== '') {
                $summary['uploaded_count']++;
                $uploaderLevel = $this->resolveFundUtilizationUploaderLevelFromRecord($fdpDocument, 'posting_encoder_id');

                if ($this->hasFundUtilizationPendingPo($fdpDocument->posting_link, $fdpDocument->posting_status ?? null, $uploaderLevel, $fdpDocument->posting_approved_at_dilg_po ?? null, $fdpDocument->posting_approved_at_dilg_ro ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($fdpDocument->posting_link, $fdpDocument->posting_status ?? null, $uploaderLevel, $fdpDocument->posting_approved_at_dilg_po ?? null, $fdpDocument->posting_approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($fdpDocument->posting_link, $fdpDocument->posting_status ?? null)) {
                    $summary['returned_count']++;
                } elseif ($this->hasFundUtilizationApproved($fdpDocument->posting_link, $fdpDocument->posting_status ?? null)) {
                    $summary['approved_count']++;
                }
            }
        }

        $summary['pending_total'] = $summary['po_count'] + $summary['ro_count'];

        if ($summary['returned_count'] > 0) {
            $summary['label'] = 'Returned';
            $summary['detail'] = $summary['returned_count'] . ' returned item' . ($summary['returned_count'] === 1 ? '' : 's');
            $summary['icon'] = 'fa-undo';
            $summary['text_color'] = '#b91c1c';
            $summary['background_color'] = '#fef2f2';
            $summary['border_color'] = '#fca5a5';

            return $summary;
        }

        if ($summary['pending_total'] > 0) {
            $summary['label'] = 'Pending Validation';
            $summary['icon'] = 'fa-clock';

            if ($summary['po_count'] > 0 && $summary['ro_count'] > 0) {
                $summary['detail'] = 'For PO: ' . $summary['po_count'] . ' | RO: ' . $summary['ro_count'];
            } elseif ($summary['po_count'] > 0) {
                $summary['detail'] = 'For PO: ' . $summary['po_count'];
            } else {
                $summary['detail'] = 'For RO: ' . $summary['ro_count'];
            }

            if ($summary['po_count'] > 0) {
                $summary['text_color'] = '#92400e';
                $summary['background_color'] = '#fffbeb';
                $summary['border_color'] = '#fcd34d';
            } else {
                $summary['text_color'] = '#1d4ed8';
                $summary['background_color'] = '#eff6ff';
                $summary['border_color'] = '#93c5fd';
            }

            return $summary;
        }

        if ($summary['approved_count'] > 0) {
            $summary['label'] = 'Validated';
            $summary['detail'] = $summary['approved_count'] . ' approved item' . ($summary['approved_count'] === 1 ? '' : 's');
            $summary['icon'] = 'fa-check-circle';
            $summary['text_color'] = '#047857';
            $summary['background_color'] = '#ecfdf5';
            $summary['border_color'] = '#6ee7b7';

            return $summary;
        }

        if ($summary['uploaded_count'] > 0) {
            $summary['label'] = 'Uploaded';
            $summary['detail'] = 'Awaiting validation updates';
            $summary['icon'] = 'fa-file-upload';
            $summary['text_color'] = '#1d4ed8';
            $summary['background_color'] = '#eff6ff';
            $summary['border_color'] = '#93c5fd';
        }

        return $summary;
    }

    private function publicFileUrl(string $path): string
    {
        return url(Storage::disk('public')->url($path));
    }

    private function generateTitle(string $selectedQuarter): string
    {
        $year = now()->year;
        $quarterNumber = str_replace('Q', '', $selectedQuarter ?: 'Q1');
        $quarterOrdinal = ['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'][$quarterNumber];
        return "STATUS ON THE SUBMISSION OF QUARTERLY FUND UTILIZATION REPORTS (FUR) FOR THE {$quarterOrdinal} QUARTER OF CY {$year} FOR LGSF PROJECTS";
    }



    private function exportExcel(string $filename, array $headers, array $rows, string $selectedQuarter)
    {
        $title = $this->generateTitle($selectedQuarter);
        $table = $this->buildHtmlTable($headers, $rows, false, true);
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0">';
        $html .= '<tr><td colspan="' . count($headers) . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $html .= '<tr><td colspan="' . count($headers) . '">&nbsp;</td></tr>';
        $html .= '</table>';
        $html .= $table;
        $html .= '</body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function exportPdf(string $filename, array $headers, array $rows, string $selectedQuarter)
    {
        $title = $this->generateTitle($selectedQuarter);
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('PDMU');
        $pdf->SetAuthor('PDMU');
        $pdf->SetTitle('Fund Utilization Report');
        $pdf->SetMargins(6, 8, 6);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 7);

        $html = '<h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3><br>' . $this->buildHtmlTable($headers, $rows, true);
        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildHtmlTable(array $headers, array $rows, bool $forPdf, bool $allowHtml = false): string
    {
        $borderStyle = $forPdf ? '1' : '1';
        $table = '<table border="' . $borderStyle . '" cellpadding="3" cellspacing="0">';
        $table .= '<thead><tr style="background-color:#f3f4f6;">';
        foreach ($headers as $header) {
            $table .= '<th style="font-weight:bold;">' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $table .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $table .= '<tr>';
            foreach ($row as $cell) {
                if ($allowHtml) {
                    $table .= '<td>' . $cell . '</td>';
                } else {
                    $table .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
                }
            }
            $table .= '</tr>';
        }

        $table .= '</tbody></table>';
        return $table;
    }

    private function formatExportUploadMarker(?string $value): string
    {
        return trim((string) $value) !== '' ? '✔' : '✘';
    }

    private function sanitizeReportPayload(array $validated): array
    {
        return InputSanitizer::sanitizeTextFields($validated, [
            'project_code',
            'province',
            'implementing_unit',
            'barangay',
            'fund_source',
            'project_status',
            'project_title',
        ]);
    }

    private function sanitizeReportRemarks(?string $remarks): ?string
    {
        return InputSanitizer::sanitizeNullablePlainText($remarks, true);
    }

    /**
     * Show the form for creating a new Fund Utilization Report.
     */
    public function create()
    {
        // Cordillera Administrative Region (CAR) provinces
        $provinces = [
            'Abra',
            'Apayao',
            'Benguet',
            'City of Baguio',
            'Ifugao',
            'Kalinga',
            'Mountain Province'
        ];
        
        // Province to municipalities/cities mapping
        $provinceMunicipalities = [
            'Abra' => ['Bangued', 'Boliney', 'Bucay', 'Daguioman', 'Danglas', 'Dolores', 'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Malibcong', 'Manabo', 'Peñarrubia', 'Pidcal', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan', 'San Quintin'],
            'Apayao' => ['Calanasan', 'Conner', 'Flora', 'Kabugao', 'Pudtol', 'Santa Marcela'],
            'Benguet' => ['Atok', 'Baguio City', 'Bakun', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'],
            'City of Baguio' => ['Baguio City'],
            'Ifugao' => ['Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan', 'Kiangan', 'Lagawe', 'Mayoyao', 'Tinoc'],
            'Kalinga' => ['Balbalan', 'Dagupagsan', 'Lubuagan', 'Mabunguran', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk City', 'Tanudan', 'Tinglayan'],
            'Mountain Province' => ['Amlang', 'Amtan', 'Bauko', 'Besao', 'Cervantes', 'Natonin', 'Paracelis', 'Sabangan', 'Sagada', 'Tadian']
        ];
        
        // Get current user's office
        $currentUserOffice = Auth::user()->office;
        
        // Fund source and funding year options
        $fundSources = $this->fundUtilizationFundSources();
        $fundingYears = [2025, 2024, 2023, 2022, 2021];
        
        return view('reports.fund-utilization.create', compact('provinces', 'provinceMunicipalities', 'currentUserOffice', 'fundSources', 'fundingYears'));
    }
    
    /**
     * Get municipalities for a selected province (API endpoint)
     */
    public function getMunicipalities($province)
    {
        $provinceMunicipalities = [
            'Abra' => ['Bangued', 'Boliney', 'Bucay', 'Daguioman', 'Danglas', 'Dolores', 'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Malibcong', 'Manabo', 'Peñarrubia', 'Pidcal', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan', 'San Quintin'],
            'Apayao' => ['Calanasan', 'Conner', 'Flora', 'Kabugao', 'Pudtol', 'Santa Marcela'],
            'Benguet' => ['Atok', 'Baguio City', 'Bakun', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'],
            'City of Baguio' => ['Baguio City'],
            'Ifugao' => ['Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan', 'Kiangan', 'Lagawe', 'Mayoyao', 'Tinoc'],
            'Kalinga' => ['Balbalan', 'Dagupagsan', 'Lubuagan', 'Mabunguran', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk City', 'Tanudan', 'Tinglayan'],
            'Mountain Province' => ['Amlang', 'Amtan', 'Bauko', 'Besao', 'Cervantes', 'Natonin', 'Paracelis', 'Sabangan', 'Sagada', 'Tadian']
        ];
        
        $municipalities = $provinceMunicipalities[$province] ?? [];
        
        return response()->json(['municipalities' => $municipalities]);
    }

    /**
     * Store a newly created Fund Utilization Report in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_code' => 'required|string|unique:tbfur',
            'province' => 'required|string',
            'implementing_unit' => 'required|string',
            'barangay' => 'required|string',
            'fund_source' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if ($this->isSglgifFundSource($value)) {
                        $fail('SGLGIF projects are excluded from Fund Utilization.');
                    }
                },
            ],
            'funding_year' => 'required|integer',
            'allocation' => 'required|numeric|min:0',
            'contract_amount' => 'required|numeric|min:0',
            'project_status' => 'required|string',
            'project_title' => 'required|string',
        ]);

        FundUtilizationReport::create($this->sanitizeReportPayload($validated));

        return redirect()->route('fund-utilization.index')
                        ->with('success', 'Fund Utilization Report created successfully.');
    }

    /**
     * Show the form for editing the specified Fund Utilization Report.
     */
    public function edit($projectCode)
    {
        $report = FundUtilizationReport::findOrFail($projectCode);
        $this->ensureFundUtilizationSourceAllowed($report->fund_source);

        // Check if user has permission to edit (only DILG users)
        $user = Auth::user();
        if (!$user || $user->agency !== 'DILG') {
            abort(403, 'Unauthorized');
        }

        // Cordillera Administrative Region (CAR) provinces
        $provinces = [
            'Abra',
            'Apayao',
            'Benguet',
            'City of Baguio',
            'Ifugao',
            'Kalinga',
            'Mountain Province'
        ];

        // Province to municipalities/cities mapping
        $provinceMunicipalities = [
            'Abra' => ['Bangued', 'Boliney', 'Bucay', 'Daguioman', 'Danglas', 'Dolores', 'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Malibcong', 'Manabo', 'Peñarrubia', 'Pidcal', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan', 'San Quintin'],
            'Apayao' => ['Calanasan', 'Conner', 'Flora', 'Kabugao', 'Pudtol', 'Santa Marcela'],
            'Benguet' => ['Atok', 'Baguio City', 'Bakun', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'],
            'City of Baguio' => ['Baguio City'],
            'Ifugao' => ['Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan', 'Kiangan', 'Lagawe', 'Mayoyao', 'Tinoc'],
            'Kalinga' => ['Balbalan', 'Dagupagsan', 'Lubuagan', 'Mabunguran', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk City', 'Tanudan', 'Tinglayan'],
            'Mountain Province' => ['Amlang', 'Amtan', 'Bauko', 'Besao', 'Cervantes', 'Natonin', 'Paracelis', 'Sabangan', 'Sagada', 'Tadian']
        ];

        // Fund source and funding year options
        $fundSources = $this->fundUtilizationFundSources();
        $fundingYears = [2025, 2024, 2023, 2022, 2021];
        $projectStatuses = ['Ongoing', 'Completed', 'Cancelled', 'On Hold'];

        return view('reports.fund-utilization.edit', compact('report', 'provinces', 'provinceMunicipalities', 'fundSources', 'fundingYears', 'projectStatuses'));
    }

    /**
     * Update the specified Fund Utilization Report in storage.
     */
    public function update(Request $request, $projectCode)
    {
        $report = FundUtilizationReport::findOrFail($projectCode);
        $this->ensureFundUtilizationSourceAllowed($report->fund_source);

        // Check if user has permission to update (only DILG users)
        $user = Auth::user();
        if (!$user || $user->agency !== 'DILG') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'project_code' => 'required|string|unique:tbfur,project_code,' . $projectCode . ',project_code',
            'province' => 'required|string',
            'implementing_unit' => 'required|string',
            'barangay' => 'required|string',
            'fund_source' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if ($this->isSglgifFundSource($value)) {
                        $fail('SGLGIF projects are excluded from Fund Utilization.');
                    }
                },
            ],
            'funding_year' => 'required|integer',
            'allocation' => 'required|numeric|min:0',
            'contract_amount' => 'required|numeric|min:0',
            'project_status' => 'required|string',
            'project_title' => 'required|string',
        ]);

        $report->update($this->sanitizeReportPayload($validated));

        return redirect()->route('fund-utilization.show', $report->project_code)
                        ->with('success', 'Fund Utilization Report updated successfully.');
    }

    /**
     * Display the specified Fund Utilization Report.
     */
    public function show($projectCode)
    {
        // First, try to find in FUR table
        $report = FundUtilizationReport::where('project_code', $projectCode)->first();
        
        // If not found in FUR, try to find in LocallyFundedProject by subaybayan_project_code
        if (!$report) {
            $lfpProject = LocallyFundedProject::where('subaybayan_project_code', $projectCode)->firstOrFail();
            $this->ensureFundUtilizationSourceAllowed($lfpProject->fund_source);
            
            // Create a temporary FUR-like object from LFP data for the view
            $report = new \stdClass();
            $report->project_code = $lfpProject->subaybayan_project_code;
            $report->project_title = $lfpProject->project_name;
            $report->province = $lfpProject->province;
            $report->implementing_unit = $lfpProject->implementing_unit;
            $report->barangay = $lfpProject->barangay;
            $report->funding_year = $lfpProject->funding_year;
            $report->fund_source = $lfpProject->fund_source;
            $report->allocation = $lfpProject->lgsf_allocation;
            $report->contract_amount = $lfpProject->contract_amount;
            $report->project_status = 'Ongoing';
            $report->is_lfp = true;
            $report->lfp_id = $lfpProject->id;
        } else {
            $this->ensureFundUtilizationSourceAllowed($report->fund_source);
            $report->is_lfp = false;
        }
        
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $deadlineReportingYear = $this->fundUtilizationDeadlineReportingYear();
        $configuredQuarterDeadlines = app(LguReportorialDeadlineResolver::class)->resolveMany(
            'fund_utilization_reports',
            $deadlineReportingYear,
            $quarters
        );
        
        $movUploads = [];
        $batchDocuments = [];
        $writtenNotices = [];
        $fdpDocuments = [];
        $adminRemarks = [];
        $accomplishmentPercentages = [];

        foreach ($quarters as $quarter) {
            if ($report->is_lfp ?? false) {
                // For LFP projects, retrieve FUR data by subaybayan_project_code + quarter
                $movUploads[$quarter] = FURMovUpload::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $batchDocuments[$quarter] = FURBatchDocument::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $writtenNotices[$quarter] = FURWrittenNotice::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $fdpDocuments[$quarter] = FURFDP::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $adminRemarks[$quarter] = FURAdminRemark::where('project_code', $projectCode)->where('quarter', $quarter)->first();
            } else {
                $movUploads[$quarter] = $report->movUploads()->where('quarter', $quarter)->first();
                $batchDocuments[$quarter] = $report->batchDocuments()->where('quarter', $quarter)->first();
                $writtenNotices[$quarter] = $report->writtenNotices()->where('quarter', $quarter)->first();
                $fdpDocuments[$quarter] = $report->fdpDocuments()->where('quarter', $quarter)->first();
                $adminRemarks[$quarter] = $report->adminRemarks()->where('quarter', $quarter)->first();
            }
            
            // Calculate accomplishment percentage for this quarter
            $accomplishmentPercentages[$quarter] = $this->calculateAccomplishmentPercentage($movUploads[$quarter], $writtenNotices[$quarter], $fdpDocuments[$quarter], $batchDocuments[$quarter]);
        }

        $activityLogs = $this->getFundUtilizationLogs($projectCode);
        $submissionWorkflows = $this->resolveFundUtilizationWorkflowMap($report->project_code);

        return view('reports.fund-utilization.show', compact(
            'report',
            'quarters',
            'movUploads',
            'batchDocuments',
            'writtenNotices',
            'fdpDocuments',
            'adminRemarks',
            'activityLogs',
            'submissionWorkflows',
            'accomplishmentPercentages',
            'deadlineReportingYear',
            'configuredQuarterDeadlines'
        ));
    }

    /**
     * Calculate accomplishment percentage for a quarter
     * Based on number of documents APPROVED out of total required documents
     * Only counts documents that have been approved (status = 'approved')
     */
    private function calculateAccomplishmentPercentage($movUpload, $writtenNotice, $fdpDocument, $batchDocument = null)
    {
        if ($batchDocument && !empty($batchDocument->approved_at_dilg_ro)) {
            return 100;
        }

        $totalDocuments = 10; // MOV + Batch Documents + 6 Written Notices + FDP + LGU Website = 10
        $approvedDocuments = 0;

        // Check MOV - must have approved status
        if ($movUpload && $movUpload->status === 'approved') {
            $approvedDocuments++;
        }

        // Check Batch Documents - must have approved status
        if ($batchDocument && $batchDocument->status === 'approved') {
            $approvedDocuments++;
        }

        // Check Written Notice documents - individual approval status for each
        if ($writtenNotice) {
            $statusFields = [
                'dbm_status',
                'dilg_status',
                'speaker_status',
                'president_status',
                'house_status',
                'senate_status'
            ];

            foreach ($statusFields as $statusField) {
                if ($writtenNotice->$statusField === 'approved') {
                    $approvedDocuments++;
                }
            }
        }

        // Check FDP - must have approved status
        if ($fdpDocument && $fdpDocument->fdp_status === 'approved') {
            $approvedDocuments++;
        }

        // Check LGU Website (Posting Link) - count if posting link is provided
        if ($fdpDocument && $fdpDocument->posting_link) {
            $approvedDocuments++;
        }

        $percentage = ($approvedDocuments / $totalDocuments) * 100;
        return round($percentage, 0); // Round to nearest integer
    }

    /**
     * Upload MOV file
     */
    public function uploadMOV(FundUtilizationMovUploadRequest $request, $projectCode, FundUtilizationWorkflowService $workflowService)
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        if (!$this->canUploadFundUtilizationDocuments($user)) {
            return back()->withErrors([
                'mov_file' => 'Only LGU User and DILG Provincial Office users can upload documents.',
            ]);
        }

        if ($request->hasFile('mov_file')) {
            $existingRecord = FURMovUpload::where('project_code', $projectCode)
                                         ->where('quarter', $request->quarter)
                                         ->first();
            $oldFilePath = $existingRecord?->mov_file_path;
            $file = $request->file('mov_file');
            $originalName = basename(trim((string) $file->getClientOriginalName()));
            $path = $file->storeAs('fur/mov/' . $projectCode, $originalName, 'public');

            try {
                $record = DB::transaction(function () use ($existingRecord, $path, $projectCode, $request, $report, $user, $workflowService) {
                    $secureTimestamp = SecureTimestampService::getUploadTimestamp();

                    $updates = [
                        'mov_file_path' => $path,
                        'updated_at' => $secureTimestamp,
                        'encoder_id' => auth()->id(),
                        'mov_uploaded_at' => $secureTimestamp,
                        'mov_encoder_id' => auth()->id(),
                        'status' => 'pending',
                    ];

                    if (!$existingRecord) {
                        $updates['created_at'] = $secureTimestamp;
                    }

                    $record = FURMovUpload::updateOrCreate(
                        ['project_code' => $projectCode, 'quarter' => $request->quarter],
                        $updates
                    );

                    $workflowService->submitOrResubmit($report, $request->quarter, 'mov', $record, $user);
                    SecureTimestampService::logUploadTimestamp('mov', $projectCode, $request->quarter, $secureTimestamp);

                    return $record;
                });
            } catch (\Throwable $exception) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }

                return back()->withErrors([
                    'mov_file' => $exception->getMessage(),
                ]);
            }

            if ($oldFilePath && $oldFilePath !== $record->mov_file_path && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }
        }

        return back()->with('success', 'MOV file uploaded successfully.');
    }

    /**
     * Upload Batch Documents file
     */
    public function uploadBatchDocument(FundUtilizationBatchDocumentUploadRequest $request, $projectCode, FundUtilizationWorkflowService $workflowService)
    {
        if (!$this->canUploadFundUtilizationDocuments(Auth::user())) {
            return back()->withErrors([
                'batch_document_file' => 'Only LGU User and DILG Provincial Office users can upload documents.',
            ]);
        }

        $files = array_values(array_filter((array) $request->file('batch_document_file', [])));
        if (!empty($files)) {
            $result = ['newFilePaths' => []];
            try {
                $result = $this->storeBatchDocumentsForProject(
                    $projectCode,
                    $request->quarter,
                    $files,
                    Auth::user(),
                    $workflowService
                );

                foreach (array_unique($result['oldFilePaths'] ?? []) as $oldFilePath) {
                    if ($oldFilePath && !in_array($oldFilePath, $result['newFilePaths'] ?? [], true) && Storage::disk('public')->exists($oldFilePath)) {
                        Storage::disk('public')->delete($oldFilePath);
                    }
                }
            } catch (\Throwable $exception) {
                foreach (array_unique($result['newFilePaths'] ?? []) as $newFilePath) {
                    if ($newFilePath && Storage::disk('public')->exists($newFilePath)) {
                        Storage::disk('public')->delete($newFilePath);
                    }
                }

                throw $exception;
            }
        }

        return back()->with('success', 'Batch Documents files uploaded successfully.');
    }

    public function uploadBatchDocumentsBulk(FundUtilizationBatchDocumentBulkUploadRequest $request, FundUtilizationWorkflowService $workflowService)
    {
        $validated = $request->validated();

        $projectCodes = collect($validated['project_codes'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($projectCodes->isEmpty()) {
            $message = 'Please select at least one project for batch upload.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['project_codes' => $message]);
        }

        $uploadedFiles = array_values(array_filter((array) $request->file('batch_document_files', [])));
        $user = Auth::user();
        if (!$this->canUploadFundUtilizationDocuments($user)) {
            $message = 'Only LGU User and DILG Provincial Office users can upload documents.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 403)
                : back()->withErrors(['batch_upload' => $message]);
        }
        $newFilePaths = [];
        $oldFilePaths = [];
        $processedCount = 0;

        try {
            foreach ($projectCodes as $projectCode) {
                $result = $this->storeBatchDocumentsForProject(
                    $projectCode,
                    $validated['quarter'],
                    $uploadedFiles,
                    $user,
                    $workflowService
                );

                $processedCount++;
                $newFilePaths = array_merge($newFilePaths, $result['newFilePaths'] ?? []);
                $oldFilePaths = array_merge($oldFilePaths, $result['oldFilePaths'] ?? []);
            }
        } catch (\Throwable $exception) {
            foreach (array_unique($newFilePaths) as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Fund utilization batch upload bulk failed.', [
                'quarter' => $validated['quarter'] ?? null,
                'project_codes' => $projectCodes->all(),
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            $message = 'Batch upload failed. Please try again.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 500)
                : back()->withErrors(['batch_upload' => $message]);
        }

        foreach (array_unique($oldFilePaths) as $path) {
            if ($path && !in_array($path, $newFilePaths, true) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $message = $processedCount === 1
            ? 'Batch document uploaded successfully for 1 project.'
            : "Batch document uploaded successfully for {$processedCount} projects.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'processed_count' => $processedCount])
            : back()->with('success', $message);
    }

    public function uploadIndividualDocumentsBulk(FundUtilizationIndividualDocumentBulkUploadRequest $request, FundUtilizationWorkflowService $workflowService)
    {
        $validated = $request->validated();

        $projectCodes = collect($validated['project_codes'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($projectCodes->isEmpty()) {
            $message = 'Please select at least one project for batch upload.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['project_codes' => $message]);
        }

        $user = Auth::user();
        if (!$this->canUploadFundUtilizationDocuments($user)) {
            $message = 'Only LGU User and DILG Provincial Office users can upload documents.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 403)
                : back()->withErrors(['individual_documents' => $message]);
        }

        $movFile = $request->file('mov_file');
        $writtenNoticeFiles = [];
        foreach (array_keys($this->writtenNoticeFieldConfigs()) as $field) {
            if ($request->hasFile($field)) {
                $writtenNoticeFiles[$field] = $request->file($field);
            }
        }
        $fdpFile = $request->file('fdp_file');
        $postingLinkInput = trim((string) ($validated['posting_link'] ?? ''));
        $postingLink = $postingLinkInput !== '' ? InputSanitizer::sanitizeHttpUrl($postingLinkInput) : null;

        if ($postingLinkInput !== '' && $postingLink === null) {
            $message = 'Please enter a valid http or https URL.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['posting_link' => $message]);
        }

        $newFilePaths = [];
        $oldFilePaths = [];
        $processedCount = 0;

        try {
            foreach ($projectCodes as $projectCode) {
                if ($movFile) {
                    $result = $this->storeMovForProject($projectCode, $validated['quarter'], $movFile, $user, $workflowService);
                    $newFilePaths = array_merge($newFilePaths, $result['newFilePaths'] ?? []);
                    $oldFilePaths = array_merge($oldFilePaths, $result['oldFilePaths'] ?? []);
                }

                if (!empty($writtenNoticeFiles)) {
                    $result = $this->storeWrittenNoticeFilesForProject($projectCode, $validated['quarter'], $writtenNoticeFiles, $user, $workflowService);
                    $newFilePaths = array_merge($newFilePaths, $result['newFilePaths'] ?? []);
                    $oldFilePaths = array_merge($oldFilePaths, $result['oldFilePaths'] ?? []);
                }

                if ($fdpFile) {
                    $result = $this->storeFdpForProject($projectCode, $validated['quarter'], $fdpFile, $user, $workflowService);
                    $newFilePaths = array_merge($newFilePaths, $result['newFilePaths'] ?? []);
                    $oldFilePaths = array_merge($oldFilePaths, $result['oldFilePaths'] ?? []);
                }

                if ($postingLink !== null) {
                    $this->storePostingLinkForProject($projectCode, $validated['quarter'], $postingLink, $user, $workflowService);
                }

                $processedCount++;
            }
        } catch (\Throwable $exception) {
            foreach (array_unique($newFilePaths) as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Fund utilization individual batch upload failed.', [
                'quarter' => $validated['quarter'] ?? null,
                'project_codes' => $projectCodes->all(),
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            $message = 'Individual document batch upload failed. Please try again.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 500)
                : back()->withErrors(['individual_documents' => $message]);
        }

        foreach (array_unique($oldFilePaths) as $path) {
            if ($path && !in_array($path, $newFilePaths, true) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $message = $processedCount === 1
            ? 'Individual documents uploaded successfully for 1 project.'
            : "Individual documents uploaded successfully for {$processedCount} projects.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'processed_count' => $processedCount])
            : back()->with('success', $message);
    }

    private function storeMovForProject(string $projectCode, string $quarter, $file, $user, FundUtilizationWorkflowService $workflowService): array
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $existingRecord = FURMovUpload::where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();
        $oldFilePath = $existingRecord?->mov_file_path;
        $originalName = basename(trim((string) $file->getClientOriginalName()));
        $path = $file->storeAs('fur/mov/' . $projectCode, $originalName, 'public');

        try {
            $record = DB::transaction(function () use ($existingRecord, $path, $projectCode, $quarter, $report, $user, $workflowService) {
                $secureTimestamp = SecureTimestampService::getUploadTimestamp();

                $updates = [
                    'mov_file_path' => $path,
                    'updated_at' => $secureTimestamp,
                    'encoder_id' => auth()->id(),
                    'mov_uploaded_at' => $secureTimestamp,
                    'mov_encoder_id' => auth()->id(),
                    'status' => 'pending',
                ];

                if (!$existingRecord) {
                    $updates['created_at'] = $secureTimestamp;
                }

                $record = FURMovUpload::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    $updates
                );

                $workflowService->submitOrResubmit($report, $quarter, 'mov', $record, $user);
                SecureTimestampService::logUploadTimestamp('mov', $projectCode, $quarter, $secureTimestamp);

                return $record;
            });
        } catch (\Throwable $exception) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return [
            'oldFilePaths' => array_values(array_filter([$oldFilePath])),
            'newFilePaths' => array_values(array_filter([$record->mov_file_path ?? $path])),
        ];
    }

    private function storeWrittenNoticeFilesForProject(string $projectCode, string $quarter, array $files, $user, FundUtilizationWorkflowService $workflowService): array
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $existingRecord = FURWrittenNotice::where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();
        $data = ['project_code' => $projectCode, 'quarter' => $quarter];
        $updates = [];
        $secureTimestamp = SecureTimestampService::getUploadTimestamp();
        $replacedPaths = [];
        $newPaths = [];
        $uploadedDocumentTypes = [];

        foreach ($this->writtenNoticeFieldConfigs() as $requestField => $fieldConfig) {
            if (!array_key_exists($requestField, $files) || !$files[$requestField]) {
                continue;
            }

            $oldPath = $existingRecord?->{$fieldConfig['path']};
            $file = $files[$requestField];
            $originalName = basename(trim((string) $file->getClientOriginalName()));
            $path = $file->storeAs('fur/written-notice/' . $projectCode, $originalName, 'public');
            $updates[$fieldConfig['path']] = $path;
            if ($requestField === 'speaker_house') {
                $updates['speaker_house_original_name'] = $originalName;
            }
            $replacedPaths[] = ['old' => $oldPath, 'new' => $path];
            $newPaths[] = $path;
            $uploadedDocumentTypes[] = $fieldConfig['workflow_type'];
            $updates[$fieldConfig['uploaded_at']] = $secureTimestamp;
            $updates[$fieldConfig['encoder_id']] = auth()->id();
            $updates[$fieldConfig['status']] = 'pending';
            $updates['status'] = 'pending';

            $shortFieldName = str_replace('secretary_', '', $requestField);
            SecureTimestampService::logUploadTimestamp('written-notice-' . $shortFieldName, $projectCode, $quarter, $secureTimestamp, [
                'file_path' => $path,
                'file_name' => basename($path),
            ]);
        }

        if (empty($updates)) {
            return [
                'oldFilePaths' => [],
                'newFilePaths' => [],
            ];
        }

        try {
            DB::transaction(function () use ($existingRecord, $data, $updates, $secureTimestamp, $report, $quarter, $uploadedDocumentTypes, $user, $workflowService) {
                if (!$existingRecord) {
                    $updates['created_at'] = $secureTimestamp;
                }

                $record = FURWrittenNotice::updateOrCreate($data, $updates);

                foreach (array_unique($uploadedDocumentTypes) as $documentType) {
                    $workflowService->submitOrResubmit($report, $quarter, $documentType, $record, $user);
                }
            });
        } catch (\Throwable $exception) {
            foreach (array_unique($newPaths) as $newPath) {
                if ($newPath && Storage::disk('public')->exists($newPath)) {
                    Storage::disk('public')->delete($newPath);
                }
            }

            throw $exception;
        }

        return [
            'oldFilePaths' => array_values(array_filter(array_map(fn ($entry) => $entry['old'] ?? null, $replacedPaths))),
            'newFilePaths' => array_values(array_filter($newPaths)),
        ];
    }

    private function storeFdpForProject(string $projectCode, string $quarter, $file, $user, FundUtilizationWorkflowService $workflowService): array
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $existingRecord = FURFDP::where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();
        $oldFilePath = $existingRecord?->fdp_file_path;
        $originalName = basename(trim((string) $file->getClientOriginalName()));
        $path = $file->storeAs('fur/fdp/' . $projectCode, $originalName, 'public');

        try {
            $record = DB::transaction(function () use ($existingRecord, $path, $projectCode, $quarter, $report, $user, $workflowService) {
                $secureTimestamp = SecureTimestampService::getUploadTimestamp();

                $updates = [
                    'fdp_file_path' => $path,
                    'updated_at' => $secureTimestamp,
                    'encoder_id' => auth()->id(),
                    'fdp_uploaded_at' => $secureTimestamp,
                    'fdp_encoder_id' => auth()->id(),
                    'fdp_status' => 'pending',
                    'status' => 'pending',
                ];

                if (!$existingRecord) {
                    $updates['created_at'] = $secureTimestamp;
                }

                $record = FURFDP::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    $updates
                );

                $workflowService->submitOrResubmit($report, $quarter, 'fdp', $record, $user);
                SecureTimestampService::logUploadTimestamp('fdp', $projectCode, $quarter, $secureTimestamp);

                return $record;
            });
        } catch (\Throwable $exception) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return [
            'oldFilePaths' => array_values(array_filter([$oldFilePath])),
            'newFilePaths' => array_values(array_filter([$record->fdp_file_path ?? $path])),
        ];
    }

    private function storePostingLinkForProject(string $projectCode, string $quarter, string $postingLink, $user, FundUtilizationWorkflowService $workflowService): void
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $secureTimestamp = SecureTimestampService::getUploadTimestamp();

        DB::transaction(function () use ($projectCode, $quarter, $postingLink, $secureTimestamp, $report, $user, $workflowService) {
            $record = FURFDP::updateOrCreate(
                ['project_code' => $projectCode, 'quarter' => $quarter],
                [
                    'posting_link' => $postingLink,
                    'posting_uploaded_at' => $secureTimestamp,
                    'posting_encoder_id' => auth()->id(),
                    'posting_status' => 'pending',
                ]
            );

            $workflowService->submitOrResubmit($report, $quarter, 'posting-link', $record, $user);
        });

        Log::channel('upload_timestamps')->info('Document uploaded', [
            'document_type' => 'posting-link',
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'upload_timestamp' => $secureTimestamp->format('Y-m-d H:i:s'),
            'timezone' => $secureTimestamp->timezone->getName(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);
    }

    private function storeBatchDocumentsForProject(string $projectCode, string $quarter, array $files, $user, FundUtilizationWorkflowService $workflowService): array
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $existingRecord = FURBatchDocument::where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();
        $oldFilePaths = $this->getBatchDocumentFilePaths($existingRecord);
        $newFilePaths = [];
        $storageDirectory = 'fur/batch-documents/' . $projectCode;

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $fileName = $this->buildBatchDocumentStoredFileName($file);
            $storedFileName = $fileName;
            $pathInfo = pathinfo($fileName);
            $fileBaseName = $pathInfo['filename'] ?? 'document';
            $fileExtension = $pathInfo['extension'] ?? '';
            $sequence = 1;

            while (Storage::disk('public')->exists($storageDirectory . '/' . $storedFileName)) {
                $suffix = '-' . $sequence;
                $storedFileName = $fileBaseName . $suffix . ($fileExtension !== '' ? '.' . $fileExtension : '');
                $sequence++;
            }

            $newFilePaths[] = $file->storeAs($storageDirectory, $storedFileName, 'public');
        }

        $record = DB::transaction(function () use ($existingRecord, $projectCode, $quarter, $newFilePaths, $report, $user, $workflowService) {
            $secureTimestamp = SecureTimestampService::getUploadTimestamp();

            $updates = [
                'updated_at' => $secureTimestamp,
                'encoder_id' => auth()->id(),
                'batch_document_uploaded_at' => $secureTimestamp,
                'batch_document_encoder_id' => auth()->id(),
                'status' => 'pending',
            ];

            if (!$existingRecord) {
                $updates['created_at'] = $secureTimestamp;
            }

            $updates = array_merge($updates, $this->buildBatchDocumentStoragePayload($newFilePaths));

            $record = FURBatchDocument::updateOrCreate(
                ['project_code' => $projectCode, 'quarter' => $quarter],
                $updates
            );

            $workflowService->submitOrResubmit($report, $quarter, 'batch-document', $record, $user);
            SecureTimestampService::logUploadTimestamp('batch-document', $projectCode, $quarter, $secureTimestamp);

            // Log each uploaded file separately so we can filter history per-file
            foreach ($newFilePaths as $fp) {
                \Log::channel('upload_timestamps')->info('Document uploaded', [
                    'document_type' => 'batch-document',
                    'project_code' => $projectCode,
                    'quarter' => $quarter,
                    'upload_timestamp' => $secureTimestamp->format('Y-m-d H:i:s'),
                    'file_path' => $fp,
                    'file_name' => basename($fp),
                    'timezone' => $secureTimestamp->timezone->getName(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'user_id' => auth()->id(),
                ]);
            }

            return $record;
        });

        return [
            'oldFilePaths' => $oldFilePaths,
            'newFilePaths' => $this->getBatchDocumentFilePaths($record),
        ];
    }

    private function writtenNoticeFieldConfigs(): array
    {
        return [
            'secretary_dbm' => [
                'workflow_type' => 'written-notice-dbm',
                'path' => 'secretary_dbm_path',
                'uploaded_at' => 'dbm_uploaded_at',
                'encoder_id' => 'dbm_encoder_id',
                'status' => 'dbm_status',
                'approved_by' => 'dbm_approved_by',
                'approved_at' => 'dbm_approved_at',
                'approved_at_dilg_po' => 'dbm_approved_at_dilg_po',
                'approved_at_dilg_ro' => 'dbm_approved_at_dilg_ro',
                'remarks' => 'dbm_remarks',
            ],
            'secretary_dilg' => [
                'workflow_type' => 'written-notice-dilg',
                'path' => 'secretary_dilg_path',
                'uploaded_at' => 'dilg_uploaded_at',
                'encoder_id' => 'dilg_encoder_id',
                'status' => 'dilg_status',
                'approved_by' => 'dilg_approved_by',
                'approved_at' => 'dilg_approved_at',
                'approved_at_dilg_po' => 'dilg_approved_at_dilg_po',
                'approved_at_dilg_ro' => 'dilg_approved_at_dilg_ro',
                'remarks' => 'dilg_remarks',
            ],
            'speaker_house' => [
                'workflow_type' => 'written-notice-speaker',
                'path' => 'speaker_house_path',
                'uploaded_at' => 'speaker_uploaded_at',
                'encoder_id' => 'speaker_encoder_id',
                'status' => 'speaker_status',
                'approved_by' => 'speaker_approved_by',
                'approved_at' => 'speaker_approved_at',
                'approved_at_dilg_po' => 'speaker_approved_at_dilg_po',
                'approved_at_dilg_ro' => 'speaker_approved_at_dilg_ro',
                'remarks' => 'speaker_remarks',
            ],
            'president_senate' => [
                'workflow_type' => 'written-notice-president',
                'path' => 'president_senate_path',
                'uploaded_at' => 'president_uploaded_at',
                'encoder_id' => 'president_encoder_id',
                'status' => 'president_status',
                'approved_by' => 'president_approved_by',
                'approved_at' => 'president_approved_at',
                'approved_at_dilg_po' => 'president_approved_at_dilg_po',
                'approved_at_dilg_ro' => 'president_approved_at_dilg_ro',
                'remarks' => 'president_remarks',
            ],
            'house_committee' => [
                'workflow_type' => 'written-notice-house',
                'path' => 'house_committee_path',
                'uploaded_at' => 'house_uploaded_at',
                'encoder_id' => 'house_encoder_id',
                'status' => 'house_status',
                'approved_by' => 'house_approved_by',
                'approved_at' => 'house_approved_at',
                'approved_at_dilg_po' => 'house_approved_at_dilg_po',
                'approved_at_dilg_ro' => 'house_approved_at_dilg_ro',
                'remarks' => 'house_remarks',
            ],
            'senate_committee' => [
                'workflow_type' => 'written-notice-senate',
                'path' => 'senate_committee_path',
                'uploaded_at' => 'senate_uploaded_at',
                'encoder_id' => 'senate_encoder_id',
                'status' => 'senate_status',
                'approved_by' => 'senate_approved_by',
                'approved_at' => 'senate_approved_at',
                'approved_at_dilg_po' => 'senate_approved_at_dilg_po',
                'approved_at_dilg_ro' => 'senate_approved_at_dilg_ro',
                'remarks' => 'senate_remarks',
            ],
        ];
    }

    /**
     * Upload Written Notice files
     */
    public function uploadWrittenNotice(FundUtilizationWrittenNoticeUploadRequest $request, $projectCode, FundUtilizationWorkflowService $workflowService)
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        if (!$this->canUploadFundUtilizationDocuments($user)) {
            return back()->withErrors([
                'written_notice' => 'Only LGU User and DILG Provincial Office users can upload documents.',
            ]);
        }
        $data = ['project_code' => $projectCode, 'quarter' => $request->quarter];
        $updates = [];

        // Get secure, tamper-proof timestamp from PAGASA server
        $secureTimestamp = SecureTimestampService::getUploadTimestamp();
        $existingRecord = FURWrittenNotice::where('project_code', $projectCode)
            ->where('quarter', $request->quarter)
            ->first();
        $replacedPaths = [];
        $newPaths = [];
        $uploadedDocumentTypes = [];

        $fields = $this->writtenNoticeFieldConfigs();

        foreach ($fields as $requestField => $fieldConfig) {
            if ($request->hasFile($requestField)) {
                $oldPath = $existingRecord?->{$fieldConfig['path']};
                $file = $request->file($requestField);
                $originalName = basename(trim((string) $file->getClientOriginalName()));
                $path = $file->storeAs('fur/written-notice/' . $projectCode, $originalName, 'public');
                $updates[$fieldConfig['path']] = $path;
                if ($requestField === 'speaker_house') {
                    $updates['speaker_house_original_name'] = $originalName;
                }
                $replacedPaths[] = ['old' => $oldPath, 'new' => $path];
                $newPaths[] = $path;
                $uploadedDocumentTypes[] = $fieldConfig['workflow_type'];
                // Set individual upload timestamp for this specific document
                $updates[$fieldConfig['uploaded_at']] = $secureTimestamp;
                $updates[$fieldConfig['encoder_id']] = auth()->id();
                // Reset workflow state when a returned file is resubmitted, but retain history.
                $updates[$fieldConfig['status']] = 'pending';
                // Also reset the shared workflow state without erasing history.
                $updates['status'] = 'pending';

                // Log the upload for audit trail
                $shortFieldName = str_replace('secretary_', '', $requestField);
                SecureTimestampService::logUploadTimestamp('written-notice-' . $shortFieldName, $projectCode, $request->quarter, $secureTimestamp);
            }
        }

        if (!empty($updates)) {
            try {
                $record = DB::transaction(function () use ($existingRecord, $data, $updates, $secureTimestamp, $report, $request, $uploadedDocumentTypes, $user, $workflowService) {
                    if (!$existingRecord) {
                        $updates['created_at'] = $secureTimestamp;
                    }

                    $record = FURWrittenNotice::updateOrCreate($data, $updates);

                    foreach (array_unique($uploadedDocumentTypes) as $documentType) {
                        $workflowService->submitOrResubmit($report, $request->quarter, $documentType, $record, $user);
                    }

                    return $record;
                });
            } catch (\Throwable $exception) {
                foreach (array_unique($newPaths) as $newPath) {
                    if ($newPath && Storage::disk('public')->exists($newPath)) {
                        Storage::disk('public')->delete($newPath);
                    }
                }

                return back()->withErrors([
                    'written_notice' => $exception->getMessage(),
                ]);
            }

            foreach ($replacedPaths as $replacedPath) {
                $oldPath = $replacedPath['old'] ?? null;
                $newPath = $replacedPath['new'] ?? null;

                if ($oldPath && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        return back()->with('success', 'Written Notice files uploaded successfully.');
    }

    /**
     * Upload FDP file
     */
    public function uploadFDP(FundUtilizationFdpUploadRequest $request, $projectCode, FundUtilizationWorkflowService $workflowService)
    {
        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        if (!$this->canUploadFundUtilizationDocuments($user)) {
            return back()->withErrors([
                'fdp_file' => 'Only LGU User and DILG Provincial Office users can upload documents.',
            ]);
        }

        if ($request->hasFile('fdp_file')) {
            $existingRecord = FURFDP::where('project_code', $projectCode)
                                    ->where('quarter', $request->quarter)
                                    ->first();
            $oldFilePath = $existingRecord?->fdp_file_path;
            $file = $request->file('fdp_file');
            $originalName = basename(trim((string) $file->getClientOriginalName()));
            $path = $file->storeAs('fur/fdp/' . $projectCode, $originalName, 'public');

            try {
                $record = DB::transaction(function () use ($existingRecord, $path, $projectCode, $request, $report, $user, $workflowService) {
                    $secureTimestamp = SecureTimestampService::getUploadTimestamp();

                    $updates = [
                        'fdp_file_path' => $path,
                        'updated_at' => $secureTimestamp,
                        'encoder_id' => auth()->id(),
                        'fdp_uploaded_at' => $secureTimestamp,
                        'fdp_encoder_id' => auth()->id(),
                        'fdp_status' => 'pending',
                        'status' => 'pending',
                    ];

                    if (!$existingRecord) {
                        $updates['created_at'] = $secureTimestamp;
                    }

                    $record = FURFDP::updateOrCreate(
                        ['project_code' => $projectCode, 'quarter' => $request->quarter],
                        $updates
                    );

                    $workflowService->submitOrResubmit($report, $request->quarter, 'fdp', $record, $user);
                    SecureTimestampService::logUploadTimestamp('fdp', $projectCode, $request->quarter, $secureTimestamp);

                    return $record;
                });
            } catch (\Throwable $exception) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }

                return back()->withErrors([
                    'fdp_file' => $exception->getMessage(),
                ]);
            }

            if ($oldFilePath && $oldFilePath !== $record->fdp_file_path && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }
        }

        return back()->with('success', 'FDP document uploaded successfully.');
    }

    /**
     * Save LGU posting link (website/social media).
     */
    public function savePostingLink(FundUtilizationPostingLinkRequest $request, $projectCode, FundUtilizationWorkflowService $workflowService)
    {
        $validated = $request->validated();

        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        if (!$this->canUploadFundUtilizationDocuments($user)) {
            return back()
                ->withInput()
                ->withErrors(['posting_link' => 'Only LGU User and DILG Provincial Office users can upload documents.']);
        }

        $secureTimestamp = SecureTimestampService::getUploadTimestamp();

        $postingLink = InputSanitizer::sanitizeHttpUrl($validated['posting_link']);
        if ($postingLink === null) {
            return back()
                ->withInput()
                ->withErrors(['posting_link' => 'Please enter a valid http or https URL.']);
        }

        try {
            DB::transaction(function () use ($projectCode, $validated, $secureTimestamp, $postingLink, $report, $user, $workflowService) {
                $record = FURFDP::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $validated['quarter']],
                    [
                        'posting_link' => $postingLink,
                        'posting_uploaded_at' => $secureTimestamp,
                        'posting_encoder_id' => auth()->id(),
                        'posting_status' => 'pending',
                    ]
                );

                $workflowService->submitOrResubmit($report, $validated['quarter'], 'posting-link', $record, $user);
            });
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['posting_link' => $exception->getMessage()]);
        }

        Log::channel('upload_timestamps')->info('Document uploaded', [
            'document_type' => 'posting-link',
            'project_code' => $projectCode,
            'quarter' => $validated['quarter'],
            'upload_timestamp' => $secureTimestamp->format('Y-m-d H:i:s'),
            'timezone' => $secureTimestamp->timezone->getName(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'LGU posting link saved successfully.');
    }

    /**
     * Approve or return upload with remarks
     */
    public function requestResubmission(Request $request, $projectCode, $uploadType, $quarter, FundUtilizationWorkflowService $workflowService)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'action' => ['required', 'in:request_resubmission'],
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $remarks = $this->sanitizeReportRemarks($validated['remarks'] ?? null);
        if ($remarks === null || trim((string) $remarks) === '') {
            return back()->withErrors(['remarks' => 'Remarks must contain plain text.']);
        }

        $report = $this->getReportOrLfpProject($projectCode);
        $record = $this->resolveFundUtilizationUploadRecord($uploadType, $projectCode, $quarter);

        if (!$record) {
            return back()->withErrors(['document' => 'The selected document record was not found.']);
        }

        try {
            $updatedWorkflow = $workflowService->requestResubmission($report, $quarter, $uploadType, $user, (string) $remarks);
        } catch (\Throwable $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        $documentLabel = $this->resolveFundUtilizationDocumentLabel($uploadType);

        Log::channel('upload_timestamps')->info('Document action', [
            'document_type' => $uploadType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => 'request_resubmission',
            'remarks' => $remarks,
            'action_timestamp' => pagasa_time()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $documentLabel . ' resubmission request submitted successfully.',
                'status' => $updatedWorkflow->status,
                'action' => 'request_resubmission',
            ]);
        }

        return back()->with('success', $documentLabel . ' resubmission request submitted successfully.');
    }

    public function decideResubmissionRequest(Request $request, $projectCode, $uploadType, $quarter, FundUtilizationWorkflowService $workflowService)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
        ]);

        $expectsJson = $request->expectsJson() || $request->ajax();
        $errorResponse = static function (string $message, int $status = 422) use ($expectsJson) {
            if ($expectsJson) {
                return response()->json(['message' => $message], $status);
            }

            return back()->withErrors(['document' => $message]);
        };

        $report = $this->getReportOrLfpProject($projectCode);
        $record = $this->resolveFundUtilizationUploadRecord($uploadType, $projectCode, $quarter);

        if (!$record) {
            return $errorResponse('The selected document record was not found.', 404);
        }

        try {
            if ($validated['decision'] === 'approve') {
                $deleteResponse = $this->deleteDocument($projectCode, $uploadType, $quarter, true);
                if (method_exists($deleteResponse, 'getStatusCode') && $deleteResponse->getStatusCode() >= 400) {
                    $payload = method_exists($deleteResponse, 'getContent') ? json_decode((string) $deleteResponse->getContent(), true) : null;
                    $message = is_array($payload) && !empty($payload['message']) ? (string) $payload['message'] : 'Unable to delete the document for resubmission.';

                    return $errorResponse($message);
                }
            }

            $updatedWorkflow = $workflowService->resolveResubmissionRequest($report, $quarter, $uploadType, $user, $validated['decision'] === 'approve');
        } catch (\Throwable $exception) {
            return $errorResponse($exception->getMessage());
        }

        $documentLabel = $this->resolveFundUtilizationDocumentLabel($uploadType);

        Log::channel('upload_timestamps')->info('Document action', [
            'document_type' => $uploadType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => 'resubmission_' . $validated['decision'],
            'action_timestamp' => pagasa_time()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        $message = $validated['decision'] === 'approve'
            ? $documentLabel . ' resubmission request approved and the document was deleted.'
            : $documentLabel . ' resubmission request rejected.';

        if ($expectsJson) {
            return response()->json([
                'message' => $message,
                'status' => $updatedWorkflow->status,
                'action' => 'resubmission_' . $validated['decision'],
            ]);
        }

        return back()->with('success', $message);
    }

    public function approveUpload(FundUtilizationApprovalActionRequest $request, $projectCode, $uploadType, $quarter, FundUtilizationWorkflowService $workflowService)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $action = $validated['action'];
        $remarks = $this->sanitizeReportRemarks($validated['remarks'] ?? null);
        $report = $this->getReportOrLfpProject($projectCode);
        $expectsJson = $request->expectsJson() || $request->ajax();
        $errorResponse = static function (string $message, int $status = 422) use ($expectsJson) {
            if ($expectsJson) {
                return response()->json([
                    'message' => $message,
                ], $status);
            }

            return back()->withErrors([
                'document' => $message,
            ]);
        };

        if ($action === 'return' && $remarks === null) {
            if ($expectsJson) {
                return response()->json([
                    'message' => 'Return remarks must contain plain text.',
                    'errors' => [
                        'remarks' => ['Return remarks must contain plain text.'],
                    ],
                ], 422);
            }

            return back()->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $documentLabelMap = [
            'mov' => 'MOV file',
            'batch-document' => 'Batch Documents file',
            'written-notice-dbm' => 'DBM document',
            'written-notice-dilg' => 'DILG document',
            'written-notice-speaker' => 'Speaker document',
            'written-notice-president' => 'President document',
            'written-notice-house' => 'House document',
            'written-notice-senate' => 'Senate document',
            'fdp' => 'FDP document',
            'posting-link' => 'Posting link',
        ];
        $documentLabel = $documentLabelMap[$uploadType] ?? 'Document';

        $recordQuery = match ($uploadType) {
            'mov' => FURMovUpload::query(),
            'batch-document' => FURBatchDocument::query(),
            'written-notice-dbm',
            'written-notice-dilg',
            'written-notice-speaker',
            'written-notice-president',
            'written-notice-house',
            'written-notice-senate' => FURWrittenNotice::query(),
            'fdp',
            'posting-link' => FURFDP::query(),
            default => null,
        };

        if ($recordQuery === null) {
            abort(404);
        }

        $record = $recordQuery
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();

        if (!$record) {
            return $errorResponse('The selected document record was not found.', 404);
        }

        $workflow = $workflowService->workflowFor($report->project_code, $quarter, $uploadType);
        if (!$workflow) {
            $encoderField = match ($uploadType) {
                'written-notice-dbm' => 'dbm_encoder_id',
                'written-notice-dilg' => 'dilg_encoder_id',
                'written-notice-speaker' => 'speaker_encoder_id',
                'written-notice-president' => 'president_encoder_id',
                'written-notice-house' => 'house_encoder_id',
                'written-notice-senate' => 'senate_encoder_id',
                default => 'encoder_id',
            };

            $uploaderId = $record->{$encoderField} ?? $record->encoder_id ?? null;
            $uploader = $uploaderId ? User::where('idno', $uploaderId)->first() : null;

            if ($uploader && (int) $uploader->getKey() !== (int) $user->getKey()) {
                $isProvincialUser = $uploader->isProvincialDilgAssignment();
                $uploaderRole = $isProvincialUser ? User::ROLE_PROVINCIAL : $uploader->normalizedRole();
                $isRegionalValidator = $this->isFundUtilizationRegionalValidator($user);

                $workflow = new FundUtilizationApprovalWorkflow();
                $workflow->project_code = $report->project_code;
                $workflow->quarter = $quarter;
                $workflow->document_type = $uploadType;
                $workflow->uploader_id = $uploader->getKey();
                $workflow->uploader_role = $uploaderRole;
                $workflow->revision_number = 1;
                $workflow->current_approval_level = $isRegionalValidator ? 2 : 1;
                $workflow->status = $isRegionalValidator ? 'Pending Level 2 Approval' : 'Pending Level 1 Approval';
                $workflow->current_approver_id = $user->getKey();
                $workflow->current_approver_role = $user->normalizedRole();
                $workflow->submitted_at = $record->created_at ?? now();
                $workflow->save();
            } else {
                try {
                    \Log::channel('upload_timestamps')->warning('Approve/return attempted but workflow missing', [
                        'project_code' => $report->project_code,
                        'quarter' => $quarter,
                        'upload_type' => $uploadType,
                        'user_id' => optional(Auth::user())->getKey(),
                    ]);
                } catch (\Throwable $e) {
                    // Ignore logging failures
                }

                return $errorResponse('No workflow record exists yet for this submission. Resubmit the document first.');
            }
        }

        if (!Gate::forUser($user)->allows('fund-utilization.validateWorkflow', $workflow)) {
            return $errorResponse('Only the currently assigned validator can perform this action.', 403);
        }

        try {
            $updatedWorkflow = $action === 'approve'
                ? $workflowService->approve($report, $quarter, $uploadType, $record, $user)
                : $workflowService->returnForRevision($report, $quarter, $uploadType, $record, $user, (string) $remarks);
        } catch (\Throwable $exception) {
            return $errorResponse($exception->getMessage());
        }

        if ($action === 'approve') {
            $message = $updatedWorkflow->status === 'Approved'
                ? $documentLabel . ' approved successfully.'
                : $documentLabel . ' approved and forwarded to the next validation level.';
        } else {
            $message = $documentLabel . ' returned for revision.';
        }

        Log::channel('upload_timestamps')->info('Document action', [
            'document_type' => $uploadType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => $action,
            'remarks' => $remarks,
            'file_path' => $record->speaker_house_path ?? null,
            'file_name' => $record->speaker_house_path ? basename((string) $record->speaker_house_path) : null,
            'action_timestamp' => pagasa_time()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        if ($expectsJson) {
            return response()->json([
                'message' => $message,
                'status' => $updatedWorkflow->status,
                'action' => $action,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Save user remarks for uploads
     */
    public function saveUserRemarks(Request $request, $projectCode, $uploadType, $quarter)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $remarks = $this->sanitizeReportRemarks($validated['remarks'] ?? null);

        switch ($uploadType) {
            case 'mov':
                FURMovUpload::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['user_remarks' => $remarks]
                );
                break;
            case 'batch-document':
                FURBatchDocument::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['user_remarks' => $remarks]
                );
                break;
            case 'written-notice':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['user_remarks' => $remarks]
                );
                break;
            case 'dbm-secretary':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['dbm_remarks' => $remarks]
                );
                break;
            case 'dilg-secretary':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['dilg_remarks' => $remarks]
                );
                break;
            case 'speaker-house':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['speaker_remarks' => $remarks]
                );
                break;
            case 'president-senate':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['president_remarks' => $remarks]
                );
                break;
            case 'house-committee':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['house_remarks' => $remarks]
                );
                break;
            case 'senate-committee':
                FURWrittenNotice::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['senate_remarks' => $remarks]
                );
                break;
            case 'fdp':
                FURFDP::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['user_remarks' => $remarks]
                );
                break;
            case 'posting-link':
                FURFDP::updateOrCreate(
                    ['project_code' => $projectCode, 'quarter' => $quarter],
                    ['user_remarks' => $remarks]
                );
                break;
        }

        Log::channel('upload_timestamps')->info('Document remarks saved', [
            'document_type' => $uploadType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => 'remarks',
            'remarks' => $remarks,
            'action_timestamp' => pagasa_time()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Remarks saved successfully.');
    }

    /**
     * View document
     */
    public function viewDocument($projectCode, $docType, $quarter)
    {
        $report = FundUtilizationReport::findOrFail($projectCode);
        $this->ensureFundUtilizationSourceAllowed($report->fund_source);
        
        $docTypeMap = [
            'mov' => ['table' => 'tbfur_mov_uploads', 'column' => 'mov_file_path'],
            'batch-document' => ['table' => 'tbfur_batch_documents', 'column' => 'batch_document_file_path'],
            'written-notice-dbm' => ['table' => 'tbfur_written_notice', 'column' => 'secretary_dbm_path'],
            'written-notice-dilg' => ['table' => 'tbfur_written_notice', 'column' => 'secretary_dilg_path'],
            'written-notice-speaker' => ['table' => 'tbfur_written_notice', 'column' => 'speaker_house_path'],
            'written-notice-president' => ['table' => 'tbfur_written_notice', 'column' => 'president_senate_path'],
            'written-notice-house' => ['table' => 'tbfur_written_notice', 'column' => 'house_committee_path'],
            'written-notice-senate' => ['table' => 'tbfur_written_notice', 'column' => 'senate_committee_path'],
            'fdp' => ['table' => 'tbfur_fdp', 'column' => 'fdp_file_path'],
        ];

        if (!isset($docTypeMap[$docType])) {
            abort(404);
        }

        $config = $docTypeMap[$docType];
        $table = $config['table'];
        $column = $config['column'];

        // Get the file path from database
        $upload = \DB::table($table)
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();

        if ($docType === 'batch-document') {
            $filePaths = $this->getBatchDocumentFilePaths($upload);
            $fileIndex = max(0, (int) request()->query('file', 0));
            $selectedFilePath = $filePaths[$fileIndex] ?? null;

            if (!$upload || !$selectedFilePath) {
                abort(404, 'Document not found');
            }

            $filePath = storage_path('app/public/' . $selectedFilePath);
        } else {
            if (!$upload || !$upload->$column) {
                abort(404, 'Document not found');
            }

            $filePath = storage_path('app/public/' . $upload->$column);
        }

        if (!file_exists($filePath)) {
            abort(404, 'File not found on disk');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        if (!in_array($extension, $inlineExtensions, true)) {
            return response()->download($filePath, basename($filePath), $headers);
        }

        return response()->file($filePath, $headers);
    }

    /**
     * Delete a document from storage and clear the database path
     */
    public function deleteDocument($projectCode, $docType, $quarter, bool $force = false)
    {
        $docTypeMap = [
            'mov' => ['table' => 'tbfur_mov_uploads', 'column' => 'mov_file_path', 'filePath' => 'mov_file_path', 'has_file' => true, 'statusField' => 'status', 'encoderField' => 'mov_encoder_id'],
            'batch-document' => ['table' => 'tbfur_batch_documents', 'column' => 'batch_document_file_path', 'filePath' => 'batch_document_file_path', 'has_file' => true, 'statusField' => 'status', 'encoderField' => 'batch_document_encoder_id'],
            'written-notice-dbm' => ['table' => 'tbfur_written_notice', 'column' => 'secretary_dbm_path', 'filePath' => 'secretary_dbm_path', 'has_file' => true, 'statusField' => 'dbm_status', 'encoderField' => 'dbm_encoder_id'],
            'written-notice-dilg' => ['table' => 'tbfur_written_notice', 'column' => 'secretary_dilg_path', 'filePath' => 'secretary_dilg_path', 'has_file' => true, 'statusField' => 'dilg_status', 'encoderField' => 'dilg_encoder_id'],
            'written-notice-speaker' => ['table' => 'tbfur_written_notice', 'column' => 'speaker_house_path', 'filePath' => 'speaker_house_path', 'has_file' => true, 'statusField' => 'speaker_status', 'encoderField' => 'speaker_encoder_id'],
            'written-notice-president' => ['table' => 'tbfur_written_notice', 'column' => 'president_senate_path', 'filePath' => 'president_senate_path', 'has_file' => true, 'statusField' => 'president_status', 'encoderField' => 'president_encoder_id'],
            'written-notice-house' => ['table' => 'tbfur_written_notice', 'column' => 'house_committee_path', 'filePath' => 'house_committee_path', 'has_file' => true, 'statusField' => 'house_status', 'encoderField' => 'house_encoder_id'],
            'written-notice-senate' => ['table' => 'tbfur_written_notice', 'column' => 'senate_committee_path', 'filePath' => 'senate_committee_path', 'has_file' => true, 'statusField' => 'senate_status', 'encoderField' => 'senate_encoder_id'],
            'fdp' => ['table' => 'tbfur_fdp', 'column' => 'fdp_file_path', 'filePath' => 'fdp_file_path', 'has_file' => true, 'statusField' => 'fdp_status', 'encoderField' => 'fdp_encoder_id'],
            'posting-link' => ['table' => 'tbfur_fdp', 'column' => 'posting_link', 'filePath' => null, 'has_file' => false, 'statusField' => 'posting_status', 'encoderField' => 'posting_encoder_id'],
        ];

        if (!isset($docTypeMap[$docType])) {
            return response()->json(['message' => 'Invalid document type'], 400);
        }

        $config = $docTypeMap[$docType];
        $table = $config['table'];
        $column = $config['column'];
        $hasFile = $config['has_file'];
        $statusField = $config['statusField'] ?? 'status';
        $encoderField = $config['encoderField'] ?? null;

        // Get the file path from database
        $upload = \DB::table($table)
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();

        $batchDocumentPaths = [];
        if ($docType === 'batch-document') {
            $batchDocumentPaths = $this->getBatchDocumentFilePaths($upload);
            if (!$upload || empty($batchDocumentPaths)) {
                return response()->json(['message' => 'Document not found'], 404);
            }
        } elseif (!$upload || !$upload->$column) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (!$force && !$this->canDeleteFundUtilizationDocument(auth()->user(), $upload, $projectCode, $docType, $quarter, $statusField, $encoderField)) {
            return response()->json(['message' => 'You are not allowed to delete this document.'], 403);
        }

        $storagePath = null;
        if ($docType === 'batch-document') {
            foreach ($batchDocumentPaths as $storagePath) {
                if ($storagePath && Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->delete($storagePath);
                }
            }
        } else {
            $storagePath = $upload->$column;
            if ($hasFile && $storagePath && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }
        }

        // Clear the path from database and reset related approval/remarks state
        $updateData = [$column => null];

        switch ($docType) {
            case 'mov':
                $updateData = array_merge($updateData, [
                    'status' => 'pending',
                    'mov_uploaded_at' => null,
                    'mov_encoder_id' => null,
                    'encoder_id' => null,
                    'updated_at' => null,
                ]);
                break;
            case 'batch-document':
                $updateData = array_merge($updateData, [
                    'batch_document_files_json' => null,
                    'status' => 'pending',
                    'batch_document_uploaded_at' => null,
                    'batch_document_encoder_id' => null,
                    'encoder_id' => null,
                    'updated_at' => null,
                ]);
                break;
            case 'written-notice-dbm':
                $updateData = array_merge($updateData, [
                    'dbm_status' => 'pending',
                    'dbm_uploaded_at' => null,
                    'dbm_encoder_id' => null,
                ]);
                break;
            case 'written-notice-dilg':
                $updateData = array_merge($updateData, [
                    'dilg_status' => 'pending',
                    'dilg_uploaded_at' => null,
                    'dilg_encoder_id' => null,
                ]);
                break;
            case 'written-notice-speaker':
                $updateData = array_merge($updateData, [
                    'speaker_status' => 'pending',
                    'speaker_uploaded_at' => null,
                    'speaker_encoder_id' => null,
                ]);
                break;
            case 'written-notice-president':
                $updateData = array_merge($updateData, [
                    'president_status' => 'pending',
                    'president_uploaded_at' => null,
                    'president_encoder_id' => null,
                ]);
                break;
            case 'written-notice-house':
                $updateData = array_merge($updateData, [
                    'house_status' => 'pending',
                    'house_uploaded_at' => null,
                    'house_encoder_id' => null,
                ]);
                break;
            case 'written-notice-senate':
                $updateData = array_merge($updateData, [
                    'senate_status' => 'pending',
                    'senate_uploaded_at' => null,
                    'senate_encoder_id' => null,
                ]);
                break;
            case 'fdp':
                $updateData = array_merge($updateData, [
                    'fdp_status' => 'pending',
                    'fdp_uploaded_at' => null,
                    'fdp_encoder_id' => null,
                    'status' => 'pending',
                    'encoder_id' => null,
                    'updated_at' => null,
                ]);
                break;
            case 'posting-link':
                $updateData = array_merge($updateData, [
                    'posting_status' => 'pending',
                    'posting_uploaded_at' => null,
                    'posting_encoder_id' => null,
                ]);
                break;
        }

        if (strpos($docType, 'written-notice-') === 0) {
            // Reset the shared written-notice workflow state without erasing history.
            $updateData = array_merge($updateData, [
                'status' => 'pending',
                'encoder_id' => null,
                'updated_at' => null,
            ]);
        }
        
        \DB::table($table)
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->update($updateData);

        $deletedDocumentLabel = $docType === 'batch-document'
            ? collect($batchDocumentPaths)
                ->map(fn ($path) => basename((string) $path))
                ->filter()
                ->implode(', ')
            : basename((string) $storagePath);

        // Preserve document deletion as part of the audit trail.
        \Log::channel('upload_timestamps')->info('Document deleted', [
            'document_type' => $docType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => 'delete',
            'deleted_at' => pagasa_time()->format('Y-m-d H:i:s'),
            'deleted_by' => auth()->id(),
            'user_id' => auth()->id(),
            'storage_path' => $docType === 'batch-document' ? $batchDocumentPaths : $storagePath,
            'remarks' => $deletedDocumentLabel !== '' ? 'Deleted: ' . $deletedDocumentLabel : 'Document deleted',
        ]);

        $workflow = \App\Models\FundUtilizationApprovalWorkflow::where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->where('document_type', $docType)
            ->first();
        if ($workflow) {
            \App\Models\ApprovalLog::create([
                'submission_id' => $workflow->id,
                'project_code' => $projectCode,
                'quarter' => $quarter,
                'document_type' => $docType,
                'approver_id' => auth()->id(),
                'uploader_id' => $workflow->uploader_id,
                'approval_level' => (int)$workflow->current_approval_level ?: 1,
                'action' => 'Deleted',
                'remarks' => $deletedDocumentLabel !== '' ? 'Deleted: ' . $deletedDocumentLabel : 'Document deleted',
                'created_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Document deleted successfully'], 200);
    }

    private function getFundUtilizationLogs(string $projectCode): array
    {
        $logFiles = glob(storage_path('logs/upload_timestamps-*.log')) ?: [];
        $singleLogFile = storage_path('logs/upload_timestamps.log');
        if (is_file($singleLogFile)) {
            $logFiles[] = $singleLogFile;
        }
        rsort($logFiles);

        $entries = [];
        foreach ($logFiles as $logFile) {
            $content = @file_get_contents($logFile);
            if (!$content) {
                continue;
            }

            // Split by log entries starting with [YYYY-MM-DD HH:MM:SS]
            $logEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\])/', $content, -1, PREG_SPLIT_NO_EMPTY);
            
            foreach ($logEntries as $logEntry) {
                $logEntry = trim($logEntry);
                if (empty($logEntry)) {
                    continue;
                }
                
                // Check if this entry is for the requested project
                if (strpos($logEntry, '"project_code":"'.$projectCode.'"') === false) {
                    continue;
                }

                $parsed = $this->parseUploadLogLine($logEntry);
                if ($parsed) {
                    // Only include logs for the current project of FUR.
                    // Exclude logs where 'module' is set to other sections (e.g. pre_implementation_documents, locally_funded).
                    if (isset($parsed['module']) && $parsed['module'] !== 'fund_utilization') {
                        continue;
                    }
                    // Exclude any viewing/reading actions
                    if (in_array(strtolower($parsed['action'] ?? ''), ['view', 'read', 'download', 'viewed', 'readed', 'downloaded'])) {
                        continue;
                    }
                    $entries[] = $parsed;
                }
            }
        }

        $workflowLogs = ApprovalLog::query()
            ->where('project_code', $projectCode)
            ->orderByDesc('created_at')
            ->get();

        foreach ($workflowLogs as $workflowLog) {
            $entries[] = [
                'timestamp' => $workflowLog->created_at?->copy()->setTimezone(config('app.timezone')) ?? now(),
                'message' => 'Workflow ' . $workflowLog->action,
                'action' => strtolower($workflowLog->action),
                'document_type' => $workflowLog->document_type,
                'quarter' => $workflowLog->quarter,
                'user_id' => $workflowLog->approver_id ?: $workflowLog->uploader_id,
                'remarks' => $workflowLog->remarks,
            ];
        }

        if (empty($entries)) {
            return [];
        }

        $userIds = collect($entries)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $users = User::whereIn('idno', $userIds)->get()->keyBy('idno');

        foreach ($entries as &$entry) {
            $user = $entry['user_id'] ? ($users[$entry['user_id']] ?? null) : null;
            $entry['user_name'] = $user ? trim($user->fname.' '.$user->lname) : 'Unknown';
            $entry['user_agency'] = $user ? $user->agency : null;
            $entry['user_position'] = $user ? $user->position : null;
        }
        unset($entry);

        usort($entries, function ($a, $b) {
            return $b['timestamp']->getTimestamp() <=> $a['timestamp']->getTimestamp();
        });

        return $entries;
    }

    private function parseUploadLogLine(string $line): ?array
    {
        // More flexible pattern that handles multiline JSON
        // Matches: [TIMESTAMP] CHANNEL.LEVEL: MESSAGE {JSON...}
        $pattern = '/^\[([^\]]+)\]\s+[^\:]+\.\w+:\s+([^{]+)\s*(\{.*)/';
        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $loggedAt = trim($matches[1]);
        $message = trim($matches[2]);
        $contextJson = $matches[3];
        
        // Try to parse the JSON
        $context = json_decode($contextJson, true);
        if (!is_array($context)) {
            return null;
        }

        $action = $context['action'] ?? null;
        if (!$action) {
            if (str_contains($message, 'Document uploaded')) {
                $action = 'upload';
            } elseif (str_contains($message, 'Document deleted')) {
                $action = 'delete';
            } elseif (str_contains($message, 'Document action')) {
                $action = $context['action'] ?? 'action';
            } elseif (str_contains($message, 'Document remarks saved')) {
                $action = 'remarks';
            } else {
                $action = 'update';
            }
        }

        $timestamp = $context['action_timestamp']
            ?? $context['upload_timestamp']
            ?? $context['deleted_at']
            ?? $loggedAt;

        return [
            'timestamp' => \Carbon\Carbon::parse($timestamp)->setTimezone(config('app.timezone')),
            'message' => $message,
            'action' => $action,
            'module' => $context['module'] ?? 'fund_utilization',
            'document_label' => $context['document_label'] ?? null,
            'action_label' => $context['action_label'] ?? null,
            'section' => $context['section'] ?? null,
            'field' => $context['field'] ?? null,
            'details' => $context['details'] ?? null,
            'document_type' => $context['document_type'] ?? null,
            'quarter' => $context['quarter'] ?? null,
            'user_id' => $context['user_id'] ?? $context['deleted_by'] ?? $context['approved_by'] ?? null,
            'remarks' => $context['remarks'] ?? null,
            'file_path' => $context['file_path'] ?? $context['storage_path'] ?? null,
            'file_name' => is_string($context['file_path'] ?? null) ? basename($context['file_path']) : (
                is_string($context['storage_path'] ?? null) ? basename($context['storage_path']) : null
            ),
            'storage_path' => $context['storage_path'] ?? null,
        ];
    }

    private function notifyDilgProvinceUsers(FundUtilizationReport $report, string $documentType, string $quarter): void
    {
        $user = Auth::user();
        if (!$user || $user->agency !== 'LGU') {
            return;
        }

        $targetProvince = trim((string) ($user->province ?? ''));
        if ($targetProvince === '') {
            $targetProvince = trim((string) $report->province);
        }
        if ($targetProvince === '') {
            return;
        }

        $dilgUsers = User::query()
            ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [strtolower($targetProvince)])
            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) <> ?', ['regional office'])
            ->where('status', 'active')
            ->get(['idno']);

        if ($dilgUsers->isEmpty()) {
            return;
        }

        $message = sprintf(
            '%s submitted %s for %s (%s) - %s',
            trim($user->fname . ' ' . $user->lname),
            strtoupper(str_replace('-', ' ', $documentType)),
            $report->project_code,
            $quarter,
            $targetProvince
        );

        $url = NotificationUrl::normalizeForStorage(
            trim((string) ($report->project_code ?? '')) !== ''
                ? route('fund-utilization.show', ['projectCode' => $report->project_code], false)
                : route('fund-utilization.index', [], false)
        );
        $now = now();

        $rows = $dilgUsers->map(function ($dilgUser) use ($message, $url, $documentType, $quarter, $now) {
            return [
                'user_id' => $dilgUser->idno,
                'message' => $message,
                'url' => $url,
                'document_type' => $documentType,
                'quarter' => $quarter,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('tbnotifications')->insert($rows);
    }

    private function notifyLguUsersAfterRegionalApproval(FundUtilizationReport $report, string $documentType, string $quarter): void
    {
        try {
            if (!Schema::hasTable('tbnotifications')) {
                return;
            }

            $user = Auth::user();
            if (!$user || strtoupper(trim((string) ($user->agency ?? ''))) !== 'DILG') {
                return;
            }

            $isRegionalOffice = strtolower(trim((string) ($user->province ?? ''))) === 'regional office'
                || str_contains(strtolower(trim((string) ($user->office ?? ''))), 'regional office');
            if (!$isRegionalOffice) {
                return;
            }

            $targetProvince = trim((string) ($report->province ?? ''));
            if ($targetProvince === '') {
                return;
            }

            $implementingUnit = trim((string) ($report->implementing_unit ?? ''));
            $candidateOfficeNames = collect([$implementingUnit])
                ->map(function ($value) {
                    return strtolower(trim((string) $value));
                })
                ->filter(function ($value) {
                    return $value !== '';
                })
                ->flatMap(function ($value) {
                    $withoutPrefix = trim((string) preg_replace('/^(municipality|city)\s+of\s+/i', '', $value));
                    return array_values(array_unique(array_filter([$value, $withoutPrefix])));
                })
                ->values()
                ->all();

            $provinceLguUsers = User::query()
                ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['LGU'])
                ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [strtolower($targetProvince)])
                ->where('status', 'active')
                ->get(['idno', 'office']);

            if ($provinceLguUsers->isEmpty()) {
                return;
            }

            $recipients = $provinceLguUsers;
            if (!empty($candidateOfficeNames)) {
                $filteredRecipients = $provinceLguUsers->filter(function ($lguUser) use ($candidateOfficeNames) {
                    $office = strtolower(trim((string) ($lguUser->office ?? '')));
                    $officeWithoutPrefix = trim((string) preg_replace('/^(municipality|city)\s+of\s+/i', '', $office));
                    return in_array($office, $candidateOfficeNames, true)
                        || in_array($officeWithoutPrefix, $candidateOfficeNames, true);
                })->values();

                // Fallback to province-wide LGU recipients if office name matching is unavailable.
                if ($filteredRecipients->isNotEmpty()) {
                    $recipients = $filteredRecipients;
                }
            }

            $actorName = trim((string) ($user->fname ?? '') . ' ' . (string) ($user->lname ?? ''));
            if ($actorName === '') {
                $actorName = 'DILG Regional Office';
            }

            $projectLabel = trim((string) ($report->project_code ?? ''));
            $projectTitle = trim((string) ($report->project_title ?? ''));
            if ($projectTitle !== '') {
                $projectLabel .= ' (' . $projectTitle . ')';
            }

            $message = sprintf(
                '%s approved %s for %s (%s) - %s.',
                $actorName,
                strtoupper(str_replace('-', ' ', $documentType)),
                $projectLabel,
                $quarter,
                $targetProvince
            );

            $now = now();
            $url = NotificationUrl::normalizeForStorage(
                trim((string) ($report->project_code ?? '')) !== ''
                    ? route('fund-utilization.show', ['projectCode' => $report->project_code], false)
                    : route('fund-utilization.index', [], false)
            );
            $actorId = (int) Auth::id();

            $rows = $recipients
                ->filter(function ($recipient) use ($actorId) {
                    return (int) ($recipient->idno ?? 0) !== $actorId;
                })
                ->map(function ($recipient) use ($message, $url, $documentType, $quarter, $now) {
                    return [
                        'user_id' => (int) $recipient->idno,
                        'message' => $message,
                        'url' => $url,
                        'document_type' => $documentType,
                        'quarter' => $quarter,
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->values()
                ->all();

            if (!empty($rows)) {
                DB::table('tbnotifications')->insert($rows);
            }
        } catch (\Throwable $error) {
            Log::warning('Failed to create LGU notifications after regional approval (FUR).', [
                'project_code' => $report->project_code ?? null,
                'document_type' => $documentType,
                'quarter' => $quarter,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function notifyDilgRegionalUsers(FundUtilizationReport $report, string $documentType, string $quarter): void
    {
        $user = Auth::user();
        $userProvinceLower = strtolower(trim((string) ($user->province ?? '')));
        if (
            !$user
            || $user->agency !== 'DILG'
            || $userProvinceLower === ''
            || $userProvinceLower === 'regional office'
        ) {
            return;
        }

        $regionalUsers = User::query()
            ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', ['regional office'])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(office, ""))) LIKE ?', ['%regional office%']);
            })
            ->where('status', 'active')
            ->get(['idno']);

        if ($regionalUsers->isEmpty()) {
            return;
        }

        $message = sprintf(
            '%s validated %s for %s (%s) - %s and elevated it for DILG Regional validation',
            trim($user->fname . ' ' . $user->lname),
            strtoupper(str_replace('-', ' ', $documentType)),
            $report->project_code,
            $quarter,
            $report->province
        );

        $url = NotificationUrl::normalizeForStorage(
            trim((string) ($report->project_code ?? '')) !== ''
                ? route('fund-utilization.show', ['projectCode' => $report->project_code], false)
                : route('fund-utilization.index', [], false)
        );
        $now = now();

        $rows = $regionalUsers->map(function ($regionalUser) use ($message, $url, $documentType, $quarter, $now) {
            return [
                'user_id' => $regionalUser->idno,
                'message' => $message,
                'url' => $url,
                'document_type' => $documentType,
                'quarter' => $quarter,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('tbnotifications')->insert($rows);
    }

    /**
     * Delete a project and all its associated data and logs
     */
    public function deleteProject(string $projectCode)
    {
        // Get the project
        $project = FundUtilizationReport::find($projectCode);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        if ($this->isSglgifFundSource($project->fund_source)) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Delete all uploaded files for this project
        $this->deleteProjectFiles($projectCode);

        // Delete all associated logs for this project
        $this->deleteProjectLogs($projectCode);

        // Delete the project (this will cascade delete related records due to foreign key constraints)
        $project->delete();

        return response()->json(['message' => 'Project and all associated logs deleted successfully'], 200);
    }

    /**
     * Delete all uploaded files associated with a specific project
     */
    private function deleteProjectFiles(string $projectCode): void
    {
        // Delete MOV files
        $movUploads = FURMovUpload::where('project_code', $projectCode)->get();
        foreach ($movUploads as $mov) {
            if ($mov->mov_file_path && Storage::exists($mov->mov_file_path)) {
                Storage::delete($mov->mov_file_path);
            }
        }

        $batchDocuments = FURBatchDocument::where('project_code', $projectCode)->get();
        foreach ($batchDocuments as $batchDocument) {
            foreach ($this->getBatchDocumentFilePaths($batchDocument) as $storagePath) {
                if ($storagePath && Storage::exists($storagePath)) {
                    Storage::delete($storagePath);
                }
            }
        }

        // Delete Written Notice files (all types)
        $writtenNotices = FURWrittenNotice::where('project_code', $projectCode)->get();
        foreach ($writtenNotices as $notice) {
            $pathFields = [
                'secretary_dbm_path',
                'secretary_dilg_path',
                'speaker_house_path',
                'president_senate_path',
                'house_committee_path',
                'senate_committee_path'
            ];
            
            foreach ($pathFields as $field) {
                if ($notice->$field && Storage::exists($notice->$field)) {
                    Storage::delete($notice->$field);
                }
            }
        }

        // Delete FDP files
        $fdpDocuments = FURFDP::where('project_code', $projectCode)->get();
        foreach ($fdpDocuments as $fdp) {
            if ($fdp->fdp_file_path && Storage::exists($fdp->fdp_file_path)) {
                Storage::delete($fdp->fdp_file_path);
            }
        }
    }

    /**
     * Delete all activity logs associated with a specific project
     */
    private function deleteProjectLogs(string $projectCode): void
    {
        $logFiles = glob(storage_path('logs/upload_timestamps-*.log')) ?: [];

        foreach ($logFiles as $logFile) {
            $content = @file_get_contents($logFile);
            if (!$content) {
                continue;
            }

            // Split by log entries starting with [YYYY-MM-DD HH:MM:SS]
            $logEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\])/', $content, -1, PREG_SPLIT_NO_EMPTY);
            
            $filteredEntries = [];
            foreach ($logEntries as $logEntry) {
                $logEntry = trim($logEntry);
                if (empty($logEntry)) {
                    continue;
                }
                
                // Keep entries that are NOT for this project
                if (strpos($logEntry, '"project_code":"'.$projectCode.'"') === false) {
                    $filteredEntries[] = $logEntry;
                }
            }

            // Write back the filtered content
            if (empty($filteredEntries)) {
                // If no entries remain, delete the file
                @unlink($logFile);
            } else {
                file_put_contents($logFile, implode("\n", $filteredEntries));
            }
        }
    }
}
