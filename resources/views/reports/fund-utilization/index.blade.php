@extends('layouts.dashboard')

@section('title', 'Fund Utilization Report')
@section('page-title', 'Fund Utilization Report')

@section('content')
    <div class="content-header">
        <h1>Fund Utilization Report</h1>
        <p>Manage fund utilization reports and project documents</p>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @php
        $activeFilters = array_merge([
            'search' => '',
            'program' => [],
            'fund_source' => [],
            'funding_year' => [],
            'province' => [],
            'city' => [],
        ], $filters ?? []);
        $provinceMunicipalities = $filterOptions['provinceMunicipalities'] ?? [];
        $selectedProvinceFilters = collect($activeFilters['province'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->values();
        $cityOptions = $selectedProvinceFilters->isNotEmpty()
            ? $selectedProvinceFilters->flatMap(fn ($province) => $provinceMunicipalities[$province] ?? [])
            : collect();
        $cityOptions = $cityOptions
            ->map(fn($city) => trim((string) $city))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $multiFilterKeys = ['program', 'fund_source', 'funding_year', 'province', 'city'];
    @endphp

    <form id="fund-utilization-filters" method="GET" action="{{ route('fund-utilization.index') }}" class="dashboard-card project-filter-form collapsed" style="background: #ffffff; padding: 16px 18px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
        <button type="button" class="project-filter-toggle" onclick="toggleProjectFilter(this)" aria-expanded="false" aria-controls="fund-utilization-filter-body">
            <i class="fas fa-filter" aria-hidden="true" style="font-size: 16px;"></i>
            <span>PROJECT FILTER</span>
            <span class="project-filter-chevron">
                <i class="fas fa-chevron-up"></i>
            </span>
        </button>

        <div id="fund-utilization-filter-body" class="project-filter-body">
            <div class="dashboard-filter-grid" style="display: grid; grid-template-columns: repeat(3, minmax(200px, 1fr)); gap: 12px 16px; align-items: end;">
                <div>
                    <label for="fund-utilization-search" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Search</label>
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px; pointer-events: none;"></i>
                        <input id="fund-utilization-search" type="text" name="search" value="{{ $activeFilters['search'] }}" placeholder="Search project code, title, province..." style="width: 100%; height: 34px; padding: 0 12px 0 34px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 12px; background-color: #ffffff; color: #374151; box-sizing: border-box;">
                    </div>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="fund_utilization_program" data-badge-container-id="fund_utilization_program_badges" data-dropdown-toggle-id="fund_utilization_program_dropdown_toggle" data-dropdown-menu-id="fund_utilization_program_dropdown_menu" data-empty-badge-text="No program selected.">
                    <label for="fund_utilization_program_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Program</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="fund_utilization_program_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="fund_utilization_program_dropdown_menu">
                            <div id="fund_utilization_program_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div id="fund_utilization_program_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="fund_utilization_program" name="program[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Program" aria-hidden="true">
                        @foreach(($filterOptions['programs'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['program'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="fund_utilization_fund_source" data-badge-container-id="fund_utilization_fund_source_badges" data-dropdown-toggle-id="fund_utilization_fund_source_dropdown_toggle" data-dropdown-menu-id="fund_utilization_fund_source_dropdown_menu" data-empty-badge-text="All">
                    <label for="fund_utilization_fund_source_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Fund Source</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="fund_utilization_fund_source_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="fund_utilization_fund_source_dropdown_menu">
                            <div id="fund_utilization_fund_source_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div id="fund_utilization_fund_source_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="fund_utilization_fund_source" name="fund_source[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Fund Source" aria-hidden="true">
                        @foreach(($filterOptions['fund_sources'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['fund_source'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="fund_utilization_funding_year" data-badge-container-id="fund_utilization_funding_year_badges" data-dropdown-toggle-id="fund_utilization_funding_year_dropdown_toggle" data-dropdown-menu-id="fund_utilization_funding_year_dropdown_menu" data-empty-badge-text="All">
                    <label for="fund_utilization_funding_year_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Funding Year</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="fund_utilization_funding_year_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="fund_utilization_funding_year_dropdown_menu">
                            <div id="fund_utilization_funding_year_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div id="fund_utilization_funding_year_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="fund_utilization_funding_year" name="funding_year[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Funding Year" aria-hidden="true">
                        @foreach(($filterOptions['funding_years'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['funding_year'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="fund_utilization_province" data-badge-container-id="fund_utilization_province_badges" data-dropdown-toggle-id="fund_utilization_province_dropdown_toggle" data-dropdown-menu-id="fund_utilization_province_dropdown_menu" data-empty-badge-text="All">
                    <label for="fund_utilization_province_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Province</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="fund_utilization_province_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="fund_utilization_province_dropdown_menu">
                            <div id="fund_utilization_province_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div id="fund_utilization_province_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="fund_utilization_province" name="province[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Province" aria-hidden="true">
                        @foreach(($filterOptions['provinces'] ?? []) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['province'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="fund_utilization_city" data-badge-container-id="fund_utilization_city_badges" data-dropdown-toggle-id="fund_utilization_city_dropdown_toggle" data-dropdown-menu-id="fund_utilization_city_dropdown_menu" data-empty-badge-text="All" data-empty-menu-text="Select at least one province first.">
                    <label for="fund_utilization_city_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">City/Municipality</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="fund_utilization_city_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="fund_utilization_city_dropdown_menu">
                            <div id="fund_utilization_city_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div id="fund_utilization_city_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="fund_utilization_city" name="city[]" multiple class="dashboard-stacked-filter-source" data-filter-label="City/Municipality" aria-hidden="true">
                        @foreach($cityOptions as $city)
                            <option value="{{ $city }}" @selected(in_array((string) $city, ($activeFilters['city'] ?? []), true))>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-filter-reset" style="display: flex; align-items: end; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('fund-utilization.index', ['per_page' => $perPage ?? 10]) }}" class="dashboard-filter-reset-link" style="height: 34px; min-width: 150px; border-radius: 7px; background: linear-gradient(180deg, #003a99 0%, #002c76 100%); color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 600; padding: 0 14px;">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                        Reset Filter
                    </a>
                    <button type="submit" class="dashboard-filter-apply-btn">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Apply Filter
                    </button>
                    <button type="button" class="dashboard-filter-export-btn" onclick="openExportModal('excel')">
                        <i class="fas fa-file-excel" aria-hidden="true"></i>
                        Export Report
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Reports Card -->
    <div class="report-table-card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <div class="report-table-scroll">
            <table id="fund-utilization-table" style="width: 100%; border-collapse: collapse; min-width: 980px;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px; width: 220px; max-width: 220px;">Project Details</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Location</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Funding / Status</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Validation / Progress</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    @php
                        $validationSummary = $report->validation_summary ?? [
                            'label' => 'No Upload',
                            'detail' => 'No uploaded documents yet',
                            'icon' => 'fa-minus-circle',
                            'text_color' => '#4b5563',
                            'background_color' => '#f3f4f6',
                            'border_color' => '#d1d5db',
                        ];
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb; transition: all 0.3s ease;">
                        <td style="padding: 12px; color: #111827; font-size: 14px; width: 220px; max-width: 220px;">
                            <div style="display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; background: #e0e7ff; color: #1e3a8a; font-size: 10px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 8px;">
                                {{ $report->project_code }}
                            </div>
                            <div style="max-width: 220px; white-space: normal; word-break: break-word; font-weight: 700; line-height: 1.4;">{{ $report->project_title }}</div>
                            <div style="margin-top: 8px; font-size: 11px; color: #6b7280; line-height: 1.45;">
                                <div><strong>Fund Source:</strong> {{ $report->fund_source ?: '-' }}</div>
                                <div><strong>Funding Year:</strong> {{ $report->funding_year ?: '-' }}</div>
                            </div>
                        </td>
                        <td style="padding: 12px; color: #111827; font-size: 14px;">
                            @php
                                $barangayList = collect(preg_split('/[\\r\\n,]+/', $report->barangay ?? ''))
                                    ->map(fn($item) => trim($item))
                                    ->filter();
                            @endphp
                            <div style="font-size: 12px; line-height: 1.4;">
                                <strong>Province:</strong> {{ $report->province ?: '-' }}<br>
                                <strong>City/Mun:</strong> {{ $report->city_municipality ?: ($report->implementing_unit ?: '-') }}<br>
                                <strong>Barangay:</strong>
                                @if($barangayList->isEmpty())
                                    <span> Not specified</span><br>
                                @else
                                    <ul style="margin: 4px 0 0 16px; padding: 0;">
                                        @foreach($barangayList as $barangay)
                                            <li style="margin: 0; list-style: disc;">{{ strcasecmp(trim((string) $barangay), 'Unknown') === 0 ? '-' : $barangay }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <strong>Implementing Unit:</strong><br>
                                <span>{{ $report->implementing_unit ?: '-' }}</span>
                            </div>
                        </td>
                        <td style="padding: 12px; color: #111827; font-size: 13px;">
                            <div style="display: flex; flex-direction: column; gap: 8px; line-height: 1.4;">
                                <div>
                                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">Allocation</div>
                                    <div style="font-weight: 700;">{{ $report->allocation ? 'PHP ' . number_format($report->allocation, 2) : '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">Contract Amount</div>
                                    <div style="font-weight: 700;">{{ $report->contract_amount ? 'PHP ' . number_format($report->contract_amount, 2) : '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">Project Status</div>
                                    <div style="font-weight: 700;">{{ $report->project_status ?: '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                                <span style="display: inline-flex; flex-direction: column; align-items: center; gap: 4px; min-width: 150px; max-width: 180px; padding: 8px 12px; border-radius: 12px; border: 1px solid {{ $validationSummary['border_color'] ?? '#d1d5db' }}; background-color: {{ $validationSummary['background_color'] ?? '#f3f4f6' }}; color: {{ $validationSummary['text_color'] ?? '#374151' }}; font-size: 11px; line-height: 1.25; font-weight: 700;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                                        <i class="fas {{ $validationSummary['icon'] ?? 'fa-minus-circle' }}" aria-hidden="true"></i>
                                        <span>{{ $validationSummary['label'] ?? 'No Upload' }}</span>
                                    </span>
                                    <span style="font-size: 10px; font-weight: 600; opacity: 0.9; text-align: center; white-space: normal;">
                                        {{ $validationSummary['detail'] ?? 'No uploaded documents yet' }}
                                    </span>
                                </span>
                                <div style="display: grid; grid-template-columns: repeat(2, minmax(54px, 1fr)); gap: 6px; width: 100%; max-width: 170px;">
                                    <span style="padding: 6px 8px; border-radius: 8px; background: #f8fafc; border: 1px solid #e5e7eb; font-size: 11px; font-weight: 700; color: {{ $report->quarter_q1_percentage == 100 ? '#10b981' : ($report->quarter_q1_percentage > 70 ? '#f59e0b' : '#ef4444') }};">Q1: {{ $report->quarter_q1_percentage }}%</span>
                                    <span style="padding: 6px 8px; border-radius: 8px; background: #f8fafc; border: 1px solid #e5e7eb; font-size: 11px; font-weight: 700; color: {{ $report->quarter_q2_percentage == 100 ? '#10b981' : ($report->quarter_q2_percentage > 70 ? '#f59e0b' : '#ef4444') }};">Q2: {{ $report->quarter_q2_percentage }}%</span>
                                    <span style="padding: 6px 8px; border-radius: 8px; background: #f8fafc; border: 1px solid #e5e7eb; font-size: 11px; font-weight: 700; color: {{ $report->quarter_q3_percentage == 100 ? '#10b981' : ($report->quarter_q3_percentage > 70 ? '#f59e0b' : '#ef4444') }};">Q3: {{ $report->quarter_q3_percentage }}%</span>
                                    <span style="padding: 6px 8px; border-radius: 8px; background: #f8fafc; border: 1px solid #e5e7eb; font-size: 11px; font-weight: 700; color: {{ $report->quarter_q4_percentage == 100 ? '#10b981' : ($report->quarter_q4_percentage > 70 ? '#f59e0b' : '#ef4444') }};">Q4: {{ $report->quarter_q4_percentage }}%</span>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('fund-utilization.show', $report->project_code) }}" style="display: inline-block; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease;">
                                <i class="fas fa-eye" style="margin-right: 4px;"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #6b7280;">
                            <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                            No reports found. Create one to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>

        @if($reports->count() > 0)
            <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="font-size: 12px; color: #6b7280;">
                        Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }} &middot;
                        Showing {{ $reports->firstItem() ?? 0 }}-{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }}
                    </div>
                    <form method="GET" action="{{ route('fund-utilization.index') }}" style="display: inline-flex; align-items: center;">
                        <input type="hidden" name="search" value="{{ $activeFilters['search'] ?? '' }}">
                        @foreach ($multiFilterKeys as $filterKey)
                            @foreach (($activeFilters[$filterKey] ?? []) as $selectedValue)
                                <input type="hidden" name="{{ $filterKey }}[]" value="{{ $selectedValue }}">
                            @endforeach
                        @endforeach
                        <select id="per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                            @foreach([10, 15, 25, 50] as $option)
                                <option value="{{ $option }}" {{ (int) ($perPage ?? 10) === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                    @if($reports->onFirstPage())
                        <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-chevron-left"></i> Back
                        </span>
                    @else
                        <a href="{{ $reports->previousPageUrl() }}" style="padding: 8px 12px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                            <i class="fas fa-chevron-left"></i> Back
                        </a>
                    @endif

                    @if($reports->hasMorePages())
                        <a href="{{ $reports->nextPageUrl() }}" style="padding: 8px 12px; background-color: #002C76; color: white; border: 1px solid #002C76; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
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

    <!-- Export Modal -->
    <div id="exportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 400px; width: 90%;">
            <h3 style="margin: 0 0 20px 0; color: #111827; font-size: 18px; font-weight: 600;">Select Quarter for Export</h3>
            <form id="exportForm" method="GET" action="{{ route('fund-utilization.export') }}">
                <div style="margin-bottom: 20px;">
                    <label for="quarter" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500;">Quarter:</label>
                    <select id="quarter" name="quarter" required style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb;">
                        <option value="">Select Quarter</option>
                        <option value="Q1">Q1 (January - March)</option>
                        <option value="Q2">Q2 (April - June)</option>
                        <option value="Q3">Q3 (July - September)</option>
                        <option value="Q4">Q4 (October - December)</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeExportModal()" style="padding: 10px 20px; background-color: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;"><i class="fas fa-times" style="margin-right: 8px;"></i>Cancel</button>
                    <button type="submit" id="exportBtn" style="padding: 10px 20px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">Export</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedFormat = '';

        function openExportModal(format) {
            selectedFormat = format;
            document.getElementById('exportModal').style.display = 'flex';
        }

        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
            selectedFormat = '';
        }

        const PROJECT_FILTER_STATE_KEY = 'fund-utilization-filter-collapsed';
        const FUND_UTILIZATION_LOCATION_MAP = @json($provinceMunicipalities ?? []);

        function readProjectFilterCollapsedState() {
            try {
                const value = window.localStorage.getItem(PROJECT_FILTER_STATE_KEY);
                return value === null ? true : value === '1';
            } catch (error) {
                return true;
            }
        }

        function writeProjectFilterCollapsedState(isCollapsed) {
            try {
                window.localStorage.setItem(PROJECT_FILTER_STATE_KEY, isCollapsed ? '1' : '0');
            } catch (error) {
            }
        }

        function setProjectFilterBodyHeight(form) {
            if (!form) {
                return;
            }

            const body = form.querySelector('.project-filter-body');
            if (!body) {
                return;
            }

            body.style.maxHeight = form.classList.contains('collapsed') ? '0px' : `${body.scrollHeight}px`;
        }

        function toggleProjectFilter(button) {
            const form = button.closest('.project-filter-form');
            if (!form) {
                return;
            }

            const body = form.querySelector('.project-filter-body');
            if (!body) {
                return;
            }

            form.querySelectorAll('[data-stacked-filter]').forEach((stackedFilter) => {
                if (typeof stackedFilter.__closeDropdown === 'function') {
                    stackedFilter.__closeDropdown();
                }
            });

            const isCollapsed = form.classList.contains('collapsed');
            if (isCollapsed) {
                form.classList.remove('collapsed');
                requestAnimationFrame(() => {
                    body.style.maxHeight = `${body.scrollHeight}px`;
                });
            } else {
                body.style.maxHeight = `${body.scrollHeight}px`;
                requestAnimationFrame(() => {
                    form.classList.add('collapsed');
                    body.style.maxHeight = '0px';
                });
            }

            const nextCollapsed = !isCollapsed;
            button.setAttribute('aria-expanded', nextCollapsed ? 'false' : 'true');
            writeProjectFilterCollapsedState(nextCollapsed);
        }

        function initializeStackedFilters() {
            document.querySelectorAll('[data-stacked-filter]').forEach((stackedFilter) => {
                if (stackedFilter.dataset.stackedFilterInitialized === '1') {
                    return;
                }

                const sourceSelect = document.getElementById(stackedFilter.dataset.sourceSelectId || '');
                const badgeContainer = document.getElementById(stackedFilter.dataset.badgeContainerId || '');
                const dropdownToggle = document.getElementById(stackedFilter.dataset.dropdownToggleId || '');
                const dropdownMenu = document.getElementById(stackedFilter.dataset.dropdownMenuId || '');

                if (!sourceSelect || !badgeContainer || !dropdownToggle || !dropdownMenu) {
                    return;
                }

                const filterLabel = String(sourceSelect.dataset.filterLabel || 'Filter').trim();
                const defaultEmptyBadgeText = stackedFilter.dataset.emptyBadgeText || `No ${filterLabel.toLowerCase()} selected.`;
                const defaultEmptyMenuText = stackedFilter.dataset.emptyMenuText || `No ${filterLabel.toLowerCase()} options available.`;
                const searchState = { value: '' };

                if (dropdownMenu.dataset.overlayAttached !== '1') {
                    document.body.appendChild(dropdownMenu);
                    dropdownMenu.dataset.overlayAttached = '1';
                }

                const getSelectOptions = () => Array.from(sourceSelect.options || []);
                const getOptionLabel = (optionElement) => String(optionElement?.textContent || '').replace(/\s+/g, ' ').trim();
                const getEmptyBadgeText = () => stackedFilter.dataset.emptyBadgeText || defaultEmptyBadgeText;
                const getEmptyMenuText = () => stackedFilter.dataset.emptyMenuText || defaultEmptyMenuText;
                const ensureSelectionOrder = () => {
                    if (!Array.isArray(sourceSelect.__selectionOrder)) {
                        sourceSelect.__selectionOrder = getSelectOptions()
                            .filter((optionElement) => optionElement.selected && optionElement.value.trim() !== '')
                            .map((optionElement) => optionElement.value);
                    }
                };
                const updateSelectionOrderForValue = (value, isSelected) => {
                    ensureSelectionOrder();
                    sourceSelect.__selectionOrder = sourceSelect.__selectionOrder.filter((item) => item !== value);
                    if (isSelected) {
                        sourceSelect.__selectionOrder.push(value);
                    }
                };
                const syncSelectionOrderFromSelect = () => {
                    ensureSelectionOrder();
                    const selectedValues = new Set(
                        getSelectOptions()
                            .filter((optionElement) => optionElement.selected && optionElement.value.trim() !== '')
                            .map((optionElement) => optionElement.value)
                    );

                    sourceSelect.__selectionOrder = sourceSelect.__selectionOrder.filter((value) => selectedValues.has(value));
                    getSelectOptions().forEach((optionElement) => {
                        if (
                            optionElement.selected
                            && optionElement.value.trim() !== ''
                            && !sourceSelect.__selectionOrder.includes(optionElement.value)
                        ) {
                            sourceSelect.__selectionOrder.push(optionElement.value);
                        }
                    });
                };
                const getSelectedOptionsInOrder = () => {
                    syncSelectionOrderFromSelect();
                    const selectedOptions = getSelectOptions()
                        .filter((optionElement) => optionElement.selected && optionElement.value.trim() !== '');
                    const optionByValue = new Map(selectedOptions.map((optionElement) => [optionElement.value, optionElement]));
                    const orderedOptions = sourceSelect.__selectionOrder
                        .map((value) => optionByValue.get(value))
                        .filter(Boolean);

                    selectedOptions.forEach((optionElement) => {
                        if (!orderedOptions.includes(optionElement)) {
                            orderedOptions.push(optionElement);
                        }
                    });

                    return orderedOptions;
                };

                const updateFilterBodyHeight = () => {
                    const parentForm = stackedFilter.closest('.project-filter-form');
                    if (!parentForm || parentForm.classList.contains('collapsed')) {
                        return;
                    }

                    requestAnimationFrame(() => setProjectFilterBodyHeight(parentForm));
                };

                const positionDropdownMenu = () => {
                    if (!dropdownMenu.classList.contains('is-open')) {
                        return;
                    }

                    const viewportMargin = 8;
                    const menuGap = 4;
                    const rect = dropdownToggle.getBoundingClientRect();
                    const availableBelow = Math.max(0, window.innerHeight - rect.bottom - viewportMargin);
                    const availableAbove = Math.max(0, rect.top - viewportMargin);
                    const preferredHeight = Math.min(dropdownMenu.scrollHeight, 220);
                    const shouldOpenUpward = availableBelow < Math.min(preferredHeight, 160) && availableAbove > availableBelow;
                    const availableHeight = Math.max(96, Math.min(Math.max(96, window.innerHeight - (viewportMargin * 2)), (shouldOpenUpward ? availableAbove : availableBelow) - menuGap));
                    const renderedHeight = Math.min(dropdownMenu.scrollHeight, availableHeight);
                    const renderedWidth = Math.min(rect.width, window.innerWidth - (viewportMargin * 2));
                    const top = shouldOpenUpward
                        ? Math.max(viewportMargin, rect.top - renderedHeight - menuGap)
                        : Math.min(window.innerHeight - viewportMargin - renderedHeight, rect.bottom + menuGap);
                    const left = Math.min(Math.max(viewportMargin, rect.left), window.innerWidth - viewportMargin - renderedWidth);

                    dropdownMenu.style.left = `${left}px`;
                    dropdownMenu.style.top = `${Math.max(viewportMargin, top)}px`;
                    dropdownMenu.style.width = `${renderedWidth}px`;
                    dropdownMenu.style.maxHeight = `${availableHeight}px`;
                };

                const closeDropdown = () => {
                    dropdownMenu.classList.remove('is-open');
                    dropdownToggle.classList.remove('is-open');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                    dropdownMenu.style.left = '';
                    dropdownMenu.style.top = '';
                    dropdownMenu.style.width = '';
                    dropdownMenu.style.maxHeight = '';
                    searchState.value = '';
                };

                const openDropdown = () => {
                    document.querySelectorAll('[data-stacked-filter]').forEach((otherFilter) => {
                        if (otherFilter !== stackedFilter && typeof otherFilter.__closeDropdown === 'function') {
                            otherFilter.__closeDropdown();
                        }
                    });

                    renderDropdownOptions();
                    dropdownMenu.classList.add('is-open');
                    dropdownToggle.classList.add('is-open');
                    dropdownToggle.setAttribute('aria-expanded', 'true');
                    requestAnimationFrame(positionDropdownMenu);
                };

                const renderBadges = () => {
                    const selected = getSelectedOptionsInOrder();
                    badgeContainer.innerHTML = '';

                    if (!selected.length) {
                        const summary = document.createElement('span');
                        summary.className = 'dashboard-filter-badge-empty';
                        summary.textContent = getEmptyBadgeText();
                        badgeContainer.appendChild(summary);
                    } else {
                        const summary = document.createElement('span');
                        summary.className = 'dashboard-filter-summary-text';
                        summary.textContent = selected.map(getOptionLabel).join(', ');
                        badgeContainer.appendChild(summary);
                    }

                    updateFilterBodyHeight();
                    requestAnimationFrame(positionDropdownMenu);
                };

                const renderDropdownOptions = () => {
                    const options = getSelectOptions().filter((optionElement) => optionElement.value.trim() !== '');
                    const normalizedSearch = searchState.value.trim().toLowerCase();
                    const filteredOptions = normalizedSearch === ''
                        ? options
                        : options.filter((optionElement) => getOptionLabel(optionElement).toLowerCase().includes(normalizedSearch));
                    dropdownMenu.innerHTML = '';

                    if (options.length > 0) {
                        const searchWrap = document.createElement('div');
                        searchWrap.className = 'dashboard-stacked-filter-search';

                        const searchField = document.createElement('div');
                        searchField.className = 'dashboard-stacked-filter-search-field';

                        const searchIcon = document.createElement('i');
                        searchIcon.className = 'fas fa-search';
                        searchIcon.setAttribute('aria-hidden', 'true');

                        const searchInput = document.createElement('input');
                        searchInput.type = 'search';
                        searchInput.className = 'dashboard-stacked-filter-search-input';
                        searchInput.placeholder = `Search ${filterLabel.toLowerCase()}`;
                        searchInput.value = searchState.value;
                        searchInput.autocomplete = 'off';
                        searchInput.addEventListener('click', (event) => event.stopPropagation());
                        searchInput.addEventListener('keydown', (event) => {
                            if (event.key === 'Escape') {
                                event.preventDefault();
                                event.stopPropagation();
                                closeDropdown();
                                dropdownToggle.focus();
                            }
                        });
                        searchInput.addEventListener('input', (event) => {
                            searchState.value = event.target.value || '';
                            renderDropdownOptions();
                            requestAnimationFrame(positionDropdownMenu);
                        });

                        searchField.appendChild(searchIcon);
                        searchField.appendChild(searchInput);
                        searchWrap.appendChild(searchField);
                        dropdownMenu.appendChild(searchWrap);
                    }

                    if (!filteredOptions.length) {
                        const emptyMenuItem = document.createElement('div');
                        emptyMenuItem.className = 'dashboard-stacked-filter-menu-empty';
                        emptyMenuItem.textContent = getEmptyMenuText();
                        dropdownMenu.appendChild(emptyMenuItem);
                        return;
                    }

                    filteredOptions.forEach((optionElement) => {
                        const index = getSelectOptions().indexOf(optionElement);
                        const optionButton = document.createElement('button');
                        optionButton.type = 'button';
                        optionButton.className = 'dashboard-stacked-filter-option';
                        optionButton.dataset.optionIndex = String(index);
                        optionButton.setAttribute('role', 'option');
                        optionButton.setAttribute('aria-selected', optionElement.selected ? 'true' : 'false');

                        if (optionElement.selected) {
                            optionButton.classList.add('is-selected');
                        }

                        const optionLabel = document.createElement('span');
                        optionLabel.className = 'dashboard-stacked-filter-option-label';
                        optionLabel.textContent = getOptionLabel(optionElement);

                        const optionCheck = document.createElement('span');
                        optionCheck.className = 'dashboard-stacked-filter-option-check';
                        optionCheck.textContent = '✓';

                        optionButton.appendChild(optionLabel);
                        optionButton.appendChild(optionCheck);
                        dropdownMenu.appendChild(optionButton);
                    });
                };

                const refreshDropdown = () => {
                    renderBadges();
                    renderDropdownOptions();
                };

                const notifyChange = () => {
                    sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
                };

                dropdownToggle.addEventListener('click', (event) => {
                    if (event.target.closest('.dashboard-filter-badge-remove')) {
                        return;
                    }

                    dropdownMenu.classList.contains('is-open') ? closeDropdown() : openDropdown();
                });

                dropdownToggle.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        dropdownMenu.classList.contains('is-open') ? closeDropdown() : openDropdown();
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        closeDropdown();
                    }
                });

                dropdownMenu.addEventListener('click', (event) => {
                    const optionButton = event.target.closest('.dashboard-stacked-filter-option');
                    if (!optionButton) {
                        return;
                    }

                    const optionIndex = Number(optionButton.dataset.optionIndex);
                    const matchingOption = sourceSelect.options[optionIndex];
                    if (!matchingOption) {
                        return;
                    }

                    matchingOption.selected = !matchingOption.selected;
                    refreshDropdown();
                    notifyChange();
                });

                document.addEventListener('click', (event) => {
                    if (!stackedFilter.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        closeDropdown();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeDropdown();
                    }
                });

                window.addEventListener('resize', () => requestAnimationFrame(positionDropdownMenu));
                document.addEventListener('scroll', () => requestAnimationFrame(positionDropdownMenu), true);
                sourceSelect.addEventListener('change', () => {
                    syncSelectionOrderFromSelect();
                    refreshDropdown();
                });

                refreshDropdown();
                stackedFilter.__closeDropdown = closeDropdown;
                stackedFilter.__refreshFilterUi = refreshDropdown;
                stackedFilter.dataset.stackedFilterInitialized = '1';
            });
        }

        function replaceSelectOptions(selectElement, values, selectedValues) {
            const selectedValueSet = new Set(selectedValues);
            selectElement.innerHTML = '';

            values.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                option.selected = selectedValueSet.has(value);
                selectElement.appendChild(option);
            });

            if (Array.isArray(selectElement.__selectionOrder)) {
                selectElement.__selectionOrder = selectElement.__selectionOrder.filter((value) => values.includes(value) && selectedValueSet.has(value));
                values.forEach((value) => {
                    if (selectedValueSet.has(value) && !selectElement.__selectionOrder.includes(value)) {
                        selectElement.__selectionOrder.push(value);
                    }
                });
            }
        }

        function setStackedFilterEmptyMenuText(selectId, message) {
            const stackedFilter = document.querySelector(`[data-stacked-filter][data-source-select-id="${selectId}"]`);
            if (stackedFilter) {
                stackedFilter.dataset.emptyMenuText = message;
            }
        }

        function rebuildDependentCityOptions() {
            const provinceSelect = document.getElementById('fund_utilization_province');
            const citySelect = document.getElementById('fund_utilization_city');
            const cityStackedFilter = citySelect ? citySelect.closest('[data-stacked-filter]') : null;

            if (!provinceSelect || !citySelect) {
                return;
            }

            const selectedProvinces = Array.isArray(provinceSelect.__selectionOrder)
                ? provinceSelect.__selectionOrder.filter((value) => Array.from(provinceSelect.selectedOptions || []).some((option) => option.value.trim() === value))
                : Array.from(provinceSelect.selectedOptions || []).map((option) => option.value.trim()).filter(Boolean);
            const currentSelectedCities = Array.from(citySelect.selectedOptions || [])
                .map((option) => option.value.trim())
                .filter(Boolean);
            const nextCities = [];
            const seenCities = new Set();

            selectedProvinces.forEach((province) => {
                (FUND_UTILIZATION_LOCATION_MAP[province] || []).forEach((city) => {
                    const normalizedCity = String(city || '').trim();
                    if (normalizedCity === '') {
                        return;
                    }

                    const dedupeKey = normalizedCity.toLowerCase();
                    if (!seenCities.has(dedupeKey)) {
                        seenCities.add(dedupeKey);
                        nextCities.push(normalizedCity);
                    }
                });
            });

            replaceSelectOptions(
                citySelect,
                nextCities,
                currentSelectedCities.filter((value) => nextCities.includes(value))
            );

            setStackedFilterEmptyMenuText(
                'fund_utilization_city',
                selectedProvinces.length ? 'No city/municipality options available.' : 'Select at least one province first.'
            );

            if (cityStackedFilter && typeof cityStackedFilter.__refreshFilterUi === 'function') {
                cityStackedFilter.__refreshFilterUi();
            }
        }

        document.getElementById('exportForm').addEventListener('submit', function (event) {
            event.preventDefault();
            const quarter = document.getElementById('quarter').value;

            if (!quarter) {
                alert('Please select a quarter.');
                return;
            }

            const baseUrl = '{{ route("fund-utilization.export") }}';
            const url = new URL(baseUrl);
            const currentUrl = new URL(window.location.href);

            url.search = '';
            url.searchParams.set('format', selectedFormat);
            url.searchParams.set('quarter', quarter);

            for (const [key, value] of currentUrl.searchParams.entries()) {
                if (key !== 'format' && key !== 'quarter') {
                    url.searchParams.append(key, value);
                }
            }

            window.location.href = url.toString();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initializeStackedFilters();

            const forms = document.querySelectorAll('.project-filter-form');
            forms.forEach((form) => {
                const collapsed = readProjectFilterCollapsedState();
                const toggleButton = form.querySelector('.project-filter-toggle');
                form.classList.toggle('collapsed', collapsed);
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                }
                setProjectFilterBodyHeight(form);
            });

            const provinceSelect = document.getElementById('fund_utilization_province');
            if (provinceSelect) {
                provinceSelect.addEventListener('change', rebuildDependentCityOptions);
            }

            rebuildDependentCityOptions();

            window.addEventListener('resize', () => {
                forms.forEach((form) => {
                    if (!form.classList.contains('collapsed')) {
                        setProjectFilterBodyHeight(form);
                    }
                });
            });
        });
    </script>

    <style>
        .project-filter-form {
            background: #ffffff;
            padding: 16px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .project-filter-toggle {
            width: 100%;
            border: none;
            background: transparent;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 0;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .project-filter-toggle > i,
        .project-filter-toggle > span:first-of-type {
            flex: 0 0 auto;
        }

        .project-filter-chevron {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .project-filter-body {
            overflow: visible;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.25s ease, opacity 0.2s ease, transform 0.2s ease;
            max-height: none;
        }

        .project-filter-form.collapsed .project-filter-body {
            max-height: 0;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
        }

        .project-filter-form.collapsed .project-filter-chevron {
            transform: rotate(180deg);
        }

        .dashboard-stacked-filter-source {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .dashboard-stacked-filter-dropdown {
            position: relative;
        }

        .dashboard-stacked-filter-toggle {
            min-height: 34px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #ffffff;
            padding: 6px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            cursor: pointer;
            box-sizing: border-box;
        }

        .dashboard-stacked-filter-toggle.is-open {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .dashboard-filter-badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 20px;
            flex: 1 1 auto;
        }

        .dashboard-filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            background: #e8eefc;
            color: #1e3a8a;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 7px;
            line-height: 1.2;
            max-width: 100%;
        }

        .dashboard-filter-badge-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        .dashboard-filter-badge-remove {
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
            font-size: 10px;
            padding: 0;
            line-height: 1;
        }

        .dashboard-filter-badge-empty {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.2;
        }

        .dashboard-filter-summary-text {
            color: #111827;
            font-size: 12px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dashboard-stacked-filter-chevron {
            color: #6b7280;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .dashboard-stacked-filter-menu {
            position: fixed;
            left: 0;
            top: 0;
            display: none;
            width: auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            padding: 4px;
            max-height: 220px;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
            z-index: 1250;
        }

        .dashboard-stacked-filter-menu.is-open {
            display: block;
        }

        .dashboard-stacked-filter-search {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #ffffff;
            padding: 2px 2px 6px;
        }

        .dashboard-stacked-filter-search-field {
            position: relative;
        }

        .dashboard-stacked-filter-search-field i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 12px;
            pointer-events: none;
        }

        .dashboard-stacked-filter-search-input {
            width: 100%;
            height: 32px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0 10px 0 30px;
            font-size: 12px;
            color: #111827;
            background: #ffffff;
            box-sizing: border-box;
        }

        .dashboard-stacked-filter-search-input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.18);
        }

        .dashboard-stacked-filter-option {
            width: 100%;
            border: none;
            background: transparent;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-radius: 6px;
            padding: 7px 8px;
            cursor: pointer;
            font-size: 12px;
            text-align: left;
        }

        .dashboard-stacked-filter-option-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dashboard-stacked-filter-option:hover,
        .dashboard-stacked-filter-option:focus-visible {
            background: #f3f4f6;
            outline: none;
        }

        .dashboard-stacked-filter-option.is-selected {
            background: #e8eefc;
            color: #1e3a8a;
            font-weight: 700;
        }

        .dashboard-stacked-filter-option-check {
            opacity: 0;
            font-size: 11px;
        }

        .dashboard-stacked-filter-option.is-selected .dashboard-stacked-filter-option-check {
            opacity: 1;
        }

        .dashboard-stacked-filter-menu-empty {
            color: #6b7280;
            font-size: 12px;
            padding: 6px 8px;
        }

        .dashboard-filter-reset {
            grid-column: 1 / -1;
            display: flex;
            align-items: end;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dashboard-filter-reset-link,
        .dashboard-filter-apply-btn,
        .dashboard-filter-export-btn {
            height: 34px;
            min-width: 150px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 0 14px;
        }

        .dashboard-filter-reset-link {
            background: linear-gradient(180deg, #003a99 0%, #002c76 100%);
            color: #ffffff;
            text-decoration: none;
        }

        .dashboard-filter-apply-btn,
        .dashboard-filter-export-btn {
            border: none;
            cursor: pointer;
        }

        .dashboard-filter-apply-btn {
            background: #047857;
            color: #ffffff;
        }

        .dashboard-filter-export-btn {
            background: #166534;
            color: #ffffff;
        }

        #fund-utilization-table tbody tr:hover {
            background-color: #eef4ff !important;
        }

        .report-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table td,
        table th {
            vertical-align: top;
        }

        input[type="text"]:focus,
        select:focus,
        .dashboard-stacked-filter-toggle:focus-visible {
            outline: none;
            border-color: #002c76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.12);
            background-color: white;
        }

        #fund-utilization-table tbody td:last-child a:hover {
            background-color: #001f59 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        @media (max-width: 1100px) {
            .dashboard-filter-grid {
                grid-template-columns: repeat(2, minmax(200px, 1fr)) !important;
            }
        }

        @media (max-width: 768px) {
            .report-table-card {
                padding: 16px !important;
            }

            .dashboard-filter-grid {
                grid-template-columns: 1fr !important;
            }

            .dashboard-filter-reset {
                justify-content: stretch;
            }

            .dashboard-filter-reset-link,
            .dashboard-filter-apply-btn,
            .dashboard-filter-export-btn {
                width: 100%;
            }
        }
    </style>
@endsection
