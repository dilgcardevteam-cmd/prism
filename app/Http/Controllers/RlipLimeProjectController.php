<?php

namespace App\Http\Controllers;

use App\Services\RlipLimeDataService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RlipLimeProjectController extends Controller
{
    public function __construct(
        private readonly RlipLimeDataService $rlipLimeDataService
    ) {
        $this->middleware('auth');
        $this->middleware('crud_permission:rlip_lime_projects,view')->only(['index', 'show']);
    }

    public function index(Request $request)
    {
        $dataset = $this->rlipLimeDataService->getDataset();
        $allRows = collect($dataset['rows'] ?? []);
        $scopedRows = $this->applyRoleScope($allRows);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'project_code' => trim((string) $request->query('project_code', '')),
            'funding_year' => trim((string) $request->query('funding_year', '')),
            'fund_source' => trim((string) $request->query('fund_source', '')),
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $filterOptionsRows = $scopedRows;
        $rows = $this->applyFilters($scopedRows, $filters);

        $sortBy = trim((string) $request->query('sort_by', 'project_code'));
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $rows = $this->sortRows($rows, $sortBy, $sortDir);

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $rows->count();
        $paginatedItems = $rows
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $projects = new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $fundingYears = $this->extractSortedValues($filterOptionsRows, 'funding_year', true);
        $fundSources = $this->extractSortedValues($filterOptionsRows, 'fund_source');
        $provinces = $this->extractSortedValues($filterOptionsRows, 'province');
        $statusOptions = $this->extractSortedValues($filterOptionsRows, 'project_status');
        $provinceMunicipalities = $this->buildProvinceMunicipalityMap($filterOptionsRows);

        return view('projects.rlip-lime', [
            'projects' => $projects,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'perPage' => $perPage,
            'fundingYears' => $fundingYears,
            'fundSources' => $fundSources,
            'provinces' => $provinces,
            'statusOptions' => $statusOptions,
            'provinceMunicipalities' => $provinceMunicipalities,
            'categories' => $dataset['categories'] ?? [],
            'sourceMeta' => $dataset['meta'] ?? [],
        ]);
    }

    public function dashboard(Request $request)
    {
        $dataset = $this->rlipLimeDataService->getDataset();
        $allRows = collect($dataset['rows'] ?? []);
        $scopedRows = $this->applyRoleScope($allRows);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'funding_year' => trim((string) $request->query('funding_year', '')),
            'fund_source' => trim((string) $request->query('fund_source', '')),
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $rows = $this->applyFilters($scopedRows, [
            'search' => $filters['search'],
            'project_code' => '',
            'funding_year' => $filters['funding_year'],
            'fund_source' => $filters['fund_source'],
            'province' => $filters['province'],
            'city' => $filters['city'],
            'status' => $filters['status'],
        ]);

        $totalProjects = $rows->count();
        $totalProgrammedAmount = (float) $rows
            ->pluck('total_amount_programmed_value')
            ->filter(fn ($value) => is_numeric($value))
            ->sum();
        $averageCompletion = round((float) $rows
            ->pluck('overall_completion_value')
            ->filter(fn ($value) => is_numeric($value))
            ->avg(), 2);
        $totalEmployment = (int) round((float) $rows
            ->pluck('employment_generated_value')
            ->filter(fn ($value) => is_numeric($value))
            ->sum(), 0);
        $totalContractAmount = (float) $rows
            ->map(fn (array $row) => $this->extractNumeric($row['contract_amount'] ?? null))
            ->filter(fn ($value) => is_numeric($value))
            ->sum();
        $averageProgrammedAmount = $totalProjects > 0
            ? round($totalProgrammedAmount / $totalProjects, 2)
            : 0;

        $statusBreakdown = $this->buildCountBreakdown($rows, 'project_status');
        $fundSourceBreakdown = $this->buildCountBreakdown($rows, 'fund_source');
        $provinceBreakdown = $this->buildCountBreakdown($rows, 'province', 8);
        $cityBreakdown = $this->buildCountBreakdown($rows, 'city_municipality', 8);
        $projectTypeBreakdown = $this->buildCountBreakdown($rows, 'project_type', 8);
        $modeBreakdown = $this->buildCountBreakdown($rows, 'mode_of_implementation');
        $profileApprovalBreakdown = $this->buildCountBreakdown($rows, 'profile_approval_status');
        $completionApprovalBreakdown = $this->buildCountBreakdown($rows, 'completion_approval_status');

        $fundingYearBreakdown = $rows
            ->groupBy(fn (array $row) => trim((string) ($row['funding_year'] ?? '')) ?: 'UNSPECIFIED')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->sortByDesc(function (array $item) {
                $label = trim((string) ($item['label'] ?? ''));
                if (is_numeric($label)) {
                    return (int) $label;
                }

                return -1;
            })
            ->values();

        $completionBuckets = [
            ['label' => '0-24%', 'count' => 0],
            ['label' => '25-49%', 'count' => 0],
            ['label' => '50-74%', 'count' => 0],
            ['label' => '75-99%', 'count' => 0],
            ['label' => '100%', 'count' => 0],
            ['label' => 'No Data', 'count' => 0],
        ];

        $completedProjects = 0;
        $ongoingProjects = 0;
        $notStartedProjects = 0;
        $withAipCount = 0;
        $withBriefAttachmentCount = 0;
        $withCompletionAttachmentCount = 0;
        $withScheduleCount = 0;
        $withoutScheduleCount = 0;
        $overdueCount = 0;
        $dueSoonCount = 0;
        $completedWithoutDateCount = 0;

        $todayTs = now()->startOfDay()->timestamp;
        $dueSoonTs = now()->copy()->addDays(30)->endOfDay()->timestamp;

        foreach ($rows as $row) {
            $statusText = mb_strtolower(trim((string) ($row['project_status'] ?? '')));
            $completion = is_numeric($row['overall_completion_value'] ?? null)
                ? (float) $row['overall_completion_value']
                : null;

            if ($completion === null) {
                $completionBuckets[5]['count']++;
            } elseif ($completion >= 100) {
                $completionBuckets[4]['count']++;
            } elseif ($completion >= 75) {
                $completionBuckets[3]['count']++;
            } elseif ($completion >= 50) {
                $completionBuckets[2]['count']++;
            } elseif ($completion >= 25) {
                $completionBuckets[1]['count']++;
            } else {
                $completionBuckets[0]['count']++;
            }

            if (
                ($completion !== null && $completion >= 100)
                || str_contains($statusText, 'completed')
                || str_contains($statusText, 'complete')
            ) {
                $completedProjects++;
            } elseif (
                str_contains($statusText, 'not yet')
                || str_contains($statusText, 'not started')
                || ($completion !== null && $completion <= 0)
            ) {
                $notStartedProjects++;
            } else {
                $ongoingProjects++;
            }

            if ($this->isAffirmativeValue((string) ($row['has_aip'] ?? ''))) {
                $withAipCount++;
            }
            if ($this->hasDocumentValue((string) ($row['project_brief_attachment'] ?? ''))) {
                $withBriefAttachmentCount++;
            }
            if ($this->hasDocumentValue((string) ($row['completion_has_attachment'] ?? ''))) {
                $withCompletionAttachmentCount++;
            }

            $startTs = $this->parseDateToTimestamp((string) ($row['project_schedule_start_date'] ?? ''));
            $endTs = $this->parseDateToTimestamp((string) ($row['project_schedule_end_date'] ?? ''));
            $completionDateTs = $this->parseDateToTimestamp((string) ($row['date_of_completion'] ?? ''));
            $isComplete = $completion !== null && $completion >= 100;

            if ($startTs !== null && $endTs !== null) {
                $withScheduleCount++;
            } else {
                $withoutScheduleCount++;
            }

            if ($endTs !== null && !$isComplete) {
                if ($endTs < $todayTs) {
                    $overdueCount++;
                } elseif ($endTs <= $dueSoonTs) {
                    $dueSoonCount++;
                }
            }

            if ($isComplete && $completionDateTs === null) {
                $completedWithoutDateCount++;
            }
        }

        $completionBucketBreakdown = collect($completionBuckets)
            ->filter(fn (array $bucket) => (int) $bucket['count'] > 0)
            ->values();

        $programmedValues = $rows
            ->pluck('total_amount_programmed_value')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->sort()
            ->values();
        $thresholdIndex = max(0, (int) floor(($programmedValues->count() - 1) * 0.75));
        $highBudgetThreshold = (float) ($programmedValues[$thresholdIndex] ?? 0);
        if ($highBudgetThreshold <= 0) {
            $highBudgetThreshold = 5000000;
        }

        $highBudgetLowProgressCount = $rows
            ->filter(function (array $row) use ($highBudgetThreshold) {
                $programmed = (float) ($row['total_amount_programmed_value'] ?? 0);
                $completion = (float) ($row['overall_completion_value'] ?? 0);

                return $programmed >= $highBudgetThreshold && $completion < 50;
            })
            ->count();

        $completedRatePercent = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 2) : 0;
        $aipCoveragePercent = $totalProjects > 0 ? round(($withAipCount / $totalProjects) * 100, 2) : 0;
        $briefCoveragePercent = $totalProjects > 0 ? round(($withBriefAttachmentCount / $totalProjects) * 100, 2) : 0;
        $completionDocCoveragePercent = $totalProjects > 0 ? round(($withCompletionAttachmentCount / $totalProjects) * 100, 2) : 0;
        $documentationCoveragePercent = round(($aipCoveragePercent + $briefCoveragePercent + $completionDocCoveragePercent) / 3, 2);
        $scheduleRiskPercent = $totalProjects > 0 ? round((($overdueCount + $dueSoonCount) / $totalProjects) * 100, 2) : 0;

        $fundingYears = $this->extractSortedValues($scopedRows, 'funding_year', true);
        $fundSources = $this->extractSortedValues($scopedRows, 'fund_source');
        $provinces = $this->extractSortedValues($scopedRows, 'province');
        $statusOptions = $this->extractSortedValues($scopedRows, 'project_status');
        $provinceMunicipalities = $this->buildProvinceMunicipalityMap($scopedRows);
        $selectedProvinceFilter = $filters['province'];
        if ($selectedProvinceFilter !== '' && array_key_exists($selectedProvinceFilter, $provinceMunicipalities)) {
            $cityOptions = collect($provinceMunicipalities[$selectedProvinceFilter] ?? []);
        } else {
            $cityOptions = collect($provinceMunicipalities)->flatten(1);
        }

        $cityOptions = $cityOptions
            ->map(fn ($city) => trim((string) $city))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $topStatusCount = (int) ($statusBreakdown->first()['count'] ?? 0);
        $topFundSourceCount = (int) ($fundSourceBreakdown->first()['count'] ?? 0);
        $topProvinceCount = (int) ($provinceBreakdown->first()['count'] ?? 0);

        return view('projects.rlip-lime-dashboard', [
            'activeTab' => 'rlip-lime',
            'filters' => $filters,
            'totalProjects' => $totalProjects,
            'totalProgrammedAmount' => $totalProgrammedAmount,
            'averageCompletion' => $averageCompletion,
            'totalEmployment' => $totalEmployment,
            'totalContractAmount' => $totalContractAmount,
            'averageProgrammedAmount' => $averageProgrammedAmount,
            'completedProjects' => $completedProjects,
            'ongoingProjects' => $ongoingProjects,
            'notStartedProjects' => $notStartedProjects,
            'highBudgetLowProgressCount' => $highBudgetLowProgressCount,
            'highBudgetThreshold' => $highBudgetThreshold,
            'completedRatePercent' => $completedRatePercent,
            'documentationCoveragePercent' => $documentationCoveragePercent,
            'scheduleRiskPercent' => $scheduleRiskPercent,
            'aipCoveragePercent' => $aipCoveragePercent,
            'briefCoveragePercent' => $briefCoveragePercent,
            'completionDocCoveragePercent' => $completionDocCoveragePercent,
            'withAipCount' => $withAipCount,
            'withBriefAttachmentCount' => $withBriefAttachmentCount,
            'withCompletionAttachmentCount' => $withCompletionAttachmentCount,
            'withScheduleCount' => $withScheduleCount,
            'withoutScheduleCount' => $withoutScheduleCount,
            'overdueCount' => $overdueCount,
            'dueSoonCount' => $dueSoonCount,
            'completedWithoutDateCount' => $completedWithoutDateCount,
            'statusBreakdown' => $statusBreakdown,
            'fundSourceBreakdown' => $fundSourceBreakdown,
            'provinceBreakdown' => $provinceBreakdown,
            'cityBreakdown' => $cityBreakdown,
            'projectTypeBreakdown' => $projectTypeBreakdown,
            'modeBreakdown' => $modeBreakdown,
            'profileApprovalBreakdown' => $profileApprovalBreakdown,
            'completionApprovalBreakdown' => $completionApprovalBreakdown,
            'completionBucketBreakdown' => $completionBucketBreakdown,
            'fundingYearBreakdown' => $fundingYearBreakdown,
            'fundingYears' => $fundingYears,
            'fundSources' => $fundSources,
            'provinces' => $provinces,
            'statusOptions' => $statusOptions,
            'provinceMunicipalities' => $provinceMunicipalities,
            'cityOptions' => $cityOptions,
            'sourceMeta' => $dataset['meta'] ?? [],
            'topStatusCount' => $topStatusCount,
            'topFundSourceCount' => $topFundSourceCount,
            'topProvinceCount' => $topProvinceCount,
        ]);
    }

    public function show(Request $request, int $rowNumber)
    {
        $dataset = $this->rlipLimeDataService->getDataset();
        $allRows = collect($dataset['rows'] ?? []);
        $scopedRows = $this->applyRoleScope($allRows);
        $project = $scopedRows->firstWhere('row_number', $rowNumber);

        if (!is_array($project)) {
            abort(404);
        }

        $categories = $dataset['categories'] ?? [];
        $categoryValues = [];
        foreach ($categories as $section => $fields) {
            $categoryValues[$section] = collect($fields)
                ->map(function (array $fieldMeta) use ($project) {
                    $index = (int) ($fieldMeta['index'] ?? -1);
                    $value = $this->resolveCellValue($project, $index);

                    return [
                        'index' => $index,
                        'label' => (string) ($fieldMeta['label'] ?? ('Column ' . $index)),
                        'value' => $value,
                        'is_empty' => trim($value) === '',
                    ];
                })
                ->values()
                ->all();
        }

        $backUrl = route('projects.rlip-lime', $request->query());

        return view('projects.rlip-lime-show', [
            'project' => $project,
            'categoryValues' => $categoryValues,
            'sourceMeta' => $dataset['meta'] ?? [],
            'backUrl' => $backUrl,
        ]);
    }

    private function applyRoleScope(Collection $rows): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return $rows;
        }

        $province = trim((string) $user->province);
        $office = trim((string) $user->office);
        $region = trim((string) $user->region);

        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $regionLower = $user->normalizedRegion();
        $officeComparable = $user->normalizedOfficeComparable();
        $isRegionalOffice = $user->isRegionalOfficeAssignment();

        if ($user->isLguScopedUser()) {
            $rows = $rows->filter(function (array $row) use ($provinceLower) {
                if ($provinceLower === '') {
                    return true;
                }

                return (string) ($row['province_lc'] ?? mb_strtolower(trim((string) ($row['province'] ?? '')))) === $provinceLower;
            });

            if ($officeLower !== '') {
                $rows = $rows->filter(function (array $row) use ($officeLower, $officeComparable) {
                    $cityRaw = (string) ($row['city_municipality_lc'] ?? mb_strtolower(trim((string) ($row['city_municipality'] ?? ''))));
                    $cityComparable = $this->normalizeOfficeComparable($cityRaw);

                    return $cityRaw === $officeLower
                        || ($officeComparable !== '' && $cityComparable === $officeComparable);
                });
            }

            return $rows->values();
        }

        if (!$user->isDilgUser()) {
            return $rows->values();
        }

        if ($isRegionalOffice) {
            return $rows->values();
        }

        if ($provinceLower !== '') {
            return $rows
                ->filter(fn (array $row) => (string) ($row['province_lc'] ?? mb_strtolower(trim((string) ($row['province'] ?? '')))) === $provinceLower)
                ->values();
        }

        if ($regionLower !== '') {
            return $rows
                ->filter(fn (array $row) => (string) ($row['region_lc'] ?? mb_strtolower(trim((string) ($row['region'] ?? '')))) === $regionLower)
                ->values();
        }

        return $rows->values();
    }

    private function applyFilters(Collection $rows, array $filters): Collection
    {
        $result = $rows;

        if ($filters['search'] !== '') {
            $search = mb_strtolower($filters['search']);
            $result = $result->filter(function (array $row) use ($search) {
                $haystack = (string) ($row['search_index'] ?? '');
                if ($haystack === '') {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($row['project_code'] ?? ''),
                        (string) ($row['project_title'] ?? ''),
                        (string) ($row['province'] ?? ''),
                        (string) ($row['city_municipality'] ?? ''),
                        (string) ($row['barangay'] ?? ''),
                        (string) ($row['fund_source'] ?? ''),
                        (string) ($row['project_type'] ?? ''),
                        (string) ($row['project_status'] ?? ''),
                    ]));
                }

                return str_contains($haystack, $search);
            });
        }

        if ($filters['project_code'] !== '') {
            $needle = mb_strtolower($filters['project_code']);
            $result = $result->filter(
                fn (array $row) => str_contains((string) ($row['project_code_lc'] ?? mb_strtolower((string) ($row['project_code'] ?? ''))), $needle)
            );
        }

        if ($filters['funding_year'] !== '') {
            $result = $result->filter(
                fn (array $row) => trim((string) ($row['funding_year'] ?? '')) === $filters['funding_year']
            );
        }

        if ($filters['fund_source'] !== '') {
            $needle = mb_strtolower($filters['fund_source']);
            $result = $result->filter(
                fn (array $row) => (string) ($row['fund_source_lc'] ?? mb_strtolower(trim((string) ($row['fund_source'] ?? '')))) === $needle
            );
        }

        if ($filters['province'] !== '') {
            $needle = mb_strtolower($filters['province']);
            $result = $result->filter(
                fn (array $row) => (string) ($row['province_lc'] ?? mb_strtolower(trim((string) ($row['province'] ?? '')))) === $needle
            );
        }

        if ($filters['city'] !== '') {
            $needle = mb_strtolower($filters['city']);
            $result = $result->filter(
                fn (array $row) => (string) ($row['city_municipality_lc'] ?? mb_strtolower(trim((string) ($row['city_municipality'] ?? '')))) === $needle
            );
        }

        if ($filters['status'] !== '') {
            $needle = mb_strtolower($filters['status']);
            $result = $result->filter(
                fn (array $row) => (string) ($row['project_status_lc'] ?? mb_strtolower(trim((string) ($row['project_status'] ?? '')))) === $needle
            );
        }

        return $result->values();
    }

    private function sortRows(Collection $rows, string $sortBy, string $sortDir): Collection
    {
        $sortable = [
            'project_code',
            'project_title',
            'location',
            'funding_year',
            'fund_source',
            'project_type',
            'project_status',
            'total_amount_programmed',
            'overall_completion',
            'employment_generated',
            'profile_approval_status',
        ];

        if (!in_array($sortBy, $sortable, true)) {
            $sortBy = 'project_code';
        }

        $sorted = $rows->values()->all();
        usort($sorted, function (array $left, array $right) use ($sortBy, $sortDir) {
            $leftValue = $this->resolveSortableValue($left, $sortBy);
            $rightValue = $this->resolveSortableValue($right, $sortBy);

            if ($leftValue == $rightValue) {
                $tieLeft = mb_strtolower((string) ($left['project_code'] ?? ''));
                $tieRight = mb_strtolower((string) ($right['project_code'] ?? ''));
                $comparison = $tieLeft <=> $tieRight;
            } else {
                $comparison = $leftValue <=> $rightValue;
            }

            return $sortDir === 'desc' ? -$comparison : $comparison;
        });

        return collect($sorted);
    }

    private function resolveSortableValue(array $row, string $sortBy): float|int|string
    {
        return match ($sortBy) {
            'funding_year' => (int) ($row['funding_year_value'] ?? 0),
            'total_amount_programmed' => (float) ($row['total_amount_programmed_value'] ?? 0),
            'overall_completion' => (float) ($row['overall_completion_value'] ?? 0),
            'employment_generated' => (float) ($row['employment_generated_value'] ?? 0),
            default => mb_strtolower(trim((string) ($row[$sortBy] ?? ''))),
        };
    }

    private function extractSortedValues(Collection $rows, string $key, bool $desc = false): Collection
    {
        $values = $rows
            ->pluck($key)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique();

        return $desc ? $values->sortDesc()->values() : $values->sort()->values();
    }

    private function buildProvinceMunicipalityMap(Collection $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $province = trim((string) ($row['province'] ?? ''));
            $city = trim((string) ($row['city_municipality'] ?? ''));
            if ($province === '' || $city === '') {
                continue;
            }

            if (!array_key_exists($province, $map)) {
                $map[$province] = [];
            }

            if (!in_array($city, $map[$province], true)) {
                $map[$province][] = $city;
            }
        }

        foreach ($map as $province => $cities) {
            sort($cities);
            $map[$province] = $cities;
        }
        ksort($map);

        return $map;
    }

    private function normalizeOfficeComparable(string $value): string
    {
        $base = trim((string) preg_replace('/,.*$/', '', $value));
        $base = preg_replace('/\([^)]*\)/', ' ', $base) ?? $base;
        $base = preg_replace('/^(municipality|city)\s+of\s+/i', '', $base) ?? $base;
        $base = preg_replace('/\s+(municipality|city)$/i', '', $base) ?? $base;
        $base = preg_replace('/[^a-z0-9\s-]/i', ' ', $base) ?? $base;
        $base = preg_replace('/\s+/', ' ', $base) ?? $base;

        return trim($base);
    }

    private function buildCountBreakdown(Collection $rows, string $key, ?int $limit = null): Collection
    {
        $breakdown = $rows
            ->groupBy(fn (array $row) => trim((string) ($row[$key] ?? '')) ?: 'UNSPECIFIED')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        if ($limit !== null && $limit > 0) {
            return $breakdown->take($limit)->values();
        }

        return $breakdown;
    }

    private function extractNumeric(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $normalized));
        if ($clean === null || $clean === '' || !is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    private function parseDateToTimestamp(string $value): ?int
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        if (is_numeric($normalized)) {
            $numeric = (float) $normalized;
            if ($numeric >= 30000 && $numeric <= 80000) {
                $excelBase = new \DateTimeImmutable('1899-12-30 00:00:00');
                return $excelBase->modify('+' . (int) floor($numeric) . ' days')->getTimestamp();
            }
            if ($numeric >= 1900 && $numeric <= 2100) {
                return strtotime((int) $numeric . '-01-01') ?: null;
            }
        }

        $parsed = strtotime($normalized);
        return $parsed !== false ? $parsed : null;
    }

    private function isAffirmativeValue(string $value): bool
    {
        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['yes', 'y', 'true', '1'], true)
            || str_contains($normalized, 'yes')
            || str_contains($normalized, 'true');
    }

    private function hasDocumentValue(string $value): bool
    {
        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['no', 'none', 'n/a', 'na', '-', '0'], true)) {
            return false;
        }

        return !str_contains($normalized, 'not available');
    }

    private function resolveCellValue(array $project, int $index): string
    {
        if (!isset($project['cells'])) {
            return '';
        }

        $cells = $project['cells'];
        if (!is_array($cells)) {
            return '';
        }

        if (array_key_exists($index, $cells)) {
            return trim((string) $cells[$index]);
        }

        $stringIndex = (string) $index;
        if (array_key_exists($stringIndex, $cells)) {
            return trim((string) $cells[$stringIndex]);
        }

        return '';
    }
}
