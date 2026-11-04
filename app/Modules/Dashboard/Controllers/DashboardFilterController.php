<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardFilterService;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardFilterController extends Controller
{
    protected DashboardFilterService $filterService;

    public function __construct(DashboardFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    public function getFilterOptions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rawFilters = $request->all();
        $options = $this->getCachedFilterOptions($user, $rawFilters);

        $responsePayload = [
            'provinces' => collect($options['provinces'] ?? [])->values()->all(),
            'cities' => collect($options['cities'] ?? [])->values()->all(),
            'barangays' => collect($options['barangays'] ?? [])->values()->all(),
            'programs' => collect($options['programs'] ?? [])->values()->all(),
            'funding_years' => collect($options['funding_years'] ?? [])->values()->all(),
            'project_types' => collect($options['project_types'] ?? [])->values()->all(),
            'project_statuses' => collect($options['project_statuses'] ?? [])->values()->all(),
            'province_city_map' => $options['province_city_map'] ?? [],
            'city_barangay_map' => $options['city_barangay_map'] ?? [],
        ];

        return response()->json($responsePayload);
    }

    public function getFilterOptionsByType(Request $request, string $type): JsonResponse
    {
        $user = Auth::user();
        $rawFilters = $request->all();
        $options = $this->getCachedFilterOptions($user, $rawFilters);

        $normalizedType = strtolower(trim($type));
        $typeMap = [
            'province' => 'provinces',
            'provinces' => 'provinces',
            'city' => 'cities',
            'cities' => 'cities',
            'city_municipality' => 'cities',
            'barangay' => 'barangays',
            'barangays' => 'barangays',
            'program' => 'programs',
            'programs' => 'programs',
            'funding_year' => 'funding_years',
            'funding_years' => 'funding_years',
            'project_type' => 'project_types',
            'project_types' => 'project_types',
            'project_status' => 'project_statuses',
            'project_statuses' => 'project_statuses',
        ];

        $key = $typeMap[$normalizedType] ?? null;
        if ($key === null) {
            return response()->json(['error' => 'Invalid filter type'], 400);
        }

        $list = collect($options[$key] ?? [])->values()->all();

        $preserveInput = $request->input("preserve_{$normalizedType}", $request->input("preserve_{$key}", []));
        if (!is_array($preserveInput)) {
            $preserveInput = $preserveInput !== null && $preserveInput !== '' ? explode(',', (string) $preserveInput) : [];
        }

        $preserve = array_values(array_intersect($preserveInput, $list));

        return response()->json([
            'options' => $list,
            'preserve' => $preserve,
        ]);
    }

    private function getCachedFilterOptions($user, array $rawFilters): array
    {
        $normalizedFilters = $this->filterService->normalizeFilters($rawFilters);
        $cacheKey = 'dashboard_filter_options_v3:' . md5(serialize([
            'user_id' => $user?->getAuthIdentifier(),
            'filters' => $normalizedFilters,
        ]));

        return Cache::remember($cacheKey, now()->addMinute(), function () use ($user, $rawFilters) {
            return $this->filterService->getFilterOptions($user, $rawFilters);
        });
    }
}
