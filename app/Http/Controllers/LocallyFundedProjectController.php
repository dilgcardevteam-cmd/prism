<?php

namespace App\Http\Controllers;

use App\Models\FundUtilizationReport;
use App\Models\DeadlineConfiguration;
use App\Models\LocallyFundedProject;
use App\Services\InterventionNotificationService;
use App\Support\InputSanitizer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LocallyFundedProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['mobileDashboardSummary', 'mobileExpectedCompletionThisMonth', 'mobileAggregatedDashboard', 'mobileIndex', 'viewMobileGalleryImage', 'mobileUploadGalleryImage']);
        $this->middleware('crud_permission:locally_funded_projects,view')->only(['index']);
        $this->middleware('crud_permission:locally_funded_projects,view')->only(['showSubaybayan', 'show']);
        $this->middleware('crud_permission:locally_funded_projects,add')->only(['create', 'store']);
        $this->middleware('crud_permission:locally_funded_projects,update')->only(['edit', 'update']);
        $this->middleware('crud_permission:locally_funded_projects,delete')->only(['destroy']);
    }

    private function locallyFundedGalleryCategories(): array
    {
        return ['All', 'Before', 'Project Billboard', 'Community Billboard', '20-40%', '50-70%', '90%', 'Completed', 'During'];
    }

    private function sanitizeGalleryFileSegment(?string $value, string $fallback = 'na'): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', '-', strtoupper((string) ($value ?? '')));
        $normalized = trim((string) $normalized, '-');

        return $normalized !== '' ? $normalized : strtoupper($fallback);
    }

    private function buildGalleryImageFileName(
        LocallyFundedProject $project,
        string $category,
        Carbon $timestamp,
        int $sequence,
        string $extension
    ): string {
        $projectCode = $this->sanitizeGalleryFileSegment(
            $project->subaybayan_project_code ?: ('PROJECT-' . $project->id),
            'PROJECT-' . $project->id
        );
        $stage = $this->sanitizeGalleryFileSegment($category, 'DURING');
        $datePart = $timestamp->format('Ymd');
        $sequencePart = str_pad((string) max($sequence, 1), 3, '0', STR_PAD_LEFT);
        $safeExtension = strtolower(trim($extension, '.'));

        return $projectCode . '-' . $stage . '-' . $datePart . '-' . $sequencePart . '.' . ($safeExtension !== '' ? $safeExtension : 'jpg');
    }

    public function mobileDashboardSummary()
    {
        $fundSourceOrder = ['SBDP', 'FALGU', 'CMGP', 'GEF', 'SAFPB'];
        $statusDisplayOrder = [
            'Completed',
            'On-going',
            'Bid Evaluation/Opening',
            'NOA Issuance',
            'DED Preparation',
            'Not Yet Started',
            'ITB/AD Posted',
            'Terminated',
            'Cancelled',
        ];

        $statusLabelByNormalized = [
            'COMPLETED' => 'Completed',
            'ONGOING' => 'On-going',
            'BID EVALUATION/OPENING' => 'Bid Evaluation/Opening',
            'NOA ISSUANCE' => 'NOA Issuance',
            'DED PREPARATION' => 'DED Preparation',
            'NOT YET STARTED' => 'Not Yet Started',
            'ITB/AD POSTED' => 'ITB/AD Posted',
            'TERMINATED' => 'Terminated',
            'CANCELLED' => 'Cancelled',
        ];

        $statusAliases = [
            'ON-GOING' => 'ONGOING',
            'NOT STARTED' => 'NOT YET STARTED',
        ];

        $normalizeFundSource = static function ($value): string {
            $normalized = strtoupper(trim((string) ($value ?? '')));

            if ($normalized === '') {
                return 'UNSPECIFIED';
            }

            if (str_starts_with($normalized, 'FALGU')) {
                return 'FALGU';
            }
            if (str_starts_with($normalized, 'CMGP')) {
                return 'CMGP';
            }
            if (str_starts_with($normalized, 'SBDP')) {
                return 'SBDP';
            }
            if (str_starts_with($normalized, 'GEF')) {
                return 'GEF';
            }
            if (str_starts_with($normalized, 'SAFPB')) {
                return 'SAFPB';
            }

            return $normalized;
        };

        $normalizeStatus = static function ($value) use ($statusAliases, $statusLabelByNormalized): ?string {
            $raw = trim((string) ($value ?? ''));
            if ($raw === '') {
                return null;
            }

            $upper = strtoupper($raw);
            $normalized = $statusAliases[$upper] ?? $upper;

            return $statusLabelByNormalized[$normalized] ?? null;
        };

        $toFloat = static function ($value): float {
            if ($value === null) {
                return 0.0;
            }

            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return 0.0;
            }

            $clean = preg_replace('/[^0-9\.-]/', '', $stringValue);
            if ($clean === null || $clean === '' || $clean === '-' || $clean === '.') {
                return 0.0;
            }

            return (float) $clean;
        };

        $hasLfpFundSourceColumn = Schema::hasColumn('locally_funded_projects', 'fund_source');
        $hasLfpAllocationColumn = Schema::hasColumn('locally_funded_projects', 'lgsf_allocation');
        $hasLfpObligationColumn = Schema::hasColumn('locally_funded_projects', 'obligation');
        $hasLfpDisbursedAmountColumn = Schema::hasColumn('locally_funded_projects', 'disbursed_amount');
        $hasLfpRevertedAmountColumn = Schema::hasColumn('locally_funded_projects', 'reverted_amount');
        $hasLfpUpdatedAtColumn = Schema::hasColumn('locally_funded_projects', 'updated_at');

        $hasPhysicalUpdatesTable = Schema::hasTable('locally_funded_physical_updates');

        $query = DB::table('subay_project_profiles as spp')
            ->leftJoin('locally_funded_projects as lfp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
            ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%']);

        if ($hasPhysicalUpdatesTable) {
            $query->leftJoin('locally_funded_physical_updates as lpu', function ($join) {
                $join->on('lpu.project_id', '=', 'lfp.id')
                    ->where('lpu.year', '=', now()->year)
                    ->where('lpu.month', '=', now()->month);
            });
        }

        $query->select([
                $hasLfpFundSourceColumn
                    ? 'lfp.fund_source as lfp_fund_source'
                    : DB::raw('NULL as lfp_fund_source'),
                'spp.program as spp_program',
                'spp.status as spp_status',
                $hasLfpAllocationColumn
                    ? 'lfp.lgsf_allocation as lfp_lgsf_allocation'
                    : DB::raw('NULL as lfp_lgsf_allocation'),
                'spp.national_subsidy_original_allocation',
                $hasLfpObligationColumn
                    ? 'lfp.obligation as lfp_obligation'
                    : DB::raw('NULL as lfp_obligation'),
                'spp.obligation as spp_obligation',
                $hasLfpDisbursedAmountColumn
                    ? 'lfp.disbursed_amount as lfp_disbursed_amount'
                    : DB::raw('NULL as lfp_disbursed_amount'),
                'spp.disbursement as spp_disbursed_amount',
                $hasLfpRevertedAmountColumn
                    ? 'lfp.reverted_amount as lfp_reverted_amount'
                    : DB::raw('NULL as lfp_reverted_amount'),
                'spp.liquidations as spp_reverted_amount',
                $hasLfpUpdatedAtColumn
                    ? 'lfp.updated_at as lfp_updated_at'
                    : DB::raw('NULL as lfp_updated_at'),
                'spp.updated_at as spp_updated_at',
            ]);

        if ($hasPhysicalUpdatesTable) {
            $query->addSelect('lpu.status_project_ro as status_project_ro');
        } else {
            $query->addSelect(DB::raw('NULL as status_project_ro'));
        }

        $rows = $query->get();

        $fundSourceCountsMap = [];
        $statusCountsMap = array_fill_keys($statusDisplayOrder, 0);

        $allocation = 0.0;
        $obligation = 0.0;
        $disbursement = 0.0;
        $reverted = 0.0;
        $latestUpdatedAt = null;

        foreach ($rows as $row) {
            $fundSource = $normalizeFundSource($row->lfp_fund_source ?: $row->spp_program);
            $fundSourceCountsMap[$fundSource] = ($fundSourceCountsMap[$fundSource] ?? 0) + 1;

            $statusLabel = $normalizeStatus($row->status_project_ro ?: $row->spp_status);
            if ($statusLabel !== null) {
                $statusCountsMap[$statusLabel] = ($statusCountsMap[$statusLabel] ?? 0) + 1;
            }

            $allocation += $toFloat($row->lfp_lgsf_allocation ?? $row->national_subsidy_original_allocation);
            $obligation += $toFloat($row->lfp_obligation ?? $row->spp_obligation);
            $disbursement += $toFloat($row->lfp_disbursed_amount ?? $row->spp_disbursed_amount);
            $reverted += $toFloat($row->lfp_reverted_amount ?? $row->spp_reverted_amount);

            $candidateUpdatedAt = $row->lfp_updated_at ?: $row->spp_updated_at;
            if ($candidateUpdatedAt) {
                if ($latestUpdatedAt === null || strtotime((string) $candidateUpdatedAt) > strtotime((string) $latestUpdatedAt)) {
                    $latestUpdatedAt = $candidateUpdatedAt;
                }
            }
        }

        $orderedFundSources = [];
        foreach ($fundSourceOrder as $fundSource) {
            $count = (int) ($fundSourceCountsMap[$fundSource] ?? 0);
            if ($count > 0) {
                $orderedFundSources[] = [
                    'fund_source' => $fundSource,
                    'count' => $count,
                ];
            }
            unset($fundSourceCountsMap[$fundSource]);
        }

        ksort($fundSourceCountsMap);
        foreach ($fundSourceCountsMap as $fundSource => $count) {
            $intCount = (int) $count;
            if ($intCount > 0) {
                $orderedFundSources[] = [
                    'fund_source' => (string) $fundSource,
                    'count' => $intCount,
                ];
            }
        }

        $statusRows = collect($statusCountsMap)
            ->map(function ($count, $status) {
                return [
                    'status' => (string) $status,
                    'count' => (int) $count,
                ];
            })
            ->filter(fn ($row) => $row['count'] > 0)
            ->sort(function ($left, $right) use ($statusDisplayOrder) {
                if ($left['count'] !== $right['count']) {
                    return $right['count'] <=> $left['count'];
                }

                return array_search($left['status'], $statusDisplayOrder, true) <=> array_search($right['status'], $statusDisplayOrder, true);
            })
            ->values()
            ->all();

        $statusTotal = array_sum(array_column($statusRows, 'count'));
        $statusMax = count($statusRows) > 0 ? max(array_column($statusRows, 'count')) : 0;
        $balance = $allocation - ($disbursement + $reverted);
        $utilizationRate = $allocation > 0 ? (($disbursement + $reverted) / $allocation) * 100 : 0.0;

        return response()->json([
            'data' => [
                'total_projects' => count($rows),
                'latest_updated_at' => $latestUpdatedAt,
                'fund_source_counts' => $orderedFundSources,
                'status_subaybayan_rows' => $statusRows,
                'status_subaybayan_total' => $statusTotal,
                'status_subaybayan_max' => $statusMax,
                'financial' => [
                    'allocation' => (float) $allocation,
                    'obligation' => (float) $obligation,
                    'disbursement' => (float) $disbursement,
                    'reverted' => (float) $reverted,
                    'balance' => (float) $balance,
                    'utilization_rate' => (float) $utilizationRate,
                ],
            ],
        ]);
    }

    public function mobileExpectedCompletionThisMonth()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthLabel = now()->format('F Y');

        if (!Schema::hasTable('subay_project_profiles')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'month_label' => $monthLabel,
                ],
            ]);
        }

        $query = DB::table('subay_project_profiles as spp')
            ->leftJoin('locally_funded_projects as lfp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
            ->whereNotNull('spp.project_code')
            ->whereRaw('TRIM(COALESCE(spp.project_code, "")) <> ""')
            ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
            ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
            ->selectRaw('TRIM(COALESCE(lfp.project_name, spp.project_title, "")) as project_title')
            ->selectRaw('TRIM(COALESCE(lfp.province, spp.province, "")) as province')
            ->selectRaw('TRIM(COALESCE(lfp.city_municipality, spp.city_municipality, "")) as city_municipality')
            ->selectRaw('lfp.target_date_completion as lfp_target_date_completion')
            ->selectRaw('lfp.revised_target_date_completion as lfp_revised_target_date_completion')
            ->selectRaw('spp.intended_completion_date as spp_intended_completion_date')
            ->selectRaw('spp.intended_completion_date_2 as spp_intended_completion_date_2')
            ->selectRaw('spp.date_of_expiration_of_contract as spp_date_of_expiration_of_contract');

        $parseCompletionDate = function ($value): ?Carbon {
            $raw = trim((string) ($value ?? ''));
            if ($raw === '') {
                return null;
            }

            if (is_numeric($raw)) {
                try {
                    return Carbon::createFromDate(1899, 12, 30)->addDays((int) floor((float) $raw))->startOfDay();
                } catch (\Exception $e) {
                    return null;
                }
            }

            try {
                return Carbon::parse($raw)->startOfDay();
            } catch (\Exception $e) {
                return null;
            }
        };

        $rows = $query->get()
            ->map(function ($row) use ($parseCompletionDate, $currentMonth, $currentYear) {
                $completionDate = $parseCompletionDate(
                    $row->lfp_revised_target_date_completion
                        ?: $row->lfp_target_date_completion
                        ?: $row->spp_intended_completion_date
                        ?: $row->spp_intended_completion_date_2
                        ?: $row->spp_date_of_expiration_of_contract
                        ?: null
                );

                if (!$completionDate || (int) $completionDate->month !== (int) $currentMonth || (int) $completionDate->year !== (int) $currentYear) {
                    return null;
                }

                return [
                    'project_code' => $row->project_code,
                    'project_title' => $row->project_title !== '' ? $row->project_title : $row->project_code,
                    'province' => $row->province !== '' ? $row->province : null,
                    'city_municipality' => $row->city_municipality !== '' ? $row->city_municipality : null,
                    'expected_completion_date' => $completionDate->format('M d, Y'),
                    'expected_completion_date_iso' => $completionDate->toDateString(),
                ];
            })
            ->filter()
            ->sortBy([
                ['expected_completion_date_iso', 'asc'],
                ['project_code', 'asc'],
                ['project_title', 'asc'],
            ])
            ->values()
            ->map(function ($row) {
                unset($row['expected_completion_date_iso']);
                return $row;
            });

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => (int) $rows->count(),
                'month_label' => $monthLabel,
            ],
        ]);
    }

    public function mobileAggregatedDashboard()
    {
        $cacheKey = 'mobile_dashboard_aggregate_v1';
        $cacheMinutes = 60;

        // Try to return cached data
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        // Build all dashboard data
        $fundSourceOrder = ['SBDP', 'FALGU', 'CMGP', 'GEF', 'SAFPB'];
        $statusDisplayOrder = [
            'Completed',
            'On-going',
            'Bid Evaluation/Opening',
            'NOA Issuance',
            'DED Preparation',
            'Not Yet Started',
            'ITB/AD Posted',
            'Terminated',
            'Cancelled',
        ];

        $statusLabelByNormalized = [
            'COMPLETED' => 'Completed',
            'ONGOING' => 'On-going',
            'BID EVALUATION/OPENING' => 'Bid Evaluation/Opening',
            'NOA ISSUANCE' => 'NOA Issuance',
            'DED PREPARATION' => 'DED Preparation',
            'NOT YET STARTED' => 'Not Yet Started',
            'ITB/AD POSTED' => 'ITB/AD Posted',
            'TERMINATED' => 'Terminated',
            'CANCELLED' => 'Cancelled',
        ];

        $statusAliases = [
            'ON-GOING' => 'ONGOING',
            'NOT STARTED' => 'NOT YET STARTED',
        ];

        $normalizeFundSource = static function ($value): string {
            $normalized = strtoupper(trim((string) ($value ?? '')));
            if ($normalized === '') {
                return 'UNSPECIFIED';
            }
            if (str_starts_with($normalized, 'FALGU')) {
                return 'FALGU';
            }
            if (str_starts_with($normalized, 'CMGP')) {
                return 'CMGP';
            }
            if (str_starts_with($normalized, 'SBDP')) {
                return 'SBDP';
            }
            if (str_starts_with($normalized, 'GEF')) {
                return 'GEF';
            }
            if (str_starts_with($normalized, 'SAFPB')) {
                return 'SAFPB';
            }
            return $normalized;
        };

        $normalizeStatus = static function ($value) use ($statusAliases, $statusLabelByNormalized): ?string {
            $raw = trim((string) ($value ?? ''));
            if ($raw === '') {
                return null;
            }
            $upper = strtoupper($raw);
            $normalized = $statusAliases[$upper] ?? $upper;
            return $statusLabelByNormalized[$normalized] ?? null;
        };

        $toFloat = static function ($value): float {
            if ($value === null) {
                return 0.0;
            }
            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return 0.0;
            }
            $clean = preg_replace('/[^0-9\.-]/', '', $stringValue);
            if ($clean === null || $clean === '' || $clean === '-' || $clean === '.') {
                return 0.0;
            }
            return (float) $clean;
        };

        // ===== 1. DASHBOARD SUMMARY DATA =====
        $hasLfpFundSourceColumn = Schema::hasColumn('locally_funded_projects', 'fund_source');
        $hasLfpAllocationColumn = Schema::hasColumn('locally_funded_projects', 'lgsf_allocation');
        $hasLfpObligationColumn = Schema::hasColumn('locally_funded_projects', 'obligation');
        $hasLfpDisbursedAmountColumn = Schema::hasColumn('locally_funded_projects', 'disbursed_amount');
        $hasLfpRevertedAmountColumn = Schema::hasColumn('locally_funded_projects', 'reverted_amount');
        $hasLfpUpdatedAtColumn = Schema::hasColumn('locally_funded_projects', 'updated_at');
        $hasPhysicalUpdatesTable = Schema::hasTable('locally_funded_physical_updates');

        $query = DB::table('subay_project_profiles as spp')
            ->leftJoin('locally_funded_projects as lfp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
            ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%']);

        if ($hasPhysicalUpdatesTable) {
            $query->leftJoin('locally_funded_physical_updates as lpu', function ($join) {
                $join->on('lpu.project_id', '=', 'lfp.id')
                    ->where('lpu.year', '=', now()->year)
                    ->where('lpu.month', '=', now()->month);
            });
        }

        $query->select([
            $hasLfpFundSourceColumn ? 'lfp.fund_source as lfp_fund_source' : DB::raw('NULL as lfp_fund_source'),
            'spp.program as spp_program',
            'spp.status as spp_status',
            $hasLfpAllocationColumn ? 'lfp.lgsf_allocation as lfp_lgsf_allocation' : DB::raw('NULL as lfp_lgsf_allocation'),
            'spp.national_subsidy_original_allocation',
            $hasLfpObligationColumn ? 'lfp.obligation as lfp_obligation' : DB::raw('NULL as lfp_obligation'),
            'spp.obligation as spp_obligation',
            $hasLfpDisbursedAmountColumn ? 'lfp.disbursed_amount as lfp_disbursed_amount' : DB::raw('NULL as lfp_disbursed_amount'),
            'spp.disbursement as spp_disbursed_amount',
            $hasLfpRevertedAmountColumn ? 'lfp.reverted_amount as lfp_reverted_amount' : DB::raw('NULL as lfp_reverted_amount'),
            'spp.liquidations as spp_reverted_amount',
            $hasLfpUpdatedAtColumn ? 'lfp.updated_at as lfp_updated_at' : DB::raw('NULL as lfp_updated_at'),
            'spp.updated_at as spp_updated_at',
        ]);

        if ($hasPhysicalUpdatesTable) {
            $query->addSelect('lpu.status_project_ro as status_project_ro');
        } else {
            $query->addSelect(DB::raw('NULL as status_project_ro'));
        }

        $rows = $query->get();

        $fundSourceCountsMap = [];
        $statusCountsMap = array_fill_keys($statusDisplayOrder, 0);
        $allocation = 0.0;
        $obligation = 0.0;
        $disbursement = 0.0;
        $reverted = 0.0;
        $latestUpdatedAt = null;

        foreach ($rows as $row) {
            $fundSource = $normalizeFundSource($row->lfp_fund_source ?: $row->spp_program);
            $fundSourceCountsMap[$fundSource] = ($fundSourceCountsMap[$fundSource] ?? 0) + 1;

            $statusLabel = $normalizeStatus($row->status_project_ro ?: $row->spp_status);
            if ($statusLabel !== null) {
                $statusCountsMap[$statusLabel] = ($statusCountsMap[$statusLabel] ?? 0) + 1;
            }

            $allocation += $toFloat($row->lfp_lgsf_allocation ?? $row->national_subsidy_original_allocation);
            $obligation += $toFloat($row->lfp_obligation ?? $row->spp_obligation);
            $disbursement += $toFloat($row->lfp_disbursed_amount ?? $row->spp_disbursed_amount);
            $reverted += $toFloat($row->lfp_reverted_amount ?? $row->spp_reverted_amount);

            $candidateUpdatedAt = $row->lfp_updated_at ?: $row->spp_updated_at;
            if ($candidateUpdatedAt) {
                if ($latestUpdatedAt === null || strtotime((string) $candidateUpdatedAt) > strtotime((string) $latestUpdatedAt)) {
                    $latestUpdatedAt = $candidateUpdatedAt;
                }
            }
        }

        $orderedFundSources = [];
        foreach ($fundSourceOrder as $fundSource) {
            $count = (int) ($fundSourceCountsMap[$fundSource] ?? 0);
            if ($count > 0) {
                $orderedFundSources[] = ['fund_source' => $fundSource, 'count' => $count];
            }
            unset($fundSourceCountsMap[$fundSource]);
        }

        ksort($fundSourceCountsMap);
        foreach ($fundSourceCountsMap as $fundSource => $count) {
            $intCount = (int) $count;
            if ($intCount > 0) {
                $orderedFundSources[] = ['fund_source' => (string) $fundSource, 'count' => $intCount];
            }
        }

        $statusRows = collect($statusCountsMap)
            ->map(function ($count, $status) {
                return ['status' => (string) $status, 'count' => (int) $count];
            })
            ->filter(fn ($row) => $row['count'] > 0)
            ->sort(function ($left, $right) use ($statusDisplayOrder) {
                if ($left['count'] !== $right['count']) {
                    return $right['count'] <=> $left['count'];
                }
                return array_search($left['status'], $statusDisplayOrder, true) <=> array_search($right['status'], $statusDisplayOrder, true);
            })
            ->values()
            ->all();

        $statusTotal = array_sum(array_column($statusRows, 'count'));
        $statusMax = count($statusRows) > 0 ? max(array_column($statusRows, 'count')) : 0;
        $balance = $allocation - ($disbursement + $reverted);
        $utilizationRate = $allocation > 0 ? (($disbursement + $reverted) / $allocation) * 100 : 0.0;

        // ===== 2. PROJECT AT RISK - SLIPPAGE SUMMARY =====
        $slippageSummaryOrder = ['On Schedule', 'Ahead', 'No Risk', 'Low Risk', 'Moderate Risk', 'High Risk'];
        $slippageCounts = [];

        if (Schema::hasTable('project_at_risks')) {
            $riskBaseQuery = DB::table('project_at_risks as par')
                ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                ->selectRaw('TRIM(COALESCE(par.risk_level, "")) as risk_level_value')
                ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                ->addSelect('par.id')
                ->whereNotNull('par.project_code')
                ->whereRaw('TRIM(par.project_code) <> ""');

            $latestExtractionByProject = DB::query()
                ->fromSub($riskBaseQuery, 'risk_base')
                ->selectRaw('risk_base.project_code')
                ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                ->groupBy('risk_base.project_code');

            $latestRowsByExtraction = DB::query()
                ->fromSub($riskBaseQuery, 'risk_base')
                ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                    $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                        ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                })
                ->select('risk_base.project_code', 'risk_base.id', 'risk_base.risk_level_value');

            $latestIdByProject = DB::query()
                ->fromSub($latestRowsByExtraction, 'risk_rows')
                ->selectRaw('risk_rows.project_code')
                ->selectRaw('MAX(risk_rows.id) as latest_id')
                ->groupBy('risk_rows.project_code');

            $finalRiskRows = DB::query()
                ->fromSub($riskBaseQuery, 'risk_base')
                ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                    $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                        ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                })
                ->select('risk_base.risk_level_value')
                ->get();

            $slippageCounts = array_fill_keys($slippageSummaryOrder, 0);
            foreach ($finalRiskRows as $row) {
                $riskLabel = $this->normalizeRiskLevel($row->risk_level_value ?? null);
                if ($riskLabel !== null && array_key_exists($riskLabel, $slippageCounts)) {
                    $slippageCounts[$riskLabel] += 1;
                }
            }
        } else {
            $slippageCounts = array_fill_keys($slippageSummaryOrder, 0);
        }

        $slippageRows = collect($slippageSummaryOrder)->map(function ($label) use ($slippageCounts) {
            return ['label' => $label, 'count' => (int) ($slippageCounts[$label] ?? 0)];
        })->values()->all();

        // ===== 3. PROJECT AT RISK - AGING SUMMARY =====
        $agingSummaryOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $agingCounts = [];

        if (Schema::hasTable('project_at_risks')) {
            $riskBaseQuery = DB::table('project_at_risks as par')
                ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                ->selectRaw('TRIM(COALESCE(par.aging, "")) as aging_value')
                ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                ->addSelect('par.id')
                ->whereNotNull('par.project_code')
                ->whereRaw('TRIM(par.project_code) <> ""');

            $latestExtractionByProject = DB::query()
                ->fromSub($riskBaseQuery, 'risk_base')
                ->selectRaw('risk_base.project_code')
                ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                ->groupBy('risk_base.project_code');

            $latestRowsByExtraction = DB::query()
                ->fromSub($riskBaseQuery, 'risk_base')
                ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                    $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                        ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                })
                ->select('risk_base.project_code', 'risk_base.id', 'risk_base.aging_value');

            $latestIdByProject = DB::query()
                ->fromSub($latestRowsByExtraction, 'risk_rows')
                ->selectRaw('risk_rows.project_code')
                ->selectRaw('MAX(risk_rows.id) as latest_id')
                ->groupBy('risk_rows.project_code');

            $finalAgingRows = DB::query()
                ->fromSub($riskBaseQuery, 'risk_base')
                ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                    $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                        ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                })
                ->select('risk_base.aging_value')
                ->get();

            $agingCounts = array_fill_keys($agingSummaryOrder, 0);
            foreach ($finalAgingRows as $row) {
                $rawAging = trim((string) ($row->aging_value ?? ''));
                if ($rawAging === '') {
                    continue;
                }

                if (is_numeric($rawAging)) {
                    $agingValue = (float) $rawAging;
                } else {
                    $cleanedAging = preg_replace('/[^0-9\.\-]/', '', $rawAging);
                    if ($cleanedAging === null || $cleanedAging === '' || !is_numeric($cleanedAging)) {
                        continue;
                    }
                    $agingValue = (float) $cleanedAging;
                }

                if ($agingValue >= 60) {
                    $riskLabel = 'High Risk';
                } elseif ($agingValue > 30 && $agingValue < 60) {
                    $riskLabel = 'Low Risk';
                } else {
                    $riskLabel = 'No Risk';
                }

                if (array_key_exists($riskLabel, $agingCounts)) {
                    $agingCounts[$riskLabel] += 1;
                }
            }
        } else {
            $agingCounts = array_fill_keys($agingSummaryOrder, 0);
        }

        $agingRows = collect($agingSummaryOrder)->map(function ($label) use ($agingCounts) {
            return ['label' => $label, 'count' => (int) ($agingCounts[$label] ?? 0)];
        })->values()->all();

        // ===== 4. PROJECT UPDATE STATUS SUMMARY =====
        $updateStatusSummaryOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $updateStatusCounts = [];

        if (Schema::hasTable('subay_project_profiles')) {
            $projectUpdateRowsQuery = DB::table('subay_project_profiles as spp')
                ->whereNotNull('spp.project_code')
                ->whereRaw('TRIM(COALESCE(spp.project_code, "")) <> ""')
                ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
                ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
                ->selectRaw('LOWER(TRIM(COALESCE(spp.status, ""))) as status_raw')
                ->selectRaw($this->projectUpdateParsedDateExpression() . ' as latest_update_date');

            $aggregatedCounts = DB::query()
                ->fromSub($projectUpdateRowsQuery, 'project_updates')
                ->where('project_updates.status_raw', '!=', 'completed')
                ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) >= 60 THEN 1 ELSE 0 END) as high_risk_total')
                ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) > 30 AND DATEDIFF(CURDATE(), project_updates.latest_update_date) < 60 THEN 1 ELSE 0 END) as low_risk_total')
                ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) <= 30 THEN 1 ELSE 0 END) as no_risk_total')
                ->first();

            $updateStatusCounts['High Risk'] = (int) ($aggregatedCounts->high_risk_total ?? 0);
            $updateStatusCounts['Low Risk'] = (int) ($aggregatedCounts->low_risk_total ?? 0);
            $updateStatusCounts['No Risk'] = (int) ($aggregatedCounts->no_risk_total ?? 0);
        } else {
            $updateStatusCounts = array_fill_keys($updateStatusSummaryOrder, 0);
        }

        $updateStatusRows = collect($updateStatusSummaryOrder)->map(function ($label) use ($updateStatusCounts) {
            return ['label' => $label, 'count' => (int) ($updateStatusCounts[$label] ?? 0)];
        })->values()->all();

        // ===== 5. EXPECTED COMPLETION THIS MONTH =====
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthLabel = now()->format('F Y');
        $expectedCompletionData = [];

        if (Schema::hasTable('subay_project_profiles')) {
            $query = DB::table('subay_project_profiles as spp')
                ->leftJoin('locally_funded_projects as lfp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
                ->whereNotNull('spp.project_code')
                ->whereRaw('TRIM(COALESCE(spp.project_code, "")) <> ""')
                ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
                ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
                ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                ->selectRaw('TRIM(COALESCE(lfp.project_name, spp.project_title, "")) as project_title')
                ->selectRaw('TRIM(COALESCE(lfp.province, spp.province, "")) as province')
                ->selectRaw('TRIM(COALESCE(lfp.city_municipality, spp.city_municipality, "")) as city_municipality')
                ->selectRaw('lfp.target_date_completion as lfp_target_date_completion')
                ->selectRaw('lfp.revised_target_date_completion as lfp_revised_target_date_completion')
                ->selectRaw('spp.intended_completion_date as spp_intended_completion_date')
                ->selectRaw('spp.intended_completion_date_2 as spp_intended_completion_date_2')
                ->selectRaw('spp.date_of_expiration_of_contract as spp_date_of_expiration_of_contract');

            $parseCompletionDate = function ($value): ?Carbon {
                $raw = trim((string) ($value ?? ''));
                if ($raw === '') {
                    return null;
                }

                if (is_numeric($raw)) {
                    try {
                        return Carbon::createFromDate(1899, 12, 30)->addDays((int) floor((float) $raw))->startOfDay();
                    } catch (\Exception $e) {
                        return null;
                    }
                }

                try {
                    return Carbon::parse($raw)->startOfDay();
                } catch (\Exception $e) {
                    return null;
                }
            };

            $expectedCompletionData = $query->get()
                ->map(function ($row) use ($parseCompletionDate, $currentMonth, $currentYear) {
                    $completionDate = $parseCompletionDate(
                        $row->lfp_revised_target_date_completion
                            ?: $row->lfp_target_date_completion
                            ?: $row->spp_intended_completion_date
                            ?: $row->spp_intended_completion_date_2
                            ?: $row->spp_date_of_expiration_of_contract
                            ?: null
                    );

                    if (!$completionDate || (int) $completionDate->month !== (int) $currentMonth || (int) $completionDate->year !== (int) $currentYear) {
                        return null;
                    }

                    return [
                        'project_code' => $row->project_code,
                        'project_title' => $row->project_title !== '' ? $row->project_title : $row->project_code,
                        'province' => $row->province !== '' ? $row->province : null,
                        'city_municipality' => $row->city_municipality !== '' ? $row->city_municipality : null,
                        'expected_completion_date' => $completionDate->format('M d, Y'),
                    ];
                })
                ->filter()
                ->sortBy([
                    ['expected_completion_date', 'asc'],
                    ['project_code', 'asc'],
                    ['project_title', 'asc'],
                ])
                ->values()
                ->all();
        }

        // ===== BUILD RESPONSE =====
        $responseData = [
            'summary' => [
                'total_projects' => count($rows),
                'latest_updated_at' => $latestUpdatedAt,
                'fund_source_counts' => $orderedFundSources,
                'status_subaybayan_rows' => $statusRows,
                'status_subaybayan_total' => $statusTotal,
                'status_subaybayan_max' => $statusMax,
                'financial' => [
                    'allocation' => (float) $allocation,
                    'obligation' => (float) $obligation,
                    'disbursement' => (float) $disbursement,
                    'reverted' => (float) $reverted,
                    'balance' => (float) $balance,
                    'utilization_rate' => (float) $utilizationRate,
                ],
            ],
            'slippage' => [
                'data' => $slippageRows,
                'meta' => ['total' => (int) array_sum(array_column($slippageRows, 'count'))],
            ],
            'aging' => [
                'data' => $agingRows,
                'meta' => ['total' => (int) array_sum(array_column($agingRows, 'count'))],
            ],
            'update_status' => [
                'data' => $updateStatusRows,
                'meta' => ['total' => (int) array_sum(array_column($updateStatusRows, 'count'))],
            ],
            'expected_completion' => [
                'data' => $expectedCompletionData,
                'meta' => [
                    'total' => (int) count($expectedCompletionData),
                    'month_label' => $monthLabel,
                ],
            ],
        ];

        // Cache for 60 minutes
        Cache::put($cacheKey, $responseData, $cacheMinutes * 60);

        return response()->json($responseData);
    }

    private function normalizeRiskLevel($riskLevel): ?string
    {
        $raw = strtoupper(trim((string) $riskLevel));
        if ($raw === '') {
            return null;
        }

        $compact = preg_replace('/[^A-Z]/', '', $raw) ?? '';
        if ($compact === '') {
            return null;
        }

        if (str_contains($compact, 'AHEAD')) {
            return 'Ahead';
        }
        if (str_contains($compact, 'ONSCHEDULE')) {
            return 'On Schedule';
        }
        if (str_contains($compact, 'NORISK')) {
            return 'No Risk';
        }
        if (str_contains($compact, 'HIGHRISK')) {
            return 'High Risk';
        }
        if (str_contains($compact, 'MODERATERISK')) {
            return 'Moderate Risk';
        }
        if (str_contains($compact, 'LOWRISK')) {
            return 'Low Risk';
        }

        return null;
    }

    private function projectUpdateParsedDateExpression(): string
    {
        return "
            COALESCE(
                IF(
                    TRIM(COALESCE(spp.date, '')) REGEXP '^[0-9]+(\\\\.[0-9]+)?$',
                    DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp.date, '')) AS DECIMAL(12,4))) DAY),
                    NULL
                ),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%Y-%m-%d'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%Y-%m-%d %H:%i:%s'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %H:%i'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %H:%i:%s'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %h:%i:%s %p'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d/%m/%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d-%m-%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d-%b-%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%b %e, %Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%M %e, %Y')
            )
        ";
    }

    public function mobileIndex(Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $currentYear = now()->year;
        $currentMonth = now()->month;

        $query = DB::table('subay_project_profiles as spp')
            ->leftJoin('locally_funded_projects as lfp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
            ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%']);

        $this->applyLocallyFundedSourceScope(
            $query,
            'COALESCE(lfp.fund_source, spp.program)',
            'COALESCE(lfp.subaybayan_project_code, spp.project_code)'
        );

        $projectProvinceExpression = 'COALESCE(lfp.province, spp.province)';
        $projectCityExpression = 'COALESCE(lfp.city_municipality, spp.city_municipality)';
        $projectRegionExpression = 'COALESCE(lfp.region, spp.region)';

        $this->applyUserScopeToLocationQuery(
            $query,
            $projectProvinceExpression,
            $projectCityExpression,
            $projectRegionExpression
        );

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $keyword = '%' . strtolower($searchTerm) . '%';
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->whereRaw('LOWER(spp.project_code) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(spp.project_title) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(lfp.province, spp.province, ""))) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(lfp.city_municipality, spp.city_municipality, ""))) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(lfp.barangay, spp.barangay, ""))) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(lfp.fund_source, spp.program)) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(lfp.mode_of_procurement, spp.procurement_type, spp.procurement)) LIKE ?', [$keyword]);
            });
        }

        if (Schema::hasTable('locally_funded_physical_updates')) {
            $query->leftJoin('locally_funded_physical_updates as lpu', function ($join) use ($currentYear, $currentMonth) {
                $join->on('lpu.project_id', '=', 'lfp.id')
                    ->where('lpu.year', '=', $currentYear)
                    ->where('lpu.month', '=', $currentMonth);
            });
        }

        $hasLfpObligationColumn = Schema::hasColumn('locally_funded_projects', 'obligation');
        $hasLfpDisbursedAmountColumn = Schema::hasColumn('locally_funded_projects', 'disbursed_amount');
        $hasLfpRevertedAmountColumn = Schema::hasColumn('locally_funded_projects', 'reverted_amount');
        $hasLfpUtilizationRateColumn = Schema::hasColumn('locally_funded_projects', 'utilization_rate');

        $query->select([
            'lfp.id as lfp_id',
            'spp.project_code',
            'spp.project_title',
            'spp.province as spp_province',
            'spp.city_municipality as spp_city_municipality',
            'spp.barangay as spp_barangay',
            'spp.funding_year',
            'spp.program as spp_program',
            'spp.procurement_type',
            'spp.procurement',
            'spp.status',
            'spp.total_accomplishment',
            'spp.national_subsidy_original_allocation',
            'spp.obligation as spp_obligation',
            'spp.disbursement as spp_disbursed_amount',
            'spp.liquidations as spp_reverted_amount',
            'spp.updated_at as spp_updated_at',
            'lfp.project_name as lfp_project_name',
            'lfp.province as lfp_province',
            'lfp.city_municipality as lfp_city_municipality',
            'lfp.barangay as lfp_barangay',
            'lfp.fund_source as lfp_fund_source',
            'lfp.mode_of_procurement as lfp_mode_of_procurement',
            'lfp.lgsf_allocation as lfp_lgsf_allocation',
            'lfp.project_type as lfp_project_type',
            'lfp.date_nadai as lfp_date_nadai',
            'lfp.no_of_beneficiaries as lfp_no_of_beneficiaries',
            'lfp.rainwater_collection_system as lfp_rainwater_collection_system',
            'lfp.date_confirmation_fund_receipt as lfp_date_confirmation_fund_receipt',
            'lfp.lgu_counterpart as lfp_lgu_counterpart',
            'lfp.date_posting_itb as lfp_date_posting_itb',
            'lfp.date_bid_opening as lfp_date_bid_opening',
            'lfp.date_noa as lfp_date_noa',
            'lfp.date_ntp as lfp_date_ntp',
            'lfp.contractor as lfp_contractor',
            'lfp.contract_amount as lfp_contract_amount',
            'lfp.project_duration as lfp_project_duration',
            'lfp.actual_start_date as lfp_actual_start_date',
            'lfp.target_date_completion as lfp_target_date_completion',
            'lfp.revised_target_date_completion as lfp_revised_target_date_completion',
            'lfp.actual_date_completion as lfp_actual_date_completion',
            $hasLfpObligationColumn
                ? 'lfp.obligation as lfp_obligation'
                : DB::raw('NULL as lfp_obligation'),
            $hasLfpDisbursedAmountColumn
                ? 'lfp.disbursed_amount as lfp_disbursed_amount'
                : DB::raw('NULL as lfp_disbursed_amount'),
            $hasLfpRevertedAmountColumn
                ? 'lfp.reverted_amount as lfp_reverted_amount'
                : DB::raw('NULL as lfp_reverted_amount'),
            $hasLfpUtilizationRateColumn
                ? 'lfp.utilization_rate as lfp_utilization_rate'
                : DB::raw('NULL as lfp_utilization_rate'),
            'lfp.updated_at as lfp_updated_at',
        ]);

        if (Schema::hasTable('locally_funded_physical_updates')) {
            $query->addSelect([
                'lpu.status_project_fou as status_project_fou',
                'lpu.status_project_ro as status_project_ro',
                'lpu.accomplishment_pct as accomplishment_pct',
                'lpu.accomplishment_pct_ro as accomplishment_pct_ro',
                'lpu.slippage as slippage',
                'lpu.slippage_ro as slippage_ro',
                'lpu.risk_aging as risk_aging',
                'lpu.nc_letters as nc_letters',
            ]);
        } else {
            $query->addSelect([
                DB::raw('NULL as status_project_fou'),
                DB::raw('NULL as status_project_ro'),
                DB::raw('NULL as accomplishment_pct'),
                DB::raw('NULL as accomplishment_pct_ro'),
                DB::raw('NULL as slippage'),
                DB::raw('NULL as slippage_ro'),
                DB::raw('NULL as risk_aging'),
                DB::raw('NULL as nc_letters'),
            ]);
        }

        $projects = $query
            ->orderByRaw('COALESCE(lfp.updated_at, spp.updated_at) DESC')
            ->orderByDesc('spp.project_code')
            ->paginate($perPage)
            ->withQueryString();

        $parseNumber = function ($value) {
            if ($value === null) {
                return null;
            }

            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $clean = preg_replace('/[^0-9\\.-]/', '', $value);
            return $clean === '' ? null : (float) $clean;
        };

        $data = $projects->getCollection()->map(function ($row) use ($parseNumber, $currentYear, $currentMonth) {
            $allocation = $row->lfp_lgsf_allocation;
            if ($allocation === null) {
                $allocation = $parseNumber($row->national_subsidy_original_allocation);
            }

            $obligation = $row->lfp_obligation;
            if ($obligation === null) {
                $obligation = $parseNumber($row->spp_obligation ?? null);
            }

            $disbursedAmount = $row->lfp_disbursed_amount;
            if ($disbursedAmount === null) {
                $disbursedAmount = $parseNumber($row->spp_disbursed_amount ?? null);
            }

            $revertedAmount = $row->lfp_reverted_amount;
            if ($revertedAmount === null) {
                $revertedAmount = $parseNumber($row->spp_reverted_amount ?? null);
            }

            $utilizationRate = $row->lfp_utilization_rate;
            if ($utilizationRate === null && $allocation !== null) {
                $allocationFloat = (float) $allocation;
                if ($allocationFloat > 0) {
                    $utilizationRate = ((((float) ($disbursedAmount ?? 0)) + ((float) ($revertedAmount ?? 0))) / $allocationFloat) * 100;
                } else {
                    $utilizationRate = 0.0;
                }
            }

            $projectTitle = $row->lfp_project_name ?: ($row->project_title ?: $row->project_code);
            $modeOfProcurement = $row->lfp_mode_of_procurement ?: ($row->procurement_type ?: $row->procurement);
            $statusSubaybayan = $row->status_project_ro ?: $row->status;
            $subayAccomplishment = $row->accomplishment_pct_ro ?? $parseNumber($row->total_accomplishment);
            $monthName = now()->setMonth($currentMonth)->format('F');
            $currentPhysicalSeed = [
                'year' => (int) $currentYear,
                'month_number' => (int) $currentMonth,
                'month_label' => $monthName,
                'month_short' => substr($monthName, 0, 3),
                'status_project_fou' => $row->status_project_fou,
                'status_project_ro' => $statusSubaybayan,
                'accomplishment_pct' => $row->accomplishment_pct !== null ? (float) $row->accomplishment_pct : null,
                'accomplishment_pct_ro' => $subayAccomplishment !== null ? (float) $subayAccomplishment : null,
                'slippage' => $row->slippage !== null ? (float) $row->slippage : null,
                'slippage_ro' => $row->slippage_ro !== null ? (float) $row->slippage_ro : null,
                'risk_aging' => $row->risk_aging,
                'nc_letters' => $row->nc_letters,
                'has_data' => $row->status_project_fou !== null
                    || $statusSubaybayan !== null
                    || $row->accomplishment_pct !== null
                    || $subayAccomplishment !== null
                    || $row->slippage !== null
                    || $row->slippage_ro !== null
                    || $row->risk_aging !== null
                    || $row->nc_letters !== null,
            ];

            return [
                'lfp_id' => $row->lfp_id,
                'subaybayan_project_code' => $row->project_code,
                'project_name' => $projectTitle,
                'province' => $row->lfp_province ?: $row->spp_province,
                'city_municipality' => $row->lfp_city_municipality ?: $row->spp_city_municipality,
                'barangay' => $row->lfp_barangay ?: $row->spp_barangay,
                'funding_year' => $row->funding_year,
                'fund_source' => $row->lfp_fund_source ?: $row->spp_program,
                'mode_of_procurement' => $modeOfProcurement,
                'lgsf_allocation' => $allocation !== null ? (float) $allocation : null,
                'obligation' => $obligation !== null ? (float) $obligation : null,
                'disbursed_amount' => $disbursedAmount !== null ? (float) $disbursedAmount : null,
                'reverted_amount' => $revertedAmount !== null ? (float) $revertedAmount : null,
                'utilization_rate' => $utilizationRate !== null ? (float) $utilizationRate : null,
                'updated_at' => $row->lfp_updated_at ?: $row->spp_updated_at,
                'status_subaybayan' => $statusSubaybayan,
                'subay_accomplishment_pct' => $subayAccomplishment,
                'status_actual' => $row->status_project_fou,
                'status_subaybayan_current' => $statusSubaybayan,
                'accomplishment_pct_ro' => $row->accomplishment_pct_ro,
                'project_type' => $row->lfp_project_type,
                'date_nadai' => $row->lfp_date_nadai,
                'no_of_beneficiaries' => $row->lfp_no_of_beneficiaries,
                'rainwater_collection_system' => $row->lfp_rainwater_collection_system,
                'date_confirmation_fund_receipt' => $row->lfp_date_confirmation_fund_receipt,
                'lgu_counterpart' => $row->lfp_lgu_counterpart,
                'date_posting_itb' => $row->lfp_date_posting_itb,
                'date_bid_opening' => $row->lfp_date_bid_opening,
                'date_noa' => $row->lfp_date_noa,
                'date_ntp' => $row->lfp_date_ntp,
                'contractor' => $row->lfp_contractor,
                'contract_amount' => $row->lfp_contract_amount !== null ? (float) $row->lfp_contract_amount : null,
                'project_duration' => $row->lfp_project_duration,
                'actual_start_date' => $row->lfp_actual_start_date,
                'target_date_completion' => $row->lfp_target_date_completion,
                'revised_target_date_completion' => $row->lfp_revised_target_date_completion,
                'actual_date_completion' => $row->lfp_actual_date_completion,
                'current_physical_seed' => $currentPhysicalSeed,
            ];
        })->values();

        if (Schema::hasTable('locally_funded_physical_updates')) {
            $projectIds = $data
                ->pluck('lfp_id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($projectIds->isNotEmpty()) {
                $timelineRows = DB::table('locally_funded_physical_updates')
                    ->whereIn('project_id', $projectIds)
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get([
                        'project_id',
                        'year',
                        'month',
                        'status_project_fou',
                        'status_project_ro',
                        'accomplishment_pct',
                        'accomplishment_pct_ro',
                        'slippage',
                        'slippage_ro',
                        'risk_aging',
                        'nc_letters',
                    ]);

                $timelineByProject = $timelineRows->groupBy('project_id');

                $data = $data->map(function ($row) use ($timelineByProject) {
                    $projectTimelineRows = $timelineByProject->get((int) $row['lfp_id'], collect());

                    $timelineEntries = $projectTimelineRows->map(function ($timelineRow) {
                        $monthNumber = (int) $timelineRow->month;
                        $monthName = now()->setMonth($monthNumber)->format('F');

                        return [
                            'year' => (int) $timelineRow->year,
                            'month_number' => $monthNumber,
                            'month_label' => $monthName,
                            'month_short' => substr($monthName, 0, 3),
                            'status_project_fou' => $timelineRow->status_project_fou,
                            'status_project_ro' => $timelineRow->status_project_ro,
                            'accomplishment_pct' => $timelineRow->accomplishment_pct !== null ? (float) $timelineRow->accomplishment_pct : null,
                            'accomplishment_pct_ro' => $timelineRow->accomplishment_pct_ro !== null ? (float) $timelineRow->accomplishment_pct_ro : null,
                            'slippage' => $timelineRow->slippage !== null ? (float) $timelineRow->slippage : null,
                            'slippage_ro' => $timelineRow->slippage_ro !== null ? (float) $timelineRow->slippage_ro : null,
                            'risk_aging' => $timelineRow->risk_aging,
                            'nc_letters' => $timelineRow->nc_letters,
                            'has_data' => $timelineRow->status_project_fou !== null
                                || $timelineRow->status_project_ro !== null
                                || $timelineRow->accomplishment_pct !== null
                                || $timelineRow->accomplishment_pct_ro !== null
                                || $timelineRow->slippage !== null
                                || $timelineRow->slippage_ro !== null
                                || $timelineRow->risk_aging !== null
                                || $timelineRow->nc_letters !== null,
                        ];
                    })->values();

                    $fallbackPhysical = $row['current_physical_seed'] ?? null;

                    if ($timelineEntries->isEmpty() && is_array($fallbackPhysical) && ($fallbackPhysical['has_data'] ?? false)) {
                        $timelineEntries = collect([$fallbackPhysical]);
                    }

                    $currentPhysical = $timelineEntries->isNotEmpty()
                        ? $timelineEntries->last()
                        : $fallbackPhysical;

                    $row['physical_timeline'] = $timelineEntries->all();
                    $row['current_physical'] = $currentPhysical;
                    unset($row['current_physical_seed']);

                    return $row;
                })->values();
            }
        }

        $projectIds = $data
            ->pluck('lfp_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (Schema::hasTable('locally_funded_gallery_images') && $projectIds->isNotEmpty()) {
            $hasCategoryColumn = Schema::hasColumn('locally_funded_gallery_images', 'category');

            $selectColumns = [
                'lgi.id',
                'lgi.project_id',
                $hasCategoryColumn ? 'lgi.category' : DB::raw("'' as category"),
                'lgi.uploaded_by',
                'lgi.latitude',
                'lgi.longitude',
                'lgi.accuracy',
                'lgi.created_at',
                DB::raw("NULLIF(TRIM(CONCAT(COALESCE(uploader.fname, ''), ' ', COALESCE(uploader.lname, ''))), '') as uploaded_by_name"),
            ];

            $galleryRows = DB::table('locally_funded_gallery_images as lgi')
                ->leftJoin('tbusers as uploader', 'uploader.idno', '=', 'lgi.uploaded_by')
                ->whereIn('lgi.project_id', $projectIds)
                ->orderByDesc('lgi.created_at')
                ->get($selectColumns);

            $galleryByProject = $galleryRows
                ->groupBy('project_id')
                ->map(function ($rowsByProject) {
                    return $rowsByProject->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'category' => trim((string) ($row->category ?? '')),
                            'image_url' => route('api.mobile.locally-funded.gallery-image', [
                                'project' => (int) $row->project_id,
                                'galleryImage' => (int) $row->id,
                            ]),
                            'uploaded_by' => $row->uploaded_by !== null ? (int) $row->uploaded_by : null,
                            'uploaded_by_name' => $row->uploaded_by_name,
                            'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
                            'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
                            'accuracy' => $row->accuracy !== null ? (float) $row->accuracy : null,
                            'created_at' => $row->created_at,
                        ];
                    })->values()->all();
                });

            $data = $data->map(function ($row) use ($galleryByProject) {
                $projectId = (int) ($row['lfp_id'] ?? 0);
                $row['gallery_images'] = $projectId > 0
                    ? ($galleryByProject->get($projectId, []))
                    : [];

                return $row;
            })->values();
        } else {
            $data = $data->map(function ($row) {
                $row['gallery_images'] = [];

                return $row;
            })->values();
        }

        $filterOptions = $this->getProjectFormOptions();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'filters' => [
                    'funding_years' => array_values($filterOptions['fundingYears'] ?? []),
                    'fund_sources' => array_values($filterOptions['fundSources'] ?? []),
                    'provinces' => array_values($filterOptions['provinces'] ?? []),
                    'cities_by_province' => $filterOptions['provinceMunicipalities'] ?? [],
                    'procurement_types' => array_values($filterOptions['procurementTypes'] ?? []),
                    'statuses' => array_values($filterOptions['statusOptions'] ?? []),
                ],
            ],
        ]);
    }

    public function viewMobileGalleryImage(int $project, int $galleryImage)
    {
        if (!Schema::hasTable('locally_funded_gallery_images')) {
            abort(404, 'Gallery image table not found');
        }

        $image = DB::table('locally_funded_gallery_images')
            ->where('id', $galleryImage)
            ->where('project_id', $project)
            ->first(['image_path']);

        if (!$image || empty($image->image_path)) {
            abort(404, 'Gallery image not found');
        }

        $filePath = storage_path('app/public/' . $image->image_path);
        if (!is_file($filePath)) {
            abort(404, 'Gallery image file not found');
        }

        $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
        if (strpos($mimeType, 'image/') !== 0) {
            abort(403, 'Invalid gallery file type');
        }

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function mobileUploadGalleryImage(Request $request, LocallyFundedProject $project)
    {
        if (!Schema::hasTable('locally_funded_gallery_images')) {
            return response()->json([
                'message' => 'Gallery table is missing. Please run migrations first.',
            ], 500);
        }

        $categories = array_values(array_filter($this->locallyFundedGalleryCategories(), function (string $category): bool {
            return $category !== 'All';
        }));

        $validated = $request->validate([
            'gallery_category' => ['required', 'string', Rule::in($categories)],
            'gallery_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'uploaded_by' => ['nullable', 'integer', 'exists:tbusers,idno'],
        ]);

        $category = InputSanitizer::sanitizeNullablePlainText($validated['gallery_category']) ?? 'During';
        $imageFile = $request->file('gallery_image');
        if (!$imageFile || !$imageFile->isValid()) {
            return response()->json([
                'message' => 'No valid image was uploaded.',
            ], 422);
        }

        $now = now();
        $today = $now->toDateString();
        $existingCountForDay = DB::table('locally_funded_gallery_images')
            ->where('project_id', $project->id)
            ->where('category', $category)
            ->whereDate('created_at', $today)
            ->count();

        $sequence = $existingCountForDay + 1;
        $extension = $imageFile->getClientOriginalExtension() ?: $imageFile->extension() ?: 'jpg';
        $directory = 'lfp/gallery/' . $project->id;
        $fileName = $this->buildGalleryImageFileName($project, $category, $now, $sequence, $extension);

        while (Storage::disk('public')->exists($directory . '/' . $fileName)) {
            $sequence++;
            $fileName = $this->buildGalleryImageFileName($project, $category, $now, $sequence, $extension);
        }

        $storedPath = $imageFile->storeAs($directory, $fileName, 'public');

        $authenticatedUploaderId = Auth::id();
        $requestUploaderId = isset($validated['uploaded_by']) ? (int) $validated['uploaded_by'] : null;
        $uploaderId = $authenticatedUploaderId ? (int) $authenticatedUploaderId : $requestUploaderId;

        if (!$uploaderId) {
            return response()->json([
                'message' => 'Unable to determine uploader. Please sign in again and retry.',
            ], 422);
        }

        $insertData = [
            'project_id' => $project->id,
            'category' => $category,
            'image_path' => $storedPath,
            'uploaded_by' => $uploaderId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($validated['latitude'] !== null && $validated['longitude'] !== null) {
            $insertData['latitude'] = $validated['latitude'];
            $insertData['longitude'] = $validated['longitude'];
            if ($validated['accuracy'] !== null) {
                $insertData['accuracy'] = $validated['accuracy'];
            }
        }

        $galleryImageId = DB::table('locally_funded_gallery_images')->insertGetId($insertData);

        $this->logLocallyFundedActivity(
            $project,
            'upload',
            'Gallery',
            'Image',
            'Category: ' . $category . ' - Files: 1',
            $now,
            $uploaderId
        );

        $this->notifyLocallyFundedUpdateRecipients($project, 'uploaded Gallery images', true);

        $uploader = DB::table('tbusers')
            ->where('idno', $uploaderId)
            ->first(['fname', 'lname']);
        $uploaderName = trim((($uploader->fname ?? '') . ' ' . ($uploader->lname ?? '')));

        return response()->json([
            'message' => 'Gallery image uploaded successfully.',
            'data' => [
                'id' => (int) $galleryImageId,
                'category' => $category,
                'image_url' => route('api.mobile.locally-funded.gallery-image', [
                    'project' => (int) $project->id,
                    'galleryImage' => (int) $galleryImageId,
                ]),
                'uploaded_by' => $uploaderId,
                'uploaded_by_name' => $uploaderName !== '' ? $uploaderName : null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'accuracy' => $validated['accuracy'] ?? null,
                'created_at' => $now->toISOString(),
            ],
        ], 201);
    }

    private function comparableLocationSql(string $columnExpression): string
    {
        return "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(TRIM(COALESCE({$columnExpression}, '')), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
    }

    private function applyOfficeScopeToLocationQuery($query, string $cityColumnExpression, string $officeLower, string $officeComparableLower): void
    {
        if ($officeLower === '') {
            return;
        }

        $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;
        $cityComparableExpression = $this->comparableLocationSql($cityColumnExpression);

        $query->where(function ($subQuery) use ($cityColumnExpression, $officeLower, $officeNeedle, $cityComparableExpression) {
            $subQuery->whereRaw('LOWER(TRIM(COALESCE(' . $cityColumnExpression . ', ""))) = ?', [$officeLower])
                ->orWhereRaw("{$cityComparableExpression} = ?", [$officeNeedle]);
        });
    }

    private function applyUserScopeToLocationQuery($query, string $provinceColumnExpression, string $cityColumnExpression, string $regionColumnExpression): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user instanceof User || $user->isSuperAdmin()) {
            return;
        }

        $province = trim((string) $user->province);
        $office = trim((string) $user->office);
        $region = trim((string) $user->region);
        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $regionLower = $user->normalizedRegion();
        $officeComparableLower = $user->normalizedOfficeComparable();
        $isRegionalOfficeUser = $user->isRegionalUser() || $user->isRegionalOfficeAssignment();

        if ($user->isLguScopedUser()) {
            if ($office !== '') {
                if ($province !== '') {
                    $query->whereRaw('LOWER(TRIM(COALESCE(' . $provinceColumnExpression . ', ""))) = ?', [$provinceLower]);
                }

                $this->applyOfficeScopeToLocationQuery($query, $cityColumnExpression, $officeLower, $officeComparableLower);
                return;
            }

            if ($province !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(' . $provinceColumnExpression . ', ""))) = ?', [$provinceLower]);
            }

            return;
        }

        if ($user->isProvincialUser()) {
            if ($province !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(' . $provinceColumnExpression . ', ""))) = ?', [$provinceLower]);
            } elseif ($region !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(' . $regionColumnExpression . ', ""))) = ?', [$regionLower]);
            }

            return;
        }

        if ($user->isRegionalUser()) {
            return;
        }

        if (!$user->isDilgUser()) {
            return;
        }

        if ($isRegionalOfficeUser) {
            if ($region !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(' . $regionColumnExpression . ', ""))) = ?', [$regionLower]);
            }

            return;
        }

        if ($province !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(' . $provinceColumnExpression . ', ""))) = ?', [$provinceLower]);
        } elseif ($region !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(' . $regionColumnExpression . ', ""))) = ?', [$regionLower]);
        }
    }

    private function userCanAccessLocation(?User $user, ?string $province, ?string $city, ?string $region = null, ?string $office = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $recordProvinceLower = Str::lower(trim((string) $province));
        $recordRegionLower = Str::lower(trim((string) $region));
        $recordCity = trim((string) $city);
        $recordOffice = trim((string) $office);

        if ($user->isLguScopedUser()) {
            $assignedProvince = $user->normalizedProvince();
            if ($assignedProvince !== '' && $recordProvinceLower !== $assignedProvince) {
                return false;
            }

            if ($user->normalizedOffice() === '') {
                return $assignedProvince === '' || $recordProvinceLower === $assignedProvince;
            }

            return $user->matchesAssignedOffice($recordCity) || $user->matchesAssignedOffice($recordOffice);
        }

        if ($user->isProvincialUser()) {
            if ($user->normalizedProvince() !== '') {
                return $recordProvinceLower === $user->normalizedProvince();
            }

            if ($user->normalizedRegion() !== '') {
                return $recordRegionLower === $user->normalizedRegion();
            }

            return true;
        }

        if ($user->isRegionalUser()) {
            return true;
        }

        if (!$user->isDilgUser()) {
            return true;
        }

        if ($user->isRegionalOfficeAssignment()) {
            return $user->normalizedRegion() === '' || $recordRegionLower === $user->normalizedRegion();
        }

        if ($user->normalizedProvince() !== '') {
            return $recordProvinceLower === $user->normalizedProvince();
        }

        if ($user->normalizedRegion() !== '') {
            return $recordRegionLower === $user->normalizedRegion();
        }

        return true;
    }

    private function authorizeLocallyFundedProjectAccess(LocallyFundedProject $project): void
    {
        $province = $project->province;
        $cityMunicipality = $project->city_municipality;
        $region = $project->region;

        $projectCode = trim((string) $project->subaybayan_project_code);
        if ($projectCode !== '' && Schema::hasTable('subay_project_profiles')) {
            $subayRow = DB::table('subay_project_profiles')
                ->where('project_code', $projectCode)
                ->first(['province', 'city_municipality', 'region']);

            if ($subayRow) {
                $province = trim((string) $province) !== '' ? $province : ($subayRow->province ?? null);
                $cityMunicipality = trim((string) $cityMunicipality) !== ''
                    ? $cityMunicipality
                    : ($subayRow->city_municipality ?? null);
                $region = trim((string) $region) !== '' ? $region : ($subayRow->region ?? null);
            }
        }

        if (!$this->userCanAccessLocation(Auth::user(), $province, $cityMunicipality, $region, $project->office)) {
            abort(403);
        }
    }

    private function getProjectFormOptions(): array
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

        // Fund source and funding year options
        $fundSources = ['SBDP', 'FALGU', 'CMGP', 'GEF', 'SAFPB', 'SGLGIF'];
        $fundingYears = [2025, 2024, 2023, 2022, 2021];

        // Procurement types (mode of procurement)
        $procurementTypes = ['admin', 'contract'];
        $statusOptions = [
            'Completed',
            'On-going',
            'Bid Evaluation/Opening',
            'NOA Issuance',
            'DED Preparation',
            'Not Yet Started',
            'ITB/AD Posted',
            'Terminated',
            'Cancelled',
            'Pending',
        ];

        return compact('provinces', 'provinceMunicipalities', 'fundSources', 'fundingYears', 'procurementTypes', 'statusOptions');
    }

    private function isSglgifProjectCode(?string $projectCode): bool
    {
        return str_starts_with(strtoupper(trim((string) $projectCode)), 'SGLGIF');
    }

    private function isExcludedSglgifLocallyFundedProject(?string $fundSource, ?string $projectCode = null): bool
    {
        return $this->isSglgifProjectCode($projectCode)
            || strtoupper(trim((string) $fundSource)) === 'SGLGIF';
    }

    private function applyLocallyFundedSourceScope($query, string $sourceExpression, ?string $projectCodeExpression = null): void
    {
        if ($projectCodeExpression !== null) {
            $query->whereRaw('UPPER(TRIM(COALESCE(' . $projectCodeExpression . ', ""))) NOT LIKE ?', ['SGLGIF%']);
        }

        $query->where(function ($subQuery) use ($sourceExpression) {
            $subQuery->whereRaw('UPPER(TRIM(COALESCE(' . $sourceExpression . ', ""))) IN (?, ?, ?, ?)', [
                'SBDP',
                'CMGP',
                'GEF',
                'SAFPB',
            ])->orWhereRaw('UPPER(TRIM(COALESCE(' . $sourceExpression . ', ""))) LIKE ?', ['%FALGU%']);
        });
    }

    private function mergeCleanCurrencyInputs(Request $request): void
    {
        $currencyFields = ['lgsf_allocation', 'lgu_counterpart', 'contract_amount', 'disbursed_amount', 'obligation', 'reverted_amount', 'balance'];
        $cleaned = [];

        foreach ($currencyFields as $field) {
            if (!$request->has($field)) {
                continue;
            }

            $raw = $request->input($field);
            if (is_array($raw)) {
                continue;
            }

            $value = preg_replace('/[^0-9.]/', '', (string) $raw);
            if ($value === null) {
                continue;
            }

            if (substr_count($value, '.') > 1) {
                $firstDot = strpos($value, '.');
                $value = substr($value, 0, $firstDot + 1) . str_replace('.', '', substr($value, $firstDot + 1));
            }

            $cleaned[$field] = $value;
        }

        if (!empty($cleaned)) {
            $request->merge($cleaned);
        }
    }

    private function sanitizeLocallyFundedPayload(array $validated): array
    {
        $validated = InputSanitizer::sanitizeTextFields($validated, [
            'province',
            'city_municipality',
            'project_name',
            'fund_source',
            'subaybayan_project_code',
            'project_type',
            'mode_of_procurement',
            'implementing_unit',
            'contractor',
            'project_duration',
            'rssa_submission_status',
        ]);

        $validated = InputSanitizer::sanitizeTextFields($validated, [
            'rainwater_collection_system',
        ], false, true);

        $validated = InputSanitizer::sanitizeTextFields($validated, [
            'project_description',
        ], true);

        return InputSanitizer::sanitizeTextFields($validated, [
            'physical_remarks',
            'financial_remarks',
            'po_remarks',
            'ro_remarks',
            'pcr_remarks',
            'rssa_remarks',
        ], true, true);
    }

    private function parseBarangaySelection(?string $json): array
    {
        return InputSanitizer::decodeJsonStringArray($json, 100);
    }

    private function sanitizeLocallyFundedRemark(?string $value): ?string
    {
        return InputSanitizer::sanitizeNullablePlainText($value, true);
    }

    private function sanitizeLocallyFundedFieldValue(string $field, mixed $value): mixed
    {
        if (!is_scalar($value)) {
            return $value;
        }

        $textFields = ['status_project_fou', 'status_project_ro', 'risk_aging', 'nc_letters'];
        if (!in_array($field, $textFields, true)) {
            return $value;
        }

        return InputSanitizer::sanitizeNullablePlainText((string) $value);
    }

    private function ensureFundUtilizationReport(LocallyFundedProject $project): void
    {
        if (!Schema::hasTable('tbfur')) {
            return;
        }

        $projectCode = trim((string) $project->subaybayan_project_code);
        if ($projectCode === '' || $this->isExcludedSglgifLocallyFundedProject($project->fund_source, $projectCode)) {
            return;
        }

        $payload = [
            'province' => $project->province,
            'implementing_unit' => $project->implementing_unit,
            'barangay' => $project->barangay,
            'fund_source' => $project->fund_source,
            'funding_year' => $project->funding_year,
            'project_title' => $project->project_name,
            'allocation' => $project->lgsf_allocation,
            'contract_amount' => $project->contract_amount,
        ];

        $report = FundUtilizationReport::where('project_code', $projectCode)->first();
        if ($report) {
            $report->fill($payload);
            $report->save();
            return;
        }

        FundUtilizationReport::create(array_merge(
            ['project_code' => $projectCode, 'project_status' => 'Ongoing'],
            $payload
        ));
    }

    private function logLocallyFundedActivity(
        LocallyFundedProject $project,
        string $action,
        string $section,
        string $field,
        ?string $details = null,
        $timestamp = null,
        $userId = null
    ): void {
        if (!$project->id) {
            return;
        }

        try {
            $loggedAt = $timestamp instanceof \DateTimeInterface
                ? Carbon::instance($timestamp)
                : ($timestamp ? Carbon::parse((string) $timestamp) : now());
        } catch (\Throwable $e) {
            $loggedAt = now();
        }

        Log::channel('upload_timestamps')->info('Document action', [
            'module' => 'locally_funded',
            'project_id' => $project->id,
            'project_code' => $project->subaybayan_project_code,
            'action' => $action,
            'action_label' => ucfirst($action),
            'section' => $section,
            'field' => $field,
            'details' => $details,
            'action_timestamp' => $loggedAt->format('Y-m-d H:i:s'),
            'user_id' => $userId ?: Auth::id(),
        ]);
    }

    private function notifyLocallyFundedUpdateRecipients(
        LocallyFundedProject $project,
        string $activityLabel,
        bool $notifyProvinceDilgForLgu = false
    ): void {
        try {
            if (!Schema::hasTable('tbnotifications')) {
                return;
            }

            /** @var User|null $actor */
            $actor = Auth::user();
            if (!$actor instanceof User) {
                return;
            }

            $actorId = (int) Auth::id();
            $actorName = trim((string) ($actor->fname ?? '') . ' ' . (string) ($actor->lname ?? ''));
            if ($actorName === '') {
                $actorName = 'A user';
            }

            $projectCode = trim((string) ($project->subaybayan_project_code ?? ''));
            if ($projectCode === '') {
                $projectCode = 'Project #' . $project->id;
            }

            $projectTitle = trim((string) ($project->project_name ?? ''));
            $projectProvince = trim((string) ($project->province ?? ''));

            $projectDescriptor = $projectCode;
            if ($projectTitle !== '') {
                $projectDescriptor .= ' (' . $projectTitle . ')';
            }
            if ($projectProvince !== '') {
                $projectDescriptor .= ' - ' . $projectProvince;
            }

            $targetProvince = trim((string) ($actor->province ?? ''));
            if ($targetProvince === '') {
                $targetProvince = $projectProvince;
            }

$url = route('locally-funded-project.show', $project, false);
            $notificationService = app(InterventionNotificationService::class);

            if ($actor->isLguScopedUser() && $targetProvince !== '') {
                $message = sprintf(
                    '%s %s for %s and it is awaiting DILG Provincial Office review.',
                    $actorName,
                    trim($activityLabel),
                    $projectDescriptor
                );

                $notificationService->notifyProvincialDilg(
                    $targetProvince,
                    $actorId,
                    $message,
                    $url,
                    'locally-funded-update'
                );

                return;
            }

            if ($actor->isDilgUser() && !$actor->isRegionalOfficeAssignment()) {
                $message = sprintf(
                    '%s %s for %s and it is awaiting DILG Regional Office review.',
                    $actorName,
                    trim($activityLabel),
                    $projectDescriptor
                );

                $notificationService->notifyRegionalDilg(
                    $actorId,
                    $message,
                    $url,
                    'locally-funded-update'
                );
            }
        } catch (\Throwable $error) {
            Log::warning('Failed to create locally funded update notifications.', [
                'project_id' => $project->id ?? null,
                'project_code' => $project->subaybayan_project_code ?? null,
                'activity_label' => $activityLabel,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function parseLocallyFundedPersistedLog(string $line, int $projectId): ?array
    {
        $pattern = '/^\[([^\]]+)\]\s+[^\:]+\.\w+:\s+([^{]+)\s*(\{.*)/';
        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $loggedAt = trim($matches[1]);
        $contextJson = $matches[3];
        $context = json_decode($contextJson, true);

        if (!is_array($context)) {
            return null;
        }

        if (($context['module'] ?? null) !== 'locally_funded') {
            return null;
        }

        if ((int) ($context['project_id'] ?? 0) !== $projectId) {
            return null;
        }

        $timestampRaw = $context['action_timestamp'] ?? $loggedAt;
        try {
            $timestamp = Carbon::parse($timestampRaw)->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            $timestamp = Carbon::parse($loggedAt)->setTimezone(config('app.timezone'));
        }

        return [
            'timestamp' => $timestamp,
            'user_id' => $context['user_id'] ?? null,
            'action' => $context['action'] ?? 'update',
            'section' => $context['section'] ?? 'General',
            'field' => $context['field'] ?? 'Updated',
            'details' => $context['details'] ?? null,
        ];
    }

    private function getPersistedLocallyFundedLogs(LocallyFundedProject $project): array
    {
        if (!$project->id) {
            return [];
        }

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

            $logEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\])/', $content, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($logEntries as $logEntry) {
                $logEntry = trim($logEntry);
                if ($logEntry === '' || strpos($logEntry, '"module":"locally_funded"') === false) {
                    continue;
                }

                $parsed = $this->parseLocallyFundedPersistedLog($logEntry, (int) $project->id);
                if ($parsed) {
                    $entries[] = $parsed;
                }
            }
        }

        return $entries;
    }

    private function mergeLocallyFundedActivityLogs(array $activityLogs, LocallyFundedProject $project): array
    {
        $persistedLogs = $this->getPersistedLocallyFundedLogs($project);

        if (empty($persistedLogs)) {
            return $activityLogs;
        }

        // Persisted logs are append-only history; keep all of them.
        // Add current-state fallback entries only when not already in persisted history.
        $merged = $persistedLogs;

        foreach ($activityLogs as $currentLog) {
            if (empty($currentLog['timestamp']) || !($currentLog['timestamp'] instanceof \DateTimeInterface)) {
                continue;
            }

            $existsInPersisted = false;
            foreach ($persistedLogs as $persistedLog) {
                if (empty($persistedLog['timestamp']) || !($persistedLog['timestamp'] instanceof \DateTimeInterface)) {
                    continue;
                }

                if (
                    $currentLog['timestamp']->getTimestamp() === $persistedLog['timestamp']->getTimestamp()
                    && (string) ($currentLog['user_id'] ?? '') === (string) ($persistedLog['user_id'] ?? '')
                    && ($currentLog['section'] ?? '') === ($persistedLog['section'] ?? '')
                    && ($currentLog['field'] ?? '') === ($persistedLog['field'] ?? '')
                    && (string) ($currentLog['details'] ?? '') === (string) ($persistedLog['details'] ?? '')
                ) {
                    $existsInPersisted = true;
                    break;
                }
            }

            if (!$existsInPersisted) {
                $merged[] = $currentLog;
            }
        }

        return $merged;
    }

    private function formatLocallyFundedActivityValue(string $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($field, ['obligation', 'disbursed_amount', 'reverted_amount'], true)) {
            return '₱ ' . number_format((float) $value, 2);
        }

        if (in_array($field, ['accomplishment_pct', 'accomplishment_pct_ro', 'slippage', 'slippage_ro', 'utilization_rate'], true)) {
            return number_format((float) $value, 2) . '%';
        }

        return (string) $value;
    }

    private function syncMissingFundUtilizationReports(): void
    {
        if (!Schema::hasTable('tbfur')) {
            return;
        }

        $now = now();

        LocallyFundedProject::query()
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbfur')
                    ->whereColumn('tbfur.project_code', 'locally_funded_projects.subaybayan_project_code');
            })
            ->whereRaw('UPPER(TRIM(COALESCE(fund_source, ""))) <> ?', ['SGLGIF'])
            ->whereRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
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
    }

    /**
     * Display a listing of locally funded projects
     */
    public function index()
    {
        // Cache key: user + filters (5min TTL)
        $userId = Auth::id();
        $filterHash = md5(serialize(request()->only(['search', 'project_code', 'funding_year', 'fund_source', 'province', 'city', 'barangay', 'procurement', 'status', 'project_update_status', 'per_page', 'sort_by', 'sort_dir'])));
        $cacheKey = "lfp_index:{$userId}:{$filterHash}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cachedViewData = $cached['view_data'] ?? [];
            if (!is_array($cachedViewData)) {
                $cachedViewData = [];
            }

            $cachedOptions = $cachedViewData['options'] ?? [];
            if (!is_array($cachedOptions)) {
                $cachedOptions = [];
            }

            return view('projects.locally-funded', array_merge($cachedOptions, $cachedViewData));
        }

        $this->syncMissingFundUtilizationReports();

        $listRouteName = 'projects.locally-funded';
        $activeProjectTab = 'locally-funded';
        $pageTitle = 'Locally Funded Projects';
        $pageDescription = 'Manage and review locally funded project records.';
        $tableTitle = 'Projects';
        $forceFundSource = '';
        $currentYear = now()->year;
        $currentMonth = now()->month;

        if (!Schema::hasTable('subay_project_profiles')) {
            $options = $this->getProjectFormOptions();

            return view('projects.locally-funded', array_merge(
                $options,
                [
                    'projects' => collect(),
                    'physicalStatuses' => [],
                    'listRouteName' => $listRouteName,
                    'activeProjectTab' => $activeProjectTab,
                    'pageTitle' => $pageTitle,
                    'pageDescription' => $pageDescription,
                    'tableTitle' => $tableTitle,
                    'forceFundSource' => $forceFundSource,
                ]
            ));
        }

        $parsedSubayDateExpression = "
            COALESCE(
                IF(
                    TRIM(COALESCE(spp_date.date, '')) REGEXP '^[0-9]+(\\.[0-9]+)?$',
                    DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp_date.date, '')) AS DECIMAL(12,4))) DAY),
                    NULL
                ),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%Y-%m-%d'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%Y-%m-%d %H:%i:%s'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%m/%d/%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%m/%d/%Y %H:%i'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%m/%d/%Y %H:%i:%s'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%m/%d/%Y %h:%i:%s %p'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%m/%d/%y'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%d/%m/%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%d-%m-%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%d-%b-%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%b %e, %Y'),
                STR_TO_DATE(TRIM(COALESCE(spp_date.date, '')), '%M %e, %Y')
            )
        ";

        $latestSubayUpdateByProjectQuery = DB::table('subay_project_profiles as spp_date')
            ->selectRaw('UPPER(TRIM(spp_date.project_code)) as project_code_key')
            ->selectRaw("MAX({$parsedSubayDateExpression}) as latest_update_date")
            ->whereNotNull('spp_date.project_code')
            ->whereRaw('TRIM(spp_date.project_code) <> ""')
            ->groupBy(DB::raw('UPPER(TRIM(spp_date.project_code))'));

        // Build query from SubayBAYAN data with role-based filtering
        $query = DB::table('subay_project_profiles as spp')
            ->leftJoinSub($latestSubayUpdateByProjectQuery, 'spp_latest_update', function ($join) {
                $join->on(DB::raw('UPPER(TRIM(spp.project_code))'), '=', 'spp_latest_update.project_code_key');
            })
            ->leftJoin('locally_funded_projects as lfp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
            ->leftJoin('locally_funded_physical_updates as lpu', function ($join) use ($currentYear, $currentMonth) {
                $join->on('lpu.project_id', '=', 'lfp.id')
                    ->where('lpu.year', '=', $currentYear)
                    ->where('lpu.month', '=', $currentMonth);
            });

        $hasFinancialUpdatesTable = Schema::hasTable('locally_funded_financial_updates');
        if ($hasFinancialUpdatesTable) {
            $financialTotalsSubquery = DB::table('locally_funded_financial_updates as lffu')
                ->select(
                    'lffu.project_id',
                    DB::raw('SUM(COALESCE(lffu.obligation, 0)) as lffu_obligation_total'),
                    DB::raw('SUM(COALESCE(lffu.disbursed_amount, 0)) as lffu_disbursed_amount_total'),
                    DB::raw('SUM(COALESCE(lffu.reverted_amount, 0)) as lffu_reverted_amount_total')
                )
                ->where('lffu.year', '=', $currentYear)
                ->groupBy('lffu.project_id');

            $query->leftJoinSub($financialTotalsSubquery, 'lffu_totals', function ($join) {
                $join->on('lffu_totals.project_id', '=', 'lfp.id');
            });
        }

        $projectProvinceExpression = 'COALESCE(lfp.province, spp.province)';
        $projectCityExpression = 'COALESCE(lfp.city_municipality, spp.city_municipality)';
        $projectRegionExpression = 'COALESCE(lfp.region, spp.region)';

        $this->applyUserScopeToLocationQuery(
            $query,
            $projectProvinceExpression,
            $projectCityExpression,
            $projectRegionExpression
        );

        $select = [
            'spp.project_code',
            'spp.project_title',
            'spp.province',
            'spp.city_municipality',
            'spp.barangay',
            'spp.funding_year',
            'spp.program',
            'spp.procurement_type',
            'spp.procurement',
            'spp.status',
            'spp.total_accomplishment',
            'spp.national_subsidy_original_allocation',
            'spp.obligation as spp_obligation',
            'spp.disbursement as spp_disbursed_amount',
            'spp.liquidations as spp_reverted_amount',
            'spp.updated_at as subay_updated_at',
            'lfp.id as lfp_id',
            'lfp.province as lfp_province',
            'lfp.city_municipality as lfp_city_municipality',
            'lfp.barangay as lfp_barangay',
            'lfp.mode_of_procurement as lfp_mode_of_procurement',
            'lfp.fund_source as lfp_fund_source',
            'lfp.lgsf_allocation as lfp_lgsf_allocation',
            'lfp.updated_at as lfp_updated_at',
            'lpu.status_project_fou as lpu_status_actual',
            'lpu.status_project_ro as lpu_status_subaybayan',
            'lpu.accomplishment_pct_ro as lpu_accomplishment_pct_ro',
        ];

        $hasLfpObligationColumn = Schema::hasColumn('locally_funded_projects', 'obligation');
        $hasLfpDisbursedAmountColumn = Schema::hasColumn('locally_funded_projects', 'disbursed_amount');
        $hasLfpRevertedAmountColumn = Schema::hasColumn('locally_funded_projects', 'reverted_amount');
        $select[] = $hasLfpObligationColumn
            ? 'lfp.obligation as lfp_obligation'
            : DB::raw('NULL as lfp_obligation');
        $select[] = $hasLfpDisbursedAmountColumn
            ? 'lfp.disbursed_amount as lfp_disbursed_amount'
            : DB::raw('NULL as lfp_disbursed_amount');
        $select[] = $hasLfpRevertedAmountColumn
            ? 'lfp.reverted_amount as lfp_reverted_amount'
            : DB::raw('NULL as lfp_reverted_amount');
        $select[] = $hasFinancialUpdatesTable
            ? 'lffu_totals.lffu_obligation_total'
            : DB::raw('NULL as lffu_obligation_total');
        $select[] = $hasFinancialUpdatesTable
            ? 'lffu_totals.lffu_disbursed_amount_total'
            : DB::raw('NULL as lffu_disbursed_amount_total');
        $select[] = $hasFinancialUpdatesTable
            ? 'lffu_totals.lffu_reverted_amount_total'
            : DB::raw('NULL as lffu_reverted_amount_total');

        $hasUtilizationRateColumn = Schema::hasColumn('locally_funded_projects', 'utilization_rate');
        $select[] = $hasUtilizationRateColumn
            ? 'lfp.utilization_rate as lfp_utilization_rate'
            : DB::raw('NULL as lfp_utilization_rate');

        $perPage = (int) request('per_page', 10);
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $filters = [
            'search' => trim((string) request('search', '')),
            'project_code' => trim((string) request('project_code', '')),
            'funding_year' => trim((string) request('funding_year', '')),
            'fund_source' => trim((string) request('fund_source', '')),
            'province' => trim((string) request('province', '')),
            'city' => trim((string) request('city', '')),
            'barangay' => trim((string) request('barangay', '')),
            'procurement' => trim((string) request('procurement', '')),
            'status' => trim((string) request('status', '')),
            'project_update_status' => trim((string) request('project_update_status', '')),
        ];

        if (strcasecmp($filters['fund_source'], 'SGLGIF') === 0) {
            $filters['fund_source'] = '';
        }

        $this->applyLocallyFundedSourceScope(
            $query,
            'COALESCE(lfp.fund_source, spp.program)',
            'COALESCE(lfp.subaybayan_project_code, spp.project_code)'
        );

        $scopedLocationOptionsQuery = clone $query;

        $scopedProvinceOptions = (clone $scopedLocationOptionsQuery)
            ->selectRaw('TRIM(COALESCE(' . $projectProvinceExpression . ", '')) as province")
            ->whereRaw('TRIM(COALESCE(' . $projectProvinceExpression . ", '')) <> ''")
            ->distinct()
            ->orderBy('province')
            ->pluck('province')
            ->values()
            ->all();

        $scopedProvinceMunicipalities = (clone $scopedLocationOptionsQuery)
            ->selectRaw('TRIM(COALESCE(' . $projectProvinceExpression . ", '')) as province")
            ->selectRaw('TRIM(COALESCE(' . $projectCityExpression . ", '')) as city_municipality")
            ->whereRaw('TRIM(COALESCE(' . $projectProvinceExpression . ", '')) <> ''")
            ->whereRaw('TRIM(COALESCE(' . $projectCityExpression . ", '')) <> ''")
            ->distinct()
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->get()
            ->groupBy('province')
            ->map(fn ($rows) => $rows->pluck('city_municipality')->filter()->values()->all())
            ->toArray();

        $scopedCityBarangays = (clone $scopedLocationOptionsQuery)
            ->selectRaw('TRIM(COALESCE(' . $projectCityExpression . ", '')) as city_municipality")
            ->selectRaw("TRIM(COALESCE(COALESCE(lfp.barangay, spp.barangay), '')) as barangay")
            ->whereRaw('TRIM(COALESCE(' . $projectCityExpression . ", '')) <> ''")
            ->whereRaw("TRIM(COALESCE(COALESCE(lfp.barangay, spp.barangay), '')) <> ''")
            ->orderBy('city_municipality')
            ->get()
            ->groupBy('city_municipality')
            ->map(function ($rows) {
                return $rows
                    ->flatMap(function ($row) {
                        $barangayText = trim((string) ($row->barangay ?? ''));
                        if ($barangayText === '') {
                            return [];
                        }

                        $normalized = preg_replace('/[\r\n;|]+/', ',', $barangayText);

                        return collect(explode(',', (string) $normalized))
                            ->map(fn ($item) => trim((string) $item))
                            ->filter()
                            ->values();
                    })
                    ->unique(fn ($value) => strtolower((string) $value))
                    ->values()
                    ->all();
            })
            ->toArray();

        if ($filters['project_code'] !== '') {
            $projectCodeKeyword = '%' . strtolower($filters['project_code']) . '%';
            $query->whereRaw('LOWER(spp.project_code) LIKE ?', [$projectCodeKeyword]);
        }

        if ($filters['search'] !== '') {
            $keyword = '%' . strtolower($filters['search']) . '%';
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->whereRaw('LOWER(spp.project_code) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(spp.project_title) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(lfp.province, spp.province, ""))) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(lfp.city_municipality, spp.city_municipality, ""))) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(lfp.barangay, spp.barangay, ""))) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(lfp.fund_source, spp.program)) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(lfp.mode_of_procurement, spp.procurement_type, spp.procurement)) LIKE ?', [$keyword]);
            });
        }

        if ($filters['funding_year'] !== '') {
            $query->whereRaw('TRIM(COALESCE(spp.funding_year, \'\')) = ?', [$filters['funding_year']]);
        }

        if ($filters['fund_source'] !== '') {
            $normalizedFundSource = strtolower($filters['fund_source']);
            if ($normalizedFundSource === 'falgu') {
                $query->whereRaw(
                    'LOWER(TRIM(COALESCE(lfp.fund_source, spp.program, \'\'))) LIKE ?',
                    ['%falgu%']
                );
            } else {
                $query->whereRaw(
                    'LOWER(TRIM(COALESCE(lfp.fund_source, spp.program, \'\'))) = ?',
                    [$normalizedFundSource]
                );
            }
        }

        if ($filters['province'] !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(lfp.province, spp.province, \'\'))) = ?', [strtolower($filters['province'])]);
        }

        if ($filters['city'] !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(lfp.city_municipality, spp.city_municipality, \'\'))) = ?', [strtolower($filters['city'])]);
        }

        if ($filters['barangay'] !== '') {
            $query->where(function ($subQuery) use ($filters) {
                $barangayKeyword = strtolower($filters['barangay']);
                $subQuery
                    ->whereRaw('LOWER(TRIM(COALESCE(lfp.barangay, spp.barangay, \'\'))) = ?', [$barangayKeyword])
                    ->orWhereRaw('LOWER(COALESCE(lfp.barangay, spp.barangay, \'\')) LIKE ?', ['%' . $barangayKeyword . '%']);
            });
        }

        if ($filters['procurement'] !== '') {
            $query->whereRaw(
                'LOWER(TRIM(COALESCE(lfp.mode_of_procurement, spp.procurement_type, spp.procurement, \'\'))) = ?',
                [strtolower($filters['procurement'])]
            );
        }

        if ($filters['status'] !== '') {
            $normalizedStatus = strtolower($filters['status']);
            if ($normalizedStatus === 'pending') {
                $query->where(function ($statusQuery) {
                    $statusQuery
                        ->whereRaw('TRIM(COALESCE(lpu.status_project_ro, spp.status, \'\')) = \'\'')
                        ->orWhereRaw('LOWER(TRIM(COALESCE(lpu.status_project_ro, spp.status, \'\'))) = ?', ['pending']);
                });
            } else {
                $query->whereRaw(
                    'LOWER(TRIM(COALESCE(lpu.status_project_ro, spp.status, \'\'))) = ?',
                    [$normalizedStatus]
                );
            }
        }

        if ($filters['project_update_status'] !== '') {
            $normalizedUpdateStatus = strtoupper(preg_replace('/[^A-Z]/', '', $filters['project_update_status']) ?? '');
            if (in_array($normalizedUpdateStatus, ['HIGHRISK', 'LOWRISK', 'NORISK'], true)) {
                $query->whereNotNull('spp_latest_update.latest_update_date');
                $query->whereRaw("LOWER(TRIM(COALESCE(lpu.status_project_ro, spp.status, ''))) <> 'completed'");

                if ($normalizedUpdateStatus === 'HIGHRISK') {
                    $query->whereRaw('DATEDIFF(CURDATE(), spp_latest_update.latest_update_date) >= 60');
                } elseif ($normalizedUpdateStatus === 'LOWRISK') {
                    $query->whereRaw('DATEDIFF(CURDATE(), spp_latest_update.latest_update_date) > 30 AND DATEDIFF(CURDATE(), spp_latest_update.latest_update_date) < 60');
                } elseif ($normalizedUpdateStatus === 'NORISK') {
                    $query->whereRaw('DATEDIFF(CURDATE(), spp_latest_update.latest_update_date) <= 30');
                }
            }
        }

        $sortBy = trim((string) request('sort_by', 'funding_year'));
        $sortDir = strtolower(trim((string) request('sort_dir', 'asc')));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $query->select($select);

        $allocationExpr = "COALESCE(lfp.lgsf_allocation, NULLIF(REPLACE(spp.national_subsidy_original_allocation, ',', ''), ''))";
        $obligationExpr = $hasFinancialUpdatesTable
            ? ($hasLfpObligationColumn
                ? "COALESCE(lffu_totals.lffu_obligation_total, lfp.obligation, NULLIF(REPLACE(spp.obligation, ',', ''), ''))"
                : "COALESCE(lffu_totals.lffu_obligation_total, NULLIF(REPLACE(spp.obligation, ',', ''), ''))")
            : ($hasLfpObligationColumn
                ? "COALESCE(lfp.obligation, NULLIF(REPLACE(spp.obligation, ',', ''), ''))"
                : "NULLIF(REPLACE(spp.obligation, ',', ''), '')");
        $disbursedExpr = $hasFinancialUpdatesTable
            ? ($hasLfpDisbursedAmountColumn
                ? "COALESCE(lffu_totals.lffu_disbursed_amount_total, lfp.disbursed_amount, NULLIF(REPLACE(spp.disbursement, ',', ''), ''))"
                : "COALESCE(lffu_totals.lffu_disbursed_amount_total, NULLIF(REPLACE(spp.disbursement, ',', ''), ''))")
            : ($hasLfpDisbursedAmountColumn
                ? "COALESCE(lfp.disbursed_amount, NULLIF(REPLACE(spp.disbursement, ',', ''), ''))"
                : "NULLIF(REPLACE(spp.disbursement, ',', ''), '')");
        $revertedExpr = $hasFinancialUpdatesTable
            ? ($hasLfpRevertedAmountColumn
                ? "COALESCE(lffu_totals.lffu_reverted_amount_total, lfp.reverted_amount, NULLIF(REPLACE(spp.liquidations, ',', ''), ''))"
                : "COALESCE(lffu_totals.lffu_reverted_amount_total, NULLIF(REPLACE(spp.liquidations, ',', ''), ''))")
            : ($hasLfpRevertedAmountColumn
                ? "COALESCE(lfp.reverted_amount, NULLIF(REPLACE(spp.liquidations, ',', ''), ''))"
                : "NULLIF(REPLACE(spp.liquidations, ',', ''), '')");
        $utilizationExpr = "CASE WHEN ({$allocationExpr} + 0) = 0 THEN NULL ELSE ((COALESCE({$disbursedExpr}, 0) + COALESCE({$revertedExpr}, 0)) / ({$allocationExpr} + 0)) * 100 END";
        $effectiveStatusExpr = "LOWER(TRIM(COALESCE(lpu.status_project_ro, spp.status, '')))";

        // Keep completed projects at the bottom regardless of active column sort.
        $query->orderByRaw("CASE WHEN {$effectiveStatusExpr} = 'completed' THEN 1 ELSE 0 END");

        switch ($sortBy) {
            case 'project_code':
                $query
                    ->orderByRaw("CASE WHEN spp.project_code IS NULL OR TRIM(spp.project_code) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.project_code', $sortDir);
                break;
            case 'project_title':
                $query
                    ->orderByRaw("CASE WHEN spp.project_title IS NULL OR TRIM(spp.project_title) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.project_title', $sortDir);
                break;
            case 'location':
                $query
                    ->orderByRaw("CASE WHEN spp.city_municipality IS NULL OR TRIM(spp.city_municipality) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.city_municipality', $sortDir)
                    ->orderByRaw("CASE WHEN spp.province IS NULL OR TRIM(spp.province) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.province', $sortDir);
                break;
            case 'funding_year':
                $query
                    ->orderByRaw("CASE WHEN spp.funding_year IS NULL OR TRIM(spp.funding_year) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("CAST(spp.funding_year AS UNSIGNED) {$sortDir}");
                break;
            case 'fund_source':
                $query
                    ->orderByRaw("CASE WHEN COALESCE(lfp.fund_source, spp.program) IS NULL OR TRIM(COALESCE(lfp.fund_source, spp.program)) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("COALESCE(lfp.fund_source, spp.program) {$sortDir}");
                break;
            case 'procurement':
                $query
                    ->orderByRaw("CASE WHEN COALESCE(lfp.mode_of_procurement, spp.procurement_type, spp.procurement) IS NULL OR TRIM(COALESCE(lfp.mode_of_procurement, spp.procurement_type, spp.procurement)) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("COALESCE(lfp.mode_of_procurement, spp.procurement_type, spp.procurement) {$sortDir}");
                break;
            case 'lgsf_allocation':
                $query
                    ->orderByRaw("CASE WHEN {$allocationExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$allocationExpr} + 0 {$sortDir}");
                break;
            case 'utilization_rate':
                $query
                    ->orderByRaw("CASE WHEN {$utilizationExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$utilizationExpr} {$sortDir}");
                break;
            case 'obligation':
                $query
                    ->orderByRaw("CASE WHEN {$obligationExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$obligationExpr} + 0 {$sortDir}");
                break;
            case 'disbursed_amount':
                $query
                    ->orderByRaw("CASE WHEN {$disbursedExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$disbursedExpr} + 0 {$sortDir}");
                break;
            case 'reverted_amount':
                $query
                    ->orderByRaw("CASE WHEN {$revertedExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$revertedExpr} + 0 {$sortDir}");
                break;
            case 'physical_subaybayan':
                $query
                    ->orderByRaw("CASE WHEN COALESCE(lpu.accomplishment_pct_ro, NULLIF(REPLACE(spp.total_accomplishment, ',', ''), '')) IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("COALESCE(lpu.accomplishment_pct_ro, NULLIF(REPLACE(spp.total_accomplishment, ',', ''), '')) + 0 {$sortDir}");
                break;
            case 'status_actual':
                $query
                    ->orderByRaw("CASE WHEN lpu.status_project_fou IS NULL OR TRIM(lpu.status_project_fou) = '' THEN 1 ELSE 0 END")
                    ->orderBy('lpu.status_project_fou', $sortDir);
                break;
            case 'status_subaybayan':
                $query
                    ->orderByRaw("CASE WHEN COALESCE(lpu.status_project_ro, spp.status) IS NULL OR TRIM(COALESCE(lpu.status_project_ro, spp.status)) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("COALESCE(lpu.status_project_ro, spp.status) {$sortDir}");
                break;
            case 'last_updated':
                $query
                    ->orderByRaw("CASE WHEN COALESCE(lfp.updated_at, spp.updated_at) IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("COALESCE(lfp.updated_at, spp.updated_at) {$sortDir}");
                break;
            default:
                $sortBy = 'funding_year';
                $sortDir = 'asc';
                $query
                    ->orderByRaw("CASE WHEN spp.funding_year IS NULL OR TRIM(spp.funding_year) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw('CAST(spp.funding_year AS UNSIGNED) ASC')
                    ->orderByRaw("CASE WHEN spp.city_municipality IS NULL OR TRIM(spp.city_municipality) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.city_municipality')
                    ->orderByRaw("CASE WHEN spp.province IS NULL OR TRIM(spp.province) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.province');
                break;
        }

        $projects = $query
            ->orderBy('spp.project_code')
            ->paginate($perPage)
            ->withQueryString();

        $projects->getCollection()->transform(function ($row) {
            $parseNumber = function ($value) {
                if ($value === null) {
                    return null;
                }
                $value = trim((string) $value);
                if ($value === '') {
                    return null;
                }
                $clean = preg_replace('/[^0-9\\.-]/', '', $value);
                return $clean === '' ? null : (float) $clean;
            };

            $updatedAt = $row->lfp_updated_at ?: $row->subay_updated_at;
            $updatedAt = $updatedAt ? Carbon::parse($updatedAt) : null;

            $allocation = $row->lfp_lgsf_allocation;
            if ($allocation === null) {
                $allocation = $parseNumber($row->national_subsidy_original_allocation);
            }

            $obligation = $row->lffu_obligation_total;
            if ($obligation === null) {
                $obligation = $row->lfp_obligation;
            }
            if ($obligation === null) {
                $obligation = $parseNumber($row->spp_obligation ?? null);
            }

            $disbursedAmount = $row->lffu_disbursed_amount_total;
            if ($disbursedAmount === null) {
                $disbursedAmount = $row->lfp_disbursed_amount;
            }
            if ($disbursedAmount === null) {
                $disbursedAmount = $parseNumber($row->spp_disbursed_amount ?? null);
            }

            $revertedAmount = $row->lffu_reverted_amount_total;
            if ($revertedAmount === null) {
                $revertedAmount = $row->lfp_reverted_amount;
            }
            if ($revertedAmount === null) {
                $revertedAmount = $parseNumber($row->spp_reverted_amount ?? null);
            }

            $subayAccomplishment = $parseNumber($row->total_accomplishment);

            $projectTitle = $row->project_title;
            if ($projectTitle === null || trim((string) $projectTitle) === '') {
                $projectTitle = $row->project_code;
            }

            $lfpUtilizationRate = $row->lfp_utilization_rate ?? null;
            $utilizationRate = null;
            if ($allocation !== null) {
                $allocationFloat = (float) $allocation;
                if ($allocationFloat > 0) {
                    $utilizationRate = ((((float) ($disbursedAmount ?? 0)) + ((float) ($revertedAmount ?? 0))) / $allocationFloat) * 100;
                } else {
                    $utilizationRate = 0.0;
                }
            } elseif ($lfpUtilizationRate !== null) {
                $utilizationRate = (float) $lfpUtilizationRate;
            }

            return (object) [
                'lfp_id' => $row->lfp_id,
                'subaybayan_project_code' => $row->project_code,
                'project_name' => $projectTitle,
                'province' => $row->lfp_province ?: $row->province,
                'city_municipality' => $row->lfp_city_municipality ?: $row->city_municipality,
                'barangay' => $row->lfp_barangay ?: $row->barangay,
                'funding_year' => $row->funding_year,
                'fund_source' => $row->lfp_fund_source ?: $row->program,
                'mode_of_procurement' => $row->lfp_mode_of_procurement ?: ($row->procurement_type ?: $row->procurement),
                'lgsf_allocation' => $allocation,
                'obligation' => $obligation !== null ? (float) $obligation : null,
                'disbursed_amount' => $disbursedAmount !== null ? (float) $disbursedAmount : null,
                'reverted_amount' => $revertedAmount !== null ? (float) $revertedAmount : null,
                'utilization_rate' => $utilizationRate,
                'updated_at' => $updatedAt,
                'status_subaybayan' => $row->status,
                'subay_accomplishment_pct' => $subayAccomplishment,
            ];
        });

        // Get current physical status for each project
        $projectIds = $projects->getCollection()->pluck('lfp_id')->filter()->values();
        $physicalStatuses = [];

        if ($projectIds->isNotEmpty()) {
            $physicalUpdates = \Illuminate\Support\Facades\DB::table('locally_funded_physical_updates')
                ->whereIn('project_id', $projectIds)
                ->where('year', $currentYear)
                ->where('month', $currentMonth)
                ->select('project_id', 'status_project_fou', 'status_project_ro', 'accomplishment_pct_ro')
                ->get()
                ->keyBy('project_id');

            foreach ($physicalUpdates as $update) {
                $physicalStatuses[$update->project_id] = [
                    'status_actual' => $update->status_project_fou,
                    'status_subaybayan' => $update->status_project_ro,
                    'accomplishment_pct_ro' => $update->accomplishment_pct_ro,
                ];
            }
        }

        $options = $this->getProjectFormOptions();
        $options['provinces'] = !empty($scopedProvinceOptions)
            ? $scopedProvinceOptions
            : ($options['provinces'] ?? []);
        $options['provinceMunicipalities'] = !empty($scopedProvinceMunicipalities)
            ? $scopedProvinceMunicipalities
            : ($options['provinceMunicipalities'] ?? []);
        $options['cityBarangays'] = !empty($scopedCityBarangays)
            ? $scopedCityBarangays
            : ($options['cityBarangays'] ?? []);
        $options['fundSources'] = collect($options['fundSources'] ?? [])
            ->reject(function ($source) {
                return strcasecmp((string) $source, 'SGLGIF') === 0;
            })
            ->values()
            ->all();

        // Cache results
        $viewData = array_merge(
            $options,
            compact(
                'projects',
                'physicalStatuses',
                'perPage',
                'sortBy',
                'sortDir',
                'filters',
                'listRouteName',
                'activeProjectTab',
                'pageTitle',
                'pageDescription',
                'tableTitle',
                'forceFundSource',
                'options'
            )
        );

        Cache::put($cacheKey, ['view_data' => $viewData], now()->addMinutes(5));

        return view('projects.locally-funded', $viewData);
    }

    /**
     * Ensure a locally funded project exists for the SubayBAYAN project code,
     * then redirect to the show page.
     */
    public function ensureFromSubay(string $projectCode)
    {
        $projectCode = trim($projectCode);
        if ($projectCode === '') {
            abort(404);
        }

        $existing = LocallyFundedProject::where('subaybayan_project_code', $projectCode)->first();
        if ($existing) {
            $this->authorizeLocallyFundedProjectAccess($existing);
            $this->ensureFundUtilizationReport($existing);
            return redirect()->route('locally-funded-project.show', $existing);
        }

        if (!Schema::hasTable('subay_project_profiles')) {
            abort(404);
        }

        $subayQuery = DB::table('subay_project_profiles as spp')
            ->select('spp.*')
            ->where('spp.project_code', $projectCode);
        $this->applyUserScopeToLocationQuery($subayQuery, 'spp.province', 'spp.city_municipality', 'spp.region');
        $subay = $subayQuery->first();

        if (!$subay) {
            abort(404);
        }

        $today = now()->toDateString();

        $cleanText = function ($value, $default = 'N/A') {
            $value = is_string($value) ? trim($value) : '';
            return $value !== '' ? $value : $default;
        };

        $parseNumber = function ($value, $default = 0) {
            if ($value === null) {
                return $default;
            }
            $value = trim((string) $value);
            if ($value === '') {
                return $default;
            }
            $clean = preg_replace('/[^0-9\\.-]/', '', $value);
            if ($clean === '' || $clean === '-' || $clean === '.') {
                return $default;
            }
            return (float) $clean;
        };

        $parseDate = function ($value) use ($today) {
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                return $today;
            }
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Exception $e) {
                return $today;
            }
        };

        $mapProjectType = function ($value) {
            $value = strtolower((string) $value);
            if (str_contains($value, 'evac') || str_contains($value, 'multi')) {
                return 'Evacuation Center / Multi-Purpose Hall';
            }
            if (str_contains($value, 'water')) {
                return 'Water Supply and Sanitation';
            }
            if (str_contains($value, 'road') || str_contains($value, 'bridge')) {
                return 'Local Roads and Bridges';
            }
            return 'Others';
        };

        $mapModeOfProcurement = function ($value) {
            $value = strtolower((string) $value);
            if (str_contains($value, 'contract')) {
                return 'contract';
            }
            if (str_contains($value, 'admin') || str_contains($value, 'implementation')) {
                return 'admin';
            }
            return 'admin';
        };

        $mapImplementingUnit = function ($value) {
            $value = strtolower((string) $value);
            if (str_contains($value, 'prov')) {
                return 'Provincial LGU';
            }
            if (str_contains($value, 'barang')) {
                return 'Barangay LGU';
            }
            if (str_contains($value, 'mun') || str_contains($value, 'city')) {
                return 'Municipal LGU';
            }
            return 'Municipal LGU';
        };

        $fundingYear = (int) $parseNumber($subay->funding_year ?? null, now()->year);
        if ($fundingYear < 2020 || $fundingYear > 2099) {
            $fundingYear = (int) now()->year;
        }

        $modeSource = $subay->procurement_type ?? $subay->procurement ?? $subay->moi ?? null;
        $implementingSource = $subay->implementing_unit ?? $subay->unit_implementing_the_project ?? null;

        $data = [
            'user_id' => Auth::id(),
            'office' => Auth::user()->office ?? null,
            'region' => Auth::user()->region ?? null,

            'province' => $cleanText($subay->province ?? null, 'Unknown'),
            'city_municipality' => $cleanText($subay->city_municipality ?? null, 'Unknown'),
            'barangay' => $cleanText($subay->barangay ?? null, 'Unknown'),
            'project_name' => $cleanText($subay->project_title ?? null, $projectCode),
            'funding_year' => $fundingYear,
            'fund_source' => $cleanText($subay->program ?? null, 'SBDP'),
            'subaybayan_project_code' => $projectCode,
            'project_description' => $cleanText($subay->project_description ?? $subay->remarks ?? null, 'Auto-created from SubayBAYAN'),
            'project_type' => $mapProjectType($subay->type_of_project ?? $subay->type ?? null),
            'date_nadai' => $parseDate($subay->date_of_nadai ?? null),
            'lgsf_allocation' => $parseNumber($subay->national_subsidy_original_allocation ?? $subay->national_subsidy_revised_allocation ?? null, 0),
            'lgu_counterpart' => $parseNumber($subay->lgu_counterpart_original_allocation ?? $subay->lgu_counterpart_revised_allocation ?? null, 0),
            'no_of_beneficiaries' => (int) $parseNumber($subay->beneficiaries ?? null, 0),
            'rainwater_collection_system' => 'No',
            'date_confirmation_fund_receipt' => $parseDate(
                $subay->submission_of_certificate_on_the_receipt_of_funds
                    ?? $subay->date_of_receipt_of_notice_to_proceed
                    ?? $subay->date_of_receipt_of_ntp
                    ?? null
            ),

            'mode_of_procurement' => $mapModeOfProcurement($modeSource),
            'implementing_unit' => $mapImplementingUnit($implementingSource),
            'date_posting_itb' => $parseDate($subay->invitation_to_bid_ib_posted ?? null),
            'date_bid_opening' => $parseDate($subay->bid_opening_bid_evaluation ?? $subay->bid_opening_evaluation ?? null),
            'date_noa' => $parseDate($subay->noa_issuance ?? null),
            'date_ntp' => $parseDate($subay->date_of_receipt_of_ntp ?? $subay->date_of_receipt_of_notice_to_proceed ?? null),
            'contractor' => $cleanText($subay->name_of_contractor ?? null, 'N/A'),
            'contract_amount' => $parseNumber($subay->contract_price ?? $subay->total_project_cost ?? $subay->total_estimated_cost_of_project ?? null, 0),
            'project_duration' => $cleanText($subay->contract_duration ?? $subay->duration ?? null, 'N/A'),
            'actual_start_date' => $parseDate($subay->actual_start_of_construction ?? null),
            'target_date_completion' => $parseDate(
                $subay->intended_completion_date
                    ?? $subay->intended_completion_date_2
                    ?? $subay->date_of_expiration_of_contract
                    ?? null
            ),
        ];

        try {
            $project = LocallyFundedProject::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            $project = LocallyFundedProject::where('subaybayan_project_code', $projectCode)->first();
            if ($project) {
                $this->authorizeLocallyFundedProjectAccess($project);
                $this->ensureFundUtilizationReport($project);
                return redirect()->route('locally-funded-project.show', $project);
            }
            throw $e;
        }

        $this->ensureFundUtilizationReport($project);

        return redirect()->route('locally-funded-project.show', $project)
            ->with('success', 'Locally funded project created from SubayBAYAN data.');
    }

    /**
     * Display a SubayBAYAN project profile (read-only).
     */
    public function showSubaybayan(string $projectCode)
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            abort(404);
        }

        $projectQuery = DB::table('subay_project_profiles as spp')
            ->select('spp.*')
            ->where('spp.project_code', $projectCode);
        $this->applyUserScopeToLocationQuery($projectQuery, 'spp.province', 'spp.city_municipality', 'spp.region');
        $project = $projectQuery->first();

        if (!$project) {
            abort(404);
        }

        $columns = Schema::getColumnListing('subay_project_profiles');
        $columns = array_values(array_filter($columns, function ($column) {
            return strtolower((string) $column) !== 'id';
        }));

        return view('projects.locally-funded-subay-show', [
            'project' => $project,
            'columns' => $columns,
        ]);
    }

    /**
     * Display the specified locally funded project.
     */
    public function show(LocallyFundedProject $project)
    {
        $this->authorizeLocallyFundedProjectAccess($project);

        $currentYear = now()->year;
        $currentMonth = now()->month;
        $parseNumericValue = static function ($value): ?float {
            if ($value === null) {
                return null;
            }

            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $cleaned = preg_replace('/[^0-9\.\-]/', '', $value);
            if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
                return null;
            }

            return (float) $cleaned;
        };

        $subayFinancialValues = [
            'obligation' => null,
            'disbursed_amount' => null,
            'reverted_amount' => null,
            'updated_at' => null,
        ];
        $subayPhysicalValues = [
            'status_project_ro' => null,
            'accomplishment_pct_ro' => null,
            'updated_at' => null,
        ];
        $projectAtRiskValues = [
            'slippage_ro' => null,
            'risk_aging' => null,
            'updated_at' => null,
        ];

        if (Schema::hasTable('subay_project_profiles')) {
            $projectCode = trim((string) $project->subaybayan_project_code);

            if ($projectCode !== '') {
                $obligationColumn = Schema::hasColumn('subay_project_profiles', 'obligation')
                    ? 'obligation'
                    : (Schema::hasColumn('subay_project_profiles', 'amount') ? 'amount' : null);
                $disbursementColumn = Schema::hasColumn('subay_project_profiles', 'disbursement')
                    ? 'disbursement'
                    : (Schema::hasColumn('subay_project_profiles', 'amount_2') ? 'amount_2' : null);
                $liquidationsColumn = Schema::hasColumn('subay_project_profiles', 'liquidations')
                    ? 'liquidations'
                    : (Schema::hasColumn('subay_project_profiles', 'amount_3') ? 'amount_3' : null);
                $hasStatusColumn = Schema::hasColumn('subay_project_profiles', 'status');
                $hasTotalAccomplishmentColumn = Schema::hasColumn('subay_project_profiles', 'total_accomplishment');

                $selectColumns = array_values(array_unique(array_filter([
                    $obligationColumn,
                    $disbursementColumn,
                    $liquidationsColumn,
                    $hasStatusColumn ? 'status' : null,
                    $hasTotalAccomplishmentColumn ? 'total_accomplishment' : null,
                    'updated_at',
                ])));

                if (count($selectColumns) > 0) {
                    $subayRow = DB::table('subay_project_profiles')
                        ->where('project_code', $projectCode)
                        ->first($selectColumns);

                    if ($subayRow) {
                        $subayFinancialValues['obligation'] = $parseNumericValue(
                            $obligationColumn ? ($subayRow->{$obligationColumn} ?? null) : null
                        );
                        $subayFinancialValues['disbursed_amount'] = $parseNumericValue(
                            $disbursementColumn ? ($subayRow->{$disbursementColumn} ?? null) : null
                        );
                        $subayFinancialValues['reverted_amount'] = $parseNumericValue(
                            $liquidationsColumn ? ($subayRow->{$liquidationsColumn} ?? null) : null
                        );
                        if ($hasStatusColumn) {
                            $statusFromSubay = trim((string) ($subayRow->status ?? ''));
                            $subayPhysicalValues['status_project_ro'] = $statusFromSubay !== '' ? $statusFromSubay : null;
                        }
                        if ($hasTotalAccomplishmentColumn) {
                            $subayPhysicalValues['accomplishment_pct_ro'] = $parseNumericValue(
                                $subayRow->total_accomplishment ?? null
                            );
                        }
                        $subayPhysicalValues['updated_at'] = $subayRow->updated_at ?? null;
                        $subayFinancialValues['updated_at'] = $subayRow->updated_at ?? null;
                    }
                }
            }
        }

        if (Schema::hasTable('project_at_risks')) {
            $projectCode = trim((string) $project->subaybayan_project_code);
            if ($projectCode !== '') {
                $hasAgingColumn = Schema::hasColumn('project_at_risks', 'aging');
                $hasDateOfExtractionColumn = Schema::hasColumn('project_at_risks', 'date_of_extraction');

                $selectColumns = array_values(array_unique(array_filter([
                    'slippage',
                    $hasAgingColumn ? 'aging' : null,
                    $hasDateOfExtractionColumn ? 'date_of_extraction' : null,
                    'updated_at',
                    'id',
                ])));

                $normalizedProjectCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $projectCode) ?? '');

                $projectAtRiskRow = DB::table('project_at_risks')
                    ->where(function ($query) use ($projectCode, $normalizedProjectCode) {
                        $query->whereRaw('UPPER(TRIM(project_code)) = ?', [strtoupper($projectCode)]);

                        if ($normalizedProjectCode !== '') {
                            $query->orWhereRaw(
                                "UPPER(REPLACE(REPLACE(REPLACE(TRIM(project_code), '-', ''), ' ', ''), '/', '')) = ?",
                                [$normalizedProjectCode]
                            );
                        }
                    })
                    ->orderByRaw(($hasDateOfExtractionColumn ? 'date_of_extraction' : 'updated_at') . ' DESC')
                    ->orderByRaw($hasDateOfExtractionColumn ? 'COALESCE(updated_at, date_of_extraction) DESC' : 'updated_at DESC')
                    ->orderBy('id', 'desc')
                    ->first($selectColumns);

                if ($projectAtRiskRow) {
                    $projectAtRiskValues['slippage_ro'] = $parseNumericValue($projectAtRiskRow->slippage ?? null);

                    $riskAgingValue = $hasAgingColumn ? ($projectAtRiskRow->aging ?? null) : null;
                    if ($riskAgingValue !== null && $riskAgingValue !== '') {
                        if (is_numeric($riskAgingValue)) {
                            $numericAging = (float) $riskAgingValue;
                            $riskAgingValue = fmod($numericAging, 1.0) === 0.0
                                ? (string) ((int) $numericAging)
                                : (string) $numericAging;
                        } else {
                            $riskAgingValue = trim((string) $riskAgingValue);
                        }
                    }
                    $projectAtRiskValues['risk_aging'] = ($riskAgingValue !== null && $riskAgingValue !== '')
                        ? (string) $riskAgingValue
                        : null;

                    $projectAtRiskValues['updated_at'] = $projectAtRiskRow->updated_at
                        ?? ($hasDateOfExtractionColumn ? ($projectAtRiskRow->date_of_extraction ?? null) : null);
                }
            }
        }

        $allPhysicalUpdates = \Illuminate\Support\Facades\DB::table('locally_funded_physical_updates')
            ->leftJoin('tbusers', 'tbusers.idno', '=', 'locally_funded_physical_updates.updated_by')
            ->where('locally_funded_physical_updates.project_id', $project->id)
            ->orderBy('locally_funded_physical_updates.year')
            ->orderBy('locally_funded_physical_updates.month')
            ->select(
                'locally_funded_physical_updates.year',
                'locally_funded_physical_updates.month',
                'locally_funded_physical_updates.status_project_fou',
                'locally_funded_physical_updates.status_project_ro',
                'locally_funded_physical_updates.accomplishment_pct',
                'locally_funded_physical_updates.accomplishment_pct_ro',
                'locally_funded_physical_updates.slippage',
                'locally_funded_physical_updates.slippage_ro',
                'locally_funded_physical_updates.risk_aging',
                'locally_funded_physical_updates.nc_letters',
                'locally_funded_physical_updates.status_project_fou_updated_at',
                'locally_funded_physical_updates.status_project_ro_updated_at',
                'locally_funded_physical_updates.accomplishment_pct_updated_at',
                'locally_funded_physical_updates.accomplishment_pct_ro_updated_at',
                'locally_funded_physical_updates.slippage_updated_at',
                'locally_funded_physical_updates.slippage_ro_updated_at',
                'locally_funded_physical_updates.risk_aging_updated_at',
                'locally_funded_physical_updates.nc_letters_updated_at',
                'locally_funded_physical_updates.status_project_fou_updated_by',
                'locally_funded_physical_updates.status_project_ro_updated_by',
                'locally_funded_physical_updates.accomplishment_pct_updated_by',
                'locally_funded_physical_updates.accomplishment_pct_ro_updated_by',
                'locally_funded_physical_updates.slippage_updated_by',
                'locally_funded_physical_updates.slippage_ro_updated_by',
                'locally_funded_physical_updates.risk_aging_updated_by',
                'locally_funded_physical_updates.nc_letters_updated_by'
            )
            ->get();

        $physicalUpdates = $allPhysicalUpdates
            ->filter(function ($row) use ($currentYear) {
                return (int) $row->year === (int) $currentYear;
            })
            ->values();

        $userIds = $allPhysicalUpdates->flatMap(function ($row) {
            return [
                $row->status_project_fou_updated_by,
                $row->status_project_ro_updated_by,
                $row->accomplishment_pct_updated_by,
                $row->accomplishment_pct_ro_updated_by,
                $row->slippage_updated_by,
                $row->slippage_ro_updated_by,
                $row->risk_aging_updated_by,
                $row->nc_letters_updated_by,
            ];
        })->filter()->unique()->values();

        $usersById = $userIds->isEmpty()
            ? collect()
            : \Illuminate\Support\Facades\DB::table('tbusers')
                ->whereIn('idno', $userIds)
                ->get(['idno', 'fname', 'lname'])
                ->keyBy('idno');

        $actualCompletionUpdatedByName = null;
        if ($project->actual_date_completion_updated_by) {
            $user = \Illuminate\Support\Facades\DB::table('tbusers')
                ->where('idno', $project->actual_date_completion_updated_by)
                ->first(['fname', 'lname']);
            if ($user) {
                $actualCompletionUpdatedByName = trim($user->fname . ' ' . $user->lname);
            }
        }

        $physicalRowDefaults = [
            'status_project_fou' => null,
            'status_project_ro' => null,
            'accomplishment_pct' => null,
            'accomplishment_pct_ro' => null,
            'slippage' => null,
            'slippage_ro' => null,
            'risk_aging' => null,
            'nc_letters' => null,
            'status_project_fou_updated_at' => null,
            'status_project_ro_updated_at' => null,
            'accomplishment_pct_updated_at' => null,
            'accomplishment_pct_ro_updated_at' => null,
            'slippage_updated_at' => null,
            'slippage_ro_updated_at' => null,
            'risk_aging_updated_at' => null,
            'nc_letters_updated_at' => null,
            'status_project_fou_updated_by' => null,
            'status_project_ro_updated_by' => null,
            'accomplishment_pct_updated_by' => null,
            'accomplishment_pct_ro_updated_by' => null,
            'slippage_updated_by' => null,
            'slippage_ro_updated_by' => null,
            'risk_aging_updated_by' => null,
            'nc_letters_updated_by' => null,
            'status_project_fou_updated_by_name' => null,
            'status_project_ro_updated_by_name' => null,
            'accomplishment_pct_updated_by_name' => null,
            'accomplishment_pct_ro_updated_by_name' => null,
            'slippage_updated_by_name' => null,
            'slippage_ro_updated_by_name' => null,
            'risk_aging_updated_by_name' => null,
            'nc_letters_updated_by_name' => null,
        ];

        $mapPhysicalUpdateRow = function ($row) use ($physicalRowDefaults, $usersById) {
            return array_merge($physicalRowDefaults, [
                'year' => isset($row->year) ? (int) $row->year : null,
                'month_number' => isset($row->month) ? (int) $row->month : null,
                'status_project_fou' => $row->status_project_fou,
                'status_project_ro' => $row->status_project_ro ?? null,
                'accomplishment_pct' => $row->accomplishment_pct,
                'accomplishment_pct_ro' => $row->accomplishment_pct_ro,
                'slippage' => $row->slippage,
                'slippage_ro' => $row->slippage_ro,
                'risk_aging' => $row->risk_aging,
                'nc_letters' => $row->nc_letters,
                'status_project_fou_updated_at' => $row->status_project_fou_updated_at,
                'status_project_ro_updated_at' => $row->status_project_ro_updated_at,
                'accomplishment_pct_updated_at' => $row->accomplishment_pct_updated_at,
                'accomplishment_pct_ro_updated_at' => $row->accomplishment_pct_ro_updated_at,
                'slippage_updated_at' => $row->slippage_updated_at,
                'slippage_ro_updated_at' => $row->slippage_ro_updated_at,
                'risk_aging_updated_at' => $row->risk_aging_updated_at,
                'nc_letters_updated_at' => $row->nc_letters_updated_at,
                'status_project_fou_updated_by' => $row->status_project_fou_updated_by,
                'status_project_ro_updated_by' => $row->status_project_ro_updated_by,
                'accomplishment_pct_updated_by' => $row->accomplishment_pct_updated_by,
                'accomplishment_pct_ro_updated_by' => $row->accomplishment_pct_ro_updated_by,
                'slippage_updated_by' => $row->slippage_updated_by,
                'slippage_ro_updated_by' => $row->slippage_ro_updated_by,
                'risk_aging_updated_by' => $row->risk_aging_updated_by,
                'nc_letters_updated_by' => $row->nc_letters_updated_by,
                'status_project_fou_updated_by_name' => $row->status_project_fou_updated_by && $usersById->has($row->status_project_fou_updated_by)
                    ? trim($usersById[$row->status_project_fou_updated_by]->fname . ' ' . $usersById[$row->status_project_fou_updated_by]->lname)
                    : null,
                'status_project_ro_updated_by_name' => $row->status_project_ro_updated_by && $usersById->has($row->status_project_ro_updated_by)
                    ? trim($usersById[$row->status_project_ro_updated_by]->fname . ' ' . $usersById[$row->status_project_ro_updated_by]->lname)
                    : null,
                'accomplishment_pct_updated_by_name' => $row->accomplishment_pct_updated_by && $usersById->has($row->accomplishment_pct_updated_by)
                    ? trim($usersById[$row->accomplishment_pct_updated_by]->fname . ' ' . $usersById[$row->accomplishment_pct_updated_by]->lname)
                    : null,
                'accomplishment_pct_ro_updated_by_name' => $row->accomplishment_pct_ro_updated_by && $usersById->has($row->accomplishment_pct_ro_updated_by)
                    ? trim($usersById[$row->accomplishment_pct_ro_updated_by]->fname . ' ' . $usersById[$row->accomplishment_pct_ro_updated_by]->lname)
                    : null,
                'slippage_updated_by_name' => $row->slippage_updated_by && $usersById->has($row->slippage_updated_by)
                    ? trim($usersById[$row->slippage_updated_by]->fname . ' ' . $usersById[$row->slippage_updated_by]->lname)
                    : null,
                'slippage_ro_updated_by_name' => $row->slippage_ro_updated_by && $usersById->has($row->slippage_ro_updated_by)
                    ? trim($usersById[$row->slippage_ro_updated_by]->fname . ' ' . $usersById[$row->slippage_ro_updated_by]->lname)
                    : null,
                'risk_aging_updated_by_name' => $row->risk_aging_updated_by && $usersById->has($row->risk_aging_updated_by)
                    ? trim($usersById[$row->risk_aging_updated_by]->fname . ' ' . $usersById[$row->risk_aging_updated_by]->lname)
                    : null,
                'nc_letters_updated_by_name' => $row->nc_letters_updated_by && $usersById->has($row->nc_letters_updated_by)
                    ? trim($usersById[$row->nc_letters_updated_by]->fname . ' ' . $usersById[$row->nc_letters_updated_by]->lname)
                    : null,
            ]);
        };

        $physicalByMonth = [];
        foreach ($physicalUpdates as $row) {
            $physicalByMonth[(int) $row->month] = $mapPhysicalUpdateRow($row);
        }

        $physicalTimelineByPeriod = [];
        foreach ($allPhysicalUpdates as $row) {
            $physicalTimelineByPeriod[sprintf('%04d-%02d', (int) $row->year, (int) $row->month)] = $mapPhysicalUpdateRow($row);
        }

        if (!isset($physicalByMonth[$currentMonth])) {
            $physicalByMonth[$currentMonth] = $physicalRowDefaults;
        } else {
            $physicalByMonth[$currentMonth] = array_merge($physicalRowDefaults, $physicalByMonth[$currentMonth]);
        }

        $currentStatusProjectRo = trim((string) ($physicalByMonth[$currentMonth]['status_project_ro'] ?? ''));
        if ($currentStatusProjectRo === '' && !empty($subayPhysicalValues['status_project_ro'])) {
            $physicalByMonth[$currentMonth]['status_project_ro'] = $subayPhysicalValues['status_project_ro'];
            if (empty($physicalByMonth[$currentMonth]['status_project_ro_updated_at'])) {
                $physicalByMonth[$currentMonth]['status_project_ro_updated_at'] = $subayPhysicalValues['updated_at'];
            }
        }

        $currentAccomplishmentPctRo = $physicalByMonth[$currentMonth]['accomplishment_pct_ro'] ?? null;
        if (
            ($currentAccomplishmentPctRo === null || $currentAccomplishmentPctRo === '')
            && $subayPhysicalValues['accomplishment_pct_ro'] !== null
        ) {
            $physicalByMonth[$currentMonth]['accomplishment_pct_ro'] = $subayPhysicalValues['accomplishment_pct_ro'];
            if (empty($physicalByMonth[$currentMonth]['accomplishment_pct_ro_updated_at'])) {
                $physicalByMonth[$currentMonth]['accomplishment_pct_ro_updated_at'] = $subayPhysicalValues['updated_at'];
            }
        }

        $currentSlippageRo = $physicalByMonth[$currentMonth]['slippage_ro'] ?? null;
        if (
            ($currentSlippageRo === null || $currentSlippageRo === '')
            && $projectAtRiskValues['slippage_ro'] !== null
        ) {
            $physicalByMonth[$currentMonth]['slippage_ro'] = $projectAtRiskValues['slippage_ro'];
            if (empty($physicalByMonth[$currentMonth]['slippage_ro_updated_at'])) {
                $physicalByMonth[$currentMonth]['slippage_ro_updated_at'] = $projectAtRiskValues['updated_at'];
            }
        }

        $currentRiskAging = trim((string) ($physicalByMonth[$currentMonth]['risk_aging'] ?? ''));
        if ($currentRiskAging === '' && !empty($projectAtRiskValues['risk_aging'])) {
            $physicalByMonth[$currentMonth]['risk_aging'] = $projectAtRiskValues['risk_aging'];
            if (empty($physicalByMonth[$currentMonth]['risk_aging_updated_at'])) {
                $physicalByMonth[$currentMonth]['risk_aging_updated_at'] = $projectAtRiskValues['updated_at'];
            }
        }

        $currentPhysicalTimelineKey = sprintf('%04d-%02d', (int) $currentYear, (int) $currentMonth);
        $physicalTimelineByPeriod[$currentPhysicalTimelineKey] = array_merge(
            $physicalRowDefaults,
            $physicalTimelineByPeriod[$currentPhysicalTimelineKey] ?? [],
            [
                'year' => (int) $currentYear,
                'month_number' => (int) $currentMonth,
            ],
            $physicalByMonth[$currentMonth] ?? []
        );

        $currentPhysical = $physicalByMonth[$currentMonth] ?? null;

        $financialByMonth = [];
        $financialTotals = [
            'obligation' => 0,
            'disbursed_amount' => 0,
            'reverted_amount' => 0,
        ];
        $financialRowDefaults = [
            'obligation' => null,
            'disbursed_amount' => null,
            'reverted_amount' => null,
            'utilization_rate' => null,
            'updated_at' => null,
            'updated_by' => null,
            'updated_by_name' => null,
            'obligation_updated_at' => null,
            'obligation_updated_by' => null,
            'disbursed_amount_updated_at' => null,
            'disbursed_amount_updated_by' => null,
            'reverted_amount_updated_at' => null,
            'reverted_amount_updated_by' => null,
            'utilization_rate_updated_at' => null,
            'utilization_rate_updated_by' => null,
        ];
        $financialUpdates = collect();

        if (\Illuminate\Support\Facades\Schema::hasTable('locally_funded_financial_updates')) {
            $financialUpdates = \Illuminate\Support\Facades\DB::table('locally_funded_financial_updates')
                ->leftJoin('tbusers', 'tbusers.idno', '=', 'locally_funded_financial_updates.updated_by')
                ->where('project_id', $project->id)
                ->where('year', $currentYear)
                ->select(
                    'locally_funded_financial_updates.month',
                    'locally_funded_financial_updates.obligation',
                    'locally_funded_financial_updates.disbursed_amount',
                    'locally_funded_financial_updates.reverted_amount',
                    'locally_funded_financial_updates.utilization_rate',
                    'locally_funded_financial_updates.updated_at',
                    'locally_funded_financial_updates.updated_by',
                    'locally_funded_financial_updates.obligation_updated_at',
                    'locally_funded_financial_updates.obligation_updated_by',
                    'locally_funded_financial_updates.disbursed_amount_updated_at',
                    'locally_funded_financial_updates.disbursed_amount_updated_by',
                    'locally_funded_financial_updates.reverted_amount_updated_at',
                    'locally_funded_financial_updates.reverted_amount_updated_by',
                    'locally_funded_financial_updates.utilization_rate_updated_at',
                    'locally_funded_financial_updates.utilization_rate_updated_by',
                    'tbusers.fname',
                    'tbusers.lname'
                )
                ->get();

            foreach ($financialUpdates as $row) {
                $financialByMonth[(int) $row->month] = array_merge($financialRowDefaults, [
                    'obligation' => $row->obligation,
                    'disbursed_amount' => $row->disbursed_amount,
                    'reverted_amount' => $row->reverted_amount,
                    'utilization_rate' => $row->utilization_rate,
                    'updated_at' => $row->updated_at,
                    'updated_by' => $row->updated_by,
                    'updated_by_name' => $row->updated_by ? trim(($row->fname ?? '') . ' ' . ($row->lname ?? '')) : null,
                    'obligation_updated_at' => $row->obligation_updated_at,
                    'obligation_updated_by' => $row->obligation_updated_by,
                    'disbursed_amount_updated_at' => $row->disbursed_amount_updated_at,
                    'disbursed_amount_updated_by' => $row->disbursed_amount_updated_by,
                    'reverted_amount_updated_at' => $row->reverted_amount_updated_at,
                    'reverted_amount_updated_by' => $row->reverted_amount_updated_by,
                    'utilization_rate_updated_at' => $row->utilization_rate_updated_at,
                    'utilization_rate_updated_by' => $row->utilization_rate_updated_by,
                ]);
            }

            foreach ($financialByMonth as $row) {
                $financialTotals['obligation'] += (float) ($row['obligation'] ?? 0);
                $financialTotals['disbursed_amount'] += (float) ($row['disbursed_amount'] ?? 0);
                $financialTotals['reverted_amount'] += (float) ($row['reverted_amount'] ?? 0);
            }
        }

        foreach (['obligation', 'disbursed_amount', 'reverted_amount'] as $field) {
            $hasMonthlyValue = collect($financialByMonth)->contains(function ($row) use ($field) {
                $value = $row[$field] ?? null;
                return $value !== null && $value !== '';
            });

            if ($hasMonthlyValue) {
                continue;
            }

            $fallbackValue = $subayFinancialValues[$field] ?? null;
            if ($fallbackValue === null) {
                $fallbackValue = $parseNumericValue($project->{$field} ?? null);
            }
            if ($fallbackValue === null) {
                continue;
            }

            if (!isset($financialByMonth[$currentMonth])) {
                $financialByMonth[$currentMonth] = $financialRowDefaults;
            } else {
                $financialByMonth[$currentMonth] = array_merge($financialRowDefaults, $financialByMonth[$currentMonth]);
            }

            $financialByMonth[$currentMonth][$field] = $fallbackValue;
            $financialByMonth[$currentMonth][$field . '_updated_at'] = $subayFinancialValues['updated_at'] ?? null;
            $financialByMonth[$currentMonth][$field . '_updated_by'] = null;
            $financialTotals[$field] = (float) $fallbackValue;
        }

        $activityLogs = [];
        $pushLog = function ($timestamp, $userId, $action, $section, $field, array $meta = []) use (&$activityLogs) {
            if (empty($timestamp)) {
                return;
            }

            try {
                $loggedAt = $timestamp instanceof \DateTimeInterface
                    ? $timestamp
                    : \Carbon\Carbon::parse($timestamp);
            } catch (\Exception $e) {
                return;
            }

            $details = [];
            if (array_key_exists('month', $meta) && $meta['month'] !== null) {
                $details[] = 'Month: ' . $meta['month'];
            }
            if (array_key_exists('value', $meta) && $meta['value'] !== null && $meta['value'] !== '') {
                $details[] = $meta['value'];
            }

            $activityLogs[] = [
                'timestamp' => $loggedAt,
                'user_id' => $userId,
                'action' => $action,
                'section' => $section,
                'field' => $field,
                'details' => count($details) ? implode(' • ', $details) : null,
            ];
        };

        $formatPhysicalValue = function ($field, $value) {
            if ($value === null || $value === '') {
                return null;
            }
            if (in_array($field, ['accomplishment_pct', 'accomplishment_pct_ro', 'slippage', 'slippage_ro'], true)) {
                return number_format((float) $value, 2) . '%';
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
            return (string) $value;
        };

        $formatFinancialValue = function ($field, $value) {
            if ($value === null || $value === '') {
                return null;
            }
            if (in_array($field, ['obligation', 'disbursed_amount', 'reverted_amount'], true)) {
                return '₱ ' . number_format((float) $value, 2);
            }
            if ($field === 'utilization_rate') {
                return number_format((float) $value, 2) . '%';
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
            return (string) $value;
        };

        $physicalFieldMap = [
            'status_project_fou' => 'Status (Actual)',
            'status_project_ro' => 'Status (Subaybayan)',
            'accomplishment_pct' => 'Accomplishment % (Actual)',
            'accomplishment_pct_ro' => 'Accomplishment % (Subaybayan)',
            'slippage' => 'Slippage (Actual)',
            'slippage_ro' => 'Slippage (Subaybayan)',
            'risk_aging' => 'Risk/Aging',
            'nc_letters' => 'NC Letters',
        ];

        foreach ($physicalUpdates as $row) {
            $month = $row->month ?? null;
            foreach ($physicalFieldMap as $field => $label) {
                $updatedAtField = $field . '_updated_at';
                $updatedByField = $field . '_updated_by';
                if (!empty($row->{$updatedAtField})) {
                    $pushLog(
                        $row->{$updatedAtField},
                        $row->{$updatedByField} ?? null,
                        'update',
                        'Physical',
                        $label,
                        [
                            'month' => $month,
                            'value' => $formatPhysicalValue($field, $row->{$field} ?? null),
                        ]
                    );
                }
            }
        }

        $financialFieldMap = [
            'obligation' => 'Obligation',
            'disbursed_amount' => 'Disbursed Amount',
            'reverted_amount' => 'Reverted Amount',
            'utilization_rate' => 'Utilization Rate',
        ];

        foreach ($financialUpdates as $row) {
            $month = $row->month ?? null;
            foreach ($financialFieldMap as $field => $label) {
                $updatedAtField = $field . '_updated_at';
                $updatedByField = $field . '_updated_by';
                if (!empty($row->{$updatedAtField})) {
                    $pushLog(
                        $row->{$updatedAtField},
                        $row->{$updatedByField} ?? null,
                        'update',
                        'Financial',
                        $label,
                        [
                            'month' => $month,
                            'value' => $formatFinancialValue($field, $row->{$field} ?? null),
                        ]
                    );
                }
            }
        }

        $formatProjectValue = function ($value) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('M d, Y');
            }
            if ($value === null || $value === '') {
                return null;
            }
            return (string) $value;
        };

        $projectLogFields = [
            ['field' => 'physical_remarks', 'label' => 'Physical Remarks', 'section' => 'Physical', 'action' => 'remarks', 'updated_at' => 'physical_remarks_updated_at', 'updated_by' => 'physical_remarks_updated_by'],
            ['field' => 'financial_remarks', 'label' => 'Financial Remarks', 'section' => 'Financial', 'action' => 'remarks', 'updated_at' => 'financial_remarks_updated_at', 'updated_by' => 'financial_remarks_updated_by'],
            ['field' => 'po_monitoring_date', 'label' => 'PO Monitoring Date', 'section' => 'Monitoring', 'action' => 'update', 'updated_at' => 'po_monitoring_date_updated_at', 'updated_by' => 'po_monitoring_date_updated_by'],
            ['field' => 'po_final_inspection', 'label' => 'PO Final Inspection', 'section' => 'Monitoring', 'action' => 'update', 'updated_at' => 'po_final_inspection_updated_at', 'updated_by' => 'po_final_inspection_updated_by'],
            ['field' => 'po_remarks', 'label' => 'PO Remarks', 'section' => 'Monitoring', 'action' => 'remarks', 'updated_at' => 'po_remarks_updated_at', 'updated_by' => 'po_remarks_updated_by'],
            ['field' => 'ro_monitoring_date', 'label' => 'RO Monitoring Date', 'section' => 'Monitoring', 'action' => 'update', 'updated_at' => 'ro_monitoring_date_updated_at', 'updated_by' => 'ro_monitoring_date_updated_by'],
            ['field' => 'ro_final_inspection', 'label' => 'RO Final Inspection', 'section' => 'Monitoring', 'action' => 'update', 'updated_at' => 'ro_final_inspection_updated_at', 'updated_by' => 'ro_final_inspection_updated_by'],
            ['field' => 'ro_remarks', 'label' => 'RO Remarks', 'section' => 'Monitoring', 'action' => 'remarks', 'updated_at' => 'ro_remarks_updated_at', 'updated_by' => 'ro_remarks_updated_by'],
            ['field' => 'pcr_submission_deadline', 'label' => 'PCR Submission Deadline', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'pcr_submission_deadline_updated_at', 'updated_by' => 'pcr_submission_deadline_updated_by'],
            ['field' => 'pcr_date_submitted_to_po', 'label' => 'PCR Date Submitted to PO', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'pcr_date_submitted_to_po_updated_at', 'updated_by' => 'pcr_date_submitted_to_po_updated_by'],
            ['field' => 'pcr_mov_file_path', 'label' => 'PCR MOV Upload', 'section' => 'Post Implementation', 'action' => 'upload', 'updated_at' => 'pcr_mov_uploaded_at', 'updated_by' => 'pcr_mov_uploaded_by'],
            ['field' => 'pcr_date_received_by_ro', 'label' => 'PCR Date Received by RO', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'pcr_date_received_by_ro_updated_at', 'updated_by' => 'pcr_date_received_by_ro_updated_by'],
            ['field' => 'pcr_remarks', 'label' => 'PCR Remarks', 'section' => 'Post Implementation', 'action' => 'remarks', 'updated_at' => 'pcr_remarks_updated_at', 'updated_by' => 'pcr_remarks_updated_by'],
            ['field' => 'rssa_report_deadline', 'label' => 'RSSA Report Deadline', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'rssa_report_deadline_updated_at', 'updated_by' => 'rssa_report_deadline_updated_by'],
            ['field' => 'rssa_submission_status', 'label' => 'RSSA Submission Status', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'rssa_submission_status_updated_at', 'updated_by' => 'rssa_submission_status_updated_by'],
            ['field' => 'rssa_date_submitted_to_po', 'label' => 'RSSA Date Submitted to PO', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'rssa_date_submitted_to_po_updated_at', 'updated_by' => 'rssa_date_submitted_to_po_updated_by'],
            ['field' => 'rssa_date_received_by_ro', 'label' => 'RSSA Date Received by RO', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'rssa_date_received_by_ro_updated_at', 'updated_by' => 'rssa_date_received_by_ro_updated_by'],
            ['field' => 'rssa_date_submitted_to_co', 'label' => 'RSSA Date Submitted to CO', 'section' => 'Post Implementation', 'action' => 'update', 'updated_at' => 'rssa_date_submitted_to_co_updated_at', 'updated_by' => 'rssa_date_submitted_to_co_updated_by'],
            ['field' => 'rssa_remarks', 'label' => 'RSSA Remarks', 'section' => 'Post Implementation', 'action' => 'remarks', 'updated_at' => 'rssa_remarks_updated_at', 'updated_by' => 'rssa_remarks_updated_by'],
        ];

        foreach ($projectLogFields as $config) {
            $updatedAtField = $config['updated_at'];
            $updatedByField = $config['updated_by'];
            $updatedAtValue = $project->{$updatedAtField} ?? null;
            if (!empty($updatedAtValue)) {
                $pushLog(
                    $updatedAtValue,
                    $project->{$updatedByField} ?? null,
                    $config['action'],
                    $config['section'],
                    $config['label'],
                    ['value' => $formatProjectValue($project->{$config['field']} ?? null)]
                );
            }
        }

        $activityLogs = $this->mergeLocallyFundedActivityLogs($activityLogs, $project);

        $activityLogs = collect($activityLogs)
            ->sortByDesc('timestamp')
            ->values()
            ->all();

        $logUserIds = collect($activityLogs)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $logUsers = $logUserIds->isEmpty()
            ? collect()
            : \Illuminate\Support\Facades\DB::table('tbusers')
                ->whereIn('idno', $logUserIds)
                ->get(['idno', 'fname', 'lname', 'agency'])
                ->keyBy('idno');

        foreach ($activityLogs as &$log) {
            $user = $log['user_id'] && $logUsers->has($log['user_id'])
                ? $logUsers[$log['user_id']]
                : null;
            $log['user_name'] = $user ? trim($user->fname . ' ' . $user->lname) : null;
            $log['user_agency'] = $user->agency ?? null;
        }
        unset($log);

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
        $fundSources = ['SBDP', 'FALGU', 'CMGP', 'SGLGIF', 'SAFPB'];
        $fundingYears = [2025, 2024, 2023, 2022, 2021];

        $financialAllocationTotal = (float) $project->lgsf_allocation;
        $financialDisbursedTotal = (float) ($financialTotals['disbursed_amount'] ?? 0);
        $financialRevertedTotal = (float) ($financialTotals['reverted_amount'] ?? 0);
        $financialBalance = $financialAllocationTotal - ($financialDisbursedTotal + $financialRevertedTotal);
        $financialUtilizationRate = $financialAllocationTotal > 0
            ? (($financialAllocationTotal - $financialBalance) / $financialAllocationTotal) * 100
            : 0;

        $remarksUserIds = collect([
            $project->physical_remarks_updated_by,
            $project->physical_remarks_encoded_by,
            $project->financial_remarks_updated_by,
            $project->financial_remarks_encoded_by,
            $project->po_monitoring_date_updated_by,
            $project->po_final_inspection_updated_by,
            $project->po_remarks_updated_by,
            $project->ro_monitoring_date_updated_by,
            $project->ro_final_inspection_updated_by,
            $project->ro_remarks_updated_by,
            $project->pcr_submission_deadline_updated_by,
            $project->pcr_date_submitted_to_po_updated_by,
            $project->pcr_mov_uploaded_by,
            $project->pcr_date_received_by_ro_updated_by,
            $project->pcr_remarks_updated_by,
            $project->rssa_report_deadline_updated_by,
            $project->rssa_submission_status_updated_by,
            $project->rssa_date_submitted_to_po_updated_by,
            $project->rssa_date_received_by_ro_updated_by,
            $project->rssa_date_submitted_to_co_updated_by,
            $project->rssa_remarks_updated_by,
        ])->filter()->unique()->values();

        $remarksUsers = $remarksUserIds->isEmpty()
            ? collect()
            : \Illuminate\Support\Facades\DB::table('tbusers')
                ->whereIn('idno', $remarksUserIds)
                ->get(['idno', 'fname', 'lname'])
                ->keyBy('idno');

        $physicalRemarksUpdatedByName = $project->physical_remarks_updated_by && $remarksUsers->has($project->physical_remarks_updated_by)
            ? trim($remarksUsers[$project->physical_remarks_updated_by]->fname . ' ' . $remarksUsers[$project->physical_remarks_updated_by]->lname)
            : null;
        $physicalRemarksEncodedByName = $project->physical_remarks_encoded_by && $remarksUsers->has($project->physical_remarks_encoded_by)
            ? trim($remarksUsers[$project->physical_remarks_encoded_by]->fname . ' ' . $remarksUsers[$project->physical_remarks_encoded_by]->lname)
            : null;
        $financialRemarksUpdatedByName = $project->financial_remarks_updated_by && $remarksUsers->has($project->financial_remarks_updated_by)
            ? trim($remarksUsers[$project->financial_remarks_updated_by]->fname . ' ' . $remarksUsers[$project->financial_remarks_updated_by]->lname)
            : null;
        $financialRemarksEncodedByName = $project->financial_remarks_encoded_by && $remarksUsers->has($project->financial_remarks_encoded_by)
            ? trim($remarksUsers[$project->financial_remarks_encoded_by]->fname . ' ' . $remarksUsers[$project->financial_remarks_encoded_by]->lname)
            : null;

        // Monitoring field user names
        $poMonitoringDateUpdatedByName = $project->po_monitoring_date_updated_by && $remarksUsers->has($project->po_monitoring_date_updated_by)
            ? trim($remarksUsers[$project->po_monitoring_date_updated_by]->fname . ' ' . $remarksUsers[$project->po_monitoring_date_updated_by]->lname)
            : null;
        $poFinalInspectionUpdatedByName = $project->po_final_inspection_updated_by && $remarksUsers->has($project->po_final_inspection_updated_by)
            ? trim($remarksUsers[$project->po_final_inspection_updated_by]->fname . ' ' . $remarksUsers[$project->po_final_inspection_updated_by]->lname)
            : null;
        $poRemarksUpdatedByName = $project->po_remarks_updated_by && $remarksUsers->has($project->po_remarks_updated_by)
            ? trim($remarksUsers[$project->po_remarks_updated_by]->fname . ' ' . $remarksUsers[$project->po_remarks_updated_by]->lname)
            : null;
        $roMonitoringDateUpdatedByName = $project->ro_monitoring_date_updated_by && $remarksUsers->has($project->ro_monitoring_date_updated_by)
            ? trim($remarksUsers[$project->ro_monitoring_date_updated_by]->fname . ' ' . $remarksUsers[$project->ro_monitoring_date_updated_by]->lname)
            : null;
        $roFinalInspectionUpdatedByName = $project->ro_final_inspection_updated_by && $remarksUsers->has($project->ro_final_inspection_updated_by)
            ? trim($remarksUsers[$project->ro_final_inspection_updated_by]->fname . ' ' . $remarksUsers[$project->ro_final_inspection_updated_by]->lname)
            : null;
        $roRemarksUpdatedByName = $project->ro_remarks_updated_by && $remarksUsers->has($project->ro_remarks_updated_by)
            ? trim($remarksUsers[$project->ro_remarks_updated_by]->fname . ' ' . $remarksUsers[$project->ro_remarks_updated_by]->lname)
            : null;

        // Post implementation requirements user names
        $pcrSubmissionDeadlineUpdatedByName = $project->pcr_submission_deadline_updated_by && $remarksUsers->has($project->pcr_submission_deadline_updated_by)
            ? trim($remarksUsers[$project->pcr_submission_deadline_updated_by]->fname . ' ' . $remarksUsers[$project->pcr_submission_deadline_updated_by]->lname)
            : null;
        $pcrDateSubmittedToPoUpdatedByName = $project->pcr_date_submitted_to_po_updated_by && $remarksUsers->has($project->pcr_date_submitted_to_po_updated_by)
            ? trim($remarksUsers[$project->pcr_date_submitted_to_po_updated_by]->fname . ' ' . $remarksUsers[$project->pcr_date_submitted_to_po_updated_by]->lname)
            : null;
        $pcrMovUploadedByName = $project->pcr_mov_uploaded_by && $remarksUsers->has($project->pcr_mov_uploaded_by)
            ? trim($remarksUsers[$project->pcr_mov_uploaded_by]->fname . ' ' . $remarksUsers[$project->pcr_mov_uploaded_by]->lname)
            : null;
        $pcrDateReceivedByRoUpdatedByName = $project->pcr_date_received_by_ro_updated_by && $remarksUsers->has($project->pcr_date_received_by_ro_updated_by)
            ? trim($remarksUsers[$project->pcr_date_received_by_ro_updated_by]->fname . ' ' . $remarksUsers[$project->pcr_date_received_by_ro_updated_by]->lname)
            : null;
        $pcrRemarksUpdatedByName = $project->pcr_remarks_updated_by && $remarksUsers->has($project->pcr_remarks_updated_by)
            ? trim($remarksUsers[$project->pcr_remarks_updated_by]->fname . ' ' . $remarksUsers[$project->pcr_remarks_updated_by]->lname)
            : null;
        $rssaReportDeadlineUpdatedByName = $project->rssa_report_deadline_updated_by && $remarksUsers->has($project->rssa_report_deadline_updated_by)
            ? trim($remarksUsers[$project->rssa_report_deadline_updated_by]->fname . ' ' . $remarksUsers[$project->rssa_report_deadline_updated_by]->lname)
            : null;
        $rssaSubmissionStatusUpdatedByName = $project->rssa_submission_status_updated_by && $remarksUsers->has($project->rssa_submission_status_updated_by)
            ? trim($remarksUsers[$project->rssa_submission_status_updated_by]->fname . ' ' . $remarksUsers[$project->rssa_submission_status_updated_by]->lname)
            : null;
        $rssaDateSubmittedToPoUpdatedByName = $project->rssa_date_submitted_to_po_updated_by && $remarksUsers->has($project->rssa_date_submitted_to_po_updated_by)
            ? trim($remarksUsers[$project->rssa_date_submitted_to_po_updated_by]->fname . ' ' . $remarksUsers[$project->rssa_date_submitted_to_po_updated_by]->lname)
            : null;
        $rssaDateReceivedByRoUpdatedByName = $project->rssa_date_received_by_ro_updated_by && $remarksUsers->has($project->rssa_date_received_by_ro_updated_by)
            ? trim($remarksUsers[$project->rssa_date_received_by_ro_updated_by]->fname . ' ' . $remarksUsers[$project->rssa_date_received_by_ro_updated_by]->lname)
            : null;
        $rssaDateSubmittedToCoUpdatedByName = $project->rssa_date_submitted_to_co_updated_by && $remarksUsers->has($project->rssa_date_submitted_to_co_updated_by)
            ? trim($remarksUsers[$project->rssa_date_submitted_to_co_updated_by]->fname . ' ' . $remarksUsers[$project->rssa_date_submitted_to_co_updated_by]->lname)
            : null;
        $rssaRemarksUpdatedByName = $project->rssa_remarks_updated_by && $remarksUsers->has($project->rssa_remarks_updated_by)
            ? trim($remarksUsers[$project->rssa_remarks_updated_by]->fname . ' ' . $remarksUsers[$project->rssa_remarks_updated_by]->lname)
            : null;

        $deadlineConfiguration = $this->deadlineConfigurationForProject($project);
        $effectivePcrSubmissionDeadline = $this->resolveEffectiveProjectDeadline(
            $project,
            $deadlineConfiguration,
            'pcr_submission_deadline'
        );
        $effectiveRssaReportDeadline = $this->resolveEffectiveProjectDeadline(
            $project,
            $deadlineConfiguration,
            'rssa_report_deadline'
        );

        $galleryImages = [];
        if (Schema::hasTable('locally_funded_gallery_images')) {
            $galleryImages = DB::table('locally_funded_gallery_images as lgi')
                ->leftJoin('tbusers as uploader', 'uploader.idno', '=', 'lgi.uploaded_by')
                ->where('lgi.project_id', $project->id)
                ->whereNotNull('lgi.image_path')
                ->orderByDesc('lgi.created_at')
                ->get([
                    'lgi.id',
                    'lgi.category',
                    'lgi.image_path',
                    'lgi.uploaded_by',
                    'lgi.latitude',
                    'lgi.longitude',
                    'lgi.accuracy',
                    'lgi.created_at',
                    DB::raw("NULLIF(TRIM(CONCAT(COALESCE(uploader.fname, ''), ' ', COALESCE(uploader.lname, ''))), '') as uploaded_by_name"),
                ])
                ->map(function ($image) use ($project) {
                    return [
                        'id' => (int) $image->id,
                        'category' => trim((string) ($image->category ?? 'During')) ?: 'During',
                        'image_url' => route('api.mobile.locally-funded.gallery-image', [
                            'project' => (int) $project->id,
                            'galleryImage' => (int) $image->id,
                        ]),
                        'uploaded_by' => $image->uploaded_by !== null ? (int) $image->uploaded_by : null,
                        'uploaded_by_name' => $image->uploaded_by_name,
                        'latitude' => $image->latitude !== null ? (float) $image->latitude : null,
                        'longitude' => $image->longitude !== null ? (float) $image->longitude : null,
                        'accuracy' => $image->accuracy !== null ? (float) $image->accuracy : null,
                        'created_at' => $image->created_at,
                    ];
                })
                ->values()
                ->all();
        }

        return view('projects.locally-funded-show', compact('project', 'provinces', 'provinceMunicipalities', 'fundSources', 'fundingYears', 'physicalByMonth', 'physicalTimelineByPeriod', 'currentPhysical', 'currentYear', 'currentMonth', 'actualCompletionUpdatedByName', 'financialByMonth', 'financialTotals', 'financialBalance', 'financialUtilizationRate', 'physicalRemarksUpdatedByName', 'physicalRemarksEncodedByName', 'financialRemarksUpdatedByName', 'financialRemarksEncodedByName', 'poMonitoringDateUpdatedByName', 'poFinalInspectionUpdatedByName', 'poRemarksUpdatedByName', 'roMonitoringDateUpdatedByName', 'roFinalInspectionUpdatedByName', 'roRemarksUpdatedByName', 'pcrSubmissionDeadlineUpdatedByName', 'pcrDateSubmittedToPoUpdatedByName', 'pcrMovUploadedByName', 'pcrDateReceivedByRoUpdatedByName', 'pcrRemarksUpdatedByName', 'rssaReportDeadlineUpdatedByName', 'rssaSubmissionStatusUpdatedByName', 'rssaDateSubmittedToPoUpdatedByName', 'rssaDateReceivedByRoUpdatedByName', 'rssaDateSubmittedToCoUpdatedByName', 'rssaRemarksUpdatedByName', 'activityLogs', 'effectivePcrSubmissionDeadline', 'effectiveRssaReportDeadline', 'galleryImages'));
    }

    public function viewPcrMov(LocallyFundedProject $project)
    {
        $this->authorizeLocallyFundedProjectAccess($project);

        if (!$project->pcr_mov_file_path) {
            abort(404, 'PCR MOV document not found');
        }

        $filePath = storage_path('app/public/' . $project->pcr_mov_file_path);
        if (!file_exists($filePath)) {
            abort(404, 'PCR MOV file not found on disk');
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
     * Show the edit form for a locally funded project.
     */
    public function edit(LocallyFundedProject $project)
    {
        $this->authorizeLocallyFundedProjectAccess($project);

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
        $fundSources = ['SBDP', 'FALGU', 'CMGP', 'SGLGIF', 'SAFPB'];
        $fundingYears = [2025, 2024, 2023, 2022, 2021];

        $prefill = $project->toArray();
        $prefill['barangay_json'] = json_encode(array_values(array_filter(array_map('trim', explode(',', $project->barangay)))));
        $dateFields = [
            'date_nadai',
            'date_confirmation_fund_receipt',
            'date_posting_itb',
            'date_bid_opening',
            'date_noa',
            'date_ntp',
            'actual_start_date',
            'target_date_completion',
            'revised_target_date_completion',
            'actual_date_completion',
        ];

        foreach ($dateFields as $field) {
            $prefill[$field] = $project->{$field} ? $project->{$field}->format('Y-m-d') : null;
        }
        request()->session()->put('_old_input', $prefill);

        $section = request()->query('section');
        $deadlineConfiguration = $this->deadlineConfigurationForProject($project);
        $effectivePcrSubmissionDeadline = $this->resolveEffectiveProjectDeadline(
            $project,
            $deadlineConfiguration,
            'pcr_submission_deadline'
        );
        $effectiveRssaReportDeadline = $this->resolveEffectiveProjectDeadline(
            $project,
            $deadlineConfiguration,
            'rssa_report_deadline'
        );

        return view('projects.locally-funded-edit', compact('project', 'provinces', 'provinceMunicipalities', 'currentUserOffice', 'fundSources', 'fundingYears', 'section', 'effectivePcrSubmissionDeadline', 'effectiveRssaReportDeadline'));
    }

    /**
     * Show the create form for locally funded projects
     */
    public function create()
    {
        $options = $this->getProjectFormOptions();

        // Get current user's information
        $user = Auth::user();
        $currentUserOffice = $user->office;
        $currentUserRegion = $user->region;
        $currentUserAgency = $user->agency;
        $currentUserProvince = $user->province;

        return view('projects.locally-funded-create', array_merge(
            $options,
            compact('currentUserOffice', 'currentUserRegion', 'currentUserAgency', 'currentUserProvince')
        ));
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
        return response()->json($municipalities);
    }

    /**
     * Store a newly created locally funded project
     */
    public function store(Request $request)
    {
        $this->mergeCleanCurrencyInputs($request);

        // Validate the request
        $validated = $request->validate([
            // Project Profile
            'province' => 'required|string',
            'city_municipality' => 'required|string',
            'barangay_json' => 'required|string',
            'project_name' => 'required|string',
            'funding_year' => 'required|integer|min:2020|max:2099',
            'fund_source' => 'required|string',
            'subaybayan_project_code' => 'required|string|unique:locally_funded_projects,subaybayan_project_code',
            'project_description' => 'required|string',
            'project_type' => 'required|string',
            'date_nadai' => 'required|date',
            'lgsf_allocation' => 'required|numeric|min:0',
            'lgu_counterpart' => 'required|numeric|min:0',
            'no_of_beneficiaries' => 'required|integer|min:0',
            'rainwater_collection_system' => 'nullable|string',
            'date_confirmation_fund_receipt' => 'required|date',
            
            // Contract Information
            'mode_of_procurement' => 'required|string',
            'implementing_unit' => 'required|string',
            'date_posting_itb' => 'required|date',
            'date_bid_opening' => 'required|date',
            'date_noa' => 'required|date',
            'date_ntp' => 'required|date',
            'contractor' => 'required|string',
            'contract_amount' => 'required|numeric|min:0',
            'project_duration' => 'required|string',
            'actual_start_date' => 'required|date',
            'target_date_completion' => 'required|date',
            'revised_target_date_completion' => 'nullable|date',
            'actual_date_completion' => 'nullable|date',

            // Financial Accomplishment
            'disbursed_amount' => 'nullable|numeric|min:0',
            'obligation' => 'nullable|numeric|min:0',
            'reverted_amount' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric|min:0',
            'utilization_rate' => 'nullable|numeric|min:0|max:100',
            'financial_remarks' => 'nullable|string|max:1000',
        ]);

        $validated = $this->sanitizeLocallyFundedPayload($validated);

        // Parse the JSON array of barangays and convert to comma-separated string
        $barangayList = $this->parseBarangaySelection($validated['barangay_json'] ?? null);
        if (count($barangayList) > 0) {
            $validated['barangay'] = implode(',', $barangayList);
        } else {
            return redirect()->back()->withInput()->withErrors(['barangay' => 'Please select at least one barangay']);
        }
        
        // Remove the JSON field as we've converted it
        unset($validated['barangay_json']);

        // Add user_id
        $validated['user_id'] = Auth::id();
        
        // Add office and region from authenticated user
        $user = Auth::user();
        $validated['office'] = InputSanitizer::sanitizeNullablePlainText($user->office) ?? '';
        $validated['region'] = InputSanitizer::sanitizeNullablePlainText($user->region) ?? '';

        // Create the project
        $project = LocallyFundedProject::create($validated);
        $this->ensureFundUtilizationReport($project);

        return redirect()->route('projects.locally-funded')
                        ->with('success', 'Locally funded project created successfully!');
    }

    /**
     * Update a locally funded project.
     */
    public function update(Request $request, LocallyFundedProject $project)
    {
        $this->authorizeLocallyFundedProjectAccess($project);

        $section = $request->input('section');
        /** @var User|null $user */
        $user = Auth::user();
        $canEditProjectProfile = $user instanceof User
            && strtoupper(trim((string) ($user->agency ?? ''))) === 'DILG'
            && trim((string) ($user->province ?? '')) === 'Regional Office'
            && $user->isSuperAdmin();

        if ($section === 'profile' && !$canEditProjectProfile) {
            abort(403, 'Unauthorized');
        }

        $this->mergeCleanCurrencyInputs($request);

        if ($section === 'physical') {
            $rulesByField = [
                'status_project_fou' => 'nullable|string',
                'status_project_ro' => 'nullable|string',
                'accomplishment_pct' => 'nullable|numeric|min:0|max:100',
                'accomplishment_pct_ro' => 'nullable|numeric|min:0|max:100',
                'slippage' => 'nullable|numeric|min:0|max:100',
                'slippage_ro' => 'nullable|numeric|min:0|max:100',
                'risk_aging' => 'nullable|string',
                'nc_letters' => 'nullable|string',
            ];
            $physicalFieldLabels = [
                'status_project_fou' => 'Status (Actual)',
                'status_project_ro' => 'Status (Subaybayan)',
                'accomplishment_pct' => 'Accomplishment % (Actual)',
                'accomplishment_pct_ro' => 'Accomplishment % (Subaybayan)',
                'slippage' => 'Slippage (Actual)',
                'slippage_ro' => 'Slippage (Subaybayan)',
                'risk_aging' => 'Risk/Aging',
                'nc_letters' => 'NC Letters',
            ];
            $requestData = $request->all();
            $provincialEditablePhysicalFields = [
                'status_project_fou',
                'accomplishment_pct',
                'slippage',
            ];
            $restrictedPhysicalRequestKeys = array_merge(
                array_diff(array_keys($rulesByField), $provincialEditablePhysicalFields),
                ['actual_date_completion', 'physical_remarks']
            );

            $isProvincialDilgUser = $user instanceof User
                && $user->isDilgUser()
                && $user->isProvincialUser()
                && !$user->isRegionalOfficeAssignment();

            if ($isProvincialDilgUser) {
                $submittedRestrictedKeys = array_intersect(array_keys($requestData), $restrictedPhysicalRequestKeys);
                if (!empty($submittedRestrictedKeys)) {
                    abort(403, 'Unauthorized');
                }
            }

            $now = now();
            $projectUpdates = [];
            $notifications = [];
            $legacyField = $request->input('physical_field');
            $processedFields = [];

            if (array_key_exists('physical_remarks', $requestData)) {
                $validated = \Illuminate\Support\Facades\Validator::make($requestData, [
                    'physical_remarks' => 'nullable|string|max:1000',
                ])->validate();

                $projectUpdates['physical_remarks'] = $this->sanitizeLocallyFundedRemark($validated['physical_remarks'] ?? null);
                $projectUpdates['physical_remarks_updated_at'] = $now;
                $projectUpdates['physical_remarks_updated_by'] = Auth::id();
                $projectUpdates['physical_remarks_encoded_by'] = $project->physical_remarks_encoded_by ?: Auth::id();
                $notifications[] = ['label' => 'Physical Remarks', 'status_sensitive' => false];
            }

            if (array_key_exists('actual_date_completion', $requestData)) {
                $validated = \Illuminate\Support\Facades\Validator::make($requestData, [
                    'actual_date_completion' => 'nullable|date',
                ])->validate();

                $projectUpdates['actual_date_completion'] = $validated['actual_date_completion'] ?? null;
                $projectUpdates['actual_date_completion_updated_by'] = Auth::id();
                $notifications[] = ['label' => 'Actual Date of Completion', 'status_sensitive' => false];
            }

            if (!empty($projectUpdates)) {
                $project->update($projectUpdates);
            }

            if (is_string($legacyField) && array_key_exists($legacyField, $rulesByField)) {
                $validated = \Illuminate\Support\Facades\Validator::make($requestData, [
                    'month' => 'required|integer|min:1|max:12',
                    $legacyField => $rulesByField[$legacyField],
                ])->validate();

                $month = (int) $validated['month'];
                $value = $this->sanitizeLocallyFundedFieldValue($legacyField, $validated[$legacyField] ?? null);

                \Illuminate\Support\Facades\DB::table('locally_funded_physical_updates')->updateOrInsert(
                    [
                        'project_id' => $project->id,
                        'year' => $now->year,
                        'month' => $month,
                    ],
                    [
                        $legacyField => $value,
                        'updated_by' => Auth::id(),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $formattedValue = $this->formatLocallyFundedActivityValue($legacyField, $value);
                $details = 'Month: ' . $month;
                if ($formattedValue !== null && $formattedValue !== '') {
                    $details .= ' • ' . $formattedValue;
                }

                $this->logLocallyFundedActivity(
                    $project,
                    'update',
                    'Physical',
                    $physicalFieldLabels[$legacyField] ?? $legacyField,
                    $details,
                    $now,
                    Auth::id()
                );

                $notifications[] = [
                    'label' => $physicalFieldLabels[$legacyField] ?? $legacyField,
                    'status_sensitive' => in_array($legacyField, ['status_project_fou', 'status_project_ro'], true),
                ];
                $processedFields[$legacyField] = true;
            }

            foreach ($rulesByField as $field => $rule) {
                if (isset($processedFields[$field]) || !array_key_exists($field, $requestData)) {
                    continue;
                }

                $validated = \Illuminate\Support\Facades\Validator::make($requestData, [
                    $field => 'sometimes|array',
                    $field . '.*' => $rule,
                ])->validate();

                $valuesByMonth = $validated[$field] ?? [];
                if (!is_array($valuesByMonth) || empty($valuesByMonth)) {
                    continue;
                }

                $fieldUpdated = false;

                foreach ($valuesByMonth as $submittedMonth => $rawValue) {
                    $validatedMonth = \Illuminate\Support\Facades\Validator::make(
                        ['month' => $submittedMonth],
                        ['month' => 'required|integer|min:1|max:12']
                    )->validate();

                    $month = (int) $validatedMonth['month'];
                    $value = $this->sanitizeLocallyFundedFieldValue($field, $rawValue);
                    $storedValue = $value === '' ? null : $value;
                    $data = [$field => $storedValue];
                    $data[$field . '_updated_at'] = $now;
                    $data[$field . '_updated_by'] = Auth::id();

                    \Illuminate\Support\Facades\DB::table('locally_funded_physical_updates')->updateOrInsert(
                        [
                            'project_id' => $project->id,
                            'year' => $now->year,
                            'month' => $month,
                        ],
                        array_merge($data, [
                            'updated_by' => Auth::id(),
                            'updated_at' => $now,
                            'created_at' => $now,
                        ])
                    );

                    $formattedValue = $this->formatLocallyFundedActivityValue($field, $storedValue);
                    $details = 'Month: ' . $month;
                    if ($formattedValue !== null && $formattedValue !== '') {
                        $details .= ' • ' . $formattedValue;
                    }

                    $this->logLocallyFundedActivity(
                        $project,
                        'update',
                        'Physical',
                        $physicalFieldLabels[$field] ?? $field,
                        $details,
                        $now,
                        Auth::id()
                    );

                    $fieldUpdated = true;
                }

                if ($fieldUpdated) {
                    $notifications[] = [
                        'label' => $physicalFieldLabels[$field] ?? $field,
                        'status_sensitive' => in_array($field, ['status_project_fou', 'status_project_ro'], true),
                    ];
                }
            }

            foreach ($notifications as $notification) {
                $this->notifyLocallyFundedUpdateRecipients(
                    $project,
                    'updated ' . $notification['label'],
                    $notification['status_sensitive']
                );
            }

            return redirect()->route('locally-funded-project.show', $project)
                ->with('success', 'Physical accomplishment updated successfully!');
        }

        if ($section === 'financial') {
            if (!\Illuminate\Support\Facades\Schema::hasTable('locally_funded_financial_updates')) {
                return redirect()->route('locally-funded-project.show', $project)
                    ->with('error', 'Financial updates table is missing. Please create locally_funded_financial_updates first.');
            }

            if ($request->has('financial_remarks')) {
                $validated = $request->validate([
                    'financial_remarks' => 'nullable|string|max:1000',
                ]);

                $project->update([
                    'financial_remarks' => $this->sanitizeLocallyFundedRemark($validated['financial_remarks'] ?? null),
                    'financial_remarks_updated_at' => now(),
                    'financial_remarks_updated_by' => Auth::id(),
                    'financial_remarks_encoded_by' => $project->financial_remarks_encoded_by ?: Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated Financial Remarks', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'Financial remarks updated successfully!');
            }

            $rulesByField = [
                'obligation' => 'nullable|numeric|min:0',
                'disbursed_amount' => 'nullable|numeric|min:0',
                'reverted_amount' => 'nullable|numeric|min:0',
                'utilization_rate' => 'nullable|numeric|min:0|max:100',
            ];
            $financialFieldLabels = [
                'obligation' => 'Obligation',
                'disbursed_amount' => 'Disbursed Amount',
                'reverted_amount' => 'Reverted Amount',
                'utilization_rate' => 'Utilization Rate',
            ];

            $field = null;
            foreach (array_keys($rulesByField) as $candidate) {
                if (array_key_exists($candidate, $request->all())) {
                    $field = $candidate;
                    break;
                }
            }

            if ($field) {
                $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
                    $field => 'sometimes|array',
                    $field . '.*' => $rulesByField[$field],
                ])->validate();

                $now = now();
                $m = (int) $now->month;
                $updatedCurrentMonth = false;

                if (isset($validated[$field]) && array_key_exists($m, $validated[$field])) {
                    $value = $validated[$field][$m];
                    $data = [$field => $value === '' ? null : $value];
                    $data[$field . '_updated_at'] = $now;
                    $data[$field . '_updated_by'] = Auth::id();

                    \Illuminate\Support\Facades\DB::table('locally_funded_financial_updates')->updateOrInsert(
                        [
                            'project_id' => $project->id,
                            'year' => $now->year,
                            'month' => $m,
                        ],
                        array_merge($data, [
                            'updated_by' => Auth::id(),
                            'updated_at' => $now,
                            'created_at' => $now,
                        ])
                    );
                    $updatedCurrentMonth = true;

                    $formattedValue = $this->formatLocallyFundedActivityValue($field, $value === '' ? null : $value);
                    $details = 'Month: ' . $m;
                    if ($formattedValue !== null && $formattedValue !== '') {
                        $details .= ' • ' . $formattedValue;
                    }
                    $this->logLocallyFundedActivity(
                        $project,
                        'update',
                        'Financial',
                        $financialFieldLabels[$field] ?? $field,
                        $details,
                        $now,
                        Auth::id()
                    );
                }

                if ($updatedCurrentMonth) {
                    $this->notifyLocallyFundedUpdateRecipients(
                        $project,
                        'updated ' . ($financialFieldLabels[$field] ?? $field),
                        false
                    );
                }

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'Financial accomplishment updated successfully!');
            }

            return redirect()->route('locally-funded-project.show', $project)
                ->with('success', 'Financial accomplishment updated successfully!');
        }

        if ($section === 'monitoring') {
            // Handle PO monitoring fields
            if ($request->has('po_monitoring_date')) {
                $validated = $request->validate([
                    'po_monitoring_date' => 'nullable|date',
                ]);

                $project->update([
                    'po_monitoring_date' => $validated['po_monitoring_date'] ?? null,
                    'po_monitoring_date_updated_at' => now(),
                    'po_monitoring_date_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PO Monitoring Date', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PO monitoring date updated successfully!');
            }

            if ($request->has('po_final_inspection')) {
                $validated = $request->validate([
                    'po_final_inspection' => 'nullable|string|in:Yes,No',
                ]);

                $project->update([
                    'po_final_inspection' => $validated['po_final_inspection'] ?? null,
                    'po_final_inspection_updated_at' => now(),
                    'po_final_inspection_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PO Final Inspection', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PO final inspection updated successfully!');
            }

            if ($request->has('po_remarks')) {
                $validated = $request->validate([
                    'po_remarks' => 'nullable|string|max:1000',
                ]);

                $project->update([
                    'po_remarks' => $this->sanitizeLocallyFundedRemark($validated['po_remarks'] ?? null),
                    'po_remarks_updated_at' => now(),
                    'po_remarks_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PO Remarks', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PO remarks updated successfully!');
            }

            // Handle RO monitoring fields
            if ($request->has('ro_monitoring_date')) {
                $validated = $request->validate([
                    'ro_monitoring_date' => 'nullable|date',
                ]);

                $project->update([
                    'ro_monitoring_date' => $validated['ro_monitoring_date'] ?? null,
                    'ro_monitoring_date_updated_at' => now(),
                    'ro_monitoring_date_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RO Monitoring Date', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RO monitoring date updated successfully!');
            }

            if ($request->has('ro_final_inspection')) {
                $validated = $request->validate([
                    'ro_final_inspection' => 'nullable|string|in:Yes,No',
                ]);

                $project->update([
                    'ro_final_inspection' => $validated['ro_final_inspection'] ?? null,
                    'ro_final_inspection_updated_at' => now(),
                    'ro_final_inspection_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RO Final Inspection', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RO final inspection updated successfully!');
            }

            if ($request->has('ro_remarks')) {
                $validated = $request->validate([
                    'ro_remarks' => 'nullable|string|max:1000',
                ]);

                $project->update([
                    'ro_remarks' => $this->sanitizeLocallyFundedRemark($validated['ro_remarks'] ?? null),
                    'ro_remarks_updated_at' => now(),
                    'ro_remarks_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RO Remarks', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RO remarks updated successfully!');
            }

            // Post implementation requirements (PCR + RSSA)
            if ($request->has('pcr_submission_deadline')) {
                $validated = $request->validate([
                    'pcr_submission_deadline' => 'nullable|date',
                ]);

                $project->update([
                    'pcr_submission_deadline' => $validated['pcr_submission_deadline'] ?? null,
                    'pcr_submission_deadline_updated_at' => now(),
                    'pcr_submission_deadline_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PCR Submission Deadline', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PCR submission deadline updated successfully!');
            }

            if ($request->has('pcr_date_submitted_to_po')) {
                $validated = $request->validate([
                    'pcr_date_submitted_to_po' => 'nullable|date',
                ]);

                $project->update([
                    'pcr_date_submitted_to_po' => $validated['pcr_date_submitted_to_po'] ?? null,
                    'pcr_date_submitted_to_po_updated_at' => now(),
                    'pcr_date_submitted_to_po_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PCR Date Submitted to PO', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PCR date submitted to PO updated successfully!');
            }

            if ($request->hasFile('pcr_mov_file')) {
                $request->validate([
                    'pcr_mov_file' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
                ]);

                $oldFilePath = $project->pcr_mov_file_path;
                $file = $request->file('pcr_mov_file');
                $path = $file->store('lfp/pcr/' . $project->id, 'public');
                $now = now();

                $project->update([
                    'pcr_mov_file_path' => $path,
                    'pcr_mov_uploaded_at' => $now,
                    'pcr_mov_uploaded_by' => Auth::id(),
                    'pcr_date_received_by_ro' => $now->toDateString(),
                    'pcr_date_received_by_ro_updated_at' => $now,
                    'pcr_date_received_by_ro_updated_by' => Auth::id(),
                ]);

                if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
                $this->notifyLocallyFundedUpdateRecipients($project, 'uploaded PCR MOV', true);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PCR MOV uploaded successfully!');
            }

            if ($request->has('pcr_date_received_by_ro')) {
                $validated = $request->validate([
                    'pcr_date_received_by_ro' => 'nullable|date',
                ]);

                $project->update([
                    'pcr_date_received_by_ro' => $validated['pcr_date_received_by_ro'] ?? null,
                    'pcr_date_received_by_ro_updated_at' => now(),
                    'pcr_date_received_by_ro_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PCR Date Received by RO', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PCR date received by RO updated successfully!');
            }

            if ($request->has('pcr_remarks')) {
                $validated = $request->validate([
                    'pcr_remarks' => 'nullable|string|max:1000',
                ]);

                $project->update([
                    'pcr_remarks' => $this->sanitizeLocallyFundedRemark($validated['pcr_remarks'] ?? null),
                    'pcr_remarks_updated_at' => now(),
                    'pcr_remarks_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated PCR Remarks', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'PCR remarks updated successfully!');
            }

            if ($request->has('rssa_report_deadline')) {
                $validated = $request->validate([
                    'rssa_report_deadline' => 'nullable|date',
                ]);

                $project->update([
                    'rssa_report_deadline' => $validated['rssa_report_deadline'] ?? null,
                    'rssa_report_deadline_updated_at' => now(),
                    'rssa_report_deadline_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RSSA Report Deadline', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RSSA report deadline updated successfully!');
            }

            if ($request->has('rssa_submission_status')) {
                $validated = $request->validate([
                    'rssa_submission_status' => 'nullable|string|max:255',
                ]);

                $project->update([
                    'rssa_submission_status' => InputSanitizer::sanitizeNullablePlainText($validated['rssa_submission_status'] ?? null),
                    'rssa_submission_status_updated_at' => now(),
                    'rssa_submission_status_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RSSA Submission Status', true);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RSSA submission status updated successfully!');
            }

            if ($request->has('rssa_date_submitted_to_po')) {
                $validated = $request->validate([
                    'rssa_date_submitted_to_po' => 'nullable|date',
                ]);

                $project->update([
                    'rssa_date_submitted_to_po' => $validated['rssa_date_submitted_to_po'] ?? null,
                    'rssa_date_submitted_to_po_updated_at' => now(),
                    'rssa_date_submitted_to_po_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RSSA Date Submitted to PO', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RSSA date submitted to PO updated successfully!');
            }

            if ($request->has('rssa_date_received_by_ro')) {
                $validated = $request->validate([
                    'rssa_date_received_by_ro' => 'nullable|date',
                ]);

                $project->update([
                    'rssa_date_received_by_ro' => $validated['rssa_date_received_by_ro'] ?? null,
                    'rssa_date_received_by_ro_updated_at' => now(),
                    'rssa_date_received_by_ro_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RSSA Date Received by RO', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RSSA date received by RO updated successfully!');
            }

            if ($request->has('rssa_date_submitted_to_co')) {
                $validated = $request->validate([
                    'rssa_date_submitted_to_co' => 'nullable|date',
                ]);

                $project->update([
                    'rssa_date_submitted_to_co' => $validated['rssa_date_submitted_to_co'] ?? null,
                    'rssa_date_submitted_to_co_updated_at' => now(),
                    'rssa_date_submitted_to_co_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RSSA Date Submitted to CO', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RSSA date submitted to CO updated successfully!');
            }

            if ($request->has('rssa_remarks')) {
                $validated = $request->validate([
                    'rssa_remarks' => 'nullable|string|max:1000',
                ]);

                $project->update([
                    'rssa_remarks' => $this->sanitizeLocallyFundedRemark($validated['rssa_remarks'] ?? null),
                    'rssa_remarks_updated_at' => now(),
                    'rssa_remarks_updated_by' => Auth::id(),
                ]);
                $this->notifyLocallyFundedUpdateRecipients($project, 'updated RSSA Remarks', false);

                return redirect()->route('locally-funded-project.show', $project)
                    ->with('success', 'RSSA remarks updated successfully!');
            }

            return redirect()->route('locally-funded-project.show', $project)
                ->with('success', 'Monitoring information updated successfully!');
        }

        if ($section === 'profile') {
            $validated = $request->validate([
                // Project Profile
                'province' => 'required|string',
                'city_municipality' => 'required|string',
                'barangay_json' => 'required|string',
                'project_name' => 'required|string',
                'funding_year' => 'required|integer|min:2020|max:2099',
                'fund_source' => 'required|string',
                'subaybayan_project_code' => 'required|string|unique:locally_funded_projects,subaybayan_project_code,' . $project->id,
                'project_description' => 'required|string',
                'project_type' => 'required|string',
                'date_nadai' => 'required|date',
                'lgsf_allocation' => 'required|numeric|min:0',
                'lgu_counterpart' => 'required|numeric|min:0',
                'no_of_beneficiaries' => 'required|integer|min:0',
                'rainwater_collection_system' => 'nullable|string',
                'date_confirmation_fund_receipt' => 'required|date',
            ]);
        } elseif ($section === 'contract') {
            $validated = $request->validate([
                // Contract Information
                'mode_of_procurement' => 'required|string',
                'implementing_unit' => 'required|string',
                'date_posting_itb' => 'required|date',
                'date_bid_opening' => 'required|date',
                'date_noa' => 'required|date',
                'date_ntp' => 'required|date',
                'contractor' => 'required|string',
                'contract_amount' => 'required|numeric|min:0',
                'project_duration' => 'required|string',
                'actual_start_date' => 'required|date',
                'target_date_completion' => 'required|date',
                'revised_target_date_completion' => 'nullable|date',
                'actual_date_completion' => 'nullable|date',
            ]);
        } else {
            $validated = $request->validate([
                // Project Profile
                'province' => 'required|string',
                'city_municipality' => 'required|string',
                'barangay_json' => 'required|string',
                'project_name' => 'required|string',
                'funding_year' => 'required|integer|min:2020|max:2099',
                'fund_source' => 'required|string',
                'subaybayan_project_code' => 'required|string|unique:locally_funded_projects,subaybayan_project_code,' . $project->id,
                'project_description' => 'required|string',
                'project_type' => 'required|string',
                'date_nadai' => 'required|date',
                'lgsf_allocation' => 'required|numeric|min:0',
                'lgu_counterpart' => 'required|numeric|min:0',
                'no_of_beneficiaries' => 'required|integer|min:0',
                'rainwater_collection_system' => 'nullable|string',
                'date_confirmation_fund_receipt' => 'required|date',

                // Contract Information
                'mode_of_procurement' => 'required|string',
                'implementing_unit' => 'required|string',
                'date_posting_itb' => 'required|date',
                'date_bid_opening' => 'required|date',
                'date_noa' => 'required|date',
                'date_ntp' => 'required|date',
                'contractor' => 'required|string',
                'contract_amount' => 'required|numeric|min:0',
                'project_duration' => 'required|string',
                'actual_start_date' => 'required|date',
                'target_date_completion' => 'required|date',
                'revised_target_date_completion' => 'nullable|date',
                'actual_date_completion' => 'nullable|date',

                // Financial Accomplishment
                'disbursed_amount' => 'nullable|numeric|min:0',
                'obligation' => 'nullable|numeric|min:0',
                'reverted_amount' => 'nullable|numeric|min:0',
                'balance' => 'nullable|numeric|min:0',
                'utilization_rate' => 'nullable|numeric|min:0|max:100',
                'financial_remarks' => 'nullable|string|max:1000',
            ]);
        }

        $validated = $this->sanitizeLocallyFundedPayload($validated);

        if (array_key_exists('barangay_json', $validated)) {
            $barangayList = $this->parseBarangaySelection($validated['barangay_json'] ?? null);
            if (count($barangayList) > 0) {
                $validated['barangay'] = implode(',', $barangayList);
            } else {
                return redirect()->back()->withInput()->withErrors(['barangay' => 'Please select at least one barangay']);
            }

            unset($validated['barangay_json']);
        }

        $project->update($validated);
        $this->ensureFundUtilizationReport($project);
        $this->notifyLocallyFundedUpdateRecipients($project, 'updated Locally Funded Project details', false);

        return redirect()->route('locally-funded-project.show', $project)
            ->with('success', 'Locally funded project updated successfully!');
    }

    /**
     * Delete the specified locally funded project
     */
    public function destroy(LocallyFundedProject $project)
    {
        $this->authorizeLocallyFundedProjectAccess($project);

        $project->delete();
        return redirect()->route('projects.locally-funded')
            ->with('success', 'Locally funded project deleted successfully!');
    }

    private function deadlineConfigurationForProject(LocallyFundedProject $project): ?DeadlineConfiguration
    {
        $fundingYear = (int) ($project->funding_year ?? 0);
        if ($fundingYear < 2020 || $fundingYear > 2099) {
            return null;
        }

        return DeadlineConfiguration::query()
            ->where('funding_year', $fundingYear)
            ->first();
    }

    private function resolveEffectiveProjectDeadline(
        LocallyFundedProject $project,
        ?DeadlineConfiguration $deadlineConfiguration,
        string $field
    ): ?Carbon {
        $projectDeadline = $project->{$field};
        if ($projectDeadline instanceof Carbon) {
            return $projectDeadline->copy();
        }

        $configuredDeadline = $deadlineConfiguration?->{$field};
        if ($configuredDeadline instanceof Carbon) {
            return $configuredDeadline->copy();
        }

        if ($field === 'pcr_submission_deadline' && $project->target_date_completion instanceof Carbon) {
            return $project->target_date_completion->copy()->addDays(30);
        }

        if ($field === 'rssa_report_deadline' && $project->target_date_completion instanceof Carbon) {
            return $project->target_date_completion->copy()->addDays(395);
        }

        return null;
    }
}
