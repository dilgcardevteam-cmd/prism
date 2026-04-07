@extends('layouts.dashboard')

@section('title', 'RLIP/LIME-20% Development Fund')
@section('page-title', 'RLIP/LIME-20% Development Fund')

@section('content')
    <div class="content-header">
        <h1>RLIP/LIME-20% Development Fund</h1>
        <p>Reviewed from RLIP/LIME master list and displayed in table format.</p>
    </div>

    <div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
            <h2 style="color: #002C76; font-size: 18px; margin: 0;">Projects</h2>
            <div style="font-size: 12px; color: #6b7280;">
                @if(!empty($sourceMeta['row_count']))
                    Source rows: {{ number_format((int) $sourceMeta['row_count']) }}
                @endif
                @if(!empty($sourceMeta['generated_at']))
                    &middot; Parsed: {{ \Illuminate\Support\Carbon::parse($sourceMeta['generated_at'])->format('Y-m-d h:i A') }}
                @endif
            </div>
        </div>

       

        @php
            $activeFilters = array_merge([
                'search' => '',
                'project_code' => '',
                'funding_year' => '',
                'fund_source' => '',
                'province' => '',
                'city' => '',
                'status' => '',
            ], $filters ?? []);

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

            $columnToggleOptions = [
                'funding_year' => 'Funding Year',
                'fund_source' => 'Fund Source',
                'project_type' => 'Project Type',
                'project_status' => 'Status',
                'total_amount_programmed' => 'Total Programmed',
                'overall_completion' => 'Overall Completion',
                'employment_generated' => 'Employment',
                'profile_approval_status' => 'Profile Approval',
                'contractor_name' => 'Contractor',
            ];
        @endphp

        <details id="rlip-filters-panel" class="rlip-filters-panel" open>
            <summary class="rlip-filters-summary">
                <span>Filters</span>
                <span class="rlip-filters-summary-icon" aria-hidden="true"></span>
            </summary>
            <div class="rlip-filters-body">
                <form id="rlip-filters-form" method="GET" action="{{ route('projects.rlip-lime') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 16px;">
                    <input type="hidden" name="sort_by" value="{{ $sortBy ?? 'project_code' }}">
                    <input type="hidden" name="sort_dir" value="{{ $sortDir ?? 'asc' }}">
                    <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">

                    <div style="min-width: 220px; flex: 1;">
                        <label for="rlip-search" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Search</label>
                        <input id="rlip-search" name="search" type="text" value="{{ $activeFilters['search'] }}" placeholder="Search project code, title, location..." style="width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                    </div>
                    <div style="min-width: 150px;">
                        <label for="filter-year" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Funding Year</label>
                        <select id="filter-year" name="funding_year" style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                            <option value="">All</option>
                            @foreach($fundingYears as $year)
                                <option value="{{ $year }}" {{ (string) $activeFilters['funding_year'] === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="min-width: 160px;">
                        <label for="filter-fund-source" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Fund Source</label>
                        <select id="filter-fund-source" name="fund_source" style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                            <option value="">All</option>
                            @foreach($fundSources as $source)
                                <option value="{{ $source }}" {{ (string) $activeFilters['fund_source'] === (string) $source ? 'selected' : '' }}>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="min-width: 170px;">
                        <label for="filter-province" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Province</label>
                        <select id="filter-province" name="province" style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                            <option value="">All</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}" {{ (string) $activeFilters['province'] === (string) $province ? 'selected' : '' }}>{{ $province }}</option>
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
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" {{ (string) $activeFilters['status'] === (string) $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('projects.rlip-lime', ['sort_by' => $sortBy ?? 'project_code', 'sort_dir' => $sortDir ?? 'asc', 'per_page' => $perPage ?? 10]) }}" style="padding: 8px 12px; background-color: #6b7280; color: white; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                        Clear
                    </a>
                </form>

                <div class="rlip-column-toggle-panel" aria-label="Table columns filter">
                    <div class="rlip-column-toggle-header">
                        <div class="rlip-column-toggle-label">Visible Columns</div>
                        <label class="rlip-column-toggle-option rlip-column-toggle-option--master">
                            <input type="checkbox" id="rlip-column-toggle-all" checked>
                            <span>Select All</span>
                        </label>
                    </div>
                    <div class="rlip-column-toggle-grid">
                        @foreach($columnToggleOptions as $columnKey => $columnLabel)
                            <label class="rlip-column-toggle-option">
                                <input type="checkbox" class="rlip-column-toggle-checkbox" data-column-toggle="{{ $columnKey }}" checked>
                                <span>{{ $columnLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </details>

        @if($projects->isEmpty())
            <p style="margin: 0; color: #6b7280; text-align: center; padding: 40px 0;">No RLIP/LIME records found for the selected filters.</p>
        @else
            @php
                $currentSortBy = $sortBy ?? request('sort_by', 'project_code');
                $currentSortDir = $sortDir ?? request('sort_dir', 'asc');
                $currentSortDir = strtolower((string) $currentSortDir) === 'desc' ? 'desc' : 'asc';

                $nextSortDirection = function (string $column, string $defaultDirection = 'asc') use ($currentSortBy, $currentSortDir): string {
                    if ($currentSortBy === $column) {
                        return $currentSortDir === 'asc' ? 'desc' : 'asc';
                    }

                    return $defaultDirection;
                };

                $sortIndicator = function (string $column) use ($currentSortBy, $currentSortDir): string {
                    if ($currentSortBy !== $column) {
                        return '';
                    }

                    return $currentSortDir === 'asc' ? '▲' : '▼';
                };

                $sortUrl = function (string $column, string $defaultDirection = 'asc') use ($nextSortDirection): string {
                    $query = array_merge(request()->query(), [
                        'sort_by' => $column,
                        'sort_dir' => $nextSortDirection($column, $defaultDirection),
                    ]);
                    unset($query['page']);

                    return route('projects.rlip-lime', $query);
                };
            @endphp

            <div class="rlip-table-wrap" role="region" aria-label="RLIP/LIME Projects table" tabindex="0">
                <table id="rlip-table" style="width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed;">
                    <thead>
                        <tr style="background-color: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('project_code') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px;">
                                    <span>Project Code</span><span class="rlip-sort-indicator">{{ $sortIndicator('project_code') }}</span>
                                </a>
                            </th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; min-width: 240px;">
                                <a href="{{ $sortUrl('project_title') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px;">
                                    <span>Project Title</span><span class="rlip-sort-indicator">{{ $sortIndicator('project_title') }}</span>
                                </a>
                            </th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; min-width: 260px;">
                                <a href="{{ $sortUrl('location') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px;">
                                    <span>Location</span><span class="rlip-sort-indicator">{{ $sortIndicator('location') }}</span>
                                </a>
                            </th>
                            <th data-column-key="funding_year" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('funding_year', 'desc') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Funding Year</span><span class="rlip-sort-indicator">{{ $sortIndicator('funding_year') }}</span>
                                </a>
                            </th>
                            <th data-column-key="fund_source" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('fund_source') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Fund Source</span><span class="rlip-sort-indicator">{{ $sortIndicator('fund_source') }}</span>
                                </a>
                            </th>
                            <th data-column-key="project_type" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('project_type') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Project Type</span><span class="rlip-sort-indicator">{{ $sortIndicator('project_type') }}</span>
                                </a>
                            </th>
                            <th data-column-key="project_status" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('project_status') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Status</span><span class="rlip-sort-indicator">{{ $sortIndicator('project_status') }}</span>
                                </a>
                            </th>
                            <th data-column-key="total_amount_programmed" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('total_amount_programmed', 'desc') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Total Programmed</span><span class="rlip-sort-indicator">{{ $sortIndicator('total_amount_programmed') }}</span>
                                </a>
                            </th>
                            <th data-column-key="overall_completion" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('overall_completion', 'desc') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Overall Completion</span><span class="rlip-sort-indicator">{{ $sortIndicator('overall_completion') }}</span>
                                </a>
                            </th>
                            <th data-column-key="employment_generated" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('employment_generated', 'desc') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Employment</span><span class="rlip-sort-indicator">{{ $sortIndicator('employment_generated') }}</span>
                                </a>
                            </th>
                            <th data-column-key="profile_approval_status" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">
                                <a href="{{ $sortUrl('profile_approval_status') }}" class="rlip-sort-link" style="display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span>Profile Approval</span><span class="rlip-sort-indicator">{{ $sortIndicator('profile_approval_status') }}</span>
                                </a>
                            </th>
                            <th data-column-key="contractor_name" style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">Contractor</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $project)
                            @php
                                $viewUrl = route('projects.rlip-lime.show', array_merge(
                                    ['rowNumber' => $project['row_number']],
                                    request()->query()
                                ));
                            @endphp
                            <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='white'">
                                <td style="padding: 12px; color: #374151; font-weight: 500;">{{ $project['project_code'] }}</td>
                                <td style="padding: 12px; color: #374151; min-width: 240px;">
                                    <span class="wrap-text" title="{{ $project['project_title'] }}" style="display: block; max-width: 260px; white-space: normal; overflow-wrap: anywhere; word-break: break-word;">
                                        {{ $project['project_title'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #374151; min-width: 260px;">
                                    <div class="wrap-text" style="font-size: 12px; line-height: 1.4; white-space: normal; max-width: 260px;">
                                        <strong>Province:</strong> {{ $project['province'] ?: '-' }}<br>
                                        <strong>City/Mun:</strong> {{ $project['city_municipality'] ?: '-' }}<br>
                                        <strong>Barangay:</strong> {{ $project['barangay'] ?: '-' }}
                                    </div>
                                </td>
                                <td data-column-key="funding_year" style="padding: 12px; color: #374151; text-align: center;">{{ $project['funding_year'] ?: '-' }}</td>
                                <td data-column-key="fund_source" style="padding: 12px; color: #374151; text-align: center;">{{ $project['fund_source'] ?: '-' }}</td>
                                <td data-column-key="project_type" style="padding: 12px; color: #374151; text-align: center;">{{ $project['project_type'] ?: '-' }}</td>
                                <td data-column-key="project_status" style="padding: 12px; text-align: center;">
                                    <span style="display: inline-block; padding: 4px 8px; background-color: #dbeafe; color: #0369a1; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                        {{ $project['project_status'] ?: '-' }}
                                    </span>
                                </td>
                                <td data-column-key="total_amount_programmed" style="padding: 12px; color: #374151; text-align: center;">
                                    @if($project['total_amount_programmed_value'] !== null)
                                        &#8369; {{ number_format((float) $project['total_amount_programmed_value'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-column-key="overall_completion" style="padding: 12px; color: #374151; text-align: center;">
                                    @if($project['overall_completion_value'] !== null)
                                        {{ number_format((float) $project['overall_completion_value'], 2) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-column-key="employment_generated" style="padding: 12px; color: #374151; text-align: center;">
                                    @if($project['employment_generated_value'] !== null)
                                        {{ number_format((float) $project['employment_generated_value'], 0) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-column-key="profile_approval_status" style="padding: 12px; text-align: center;">
                                    <span style="display: inline-block; padding: 4px 8px; background-color: #e0e7ff; color: #3730a3; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                        {{ $project['profile_approval_status'] ?: '-' }}
                                    </span>
                                </td>
                                <td data-column-key="contractor_name" style="padding: 12px; color: #374151; text-align: center;">{{ $project['contractor_name'] ?: '-' }}</td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="{{ $viewUrl }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; background-color: #0369a1; color: white; border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='#0c4a6e'" onmouseout="this.style.backgroundColor='#0369a1'">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="rlip-mobile-cards" aria-label="RLIP/LIME Projects cards">
                @foreach($projects as $project)
                    @php
                        $viewUrl = route('projects.rlip-lime.show', array_merge(
                            ['rowNumber' => $project['row_number']],
                            request()->query()
                        ));
                    @endphp
                    <details class="rlip-mobile-card">
                        <summary class="rlip-mobile-card-summary">
                            <div class="rlip-mobile-card-summary-main">
                                <div class="rlip-mobile-card-code">{{ $project['project_code'] ?: '-' }}</div>
                                <h3 class="rlip-mobile-card-title">{{ $project['project_title'] ?: 'Untitled project' }}</h3>
                            </div>
                            <span class="rlip-mobile-card-chevron" aria-hidden="true"></span>
                        </summary>

                        <div class="rlip-mobile-card-body">
                            <div class="rlip-mobile-card-body-inner">
                                <div class="rlip-mobile-card-actions">
                                    <a href="{{ $viewUrl }}" class="rlip-mobile-card-action">View</a>
                                </div>

                                <div class="rlip-mobile-card-section">
                                    <div class="rlip-mobile-card-section-label">Location</div>
                                    <div class="rlip-mobile-card-location">
                                        <div><strong>Province:</strong> {{ $project['province'] ?: '-' }}</div>
                                        <div><strong>City/Mun:</strong> {{ $project['city_municipality'] ?: '-' }}</div>
                                        <div><strong>Barangay:</strong> {{ $project['barangay'] ?: '-' }}</div>
                                    </div>
                                </div>

                                <div class="rlip-mobile-card-details">
                                    <div class="rlip-mobile-card-detail" data-column-key="funding_year">
                                        <span class="rlip-mobile-card-detail-label">Funding Year</span>
                                        <strong>{{ $project['funding_year'] ?: '-' }}</strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="fund_source">
                                        <span class="rlip-mobile-card-detail-label">Fund Source</span>
                                        <strong>{{ $project['fund_source'] ?: '-' }}</strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="project_type">
                                        <span class="rlip-mobile-card-detail-label">Project Type</span>
                                        <strong>{{ $project['project_type'] ?: '-' }}</strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="project_status">
                                        <span class="rlip-mobile-card-detail-label">Status</span>
                                        <strong>{{ $project['project_status'] ?: '-' }}</strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="total_amount_programmed">
                                        <span class="rlip-mobile-card-detail-label">Total Programmed</span>
                                        <strong>
                                            @if($project['total_amount_programmed_value'] !== null)
                                                PHP {{ number_format((float) $project['total_amount_programmed_value'], 2) }}
                                            @else
                                                -
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="overall_completion">
                                        <span class="rlip-mobile-card-detail-label">Overall Completion</span>
                                        <strong>
                                            @if($project['overall_completion_value'] !== null)
                                                {{ number_format((float) $project['overall_completion_value'], 2) }}%
                                            @else
                                                -
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="employment_generated">
                                        <span class="rlip-mobile-card-detail-label">Employment</span>
                                        <strong>
                                            @if($project['employment_generated_value'] !== null)
                                                {{ number_format((float) $project['employment_generated_value'], 0) }}
                                            @else
                                                -
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="profile_approval_status">
                                        <span class="rlip-mobile-card-detail-label">Profile Approval</span>
                                        <strong>{{ $project['profile_approval_status'] ?: '-' }}</strong>
                                    </div>
                                    <div class="rlip-mobile-card-detail" data-column-key="contractor_name">
                                        <span class="rlip-mobile-card-detail-label">Contractor</span>
                                        <strong>{{ $project['contractor_name'] ?: '-' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>

            @if($projects->hasPages())
                <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <div style="font-size: 12px; color: #6b7280;">
                            Page {{ $projects->currentPage() }} of {{ $projects->lastPage() }} &middot;
                            Showing {{ $projects->firstItem() ?? 0 }}-{{ $projects->lastItem() ?? 0 }} of {{ $projects->total() }}
                        </div>
                        <form method="GET" action="{{ route('projects.rlip-lime') }}" style="display: inline-flex; align-items: center;">
                            <input type="hidden" name="search" value="{{ $activeFilters['search'] ?? '' }}">
                            <input type="hidden" name="project_code" value="{{ $activeFilters['project_code'] ?? '' }}">
                            <input type="hidden" name="funding_year" value="{{ $activeFilters['funding_year'] ?? '' }}">
                            <input type="hidden" name="fund_source" value="{{ $activeFilters['fund_source'] ?? '' }}">
                            <input type="hidden" name="province" value="{{ $activeFilters['province'] ?? '' }}">
                            <input type="hidden" name="city" value="{{ $activeFilters['city'] ?? '' }}">
                            <input type="hidden" name="status" value="{{ $activeFilters['status'] ?? '' }}">
                            <input type="hidden" name="sort_by" value="{{ $currentSortBy }}">
                            <input type="hidden" name="sort_dir" value="{{ $currentSortDir }}">
                            <select id="per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                @foreach([10, 15, 25, 50] as $option)
                                    <option value="{{ $option }}" {{ ($perPage ?? 10) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                        @if($projects->onFirstPage())
                            <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-chevron-left"></i> Back
                            </span>
                        @else
                            <a href="{{ $projects->previousPageUrl() }}" style="padding: 8px 12px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                <i class="fas fa-chevron-left"></i> Back
                            </a>
                        @endif

                        @if($projects->hasMorePages())
                            <a href="{{ $projects->nextPageUrl() }}" style="padding: 8px 12px; background-color: #002C76; color: white; border: 1px solid #002C76; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
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
        @endif
    </div>

    <style>
        .rlip-filters-panel {
            margin-bottom: 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            overflow: hidden;
        }

        .rlip-filters-summary {
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

        .rlip-filters-summary::-webkit-details-marker {
            display: none;
        }

        .rlip-filters-summary-icon::before {
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

        .rlip-filters-panel[open] .rlip-filters-summary {
            border-bottom: 1px solid #e5e7eb;
        }

        .rlip-filters-panel[open] .rlip-filters-summary-icon::before {
            content: '-';
        }

        .rlip-filters-body {
            padding: 16px;
        }

        .rlip-table-wrap {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
        }

        .rlip-mobile-cards {
            display: none;
            gap: 12px;
        }

        .rlip-mobile-card {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .rlip-mobile-card-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px;
            cursor: pointer;
            list-style: none;
        }

        .rlip-mobile-card-summary::-webkit-details-marker {
            display: none;
        }

        .rlip-mobile-card-summary-main {
            min-width: 0;
        }

        .rlip-mobile-card-chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #eff6ff;
            color: #0369a1;
            flex: 0 0 auto;
            transition: transform 0.25s ease;
        }

        .rlip-mobile-card-chevron::before {
            content: '+';
            font-size: 18px;
            line-height: 1;
        }

        .rlip-mobile-card-code {
            color: #1f2937;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .rlip-mobile-card-title {
            margin: 4px 0 0;
            color: #111827;
            font-size: 15px;
            line-height: 1.4;
        }

        .rlip-mobile-card[open] .rlip-mobile-card-chevron {
            transform: rotate(135deg);
        }

        .rlip-mobile-card-body {
            display: grid;
            grid-template-rows: 0fr;
            opacity: 0;
            transition: grid-template-rows 0.28s ease, opacity 0.22s ease;
        }

        .rlip-mobile-card[open] .rlip-mobile-card-body {
            grid-template-rows: 1fr;
            opacity: 1;
        }

        .rlip-mobile-card-body-inner {
            min-height: 0;
            overflow: hidden;
            padding: 0 14px 14px;
        }

        .rlip-mobile-card-actions {
            margin-bottom: 12px;
        }

        .rlip-mobile-card-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 8px;
            background: #0369a1;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .rlip-mobile-card-section {
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .rlip-mobile-card-section-label,
        .rlip-mobile-card-detail-label {
            display: block;
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .rlip-mobile-card-location {
            color: #374151;
            font-size: 12px;
            line-height: 1.5;
        }

        .rlip-mobile-card-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .rlip-mobile-card-detail {
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #111827;
            font-size: 12px;
            line-height: 1.4;
        }

        .rlip-mobile-card-detail strong {
            display: block;
            color: #111827;
            font-size: 13px;
            line-height: 1.5;
        }

        .rlip-mobile-card-detail.is-column-hidden {
            display: none;
        }

        #rlip-table {
            width: max-content !important;
            min-width: 100%;
            table-layout: auto !important;
        }

        #rlip-table th,
        #rlip-table td {
            white-space: nowrap !important;
            word-break: normal !important;
            overflow-wrap: normal !important;
            padding: 8px !important;
            vertical-align: top;
        }

        #rlip-table th:nth-child(2),
        #rlip-table td:nth-child(2) {
            min-width: 320px;
            max-width: 420px;
            white-space: normal !important;
        }

        #rlip-table th:nth-child(3),
        #rlip-table td:nth-child(3) {
            min-width: 220px;
            max-width: 280px;
            white-space: normal !important;
        }

        #rlip-table .wrap-text,
        #rlip-table td:nth-child(3) .wrap-text {
            white-space: normal;
        }

        .rlip-sort-link {
            border: none;
            padding: 0;
            margin: 0;
            font: inherit;
            color: inherit;
            cursor: pointer;
            text-decoration: none;
        }

        .rlip-sort-indicator {
            font-size: 10px;
            color: #6b7280;
            min-width: 10px;
            display: inline-block;
            text-align: center;
        }

        .rlip-column-toggle-panel {
            margin-bottom: 16px;
            padding: 14px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
        }

        .rlip-column-toggle-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .rlip-column-toggle-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .rlip-column-toggle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px 16px;
        }

        .rlip-column-toggle-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #374151;
            cursor: pointer;
        }

        .rlip-column-toggle-option input {
            margin: 0;
        }

        .rlip-column-toggle-option--master {
            font-weight: 600;
        }

        #rlip-table [data-column-key].is-column-hidden {
            display: none;
        }

        @media (max-width: 768px) {
            #rlip-filters-form > div {
                width: 100%;
                min-width: 0 !important;
                flex: 1 1 100%;
            }

            #rlip-filters-form > a {
                width: 100%;
                text-align: center;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .rlip-filters-summary,
            .rlip-filters-body {
                padding-left: 12px;
                padding-right: 12px;
            }

            .rlip-column-toggle-panel {
                padding: 12px;
            }

            .rlip-table-wrap {
                display: none;
            }

            .rlip-mobile-cards {
                display: grid;
            }

            .rlip-mobile-card-summary {
                align-items: flex-start;
            }

            .rlip-mobile-card-action {
                width: 100%;
            }

            .rlip-mobile-card-details {
                grid-template-columns: 1fr;
            }

            .rlip-column-toggle-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .rlip-column-toggle-option {
                padding: 8px 10px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                background: #ffffff;
            }
        }

        @media (max-width: 480px) {
            .content-header h1 {
                font-size: 20px;
            }

            .content-header p {
                font-size: 12px;
            }

            .rlip-mobile-card {
                border-radius: 10px;
            }

            .rlip-mobile-card-summary {
                padding: 12px;
            }

            .rlip-mobile-card-body-inner {
                padding: 0 12px 12px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filtersPanel = document.getElementById('rlip-filters-panel');
            const filtersForm = document.getElementById('rlip-filters-form');
            const searchInput = document.getElementById('rlip-search');
            const provinceSelect = document.getElementById('filter-province');
            const citySelect = document.getElementById('filter-city');
            const yearSelect = document.getElementById('filter-year');
            const fundSourceSelect = document.getElementById('filter-fund-source');
            const statusSelect = document.getElementById('filter-status');
            const selectAllColumnsToggle = document.getElementById('rlip-column-toggle-all');
            const columnToggles = Array.from(document.querySelectorAll('.rlip-column-toggle-checkbox'));
            const locationData = @json($provinceMunicipalities);
            const columnToggleStorageKey = 'rlip-visible-columns';
            const selectedCity = citySelect ? (citySelect.dataset.selectedCity || '') : '';
            const AUTO_SEARCH_DELAY_MS = 700;
            const AUTO_SEARCH_MIN_CHARS = 2;
            let searchTimer = null;
            let lastSubmittedSearch = searchInput ? searchInput.value.trim() : '';

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

            function submitFilters() {
                if (searchInput) {
                    lastSubmittedSearch = searchInput.value.trim();
                }
                filtersForm.requestSubmit();
            }

            function applyVisibleColumns(visibleColumns) {
                document.querySelectorAll('#rlip-table [data-column-key], .rlip-mobile-card-detail[data-column-key]').forEach(function (cell) {
                    const columnKey = cell.dataset.columnKey || '';
                    cell.classList.toggle('is-column-hidden', !visibleColumns.includes(columnKey));
                });
            }

            function syncVisibleColumns() {
                const visibleColumns = columnToggles
                    .filter(function (toggle) {
                        return toggle.checked;
                    })
                    .map(function (toggle) {
                        return toggle.dataset.columnToggle || '';
                    })
                    .filter(Boolean);

                applyVisibleColumns(visibleColumns);

                if (columnToggles.length > 0) {
                    localStorage.setItem(columnToggleStorageKey, JSON.stringify(visibleColumns));
                }

                if (selectAllColumnsToggle) {
                    const checkedCount = visibleColumns.length;
                    const totalCount = columnToggles.length;
                    selectAllColumnsToggle.checked = totalCount > 0 && checkedCount === totalCount;
                    selectAllColumnsToggle.indeterminate = checkedCount > 0 && checkedCount < totalCount;
                }
            }

            function initializeColumnToggles() {
                if (columnToggles.length === 0) {
                    return;
                }

                const storedColumnsRaw = localStorage.getItem(columnToggleStorageKey);
                let savedColumns = null;

                try {
                    savedColumns = JSON.parse(storedColumnsRaw || 'null');
                } catch (error) {
                    savedColumns = null;
                }

                const validColumns = Array.isArray(savedColumns)
                    ? savedColumns.filter(function (columnKey) {
                        return columnToggles.some(function (toggle) {
                            return toggle.dataset.columnToggle === columnKey;
                        });
                    })
                    : null;

                if (storedColumnsRaw !== null && validColumns) {
                    columnToggles.forEach(function (toggle) {
                        toggle.checked = validColumns.includes(toggle.dataset.columnToggle || '');
                    });
                }

                columnToggles.forEach(function (toggle) {
                    toggle.addEventListener('change', syncVisibleColumns);
                });

                if (selectAllColumnsToggle) {
                    selectAllColumnsToggle.addEventListener('change', function () {
                        columnToggles.forEach(function (toggle) {
                            toggle.checked = selectAllColumnsToggle.checked;
                        });

                        syncVisibleColumns();
                    });
                }

                syncVisibleColumns();
            }

            function scheduleAutoSearch() {
                if (!searchInput) {
                    return;
                }

                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    const currentSearch = searchInput.value.trim();
                    const hasMinChars = currentSearch.length >= AUTO_SEARCH_MIN_CHARS;
                    const isCleared = currentSearch.length === 0;
                    if (!hasMinChars && !isCleared) {
                        return;
                    }

                    if (currentSearch === lastSubmittedSearch) {
                        return;
                    }

                    submitFilters();
                }, AUTO_SEARCH_DELAY_MS);
            }

            if (searchInput) {
                searchInput.addEventListener('input', scheduleAutoSearch);
            }

            provinceSelect.addEventListener('change', function () {
                populateCityOptions(this.value);
                citySelect.value = '';
                submitFilters();
            });

            [yearSelect, fundSourceSelect, citySelect, statusSelect]
                .filter(Boolean)
                .forEach(function (select) {
                    select.addEventListener('change', submitFilters);
                });

            populateCityOptions(provinceSelect.value, selectedCity);
            initializeColumnToggles();
        });
    </script>
@endsection
