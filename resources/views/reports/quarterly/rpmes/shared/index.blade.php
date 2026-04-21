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
        .dashboard-filter-summary-text { font-size: 12px; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dashboard-stacked-filter-chevron { margin-left: auto; color: #6b7280; font-size: 11px; transition: transform 0.2s ease; flex: 0 0 auto; }
        .dashboard-stacked-filter-toggle.is-open .dashboard-stacked-filter-chevron { transform: rotate(180deg); }
        .dashboard-stacked-filter-menu { position: fixed; left: 0; top: 0; display: none; width: auto; background: #ffffff; border: 1px solid #d1d5db; border-radius: 7px; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08); padding: 4px; max-height: 220px; overflow-y: auto; overflow-x: hidden; box-sizing: border-box; z-index: 1250; }
        .dashboard-stacked-filter-menu.is-open { display: block; }
        .dashboard-stacked-filter-search { position: sticky; top: 0; z-index: 1; background: #ffffff; padding: 2px 2px 6px; }
        .dashboard-stacked-filter-search-field { position: relative; }
        .dashboard-stacked-filter-search-field i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 12px; pointer-events: none; }
        .dashboard-stacked-filter-search-input { width: 100%; height: 32px; border: 1px solid #d1d5db; border-radius: 6px; padding: 0 10px 0 30px; font-size: 12px; color: #111827; background: #ffffff; box-sizing: border-box; }
        .dashboard-stacked-filter-search-input:focus { outline: 0; border-color: #60a5fa; box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.18); }
        .dashboard-stacked-filter-option { width: 100%; border: 0; background: transparent; border-radius: 4px; color: #1f2937; padding: 7px 8px; font-size: 12px; font-weight: 400; text-align: left; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; }
        .dashboard-stacked-filter-option:hover { background: #f3f4f6; }
        .dashboard-stacked-filter-option.is-selected { background: #eff6ff; color: #1d4ed8; font-weight: 500; }
        .dashboard-stacked-filter-option-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="city_municipality" data-badge-container-id="city_municipality_badges" data-dropdown-toggle-id="city_municipality_dropdown_toggle" data-dropdown-menu-id="city_municipality_dropdown_menu" data-empty-badge-text="All" data-empty-menu-text="Select at least one province first.">
                    <label for="city_municipality_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">City/Municipality</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div id="city_municipality_dropdown_toggle" class="dashboard-stacked-filter-toggle" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-controls="city_municipality_dropdown_menu"><div id="city_municipality_badges" class="dashboard-filter-badge-list" aria-live="polite"></div><span class="dashboard-stacked-filter-chevron"><i class="fas fa-chevron-down"></i></span></div>
                        <div id="city_municipality_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select id="city_municipality" name="city_municipality[]" multiple class="dashboard-stacked-filter-source" data-filter-label="City/Municipality" aria-hidden="true">@foreach (($filterOptions['cities'] ?? collect()) as $option)<option value="{{ $option }}" @selected(in_array((string) $option, ($filters['city_municipality'] ?? []), true))>{{ $option }}</option>@endforeach</select>
                </div>

                <div class="dashboard-stacked-filter" data-stacked-filter data-source-select-id="barangay" data-badge-container-id="barangay_badges" data-dropdown-toggle-id="barangay_dropdown_toggle" data-dropdown-menu-id="barangay_dropdown_menu" data-empty-badge-text="All" data-empty-menu-text="Select at least one city/municipality first.">
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
        const PROJECT_FILTER_STATE_KEY = @json(($formMeta['index_route'] ?? 'rpmes-shared') . '-filter-collapsed');
        const RPMES_LOCATION_FILTERS = {
            provinceCityMap: @json($filterOptions['province_city_map'] ?? []),
            cityBarangayMap: @json($filterOptions['city_barangay_map'] ?? []),
        };

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
            const body = form?.querySelector('.project-filter-body');
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
            const stackedFilters = document.querySelectorAll('[data-stacked-filter]');
            if (!stackedFilters.length) {
                return;
            }

            stackedFilters.forEach((stackedFilter) => {
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

                const filterLabel = String(
                    sourceSelect.dataset.filterLabel
                    || stackedFilter.querySelector('label')?.textContent
                    || 'Filter'
                ).trim();
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
                const updateFilterBodyHeight = () => {
                    const parentForm = stackedFilter.closest('.project-filter-form');
                    if (!parentForm || parentForm.classList.contains('collapsed')) {
                        return;
                    }

                    requestAnimationFrame(() => setProjectFilterBodyHeight(parentForm));
                };
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
                const syncDropdownMenuPosition = () => {
                    if (dropdownMenu.classList.contains('is-open')) {
                        requestAnimationFrame(positionDropdownMenu);
                    }
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
                const renderBadges = () => {
                    const selectedOptions = getSelectedOptionsInOrder();
                    badgeContainer.innerHTML = '';

                    const summary = document.createElement('span');
                    if (!selectedOptions.length) {
                        summary.className = 'dashboard-filter-badge-empty';
                        summary.textContent = getEmptyBadgeText();
                    } else {
                        summary.className = 'dashboard-filter-summary-text';
                        summary.textContent = selectedOptions.map(getOptionLabel).join(', ');
                    }

                    badgeContainer.appendChild(summary);
                    updateFilterBodyHeight();
                    syncDropdownMenuPosition();
                };
                const renderDropdownOptions = ({ preserveSearchFocus = false } = {}) => {
                    const selectOptions = getSelectOptions().filter((optionElement) => optionElement.value.trim() !== '');
                    const normalizedSearch = searchState.value.trim().toLowerCase();
                    const filteredOptions = normalizedSearch === ''
                        ? selectOptions
                        : selectOptions.filter((optionElement) => getOptionLabel(optionElement).toLowerCase().includes(normalizedSearch));
                    const activeSearchInput = dropdownMenu.querySelector('.dashboard-stacked-filter-search-input');
                    const shouldRestoreSearchFocus = preserveSearchFocus || document.activeElement === activeSearchInput;
                    const previousSelectionStart = shouldRestoreSearchFocus ? activeSearchInput?.selectionStart : null;
                    const previousSelectionEnd = shouldRestoreSearchFocus ? activeSearchInput?.selectionEnd : null;

                    dropdownMenu.innerHTML = '';

                    if (selectOptions.length > 0) {
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
                            syncDropdownMenuPosition();
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
                        const optionIndex = getSelectOptions().indexOf(optionElement);
                        const optionButton = document.createElement('button');
                        optionButton.type = 'button';
                        optionButton.className = 'dashboard-stacked-filter-option';
                        optionButton.dataset.optionIndex = String(optionIndex);
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
                const refreshFilterUi = () => {
                    renderBadges();
                    renderDropdownOptions();
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
                    syncDropdownMenuPosition();
                };

                dropdownToggle.addEventListener('click', () => {
                    dropdownMenu.classList.contains('is-open') ? closeDropdown() : openDropdown();
                });

                dropdownToggle.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        dropdownMenu.classList.contains('is-open') ? closeDropdown() : openDropdown();
                        return;
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
                    updateSelectionOrderForValue(matchingOption.value, matchingOption.selected);
                    refreshFilterUi();
                    sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
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

                window.addEventListener('resize', syncDropdownMenuPosition);
                document.addEventListener('scroll', syncDropdownMenuPosition, true);
                sourceSelect.addEventListener('change', refreshFilterUi);

                refreshFilterUi();
                stackedFilter.__closeDropdown = closeDropdown;
                stackedFilter.__refreshFilterUi = refreshFilterUi;
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

        function initializeRpmesLocationDependencies() {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city_municipality');
            const barangaySelect = document.getElementById('barangay');
            const provinceCityMap = RPMES_LOCATION_FILTERS.provinceCityMap || {};
            const cityBarangayMap = RPMES_LOCATION_FILTERS.cityBarangayMap || {};

            if (!provinceSelect || !citySelect || !barangaySelect) {
                return;
            }

            const getOrderedSelectedValues = (selectElement) => {
                const selectedValues = Array.from(selectElement.selectedOptions || [])
                    .map((optionElement) => String(optionElement.value || '').trim())
                    .filter((value) => value !== '');
                const selectedValueSet = new Set(selectedValues);
                const selectionOrder = Array.isArray(selectElement.__selectionOrder)
                    ? selectElement.__selectionOrder.filter((value) => selectedValueSet.has(value))
                    : [];

                selectedValues.forEach((value) => {
                    if (!selectionOrder.includes(value)) {
                        selectionOrder.push(value);
                    }
                });

                return selectionOrder;
            };

            const refreshFilterUi = (selectId) => {
                const stackedFilter = document.querySelector(`[data-stacked-filter][data-source-select-id="${selectId}"]`);
                if (stackedFilter && typeof stackedFilter.__refreshFilterUi === 'function') {
                    stackedFilter.__refreshFilterUi();
                }
            };

            const rebuildBarangayOptions = () => {
                const selectedCities = getOrderedSelectedValues(citySelect);
                const currentSelectedBarangays = Array.from(barangaySelect.selectedOptions || [])
                    .map((optionElement) => String(optionElement.value || '').trim())
                    .filter((value) => value !== '');
                const nextBarangays = [];
                const seenBarangays = new Set();

                selectedCities.forEach((city) => {
                    (cityBarangayMap[city] || []).forEach((barangay) => {
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
                    currentSelectedBarangays.filter((value) => nextBarangays.includes(value))
                );

                setStackedFilterEmptyMenuText(
                    'barangay',
                    selectedCities.length ? 'No barangay options available.' : 'Select at least one city/municipality first.'
                );
                refreshFilterUi('barangay');
            };

            const rebuildCityOptions = () => {
                const selectedProvinces = getOrderedSelectedValues(provinceSelect);
                const currentSelectedCities = Array.from(citySelect.selectedOptions || [])
                    .map((optionElement) => String(optionElement.value || '').trim())
                    .filter((value) => value !== '');
                const nextCities = [];
                const seenCities = new Set();

                selectedProvinces.forEach((province) => {
                    (provinceCityMap[province] || []).forEach((city) => {
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
                    'city_municipality',
                    selectedProvinces.length ? 'No city/municipality options available.' : 'Select at least one province first.'
                );
                refreshFilterUi('city_municipality');
                rebuildBarangayOptions();
            };

            provinceSelect.addEventListener('change', rebuildCityOptions);
            citySelect.addEventListener('change', rebuildBarangayOptions);

            rebuildCityOptions();
        }

        document.addEventListener('DOMContentLoaded', () => {
            initializeStackedFilters();
            initializeRpmesLocationDependencies();

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

            window.addEventListener('resize', () => {
                forms.forEach((form) => {
                    if (!form.classList.contains('collapsed')) {
                        setProjectFilterBodyHeight(form);
                    }
                });
            });
        });
    </script>
@endsection
