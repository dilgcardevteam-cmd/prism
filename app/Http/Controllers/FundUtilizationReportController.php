<?php

namespace App\Http\Controllers;

use App\Models\FundUtilizationReport;
use App\Models\LocallyFundedProject;
use App\Models\FURMovUpload;
use App\Models\FURWrittenNotice;
use App\Models\FURFDP;
use App\Models\FURAdminRemark;
use App\Support\LguReportorialDeadlineResolver;
use App\Support\InputSanitizer;
use App\Support\NotificationUrl;
use App\Models\User;
use App\Services\SecureTimestampService;
use Illuminate\Http\Request;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class FundUtilizationReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:fund_utilization_reports,view')->only(['index', 'edit', 'show', 'viewDocument']);
        $this->middleware('crud_permission:fund_utilization_reports,add')->only(['create', 'store', 'uploadMOV', 'uploadWrittenNotice', 'uploadFDP']);
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
        $provinces = $filters['province'] ?? [];
        $cities = $filters['city'] ?? [];

        if (!in_array('search', $exclude, true) && $search !== '') {
            $keyword = '%' . strtolower($search) . '%';

            $furQuery->where(function ($query) use ($keyword, $expressions) {
                $query->whereRaw('LOWER(tbfur.project_code) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(tbfur.project_title) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(tbfur.implementing_unit) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(tbfur.province) LIKE ?', [$keyword])
                    ->orWhereRaw("LOWER({$expressions['fur_city']}) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER({$expressions['fur_program']}) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER({$expressions['fur_fund_source']}) LIKE ?", [$keyword]);
            });

            $lfpQuery->where(function ($query) use ($keyword, $expressions) {
                $query->whereRaw('LOWER(locally_funded_projects.subaybayan_project_code) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(locally_funded_projects.project_name) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(locally_funded_projects.implementing_unit) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(locally_funded_projects.province) LIKE ?', [$keyword])
                    ->orWhereRaw("LOWER({$expressions['lfp_city']}) LIKE ?", [$keyword])
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

        if (!in_array('province', $exclude, true) && !empty($provinces)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_province']})"), $provinces);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_province']})"), $provinces);
        }

        if (!in_array('city', $exclude, true) && !empty($cities)) {
            $furQuery->whereIn(DB::raw("LOWER({$expressions['fur_city']})"), $cities);
            $lfpQuery->whereIn(DB::raw("LOWER({$expressions['lfp_city']})"), $cities);
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

    private function buildFundUtilizationPoPendingExistsExpression(string $projectCodeColumn): string
    {
        $writtenNoticeConditions = implode(' OR ', [
            "(TRIM(COALESCE(wn.secretary_dbm_path, '')) <> '' AND LOWER(COALESCE(wn.dbm_status, '')) = 'pending' AND wn.dbm_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.secretary_dilg_path, '')) <> '' AND LOWER(COALESCE(wn.dilg_status, '')) = 'pending' AND wn.dilg_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.speaker_house_path, '')) <> '' AND LOWER(COALESCE(wn.speaker_status, '')) = 'pending' AND wn.speaker_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.president_senate_path, '')) <> '' AND LOWER(COALESCE(wn.president_status, '')) = 'pending' AND wn.president_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.house_committee_path, '')) <> '' AND LOWER(COALESCE(wn.house_status, '')) = 'pending' AND wn.house_approved_at_dilg_po IS NULL)",
            "(TRIM(COALESCE(wn.senate_committee_path, '')) <> '' AND LOWER(COALESCE(wn.senate_status, '')) = 'pending' AND wn.senate_approved_at_dilg_po IS NULL)",
        ]);

        return '('
            . "EXISTS (SELECT 1 FROM tbfur_mov_uploads mu WHERE mu.project_code = {$projectCodeColumn} AND TRIM(COALESCE(mu.mov_file_path, '')) <> '' AND LOWER(COALESCE(mu.status, '')) = 'pending' AND mu.approved_at_dilg_po IS NULL)"
            . " OR EXISTS (SELECT 1 FROM tbfur_written_notice wn WHERE wn.project_code = {$projectCodeColumn} AND ({$writtenNoticeConditions}))"
            . " OR EXISTS (SELECT 1 FROM tbfur_fdp fdp WHERE fdp.project_code = {$projectCodeColumn} AND ("
                . "(TRIM(COALESCE(fdp.fdp_file_path, '')) <> '' AND LOWER(COALESCE(fdp.fdp_status, '')) = 'pending' AND fdp.approved_at_dilg_po IS NULL)"
                . " OR (TRIM(COALESCE(fdp.posting_link, '')) <> '' AND LOWER(COALESCE(fdp.posting_status, '')) = 'pending' AND fdp.posting_approved_at_dilg_po IS NULL)"
            . '))'
        . ')';
    }

    private function buildFundUtilizationRoPendingExistsExpression(string $projectCodeColumn): string
    {
        $writtenNoticeConditions = implode(' OR ', [
            "(TRIM(COALESCE(wn.secretary_dbm_path, '')) <> '' AND LOWER(COALESCE(wn.dbm_status, '')) = 'pending' AND wn.dbm_approved_at_dilg_po IS NOT NULL AND wn.dbm_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.secretary_dilg_path, '')) <> '' AND LOWER(COALESCE(wn.dilg_status, '')) = 'pending' AND wn.dilg_approved_at_dilg_po IS NOT NULL AND wn.dilg_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.speaker_house_path, '')) <> '' AND LOWER(COALESCE(wn.speaker_status, '')) = 'pending' AND wn.speaker_approved_at_dilg_po IS NOT NULL AND wn.speaker_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.president_senate_path, '')) <> '' AND LOWER(COALESCE(wn.president_status, '')) = 'pending' AND wn.president_approved_at_dilg_po IS NOT NULL AND wn.president_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.house_committee_path, '')) <> '' AND LOWER(COALESCE(wn.house_status, '')) = 'pending' AND wn.house_approved_at_dilg_po IS NOT NULL AND wn.house_approved_at_dilg_ro IS NULL)",
            "(TRIM(COALESCE(wn.senate_committee_path, '')) <> '' AND LOWER(COALESCE(wn.senate_status, '')) = 'pending' AND wn.senate_approved_at_dilg_po IS NOT NULL AND wn.senate_approved_at_dilg_ro IS NULL)",
        ]);

        return '('
            . "EXISTS (SELECT 1 FROM tbfur_mov_uploads mu WHERE mu.project_code = {$projectCodeColumn} AND TRIM(COALESCE(mu.mov_file_path, '')) <> '' AND LOWER(COALESCE(mu.status, '')) = 'pending' AND mu.approved_at_dilg_po IS NOT NULL AND mu.approved_at_dilg_ro IS NULL)"
            . " OR EXISTS (SELECT 1 FROM tbfur_written_notice wn WHERE wn.project_code = {$projectCodeColumn} AND ({$writtenNoticeConditions}))"
            . " OR EXISTS (SELECT 1 FROM tbfur_fdp fdp WHERE fdp.project_code = {$projectCodeColumn} AND ("
                . "(TRIM(COALESCE(fdp.fdp_file_path, '')) <> '' AND LOWER(COALESCE(fdp.fdp_status, '')) = 'pending' AND fdp.approved_at_dilg_po IS NOT NULL AND fdp.approved_at_dilg_ro IS NULL)"
                . " OR (TRIM(COALESCE(fdp.posting_link, '')) <> '' AND LOWER(COALESCE(fdp.posting_status, '')) = 'pending' AND fdp.posting_approved_at_dilg_po IS NOT NULL AND fdp.posting_approved_at_dilg_ro IS NULL)"
            . '))'
        . ')';
    }

    private function buildFundUtilizationValidationPriorityExpression(?User $user, string $projectCodeColumn): string
    {
        $poPendingExpression = $this->buildFundUtilizationPoPendingExistsExpression($projectCodeColumn);
        $roPendingExpression = $this->buildFundUtilizationRoPendingExistsExpression($projectCodeColumn);

        if ($this->isRegionalDilgUser($user)) {
            return "CASE WHEN {$roPendingExpression} THEN 0 WHEN {$poPendingExpression} THEN 1 ELSE 2 END";
        }

        if ($this->isProvincialDilgUser($user)) {
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

        $reports = $reportsQuery
            ->orderBy('validation_priority')
            ->orderByRaw("CASE WHEN project_status IS NULL OR TRIM(project_status) = '' THEN 1 ELSE 0 END")
            ->orderBy('project_status')
            ->orderByRaw('CAST(funding_year AS UNSIGNED) DESC')
            ->orderByRaw("CASE WHEN city_municipality IS NULL OR TRIM(city_municipality) = '' THEN 1 ELSE 0 END")
            ->orderBy('city_municipality')
            ->orderByRaw("CASE WHEN province IS NULL OR TRIM(province) = '' THEN 1 ELSE 0 END")
            ->orderBy('province')
            ->orderBy('project_code')
            ->paginate($perPage)
            ->withQueryString();

        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $reportsCollection = $reports->getCollection();
        $projectCodes = $reportsCollection
            ->pluck('project_code')
            ->filter(fn($code) => trim((string) $code) !== '')
            ->map(fn($code) => trim((string) $code))
            ->unique()
            ->values();

        $movUploadsByKey = collect();
        $writtenNoticesByKey = collect();
        $fdpDocumentsByKey = collect();

        if ($projectCodes->isNotEmpty()) {
            $movUploadsByKey = FURMovUpload::query()
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
        }

        $reports->setCollection($reportsCollection->map(function ($report) use ($quarters, $movUploadsByKey, $writtenNoticesByKey, $fdpDocumentsByKey) {
            $projectCode = trim((string) ($report->project_code ?? ''));
            $quarterDocuments = [];

            foreach ($quarters as $quarter) {
                $key = $projectCode . '|' . $quarter;
                $movUpload = $movUploadsByKey->get($key);
                $writtenNotice = $writtenNoticesByKey->get($key);
                $fdpDocument = $fdpDocumentsByKey->get($key);

                $report->{'quarter_' . strtolower($quarter) . '_percentage'} = $this->calculateAccomplishmentPercentage($movUpload, $writtenNotice, $fdpDocument);
                $quarterDocuments[$quarter] = [
                    'mov' => $movUpload,
                    'written_notice' => $writtenNotice,
                    'fdp' => $fdpDocument,
                ];
            }

            $report->validation_summary = $this->summarizeFundUtilizationValidation($quarterDocuments);
            $report->validation_listing = $this->summarizeFundUtilizationListing($quarterDocuments);

            return $report;
        }));

        return view('reports.fund-utilization.index', compact('reports', 'filters', 'filterOptions', 'perPage'));
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
            ->with(['movUploads', 'writtenNotices', 'fdpDocuments'])
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

        $useHtmlLinks = $format === 'xls';
        $rows = [];
        foreach ($reports as $report) {
            $quarter = $selectedQuarter ?: 'Q1'; // Default to Q1 if no quarter selected, but since filtered, it should have data

            $movUpload = $report->movUploads()->where('quarter', $quarter)->first();
            $writtenNotice = $report->writtenNotices()->where('quarter', $quarter)->first();
            $fdpDocument = $report->fdpDocuments()->where('quarter', $quarter)->first();

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
                $this->formatExportLink($movUpload ? $movUpload->mov_file_path : null, $useHtmlLinks),
                $movUpload && $movUpload->mov_uploaded_at ? $movUpload->mov_uploaded_at->format('Y-m-d H:i:s') : '-',
                $movUpload && $movUpload->approved_at_dilg_po ? $movUpload->approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $movUpload && $movUpload->approved_at_dilg_ro ? $movUpload->approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($writtenNotice ? $writtenNotice->secretary_dbm_path : null, $useHtmlLinks),
                $writtenNotice && $writtenNotice->dbm_uploaded_at ? $writtenNotice->dbm_uploaded_at->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->dbm_approved_at_dilg_po ? $writtenNotice->dbm_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->dbm_approved_at_dilg_ro ? $writtenNotice->dbm_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($writtenNotice ? $writtenNotice->speaker_house_path : null, $useHtmlLinks),
                $writtenNotice && $writtenNotice->speaker_uploaded_at ? $writtenNotice->speaker_uploaded_at->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->speaker_approved_at_dilg_po ? $writtenNotice->speaker_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->speaker_approved_at_dilg_ro ? $writtenNotice->speaker_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($writtenNotice ? $writtenNotice->house_committee_path : null, $useHtmlLinks),
                $writtenNotice && $writtenNotice->house_uploaded_at ? $writtenNotice->house_uploaded_at->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->house_approved_at_dilg_po ? $writtenNotice->house_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->house_approved_at_dilg_ro ? $writtenNotice->house_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($writtenNotice ? $writtenNotice->secretary_dilg_path : null, $useHtmlLinks),
                $writtenNotice && $writtenNotice->dilg_uploaded_at ? $writtenNotice->dilg_uploaded_at->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->dilg_approved_at_dilg_po ? $writtenNotice->dilg_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->dilg_approved_at_dilg_ro ? $writtenNotice->dilg_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($writtenNotice ? $writtenNotice->president_senate_path : null, $useHtmlLinks),
                $writtenNotice && $writtenNotice->president_uploaded_at ? $writtenNotice->president_uploaded_at->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->president_approved_at_dilg_po ? $writtenNotice->president_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->president_approved_at_dilg_ro ? $writtenNotice->president_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($writtenNotice ? $writtenNotice->senate_committee_path : null, $useHtmlLinks),
                $writtenNotice && $writtenNotice->senate_uploaded_at ? $writtenNotice->senate_uploaded_at->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->senate_approved_at_dilg_po ? $writtenNotice->senate_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $writtenNotice && $writtenNotice->senate_approved_at_dilg_ro ? $writtenNotice->senate_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $this->formatExportLink($fdpDocument ? $fdpDocument->fdp_file_path : null, $useHtmlLinks),
                $fdpDocument && $fdpDocument->fdp_uploaded_at ? $fdpDocument->fdp_uploaded_at->format('Y-m-d H:i:s') : '-',
                $fdpDocument && $fdpDocument->approved_at_dilg_po ? $fdpDocument->approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $fdpDocument && $fdpDocument->approved_at_dilg_ro ? $fdpDocument->approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
                $fdpDocument && $fdpDocument->posting_link
                    ? ($useHtmlLinks
                        ? $this->toHtmlLink($fdpDocument->posting_link)
                        : (InputSanitizer::sanitizeHttpUrl($fdpDocument->posting_link) ?? InputSanitizer::sanitizePlainText($fdpDocument->posting_link)))
                    : '-',
                $fdpDocument && $fdpDocument->posting_uploaded_at ? $fdpDocument->posting_uploaded_at->format('Y-m-d H:i:s') : '-',
                $fdpDocument && $fdpDocument->posting_approved_at_dilg_po ? $fdpDocument->posting_approved_at_dilg_po->format('Y-m-d H:i:s') : '-',
                $fdpDocument && $fdpDocument->posting_approved_at_dilg_ro ? $fdpDocument->posting_approved_at_dilg_ro->format('Y-m-d H:i:s') : '-',
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
        $fundSources = $this->normalizeFilterValues($request->query('fund_source', []), true);
        $fundingYears = $this->normalizeFilterValues($request->query('funding_year', []));
        $provinces = $this->normalizeFilterValues($request->query('province', []), true);
        $cities = $this->normalizeFilterValues($request->query('city', []), true);

        if (empty($provinces)) {
            $cities = [];
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
        $furProvinceExpression = "TRIM(COALESCE(tbfur.province, locally_funded_projects.province, spp.province, ''))";
        $lfpProvinceExpression = "TRIM(COALESCE(locally_funded_projects.province, spp.province, ''))";
        $furCityExpression = "TRIM(COALESCE(locally_funded_projects.city_municipality, spp.city_municipality, ''))";
        $lfpCityExpression = "TRIM(COALESCE(locally_funded_projects.city_municipality, spp.city_municipality, ''))";

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
        $lfpQuery = LocallyFundedProject::query()
            ->leftJoin('tbfur', 'tbfur.project_code', '=', 'locally_funded_projects.subaybayan_project_code')
            ->leftJoin('subay_project_profiles as spp', 'spp.project_code', '=', 'locally_funded_projects.subaybayan_project_code')
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

        // Apply user scoping
        if ($isLguScopedUser) {
            if ($userOfficeLower !== '') {
                if ($userProvinceLower !== '') {
                    $furQuery->whereRaw('LOWER(tbfur.province) = ?', [$userProvinceLower]);
                    $lfpQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
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
            } elseif ($userProvinceLower !== '') {
                $furQuery->whereRaw('LOWER(tbfur.province) = ?', [$userProvinceLower]);
                $lfpQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
            }
        } elseif ($isDilgUser && $userProvinceLower !== '' && $userProvinceLower !== 'regional office') {
            $furQuery->whereRaw('LOWER(tbfur.province) = ?', [$userProvinceLower]);
            $lfpQuery->whereRaw('LOWER(locally_funded_projects.province) = ?', [$userProvinceLower]);
        }

        $normalizedFilters = [
            'search' => $search,
            'program' => $programs,
            'fund_source' => $fundSources,
            'funding_year' => $fundingYears,
            'province' => $provinces,
            'city' => $cities,
        ];

        $activeFilters = [
            'program' => $this->normalizeFilterValues($request->query('program', [])),
            'fund_source' => $this->normalizeFilterValues($request->query('fund_source', [])),
            'funding_year' => $this->normalizeFilterValues($request->query('funding_year', [])),
            'province' => $this->normalizeFilterValues($request->query('province', [])),
            'city' => $this->normalizeFilterValues($request->query('city', [])),
        ];

        $filterOptions = $this->buildFundUtilizationFilterOptions(
            clone $furQuery,
            clone $lfpQuery,
            [
                'fur_program' => $furProgramExpression,
                'lfp_program' => $lfpProgramExpression,
                'fur_fund_source' => $furFundSourceExpression,
                'lfp_fund_source' => $lfpFundSourceExpression,
                'fur_province' => $furProvinceExpression,
                'lfp_province' => $lfpProvinceExpression,
                'fur_city' => $furCityExpression,
                'lfp_city' => $lfpCityExpression,
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
                'fur_province' => $furProvinceExpression,
                'lfp_province' => $lfpProvinceExpression,
                'fur_city' => $furCityExpression,
                'lfp_city' => $lfpCityExpression,
            ]
        );

        // Union the queries
        $reportsQuery = $furQuery->union($lfpQuery);

        $filters = [
            'search' => $search,
            'program' => $activeFilters['program'],
            'fund_source' => $activeFilters['fund_source'],
            'funding_year' => $activeFilters['funding_year'],
            'province' => $activeFilters['province'],
            'city' => $activeFilters['city'],
        ];

        return [$reportsQuery, $filters, $filterOptions];
    }

    private function buildFundUtilizationFilterOptions($furQuery, $lfpQuery, array $expressions, array $filters = [], array $activeFilters = []): array
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

        [$fundingYearFurQuery, $fundingYearLfpQuery] = $this->buildFundUtilizationOptionQueries($furQuery, $lfpQuery, $filters, $expressions, ['funding_year']);
        $fundingYears = $fundingYearFurQuery
            ->select('tbfur.funding_year')
            ->whereNotNull('tbfur.funding_year')
            ->distinct()
            ->pluck('funding_year')
            ->concat(
                $fundingYearLfpQuery
                    ->select('locally_funded_projects.funding_year')
                    ->whereNotNull('locally_funded_projects.funding_year')
                    ->distinct()
                    ->pluck('funding_year')
            )
            ->concat(collect($activeFilters['funding_year'] ?? []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sortByDesc(fn ($value) => (int) $value)
            ->values();

        [$locationFurQuery, $locationLfpQuery] = $this->buildFundUtilizationOptionQueries($furQuery, $lfpQuery, $filters, $expressions, ['province', 'city']);
        $locations = $locationFurQuery
            ->selectRaw($expressions['fur_province'] . ' as province')
            ->selectRaw($expressions['fur_city'] . ' as city_municipality')
            ->whereRaw($expressions['fur_province'] . " <> ''")
            ->distinct()
            ->get()
            ->concat(
                $locationLfpQuery
                    ->selectRaw($expressions['lfp_province'] . ' as province')
                    ->selectRaw($expressions['lfp_city'] . ' as city_municipality')
                    ->whereRaw($expressions['lfp_province'] . " <> ''")
                    ->distinct()
                    ->get()
            )
            ->map(function ($row) {
                return [
                    'province' => trim((string) ($row->province ?? '')),
                    'city_municipality' => trim((string) ($row->city_municipality ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['province'] !== '')
            ->unique(fn ($row) => $row['province'] . '|' . $row['city_municipality'])
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

        return [
            'programs' => $programs,
            'fund_sources' => $fundSources,
            'funding_years' => $fundingYears,
            'provinces' => $provinces,
            'provinceMunicipalities' => $provinceMunicipalities,
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

    private function hasFundUtilizationPendingPo(?string $path, ?string $status, $poApprovedAt): bool
    {
        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'pending'
            && empty($poApprovedAt);
    }

    private function hasFundUtilizationPendingRo(?string $path, ?string $status, $poApprovedAt, $roApprovedAt): bool
    {
        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'pending'
            && !empty($poApprovedAt)
            && empty($roApprovedAt);
    }

    private function hasFundUtilizationReturned(?string $path, ?string $status): bool
    {
        return trim((string) $path) !== ''
            && strtolower(trim((string) $status)) === 'returned';
    }

    private function summarizeFundUtilizationListing(array $quarterDocuments): array
    {
        $summary = [
            'approval_status_label' => 'Awaiting Upload',
            'approval_status_text_color' => '#4b5563',
            'approval_status_background_color' => '#f3f4f6',
            'approval_status_border_color' => '#d1d5db',
            'date_submitted_label' => '—',
            'validation_level_label' => '—',
            'validation_level_text_color' => '#4b5563',
            'validation_level_background_color' => '#f3f4f6',
            'validation_level_border_color' => '#d1d5db',
        ];

        $documents = collect();

        foreach ($quarterDocuments as $quarter => $documentGroup) {
            $movUpload = $documentGroup['mov'] ?? null;
            $writtenNotice = $documentGroup['written_notice'] ?? null;
            $fdpDocument = $documentGroup['fdp'] ?? null;

            if ($movUpload && trim((string) ($movUpload->mov_file_path ?? '')) !== '') {
                $documents->push([
                    'path' => $movUpload->mov_file_path,
                    'status' => $movUpload->status ?? null,
                    'uploaded_at' => $movUpload->mov_uploaded_at ?? null,
                    'approved_at_dilg_po' => $movUpload->approved_at_dilg_po ?? null,
                    'approved_at_dilg_ro' => $movUpload->approved_at_dilg_ro ?? null,
                ]);
            }

            if ($writtenNotice) {
                foreach ([
                    ['path' => $writtenNotice->secretary_dbm_path ?? null, 'status' => $writtenNotice->dbm_status ?? null, 'uploaded_at' => $writtenNotice->dbm_uploaded_at ?? null, 'approved_at_dilg_po' => $writtenNotice->dbm_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->dbm_approved_at_dilg_ro ?? null],
                    ['path' => $writtenNotice->secretary_dilg_path ?? null, 'status' => $writtenNotice->dilg_status ?? null, 'uploaded_at' => $writtenNotice->dilg_uploaded_at ?? null, 'approved_at_dilg_po' => $writtenNotice->dilg_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->dilg_approved_at_dilg_ro ?? null],
                    ['path' => $writtenNotice->speaker_house_path ?? null, 'status' => $writtenNotice->speaker_status ?? null, 'uploaded_at' => $writtenNotice->speaker_uploaded_at ?? null, 'approved_at_dilg_po' => $writtenNotice->speaker_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->speaker_approved_at_dilg_ro ?? null],
                    ['path' => $writtenNotice->president_senate_path ?? null, 'status' => $writtenNotice->president_status ?? null, 'uploaded_at' => $writtenNotice->president_uploaded_at ?? null, 'approved_at_dilg_po' => $writtenNotice->president_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->president_approved_at_dilg_ro ?? null],
                    ['path' => $writtenNotice->house_committee_path ?? null, 'status' => $writtenNotice->house_status ?? null, 'uploaded_at' => $writtenNotice->house_uploaded_at ?? null, 'approved_at_dilg_po' => $writtenNotice->house_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->house_approved_at_dilg_ro ?? null],
                    ['path' => $writtenNotice->senate_committee_path ?? null, 'status' => $writtenNotice->senate_status ?? null, 'uploaded_at' => $writtenNotice->senate_uploaded_at ?? null, 'approved_at_dilg_po' => $writtenNotice->senate_approved_at_dilg_po ?? null, 'approved_at_dilg_ro' => $writtenNotice->senate_approved_at_dilg_ro ?? null],
                ] as $writtenNoticeDocument) {
                    if (trim((string) ($writtenNoticeDocument['path'] ?? '')) === '') {
                        continue;
                    }

                    $documents->push($writtenNoticeDocument);
                }
            }

            if ($fdpDocument && trim((string) ($fdpDocument->fdp_file_path ?? '')) !== '') {
                $documents->push([
                    'path' => $fdpDocument->fdp_file_path,
                    'status' => $fdpDocument->fdp_status ?? null,
                    'uploaded_at' => $fdpDocument->fdp_uploaded_at ?? null,
                    'approved_at_dilg_po' => $fdpDocument->approved_at_dilg_po ?? null,
                    'approved_at_dilg_ro' => $fdpDocument->approved_at_dilg_ro ?? null,
                ]);
            }

            if ($fdpDocument && trim((string) ($fdpDocument->posting_link ?? '')) !== '') {
                $documents->push([
                    'path' => $fdpDocument->posting_link,
                    'status' => $fdpDocument->posting_status ?? null,
                    'uploaded_at' => $fdpDocument->posting_uploaded_at ?? null,
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
        }

        if ($this->hasFundUtilizationReturned($selectedDocument['path'] ?? null, $selectedDocument['status'] ?? null)) {
            $summary['approval_status_label'] = 'Returned';
            $summary['approval_status_text_color'] = '#b91c1c';
            $summary['approval_status_background_color'] = '#fef2f2';
            $summary['approval_status_border_color'] = '#fca5a5';
            $summary['validation_level_label'] = !empty($selectedDocument['approved_at_dilg_po'])
                ? 'Returned at DILG Regional Office'
                : 'Returned at DILG Provincial Office';
            $summary['validation_level_text_color'] = '#b91c1c';
            $summary['validation_level_background_color'] = '#fef2f2';
            $summary['validation_level_border_color'] = '#fca5a5';

            return $summary;
        }

        if ($this->hasFundUtilizationPendingRo(
            $selectedDocument['path'] ?? null,
            $selectedDocument['status'] ?? null,
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

            return $summary;
        }

        if ($this->hasFundUtilizationPendingPo(
            $selectedDocument['path'] ?? null,
            $selectedDocument['status'] ?? null,
            $selectedDocument['approved_at_dilg_po'] ?? null
        )) {
            $summary['approval_status_label'] = 'For DILG Provincial Office Validation';
            $summary['approval_status_text_color'] = '#1d4ed8';
            $summary['approval_status_background_color'] = '#eff6ff';
            $summary['approval_status_border_color'] = '#93c5fd';
            $summary['validation_level_label'] = 'DILG Provincial Office';
            $summary['validation_level_text_color'] = '#1d4ed8';
            $summary['validation_level_background_color'] = '#eff6ff';
            $summary['validation_level_border_color'] = '#93c5fd';

            return $summary;
        }

        if (!empty($selectedDocument['approved_at_dilg_ro'])) {
            $summary['approval_status_label'] = 'Approved';
            $summary['approval_status_text_color'] = '#047857';
            $summary['approval_status_background_color'] = '#ecfdf5';
            $summary['approval_status_border_color'] = '#6ee7b7';
            $summary['validation_level_label'] = 'Completed';
            $summary['validation_level_text_color'] = '#047857';
            $summary['validation_level_background_color'] = '#ecfdf5';
            $summary['validation_level_border_color'] = '#6ee7b7';
        }

        return $summary;
    }

    private function resolveFundUtilizationListingPriority(array $document): int
    {
        if ($this->hasFundUtilizationPendingPo($document['path'] ?? null, $document['status'] ?? null, $document['approved_at_dilg_po'] ?? null)) {
            return 0;
        }

        if ($this->hasFundUtilizationPendingRo($document['path'] ?? null, $document['status'] ?? null, $document['approved_at_dilg_po'] ?? null, $document['approved_at_dilg_ro'] ?? null)) {
            return 0;
        }

        if ($this->hasFundUtilizationReturned($document['path'] ?? null, $document['status'] ?? null)) {
            return 1;
        }

        if (!empty($document['path'])) {
            return 2;
        }

        return 3;
    }

    private function summarizeFundUtilizationValidation(array $quarterDocuments): array
    {
        $summary = [
            'po_count' => 0,
            'ro_count' => 0,
            'returned_count' => 0,
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
            $writtenNotice = $documents['written_notice'] ?? null;
            $fdpDocument = $documents['fdp'] ?? null;

            if ($movUpload && trim((string) ($movUpload->mov_file_path ?? '')) !== '') {
                $summary['uploaded_count']++;

                if ($this->hasFundUtilizationPendingPo($movUpload->mov_file_path, $movUpload->status ?? null, $movUpload->approved_at_dilg_po ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($movUpload->mov_file_path, $movUpload->status ?? null, $movUpload->approved_at_dilg_po ?? null, $movUpload->approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($movUpload->mov_file_path, $movUpload->status ?? null)) {
                    $summary['returned_count']++;
                }
            }

            if ($writtenNotice) {
                $writtenNoticeDocuments = [
                    [
                        'path' => $writtenNotice->secretary_dbm_path ?? null,
                        'status' => $writtenNotice->dbm_status ?? null,
                        'po_timestamp' => $writtenNotice->dbm_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->dbm_approved_at_dilg_ro ?? null,
                    ],
                    [
                        'path' => $writtenNotice->secretary_dilg_path ?? null,
                        'status' => $writtenNotice->dilg_status ?? null,
                        'po_timestamp' => $writtenNotice->dilg_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->dilg_approved_at_dilg_ro ?? null,
                    ],
                    [
                        'path' => $writtenNotice->speaker_house_path ?? null,
                        'status' => $writtenNotice->speaker_status ?? null,
                        'po_timestamp' => $writtenNotice->speaker_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->speaker_approved_at_dilg_ro ?? null,
                    ],
                    [
                        'path' => $writtenNotice->president_senate_path ?? null,
                        'status' => $writtenNotice->president_status ?? null,
                        'po_timestamp' => $writtenNotice->president_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->president_approved_at_dilg_ro ?? null,
                    ],
                    [
                        'path' => $writtenNotice->house_committee_path ?? null,
                        'status' => $writtenNotice->house_status ?? null,
                        'po_timestamp' => $writtenNotice->house_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->house_approved_at_dilg_ro ?? null,
                    ],
                    [
                        'path' => $writtenNotice->senate_committee_path ?? null,
                        'status' => $writtenNotice->senate_status ?? null,
                        'po_timestamp' => $writtenNotice->senate_approved_at_dilg_po ?? null,
                        'ro_timestamp' => $writtenNotice->senate_approved_at_dilg_ro ?? null,
                    ],
                ];

                foreach ($writtenNoticeDocuments as $document) {
                    if (trim((string) ($document['path'] ?? '')) === '') {
                        continue;
                    }

                    $summary['uploaded_count']++;

                    if ($this->hasFundUtilizationPendingPo($document['path'], $document['status'] ?? null, $document['po_timestamp'] ?? null)) {
                        $summary['po_count']++;
                    } elseif ($this->hasFundUtilizationPendingRo($document['path'], $document['status'] ?? null, $document['po_timestamp'] ?? null, $document['ro_timestamp'] ?? null)) {
                        $summary['ro_count']++;
                    } elseif ($this->hasFundUtilizationReturned($document['path'], $document['status'] ?? null)) {
                        $summary['returned_count']++;
                    }
                }
            }

            if ($fdpDocument && trim((string) ($fdpDocument->fdp_file_path ?? '')) !== '') {
                $summary['uploaded_count']++;

                if ($this->hasFundUtilizationPendingPo($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null, $fdpDocument->approved_at_dilg_po ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null, $fdpDocument->approved_at_dilg_po ?? null, $fdpDocument->approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($fdpDocument->fdp_file_path, $fdpDocument->fdp_status ?? null)) {
                    $summary['returned_count']++;
                }
            }

            if ($fdpDocument && trim((string) ($fdpDocument->posting_link ?? '')) !== '') {
                $summary['uploaded_count']++;

                if ($this->hasFundUtilizationPendingPo($fdpDocument->posting_link, $fdpDocument->posting_status ?? null, $fdpDocument->posting_approved_at_dilg_po ?? null)) {
                    $summary['po_count']++;
                } elseif ($this->hasFundUtilizationPendingRo($fdpDocument->posting_link, $fdpDocument->posting_status ?? null, $fdpDocument->posting_approved_at_dilg_po ?? null, $fdpDocument->posting_approved_at_dilg_ro ?? null)) {
                    $summary['ro_count']++;
                } elseif ($this->hasFundUtilizationReturned($fdpDocument->posting_link, $fdpDocument->posting_status ?? null)) {
                    $summary['returned_count']++;
                }
            }
        }

        $summary['pending_total'] = $summary['po_count'] + $summary['ro_count'];

        if ($summary['pending_total'] > 0) {
            $summary['label'] = 'Pending Validation';
            $summary['icon'] = 'fa-clock';

            if ($summary['po_count'] > 0 && $summary['ro_count'] > 0) {
                $summary['detail'] = 'PO: ' . $summary['po_count'] . ' | RO: ' . $summary['ro_count'];
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

        if ($summary['returned_count'] > 0) {
            $summary['label'] = 'Returned';
            $summary['detail'] = $summary['returned_count'] . ' returned item' . ($summary['returned_count'] === 1 ? '' : 's');
            $summary['icon'] = 'fa-undo';
            $summary['text_color'] = '#b91c1c';
            $summary['background_color'] = '#fef2f2';
            $summary['border_color'] = '#fca5a5';

            return $summary;
        }

        if ($summary['uploaded_count'] > 0) {
            $summary['label'] = 'No Pending';
            $summary['detail'] = 'Validation queue is clear';
            $summary['icon'] = 'fa-check-circle';
            $summary['text_color'] = '#047857';
            $summary['background_color'] = '#ecfdf5';
            $summary['border_color'] = '#6ee7b7';
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

    private function formatExportLink(?string $path, bool $asHtmlLink): string
    {
        if (!$path) {
            return '-';
        }

        $url = $this->publicFileUrl($path);
        return $asHtmlLink ? $this->toHtmlLink($url) : $url;
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

    private function toHtmlLink(string $value): string
    {
        $safeText = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $safeUrl = InputSanitizer::sanitizeHttpUrl($value);

        if ($safeUrl === null) {
            return $safeText;
        }

        $escapedUrl = htmlspecialchars($safeUrl, ENT_QUOTES, 'UTF-8');

        return '<a href="' . $escapedUrl . '">' . $safeText . '</a>';
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
        $writtenNotices = [];
        $fdpDocuments = [];
        $adminRemarks = [];
        $accomplishmentPercentages = [];

        foreach ($quarters as $quarter) {
            if ($report->is_lfp ?? false) {
                // For LFP projects, retrieve FUR data by subaybayan_project_code + quarter
                $movUploads[$quarter] = FURMovUpload::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $writtenNotices[$quarter] = FURWrittenNotice::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $fdpDocuments[$quarter] = FURFDP::where('project_code', $projectCode)->where('quarter', $quarter)->first();
                $adminRemarks[$quarter] = FURAdminRemark::where('project_code', $projectCode)->where('quarter', $quarter)->first();
            } else {
                $movUploads[$quarter] = $report->movUploads()->where('quarter', $quarter)->first();
                $writtenNotices[$quarter] = $report->writtenNotices()->where('quarter', $quarter)->first();
                $fdpDocuments[$quarter] = $report->fdpDocuments()->where('quarter', $quarter)->first();
                $adminRemarks[$quarter] = $report->adminRemarks()->where('quarter', $quarter)->first();
            }
            
            // Calculate accomplishment percentage for this quarter
            $accomplishmentPercentages[$quarter] = $this->calculateAccomplishmentPercentage($movUploads[$quarter], $writtenNotices[$quarter], $fdpDocuments[$quarter]);
        }

        $activityLogs = $this->getFundUtilizationLogs($projectCode);

        return view('reports.fund-utilization.show', compact(
            'report',
            'quarters',
            'movUploads',
            'writtenNotices',
            'fdpDocuments',
            'adminRemarks',
            'activityLogs',
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
    private function calculateAccomplishmentPercentage($movUpload, $writtenNotice, $fdpDocument)
    {
        $totalDocuments = 9; // MOV + 6 Written Notices (DBM, DILG, Speaker, President, House, Senate) + FDP + LGU Website = 9
        $approvedDocuments = 0;

        // Check MOV - must have approved status
        if ($movUpload && $movUpload->status === 'approved') {
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
    public function uploadMOV(Request $request, $projectCode)
    {
        $request->validate([
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'mov_file' => 'required|mimes:pdf|max:10240',
        ]);

        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        $autoElevateToRegional = $this->isProvincialDilgUser($user);

        if ($request->hasFile('mov_file')) {
            $existingRecord = FURMovUpload::where('project_code', $projectCode)
                                         ->where('quarter', $request->quarter)
                                         ->first();
            $oldFilePath = $existingRecord?->mov_file_path;
            $file = $request->file('mov_file');
            $path = $file->store('fur/mov/' . $projectCode, 'public');

            // Get secure, tamper-proof timestamp from PAGASA server
            // User cannot change this by modifying their computer's clock
            $secureTimestamp = SecureTimestampService::getUploadTimestamp();
            
            $updates = [
                'mov_file_path' => $path, 
                'updated_at' => $secureTimestamp, 
                'encoder_id' => auth()->id(),
                'mov_uploaded_at' => $secureTimestamp,
                'mov_encoder_id' => auth()->id(),
                // Reset status/approval when a returned file is resubmitted
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'approved_at_dilg_po' => null,
                'approved_at_dilg_ro' => null,
                'approved_by_dilg_po' => null,
                'approved_by_dilg_ro' => null,
                'approval_remarks' => null,
            ];

            if ($autoElevateToRegional) {
                $updates['approved_by'] = auth()->id();
                $updates['approved_at'] = $secureTimestamp;
                $updates['approved_at_dilg_po'] = $secureTimestamp;
                $updates['approved_at_dilg_ro'] = null;
                $updates['approved_by_dilg_po'] = auth()->id();
                $updates['approved_by_dilg_ro'] = null;
            }
            
            // Add created_at with secure timestamp only for new records
            if (!$existingRecord) {
                $updates['created_at'] = $secureTimestamp;
            }
            
            FURMovUpload::updateOrCreate(
                ['project_code' => $projectCode, 'quarter' => $request->quarter],
                $updates
            );

            if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            // Log the upload for audit trail
            SecureTimestampService::logUploadTimestamp('mov', $projectCode, $request->quarter, $secureTimestamp);

            if ($autoElevateToRegional) {
                $this->notifyDilgRegionalUsers($report, 'mov', $request->quarter);
            } else {
                // Notify DILG users in the same province when an LGU submits.
                $this->notifyDilgProvinceUsers($report, 'mov', $request->quarter);
            }
        }

        return back()->with('success', 'MOV file uploaded successfully.');
    }

    /**
     * Upload Written Notice files
     */
    public function uploadWrittenNotice(Request $request, $projectCode)
    {
        $request->validate([
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'secretary_dbm' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'secretary_dilg' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'speaker_house' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'president_senate' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'house_committee' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'senate_committee' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        $autoElevateToRegional = $this->isProvincialDilgUser($user);
        $data = ['project_code' => $projectCode, 'quarter' => $request->quarter];
        $updates = [];

        // Get secure, tamper-proof timestamp from PAGASA server
        $secureTimestamp = SecureTimestampService::getUploadTimestamp();
        $existingRecord = FURWrittenNotice::where('project_code', $projectCode)
            ->where('quarter', $request->quarter)
            ->first();
        $replacedPaths = [];

        // Map request fields to database fields and individual upload timestamp fields
        $fields = [
            'secretary_dbm' => [
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

        foreach ($fields as $requestField => $fieldConfig) {
            if ($request->hasFile($requestField)) {
                $oldPath = $existingRecord?->{$fieldConfig['path']};
                $file = $request->file($requestField);
                $path = $file->store('fur/written-notice/' . $projectCode, 'public');
                $updates[$fieldConfig['path']] = $path;
                $replacedPaths[] = ['old' => $oldPath, 'new' => $path];
                // Set individual upload timestamp for this specific document
                $updates[$fieldConfig['uploaded_at']] = $secureTimestamp;
                $updates[$fieldConfig['encoder_id']] = auth()->id();
                // Reset status/approval when a returned file is resubmitted
                $updates[$fieldConfig['status']] = 'pending';
                $updates[$fieldConfig['approved_by']] = null;
                $updates[$fieldConfig['approved_at']] = null;
                if (!empty($fieldConfig['approved_at_dilg_po'])) {
                    $updates[$fieldConfig['approved_at_dilg_po']] = null;
                }
                if (!empty($fieldConfig['approved_at_dilg_ro'])) {
                    $updates[$fieldConfig['approved_at_dilg_ro']] = null;
                }
                $updates[$fieldConfig['remarks']] = null;
                // Also clear shared approval state so the UI fully resets
                $updates['status'] = 'pending';
                $updates['approved_by'] = null;
                $updates['approved_at'] = null;
                $updates['approved_at_dilg_po'] = null;
                $updates['approved_at_dilg_ro'] = null;
                $updates['approval_remarks'] = null;
                $updates['user_remarks'] = null;

                if ($autoElevateToRegional) {
                    $updates[$fieldConfig['approved_by']] = auth()->id();
                    $updates[$fieldConfig['approved_at']] = $secureTimestamp;
                    if (!empty($fieldConfig['approved_at_dilg_po'])) {
                        $updates[$fieldConfig['approved_at_dilg_po']] = $secureTimestamp;
                    }
                    if (!empty($fieldConfig['approved_at_dilg_ro'])) {
                        $updates[$fieldConfig['approved_at_dilg_ro']] = null;
                    }
                    $updates[$fieldConfig['approved_by'] . '_dilg_po'] = auth()->id();
                    $updates[$fieldConfig['approved_by'] . '_dilg_ro'] = null;
                }
                
                // Log the upload for audit trail
                $shortFieldName = str_replace('secretary_', '', $requestField);
                SecureTimestampService::logUploadTimestamp('written-notice-' . $shortFieldName, $projectCode, $request->quarter, $secureTimestamp);

                if ($autoElevateToRegional) {
                    $this->notifyDilgRegionalUsers($report, 'written-notice-' . $shortFieldName, $request->quarter);
                } else {
                    // Notify DILG users in the same province when an LGU submits.
                    $this->notifyDilgProvinceUsers($report, 'written-notice-' . $shortFieldName, $request->quarter);
                }
            }
        }

        if (!empty($updates)) {
            // Add created_at with secure timestamp only for new records
            if (!$existingRecord) {
                $updates['created_at'] = $secureTimestamp;
            }
            
            FURWrittenNotice::updateOrCreate($data, $updates);

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
    public function uploadFDP(Request $request, $projectCode)
    {
        $request->validate([
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'fdp_file' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        $autoElevateToRegional = $this->isProvincialDilgUser($user);

        if ($request->hasFile('fdp_file')) {
            $existingRecord = FURFDP::where('project_code', $projectCode)
                                    ->where('quarter', $request->quarter)
                                    ->first();
            $oldFilePath = $existingRecord?->fdp_file_path;
            $file = $request->file('fdp_file');
            $path = $file->store('fur/fdp/' . $projectCode, 'public');

            // Get secure, tamper-proof timestamp from PAGASA server
            $secureTimestamp = SecureTimestampService::getUploadTimestamp();
            
            $updates = [
                'fdp_file_path' => $path, 
                'updated_at' => $secureTimestamp, 
                'encoder_id' => auth()->id(),
                'fdp_uploaded_at' => $secureTimestamp,
                'fdp_encoder_id' => auth()->id(),
                // Reset status/approval when a returned file is resubmitted
                'fdp_status' => 'pending',
                'fdp_approved_by' => null,
                'fdp_approved_at' => null,
                'fdp_remarks' => null,
                // Clear shared approval state as well
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'approved_at_dilg_po' => null,
                'approved_at_dilg_ro' => null,
                'approved_by_dilg_po' => null,
                'approved_by_dilg_ro' => null,
                'approval_remarks' => null,
                'user_remarks' => null,
            ];

            if ($autoElevateToRegional) {
                $updates['fdp_approved_by'] = auth()->id();
                $updates['fdp_approved_at'] = $secureTimestamp;
                $updates['approved_at_dilg_po'] = $secureTimestamp;
                $updates['approved_at_dilg_ro'] = null;
                $updates['approved_by_dilg_po'] = auth()->id();
                $updates['approved_by_dilg_ro'] = null;
            }
            
            // Add created_at with secure timestamp only for new records
            if (!$existingRecord) {
                $updates['created_at'] = $secureTimestamp;
            }
            
            FURFDP::updateOrCreate(
                ['project_code' => $projectCode, 'quarter' => $request->quarter],
                $updates
            );

            if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            // Log the upload for audit trail
            SecureTimestampService::logUploadTimestamp('fdp', $projectCode, $request->quarter, $secureTimestamp);

            if ($autoElevateToRegional) {
                $this->notifyDilgRegionalUsers($report, 'fdp', $request->quarter);
            } else {
                // Notify DILG users in the same province when an LGU submits.
                $this->notifyDilgProvinceUsers($report, 'fdp', $request->quarter);
            }
        }

        return back()->with('success', 'FDP document uploaded successfully.');
    }

    /**
     * Save LGU posting link (website/social media).
     */
    public function savePostingLink(Request $request, $projectCode)
    {
        $validated = $request->validate([
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'posting_link' => 'required|string|max:2048',
        ]);

        $report = $this->getReportOrLfpProject($projectCode);
        $user = Auth::user();
        $autoElevateToRegional = $this->isProvincialDilgUser($user);

        $secureTimestamp = SecureTimestampService::getUploadTimestamp();

        $postingLink = InputSanitizer::sanitizeHttpUrl($validated['posting_link']);
        if ($postingLink === null) {
            return back()
                ->withInput()
                ->withErrors(['posting_link' => 'Please enter a valid http or https URL.']);
        }

        $updates = [
            'posting_link' => $postingLink,
            'posting_uploaded_at' => $secureTimestamp,
            'posting_encoder_id' => auth()->id(),
            // Reset posting-link approval state on resubmit/edit
            'posting_status' => 'pending',
            'posting_approved_by' => null,
            'posting_approved_at' => null,
            'posting_approved_at_dilg_po' => null,
            'posting_approved_at_dilg_ro' => null,
            'posting_approved_by_dilg_po' => null,
            'posting_approved_by_dilg_ro' => null,
            'posting_remarks' => null,
        ];

        if ($autoElevateToRegional) {
            $updates['posting_approved_by'] = auth()->id();
            $updates['posting_approved_at'] = $secureTimestamp;
            $updates['posting_approved_at_dilg_po'] = $secureTimestamp;
            $updates['posting_approved_at_dilg_ro'] = null;
            $updates['posting_approved_by_dilg_po'] = auth()->id();
            $updates['posting_approved_by_dilg_ro'] = null;
        }

        FURFDP::updateOrCreate(
            ['project_code' => $projectCode, 'quarter' => $validated['quarter']],
            $updates
        );

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

        if ($autoElevateToRegional) {
            $this->notifyDilgRegionalUsers($report, 'posting-link', $validated['quarter']);
        } else {
            $this->notifyDilgProvinceUsers($report, 'posting-link', $validated['quarter']);
        }

        return back()->with('success', 'LGU posting link saved successfully.');
    }

    /**
     * Approve or return upload with remarks
     */
    public function approveUpload(Request $request, $projectCode, $uploadType, $quarter)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,return',
            'remarks' => 'required_if:action,return|nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $isDilgUser = $user && $user->agency === 'DILG';
        $isRegionalOffice = $isDilgUser
            && strtolower(trim((string) ($user->province ?? ''))) === 'regional office';
        $isProvincialOffice = $isDilgUser && !$isRegionalOffice;

        $action = $validated['action'];
        $remarks = $this->sanitizeReportRemarks($validated['remarks'] ?? null);

        if ($action === 'return' && $remarks === null) {
            return back()
                ->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        // Map uploadType to individual status field names
        $statusFieldMap = [
            'written-notice-dbm' => 'dbm_status',
            'written-notice-dilg' => 'dilg_status',
            'written-notice-speaker' => 'speaker_status',
            'written-notice-president' => 'president_status',
            'written-notice-house' => 'house_status',
            'written-notice-senate' => 'senate_status',
            'fdp' => 'fdp_status',
            'posting-link' => 'posting_status',
        ];

        // Map uploadType to individual approval timestamp field names
        $timestampFieldMap = [
            'written-notice-dbm' => 'dbm_approved_at',
            'written-notice-dilg' => 'dilg_approved_at',
            'written-notice-speaker' => 'speaker_approved_at',
            'written-notice-president' => 'president_approved_at',
            'written-notice-house' => 'house_approved_at',
            'written-notice-senate' => 'senate_approved_at',
            'fdp' => 'fdp_approved_at',
            'posting-link' => 'posting_approved_at',
        ];

        // Map uploadType to individual approver field names
        $approverFieldMap = [
            'written-notice-dbm' => 'dbm_approved_by',
            'written-notice-dilg' => 'dilg_approved_by',
            'written-notice-speaker' => 'speaker_approved_by',
            'written-notice-president' => 'president_approved_by',
            'written-notice-house' => 'house_approved_by',
            'written-notice-senate' => 'senate_approved_by',
            'fdp' => 'fdp_approved_by',
            'posting-link' => 'posting_approved_by',
        ];

        // Map uploadType to remarks field names
        $remarksFieldMap = [
            'written-notice-dbm' => 'dbm_remarks',
            'written-notice-dilg' => 'dilg_remarks',
            'written-notice-speaker' => 'speaker_remarks',
            'written-notice-president' => 'president_remarks',
            'written-notice-house' => 'house_remarks',
            'written-notice-senate' => 'senate_remarks',
            'fdp' => 'fdp_remarks',
            'posting-link' => 'posting_remarks',
        ];

        // Map uploadType to individual DILG PO/RO validation timestamp fields
        $poTimestampFieldMap = [
            'written-notice-dbm' => 'dbm_approved_at_dilg_po',
            'written-notice-dilg' => 'dilg_approved_at_dilg_po',
            'written-notice-speaker' => 'speaker_approved_at_dilg_po',
            'written-notice-president' => 'president_approved_at_dilg_po',
            'written-notice-house' => 'house_approved_at_dilg_po',
            'written-notice-senate' => 'senate_approved_at_dilg_po',
        ];

        $roTimestampFieldMap = [
            'written-notice-dbm' => 'dbm_approved_at_dilg_ro',
            'written-notice-dilg' => 'dilg_approved_at_dilg_ro',
            'written-notice-speaker' => 'speaker_approved_at_dilg_ro',
            'written-notice-president' => 'president_approved_at_dilg_ro',
            'written-notice-house' => 'house_approved_at_dilg_ro',
            'written-notice-senate' => 'senate_approved_at_dilg_ro',
        ];

        $statusField = $statusFieldMap[$uploadType] ?? 'status';
        $timestampField = $timestampFieldMap[$uploadType] ?? 'approved_at';
        $approverField = $approverFieldMap[$uploadType] ?? 'approved_by';
        $remarksField = $remarksFieldMap[$uploadType] ?? 'approval_remarks';

        $now = pagasa_time();
        $isWrittenNoticeDocument = str_starts_with($uploadType, 'written-notice-');
        $poTimestampField = $isWrittenNoticeDocument ? ($poTimestampFieldMap[$uploadType] ?? null) : null;
        $roTimestampField = $isWrittenNoticeDocument ? ($roTimestampFieldMap[$uploadType] ?? null) : null;

        // Provincial DILG approvals should be elevated to the Regional Office.
        // That means we mark the provincial validation timestamp but keep the
        // document in a pending state until the RO validates it.
        if ($action === 'approve' && $isProvincialOffice) {
            // For written notices, use individual approval timestamps so
            // approving one document does not elevate all documents.
            if ($isWrittenNoticeDocument) {
                $data = [
                    $statusField => 'pending',
                    $approverField => Auth::id(),
                    $timestampField => $now,
                    $remarksField => null,
                ];
                if ($poTimestampField) {
                    $data[$poTimestampField] = $now;
                }
                if ($roTimestampField) {
                    $data[$roTimestampField] = null;
                }
                // Set separate PO approver field
                $data[$approverField . '_dilg_po'] = Auth::id();
            } else {
                $data = [
                    $statusField => 'pending',
                    $approverField => Auth::id(),
                    $timestampField => $now,
                    $remarksField => null,
                    'approved_at_dilg_po' => $now,
                    'approved_at_dilg_ro' => null,
                    'approved_by_dilg_po' => Auth::id(),
                ];
            }
        } else {
            // Determine the approval status
            $status = $action === 'approve' ? 'approved' : 'returned';

            $data = [
                $statusField => $status,
                $approverField => Auth::id(),
            ];

            // Always set the approval timestamp when approving or returning
            $data[$timestampField] = $now;

            if ($action === 'approve') {
                $data[$remarksField] = null;
            } elseif ($action === 'return' && $remarks !== null) {
                $data[$remarksField] = $remarks;
                $data['user_remarks'] = $remarks; // Save return remarks in user_remarks so they persist in notes
            }

            // Regional Office approvals should mark the RO validation timestamp.
            if ($action === 'approve' && $isRegionalOffice) {
                if ($isWrittenNoticeDocument) {
                    if ($roTimestampField) {
                        $data[$roTimestampField] = $now;
                    }
                    // Preserve any existing PO validation timestamp.
                    if ($poTimestampField) {
                        $data[$poTimestampField] = DB::raw($poTimestampField);
                    }
                    // Set separate RO approver field
                    $data[$approverField . '_dilg_ro'] = Auth::id();
                } else {
                    $data['approved_at_dilg_ro'] = $now;
                    // Preserve any existing provincial validation if present.
                    if (!isset($data['approved_at_dilg_po'])) {
                        $data['approved_at_dilg_po'] = DB::raw('approved_at_dilg_po');
                    }
                    // Set separate RO approver field
                    $data['approved_by_dilg_ro'] = Auth::id();
                }
            }
        }

        switch ($uploadType) {
            case 'mov':
                FURMovUpload::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'MOV file validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'MOV file approved.' : 'MOV file returned.';
                }
                break;
            case 'written-notice':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'Written Notice files validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'Written Notice files approved.' : 'Written Notice files returned.';
                }
                break;
            case 'written-notice-dbm':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'DBM document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'DBM Document approved.' : 'DBM Document returned.';
                }
                break;
            case 'written-notice-dilg':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'DILG document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'DILG Document approved.' : 'DILG Document returned.';
                }
                break;
            case 'written-notice-speaker':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'Speaker document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'Speaker Document approved.' : 'Speaker Document returned.';
                }
                break;
            case 'written-notice-president':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'President document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'President Document approved.' : 'President Document returned.';
                }
                break;
            case 'written-notice-house':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'House document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'House Document approved.' : 'House Document returned.';
                }
                break;
            case 'written-notice-senate':
                FURWrittenNotice::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'Senate document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'Senate Document approved.' : 'Senate Document returned.';
                }
                break;
            case 'fdp':
                FURFDP::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'FDP document validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'FDP document approved.' : 'FDP document returned.';
                }
                break;
            case 'posting-link':
                if ($action === 'approve' && $isProvincialOffice) {
                    $data['posting_status'] = 'pending';
                    $data['posting_approved_at_dilg_po'] = $now;
                    $data['posting_approved_at_dilg_ro'] = null;
                }
                if ($action === 'approve' && $isRegionalOffice) {
                    $data['posting_approved_at_dilg_ro'] = $now;
                    // Preserve any existing provincial validation timestamp.
                    $data['posting_approved_at_dilg_po'] = DB::raw('posting_approved_at_dilg_po');
                }
                FURFDP::where('project_code', $projectCode)
                    ->where('quarter', $quarter)
                    ->update($data);
                if ($action === 'approve' && $isProvincialOffice) {
                    $message = 'Posting link validated by DILG Provincial Office and elevated for DILG Regional validation.';
                } else {
                    $message = $action === 'approve' ? 'Posting link approved.' : 'Posting link returned.';
                }
                break;
        }

        // Notify DILG Regional Office users when a provincial DILG user validates.
        if ($action === 'approve' && $isProvincialOffice) {
            $report = $this->getReportOrLfpProject($projectCode);
            $this->notifyDilgRegionalUsers($report, $uploadType, $quarter);
        }

        // Notify LGU users when DILG Regional Office approves.
        if ($action === 'approve' && $isRegionalOffice) {
            $report = $this->getReportOrLfpProject($projectCode);
            $this->notifyLguUsersAfterRegionalApproval($report, $uploadType, $quarter);
        }

        Log::channel('upload_timestamps')->info('Document action', [
            'document_type' => $uploadType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => $action,
            'remarks' => $remarks,
            'action_timestamp' => pagasa_time()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]);

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

        if (!$upload || !$upload->$column) {
            abort(404, 'Document not found');
        }

        $filePath = storage_path('app/public/' . $upload->$column);

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
    public function deleteDocument($projectCode, $docType, $quarter)
    {
        $docTypeMap = [
            'mov' => ['table' => 'tbfur_mov_uploads', 'column' => 'mov_file_path', 'filePath' => 'mov_file_path', 'has_file' => true],
            'written-notice-dbm' => ['table' => 'tbfur_written_notice', 'column' => 'secretary_dbm_path', 'filePath' => 'secretary_dbm_path', 'has_file' => true],
            'written-notice-dilg' => ['table' => 'tbfur_written_notice', 'column' => 'secretary_dilg_path', 'filePath' => 'secretary_dilg_path', 'has_file' => true],
            'written-notice-speaker' => ['table' => 'tbfur_written_notice', 'column' => 'speaker_house_path', 'filePath' => 'speaker_house_path', 'has_file' => true],
            'written-notice-president' => ['table' => 'tbfur_written_notice', 'column' => 'president_senate_path', 'filePath' => 'president_senate_path', 'has_file' => true],
            'written-notice-house' => ['table' => 'tbfur_written_notice', 'column' => 'house_committee_path', 'filePath' => 'house_committee_path', 'has_file' => true],
            'written-notice-senate' => ['table' => 'tbfur_written_notice', 'column' => 'senate_committee_path', 'filePath' => 'senate_committee_path', 'has_file' => true],
            'fdp' => ['table' => 'tbfur_fdp', 'column' => 'fdp_file_path', 'filePath' => 'fdp_file_path', 'has_file' => true],
            'posting-link' => ['table' => 'tbfur_fdp', 'column' => 'posting_link', 'filePath' => null, 'has_file' => false],
        ];

        if (!isset($docTypeMap[$docType])) {
            return response()->json(['message' => 'Invalid document type'], 400);
        }

        $config = $docTypeMap[$docType];
        $table = $config['table'];
        $column = $config['column'];
        $hasFile = $config['has_file'];

        // Get the file path from database
        $upload = \DB::table($table)
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload || !$upload->$column) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $storagePath = $upload->$column;
        if ($hasFile && $storagePath && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }

        // Clear the path from database and reset related approval/remarks state
        $updateData = [$column => null];

        switch ($docType) {
            case 'mov':
                $updateData = array_merge($updateData, [
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                    'approved_at_dilg_po' => null,
                    'approved_at_dilg_ro' => null,
                    'approval_remarks' => null,
                    'user_remarks' => null,
                    'mov_uploaded_at' => null,
                    'mov_encoder_id' => null,
                    'encoder_id' => null,
                    'updated_at' => null,
                ]);
                break;
            case 'written-notice-dbm':
                $updateData = array_merge($updateData, [
                    'dbm_status' => 'pending',
                    'dbm_approved_by' => null,
                    'dbm_approved_at' => null,
                    'dbm_approved_at_dilg_po' => null,
                    'dbm_approved_at_dilg_ro' => null,
                    'dbm_remarks' => null,
                    'dbm_uploaded_at' => null,
                    'dbm_encoder_id' => null,
                ]);
                break;
            case 'written-notice-dilg':
                $updateData = array_merge($updateData, [
                    'dilg_status' => 'pending',
                    'dilg_approved_by' => null,
                    'dilg_approved_at' => null,
                    'dilg_approved_at_dilg_po' => null,
                    'dilg_approved_at_dilg_ro' => null,
                    'dilg_remarks' => null,
                    'dilg_uploaded_at' => null,
                    'dilg_encoder_id' => null,
                ]);
                break;
            case 'written-notice-speaker':
                $updateData = array_merge($updateData, [
                    'speaker_status' => 'pending',
                    'speaker_approved_by' => null,
                    'speaker_approved_at' => null,
                    'speaker_approved_at_dilg_po' => null,
                    'speaker_approved_at_dilg_ro' => null,
                    'speaker_remarks' => null,
                    'speaker_uploaded_at' => null,
                    'speaker_encoder_id' => null,
                ]);
                break;
            case 'written-notice-president':
                $updateData = array_merge($updateData, [
                    'president_status' => 'pending',
                    'president_approved_by' => null,
                    'president_approved_at' => null,
                    'president_approved_at_dilg_po' => null,
                    'president_approved_at_dilg_ro' => null,
                    'president_remarks' => null,
                    'president_uploaded_at' => null,
                    'president_encoder_id' => null,
                ]);
                break;
            case 'written-notice-house':
                $updateData = array_merge($updateData, [
                    'house_status' => 'pending',
                    'house_approved_by' => null,
                    'house_approved_at' => null,
                    'house_approved_at_dilg_po' => null,
                    'house_approved_at_dilg_ro' => null,
                    'house_remarks' => null,
                    'house_uploaded_at' => null,
                    'house_encoder_id' => null,
                ]);
                break;
            case 'written-notice-senate':
                $updateData = array_merge($updateData, [
                    'senate_status' => 'pending',
                    'senate_approved_by' => null,
                    'senate_approved_at' => null,
                    'senate_approved_at_dilg_po' => null,
                    'senate_approved_at_dilg_ro' => null,
                    'senate_remarks' => null,
                    'senate_uploaded_at' => null,
                    'senate_encoder_id' => null,
                ]);
                break;
            case 'fdp':
                $updateData = array_merge($updateData, [
                    'fdp_status' => 'pending',
                    'fdp_approved_by' => null,
                    'fdp_approved_at' => null,
                    'fdp_remarks' => null,
                    'fdp_uploaded_at' => null,
                    'fdp_encoder_id' => null,
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                    'approved_at_dilg_po' => null,
                    'approved_at_dilg_ro' => null,
                    'approval_remarks' => null,
                    'user_remarks' => null,
                    'encoder_id' => null,
                    'updated_at' => null,
                ]);
                break;
            case 'posting-link':
                $updateData = array_merge($updateData, [
                    'posting_status' => 'pending',
                    'posting_approved_by' => null,
                    'posting_approved_at' => null,
                    'posting_approved_at_dilg_po' => null,
                    'posting_approved_at_dilg_ro' => null,
                    'posting_remarks' => null,
                    'posting_uploaded_at' => null,
                    'posting_encoder_id' => null,
                    'user_remarks' => null,
                ]);
                break;
        }

        if (strpos($docType, 'written-notice-') === 0) {
            // Clear shared written-notice approval/remarks to avoid stale banners.
            $updateData = array_merge($updateData, [
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'approved_at_dilg_po' => null,
                'approved_at_dilg_ro' => null,
                'approval_remarks' => null,
                'user_remarks' => null,
                'encoder_id' => null,
                'updated_at' => null,
            ]);
        }
        
        \DB::table($table)
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->update($updateData);

        // Delete audit trail history for this document
        \Log::channel('upload_timestamps')->info('Document deleted', [
            'document_type' => $docType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'action' => 'delete',
            'deleted_at' => pagasa_time()->format('Y-m-d H:i:s'),
            'deleted_by' => auth()->id(),
            'user_id' => auth()->id(),
            'storage_path' => $storagePath
        ]);

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
                    $entries[] = $parsed;
                }
            }
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
            'document_type' => $context['document_type'] ?? null,
            'quarter' => $context['quarter'] ?? null,
            'user_id' => $context['user_id'] ?? $context['deleted_by'] ?? $context['approved_by'] ?? null,
            'remarks' => $context['remarks'] ?? null,
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
