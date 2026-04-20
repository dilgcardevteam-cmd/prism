@extends('layouts.dashboard')

@section('title', $formMeta['page_title'])
@section('page-title', $formMeta['page_title'])

@section('content')
    @php
        $multiFilterKeys = [
            'province',
            'city_municipality',
            'barangay',
            'program',
            'funding_year',
            'project_type',
            'project_status',
        ];
    @endphp

    <style>
        .project-filter-form { background: #ffffff; padding: 16px 18px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .project-filter-toggle { width: 100%; border: 0; background: transparent; color: #002c76; font-size: 16px; font-weight: 700; margin: 0 0 14px; padding: 0; display: flex; align-items: center; gap: 8px; text-align: left; cursor: pointer; }
        .project-filter-chevron { margin-left: auto; color: #6b7280; font-size: 14px; transition: transform 0.2s ease; }
        .project-filter-body { overflow: hidden; max-height: 1200px; opacity: 1; transform: translateY(0); transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease; will-change: max-height, opacity, transform; }
        .project-filter-form.collapsed .project-filter-body { max-height: 0; opacity: 0; transform: translateY(-6px); pointer-events: none; }
        .project-filter-form.collapsed .project-filter-chevron { transform: rotate(180deg); }
        .dashboard-filter-grid { display: grid; grid-template-columns: repeat(3, minmax(200px, 1fr)); gap: 12px 16px; align-items: end; }
        .dashboard-stacked-filter-source { display: none; }
        .dashboard-stacked-filter-dropdown { position: relative; }
        .dashboard-stacked-filter-toggle { min-height: 34px; width: 100%; border: 1px solid #d1d5db; border-radius: 7px; background: #ffffff; color: #111827; padding: 5px 10px; display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
        .dashboard-stacked-filter-toggle:hover { border-color: #9ca3af; }
        .dashboard-stacked-filter-toggle:focus-visible, .dashboard-stacked-filter-toggle.is-open { outline: 0; border-color: #60a5fa; box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2); }
        .dashboard-filter-badge-list { min-height: 20px; display: flex; flex-wrap: wrap; gap: 4px; align-items: center; flex: 1; min-width: 0; }
        .dashboard-filter-badge { display: inline-flex; align-items: center; gap: 4px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; color: #374151; font-size: 11px; font-weight: 500; line-height: 1; padding: 3px 6px; max-width: 100%; }
        .dashboard-filter-badge-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dashboard-filter-badge-remove { border: 0; background: transparent; color: #6b7280; font-size: 11px; line-height: 1; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer; }
        .dashboard-filter-badge-empty { font-size: 12px; color: #6b7280; }
        .dashboard-stacked-filter-chevron { margin-left: auto; color: #6b7280; font-size: 11px; transition: transform 0.2s ease; flex: 0 0 auto; }
        .dashboard-stacked-filter-toggle.is-open .dashboard-stacked-filter-chevron { transform: rotate(180deg); }
        .dashboard-stacked-filter-menu { position: fixed; left: 0; top: 0; display: none; width: auto; background: #ffffff; border: 1px solid #d1d5db; border-radius: 7px; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08); padding: 4px; max-height: 220px; overflow-y: auto; overflow-x: hidden; box-sizing: border-box; z-index: 1250; }
        .dashboard-stacked-filter-menu.is-open { display: block; }
        .dashboard-stacked-filter-option { width: 100%; border: 0; background: transparent; border-radius: 4px; color: #1f2937; padding: 7px 8px; font-size: 12px; font-weight: 400; text-align: left; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; }
        .dashboard-stacked-filter-option:hover { background: #f3f4f6; }
        .dashboard-stacked-filter-option.is-selected { background: #eff6ff; color: #1d4ed8; font-weight: 500; }
        .dashboard-stacked-filter-option-check { visibility: hidden; color: #1d4ed8; font-size: 11px; font-weight: 700; flex: 0 0 auto; }
        .dashboard-stacked-filter-option.is-selected .dashboard-stacked-filter-option-check { visibility: visible; }
        .dashboard-stacked-filter-menu-empty { color: #6b7280; font-size: 12px; padding: 6px 8px; }
        .dashboard-filter-reset { grid-column: 3; display: flex; align-items: end; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .dashboard-filter-reset-link { height: 34px; min-width: 150px; border-radius: 7px; background: linear-gradient(180deg, #003a99 0%, #002c76 100%); color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 600; padding: 0 14px; box-shadow: 0 4px 10px rgba(0, 44, 118, 0.18); transition: box-shadow 0.18s ease, transform 0.18s ease; }
        .dashboard-filter-reset-link:hover { box-shadow: 0 6px 14px rgba(0, 44, 118, 0.24); transform: translateY(-1px); }
        .dashboard-filter-apply-btn { height: 34px; min-width: 150px; border-radius: 7px; border: 0; background: linear-gradient(180deg, #1d4ed8 0%, #1e3a8a 100%); color: #ffffff; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 600; padding: 0 14px; cursor: pointer; box-shadow: 0 4px 10px rgba(29, 78, 216, 0.22); transition: box-shadow 0.18s ease, transform 0.18s ease; }
        .dashboard-filter-apply-btn:hover { background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 14px rgba(29, 78, 216, 0.28); transform: translateY(-1px); }
        @media (max-width: 1100px) { .dashboard-filter-grid { grid-template-columns: repeat(2, minmax(200px, 1fr)); } .dashboard-filter-reset { grid-column: auto; justify-content: flex-start; } }
        @media (max-width: 700px) { .dashboard-filter-grid { grid-template-columns: 1fr; } .dashboard-filter-reset-link, .dashboard-filter-apply-btn { width: 100%; } }
    </style>

    <div class="content-header">
        <h1>{{ $formMeta['list_heading'] }}</h1>
    </div>

    <form method="GET" action="{{ route($formMeta['index_route']) }}" class="dashboard-card project-filter-form collapsed">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
        <button type="button" class="project-filter-toggle" onclick="toggleProjectFilter(this)" aria-expanded="false" aria-controls="project-filter-body">
            <i class="fas fa-filter" aria-hidden="true" style="font-size: 16px;"></i>
            <span>PROJECT FILTER</span>
            <span class="project-filter-chevron"><i class="fas fa-chevron-up"></i></span>
        </button>
        <div id="project-filter-body" class="project-filter-body">
            <div class="dashboard-filter-grid">

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="province" data-badge-container-id="province_badges" data-dropdown-toggle-id="province_dropdown_toggle" data-dropdown-menu-id="province_dropdown_menu" data-empty-badge-text="All">
                    <label for="province_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Province</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="province_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="province_dropdown_menu"><div id="province_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="province_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="province" name="province[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Province" aria-hidden="true">@foreach (($filterOptions['provinces'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['province'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="city_municipality" data-badge-container-id="city_municipality_badges" data-dropdown-toggle-id="city_municipality_dropdown_toggle" data-dropdown-menu-id="city_municipality_dropdown_menu" data-empty-badge-text="All">
                    <label for="city_municipality_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">City/Municipality</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="city_municipality_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="city_municipality_dropdown_menu"><div id="city_municipality_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="city_municipality_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="city_municipality" name="city_municipality[]" multiple class="dashboard-stacked-filter-source" data-filter-label="City/Municipality" aria-hidden="true">@foreach (($filterOptions['cities'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['city_municipality'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="barangay" data-badge-container-id="barangay_badges" data-dropdown-toggle-id="barangay_dropdown_toggle" data-dropdown-menu-id="barangay_dropdown_menu" data-empty-badge-text="All">
                    <label for="barangay_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Barangay</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="barangay_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="barangay_dropdown_menu"><div id="barangay_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="barangay_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="barangay" name="barangay[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Barangay" aria-hidden="true">@foreach (($filterOptions['barangays'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['barangay'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="program" data-badge-container-id="program_badges" data-dropdown-toggle-id="program_dropdown_toggle" data-dropdown-menu-id="program_dropdown_menu" data-empty-badge-text="No program selected.">
                    <label for="program_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Program</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="program_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="program_dropdown_menu"><div id="program_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="program_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="program" name="program[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Program" aria-hidden="true">@foreach (($filterOptions['programs'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['program'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="funding_year" data-badge-container-id="funding_year_badges" data-dropdown-toggle-id="funding_year_dropdown_toggle" data-dropdown-menu-id="funding_year_dropdown_menu" data-empty-badge-text="All">
                    <label for="funding_year_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Funding Year</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="funding_year_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="funding_year_dropdown_menu"><div id="funding_year_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="funding_year_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="funding_year" name="funding_year[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Funding Year" aria-hidden="true">@foreach (($filterOptions['funding_years'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['funding_year'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="project_type" data-badge-container-id="project_type_badges" data-dropdown-toggle-id="project_type_dropdown_toggle" data-dropdown-menu-id="project_type_dropdown_menu" data-empty-badge-text="All">
                    <label for="project_type_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Project Type</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="project_type_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="project_type_dropdown_menu"><div id="project_type_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="project_type_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="project_type" name="project_type[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Project Type" aria-hidden="true">@foreach (($filterOptions['project_types'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['project_type'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="project_status" data-badge-container-id="project_status_badges" data-dropdown-toggle-id="project_status_dropdown_toggle" data-dropdown-menu-id="project_status_dropdown_menu" data-empty-badge-text="All">
                    <label for="project_status_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Project Status</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="project_status_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="project_status_dropdown_menu"><div id="project_status_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="project_status_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="project_status" name="project_status[]" multiple class="dashboard-stacked-filter-source" data-filter-label="Project Status" aria-hidden="true">@foreach (($filterOptions['project_statuses'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['project_status'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-filter-reset">
                    <a href="{{ route($formMeta['index_route']) }}" class="dashboard-filter-reset-link"><i class="fas fa-rotate-left" aria-hidden="true"></i> Reset Filter</a>
                    <button type="submit" class="dashboard-filter-apply-btn"><i class="fas fa-check" aria-hidden="true"></i> Apply Filter</button>
                </div>
            </div>
        </div>
    </form>

    <div style="background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 1040px;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                        <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Project Code</th>
                        <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; min-width: 260px;">Project Title</th>
                        <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Fund Source</th>
                        <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Funding Year</th>
                        <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">City / Municipality</th>
                        <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Province</th>
                        <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Status</th>
                        <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $index => $project)
                        @php
                            $status = strtolower(trim((string) ($project->status ?? '')));
                            if (str_contains($status, 'complet') || str_contains($status, 'done') || str_contains($status, 'approved')) {
                                $badgeBg = '#d1fae5';
                                $badgeColor = '#065f46';
                                $dotColor = '#10b981';
                            } elseif (str_contains($status, 'ongoing') || str_contains($status, 'progress') || str_contains($status, 'active')) {
                                $badgeBg = '#dbeafe';
                                $badgeColor = '#1e40af';
                                $dotColor = '#3b82f6';
                            } elseif (str_contains($status, 'pending') || str_contains($status, 'review')) {
                                $badgeBg = '#fef3c7';
                                $badgeColor = '#92400e';
                                $dotColor = '#f59e0b';
                            } elseif (str_contains($status, 'cancel') || str_contains($status, 'reject') || str_contains($status, 'suspend')) {
                                $badgeBg = '#fee2e2';
                                $badgeColor = '#991b1b';
                                $dotColor = '#ef4444';
                            } else {
                                $badgeBg = '#f3f4f6';
                                $badgeColor = '#4b5563';
                                $dotColor = '#9ca3af';
                            }
                            $rowBg = $index % 2 === 0 ? '#ffffff' : '#f9fafb';
                        @endphp
                        <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s;"
                            onmouseover="this.style.backgroundColor='#eff6ff'" onmouseout="this.style.backgroundColor='{{ $rowBg }}'">
                            <td style="padding: 14px 16px; font-size: 13px; font-weight: 700; color: #002C76; white-space: nowrap;">{{ $project->project_code }}</td>
                            <td style="padding: 14px 16px; font-size: 13px; color: #111827; max-width: 280px;">
                                <div style="white-space: normal; line-height: 1.45;">{{ $project->project_title ?: '-' }}</div>
                            </td>
                            <td style="padding: 14px 16px; font-size: 13px; color: #374151; text-align: center; white-space: nowrap;">
                                <span style="display: inline-block; padding: 3px 10px; background-color: #e0e7ff; color: #3730a3; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                    {{ $project->fund_source ?: 'Unspecified' }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; font-size: 13px; color: #374151; text-align: center; font-weight: 600; white-space: nowrap;">{{ $project->funding_year ?: '-' }}</td>
                            <td style="padding: 14px 16px; font-size: 13px; color: #374151; white-space: nowrap;">{{ $project->city_municipality ?: '-' }}</td>
                            <td style="padding: 14px 16px; font-size: 13px; color: #374151; white-space: nowrap;">{{ $project->province ?: '-' }}</td>
                            <td style="padding: 14px 16px; text-align: center; white-space: nowrap;">
                                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background-color: {{ $badgeBg }}; color: {{ $badgeColor }}; border-radius: 9999px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background-color: {{ $dotColor }}; flex-shrink: 0;"></span>
                                    {{ $project->status ?: 'Unknown' }}
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: center; white-space: nowrap;">
                                <a href="{{ route($formMeta['show_route'], ['projectCode' => $project->project_code]) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; background-color: #002C76; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; transition: background-color 0.2s;"
                                   onmouseover="this.style.backgroundColor='#003d9e'" onmouseout="this.style.backgroundColor='#002C76'">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 60px 20px; text-align: center; color: #9ca3af;">
                                <i class="fas fa-inbox" style="font-size: 36px; margin-bottom: 12px; display: block; color: #d1d5db;"></i>
                                <div style="font-size: 14px; font-weight: 600; color: #6b7280;">No SubayBayan projects found.</div>
                                <div style="font-size: 12px; margin-top: 4px;">The table will populate once matching SubayBayan data is available for your scope.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->count() > 0)
            <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="font-size: 12px; color: #6b7280;">
                        Page {{ $projects->currentPage() }} of {{ $projects->lastPage() }} ·
                        Showing {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }} of {{ $projects->total() }}
                    </div>
                    <form method="GET" action="{{ route($formMeta['index_route']) }}" style="display: inline-flex; align-items: center;">
                        @foreach ($multiFilterKeys as $filterKey)
                            @foreach (($filters[$filterKey] ?? []) as $selectedValue)
                                <input type="hidden" name="{{ $filterKey }}[]" value="{{ $selectedValue }}">
                            @endforeach
                        @endforeach
                        <select id="per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                            @foreach([10, 15, 25, 50] as $option)
                                <option value="{{ $option }}" {{ (int) ($perPage ?? 15) === $option ? 'selected' : '' }}>{{ $option }}</option>
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
    </div>
@endsection

@section('scripts')
    <script>
        const PROJECT_FILTER_STATE_KEY=@json(($formMeta['index_route'] ?? 'rpmes-shared') . '-filter-collapsed');
        function readProjectFilterCollapsedState(){try{const v=window.localStorage.getItem(PROJECT_FILTER_STATE_KEY);return v===null?true:v==='1';}catch(e){return true;}}
        function writeProjectFilterCollapsedState(isCollapsed){try{window.localStorage.setItem(PROJECT_FILTER_STATE_KEY,isCollapsed?'1':'0');}catch(e){}}
        function setProjectFilterBodyHeight(form){const body=form.querySelector('.project-filter-body');if(!body){return;}body.style.maxHeight=form.classList.contains('collapsed')?'0px':`${body.scrollHeight}px`;}
        function toggleProjectFilter(button){const form=button.closest('.project-filter-form');if(!form){return;}const body=form.querySelector('.project-filter-body');if(!body){return;}form.querySelectorAll('[data-stacked-filter]').forEach((f)=>{if(typeof f.__closeDropdown==='function'){f.__closeDropdown();}});const isCollapsed=form.classList.contains('collapsed');if(isCollapsed){form.classList.remove('collapsed');requestAnimationFrame(()=>{body.style.maxHeight=`${body.scrollHeight}px`;});}else{body.style.maxHeight=`${body.scrollHeight}px`;requestAnimationFrame(()=>{form.classList.add('collapsed');body.style.maxHeight='0px';});}const nextCollapsed=!isCollapsed;button.setAttribute('aria-expanded',nextCollapsed?'false':'true');writeProjectFilterCollapsedState(nextCollapsed);}
        function initializeStackedFilters(){document.querySelectorAll('[data-stacked-filter]').forEach((stackedFilter)=>{if(stackedFilter.dataset.stackedFilterInitialized==='1'){return;}const sourceSelect=document.getElementById(stackedFilter.dataset.sourceSelectId||'');const badgeContainer=document.getElementById(stackedFilter.dataset.badgeContainerId||'');const dropdownToggle=document.getElementById(stackedFilter.dataset.dropdownToggleId||'');const dropdownMenu=document.getElementById(stackedFilter.dataset.dropdownMenuId||'');if(!sourceSelect||!badgeContainer||!dropdownToggle||!dropdownMenu){return;}const emptyBadgeText=stackedFilter.dataset.emptyBadgeText||'All';const filterLabel=String(sourceSelect.dataset.filterLabel||'Filter').trim();const emptyMenuText=`No ${filterLabel.toLowerCase()} options available.`;if(dropdownMenu.dataset.overlayAttached!=='1'){document.body.appendChild(dropdownMenu);dropdownMenu.dataset.overlayAttached='1';}const getSelectOptions=()=>Array.from(sourceSelect.options||[]);const updateFilterBodyHeight=()=>{const parentForm=stackedFilter.closest('.project-filter-form');if(!parentForm||parentForm.classList.contains('collapsed')){return;}requestAnimationFrame(()=>setProjectFilterBodyHeight(parentForm));};const positionDropdownMenu=()=>{if(!dropdownMenu.classList.contains('is-open')){return;}const viewportMargin=8;const menuGap=4;const rect=dropdownToggle.getBoundingClientRect();const availableBelow=Math.max(0,window.innerHeight-rect.bottom-viewportMargin);const availableAbove=Math.max(0,rect.top-viewportMargin);const preferredHeight=Math.min(dropdownMenu.scrollHeight,220);const shouldOpenUpward=availableBelow<Math.min(preferredHeight,160)&&availableAbove>availableBelow;const availableHeight=Math.max(96,Math.min(Math.max(96,window.innerHeight-(viewportMargin*2)),(shouldOpenUpward?availableAbove:availableBelow)-menuGap));const renderedHeight=Math.min(dropdownMenu.scrollHeight,availableHeight);const renderedWidth=Math.min(rect.width,window.innerWidth-(viewportMargin*2));const top=shouldOpenUpward?Math.max(viewportMargin,rect.top-renderedHeight-menuGap):Math.min(window.innerHeight-viewportMargin-renderedHeight,rect.bottom+menuGap);const left=Math.min(Math.max(viewportMargin,rect.left),window.innerWidth-viewportMargin-renderedWidth);dropdownMenu.style.left=`${left}px`;dropdownMenu.style.top=`${Math.max(viewportMargin,top)}px`;dropdownMenu.style.width=`${renderedWidth}px`;dropdownMenu.style.maxHeight=`${availableHeight}px`;};const closeDropdown=()=>{dropdownMenu.classList.remove('is-open');dropdownToggle.classList.remove('is-open');dropdownToggle.setAttribute('aria-expanded','false');dropdownMenu.style.left='';dropdownMenu.style.top='';dropdownMenu.style.width='';dropdownMenu.style.maxHeight='';};const openDropdown=()=>{document.querySelectorAll('[data-stacked-filter]').forEach((otherFilter)=>{if(otherFilter!==stackedFilter&&typeof otherFilter.__closeDropdown==='function'){otherFilter.__closeDropdown();}});dropdownMenu.classList.add('is-open');dropdownToggle.classList.add('is-open');dropdownToggle.setAttribute('aria-expanded','true');requestAnimationFrame(positionDropdownMenu);};const renderBadges=()=>{const selected=getSelectOptions().filter((optionEl)=>optionEl.selected&&optionEl.value.trim()!=='');badgeContainer.innerHTML='';if(!selected.length){const emptyBadge=document.createElement('span');emptyBadge.className='dashboard-filter-badge-empty';emptyBadge.textContent=emptyBadgeText;badgeContainer.appendChild(emptyBadge);}else{selected.forEach((optionEl)=>{const badge=document.createElement('span');badge.className='dashboard-filter-badge';const label=document.createElement('span');label.className='dashboard-filter-badge-label';label.textContent=optionEl.textContent.replace(/\s+/g,' ').trim();const removeButton=document.createElement('button');removeButton.type='button';removeButton.className='dashboard-filter-badge-remove';removeButton.dataset.removeValue=optionEl.value;removeButton.textContent='x';removeButton.setAttribute('aria-label',`Remove ${label.textContent}`);badge.appendChild(label);badge.appendChild(removeButton);badgeContainer.appendChild(badge);});}updateFilterBodyHeight();requestAnimationFrame(positionDropdownMenu);};const renderDropdownOptions=()=>{const options=getSelectOptions().filter((optionEl)=>optionEl.value.trim()!=='');dropdownMenu.innerHTML='';if(!options.length){const emptyMenuItem=document.createElement('div');emptyMenuItem.className='dashboard-stacked-filter-menu-empty';emptyMenuItem.textContent=emptyMenuText;dropdownMenu.appendChild(emptyMenuItem);return;}options.forEach((optionEl,index)=>{const optionButton=document.createElement('button');optionButton.type='button';optionButton.className='dashboard-stacked-filter-option';optionButton.dataset.optionIndex=String(index);optionButton.setAttribute('role','option');optionButton.setAttribute('aria-selected',optionEl.selected?'true':'false');if(optionEl.selected){optionButton.classList.add('is-selected');}const optionLabel=document.createElement('span');optionLabel.textContent=optionEl.textContent.replace(/\s+/g,' ').trim();const optionCheck=document.createElement('span');optionCheck.className='dashboard-stacked-filter-option-check';optionCheck.textContent='✓';optionButton.appendChild(optionLabel);optionButton.appendChild(optionCheck);dropdownMenu.appendChild(optionButton);});};dropdownToggle.addEventListener('click',(event)=>{if(event.target.closest('.dashboard-filter-badge-remove')){return;}dropdownMenu.classList.contains('is-open')?closeDropdown():openDropdown();});dropdownToggle.addEventListener('keydown',(event)=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();dropdownMenu.classList.contains('is-open')?closeDropdown():openDropdown();}if(event.key==='Escape'){event.preventDefault();closeDropdown();}});dropdownMenu.addEventListener('click',(event)=>{const optionButton=event.target.closest('.dashboard-stacked-filter-option');if(!optionButton){return;}const optionIndex=Number(optionButton.dataset.optionIndex);const matchingOption=sourceSelect.options[optionIndex];if(!matchingOption){return;}matchingOption.selected=!matchingOption.selected;renderBadges();renderDropdownOptions();});badgeContainer.addEventListener('click',(event)=>{const removeButton=event.target.closest('.dashboard-filter-badge-remove');if(!removeButton){return;}event.preventDefault();event.stopPropagation();getSelectOptions().forEach((optionEl)=>{if(optionEl.value===removeButton.dataset.removeValue){optionEl.selected=false;}});renderBadges();renderDropdownOptions();});document.addEventListener('click',(event)=>{if(!stackedFilter.contains(event.target)&&!dropdownMenu.contains(event.target)){closeDropdown();}});document.addEventListener('keydown',(event)=>{if(event.key==='Escape'){closeDropdown();}});window.addEventListener('resize',()=>requestAnimationFrame(positionDropdownMenu));document.addEventListener('scroll',()=>requestAnimationFrame(positionDropdownMenu),true);renderBadges();renderDropdownOptions();stackedFilter.__closeDropdown=closeDropdown;stackedFilter.dataset.stackedFilterInitialized='1';});}
        document.addEventListener('DOMContentLoaded',()=>{initializeStackedFilters();const forms=document.querySelectorAll('.project-filter-form');forms.forEach((form)=>{const collapsed=readProjectFilterCollapsedState();const toggleButton=form.querySelector('.project-filter-toggle');form.classList.toggle('collapsed',collapsed);if(toggleButton){toggleButton.setAttribute('aria-expanded',collapsed?'false':'true');}setProjectFilterBodyHeight(form);});window.addEventListener('resize',()=>{forms.forEach((form)=>{if(!form.classList.contains('collapsed')){setProjectFilterBodyHeight(form);}});});});
    </script>
@endsection
