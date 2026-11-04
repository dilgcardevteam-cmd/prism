<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardFilterService
{
    public function normalizeFilterValue($value): string
    {
        $normalizedValue = trim((string) $value);
        $normalizedValue = preg_replace('/\s+/u', ' ', $normalizedValue) ?? $normalizedValue;

        return trim($normalizedValue);
    }

    public function parseFilterArray($values): array
    {
        if (!is_array($values)) {
            if ($values === null || trim((string) $values) === '') {
                return [];
            }
            if (is_string($values) && str_contains($values, ',')) {
                $values = explode(',', $values);
            } else {
                $values = [$values];
            }
        }

        return collect($values)
            ->map(fn($v) => $this->normalizeFilterValue($v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function normalizeFilters(array $rawFilters): array
    {
        $province = $this->parseFilterArray($rawFilters['province'] ?? []);
        $city = $this->parseFilterArray($rawFilters['city_municipality'] ?? ($rawFilters['city'] ?? ($rawFilters['cities'] ?? [])));
        $barangay = $this->parseFilterArray($rawFilters['barangay'] ?? ($rawFilters['barangays'] ?? []));
        $programs = $this->parseFilterArray($rawFilters['program'] ?? ($rawFilters['programs'] ?? []));
        $fundSource = $this->parseFilterArray($rawFilters['fund_source'] ?? []);
        $fundingYear = $this->parseFilterArray($rawFilters['funding_year'] ?? ($rawFilters['funding_years'] ?? []));
        $projectType = $this->parseFilterArray($rawFilters['project_type'] ?? ($rawFilters['project_types'] ?? []));
        $projectStatus = $this->parseFilterArray($rawFilters['project_status'] ?? ($rawFilters['project_statuses'] ?? []));
        $search = $this->normalizeFilterValue($rawFilters['search'] ?? '');

        return [
            'search' => $search,
            'province' => $province,
            'city_municipality' => $city,
            'barangay' => $barangay,
            'programs' => $programs,
            'fund_source' => $fundSource,
            'funding_year' => $fundingYear,
            'funding_year_mode' => $rawFilters['funding_year_mode'] ?? 'years',
            'funding_year_from' => !empty($rawFilters['funding_year_from']) ? (int) $rawFilters['funding_year_from'] : null,
            'funding_year_to' => !empty($rawFilters['funding_year_to']) ? (int) $rawFilters['funding_year_to'] : null,
            'project_type' => $projectType,
            'project_status' => $projectStatus,
        ];
    }

    public function getFilterExpressions(): array
    {
        $hasSubayProjectCodeKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'project_code_key');
        $hasSubayProvinceKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'province_key');
        $hasSubayStatusKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'status_key');
        $hasSubayFundingYearKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'funding_year_key');
        $hasSubayFundingYearNumColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'funding_year_num');
        $hasSubayFundSourceKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'fund_source_key');

        $subayProjectCodeFilterExpr = $hasSubayProjectCodeKeyColumn
            ? 'COALESCE(spp.project_code_key, spp.project_code)'
            : 'spp.project_code';
        $subayProvinceFilterExpr = $hasSubayProvinceKeyColumn
            ? 'COALESCE(spp.province_key, spp.province)'
            : 'spp.province';
        $subayStatusFilterExpr = $hasSubayStatusKeyColumn
            ? 'COALESCE(spp.status_key, spp.status)'
            : 'spp.status';
        $subayFundingYearFilterExpr = $hasSubayFundingYearKeyColumn
            ? 'COALESCE(spp.funding_year_key, spp.funding_year)'
            : 'spp.funding_year';

        $computedFundSourceExpr = "
            CASE
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'SBDP%' THEN 'SBDP'
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'FA-%' THEN 'FALGU'
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'FALGU%' THEN 'FALGU'
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'CMGP%' THEN 'CMGP'
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'GEF%' THEN 'GEF'
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'SAFPB%' THEN 'SAFPB'
                WHEN UPPER(TRIM(spp.project_code)) LIKE 'SGLGIF%' THEN 'SGLGIF'
                WHEN TRIM(COALESCE(spp.program, '')) <> '' THEN UPPER(TRIM(COALESCE(spp.program, '')))
                ELSE 'UNSPECIFIED'
            END
        ";
        $fundSourceFromProjectCodeExpr = $hasSubayFundSourceKeyColumn
            ? "COALESCE(NULLIF(spp.fund_source_key, ''), {$computedFundSourceExpr})"
            : $computedFundSourceExpr;

        $subayFundingYearRangeExpr = $hasSubayFundingYearNumColumn
            ? 'spp.funding_year_num'
            : 'CASE WHEN TRIM(COALESCE(spp.funding_year, "")) REGEXP "^[0-9]+$" THEN CAST(TRIM(spp.funding_year) AS UNSIGNED) ELSE NULL END';

        return [
            'project_code' => $subayProjectCodeFilterExpr,
            'province' => $subayProvinceFilterExpr,
            'status' => $subayStatusFilterExpr,
            'funding_year' => $subayFundingYearFilterExpr,
            'fund_source' => $fundSourceFromProjectCodeExpr,
            'funding_year_range' => $subayFundingYearRangeExpr,
        ];
    }

    public function applyFiltersToQuery($query, array $filters, ?string $excludeFilter = null, ?array $expressions = null): void
    {
        $expr = $expressions ?? $this->getFilterExpressions();

        $normalizeSqlComparable = function (string $column): string {
            return "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), CHAR(13), ' '), CHAR(10), ' '), CHAR(9), ' '), '  ', ' '), '  ', ' '), '  ', ' ')))";
        };

        $normalizeDelimitedSqlComparable = function (string $column): string {
            return "LOWER(CONCAT('|', TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), CHAR(13), '|'), CHAR(10), '|'), CHAR(9), ' '), ' |', '|'), '| ', '|'), '||', '|'), '||', '|')), '|'))";
        };

        $normalizeComparableLocationSql = function (string $column): string {
            return "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), CHAR(13), ' '), CHAR(10), ' '), CHAR(9), ' ')))";
        };

        $applyExact = function ($q, string $column, array $values) use ($normalizeSqlComparable) {
            $normalizedValues = collect($values)
                ->map(fn($v) => mb_strtolower($this->normalizeFilterValue($v)))
                ->filter(fn($v) => $v !== '')
                ->unique()
                ->values()
                ->all();

            if (empty($normalizedValues)) {
                return;
            }

            $placeholders = implode(', ', array_fill(0, count($normalizedValues), '?'));
            $q->whereRaw($normalizeSqlComparable($column) . " IN ({$placeholders})", $normalizedValues);
        };

        $applyComparableLocation = function ($q, string $column, array $values) use ($normalizeComparableLocationSql) {
            $normalizedValues = collect($values)
                ->map(fn($v) => ProjectLocationFilterHelper::normalizeComparableLocationLabel($v))
                ->filter(fn($v) => $v !== '')
                ->unique()
                ->values()
                ->all();

            if (empty($normalizedValues)) {
                return;
            }

            $placeholders = implode(', ', array_fill(0, count($normalizedValues), '?'));
            $q->whereRaw($normalizeComparableLocationSql($column) . " IN ({$placeholders})", $normalizedValues);
        };

        $applyDelimited = function ($q, string $column, array $values) use ($normalizeDelimitedSqlComparable) {
            $normalizedValues = collect($values)
                ->map(fn($v) => mb_strtolower($this->normalizeFilterValue($v)))
                ->filter(fn($v) => $v !== '')
                ->unique()
                ->values()
                ->all();

            if (empty($normalizedValues)) {
                return;
            }

            $exprSql = $normalizeDelimitedSqlComparable($column);
            $q->where(function ($subQuery) use ($normalizedValues, $exprSql) {
                foreach ($normalizedValues as $val) {
                    $subQuery->orWhereRaw($exprSql . ' LIKE ?', ['%|' . $val . '|%']);
                }
            });
        };

        if ($excludeFilter !== 'province' && !empty($filters['province'])) {
            $applyExact($query, $expr['province'], $filters['province']);
        }

        if ($excludeFilter !== 'city_municipality' && !empty($filters['city_municipality'])) {
            $applyComparableLocation($query, 'spp.city_municipality', $filters['city_municipality']);
        }

        if ($excludeFilter !== 'barangay' && !empty($filters['barangay'])) {
            $applyDelimited($query, 'spp.barangay', $filters['barangay']);
        }

        if ($excludeFilter !== 'program' && !empty($filters['programs'])) {
            $applyExact($query, 'spp.program', $filters['programs']);
        }

        if ($excludeFilter !== 'fund_source' && !empty($filters['fund_source'])) {
            $applyExact($query, $expr['fund_source'], $filters['fund_source']);
        }

        if ($excludeFilter !== 'funding_year') {
            if (!empty($filters['funding_year'])) {
                $applyExact($query, $expr['funding_year'], $filters['funding_year']);
            }
            if (!empty($filters['funding_year_from'])) {
                $query->whereRaw("{$expr['funding_year_range']} >= ?", [(int) $filters['funding_year_from']]);
            }
            if (!empty($filters['funding_year_to'])) {
                $query->whereRaw("{$expr['funding_year_range']} <= ?", [(int) $filters['funding_year_to']]);
            }
        }

        if ($excludeFilter !== 'project_type' && !empty($filters['project_type'])) {
            $applyExact($query, 'spp.type_of_project', $filters['project_type']);
        }

        if ($excludeFilter !== 'project_status' && !empty($filters['project_status'])) {
            $applyExact($query, $expr['status'], $filters['project_status']);
        }

        if (!empty($filters['search'])) {
            $needle = '%' . mb_strtolower($filters['search']) . '%';
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(COALESCE(spp.project_code, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(spp.project_title, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(spp.program, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(spp.province, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(spp.city_municipality, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(spp.barangay, "")) LIKE ?', [$needle]);
            });
        }
    }

    public function applyUserScope($query, ?User $user, array $expressions): void
    {
        if (!$user) {
            return;
        }

        $isLguScopedUser = $user->isLguScopedUser();
        $isDilgUser = $user->isDilgUser();
        $isRegionalOfficeUser = $user->isRegionalOfficeAssignment();
        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $officeComparableLower = $user->normalizedOfficeComparable();

        $subayCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";

        if ($isLguScopedUser) {
            if ($officeLower !== '') {
                if ($provinceLower !== '') {
                    $query->whereRaw('LOWER(TRIM(COALESCE(' . $expressions['province'] . ', ""))) = ?', [$provinceLower]);
                }
                $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;
                $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $subayCityComparableExpression) {
                    $subQuery->whereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) = ?', [$officeLower])
                        ->orWhereRaw("{$subayCityComparableExpression} = ?", [$officeNeedle]);
                });
            } elseif ($provinceLower !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(' . $expressions['province'] . ', ""))) = ?', [$provinceLower]);
            }
        } elseif ($isDilgUser) {
            if (!$isRegionalOfficeUser && $provinceLower !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(' . $expressions['province'] . ', ""))) = ?', [$provinceLower]);
            }
        }
    }

    public function getFilterOptions(?User $user, array $rawFilters = []): array
    {
        $filters = $this->normalizeFilters($rawFilters);
        $expressions = $this->getFilterExpressions();

        $configuredHierarchy = ProjectLocationFilterHelper::buildConfiguredLocationHierarchy();
        $configuredProvinces = ProjectLocationFilterHelper::buildConfiguredProvinceLabels();

        // Scope configured provinces if user is provincial/LGU
        if ($user) {
            $isLguScopedUser = $user->isLguScopedUser();
            $isDilgUser = $user->isDilgUser();
            $isRegional = $user->isRegionalOfficeAssignment();
            $userProvNorm = ProjectLocationFilterHelper::normalizeComparableLocationLabel($user->province ?? '');
            if (($isLguScopedUser || ($isDilgUser && !$isRegional)) && $userProvNorm !== '') {
                $configuredProvinces = array_values(array_filter(
                    $configuredProvinces,
                    fn($p) => ProjectLocationFilterHelper::normalizeComparableLocationLabel($p) === $userProvNorm
                ));
            }
        }

        $configuredProvinceCityMap = ProjectLocationFilterHelper::buildConfiguredProvinceCityMapFromHierarchy($configuredProvinces);
        $configuredCityBarangayMap = ProjectLocationFilterHelper::buildConfiguredCityBarangayMapFromHierarchy($configuredProvinces);

        $filterOptions = [
            'provinces' => collect($configuredProvinces),
            'province_city_map' => $configuredProvinceCityMap,
            'cities' => collect(),
            'city_barangay_map' => $configuredCityBarangayMap,
            'barangays' => collect(),
            'programs' => collect(),
            'fund_sources' => collect(),
            'funding_years' => collect(),
            'project_types' => collect(),
            'project_statuses' => collect(),
            'localized_hierarchy' => $configuredHierarchy,
        ];

        if (!Schema::hasTable('subay_project_profiles')) {
            return $filterOptions;
        }

        $baseQuery = DB::table('subay_project_profiles as spp')
            ->whereRaw("{$expressions['project_code']} IS NOT NULL")
            ->whereRaw("{$expressions['project_code']} <> ''")
            ->whereRaw("{$expressions['fund_source']} <> ?", ['SGLGIF']);

        $this->applyUserScope($baseQuery, $user, $expressions);

        // 1. Provinces
        $provinceOptionsQuery = clone $baseQuery;
        $this->applyFiltersToQuery($provinceOptionsQuery, $filters, 'province', $expressions);
        $dbProvinces = $provinceOptionsQuery
            ->selectRaw("{$expressions['province']} as option_value")
            ->whereRaw("{$expressions['province']} IS NOT NULL")
            ->whereRaw("TRIM(COALESCE({$expressions['province']}, '')) <> ''")
            ->distinct()
            ->orderBy('option_value')
            ->pluck('option_value')
            ->map(fn($p) => $this->normalizeFilterValue($p))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // When project filters are active, intersect provinces with dbProvinces; otherwise keep configured
        $hasProjectFilters = !empty($filters['programs']) || !empty($filters['project_type']) || !empty($filters['project_status']);
        if ($hasProjectFilters && !empty($dbProvinces)) {
            $matchedProvinces = collect($configuredProvinces)
                ->filter(function ($p) use ($dbProvinces) {
                    $pNorm = mb_strtolower(trim($p));
                    return collect($dbProvinces)->contains(fn($dbP) => mb_strtolower(trim($dbP)) === $pNorm);
                })
                ->values();
            if ($matchedProvinces->isNotEmpty()) {
                $filterOptions['provinces'] = $matchedProvinces;
            } else {
                $filterOptions['provinces'] = collect($dbProvinces);
            }
        } else {
            $filterOptions['provinces'] = collect($configuredProvinces);
        }

        // 2. Cities & Province-City Map (based on available projects)
        $cityOptionsQuery = clone $baseQuery;
        $this->applyFiltersToQuery($cityOptionsQuery, $filters, 'city_municipality', $expressions);
        $cityRows = $cityOptionsQuery
            ->selectRaw("TRIM(COALESCE({$expressions['province']}, '')) as province, TRIM(COALESCE(spp.city_municipality, '')) as city_municipality")
            ->whereNotNull('spp.city_municipality')
            ->whereRaw('TRIM(spp.city_municipality) <> ""')
            ->distinct()
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->get();

        $projectProvinceCityMap = [];
        $allProjectCities = [];
        foreach ($cityRows as $row) {
            $pLabel = $this->normalizeFilterValue($row->province ?? '');
            $cLabel = $this->normalizeFilterValue($row->city_municipality ?? '');
            if ($pLabel === '' || $cLabel === '') {
                continue;
            }

            // Find matching configured province label casing for display consistency
            $targetKey = $pLabel;
            foreach ($configuredProvinces as $confP) {
                if (mb_strtolower(trim($confP)) === mb_strtolower(trim($pLabel))) {
                    $targetKey = $confP;
                    break;
                }
            }

            $projectProvinceCityMap[$targetKey] ??= [];
            if (!in_array($cLabel, $projectProvinceCityMap[$targetKey], true)) {
                $projectProvinceCityMap[$targetKey][] = $cLabel;
            }

            if (!in_array($cLabel, $allProjectCities, true)) {
                $allProjectCities[] = $cLabel;
            }
        }

        foreach ($projectProvinceCityMap as $k => &$cList) {
            natcasesort($cList);
            $cList = array_values($cList);
        }
        unset($cList);
        natcasesort($allProjectCities);
        $allProjectCities = array_values($allProjectCities);

        $filterOptions['province_city_map'] = $projectProvinceCityMap;

        // Populate cities list for the currently selected province(s), or all available project cities if All
        if (!empty($filters['province'])) {
            $selCities = collect();
            foreach ($filters['province'] as $prov) {
                $provNorm = mb_strtolower(trim($prov));
                foreach ($projectProvinceCityMap as $k => $cList) {
                    if (mb_strtolower(trim($k)) === $provNorm) {
                        $selCities = $selCities->concat($cList);
                    }
                }
            }
            $filterOptions['cities'] = $selCities->unique()->values();
        } else {
            $filterOptions['cities'] = collect($allProjectCities);
        }

        // 3. Barangays & City-Barangay Map (based on available projects)
        $barangayOptionsQuery = clone $baseQuery;
        $this->applyFiltersToQuery($barangayOptionsQuery, $filters, 'barangay', $expressions);
        $barangayRows = $barangayOptionsQuery
            ->selectRaw("TRIM(COALESCE({$expressions['province']}, '')) as province, TRIM(COALESCE(spp.city_municipality, '')) as city_municipality, spp.barangay as barangay")
            ->whereNotNull('spp.city_municipality')
            ->whereRaw('TRIM(spp.city_municipality) <> ""')
            ->whereNotNull('spp.barangay')
            ->whereRaw('TRIM(spp.barangay) <> ""')
            ->distinct()
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->get();

        $projectCityBarangayMap = [];
        $allProjectBarangays = [];

        foreach ($barangayRows as $row) {
            $pNorm = mb_strtolower($this->normalizeFilterValue($row->province ?? ''));
            $cNorm = mb_strtolower($this->normalizeFilterValue($row->city_municipality ?? ''));
            $cLabel = $this->normalizeFilterValue($row->city_municipality ?? '');
            $mapKey = $pNorm . '|' . $cNorm;

            $items = preg_split('/\r\n|\r|\n/u', (string) ($row->barangay ?? '')) ?: [];
            foreach ($items as $bItem) {
                $bLabel = $this->normalizeFilterValue($bItem);
                if ($bLabel === '') {
                    continue;
                }

                $projectCityBarangayMap[$mapKey] ??= [];
                if (!in_array($bLabel, $projectCityBarangayMap[$mapKey], true)) {
                    $projectCityBarangayMap[$mapKey][] = $bLabel;
                }

                $projectCityBarangayMap[$cNorm] ??= [];
                if (!in_array($bLabel, $projectCityBarangayMap[$cNorm], true)) {
                    $projectCityBarangayMap[$cNorm][] = $bLabel;
                }

                $projectCityBarangayMap[$cLabel] ??= [];
                if (!in_array($bLabel, $projectCityBarangayMap[$cLabel], true)) {
                    $projectCityBarangayMap[$cLabel][] = $bLabel;
                }

                if (!in_array($bLabel, $allProjectBarangays, true)) {
                    $allProjectBarangays[] = $bLabel;
                }
            }
        }

        foreach ($projectCityBarangayMap as $k => &$bList) {
            natcasesort($bList);
            $bList = array_values($bList);
        }
        unset($bList);

        $filterOptions['city_barangay_map'] = $projectCityBarangayMap;

        if (!empty($filters['city_municipality'])) {
            $selBarangays = collect();
            foreach ($filters['city_municipality'] as $city) {
                $cityNorm = mb_strtolower(trim($city));
                if (!empty($filters['province'])) {
                    foreach ($filters['province'] as $prov) {
                        $pNorm = mb_strtolower(trim($prov));
                        $mk = $pNorm . '|' . $cityNorm;
                        if (!empty($projectCityBarangayMap[$mk])) {
                            $selBarangays = $selBarangays->concat($projectCityBarangayMap[$mk]);
                        }
                    }
                }
                if ($selBarangays->isEmpty() && !empty($projectCityBarangayMap[$cityNorm])) {
                    $selBarangays = $selBarangays->concat($projectCityBarangayMap[$cityNorm]);
                }
                if ($selBarangays->isEmpty() && !empty($projectCityBarangayMap[$city])) {
                    $selBarangays = $selBarangays->concat($projectCityBarangayMap[$city]);
                }
            }
            $filterOptions['barangays'] = $selBarangays->unique()->values();
        } else {
            $filterOptions['barangays'] = collect();
        }

        // 4. Programs, Project Types, Project Statuses, Funding Years
        foreach ([
            'fund_sources' => ['fund_source', $expressions['fund_source']],
            'funding_years' => ['funding_year', $expressions['funding_year']],
            'programs' => ['program', 'spp.program'],
            'project_types' => ['project_type', 'spp.type_of_project'],
            'project_statuses' => ['project_status', 'spp.status'],
        ] as $optionKey => [$excludeFilter, $column]) {
            $optionsQuery = clone $baseQuery;
            $this->applyFiltersToQuery($optionsQuery, $filters, $excludeFilter, $expressions);
            $filterOptions[$optionKey] = $optionsQuery
                ->selectRaw("{$column} as option_value")
                ->whereRaw("{$column} IS NOT NULL")
                ->whereRaw("TRIM(COALESCE({$column}, '')) <> ''")
                ->distinct()
                ->orderBy('option_value')
                ->pluck('option_value');
        }

        return $filterOptions;
    }
}

