<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectLocationFilterHelper
{
    public static function normalizeLabel($value): string
    {
        $label = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $label === null ? '' : $label;
    }

    public static function normalizeComparableLocationLabel($value): string
    {
        $label = mb_strtolower(self::normalizeLabel($value));
        if ($label === '') {
            return '';
        }

        $label = str_replace(
            ['(capital)', 'municipality of ', 'city of ', ' municipality', ' city'],
            '',
            $label
        );

        $label = preg_replace('/\s+/u', ' ', trim($label));

        return $label === null ? '' : $label;
    }

    public static function buildConfiguredLocationHierarchy(): array
    {
        static $hierarchy;

        if (is_array($hierarchy)) {
            return $hierarchy;
        }

        $hierarchy = [];
        $path = resource_path('js/locationData.js');
        if (!is_file($path)) {
            return $hierarchy;
        }

        $content = @file_get_contents($path);
        if (!is_string($content) || trim($content) === '') {
            return $hierarchy;
        }

        if (!preg_match('/export\s+const\s+locationData\s*=\s*(\{.*\})\s*;?\s*$/su', $content, $matches)) {
            return $hierarchy;
        }

        $decoded = json_decode($matches[1], true);
        if (!is_array($decoded)) {
            return $hierarchy;
        }

        foreach ($decoded as $province => $cities) {
            $provinceLabel = self::normalizeLabel($province);
            if ($provinceLabel === '' || !is_array($cities)) {
                continue;
            }

            $hierarchy[$provinceLabel] ??= [];

            foreach ($cities as $city => $barangays) {
                $cityLabel = self::normalizeLabel($city);
                if ($cityLabel === '') {
                    continue;
                }

                $hierarchy[$provinceLabel][$cityLabel] = collect(is_array($barangays) ? $barangays : [])
                    ->map([self::class, 'normalizeLabel'])
                    ->filter()
                    ->unique(function ($label) {
                        return mb_strtolower((string) $label);
                    })
                    ->values()
                    ->all();
            }
        }

        return $hierarchy;
    }

    public static function buildConfiguredProvinceLabels(): array
    {
        return array_keys(self::buildConfiguredLocationHierarchy());
    }

    public static function buildConfiguredProvinceCityMapFromHierarchy(array $provinceOptionLabels = []): array
    {
        $hierarchy = self::buildConfiguredLocationHierarchy();
        if (empty($hierarchy)) {
            return [];
        }

        $provinceLabels = collect($provinceOptionLabels)
            ->map([self::class, 'normalizeLabel'])
            ->filter()
            ->values()
            ->all();

        if (empty($provinceLabels)) {
            $provinceLabels = array_keys($hierarchy);
        }

        $configuredProvinceIndex = [];
        foreach ($hierarchy as $provinceLabel => $cityMap) {
            $configuredProvinceIndex[mb_strtolower($provinceLabel)] = array_keys($cityMap);
        }

        $provinceCityMap = [];
        foreach ($provinceLabels as $provinceLabel) {
            $provinceCityMap[$provinceLabel] = $configuredProvinceIndex[mb_strtolower($provinceLabel)] ?? [];
        }

        return $provinceCityMap;
    }

    public static function buildConfiguredCityBarangayMapFromHierarchy(array $provinceLabels = []): array
    {
        $hierarchy = self::buildConfiguredLocationHierarchy();
        if (empty($hierarchy)) {
            return [];
        }

        $allowedProvinceKeys = collect($provinceLabels)
            ->map([self::class, 'normalizeLabel'])
            ->filter()
            ->map(function ($label) {
                return mb_strtolower((string) $label);
            })
            ->values()
            ->all();

        $useAllProvinces = empty($allowedProvinceKeys);
        $cityBarangayMap = [];

        foreach ($hierarchy as $provinceLabel => $cityMap) {
            if (!$useAllProvinces && !in_array(mb_strtolower($provinceLabel), $allowedProvinceKeys, true)) {
                continue;
            }

            foreach ($cityMap as $cityLabel => $barangays) {
                $cityBarangayMap[$cityLabel] = collect(is_array($barangays) ? $barangays : [])
                    ->map([self::class, 'normalizeLabel'])
                    ->filter()
                    ->unique(function ($label) {
                        return mb_strtolower((string) $label);
                    })
                    ->values()
                    ->all();
            }
        }

        return $cityBarangayMap;
    }

    public static function buildProvinceCityMap($baseQuery, array $provinceOptionLabels, string $provinceColumn, string $cityColumn): array
    {
        $configuredMap = self::buildConfiguredProvinceCityMap($provinceOptionLabels);
        if (!empty(array_filter($configuredMap))) {
            return $configuredMap;
        }

        $provinceCityMap = [];
        foreach ($provinceOptionLabels as $provinceLabel) {
            $normalizedProvince = self::normalizeLabel($provinceLabel);
            if ($normalizedProvince !== '') {
                $provinceCityMap[$normalizedProvince] = [];
            }
        }

        $provinceCityRows = (clone $baseQuery)
            ->selectRaw("TRIM(COALESCE({$provinceColumn}, '')) as province")
            ->selectRaw("TRIM(COALESCE({$cityColumn}, '')) as city_municipality")
            ->whereRaw("TRIM(COALESCE({$provinceColumn}, '')) <> ''")
            ->whereRaw("TRIM(COALESCE({$cityColumn}, '')) <> ''")
            ->distinct()
            ->orderByRaw("TRIM(COALESCE({$provinceColumn}, ''))")
            ->orderByRaw("TRIM(COALESCE({$cityColumn}, ''))")
            ->get();

        foreach ($provinceCityRows as $row) {
            $provinceLabel = self::normalizeLabel($row->province ?? '');
            $cityLabel = self::normalizeLabel($row->city_municipality ?? '');

            if ($provinceLabel === '' || $cityLabel === '') {
                continue;
            }

            $provinceCityMap[$provinceLabel] ??= [];
            if (!in_array($cityLabel, $provinceCityMap[$provinceLabel], true)) {
                $provinceCityMap[$provinceLabel][] = $cityLabel;
            }
        }

        return $provinceCityMap;
    }

    public static function buildConfiguredProvinceCityMap(array $provinceOptionLabels): array
    {
        $provinceCityMap = [];
        $normalizedProvinceLabels = collect($provinceOptionLabels)
            ->map([self::class, 'normalizeLabel'])
            ->filter()
            ->values()
            ->all();

        foreach ($normalizedProvinceLabels as $provinceLabel) {
            $provinceCityMap[$provinceLabel] = [];
        }

        if (!Schema::hasTable('location_provinces') || !Schema::hasTable('location_city_municipalities')) {
            return $provinceCityMap;
        }

        $configuredProvinceCityRows = DB::table('location_city_municipalities as lcm')
            ->join('location_provinces as lp', 'lp.id', '=', 'lcm.province_id')
            ->selectRaw('TRIM(COALESCE(lp.province_name, "")) as province')
            ->selectRaw('TRIM(COALESCE(lcm.citymun_name, "")) as city_municipality')
            ->whereNotNull('lp.province_name')
            ->whereRaw('TRIM(lp.province_name) <> ""')
            ->whereNotNull('lcm.citymun_name')
            ->whereRaw('TRIM(lcm.citymun_name) <> ""')
            ->orderBy('lp.province_name')
            ->orderBy('lcm.citymun_name')
            ->get();

        $configuredProvinceIndex = [];
        foreach ($configuredProvinceCityRows as $row) {
            $provinceLabel = self::normalizeLabel($row->province ?? '');
            $cityLabel = self::normalizeLabel($row->city_municipality ?? '');

            if ($provinceLabel === '' || $cityLabel === '') {
                continue;
            }

            $configuredProvinceKey = mb_strtolower($provinceLabel);
            $configuredProvinceIndex[$configuredProvinceKey] ??= [];
            if (!in_array($cityLabel, $configuredProvinceIndex[$configuredProvinceKey], true)) {
                $configuredProvinceIndex[$configuredProvinceKey][] = $cityLabel;
            }
        }

        foreach ($normalizedProvinceLabels as $provinceLabel) {
            $provinceCityMap[$provinceLabel] = $configuredProvinceIndex[mb_strtolower($provinceLabel)] ?? [];
        }

        return $provinceCityMap;
    }

    public static function buildCityBarangayMap($baseQuery, string $cityColumn, string $barangayColumn): array
    {
        $cityBarangayRows = (clone $baseQuery)
            ->selectRaw("TRIM(COALESCE({$cityColumn}, '')) as city_municipality")
            ->selectRaw("TRIM(COALESCE({$barangayColumn}, '')) as barangay")
            ->whereRaw("TRIM(COALESCE({$cityColumn}, '')) <> ''")
            ->whereRaw("TRIM(COALESCE({$barangayColumn}, '')) <> ''")
            ->orderByRaw("TRIM(COALESCE({$cityColumn}, ''))")
            ->get();

        $cityBarangayMap = [];
        $seenBarangays = [];

        foreach ($cityBarangayRows as $row) {
            $cityLabel = self::normalizeLabel($row->city_municipality ?? '');
            if ($cityLabel === '') {
                continue;
            }

            $barangayItems = preg_split('/\r\n|\r|\n|,/u', (string) ($row->barangay ?? '')) ?: [];
            foreach ($barangayItems as $barangayValue) {
                $barangayLabel = self::normalizeLabel($barangayValue);
                if ($barangayLabel === '') {
                    continue;
                }

                $cityBarangayMap[$cityLabel] ??= [];
                $seenBarangays[$cityLabel] ??= [];
                $dedupeKey = mb_strtolower($barangayLabel);

                if (!array_key_exists($dedupeKey, $seenBarangays[$cityLabel])) {
                    $cityBarangayMap[$cityLabel][] = $barangayLabel;
                    $seenBarangays[$cityLabel][$dedupeKey] = true;
                }
            }
        }

        return $cityBarangayMap;
    }

    public static function selectedMappedValues(array $selectedKeys, array $map): Collection
    {
        return collect($selectedKeys)
            ->map([self::class, 'normalizeLabel'])
            ->filter()
            ->flatMap(function (string $key) use ($map) {
                return $map[$key] ?? [];
            })
            ->map([self::class, 'normalizeLabel'])
            ->filter()
            ->unique(function ($label) {
                return mb_strtolower((string) $label);
            })
            ->values();
    }
}
