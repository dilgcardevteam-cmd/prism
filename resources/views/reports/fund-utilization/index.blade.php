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
        $batchUploadOpen = request()->boolean('batch_upload');
        $activeFilters = array_merge([
            'search' => '',
            'program' => [],
            'funding_year' => [],
            'province' => [],
            'city' => [],
            'barangay' => [],
        ], $filters ?? []);
        $provinceMunicipalities = $filterOptions['provinceMunicipalities'] ?? [];
        $cityBarangayMap = $filterOptions['cityBarangayMap'] ?? [];
        $selectedProvinceFilters = collect($activeFilters['province'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->values();
        $cityOptions = $selectedProvinceFilters->isNotEmpty()
            ? $selectedProvinceFilters->flatMap(fn ($province) => $provinceMunicipalities[$province] ?? [])
            : collect();
        $cityOptions = $cityOptions
            ->concat(collect($activeFilters['city'] ?? []))
            ->map(fn($city) => trim((string) $city))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $selectedCityFilters = collect($activeFilters['city'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->values();
        $barangayOptions = $selectedCityFilters->isNotEmpty()
            ? $selectedCityFilters->flatMap(fn ($city) => $cityBarangayMap[$city] ?? [])
            : collect();
        $barangayOptions = $barangayOptions
            ->concat(collect($activeFilters['barangay'] ?? []))
            ->map(fn($barangay) => trim((string) $barangay))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $multiFilterKeys = ['program', 'funding_year', 'province', 'city', 'barangay'];
        $batchUploadProjects = collect($batchUploadProjects ?? [])
            ->map(function ($report) {
                return [
                    'project_code' => trim((string) ($report->project_code ?? '')),
                    'project_title' => trim((string) ($report->project_title ?? '')),
                    'province' => trim((string) ($report->province ?? '')),
                    'city_municipality' => trim((string) ($report->city_municipality ?? ($report->implementing_unit ?? ''))),
                    'barangay' => trim((string) ($report->barangay ?? '')),
                    'funding_year' => trim((string) ($report->funding_year ?? '')),
                    'open_url' => route('fund-utilization.show', $report->project_code),
                ];
            })
            ->filter(fn ($project) => $project['project_code'] !== '')
            ->values();
        $defaultFundUtilizationRouteParams = array_filter([
            'per_page' => $perPage ?? 10,
            'batch_upload' => $batchUploadOpen ? 1 : null,
        ], fn ($value) => $value !== null && $value !== '');
        $canBatchUploadFundUtilization = Auth::check() && in_array(Auth::user()->normalizedRole(), [
            \App\Models\User::ROLE_LGU,
            \App\Models\User::ROLE_PROVINCIAL,
        ], true);
    @endphp

    @if($canBatchUploadFundUtilization)
        <div style="display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
            <button type="button" class="dashboard-filter-export-btn" onclick="openBatchUploadModal()" style="height: 38px; min-width: 210px; border-radius: 8px; background: linear-gradient(180deg, #003a99 0%, #002C76 100%); box-shadow: 0 12px 24px rgba(0, 44, 118, 0.18);">
                <i class="fas fa-layer-group" aria-hidden="true"></i>
                Batch Upload FUR
            </button>
        </div>
    @endif

    <form id="fund-utilization-filters" method="GET" action="{{ route('fund-utilization.index') }}" class="dashboard-card project-filter-form" style="background: #ffffff; padding: 16px 18px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
        @if($batchUploadOpen)
            <input type="hidden" name="batch_upload" value="1">
        @endif
        <button type="button" class="project-filter-toggle" onclick="toggleProjectFilter(this)" aria-expanded="true" aria-controls="fund-utilization-filter-body">
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

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="fund_utilization_barangay" data-badge-container-id="fund_utilization_barangay_badges" data-dropdown-toggle-id="fund_utilization_barangay_dropdown_toggle" data-dropdown-menu-id="fund_utilization_barangay_dropdown_menu" data-empty-badge-text="All" data-empty-menu-text="Select at least one city/municipality first.">
                    <label for="fund_utilization_barangay_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Barangay</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="fund_utilization_barangay_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="fund_utilization_barangay_dropdown_menu">
                            <div id="fund_utilization_barangay_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div id="fund_utilization_barangay_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="fund_utilization_barangay" name="barangay[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Barangay" aria-hidden="true">
                        @foreach($barangayOptions as $barangay)
                            <option value="{{ $barangay }}" @selected(in_array((string) $barangay, ($activeFilters['barangay'] ?? []), true))>{{ $barangay }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-filter-reset" style="display: flex; align-items: end; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('fund-utilization.index', $defaultFundUtilizationRouteParams) }}" class="dashboard-filter-reset-link" style="height: 34px; min-width: 150px; border-radius: 7px; background: linear-gradient(180deg, #003a99 0%, #002c76 100%); color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 600; padding: 0 14px;">
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
            <table id="fund-utilization-table" style="width: 100%; border-collapse: collapse; min-width: 1600px;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px; width: 220px; max-width: 220px;">Project Details</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Location</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Funding / Status</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Validation / Progress</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Approval Status</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Date Submitted</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Validation Level</th>
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
                        $validationListing = $report->validation_listing ?? [
                            'approval_status_label' => 'Awaiting Upload',
                            'approval_status_text_color' => '#4b5563',
                            'approval_status_background_color' => '#f3f4f6',
                            'approval_status_border_color' => '#d1d5db',
                            'date_submitted_label' => '—',
                            'validation_level_label' => '—',
                            'validation_level_text_color' => '#4b5563',
                            'validation_level_background_color' => '#f3f4f6',
                            'validation_level_border_color' => '#d1d5db',
                            'date_validated_label' => '—',
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
                            <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 6px; max-width: 220px;">
                                <span style="display: inline-block; max-width: 220px; padding: 4px 10px; border-radius: 999px; border: 1px solid {{ $validationListing['approval_status_border_color'] ?? '#d1d5db' }}; background-color: {{ $validationListing['approval_status_background_color'] ?? '#f3f4f6' }}; color: {{ $validationListing['approval_status_text_color'] ?? '#374151' }}; font-size: 11px; font-weight: 700; white-space: normal; line-height: 1.25; text-align: center;">
                                    {{ $validationListing['approval_status_label'] ?? 'Awaiting Upload' }}
                                </span>
                                <span style="font-size: 11px; color: #111827; line-height: 1.2; white-space: nowrap;">
                                    {{ $validationListing['date_validated_label'] ?? '—' }}
                                </span>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">
                            {{ $validationListing['date_submitted_label'] ?? '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="display: inline-block; max-width: 220px; padding: 4px 10px; border-radius: 999px; border: 1px solid {{ $validationListing['validation_level_border_color'] ?? '#d1d5db' }}; background-color: {{ $validationListing['validation_level_background_color'] ?? '#f3f4f6' }}; color: {{ $validationListing['validation_level_text_color'] ?? '#374151' }}; font-size: 11px; font-weight: 700; white-space: normal; line-height: 1.25; text-align: center;">
                                {{ $validationListing['validation_level_label'] ?? '—' }}
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('fund-utilization.show', $report->project_code) }}" style="display: inline-block; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease;">
                                <i class="fas fa-eye" style="margin-right: 4px;"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center; color: #6b7280;">
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
                        @if($batchUploadOpen)
                            <input type="hidden" name="batch_upload" value="1">
                        @endif
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

    <div id="batchUploadModal" class="fur-batch-modal" aria-hidden="true" hidden>
        <div class="fur-batch-modal-backdrop" aria-hidden="true"></div>
        <div class="fur-batch-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="batchUploadModalTitle">
            <div class="fur-batch-modal-header">
                <div>
                    <h3 id="batchUploadModalTitle">Batch Upload FUR</h3>
                    <p>Use project filters to narrow the upload queue before opening each FUR project.</p>
                </div>
                <button type="button" class="fur-batch-modal-close" data-batch-upload-close aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="fur-batch-modal-content">
                <div class="fur-batch-modal-scroll">
                    <form id="batchUploadFilterForm" method="GET" action="{{ route('fund-utilization.index') }}">
                        <input type="hidden" name="batch_upload" value="1">
                        <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
                        <div class="fur-batch-filter-form">
                            <button type="button" class="fur-batch-filter-toggle" onclick="toggleBatchUploadFilterPanel(this)" aria-expanded="true" aria-controls="batch-upload-filter-panel">
                                <span class="fur-batch-filter-toggle-copy">
                                    <i class="fas fa-filter" aria-hidden="true"></i>
                                    <span>Batch Project Filters</span>
                                </span>
                                <span class="fur-batch-filter-chevron">
                                    <i class="fas fa-chevron-up"></i>
                                </span>
                            </button>

                            <div id="batch-upload-filter-panel" class="fur-batch-filter-panel">
                                <div class="fur-batch-modal-body">
                                    <div class="dashboard-filter-grid" style="display: grid; grid-template-columns: repeat(3, minmax(200px, 1fr)); gap: 12px 16px; align-items: end;">
                                        <div>
                                            <label for="batch-upload-search" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Search</label>
                                            <div style="position: relative;">
                                                <i class="fas fa-search" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px; pointer-events: none;"></i>
                                                <input id="batch-upload-search" type="text" name="search" value="{{ $activeFilters['search'] }}" placeholder="Search project code, title, province..." style="width: 100%; height: 34px; padding: 0 12px 0 34px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 12px; background-color: #ffffff; color: #374151; box-sizing: border-box;">
                                            </div>
                                        </div>

                                        <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="batch_upload_program" data-badge-container-id="batch_upload_program_badges" data-dropdown-toggle-id="batch_upload_program_dropdown_toggle" data-dropdown-menu-id="batch_upload_program_dropdown_menu" data-empty-badge-text="No program selected.">
                                            <label for="batch_upload_program_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Program</label>
                                            <div class="dashboard-stacked-filter-dropdown">
                                                <div id="batch_upload_program_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="batch_upload_program_dropdown_menu">
                                                    <div id="batch_upload_program_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                                                    <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                                                </div>
                                                <div id="batch_upload_program_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                                            </div>
                                            <select id="batch_upload_program" name="program[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Program" aria-hidden="true">
                                                @foreach(($filterOptions['programs'] ?? []) as $option)
                                                    <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['program'] ?? []), true))>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="batch_upload_funding_year" data-badge-container-id="batch_upload_funding_year_badges" data-dropdown-toggle-id="batch_upload_funding_year_dropdown_toggle" data-dropdown-menu-id="batch_upload_funding_year_dropdown_menu" data-empty-badge-text="All">
                                            <label for="batch_upload_funding_year_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Funding Year</label>
                                            <div class="dashboard-stacked-filter-dropdown">
                                                <div id="batch_upload_funding_year_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="batch_upload_funding_year_dropdown_menu">
                                                    <div id="batch_upload_funding_year_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                                                    <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                                                </div>
                                                <div id="batch_upload_funding_year_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                                            </div>
                                            <select id="batch_upload_funding_year" name="funding_year[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Funding Year" aria-hidden="true">
                                                @foreach(($filterOptions['funding_years'] ?? []) as $option)
                                                    <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['funding_year'] ?? []), true))>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="batch_upload_province" data-badge-container-id="batch_upload_province_badges" data-dropdown-toggle-id="batch_upload_province_dropdown_toggle" data-dropdown-menu-id="batch_upload_province_dropdown_menu" data-empty-badge-text="All">
                                            <label for="batch_upload_province_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Province</label>
                                            <div class="dashboard-stacked-filter-dropdown">
                                                <div id="batch_upload_province_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="batch_upload_province_dropdown_menu">
                                                    <div id="batch_upload_province_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                                                    <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                                                </div>
                                                <div id="batch_upload_province_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                                            </div>
                                            <select id="batch_upload_province" name="province[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Province" aria-hidden="true">
                                                @foreach(($filterOptions['provinces'] ?? []) as $option)
                                                    <option value="{{ $option }}" @selected(in_array((string) $option, ($activeFilters['province'] ?? []), true))>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="batch_upload_city" data-badge-container-id="batch_upload_city_badges" data-dropdown-toggle-id="batch_upload_city_dropdown_toggle" data-dropdown-menu-id="batch_upload_city_dropdown_menu" data-empty-badge-text="All" data-empty-menu-text="Select at least one province first.">
                                            <label for="batch_upload_city_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">City/Municipality</label>
                                            <div class="dashboard-stacked-filter-dropdown">
                                                <div id="batch_upload_city_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="batch_upload_city_dropdown_menu">
                                                    <div id="batch_upload_city_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                                                    <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                                                </div>
                                                <div id="batch_upload_city_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                                            </div>
                                            <select id="batch_upload_city" name="city[]" multiple class="dashboard-stacked-filter-source" data-filter-label="City/Municipality" aria-hidden="true">
                                                @foreach($cityOptions as $city)
                                                    <option value="{{ $city }}" @selected(in_array((string) $city, ($activeFilters['city'] ?? []), true))>{{ $city }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="batch_upload_barangay" data-badge-container-id="batch_upload_barangay_badges" data-dropdown-toggle-id="batch_upload_barangay_dropdown_toggle" data-dropdown-menu-id="batch_upload_barangay_dropdown_menu" data-empty-badge-text="All" data-empty-menu-text="Select at least one city/municipality first.">
                                            <label for="batch_upload_barangay_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Barangay</label>
                                            <div class="dashboard-stacked-filter-dropdown">
                                                <div id="batch_upload_barangay_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="batch_upload_barangay_dropdown_menu">
                                                    <div id="batch_upload_barangay_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                                                    <span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span>
                                                </div>
                                                <div id="batch_upload_barangay_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                                            </div>
                                            <select id="batch_upload_barangay" name="barangay[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Barangay" aria-hidden="true">
                                                @foreach($barangayOptions as $barangay)
                                                    <option value="{{ $barangay }}" @selected(in_array((string) $barangay, ($activeFilters['barangay'] ?? []), true))>{{ $barangay }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="fur-batch-modal-actions">
                                        <a href="{{ route('fund-utilization.index', ['batch_upload' => 1, 'per_page' => $perPage ?? 10]) }}" class="fur-batch-modal-reset">
                                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                            Reset Filters
                                        </a>
                                        <button type="submit" class="fur-batch-modal-primary">
                                            <i class="fas fa-filter" aria-hidden="true"></i>
                                            Apply Project Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <section class="fur-batch-document-panel" aria-labelledby="batchUploadDocumentTitle">
                        <div class="fur-batch-document-panel-header">
                            <div>
                                <h4 id="batchUploadDocumentTitle">Upload Documents</h4>
                                <p>Attach the documents that will be used for the selected projects.</p>
                            </div>
                            <span class="fur-batch-document-pill">Batch Upload</span>
                        </div>

                        <div class="fur-batch-document-layout">
                            <div class="fur-batch-document-main">
                                <div class="fur-batch-document-dropzone">
                                    <div class="fur-batch-document-copy">
                                        <div class="fur-batch-document-icon">
                                            <i class="fas fa-folder-open" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <h5>Choose one or more PDF documents</h5>
                                            <p>Only PDF files are allowed, with a maximum size of 50 MB per file.</p>
                                        </div>
                                    </div>

                                    <label for="batchUploadDocumentFiles" class="fur-batch-document-button">
                                        <i class="fas fa-paperclip" aria-hidden="true"></i>
                                        Select Documents
                                    </label>
                                    <input id="batchUploadDocumentFiles" type="file" multiple accept="application/pdf,.pdf" class="fur-batch-document-input">
                                </div>
                            </div>
                            <div class="fur-batch-document-file-list" id="batchUploadDocumentList" hidden></div>
                        </div>
                        <div class="fur-batch-document-submit-row" id="batchUploadDocumentSubmitRow" hidden>
                            <button type="button" id="batchUploadDocumentSubmitBtn" class="fur-batch-document-submit-btn">
                                <i class="fas fa-upload" aria-hidden="true"></i>
                                Submit Documents
                            </button>
                        </div>
                    </section>

                    <div class="fur-batch-project-list">
                        <div class="fur-batch-shuttle" data-batch-shuttle>
                    <input type="hidden" id="batch_upload_selected_project_codes" name="selected_project_codes_json" value="[]">

                    <section class="fur-batch-shuttle-panel">
                        <div class="fur-batch-shuttle-panel-header">
                            <div>
                                <h4>Available Projects</h4>
                                <p>Filtered results from the current modal filters.</p>
                            </div>
                            <span class="fur-batch-shuttle-count" id="batchUploadAvailableCount">0</span>
                        </div>
                        <div class="fur-batch-shuttle-table-wrap">
                            <table class="fur-batch-shuttle-table">
                                <thead>
                                    <tr>
                                        <th style="width: 44px;">
                                            <input type="checkbox" id="batchUploadAvailableToggleAll">
                                        </th>
                                        <th style="width: 320px;">Project</th>
                                    </tr>
                                </thead>
                                <tbody id="batchUploadAvailableBody"></tbody>
                            </table>
                            <div class="fur-batch-empty-state" id="batchUploadAvailableEmpty" hidden>
                                No projects match the selected filters.
                            </div>
                        </div>
                    </section>

                    <div class="fur-batch-shuttle-controls">
                        <button type="button" class="fur-batch-shuttle-btn" id="batchUploadMoveSelectedRight" aria-label="Move selected projects to selected list">
                            <i class="fas fa-angle-right" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="fur-batch-shuttle-btn" id="batchUploadMoveAllRight" aria-label="Move all projects to selected list">
                            <i class="fas fa-angles-right" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="fur-batch-shuttle-btn" id="batchUploadMoveSelectedLeft" aria-label="Remove selected projects from selected list">
                            <i class="fas fa-angle-left" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="fur-batch-shuttle-btn" id="batchUploadMoveAllLeft" aria-label="Remove all projects from selected list">
                            <i class="fas fa-angles-left" aria-hidden="true"></i>
                        </button>
                    </div>

                    <section class="fur-batch-shuttle-panel">
                        <div class="fur-batch-shuttle-panel-header">
                            <div>
                                <h4>Selected Projects</h4>
                                <p>These are the projects moved from the filtered list.</p>
                            </div>
                            <span class="fur-batch-shuttle-count" id="batchUploadSelectedCount">0</span>
                        </div>
                        <div class="fur-batch-shuttle-table-wrap">
                            <table class="fur-batch-shuttle-table">
                                <thead>
                                    <tr>
                                        <th style="width: 44px;">
                                            <input type="checkbox" id="batchUploadSelectedToggleAll">
                                        </th>
                                        <th style="width: 320px;">Project</th>
                                        <th style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="batchUploadSelectedBody"></tbody>
                            </table>
                            <div class="fur-batch-empty-state" id="batchUploadSelectedEmpty">
                                No selected projects yet.
                            </div>
                        </div>
                    </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="batchUploadDocumentChecklistModal" class="fur-batch-checklist-modal" aria-hidden="true" hidden>
        <div class="fur-batch-checklist-backdrop" data-batch-checklist-close="1" aria-hidden="true"></div>
        <div class="fur-batch-checklist-dialog" role="dialog" aria-modal="true" aria-labelledby="batchUploadDocumentChecklistTitle">
            <div class="fur-batch-checklist-header">
                <div class="fur-batch-checklist-title-wrap">
                    <div class="fur-batch-checklist-icon">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="batchUploadDocumentChecklistTitle">Batch Upload Reminder</h3>
                        <p>Please check if the following are available in the document to be uploaded.</p>
                    </div>
                </div>
                <button type="button" class="fur-batch-checklist-close" data-batch-checklist-close="1" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="fur-batch-checklist-body">
                <div class="fur-batch-checklist-card">
                    <div class="fur-batch-checklist-label">Required items</div>
                    <div class="fur-batch-checklist-copy">
                        <div class="fur-batch-checklist-strong">Fund Utilization Report</div>
                        <div class="fur-batch-checklist-strong">Written Notices</div>
                        <div class="fur-batch-checklist-subtitle">Distribution Recipients:</div>
                        <ul class="fur-batch-checklist-list">
                            <li>Secretary of DBM</li>
                            <li>Secretary of DILG</li>
                            <li>Speaker of the House</li>
                            <li>President of the Senate</li>
                            <li>House Committee on Appropriation</li>
                            <li>Senate Committee on Finance</li>
                        </ul>
                        <div class="fur-batch-checklist-strong">Full Disclosure Policy (FDP)</div>
                        <div class="fur-batch-checklist-strong">LGU Website / Social Media</div>
                    </div>
                </div>
            </div>

            <div class="fur-batch-checklist-actions">
                <button type="button" class="fur-batch-checklist-cancel" data-batch-checklist-close="1">Cancel</button>
                <button type="button" id="confirmBatchUploadDocumentChecklistBtn" class="fur-batch-checklist-confirm">Confirm and Continue</button>
            </div>
        </div>
    </div>

    <div id="batchUploadQuarterModal" class="fur-batch-checklist-modal fur-batch-quarter-modal" aria-hidden="true" hidden>
        <div class="fur-batch-checklist-backdrop" data-batch-quarter-close="1" aria-hidden="true"></div>
        <div class="fur-batch-checklist-dialog fur-batch-quarter-dialog" role="dialog" aria-modal="true" aria-labelledby="batchUploadQuarterTitle">
            <div class="fur-batch-checklist-header">
                <div class="fur-batch-checklist-title-wrap">
                    <div class="fur-batch-checklist-icon">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="batchUploadQuarterTitle">Select Quarter</h3>
                        <p>Choose the quarter that will be used for this batch-upload submission.</p>
                    </div>
                </div>
                <button type="button" class="fur-batch-checklist-close" data-batch-quarter-close="1" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="fur-batch-checklist-body">
                <div class="fur-batch-checklist-card fur-batch-quarter-card">
                    <label for="batchUploadQuarterSelect" class="fur-batch-quarter-label">Quarter</label>
                    <select id="batchUploadQuarterSelect" class="fur-batch-quarter-select">
                        <option value="">Select Quarter</option>
                        <option value="Q1">Q1 (January - March)</option>
                        <option value="Q2">Q2 (April - June)</option>
                        <option value="Q3">Q3 (July - September)</option>
                        <option value="Q4">Q4 (October - December)</option>
                    </select>
                </div>
            </div>

            <div class="fur-batch-checklist-actions">
                <button type="button" class="fur-batch-checklist-cancel" data-batch-quarter-close="1">Cancel</button>
                <button type="button" id="confirmBatchUploadQuarterBtn" class="fur-batch-checklist-confirm">Continue</button>
            </div>
        </div>
    </div>

    <div id="batchUploadDocumentPreviewModal" class="fur-batch-checklist-modal fur-batch-preview-modal" aria-hidden="true" hidden>
        <div class="fur-batch-checklist-backdrop" data-batch-preview-close="1" aria-hidden="true"></div>
        <div class="fur-batch-checklist-dialog fur-batch-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="batchUploadDocumentPreviewTitle">
            <div class="fur-batch-checklist-header">
                <div class="fur-batch-checklist-title-wrap">
                    <div class="fur-batch-checklist-icon">
                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="batchUploadDocumentPreviewTitle">Document Preview</h3>
                        <p id="batchUploadDocumentPreviewName">Selected document preview</p>
                    </div>
                </div>
                <button type="button" class="fur-batch-checklist-close" data-batch-preview-close="1" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="fur-batch-preview-body">
                <div id="batchUploadDocumentPreviewContent" class="fur-batch-preview-content"></div>
            </div>
        </div>
    </div>

    <div id="batchUploadSubmitConfirmModal" class="fur-batch-checklist-modal fur-batch-submit-modal" aria-hidden="true" hidden>
        <div class="fur-batch-checklist-backdrop" data-batch-submit-close="1" aria-hidden="true"></div>
        <div class="fur-batch-checklist-dialog fur-batch-submit-dialog" role="dialog" aria-modal="true" aria-labelledby="batchUploadSubmitConfirmTitle">
            <div class="fur-batch-checklist-header">
                <div class="fur-batch-checklist-title-wrap">
                    <div class="fur-batch-checklist-icon">
                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 id="batchUploadSubmitConfirmTitle">Confirm Batch Upload</h3>
                        <p>Are you sure you want to upload this document to the selected projects for the chosen quarter?</p>
                    </div>
                </div>
                <button type="button" class="fur-batch-checklist-close" data-batch-submit-close="1" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="fur-batch-checklist-body">
                <div class="fur-batch-checklist-card fur-batch-submit-card">
                    <div class="fur-batch-submit-summary-row">
                        <span class="fur-batch-submit-summary-label">Quarter</span>
                        <span id="batchUploadSubmitQuarterValue" class="fur-batch-submit-summary-value">-</span>
                    </div>
                    <div class="fur-batch-submit-summary-row">
                        <span class="fur-batch-submit-summary-label">Document</span>
                        <span id="batchUploadSubmitDocumentValue" class="fur-batch-submit-summary-value">-</span>
                    </div>
                    <div id="batchUploadSubmitDocumentList" class="fur-batch-submit-document-list"></div>
                    <div class="fur-batch-submit-summary-row">
                        <span class="fur-batch-submit-summary-label">Selected Projects</span>
                        <span id="batchUploadSubmitProjectCountValue" class="fur-batch-submit-summary-value">0</span>
                    </div>
                    <div id="batchUploadSubmitProjectList" class="fur-batch-submit-project-list"></div>
                </div>
            </div>

            <div class="fur-batch-checklist-actions">
                <button type="button" class="fur-batch-checklist-cancel" data-batch-submit-close="1">Cancel</button>
                <button type="button" id="confirmBatchUploadSubmitBtn" class="fur-batch-checklist-confirm">Yes, Upload</button>
            </div>
        </div>
    </div>

    <script>
        let selectedFormat = '';
        const shouldAutoOpenBatchUploadModal = @json($batchUploadOpen);
        const BATCH_UPLOAD_BULK_ROUTE = @json(route('fund-utilization.batch-upload-documents'));
        const BATCH_UPLOAD_CSRF_TOKEN = @json(csrf_token());

        function openExportModal(format) {
            selectedFormat = format;
            document.getElementById('exportModal').style.display = 'flex';
        }

        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
            selectedFormat = '';
        }

        const FUND_UTILIZATION_LOCATION_MAP = @json($provinceMunicipalities ?? []);
        const FUND_UTILIZATION_BARANGAY_MAP = @json($cityBarangayMap ?? []);
        const BATCH_UPLOAD_SELECTION_STORAGE_KEY = 'fund-utilization-batch-upload-selected-projects';
        const BATCH_UPLOAD_MODAL_OPEN_STORAGE_KEY = 'fund-utilization-batch-upload-modal-open';
        const BATCH_UPLOAD_DOCUMENT_DB_NAME = 'fund-utilization-batch-upload';
        const BATCH_UPLOAD_DOCUMENT_STORE_NAME = 'modal-state';
        const BATCH_UPLOAD_DOCUMENT_STORE_KEY = 'selected-documents';
        const BATCH_UPLOAD_MAX_FILE_SIZE_BYTES = 50 * 1024 * 1024;
        const BATCH_UPLOAD_PROJECTS = @json($batchUploadProjects);
        const batchUploadModalCache = {
            elements: null,
            documents: {
                files: [],
                objectUrls: new Map(),
            },
            submitState: {
                quarter: '',
            },
            previewState: {
                fileKey: '',
            },
            submitRequest: {
                isSubmitting: false,
            },
            documentPersistence: {
                dbPromise: null,
                queue: Promise.resolve(),
            },
            shuttleController: null,
            rowMarkup: {
                available: new Map(),
                selected: new Map(),
            },
            shuttleState: new Map(),
            rowTemplate: document.createElement('template'),
        };
        let pendingBatchUploadDocumentInput = null;
        let batchUploadShuttleInitialized = false;

        function readBatchUploadModalOpenState() {
            try {
                return window.sessionStorage.getItem(BATCH_UPLOAD_MODAL_OPEN_STORAGE_KEY) === '1';
            } catch (error) {
                return false;
            }
        }

        function writeBatchUploadModalOpenState(isOpen) {
            try {
                if (isOpen) {
                    window.sessionStorage.setItem(BATCH_UPLOAD_MODAL_OPEN_STORAGE_KEY, '1');
                } else {
                    window.sessionStorage.removeItem(BATCH_UPLOAD_MODAL_OPEN_STORAGE_KEY);
                }
            } catch (error) {
                // Ignore browser storage failures and keep the modal usable.
            }
        }

        function clearBatchUploadSelectedCodes() {
            try {
                window.localStorage.removeItem(BATCH_UPLOAD_SELECTION_STORAGE_KEY);
            } catch (error) {
            }
        }

        function getBatchUploadDocumentPersistenceKey(fileLike) {
            return [fileLike.name, fileLike.size, fileLike.lastModified, fileLike.type].join('::');
        }

        function isValidBatchUploadDocumentFile(fileLike) {
            if (!fileLike || typeof fileLike.name !== 'string') {
                return false;
            }

            const fileName = fileLike.name.trim().toLowerCase();
            const fileType = String(fileLike.type || '').toLowerCase();
            const isPdfFile = fileName.endsWith('.pdf') || fileType === 'application/pdf';

            return isPdfFile && Number(fileLike.size) > 0 && Number(fileLike.size) <= BATCH_UPLOAD_MAX_FILE_SIZE_BYTES;
        }

        function getBatchUploadDocumentObjectUrl(file) {
            const key = getBatchUploadDocumentPersistenceKey(file);
            if (!batchUploadModalCache.documents.objectUrls.has(key)) {
                batchUploadModalCache.documents.objectUrls.set(key, URL.createObjectURL(file));
            }

            return batchUploadModalCache.documents.objectUrls.get(key);
        }

        function getSelectedBatchUploadProjectCodes() {
            const selectedCodesInput = document.getElementById('batch_upload_selected_project_codes');
            if (!selectedCodesInput) {
                return [];
            }

            try {
                const parsedValue = JSON.parse(selectedCodesInput.value || '[]');
                return Array.isArray(parsedValue)
                    ? parsedValue.map((value) => String(value || '').trim()).filter(Boolean)
                    : [];
            } catch (error) {
                return [];
            }
        }

        function getBatchUploadDocumentDatabase() {
            if (!('indexedDB' in window)) {
                return Promise.resolve(null);
            }

            if (batchUploadModalCache.documentPersistence.dbPromise) {
                return batchUploadModalCache.documentPersistence.dbPromise;
            }

            batchUploadModalCache.documentPersistence.dbPromise = new Promise((resolve) => {
                let settled = false;
                const finalize = (database) => {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    resolve(database);
                };
                const request = window.indexedDB.open(BATCH_UPLOAD_DOCUMENT_DB_NAME, 1);

                request.onupgradeneeded = () => {
                    const database = request.result;
                    if (!database.objectStoreNames.contains(BATCH_UPLOAD_DOCUMENT_STORE_NAME)) {
                        database.createObjectStore(BATCH_UPLOAD_DOCUMENT_STORE_NAME);
                    }
                };

                request.onsuccess = () => finalize(request.result);
                request.onerror = () => finalize(null);
                request.onblocked = () => finalize(null);
            });

            return batchUploadModalCache.documentPersistence.dbPromise;
        }

        function queueBatchUploadDocumentPersistence(task) {
            const persistence = batchUploadModalCache.documentPersistence;
            persistence.queue = persistence.queue
                .then(() => task())
                .catch(() => null);

            return persistence.queue;
        }

        async function readPersistedBatchUploadDocuments() {
            const database = await getBatchUploadDocumentDatabase();
            if (!database) {
                return [];
            }

            return new Promise((resolve) => {
                try {
                    const transaction = database.transaction(BATCH_UPLOAD_DOCUMENT_STORE_NAME, 'readonly');
                    const store = transaction.objectStore(BATCH_UPLOAD_DOCUMENT_STORE_NAME);
                    const request = store.get(BATCH_UPLOAD_DOCUMENT_STORE_KEY);
                    let settled = false;
                    const finalize = (value) => {
                        if (settled) {
                            return;
                        }

                        settled = true;
                        resolve(Array.isArray(value) ? value : []);
                    };

                    request.onsuccess = () => finalize(request.result);
                    request.onerror = () => finalize([]);
                    transaction.onabort = () => finalize([]);
                } catch (error) {
                    resolve([]);
                }
            });
        }

        async function writePersistedBatchUploadDocuments(records) {
            const database = await getBatchUploadDocumentDatabase();
            if (!database) {
                return;
            }

            return new Promise((resolve) => {
                try {
                    const transaction = database.transaction(BATCH_UPLOAD_DOCUMENT_STORE_NAME, 'readwrite');
                    const store = transaction.objectStore(BATCH_UPLOAD_DOCUMENT_STORE_NAME);
                    const request = records.length > 0
                        ? store.put(records, BATCH_UPLOAD_DOCUMENT_STORE_KEY)
                        : store.delete(BATCH_UPLOAD_DOCUMENT_STORE_KEY);
                    let settled = false;
                    const finalize = () => {
                        if (settled) {
                            return;
                        }

                        settled = true;
                        resolve();
                    };

                    request.onsuccess = finalize;
                    request.onerror = finalize;
                    transaction.oncomplete = finalize;
                    transaction.onabort = finalize;
                } catch (error) {
                    resolve();
                }
            });
        }

        function persistBatchUploadDocuments(files) {
            const snapshot = files.map((file) => ({
                key: getBatchUploadDocumentPersistenceKey(file),
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: file.lastModified,
                blob: file,
            }));

            return queueBatchUploadDocumentPersistence(() => writePersistedBatchUploadDocuments(snapshot));
        }

        async function restorePersistedBatchUploadDocuments() {
            const storedRecords = await readPersistedBatchUploadDocuments();

            return storedRecords
                .map((record) => {
                    if (!record || typeof record.name !== 'string' || !(record.blob instanceof Blob)) {
                        return null;
                    }

                    try {
                        return new File([record.blob], record.name, {
                            type: record.type || record.blob.type || '',
                            lastModified: Number(record.lastModified) || Date.now(),
                        });
                    } catch (error) {
                        return null;
                    }
                })
                .filter(Boolean);
        }

        function getBatchUploadModalElements() {
            if (batchUploadModalCache.elements) {
                return batchUploadModalCache.elements;
            }

            const modal = document.getElementById('batchUploadModal');
            if (!modal) {
                return null;
            }

            batchUploadModalCache.elements = {
                modal,
                modalScroll: modal.querySelector('.fur-batch-modal-scroll'),
                filterForm: modal.querySelector('.fur-batch-filter-form'),
            };

            return batchUploadModalCache.elements;
        }

        function openBatchUploadModal() {
            const modalElements = getBatchUploadModalElements();
            if (!modalElements) {
                return;
            }

            const { modal, modalScroll, filterForm } = modalElements;
            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('fur-batch-modal-open');
            writeBatchUploadModalOpenState(true);

            if (modalScroll) {
                modalScroll.scrollTop = 0;
            }

            if (!batchUploadShuttleInitialized) {
                initializeBatchUploadShuttle();
            }

            if (filterForm) {
                filterForm.classList.remove('collapsed');
                setBatchUploadFilterPanelHeight(filterForm);
            }
        }

        function closeBatchUploadModal() {
            const modalElements = getBatchUploadModalElements();
            if (!modalElements) {
                return;
            }

            const { modal } = modalElements;
            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('fur-batch-modal-open');
            writeBatchUploadModalOpenState(false);
        }

        function resetBatchUploadDocumentState() {
            const batchUploadDocumentFiles = document.getElementById('batchUploadDocumentFiles');
            const batchUploadDocumentList = document.getElementById('batchUploadDocumentList');
            const batchUploadDocumentSubmitRow = document.getElementById('batchUploadDocumentSubmitRow');
            const batchUploadDocumentSubmitBtn = document.getElementById('batchUploadDocumentSubmitBtn');
            const batchUploadDocumentPanel = batchUploadDocumentFiles?.closest('.fur-batch-document-panel');
            const batchDocumentState = batchUploadModalCache.documents;

            batchDocumentState.objectUrls.forEach((objectUrl) => {
                URL.revokeObjectURL(objectUrl);
            });
            batchDocumentState.objectUrls.clear();
            batchDocumentState.files = [];

            if (batchUploadDocumentFiles) {
                batchUploadDocumentFiles.value = '';
                delete batchUploadDocumentFiles.dataset.batchChecklistConfirmed;

                if (typeof DataTransfer === 'function') {
                    const dataTransfer = new DataTransfer();
                    batchUploadDocumentFiles.files = dataTransfer.files;
                }
            }

            if (batchUploadDocumentList) {
                batchUploadDocumentList.hidden = true;
                batchUploadDocumentList.innerHTML = '';
            }

            if (batchUploadDocumentSubmitRow) {
                batchUploadDocumentSubmitRow.hidden = true;
            }

            if (batchUploadDocumentSubmitBtn) {
                batchUploadDocumentSubmitBtn.disabled = true;
            }

            batchUploadDocumentPanel?.classList.remove('has-files');
            persistBatchUploadDocuments([]);
        }

        function resetBatchUploadFilters() {
            const searchInput = document.getElementById('batch-upload-search');
            if (searchInput) {
                searchInput.value = '';
            }

            ['program', 'funding_year', 'province', 'city', 'barangay'].forEach((suffix) => {
                const select = document.getElementById(`batch_upload_${suffix}`);
                if (!select) {
                    return;
                }

                Array.from(select.options).forEach((option) => {
                    option.selected = false;
                });
                select.__selectionOrder = [];
            });

            rebuildStandardCityOptions('batch_upload_province', 'batch_upload_city');
            rebuildStandardBarangayOptions('batch_upload_city', 'batch_upload_barangay');

            const modalElements = getBatchUploadModalElements();
            modalElements?.modal.querySelectorAll('[data-stacked-filter]').forEach((stackedFilter) => {
                if (typeof stackedFilter.__closeDropdown === 'function') {
                    stackedFilter.__closeDropdown();
                }

                if (typeof stackedFilter.__refreshFilterUi === 'function') {
                    stackedFilter.__refreshFilterUi();
                }
            });

            if (modalElements?.filterForm) {
                modalElements.filterForm.classList.remove('collapsed');
                setBatchUploadFilterPanelHeight(modalElements.filterForm);
            }
        }

        function resetBatchUploadModalState() {
            closeBatchUploadDocumentChecklistModal();
            closeBatchUploadQuarterModal();
            closeBatchUploadDocumentPreviewModal();
            closeBatchUploadSubmitConfirmModal();

            batchUploadModalCache.submitState.quarter = '';
            const quarterSelect = document.getElementById('batchUploadQuarterSelect');
            if (quarterSelect) {
                quarterSelect.value = '';
            }

            resetBatchUploadFilters();
            clearBatchUploadSelectedCodes();
            document.getElementById('batch_upload_selected_project_codes').value = '[]';

            if (batchUploadModalCache.shuttleController && typeof batchUploadModalCache.shuttleController.resetSelections === 'function') {
                batchUploadModalCache.shuttleController.resetSelections();
            }

            batchUploadModalCache.shuttleState.clear();
            resetBatchUploadDocumentState();
        }

        function resetAndCloseBatchUploadModal() {
            resetBatchUploadModalState();
            closeBatchUploadModal();
        }

        function openBatchUploadDocumentChecklistModal(inputElement) {
            const modal = document.getElementById('batchUploadDocumentChecklistModal');
            if (!modal || !inputElement) {
                return;
            }

            pendingBatchUploadDocumentInput = inputElement;
            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeBatchUploadDocumentChecklistModal() {
            const modal = document.getElementById('batchUploadDocumentChecklistModal');
            if (!modal) {
                pendingBatchUploadDocumentInput = null;
                return;
            }

            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            pendingBatchUploadDocumentInput = null;
        }

        function openBatchUploadQuarterModal() {
            const modal = document.getElementById('batchUploadQuarterModal');
            const quarterSelect = document.getElementById('batchUploadQuarterSelect');
            if (!modal || !quarterSelect) {
                return;
            }

            quarterSelect.value = batchUploadModalCache.submitState.quarter || '';
            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeBatchUploadQuarterModal() {
            const modal = document.getElementById('batchUploadQuarterModal');
            if (!modal) {
                return;
            }

            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        function initializeBatchUploadQuarterModal() {
            document.querySelectorAll('[data-batch-quarter-close="1"]').forEach((element) => {
                if (element.dataset.batchQuarterCloseBound === '1') {
                    return;
                }

                element.dataset.batchQuarterCloseBound = '1';
                element.addEventListener('click', closeBatchUploadQuarterModal);
            });

            const confirmButton = document.getElementById('confirmBatchUploadQuarterBtn');
            const quarterSelect = document.getElementById('batchUploadQuarterSelect');
            if (confirmButton && quarterSelect && confirmButton.dataset.batchQuarterConfirmBound !== '1') {
                confirmButton.dataset.batchQuarterConfirmBound = '1';
                confirmButton.addEventListener('click', () => {
                    if (!quarterSelect.value) {
                        quarterSelect.focus();
                        return;
                    }

                    batchUploadModalCache.submitState.quarter = quarterSelect.value;
                    closeBatchUploadQuarterModal();
                    openBatchUploadSubmitConfirmModal();
                });
            }
        }

        function closeBatchUploadDocumentPreviewModal() {
            const modal = document.getElementById('batchUploadDocumentPreviewModal');
            const previewContent = document.getElementById('batchUploadDocumentPreviewContent');
            const previewName = document.getElementById('batchUploadDocumentPreviewName');
            if (!modal) {
                return;
            }

            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            batchUploadModalCache.previewState.fileKey = '';

            if (previewContent) {
                previewContent.innerHTML = '';
            }

            if (previewName) {
                previewName.textContent = 'Selected document preview';
            }
        }

        function openBatchUploadDocumentPreviewModal(file, objectUrl) {
            const modal = document.getElementById('batchUploadDocumentPreviewModal');
            const previewContent = document.getElementById('batchUploadDocumentPreviewContent');
            const previewName = document.getElementById('batchUploadDocumentPreviewName');
            if (!modal || !previewContent || !file || !objectUrl) {
                return;
            }

            const normalizedType = String(file.type || '').toLowerCase();
            const fileName = String(file.name || 'Selected document');
            const escapedName = fileName
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const escapedUrl = String(objectUrl)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;');
            let previewMarkup = '';

            if (normalizedType.startsWith('image/')) {
                previewMarkup = `<img src="${escapedUrl}" alt="${escapedName}" class="fur-batch-preview-image">`;
            } else if (normalizedType === 'application/pdf') {
                previewMarkup = `<iframe src="${escapedUrl}" class="fur-batch-preview-frame" title="${escapedName}"></iframe>`;
            } else if (normalizedType.startsWith('text/')) {
                previewMarkup = `<iframe src="${escapedUrl}" class="fur-batch-preview-frame" title="${escapedName}"></iframe>`;
            } else {
                previewMarkup = `
                    <div class="fur-batch-preview-empty">
                        <div class="fur-batch-preview-empty-icon">
                            <i class="fas fa-file" aria-hidden="true"></i>
                        </div>
                        <div class="fur-batch-preview-empty-title">${escapedName}</div>
                        <div class="fur-batch-preview-empty-copy">Preview is not available for this file type inside the modal.</div>
                    </div>
                `;
            }

            batchUploadModalCache.previewState.fileKey = getBatchUploadDocumentPersistenceKey(file);
            previewContent.innerHTML = previewMarkup;
            if (previewName) {
                previewName.textContent = fileName;
            }

            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function initializeBatchUploadDocumentPreviewModal() {
            document.querySelectorAll('[data-batch-preview-close="1"]').forEach((element) => {
                if (element.dataset.batchPreviewCloseBound === '1') {
                    return;
                }

                element.dataset.batchPreviewCloseBound = '1';
                element.addEventListener('click', closeBatchUploadDocumentPreviewModal);
            });
        }

        function closeBatchUploadSubmitConfirmModal() {
            const modal = document.getElementById('batchUploadSubmitConfirmModal');
            if (!modal) {
                return;
            }

            modal.hidden = true;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        function openBatchUploadSubmitConfirmModal() {
            const modal = document.getElementById('batchUploadSubmitConfirmModal');
            const quarterValue = document.getElementById('batchUploadSubmitQuarterValue');
            const documentValue = document.getElementById('batchUploadSubmitDocumentValue');
            const documentList = document.getElementById('batchUploadSubmitDocumentList');
            const projectCountValue = document.getElementById('batchUploadSubmitProjectCountValue');
            const projectList = document.getElementById('batchUploadSubmitProjectList');
            const selectedProjectCodes = getSelectedBatchUploadProjectCodes();
            const selectedFiles = batchUploadModalCache.documents.files || [];
            const selectedQuarter = batchUploadModalCache.submitState.quarter || '';
            const projectMap = new Map(BATCH_UPLOAD_PROJECTS.map((project) => [project.project_code, project]));

            if (!modal || !quarterValue || !documentValue || !documentList || !projectCountValue || !projectList) {
                return;
            }

            quarterValue.textContent = selectedQuarter || '-';
            documentValue.textContent = selectedFiles.length === 0
                ? '-'
                : `${selectedFiles.length} document${selectedFiles.length === 1 ? '' : 's'} selected`;
            documentList.innerHTML = selectedFiles
                .map((file) => {
                    const escapedFileKey = getBatchUploadDocumentPersistenceKey(file)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                    const escapedFileName = String(file.name || 'Selected document')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');

                    return `
                        <div class="fur-batch-submit-document-item">
                            <span class="fur-batch-submit-document-name">${escapedFileName}</span>
                            <button type="button" class="fur-batch-submit-document-view-btn" data-batch-submit-document-view="${escapedFileKey}">View</button>
                        </div>
                    `;
                })
                .join('');
            projectCountValue.textContent = String(selectedProjectCodes.length);
            projectList.innerHTML = `
                <div class="fur-batch-submit-project-grid fur-batch-submit-project-grid-head">
                    <span class="fur-batch-submit-project-head">Project Code</span>
                    <span class="fur-batch-submit-project-head">Project Title</span>
                </div>
                ${selectedProjectCodes.slice(0, 8)
                .map((projectCode) => {
                    const project = projectMap.get(projectCode);
                    const escapedCode = String(projectCode)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                    const escapedTitle = String(project?.project_title || 'Untitled project')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');

                    return `
                        <div class="fur-batch-submit-project-grid">
                            <span class="fur-batch-submit-project-code">${escapedCode}</span>
                            <span class="fur-batch-submit-project-title">${escapedTitle}</span>
                        </div>
                    `;
                })
                .join('')}`;

            if (selectedProjectCodes.length > 8) {
                projectList.insertAdjacentHTML('beforeend', `
                    <div class="fur-batch-submit-project-grid fur-batch-submit-project-grid-more">
                        <span class="fur-batch-submit-project-more" style="grid-column: 1 / -1;">+${selectedProjectCodes.length - 8} more</span>
                    </div>
                `);
            }

            modal.hidden = false;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        async function submitBatchUploadDocuments() {
            if (batchUploadModalCache.submitRequest.isSubmitting) {
                return;
            }

            const selectedProjectCodes = getSelectedBatchUploadProjectCodes();
            const selectedQuarter = batchUploadModalCache.submitState.quarter || '';
            const selectedFiles = batchUploadModalCache.documents.files || [];
            const confirmButton = document.getElementById('confirmBatchUploadSubmitBtn');
            const originalButtonText = confirmButton?.innerHTML || 'Yes, Upload';

            if (!selectedQuarter) {
                closeBatchUploadSubmitConfirmModal();
                openBatchUploadQuarterModal();
                return;
            }

            if (selectedFiles.length === 0) {
                alert('Please select at least one PDF document first.');
                closeBatchUploadSubmitConfirmModal();
                return;
            }

            if (selectedFiles.some((file) => !isValidBatchUploadDocumentFile(file))) {
                alert('Only PDF files up to 50 MB can be submitted for batch upload.');
                closeBatchUploadSubmitConfirmModal();
                return;
            }

            if (selectedProjectCodes.length === 0) {
                alert('Please select at least one project first.');
                closeBatchUploadSubmitConfirmModal();
                return;
            }

            batchUploadModalCache.submitRequest.isSubmitting = true;
            if (confirmButton) {
                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Uploading...';
            }

            try {
                const formData = new FormData();
                formData.append('_token', BATCH_UPLOAD_CSRF_TOKEN);
                formData.append('quarter', selectedQuarter);
                selectedFiles.forEach((file) => {
                    formData.append('batch_document_files[]', file);
                });
                selectedProjectCodes.forEach((projectCode) => {
                    formData.append('project_codes[]', projectCode);
                });

                const response = await fetch(BATCH_UPLOAD_BULK_ROUTE, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload?.message || 'Batch upload failed. Please try again.');
                }

                closeBatchUploadSubmitConfirmModal();
                resetBatchUploadModalState();
                await persistBatchUploadDocuments([]);
                closeBatchUploadModal();
                alert(payload?.message || 'Batch upload completed successfully.');
                window.location.reload();
            } catch (error) {
                alert(error?.message || 'Batch upload failed. Please try again.');
            } finally {
                batchUploadModalCache.submitRequest.isSubmitting = false;
                if (confirmButton) {
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = originalButtonText;
                }
            }
        }

        function initializeBatchUploadSubmitConfirmModal() {
            document.querySelectorAll('[data-batch-submit-close="1"]').forEach((element) => {
                if (element.dataset.batchSubmitCloseBound === '1') {
                    return;
                }

                element.dataset.batchSubmitCloseBound = '1';
                element.addEventListener('click', closeBatchUploadSubmitConfirmModal);
            });

            const confirmButton = document.getElementById('confirmBatchUploadSubmitBtn');
            if (confirmButton && confirmButton.dataset.batchSubmitConfirmBound !== '1') {
                confirmButton.dataset.batchSubmitConfirmBound = '1';
                confirmButton.addEventListener('click', submitBatchUploadDocuments);
            }

            const documentList = document.getElementById('batchUploadSubmitDocumentList');
            if (documentList && documentList.dataset.batchSubmitPreviewBound !== '1') {
                documentList.dataset.batchSubmitPreviewBound = '1';
                documentList.addEventListener('click', (event) => {
                    const previewButton = event.target.closest('[data-batch-submit-document-view]');
                    if (!previewButton) {
                        return;
                    }

                    const fileKey = previewButton.dataset.batchSubmitDocumentView || '';
                    const selectedFile = batchUploadModalCache.documents.files.find((file) => getBatchUploadDocumentPersistenceKey(file) === fileKey);
                    if (!selectedFile) {
                        return;
                    }

                    openBatchUploadDocumentPreviewModal(selectedFile, getBatchUploadDocumentObjectUrl(selectedFile));
                });
            }
        }

        function initializeBatchUploadDocumentChecklist() {
            const batchUploadDocumentFiles = document.getElementById('batchUploadDocumentFiles');
            if (batchUploadDocumentFiles && batchUploadDocumentFiles.dataset.batchChecklistBound !== '1') {
                batchUploadDocumentFiles.dataset.batchChecklistBound = '1';
                batchUploadDocumentFiles.addEventListener('click', (event) => {
                    if (batchUploadDocumentFiles.dataset.batchChecklistConfirmed === '1') {
                        delete batchUploadDocumentFiles.dataset.batchChecklistConfirmed;
                        return;
                    }

                    event.preventDefault();
                    openBatchUploadDocumentChecklistModal(batchUploadDocumentFiles);
                });
            }

            document.querySelectorAll('[data-batch-checklist-close="1"]').forEach((element) => {
                if (element.dataset.batchChecklistCloseBound === '1') {
                    return;
                }

                element.dataset.batchChecklistCloseBound = '1';
                element.addEventListener('click', closeBatchUploadDocumentChecklistModal);
            });

            const confirmButton = document.getElementById('confirmBatchUploadDocumentChecklistBtn');
            if (confirmButton && confirmButton.dataset.batchChecklistConfirmBound !== '1') {
                confirmButton.dataset.batchChecklistConfirmBound = '1';
                confirmButton.addEventListener('click', () => {
                    const inputToOpen = pendingBatchUploadDocumentInput;
                    closeBatchUploadDocumentChecklistModal();

                    if (!inputToOpen) {
                        return;
                    }

                    inputToOpen.dataset.batchChecklistConfirmed = '1';
                    inputToOpen.click();
                });
            }
        }

        function readBatchUploadSelectedCodes() {
            try {
                const storedValue = window.localStorage.getItem(BATCH_UPLOAD_SELECTION_STORAGE_KEY);
                const parsedValue = storedValue ? JSON.parse(storedValue) : [];
                return Array.isArray(parsedValue)
                    ? parsedValue.map((value) => String(value || '').trim()).filter(Boolean)
                    : [];
            } catch (error) {
                return [];
            }
        }

        function writeBatchUploadSelectedCodes(projectCodes) {
            try {
                window.localStorage.setItem(BATCH_UPLOAD_SELECTION_STORAGE_KEY, JSON.stringify(projectCodes));
            } catch (error) {
            }
        }

        function initializeBatchUploadShuttle() {
            if (batchUploadShuttleInitialized) {
                return;
            }

            const availableBody = document.getElementById('batchUploadAvailableBody');
            const selectedBody = document.getElementById('batchUploadSelectedBody');
            const availableEmpty = document.getElementById('batchUploadAvailableEmpty');
            const selectedEmpty = document.getElementById('batchUploadSelectedEmpty');
            const availableCount = document.getElementById('batchUploadAvailableCount');
            const selectedCount = document.getElementById('batchUploadSelectedCount');
            const availableToggleAll = document.getElementById('batchUploadAvailableToggleAll');
            const selectedToggleAll = document.getElementById('batchUploadSelectedToggleAll');
            const selectedCodesInput = document.getElementById('batch_upload_selected_project_codes');
            const batchUploadDocumentSubmitBtn = document.getElementById('batchUploadDocumentSubmitBtn');
            const shuttleRoot = document.querySelector('[data-batch-shuttle]');

            if (!availableBody || !selectedBody || !availableEmpty || !selectedEmpty || !availableCount || !selectedCount || !availableToggleAll || !selectedToggleAll || !selectedCodesInput || !shuttleRoot || !batchUploadDocumentSubmitBtn) {
                return;
            }

            const projectMap = new Map(BATCH_UPLOAD_PROJECTS.map((project) => [project.project_code, project]));
            const initiallySelectedCodes = readBatchUploadSelectedCodes().filter((projectCode) => projectMap.has(projectCode));
            const selectedCodes = new Set(initiallySelectedCodes);
            const availableChecked = new Set();
            const selectedChecked = new Set();

            const syncHiddenField = () => {
                const values = Array.from(selectedCodes);
                selectedCodesInput.value = JSON.stringify(values);
                writeBatchUploadSelectedCodes(values);
            };

            const escapeBatchUploadHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const buildProjectMetaLabel = (project) => {
                return [project.province || 'No province', project.city_municipality || 'No city/municipality', project.barangay || 'No barangay']
                    .filter(Boolean)
                    .join(' / ');
            };

            const buildProjectCellMarkup = (project) => `
                <div class="fur-batch-shuttle-project-cell">
                    <div class="fur-batch-shuttle-field">
                        <div class="fur-batch-shuttle-label">Project Code</div>
                        <div class="fur-batch-project-code">${escapeBatchUploadHtml(project.project_code)}</div>
                    </div>
                    <div class="fur-batch-shuttle-field">
                        <div class="fur-batch-shuttle-label">Project Title</div>
                        <div class="fur-batch-shuttle-title">${escapeBatchUploadHtml(project.project_title || 'Untitled project')}</div>
                    </div>
                    <div class="fur-batch-shuttle-field">
                        <div class="fur-batch-shuttle-label">Location</div>
                        <div class="fur-batch-shuttle-meta">${escapeBatchUploadHtml(buildProjectMetaLabel(project))}</div>
                    </div>
                    <div class="fur-batch-shuttle-field">
                        <div class="fur-batch-shuttle-label">Funding Year</div>
                        <div class="fur-batch-shuttle-year">${escapeBatchUploadHtml(project.funding_year || '-')}</div>
                    </div>
                </div>
            `;

            const buildAvailableRowMarkup = (project, isChecked = false) => `
                <tr data-project-code="${escapeBatchUploadHtml(project.project_code)}">
                    <td>
                        <input type="checkbox" class="fur-batch-row-checkbox" data-batch-available-checkbox value="${escapeBatchUploadHtml(project.project_code)}"${isChecked ? ' checked' : ''}>
                    </td>
                    <td>${buildProjectCellMarkup(project)}</td>
                </tr>
            `;

            const buildSelectedRowMarkup = (project, isChecked = false) => `
                <tr data-project-code="${escapeBatchUploadHtml(project.project_code)}">
                    <td>
                        <input type="checkbox" class="fur-batch-row-checkbox" data-batch-selected-checkbox value="${escapeBatchUploadHtml(project.project_code)}"${isChecked ? ' checked' : ''}>
                    </td>
                    <td>${buildProjectCellMarkup(project)}</td>
                    <td>
                        <div class="fur-batch-shuttle-field fur-batch-shuttle-field-center">
                            <div class="fur-batch-shuttle-label">Action</div>
                            <a href="${escapeBatchUploadHtml(project.open_url)}" class="fur-batch-project-link">
                                Open
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `;

            let availableProjectCount = 0;
            let selectedProjectCount = 0;
            const availableRowLookup = new Map();
            const selectedRowLookup = new Map();

            const createRowElementFromMarkup = (markup) => {
                batchUploadModalCache.rowTemplate.innerHTML = markup.trim();
                return batchUploadModalCache.rowTemplate.content.firstElementChild.cloneNode(true);
            };

            const getCachedRowMarkup = (project, listType, isChecked = false) => {
                const normalizedListType = listType === 'selected' ? 'selected' : 'available';
                const cacheKey = `${project.project_code}::${isChecked ? '1' : '0'}`;
                const rowCache = batchUploadModalCache.rowMarkup[normalizedListType];
                if (rowCache.has(cacheKey)) {
                    return rowCache.get(cacheKey);
                }

                const markup = normalizedListType === 'selected'
                    ? buildSelectedRowMarkup(project, isChecked)
                    : buildAvailableRowMarkup(project, isChecked);

                rowCache.set(cacheKey, markup);
                return markup;
            };

            const rebuildRowLookups = () => {
                availableRowLookup.clear();
                selectedRowLookup.clear();

                Array.from(availableBody.children).forEach((row) => {
                    if (row.dataset.projectCode) {
                        availableRowLookup.set(row.dataset.projectCode, row);
                    }
                });

                Array.from(selectedBody.children).forEach((row) => {
                    if (row.dataset.projectCode) {
                        selectedRowLookup.set(row.dataset.projectCode, row);
                    }
                });
            };

            const syncToggleStates = () => {
                availableToggleAll.checked = availableProjectCount > 0 && availableChecked.size === availableProjectCount;
                availableToggleAll.indeterminate = availableChecked.size > 0 && availableChecked.size < availableProjectCount;

                selectedToggleAll.checked = selectedProjectCount > 0 && selectedChecked.size === selectedProjectCount;
                selectedToggleAll.indeterminate = selectedChecked.size > 0 && selectedChecked.size < selectedProjectCount;
            };

            const syncShuttleSummary = () => {
                availableProjectCount = availableBody.childElementCount;
                selectedProjectCount = selectedBody.childElementCount;

                availableEmpty.hidden = availableProjectCount > 0;
                selectedEmpty.hidden = selectedProjectCount > 0;
                availableCount.textContent = String(availableProjectCount);
                selectedCount.textContent = String(selectedProjectCount);
                batchUploadDocumentSubmitBtn.disabled = selectedProjectCount === 0;

                syncHiddenField();
                syncToggleStates();
            };

            const buildShuttleStateCacheKey = () => {
                const selectedProjectCodes = [];
                BATCH_UPLOAD_PROJECTS.forEach((project) => {
                    if (selectedCodes.has(project.project_code)) {
                        selectedProjectCodes.push(project.project_code);
                    }
                });

                return selectedProjectCodes.length ? selectedProjectCodes.join('|') : '__available__';
            };

            const restoreCachedShuttleState = (cacheKey) => {
                const cachedState = batchUploadModalCache.shuttleState.get(cacheKey);
                if (!cachedState) {
                    return false;
                }

                availableBody.innerHTML = cachedState.availableHtml;
                selectedBody.innerHTML = cachedState.selectedHtml;
                rebuildRowLookups();
                syncShuttleSummary();
                return true;
            };

            const cacheCurrentShuttleState = (cacheKey) => {
                if (availableChecked.size > 0 || selectedChecked.size > 0) {
                    return;
                }

                batchUploadModalCache.shuttleState.set(cacheKey, {
                    availableHtml: availableBody.innerHTML,
                    selectedHtml: selectedBody.innerHTML,
                });
            };

            const updateCheckedState = (checkedSet, checkbox) => {
                if (!checkbox) {
                    return;
                }

                if (checkbox.checked) {
                    checkedSet.add(checkbox.value);
                } else {
                    checkedSet.delete(checkbox.value);
                }
            };

            const renderShuttle = () => {
                const cacheKey = buildShuttleStateCacheKey();
                if (availableChecked.size === 0 && selectedChecked.size === 0 && restoreCachedShuttleState(cacheKey)) {
                    return;
                }

                const availableProjects = [];
                const selectedProjects = [];

                BATCH_UPLOAD_PROJECTS.forEach((project) => {
                    if (selectedCodes.has(project.project_code)) {
                        selectedProjects.push(project);
                    } else {
                        availableProjects.push(project);
                    }
                });

                const availableProjectCodeSet = new Set(availableProjects.map((project) => project.project_code));
                const selectedProjectCodeSet = new Set(selectedProjects.map((project) => project.project_code));

                availableChecked.forEach((projectCode) => {
                    if (!availableProjectCodeSet.has(projectCode)) {
                        availableChecked.delete(projectCode);
                    }
                });

                selectedChecked.forEach((projectCode) => {
                    if (!selectedProjectCodeSet.has(projectCode)) {
                        selectedChecked.delete(projectCode);
                    }
                });

                availableBody.innerHTML = availableProjects
                    .map((project) => getCachedRowMarkup(project, 'available', availableChecked.has(project.project_code)))
                    .join('');
                selectedBody.innerHTML = selectedProjects
                    .map((project) => getCachedRowMarkup(project, 'selected', selectedChecked.has(project.project_code)))
                    .join('');

                rebuildRowLookups();
                syncShuttleSummary();
                cacheCurrentShuttleState(cacheKey);
            };

            const moveProjectsBetweenLists = (projectCodes, direction) => {
                if (!Array.isArray(projectCodes) || projectCodes.length === 0) {
                    return;
                }

                const movingToSelected = direction === 'right';
                const sourceLookup = movingToSelected ? availableRowLookup : selectedRowLookup;
                const targetLookup = movingToSelected ? selectedRowLookup : availableRowLookup;
                const sourceBody = movingToSelected ? availableBody : selectedBody;
                const targetBody = movingToSelected ? selectedBody : availableBody;
                const sourceCheckedSet = movingToSelected ? availableChecked : selectedChecked;
                const targetCheckedSet = movingToSelected ? selectedChecked : availableChecked;
                const fragment = document.createDocumentFragment();

                projectCodes.forEach((projectCode) => {
                    const normalizedProjectCode = String(projectCode || '').trim();
                    if (!normalizedProjectCode) {
                        return;
                    }

                    const project = projectMap.get(normalizedProjectCode);
                    const existingRow = sourceLookup.get(normalizedProjectCode);
                    if (!project || !existingRow) {
                        return;
                    }

                    if (movingToSelected) {
                        selectedCodes.add(normalizedProjectCode);
                    } else {
                        selectedCodes.delete(normalizedProjectCode);
                    }

                    sourceCheckedSet.delete(normalizedProjectCode);
                    targetCheckedSet.delete(normalizedProjectCode);
                    sourceLookup.delete(normalizedProjectCode);
                    existingRow.remove();

                    const nextRow = createRowElementFromMarkup(movingToSelected
                        ? getCachedRowMarkup(project, 'selected', false)
                        : getCachedRowMarkup(project, 'available', false));

                    targetLookup.set(normalizedProjectCode, nextRow);
                    fragment.appendChild(nextRow);
                });

                if (fragment.childNodes.length > 0) {
                    targetBody.appendChild(fragment);
                }

                if (!sourceBody.firstElementChild) {
                    sourceCheckedSet.clear();
                }

                syncShuttleSummary();
            };

            const handleRowToggle = (event, checkedSet, checkboxSelector) => {
                const row = event.target.closest('tr[data-project-code]');
                if (!row || event.target.closest('input, button, a, label, select, textarea')) {
                    return;
                }

                const checkbox = row.querySelector(checkboxSelector);
                if (!checkbox) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                updateCheckedState(checkedSet, checkbox);
                syncToggleStates();
            };

            const handleRowTransfer = (event, callback) => {
                const row = event.target.closest('tr[data-project-code]');
                if (!row || event.target.closest('input, button, a, label, select, textarea')) {
                    return;
                }

                callback(row.dataset.projectCode || '');
            };

            const moveCheckedRight = () => {
                moveProjectsBetweenLists(Array.from(availableChecked), 'right');
            };

            const moveAllRight = () => {
                BATCH_UPLOAD_PROJECTS.forEach((project) => selectedCodes.add(project.project_code));
                availableChecked.clear();
                selectedChecked.clear();
                renderShuttle();
            };

            const moveCheckedLeft = () => {
                moveProjectsBetweenLists(Array.from(selectedChecked), 'left');
            };

            const moveAllLeft = () => {
                selectedCodes.clear();
                availableChecked.clear();
                selectedChecked.clear();
                renderShuttle();
            };

            availableBody.addEventListener('change', (event) => {
                const checkbox = event.target.closest('[data-batch-available-checkbox]');
                if (!checkbox) {
                    return;
                }

                updateCheckedState(availableChecked, checkbox);
                syncToggleStates();
            });

            selectedBody.addEventListener('change', (event) => {
                const checkbox = event.target.closest('[data-batch-selected-checkbox]');
                if (!checkbox) {
                    return;
                }

                updateCheckedState(selectedChecked, checkbox);
                syncToggleStates();
            });

            availableBody.addEventListener('click', (event) => handleRowToggle(event, availableChecked, '[data-batch-available-checkbox]'));
            selectedBody.addEventListener('click', (event) => handleRowToggle(event, selectedChecked, '[data-batch-selected-checkbox]'));

            availableBody.addEventListener('dblclick', (event) => handleRowTransfer(event, (projectCode) => {
                if (!projectCode) {
                    return;
                }

                moveProjectsBetweenLists([projectCode], 'right');
            }));

            selectedBody.addEventListener('dblclick', (event) => handleRowTransfer(event, (projectCode) => {
                if (!projectCode) {
                    return;
                }

                moveProjectsBetweenLists([projectCode], 'left');
            }));

            availableToggleAll.addEventListener('change', (event) => {
                const shouldCheck = event.target.checked;
                availableBody.querySelectorAll('[data-batch-available-checkbox]').forEach((checkbox) => {
                    checkbox.checked = shouldCheck;
                    if (shouldCheck) {
                        availableChecked.add(checkbox.value);
                    } else {
                        availableChecked.delete(checkbox.value);
                    }
                });
                syncToggleStates();
            });

            selectedToggleAll.addEventListener('change', (event) => {
                const shouldCheck = event.target.checked;
                selectedBody.querySelectorAll('[data-batch-selected-checkbox]').forEach((checkbox) => {
                    checkbox.checked = shouldCheck;
                    if (shouldCheck) {
                        selectedChecked.add(checkbox.value);
                    } else {
                        selectedChecked.delete(checkbox.value);
                    }
                });
                syncToggleStates();
            });

            document.getElementById('batchUploadMoveSelectedRight')?.addEventListener('click', moveCheckedRight);
            document.getElementById('batchUploadMoveAllRight')?.addEventListener('click', moveAllRight);
            document.getElementById('batchUploadMoveSelectedLeft')?.addEventListener('click', moveCheckedLeft);
            document.getElementById('batchUploadMoveAllLeft')?.addEventListener('click', moveAllLeft);

            batchUploadModalCache.shuttleController = {
                resetSelections: () => {
                    selectedCodes.clear();
                    availableChecked.clear();
                    selectedChecked.clear();
                    renderShuttle();
                },
            };

            shuttleRoot.dataset.batchShuttleReady = '1';
            batchUploadShuttleInitialized = true;
            renderShuttle();
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

        function setBatchUploadFilterPanelHeight(form) {
            if (!form) {
                return;
            }

            const panel = form.querySelector('.fur-batch-filter-panel');
            if (!panel) {
                return;
            }

            panel.hidden = form.classList.contains('collapsed');
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
            const expandedHeight = `${body.scrollHeight}px`;
            if (isCollapsed) {
                form.classList.remove('collapsed');
                body.style.maxHeight = '0px';
                void body.offsetHeight;
                body.style.maxHeight = expandedHeight;
            } else {
                body.style.maxHeight = expandedHeight;
                void body.offsetHeight;
                form.classList.add('collapsed');
                body.style.maxHeight = '0px';
            }

            const nextCollapsed = !isCollapsed;
            button.setAttribute('aria-expanded', nextCollapsed ? 'false' : 'true');
        }

        function toggleBatchUploadFilterPanel(button) {
            const form = button.closest('.fur-batch-filter-form');
            if (!form) {
                return;
            }

            const panel = form.querySelector('.fur-batch-filter-panel');
            if (!panel) {
                return;
            }

            form.querySelectorAll('[data-stacked-filter]').forEach((stackedFilter) => {
                if (typeof stackedFilter.__closeDropdown === 'function') {
                    stackedFilter.__closeDropdown();
                }
            });

            form.classList.toggle('collapsed');
            setBatchUploadFilterPanelHeight(form);

            const nextCollapsed = form.classList.contains('collapsed');
            button.setAttribute('aria-expanded', nextCollapsed ? 'false' : 'true');
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
                const isModalStackedFilter = Boolean(stackedFilter.closest('.fur-batch-modal'));

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

                let dropdownPositionFrame = null;
                let dropdownPositionListenersAttached = false;
                const requestPositionDropdownMenu = () => {
                    if (dropdownPositionFrame !== null) {
                        return;
                    }

                    dropdownPositionFrame = requestAnimationFrame(() => {
                        dropdownPositionFrame = null;
                        positionDropdownMenu();
                    });
                };
                const handleDropdownViewportChange = () => requestPositionDropdownMenu();
                const attachDropdownPositionListeners = () => {
                    if (dropdownPositionListenersAttached) {
                        return;
                    }

                    dropdownPositionListenersAttached = true;
                    window.addEventListener('resize', handleDropdownViewportChange);
                    document.addEventListener('scroll', handleDropdownViewportChange, true);
                };
                const detachDropdownPositionListeners = () => {
                    if (!dropdownPositionListenersAttached) {
                        return;
                    }

                    dropdownPositionListenersAttached = false;
                    window.removeEventListener('resize', handleDropdownViewportChange);
                    document.removeEventListener('scroll', handleDropdownViewportChange, true);
                };

                const closeDropdown = () => {
                    detachDropdownPositionListeners();
                    dropdownMenu.classList.remove('is-open');
                    dropdownToggle.classList.remove('is-open');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                    dropdownMenu.style.left = '';
                    dropdownMenu.style.top = '';
                    dropdownMenu.style.width = '';
                    dropdownMenu.style.maxHeight = '';
                    dropdownMenu.style.zIndex = '';
                    searchState.value = '';

                    if (dropdownPositionFrame !== null) {
                        cancelAnimationFrame(dropdownPositionFrame);
                        dropdownPositionFrame = null;
                    }
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
                    dropdownMenu.style.zIndex = isModalStackedFilter ? '1455' : '';
                    attachDropdownPositionListeners();
                    requestPositionDropdownMenu();
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
                    requestPositionDropdownMenu();
                };

                const renderDropdownOptions = ({ preserveSearchFocus = false } = {}) => {
                    const options = getSelectOptions().filter((optionElement) => optionElement.value.trim() !== '');
                    const normalizedSearch = searchState.value.trim().toLowerCase();
                    const filteredOptions = normalizedSearch === ''
                        ? options
                        : options.filter((optionElement) => getOptionLabel(optionElement).toLowerCase().includes(normalizedSearch));
                    const activeSearchInput = dropdownMenu.querySelector('.dashboard-stacked-filter-search-input');
                    const shouldRestoreSearchFocus = preserveSearchFocus || document.activeElement === activeSearchInput;
                    const previousSelectionStart = shouldRestoreSearchFocus ? activeSearchInput?.selectionStart : null;
                    const previousSelectionEnd = shouldRestoreSearchFocus ? activeSearchInput?.selectionEnd : null;
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
                            renderDropdownOptions({ preserveSearchFocus: true });
                            requestPositionDropdownMenu();
                        });

                        searchField.appendChild(searchIcon);
                        searchField.appendChild(searchInput);
                        searchWrap.appendChild(searchField);
                        dropdownMenu.appendChild(searchWrap);

                        if (shouldRestoreSearchFocus) {
                            requestAnimationFrame(() => {
                                searchInput.focus({ preventScroll: true });

                                const selectionStart = Number.isInteger(previousSelectionStart)
                                    ? Math.min(previousSelectionStart, searchInput.value.length)
                                    : searchInput.value.length;
                                const selectionEnd = Number.isInteger(previousSelectionEnd)
                                    ? Math.min(previousSelectionEnd, searchInput.value.length)
                                    : selectionStart;

                                searchInput.setSelectionRange(selectionStart, selectionEnd);
                            });
                        }
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

        function rebuildStandardCityOptions(provinceSelectId, citySelectId) {
            const provinceSelect = document.getElementById(provinceSelectId);
            const citySelect = document.getElementById(citySelectId);
            const cityStackedFilter = citySelect ? citySelect.closest('[data-stacked-filter]') : null;

            if (!provinceSelect || !citySelect) {
                return;
            }

            const selectedProvinces = Array.isArray(provinceSelect.__selectionOrder)
                ? provinceSelect.__selectionOrder.filter((value) => Array.from(provinceSelect.selectedOptions || []).some((option) => option.value.trim() === value))
                : Array.from(provinceSelect.selectedOptions || []).map((option) => String(option.value || '').trim()).filter(Boolean);
            const selectedCities = Array.from(citySelect.selectedOptions || [])
                .map((option) => String(option.value || '').trim())
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
                selectedCities.filter((value) => nextCities.includes(value))
            );

            setStackedFilterEmptyMenuText(
                citySelectId,
                selectedProvinces.length ? 'No city/municipality options available.' : 'Select at least one province first.'
            );

            if (cityStackedFilter && typeof cityStackedFilter.__refreshFilterUi === 'function') {
                cityStackedFilter.__refreshFilterUi();
            }
        }

        function rebuildStandardBarangayOptions(citySelectId, barangaySelectId) {
            const citySelect = document.getElementById(citySelectId);
            const barangaySelect = document.getElementById(barangaySelectId);
            const barangayStackedFilter = barangaySelect ? barangaySelect.closest('[data-stacked-filter]') : null;

            if (!citySelect || !barangaySelect) {
                return;
            }

            const selectedCities = Array.isArray(citySelect.__selectionOrder)
                ? citySelect.__selectionOrder.filter((value) => Array.from(citySelect.selectedOptions || []).some((option) => option.value.trim() === value))
                : Array.from(citySelect.selectedOptions || []).map((option) => String(option.value || '').trim()).filter(Boolean);
            const selectedBarangays = Array.from(barangaySelect.selectedOptions || [])
                .map((option) => String(option.value || '').trim())
                .filter(Boolean);
            const nextBarangays = [];
            const seenBarangays = new Set();

            selectedCities.forEach((city) => {
                (FUND_UTILIZATION_BARANGAY_MAP[city] || []).forEach((barangay) => {
                    const normalizedBarangay = String(barangay || '').trim();
                    if (normalizedBarangay === '') {
                        return;
                    }

                    const dedupeKey = normalizedBarangay.toLowerCase();
                    if (!seenBarangays.has(dedupeKey)) {
                        seenBarangays.add(dedupeKey);
                        nextBarangays.push(normalizedBarangay);
                    }
                });
            });

            replaceSelectOptions(
                barangaySelect,
                nextBarangays,
                selectedBarangays.filter((value) => nextBarangays.includes(value))
            );

            setStackedFilterEmptyMenuText(
                barangaySelectId,
                selectedCities.length ? 'No barangay options available.' : 'Select at least one city/municipality first.'
            );

            if (barangayStackedFilter && typeof barangayStackedFilter.__refreshFilterUi === 'function') {
                barangayStackedFilter.__refreshFilterUi();
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

        function rebuildDependentBarangayOptions() {
            rebuildStandardBarangayOptions('fund_utilization_city', 'fund_utilization_barangay');
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

            if (window.AppUI && typeof window.AppUI.suppressPageLoaderForDownload === 'function') {
                window.AppUI.suppressPageLoaderForDownload();
            }

            window.location.href = url.toString();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initializeStackedFilters();

            const forms = document.querySelectorAll('.project-filter-form');
            forms.forEach((form) => {
                const toggleButton = form.querySelector('.project-filter-toggle');
                form.classList.remove('collapsed');
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', 'true');
                }
                setProjectFilterBodyHeight(form);
            });

            const batchUploadFilterForm = document.querySelector('.fur-batch-filter-form');
            if (batchUploadFilterForm) {
                const toggleButton = batchUploadFilterForm.querySelector('.fur-batch-filter-toggle');
                batchUploadFilterForm.classList.remove('collapsed');
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', 'true');
                }
                setBatchUploadFilterPanelHeight(batchUploadFilterForm);
            }

            const provinceSelect = document.getElementById('fund_utilization_province');
            if (provinceSelect) {
                provinceSelect.addEventListener('change', () => {
                    rebuildDependentCityOptions();
                    rebuildDependentBarangayOptions();
                });
            }

            const citySelect = document.getElementById('fund_utilization_city');
            if (citySelect) {
                citySelect.addEventListener('change', rebuildDependentBarangayOptions);
            }

            rebuildDependentCityOptions();
            rebuildDependentBarangayOptions();
            rebuildStandardCityOptions('batch_upload_province', 'batch_upload_city');
            rebuildStandardBarangayOptions('batch_upload_city', 'batch_upload_barangay');

            const batchProvinceSelect = document.getElementById('batch_upload_province');
            if (batchProvinceSelect) {
                batchProvinceSelect.addEventListener('change', () => {
                    rebuildStandardCityOptions('batch_upload_province', 'batch_upload_city');
                    rebuildStandardBarangayOptions('batch_upload_city', 'batch_upload_barangay');
                });
            }

            const batchUploadDocumentFiles = document.getElementById('batchUploadDocumentFiles');
            const batchUploadDocumentList = document.getElementById('batchUploadDocumentList');
            const batchUploadDocumentSubmitRow = document.getElementById('batchUploadDocumentSubmitRow');
            const batchUploadDocumentSubmitBtn = document.getElementById('batchUploadDocumentSubmitBtn');
            if (batchUploadDocumentFiles && batchUploadDocumentList && batchUploadDocumentSubmitRow && batchUploadDocumentSubmitBtn) {
                const batchUploadDocumentPanel = batchUploadDocumentFiles.closest('.fur-batch-document-panel');
                const batchDocumentState = batchUploadModalCache.documents;
                const batchDocumentKey = (file) => [file.name, file.size, file.lastModified, file.type].join('::');
                const formatBatchDocumentSize = (bytes) => {
                    if (!Number.isFinite(bytes) || bytes <= 0) {
                        return '0 B';
                    }

                    const units = ['B', 'KB', 'MB', 'GB'];
                    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                    const value = bytes / (1024 ** exponent);
                    return `${value >= 10 || exponent === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[exponent]}`;
                };
                const escapeBatchDocumentHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
                const syncBatchDocumentInputFiles = () => {
                    if (typeof DataTransfer !== 'function') {
                        return;
                    }

                    const dataTransfer = new DataTransfer();
                    batchDocumentState.files.forEach((file) => dataTransfer.items.add(file));
                    batchUploadDocumentFiles.files = dataTransfer.files;
                };
                const getBatchDocumentObjectUrl = (file) => getBatchUploadDocumentObjectUrl(file);
                const revokeBatchDocumentObjectUrl = (fileKey) => {
                    const objectUrl = batchDocumentState.objectUrls.get(fileKey);
                    if (!objectUrl) {
                        return;
                    }

                    URL.revokeObjectURL(objectUrl);
                    batchDocumentState.objectUrls.delete(fileKey);
                };
                const updateBatchUploadDocumentMeta = () => {
                    const files = batchDocumentState.files;
                    if (files.length === 0) {
                        batchUploadDocumentList.hidden = true;
                        batchUploadDocumentList.innerHTML = '';
                        batchUploadDocumentSubmitRow.hidden = true;
                        batchUploadDocumentPanel?.classList.remove('has-files');
                        return;
                    }

                    const previewNames = files.slice(0, 3).map((file) => file.name);
                    const remainingCount = files.length - previewNames.length;
                    const selectionSummary = remainingCount > 0
                        ? `${files.length} files selected: ${previewNames.join(', ')} + ${remainingCount} more`
                        : `${files.length} file${files.length === 1 ? '' : 's'} selected: ${previewNames.join(', ')}`;

                    batchUploadDocumentList.hidden = false;
                    batchUploadDocumentSubmitRow.hidden = false;
                    batchUploadDocumentPanel?.classList.add('has-files');
                    batchUploadDocumentList.innerHTML = `
                        <div class="fur-batch-document-file-list-header">
                            <div>
                                <div class="fur-batch-document-file-list-title">Selected Documents</div>
                                <div class="fur-batch-document-file-list-summary">${escapeBatchDocumentHtml(selectionSummary)}</div>
                            </div>
                            <button type="button" class="fur-batch-document-clear-btn" data-batch-document-clear-all="1">Clear All</button>
                        </div>
                        <div class="fur-batch-document-file-items">
                            ${files.map((file) => {
                                const fileKey = batchDocumentKey(file);
                                const objectUrl = getBatchDocumentObjectUrl(file);
                                return `
                                    <div class="fur-batch-document-file-item" data-batch-document-key="${escapeBatchDocumentHtml(fileKey)}">
                                        <div class="fur-batch-document-file-copy">
                                            <div class="fur-batch-document-file-name">${escapeBatchDocumentHtml(file.name)}</div>
                                            <div class="fur-batch-document-file-meta">${escapeBatchDocumentHtml(formatBatchDocumentSize(file.size))}${file.type ? ` • ${escapeBatchDocumentHtml(file.type)}` : ''}</div>
                                        </div>
                                        <div class="fur-batch-document-file-actions">
                                            <button type="button" class="fur-batch-document-view-btn" data-batch-document-view="${escapeBatchDocumentHtml(fileKey)}">View</button>
                                            <button type="button" class="fur-batch-document-remove-btn" data-batch-document-remove="${escapeBatchDocumentHtml(fileKey)}">Remove</button>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    `;
                };
                const removeBatchDocumentFile = (fileKey) => {
                    batchDocumentState.files = batchDocumentState.files.filter((file) => batchDocumentKey(file) !== fileKey);
                    if (batchUploadModalCache.previewState.fileKey === fileKey) {
                        closeBatchUploadDocumentPreviewModal();
                    }
                    revokeBatchDocumentObjectUrl(fileKey);
                    syncBatchDocumentInputFiles();
                    updateBatchUploadDocumentMeta();
                    persistBatchUploadDocuments(batchDocumentState.files);
                };
                const clearBatchDocumentFiles = () => {
                    closeBatchUploadDocumentPreviewModal();
                    Array.from(batchDocumentState.objectUrls.keys()).forEach(revokeBatchDocumentObjectUrl);
                    batchDocumentState.files = [];
                    batchUploadDocumentFiles.value = '';
                    syncBatchDocumentInputFiles();
                    updateBatchUploadDocumentMeta();
                    persistBatchUploadDocuments(batchDocumentState.files);
                };

                batchUploadDocumentFiles.addEventListener('change', () => {
                    const incomingFiles = Array.from(batchUploadDocumentFiles.files || []);
                    if (incomingFiles.length === 0) {
                        return;
                    }

                    const acceptedFiles = incomingFiles.filter(isValidBatchUploadDocumentFile);
                    const rejectedFiles = incomingFiles.filter((file) => !isValidBatchUploadDocumentFile(file));

                    if (rejectedFiles.length > 0) {
                        const rejectedFileNames = rejectedFiles.map((file) => file.name).join(', ');
                        alert(`Only PDF files up to 50 MB are allowed. Rejected: ${rejectedFileNames}`);
                    }

                    if (acceptedFiles.length === 0) {
                        batchUploadDocumentFiles.value = '';
                        syncBatchDocumentInputFiles();
                        return;
                    }

                    const existingKeys = new Set(batchDocumentState.files.map(batchDocumentKey));
                    acceptedFiles.forEach((file) => {
                        const fileKey = batchDocumentKey(file);
                        if (!existingKeys.has(fileKey)) {
                            existingKeys.add(fileKey);
                            batchDocumentState.files.push(file);
                        }
                    });

                    syncBatchDocumentInputFiles();
                    updateBatchUploadDocumentMeta();
                    persistBatchUploadDocuments(batchDocumentState.files);
                });

                batchUploadDocumentList.addEventListener('click', (event) => {
                    const clearButton = event.target.closest('[data-batch-document-clear-all="1"]');
                    if (clearButton) {
                        clearBatchDocumentFiles();
                        return;
                    }

                    const viewButton = event.target.closest('[data-batch-document-view]');
                    if (viewButton) {
                        const fileKey = viewButton.dataset.batchDocumentView || '';
                        const previewFile = batchDocumentState.files.find((file) => batchDocumentKey(file) === fileKey);
                        if (!previewFile) {
                            return;
                        }

                        openBatchUploadDocumentPreviewModal(previewFile, getBatchDocumentObjectUrl(previewFile));
                        return;
                    }

                    const removeButton = event.target.closest('[data-batch-document-remove]');
                    if (removeButton) {
                        removeBatchDocumentFile(removeButton.dataset.batchDocumentRemove || '');
                    }
                });

                batchUploadDocumentSubmitBtn.addEventListener('click', () => {
                    if (batchDocumentState.files.length === 0 || batchUploadDocumentSubmitBtn.disabled) {
                        return;
                    }

                    openBatchUploadQuarterModal();
                });

                window.addEventListener('beforeunload', () => {
                    Array.from(batchDocumentState.objectUrls.keys()).forEach(revokeBatchDocumentObjectUrl);
                });

                restorePersistedBatchUploadDocuments().then((restoredFiles) => {
                    const validRestoredFiles = restoredFiles.filter(isValidBatchUploadDocumentFile);
                    if (validRestoredFiles.length !== restoredFiles.length) {
                        persistBatchUploadDocuments(validRestoredFiles);
                    }

                    if (!validRestoredFiles.length) {
                        updateBatchUploadDocumentMeta();
                        return;
                    }

                    const restoredKeys = new Set();
                    batchDocumentState.files = [];
                    validRestoredFiles.forEach((file) => {
                        const fileKey = batchDocumentKey(file);
                        if (!restoredKeys.has(fileKey)) {
                            restoredKeys.add(fileKey);
                            batchDocumentState.files.push(file);
                        }
                    });

                    syncBatchDocumentInputFiles();
                    updateBatchUploadDocumentMeta();
                });
            }

            const batchCitySelect = document.getElementById('batch_upload_city');
            if (batchCitySelect) {
                batchCitySelect.addEventListener('change', () => rebuildStandardBarangayOptions('batch_upload_city', 'batch_upload_barangay'));
            }

            document.querySelectorAll('[data-batch-upload-close]').forEach((element) => {
                element.addEventListener('click', resetAndCloseBatchUploadModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    const previewModal = document.getElementById('batchUploadDocumentPreviewModal');
                    if (previewModal && previewModal.classList.contains('is-open')) {
                        closeBatchUploadDocumentPreviewModal();
                        return;
                    }

                    const submitConfirmModal = document.getElementById('batchUploadSubmitConfirmModal');
                    if (submitConfirmModal && submitConfirmModal.classList.contains('is-open')) {
                        closeBatchUploadSubmitConfirmModal();
                        return;
                    }

                    const quarterModal = document.getElementById('batchUploadQuarterModal');
                    if (quarterModal && quarterModal.classList.contains('is-open')) {
                        closeBatchUploadQuarterModal();
                        return;
                    }

                    const checklistModal = document.getElementById('batchUploadDocumentChecklistModal');
                    if (checklistModal && checklistModal.classList.contains('is-open')) {
                        closeBatchUploadDocumentChecklistModal();
                        return;
                    }

                    closeBatchUploadModal();
                }
            });

            if (!shouldAutoOpenBatchUploadModal && BATCH_UPLOAD_PROJECTS.length > 0) {
                const warmupBatchUploadShuttle = () => {
                    if (!batchUploadShuttleInitialized) {
                        initializeBatchUploadShuttle();
                    }
                };

                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(warmupBatchUploadShuttle, { timeout: 1000 });
                } else {
                    window.setTimeout(warmupBatchUploadShuttle, 180);
                }
            }

            initializeBatchUploadDocumentChecklist();
            initializeBatchUploadQuarterModal();
            initializeBatchUploadDocumentPreviewModal();
            initializeBatchUploadSubmitConfirmModal();

            if (shouldAutoOpenBatchUploadModal || readBatchUploadModalOpenState()) {
                openBatchUploadModal();
            }

            window.addEventListener('resize', () => {
                forms.forEach((form) => {
                    if (!form.classList.contains('collapsed')) {
                        setProjectFilterBodyHeight(form);
                    }
                });

                if (batchUploadFilterForm && !batchUploadFilterForm.classList.contains('collapsed')) {
                    setBatchUploadFilterPanelHeight(batchUploadFilterForm);
                }
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
            overflow: hidden;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.14s ease-out, opacity 0.12s ease-out, transform 0.12s ease-out;
            max-height: none;
            will-change: max-height, opacity, transform;
        }

        .project-filter-form.collapsed .project-filter-body {
            max-height: 0;
            opacity: 0;
            transform: translateY(-3px);
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

        .fur-batch-modal {
            position: fixed;
            inset: 0;
            z-index: 1400;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .fur-batch-modal.is-open {
            display: flex;
        }

        .fur-batch-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.46);
        }

        .fur-batch-checklist-modal {
            position: fixed;
            inset: 0;
            z-index: 1505;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .fur-batch-checklist-modal.is-open {
            display: flex;
        }

        .fur-batch-preview-modal {
            z-index: 1515;
        }

        .fur-batch-checklist-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.52);
        }

        .fur-batch-checklist-dialog {
            position: relative;
            z-index: 1;
            width: min(640px, 100%);
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .fur-batch-checklist-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .fur-batch-checklist-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .fur-batch-checklist-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .fur-batch-checklist-header h3 {
            margin: 0;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
        }

        .fur-batch-checklist-header p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.88);
            font-size: 13px;
            line-height: 1.5;
        }

        .fur-batch-checklist-close {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .fur-batch-checklist-body {
            padding: 22px;
            background: #fcfdff;
        }

        .fur-batch-checklist-card {
            padding: 16px 18px;
            border: 1px solid #c9d8ef;
            border-radius: 12px;
            background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
        }

        .fur-batch-checklist-label {
            color: #002C76;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 0.01em;
        }

        .fur-batch-checklist-copy {
            color: #334155;
            font-size: 13px;
            line-height: 1.72;
        }

        .fur-batch-checklist-strong {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .fur-batch-checklist-subtitle {
            color: #475569;
            margin-bottom: 4px;
        }

        .fur-batch-checklist-list {
            margin: 0 0 10px;
            padding-left: 20px;
            color: #475569;
            line-height: 1.72;
            list-style-type: disc;
        }

        .fur-batch-checklist-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 0 22px 22px;
            background: #fcfdff;
        }

        .fur-batch-checklist-cancel,
        .fur-batch-checklist-confirm {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .fur-batch-checklist-cancel {
            background: #e2e8f0;
            color: #475569;
        }

        .fur-batch-checklist-confirm {
            background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);
            color: #ffffff;
        }

        .fur-batch-quarter-dialog {
            width: min(520px, 100%);
        }

        .fur-batch-quarter-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .fur-batch-quarter-label {
            color: #002C76;
            font-size: 13px;
            font-weight: 700;
        }

        .fur-batch-quarter-select {
            width: 100%;
            height: 44px;
            border: 1px solid #c9d8ef;
            border-radius: 10px;
            padding: 0 14px;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
        }

        .fur-batch-quarter-select:focus {
            outline: none;
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.12);
        }

        .fur-batch-preview-dialog {
            width: min(1080px, 100%);
            height: min(86vh, 820px);
            display: flex;
            flex-direction: column;
        }

        .fur-batch-preview-body {
            flex: 1;
            min-height: 0;
            padding: 18px 22px 22px;
            background: #f8fbff;
        }

        .fur-batch-preview-content {
            width: 100%;
            height: 100%;
            min-height: 420px;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fur-batch-preview-frame {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
        }

        .fur-batch-preview-image {
            display: block;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            background: #ffffff;
        }

        .fur-batch-preview-empty {
            width: min(420px, 100%);
            padding: 24px;
            text-align: center;
            color: #475569;
        }

        .fur-batch-preview-empty-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            border-radius: 16px;
            background: rgba(0, 44, 118, 0.08);
            color: #002C76;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .fur-batch-preview-empty-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .fur-batch-preview-empty-copy {
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.6;
        }

        .fur-batch-submit-dialog {
            width: min(620px, 100%);
        }

        .fur-batch-submit-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .fur-batch-submit-summary-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dbe4f0;
        }

        .fur-batch-submit-summary-row:last-of-type {
            padding-bottom: 0;
        }

        .fur-batch-submit-summary-label {
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .fur-batch-submit-summary-value {
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .fur-batch-submit-document-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .fur-batch-submit-document-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 12px;
            background: rgba(0, 44, 118, 0.08);
            border: 1px solid rgba(0, 44, 118, 0.08);
        }

        .fur-batch-submit-document-name {
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .fur-batch-submit-document-view-btn {
            border: none;
            border-radius: 999px;
            padding: 7px 12px;
            background: #ffffff;
            color: #002C76;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1.2;
            flex-shrink: 0;
        }

        .fur-batch-submit-project-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .fur-batch-submit-project-grid {
            display: grid;
            grid-template-columns: minmax(180px, 240px) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(0, 44, 118, 0.08);
            border: 1px solid rgba(0, 44, 118, 0.08);
        }

        .fur-batch-submit-project-grid-head {
            background: #eef4ff;
            border-color: #dbe4f0;
        }

        .fur-batch-submit-project-grid-more {
            background: rgba(15, 23, 42, 0.04);
            border-color: rgba(148, 163, 184, 0.22);
        }

        .fur-batch-submit-project-head {
            color: #475569;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .fur-batch-submit-project-code {
            color: #002C76;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.03em;
            overflow-wrap: anywhere;
        }

        .fur-batch-submit-project-title {
            color: #0f172a;
            font-size: 11px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .fur-batch-submit-project-more {
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        .fur-batch-modal-dialog {
            position: relative;
            z-index: 1;
            width: min(1380px, 100%);
            max-height: min(92vh, 900px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(148, 163, 184, 0.28);
            contain: layout paint;
        }

        .fur-batch-modal-content {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
        }

        .fur-batch-modal-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            background: #ffffff;
            padding-bottom: 24px;
        }

        .fur-batch-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 24px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #eef4ff 0%, #f8fafc 55%, #ffffff 100%);
        }

        .fur-batch-modal-header h3 {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
        }

        .fur-batch-modal-header p {
            margin: 6px 0 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
        }

        .fur-batch-modal-close {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            color: #0f172a;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .fur-batch-modal-close:hover {
            background: rgba(15, 23, 42, 0.14);
            transform: translateY(-1px);
        }

        .fur-batch-modal-body {
            padding: 22px 24px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .fur-batch-filter-form {
            margin: 16px 24px 0;
            border: 1px solid rgba(191, 219, 254, 0.95);
            border-radius: 18px;
            overflow: hidden;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.96) 100%);
        }

        .fur-batch-filter-toggle {
            width: 100%;
            border: none;
            background: transparent;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 24px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .fur-batch-filter-toggle-copy {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .fur-batch-filter-chevron {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: transform 0.2s ease;
        }

        .fur-batch-filter-panel {
            display: block;
            contain: layout paint;
        }

        .fur-batch-filter-form.collapsed .fur-batch-filter-panel {
            display: none;
        }

        .fur-batch-filter-form.collapsed .fur-batch-filter-chevron {
            transform: rotate(180deg);
        }

        .fur-batch-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 16px;
        }

        .fur-batch-filter-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .fur-batch-filter-field span {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .fur-batch-filter-field input,
        .fur-batch-filter-field select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            color: #0f172a;
            font-size: 13px;
            padding: 11px 12px;
            box-sizing: border-box;
        }

        .fur-batch-filter-field select {
            min-height: 142px;
        }

        .fur-batch-filter-hint {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .fur-batch-modal-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .fur-batch-modal-reset,
        .fur-batch-modal-secondary,
        .fur-batch-modal-primary {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .fur-batch-modal-reset {
            background: #e2e8f0;
            color: #0f172a;
        }

        .fur-batch-modal-secondary {
            background: #64748b;
            color: #ffffff;
        }

        .fur-batch-modal-primary {
            background: linear-gradient(180deg, #003a99 0%, #002C76 100%);
            color: #ffffff;
            box-shadow: 0 14px 26px rgba(0, 44, 118, 0.2);
        }

        .fur-batch-project-list {
            overflow: visible;
            padding: 22px 24px 26px;
            background: #f8fbff;
            contain: layout paint;
        }

        .fur-batch-shuttle {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 84px minmax(0, 1fr);
            gap: 22px;
            align-items: stretch;
        }

        .fur-batch-shuttle-panel {
            min-width: 0;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(191, 219, 254, 0.95);
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            contain: layout paint;
        }

        .fur-batch-shuttle-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(219, 228, 240, 0.9);
            background: #eef4ff;
        }

        .fur-batch-shuttle-panel-header h4 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .fur-batch-shuttle-panel-header p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .fur-batch-shuttle-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            padding: 7px 12px;
            border-radius: 999px;
            background: linear-gradient(180deg, #003a99 0%, #002C76 100%);
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 6px 12px rgba(0, 44, 118, 0.14);
        }

        .fur-batch-shuttle-table-wrap {
            position: relative;
            min-height: 460px;
            max-height: 560px;
            overflow: auto;
            padding: 0 6px 6px;
            contain: layout paint;
        }

        .fur-batch-document-panel {
            margin: 18px 24px 0;
            padding: 16px;
            border: 1px solid rgba(0, 44, 118, 0.12);
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
            contain: layout paint;
        }

        .fur-batch-document-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
            align-items: start;
        }

        .fur-batch-document-panel.has-files .fur-batch-document-layout {
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: 16px;
        }

        .fur-batch-document-main {
            min-width: 0;
        }

        .fur-batch-document-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .fur-batch-document-panel-header h4 {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            font-family: inherit;
        }

        .fur-batch-document-panel-header p {
            margin: 4px 0 0;
            color: #475569;
            font-size: 11px;
            line-height: 1.45;
            font-family: inherit;
        }

        .fur-batch-document-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(0, 44, 118, 0.08);
            color: #002C76;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
            font-family: inherit;
        }

        .fur-batch-document-dropzone {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border: 1px dashed rgba(0, 44, 118, 0.22);
            border-radius: 14px;
            background: #f8fbff;
        }

        .fur-batch-document-copy {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .fur-batch-document-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(0, 44, 118, 0.08);
            color: #002C76;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .fur-batch-document-copy h5 {
            margin: 0;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            font-family: inherit;
        }

        .fur-batch-document-copy p {
            margin: 4px 0 0;
            color: #475569;
            font-size: 11px;
            line-height: 1.4;
            font-family: inherit;
        }

        .fur-batch-document-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #002C76;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 6px 12px rgba(0, 44, 118, 0.12);
            font-family: inherit;
        }

        .fur-batch-document-input {
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

        .fur-batch-document-file-list {
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: #ffffff;
            overflow: hidden;
            align-self: stretch;
            min-height: 100%;
        }

        .fur-batch-document-submit-row {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
        }

        .fur-batch-document-submit-btn {
            border: none;
            border-radius: 10px;
            padding: 11px 18px;
            background: linear-gradient(180deg, #003a99 0%, #002C76 100%);
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 16px rgba(0, 44, 118, 0.16);
        }

        .fur-batch-document-submit-btn:disabled {
            background: #94a3b8;
            color: rgba(255, 255, 255, 0.92);
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.9;
        }

        .fur-batch-document-file-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fbff;
        }

        .fur-batch-document-file-list-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .fur-batch-document-file-list-summary {
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 3px;
        }

        .fur-batch-document-clear-btn {
            border: none;
            background: rgba(220, 38, 38, 0.08);
            color: #b91c1c;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .fur-batch-document-file-items {
            display: flex;
            flex-direction: column;
        }

        .fur-batch-document-file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 14px;
            border-top: 1px solid #eef2f7;
        }

        .fur-batch-document-file-item:first-child {
            border-top: none;
        }

        .fur-batch-document-file-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .fur-batch-document-file-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .fur-batch-document-file-meta {
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }

        .fur-batch-document-file-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .fur-batch-document-view-btn,
        .fur-batch-document-remove-btn {
            border: none;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .fur-batch-document-view-btn {
            background: rgba(0, 44, 118, 0.08);
            color: #002C76;
        }

        .fur-batch-document-remove-btn {
            background: rgba(220, 38, 38, 0.08);
            color: #b91c1c;
        }

        .fur-batch-shuttle-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .fur-batch-shuttle-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 10px 12px;
            background: #eef4ff;
            color: #1e293b;
            font-size: 11px;
            font-weight: 800;
            text-align: left;
            border-bottom: 1px solid #dbe4f0;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .fur-batch-shuttle-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #0f172a;
            font-size: 11px;
            vertical-align: top;
            background: rgba(255, 255, 255, 0.9);
        }

        .fur-batch-shuttle-table tbody tr:nth-child(odd) td {
            background: rgba(255, 255, 255, 0.98);
        }

        .fur-batch-shuttle-table tbody tr:nth-child(even) td {
            background: rgba(248, 251, 255, 0.98);
        }

        .fur-batch-shuttle-table tbody tr td:first-child {
            text-align: center;
            vertical-align: middle;
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .fur-batch-shuttle-table tbody tr td:last-child {
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .fur-batch-shuttle-table tbody tr:hover {
            background: #eaf3ff;
        }

        .fur-batch-shuttle-table tbody tr:hover td {
            background: transparent;
        }

        .fur-batch-shuttle-table tbody tr {
            cursor: pointer;
        }

        .fur-batch-row-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #002C76;
            cursor: pointer;
        }

        .fur-batch-shuttle-project-cell {
            display: flex;
            flex-direction: column;
            gap: 0;
            min-width: 0;
            border: 1px solid rgba(0, 44, 118, 0.16);
            border-radius: 14px;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(245, 249, 255, 0.98) 100%);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
        }

        .fur-batch-shuttle-field {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            padding: 8px 10px;
        }

        .fur-batch-shuttle-field-center {
            align-items: center;
            text-align: center;
        }

        .fur-batch-shuttle-label {
            color: #002C76;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-family: inherit;
        }

        .fur-batch-project-code {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 4px 8px;
            border-radius: 999px;
            background: linear-gradient(180deg, #dbeafe 0%, #eef4ff 100%);
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
            font-family: inherit;
        }

        .fur-batch-shuttle-title {
            margin-top: 0;
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.3;
            overflow-wrap: anywhere;
            font-family: inherit;
        }

        .fur-batch-shuttle-meta {
            color: #475569;
            font-size: 10px;
            line-height: 1.3;
            overflow-wrap: anywhere;
            font-family: inherit;
        }

        .fur-batch-shuttle-year {
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
            font-family: inherit;
        }

        .fur-batch-project-link {
            margin: 18px 24px 24px;
            display: inline-flex;
            padding: 22px 0 0;
            gap: 8px;
            color: #002C76;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(0, 44, 118, 0.08);
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
            font-family: inherit;
        }

        .fur-batch-project-link:hover {
            color: #001f59;
            background: rgba(0, 44, 118, 0.14);
            transform: translateY(-1px);
        }

        .fur-batch-shuttle-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .fur-batch-shuttle-btn {
            width: 58px;
            height: 58px;
            border: 1px solid rgba(148, 163, 184, 0.32);
            border-radius: 18px;
            background: #ffffff;
            color: #002C76;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
            transition: background-color 0.08s linear, border-color 0.08s linear, color 0.08s linear;
        }

        .fur-batch-shuttle-btn:hover {
            border-color: #002C76;
            background: #eef4ff;
            color: #001f59;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
        }

        .fur-batch-empty-state {
            padding: 28px 22px;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            text-align: center;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            margin: 16px;
        }

        body.fur-batch-modal-open {
            overflow: hidden;
        }

        @media (max-width: 1100px) {
            .dashboard-filter-grid {
                grid-template-columns: repeat(2, minmax(200px, 1fr)) !important;
            }

            .fur-batch-shuttle {
                grid-template-columns: 1fr;
            }

            .fur-batch-shuttle-controls {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .fur-batch-document-panel.has-files .fur-batch-document-layout {
                grid-template-columns: 1fr;
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

            .fur-batch-modal {
                padding: 12px;
            }

            .fur-batch-modal-dialog {
                max-height: calc(100vh - 24px);
                border-radius: 16px;
            }

            .fur-batch-modal-header,
            .fur-batch-modal-body,
            .fur-batch-project-list {
                padding-left: 16px;
                padding-right: 16px;
            }

            .fur-batch-shuttle-table-wrap {
                min-height: 280px;
                max-height: 360px;
            }

            .fur-batch-modal-actions {
                justify-content: stretch;
            }

            .fur-batch-modal-reset,
            .fur-batch-modal-secondary,
            .fur-batch-modal-primary {
                width: 100%;
            }

            .fur-batch-shuttle-btn {
                width: 48px;
                height: 48px;
                border-radius: 14px;
            }
        }
    </style>
@endsection
