@extends('layouts.dashboard')

@section('page-title', 'Local Project Monitoring Committee')

@section('content')
<div class="content-header">
    <h1>Local Project Monitoring Committee</h1>
    <p>Manage and monitor local project committees</p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
            </div>
            <div class="card-body">
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
            </div>
            <div class="card-body">
                @php
                    $activeFilters = array_merge([
                        'search' => '',
                        'province' => '',
                        'city' => '',
                        'status' => '',
                    ], $filters ?? []);
                    $provinceMunicipalities = $filterOptions['provinceMunicipalities'] ?? [];
                    $selectedProvinceFilter = trim((string) ($activeFilters['province'] ?? ''));
                    if ($selectedProvinceFilter !== '' && array_key_exists($selectedProvinceFilter, $provinceMunicipalities)) {
                        $cityOptions = collect($provinceMunicipalities[$selectedProvinceFilter] ?? []);
                    } else {
                        $cityOptions = collect($provinceMunicipalities)->flatten(1);
                    }
                    $cityOptions = $cityOptions
                        ->map(fn($city) => trim((string) $city))
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();
                @endphp
                <details id="lpmc-filters-panel" class="lpmc-filters-panel" open>
                    <summary class="lpmc-filters-summary">
                        <span>Filters</span>
                        <span class="lpmc-filters-summary-icon" aria-hidden="true"></span>
                    </summary>
                    <div class="lpmc-filters-body">
                        <form id="lpmc-filters-form" method="GET" action="{{ route('local-project-monitoring-committee.index') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 16px;">
                            <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
                            <div style="min-width: 220px; flex: 1;">
                                <label for="lpmc-search" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Search</label>
                                <div class="lpmc-search-field">
                                    <input id="lpmc-search" name="search" type="text" value="{{ $activeFilters['search'] }}" placeholder="Search province or city/municipality..." autocomplete="off" class="lpmc-search-input" style="width: 100%; padding: 8px 36px 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                    <span id="lpmc-search-spinner" class="lpmc-search-spinner" aria-hidden="true"></span>
                                </div>
                            </div>
                            <div style="min-width: 170px;">
                                <label for="filter-province" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Province</label>
                                <select id="filter-province" name="province" style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                    <option value="">All</option>
                                    @foreach(($filterOptions['provinces'] ?? []) as $option)
                                        <option value="{{ $option }}" {{ (string) $activeFilters['province'] === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="min-width: 170px;">
                                <label for="filter-city" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">City/Mun</label>
                                <select id="filter-city" name="city" data-selected-city="{{ $activeFilters['city'] }}" style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                    <option value="">All</option>
                                    @foreach($cityOptions as $city)
                                        <option value="{{ $city }}" {{ (string) $activeFilters['city'] === (string) $city ? 'selected' : '' }}>{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="min-width: 170px;">
                                <label for="filter-status" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Status</label>
                                <select id="filter-status" name="status" style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                    <option value="">All</option>
                                    @foreach(($filterOptions['statuses'] ?? []) as $value => $label)
                                        <option value="{{ $value }}" {{ (string) $activeFilters['status'] === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <a href="{{ route('local-project-monitoring-committee.index', ['per_page' => $perPage ?? 15]) }}" style="padding: 8px 12px; background-color: #6b7280; color: white; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                                Clear
                            </a>
                        </form>
                        <div class="lpmc-filter-summary">
                            <span>{{ number_format($officeRows->total()) }} office{{ $officeRows->total() === 1 ? '' : 's' }} matched</span>
                            @if($activeFilters['search'] !== '')
                                <span class="lpmc-active-filter">Search: {{ $activeFilters['search'] }}</span>
                            @endif
                            @if($activeFilters['province'] !== '')
                                <span class="lpmc-active-filter">Province: {{ $activeFilters['province'] }}</span>
                            @endif
                            @if($activeFilters['city'] !== '')
                                <span class="lpmc-active-filter">City/Mun: {{ $activeFilters['city'] }}</span>
                            @endif
                            @if($activeFilters['status'] !== '')
                                <span class="lpmc-active-filter">Status: {{ ($filterOptions['statuses'][$activeFilters['status']] ?? $activeFilters['status']) }}</span>
                            @endif
                        </div>
                    </div>
                </details>
                <div class="lpmc-status-legend">
                    <span class="lpmc-status-chip lpmc-status-chip--empty">
                        <i class="fas fa-minus"></i>
                        <span>No upload</span>
                    </span>
                    <span class="lpmc-status-chip lpmc-status-chip--pending-po">
                        <i class="fas fa-hourglass-half"></i>
                        <span>For PO Approval</span>
                    </span>
                    <span class="lpmc-status-chip lpmc-status-chip--pending-ro">
                        <i class="fas fa-clock"></i>
                        <span>For RO Approval</span>
                    </span>
                    <span class="lpmc-status-chip lpmc-status-chip--approved">
                        <i class="fas fa-check-circle"></i>
                        <span>Approved</span>
                    </span>
                    <span class="lpmc-status-chip lpmc-status-chip--returned">
                        <i class="fas fa-undo"></i>
                        <span>Returned</span>
                    </span>
                </div>
                <div class="table-responsive report-table-shell" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <table id="lpmc-office-table" style="width: 100%; border-collapse: collapse; min-width: 1900px;">
                        <thead>
                            <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Province</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">City/Municipality</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Executive Order for CY 2025 (MOV)</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Annual Work and Financial Plan (AWFP) for CY 2025</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Monitoring and Evaluation Plan for CY 2025</th>
                                <th colspan="12" style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Per Quarter Uploads</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Executive Order for 2026</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">CY 2026 Annual Work and Financial Plan</th>
                                <th rowspan="3" style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">CY 2026 Monitoring and Evaluation Plan</th>
                                <th rowspan="3" style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Actions</th>
                            </tr>
                            <tr>
                                <th colspan="4" style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Meetings Conducted</th>
                                <th colspan="4" style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Monitoring Conducted</th>
                                <th colspan="4" style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Training Conducted</th>
                            </tr>
                            <tr>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q1</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q2</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q3</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q4</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q1</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q2</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q3</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q4</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q1</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q2</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q3</th>
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Q4</th>
                            </tr>
                        </thead>
                        <tbody id="lpmc-table-body">
                            @forelse ($officeRows as $row)
                                @php
                                    $officeDocs = $documentsByOffice[$row['city_municipality']] ?? [];
                                    $statusIcon = function ($doc) {
                                        if (!$doc) {
                                            return '<span class="lpmc-status-chip lpmc-status-chip--empty" title="No upload yet"><i class="fas fa-minus"></i><span>-</span></span>';
                                        }

                                        if ($doc->status === 'approved') {
                                            return '<span class="lpmc-status-chip lpmc-status-chip--approved" title="Approved"><i class="fas fa-check-circle"></i><span>Approved</span></span>';
                                        }

                                        if ($doc->status === 'returned') {
                                            return '<span class="lpmc-status-chip lpmc-status-chip--returned" title="Returned"><i class="fas fa-undo"></i><span>Returned</span></span>';
                                        }

                                        if ($doc->status === 'pending_ro') {
                                            return '<span class="lpmc-status-chip lpmc-status-chip--pending-ro" title="For DILG Regional Office Approval"><i class="fas fa-clock"></i><span>For RO</span></span>';
                                        }

                                        if ($doc->status === 'pending' || !empty($doc->file_path)) {
                                            return '<span class="lpmc-status-chip lpmc-status-chip--pending-po" title="For DILG Provincial Office Approval"><i class="fas fa-hourglass-half"></i><span>For PO</span></span>';
                                        }

                                        return '<span class="lpmc-status-chip lpmc-status-chip--empty" title="No upload yet"><i class="fas fa-minus"></i><span>-</span></span>';
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid #e5e7eb; transition: all 0.3s ease;">
                                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $row['province'] }}</td>
                                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $row['city_municipality'] }}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['eo|2025|'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['awfp|2025|'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['mep|2025|'] ?? null) !!}</td>

                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['meetings||Q1'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['meetings||Q2'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['meetings||Q3'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['meetings||Q4'] ?? null) !!}</td>

                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['monitoring||Q1'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['monitoring||Q2'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['monitoring||Q3'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['monitoring||Q4'] ?? null) !!}</td>

                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['training||Q1'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['training||Q2'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['training||Q3'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['training||Q4'] ?? null) !!}</td>

                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['eo|2026|'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['awfp|2026|'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center; color: #111827; font-size: 14px;">{!! $statusIcon($officeDocs['mep|2026|'] ?? null) !!}</td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('local-project-monitoring-committee.edit', $row['city_municipality']) }}" style="display: inline-block; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease;">
                                            <i class="fas fa-eye" style="margin-right: 4px;"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr style="border-bottom: 1px solid #e5e7eb; transition: all 0.3s ease;">
                                    <td colspan="21" style="padding: 40px; text-align: center; color: #6b7280;">
                                        <i class="fas fa-table" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                        No records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($officeRows->count() > 0)
                    <div class="table-pagination-row" style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <div style="font-size: 12px; color: #6b7280;">
                                Page {{ $officeRows->currentPage() }} of {{ $officeRows->lastPage() }} &middot;
                                Showing {{ $officeRows->firstItem() ?? 0 }}-{{ $officeRows->lastItem() ?? 0 }} of {{ $officeRows->total() }}
                            </div>
                            <form method="GET" style="display: inline-flex; align-items: center;">
                                @foreach (request()->except(['page', 'per_page']) as $queryKey => $queryValue)
                                    @if (is_array($queryValue))
                                        @foreach ($queryValue as $nestedValue)
                                            <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                    @endif
                                @endforeach
                                <select name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                    @foreach ([10, 15, 25, 50] as $option)
                                        <option value="{{ $option }}" @selected((int) ($perPage ?? 15) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                            @if ($officeRows->onFirstPage())
                                <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-chevron-left"></i> Back
                                </span>
                            @else
                                <a href="{{ $officeRows->previousPageUrl() }}" style="padding: 8px 12px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    <i class="fas fa-chevron-left"></i> Back
                                </a>
                            @endif

                            @if ($officeRows->hasMorePages())
                                <a href="{{ $officeRows->nextPageUrl() }}" style="padding: 8px 12px; background-color: #002C76; color: white; border: 1px solid #002C76; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            @else
                                <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .lpmc-filters-panel {
        margin-bottom: 16px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        overflow: hidden;
    }

    .lpmc-filters-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        color: #1f2937;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        list-style: none;
        user-select: none;
    }

    .lpmc-filters-summary::-webkit-details-marker {
        display: none;
    }

    .lpmc-filters-summary-icon::before {
        content: '+';
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #e5e7eb;
        color: #374151;
        font-size: 16px;
        line-height: 1;
    }

    .lpmc-filters-panel[open] .lpmc-filters-summary {
        border-bottom: 1px solid #e5e7eb;
    }

    .lpmc-filters-panel[open] .lpmc-filters-summary-icon::before {
        content: '-';
    }

    .lpmc-filters-body {
        padding: 16px;
    }

    .lpmc-search-field {
        position: relative;
    }

    .lpmc-search-field .lpmc-search-input {
        transition: border-color 0.2s ease, box-shadow 0.2s ease, padding-right 0.2s ease;
    }

    .lpmc-search-field.is-loading .lpmc-search-input {
        padding-right: 40px !important;
        border-color: #93c5fd !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .lpmc-search-spinner {
        position: absolute;
        right: 12px;
        top: 50%;
        width: 16px;
        height: 16px;
        margin-top: -8px;
        border-radius: 999px;
        border: 2px solid #dbeafe;
        border-top-color: #2563eb;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .lpmc-search-field.is-loading .lpmc-search-spinner {
        opacity: 1;
        animation: lpmc-search-spin 0.7s linear infinite;
    }

    .lpmc-filter-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 12px;
        font-size: 12px;
        color: #6b7280;
    }

    .lpmc-active-filter {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        background: #e0e7ff;
        color: #3730a3;
        font-weight: 600;
    }

    .lpmc-status-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .lpmc-status-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-width: 74px;
        padding: 5px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .lpmc-status-chip i {
        font-size: 10px;
    }

    .lpmc-status-chip--empty {
        background: #f3f4f6;
        border-color: #e5e7eb;
        color: #6b7280;
    }

    .lpmc-status-chip--pending-po {
        background: #fffbeb;
        border-color: #fcd34d;
        color: #b45309;
    }

    .lpmc-status-chip--pending-ro {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .lpmc-status-chip--approved {
        background: #ecfdf3;
        border-color: #86efac;
        color: #15803d;
    }

    .lpmc-status-chip--returned {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #b91c1c;
    }

    .report-table-shell table tbody tr:hover {
        background-color: #eef4ff !important;
    }

    .report-table-shell {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @keyframes lpmc-search-spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 768px) {
        .lpmc-filters-panel {
            margin-bottom: 14px;
        }

        .report-table-shell {
            padding: 16px !important;
        }

        .table-pagination-row {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .lpmc-filters-body form {
            align-items: stretch !important;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filtersPanel = document.getElementById('lpmc-filters-panel');
        const filtersForm = document.getElementById('lpmc-filters-form');
        const searchInput = document.getElementById('lpmc-search');
        const searchField = searchInput ? searchInput.closest('.lpmc-search-field') : null;
        const provinceSelect = document.getElementById('filter-province');
        const citySelect = document.getElementById('filter-city');
        const statusSelect = document.getElementById('filter-status');
        const locationData = @json($provinceMunicipalities ?? []);
        const selectedCity = citySelect ? (citySelect.dataset.selectedCity || '') : '';

        if (filtersPanel && window.matchMedia('(max-width: 768px)').matches) {
            filtersPanel.removeAttribute('open');
        }

        if (!filtersForm || !provinceSelect || !citySelect) {
            return;
        }

        const allCities = new Set();
        Object.values(locationData).forEach(function (cities) {
            if (!Array.isArray(cities)) {
                return;
            }

            cities.forEach(function (city) {
                allCities.add(city);
            });
        });

        function populateCityOptions(selectedProvince, preferredValue) {
            const currentValue = preferredValue || citySelect.value || '';

            citySelect.innerHTML = '';
            const allOption = document.createElement('option');
            allOption.value = '';
            allOption.textContent = 'All';
            citySelect.appendChild(allOption);

            const cities = selectedProvince && Array.isArray(locationData[selectedProvince])
                ? locationData[selectedProvince]
                : Array.from(allCities);

            cities.sort().forEach(function (city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });

            if (currentValue && cities.includes(currentValue)) {
                citySelect.value = currentValue;
            }
        }

        function setSearchLoading(isLoading) {
            if (!searchField) {
                return;
            }

            searchField.classList.toggle('is-loading', isLoading);
        }

        function debounce(callback, delay) {
            let timerId;

            return function () {
                const args = arguments;
                clearTimeout(timerId);
                timerId = window.setTimeout(function () {
                    callback.apply(null, args);
                }, delay);
            };
        }

        function submitFilters() {
            filtersForm.requestSubmit();
        }

        if (searchInput) {
            const debouncedSearch = debounce(function () {
                submitFilters();
            }, 1000);

            searchInput.addEventListener('input', function () {
                setSearchLoading(searchInput.value.trim() !== '');
                debouncedSearch();
            });
        }

        provinceSelect.addEventListener('change', function () {
            populateCityOptions(this.value);
            citySelect.value = '';
            submitFilters();
        });

        [citySelect, statusSelect].filter(Boolean).forEach(function (select) {
            select.addEventListener('change', submitFilters);
        });

        populateCityOptions(provinceSelect.value, selectedCity);
        window.addEventListener('pageshow', function () {
            setSearchLoading(false);
        });
    });
</script>
@endsection
