@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('styles')
    <style>
        /* Prevent first-paint flash of the raw filter form before the page-level styles load. */
        .project-filter-body {
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease;
            will-change: max-height, opacity, transform;
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

        .dashboard-stacked-filter-menu {
            display: none;
        }
    </style>
@endsection

@section('content')
    <div class="content-header">
        <h1>Welcome back, {{ Auth::user()->fname ?? 'User' }}! 👋</h1>
        <p>Project Status Summary as of {{ $subayUploadDateLabel ?? 'No SubayBAYAN upload yet' }}.</p>
    </div>

    @php
        $statusIconMap = [
            'Completed' => ['icon' => 'fa-circle-check', 'color' => '#16a34a', 'bg' => '#dcfce7', 'tileBg' => '#f0fdf4', 'tileBorder' => '#bbf7d0', 'labelColor' => '#14532d'],
            'On-going' => ['icon' => 'fa-spinner', 'color' => '#2563eb', 'bg' => '#dbeafe', 'tileBg' => '#eff6ff', 'tileBorder' => '#bfdbfe', 'labelColor' => '#1e3a8a'],
            'Bid Evaluation/Opening' => ['icon' => 'fa-gavel', 'color' => '#b45309', 'bg' => '#ffedd5', 'tileBg' => '#fffbeb', 'tileBorder' => '#fde68a', 'labelColor' => '#92400e'],
            'NOA Issuance' => ['icon' => 'fa-file-signature', 'color' => '#0ea5e9', 'bg' => '#e0f2fe', 'tileBg' => '#ecfeff', 'tileBorder' => '#a5f3fc', 'labelColor' => '#155e75'],
            'DED Preparation' => ['icon' => 'fa-drafting-compass', 'color' => '#7c3aed', 'bg' => '#ede9fe', 'tileBg' => '#f5f3ff', 'tileBorder' => '#ddd6fe', 'labelColor' => '#5b21b6'],
            'Not Yet Started' => ['icon' => 'fa-hourglass-start', 'color' => '#6b7280', 'bg' => '#f3f4f6', 'tileBg' => '#f8fafc', 'tileBorder' => '#cbd5e1', 'labelColor' => '#334155'],
            'ITB/AD Posted' => ['icon' => 'fa-bullhorn', 'color' => '#ea580c', 'bg' => '#fff7ed', 'tileBg' => '#fff7ed', 'tileBorder' => '#fed7aa', 'labelColor' => '#9a3412'],
            'Terminated' => ['icon' => 'fa-circle-xmark', 'color' => '#ef4444', 'bg' => '#fee2e2', 'tileBg' => '#fef2f2', 'tileBorder' => '#fecaca', 'labelColor' => '#991b1b'],
            'Cancelled' => ['icon' => 'fa-ban', 'color' => '#e11d48', 'bg' => '#ffe4e6', 'tileBg' => '#fff1f2', 'tileBorder' => '#fecdd3', 'labelColor' => '#9f1239'],
        ];

        $fundSourceIconMap = [
            'SBDP' => 'fa-shield-halved',                  // Subukan Barangay Development Program
            'FALGU' => 'fa-hand-holding-dollar',           // Fund for Agricultural and Livelihood Generation Unit
            'CMGP' => 'fa-people-group',                   // Cooperative Management Growth Program
            'GEF' => 'fa-leaf',                            // Global Environment Facility
            'SAFPB' => 'fa-bridge',                        // Single Arch Footbridge Program
            'ADM-LA' => 'fa-file-invoice-dollar',          // Administrative - Local Assistance
            'ADM-OT' => 'fa-folder',                       // Administrative - Other
            'ADM-PW' => 'fa-hammer',                       // Administrative - Public Works
            'AM-DRR' => 'fa-shield-exclamation',           // Assistance to Municipalities - Disaster Risk Reduction
            'AM-LA' => 'fa-handshake',                     // Assistance to Municipalities - Local Assistance
            'AM-PW' => 'fa-hammer',                        // Assistance to Municipalities - Public Works
            'DILG-BLDGS' => 'fa-building',                 // DILG Buildings
            'DRRAP' => 'fa-megaphone',                     // Disaster Risk Reduction Advocacy Program
            'DTEAP' => 'fa-computer',                      // Disaster Technology Enhancement and Advancement Program
            'KA' => 'fa-heart',                            // Kapatiran (Community Fellowship)
            'LA' => 'fa-handshake',                        // Local Assistance
            'LGSF' => 'fa-landmark',                       // Local Government Support Fund
            'LO' => 'fa-store',                            // Livelihood Operation
            'LR' => 'fa-water',                            // Land Reclamation
            'OT' => 'fa-box',                              // Other
            'PA' => 'fa-hands-praying',                    // Pastoral/Provincial Assistance
            'PM' => 'fa-clipboard-list',                   // Program Management
            'PW' => 'fa-hammer',                           // Public Works
            'SA' => 'fa-hand-holding-heart',               // Social Assistance
            'SF' => 'fa-piggy-bank',                       // Special Fund
        ];

        $fundSourceStyleMap = [
            'SBDP' => ['bg' => 'linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%)', 'border' => '#bfdbfe', 'iconBg' => 'linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%)', 'iconColor' => '#1d4ed8', 'labelColor' => '#1e3a8a'],   // blue
            'FALGU' => ['bg' => 'linear-gradient(180deg, #f8fef9 0%, #f0fdf4 100%)', 'border' => '#bbf7d0', 'iconBg' => 'linear-gradient(180deg, #ecfdf3 0%, #dcfce7 100%)', 'iconColor' => '#15803d', 'labelColor' => '#14532d'], // green
            'CMGP' => ['bg' => 'linear-gradient(180deg, #fffaf4 0%, #fff7ed 100%)', 'border' => '#fed7aa', 'iconBg' => 'linear-gradient(180deg, #fff4e5 0%, #ffedd5 100%)', 'iconColor' => '#c2410c', 'labelColor' => '#7c2d12'],  // orange
            'GEF' => ['bg' => 'linear-gradient(180deg, #f4feff 0%, #ecfeff 100%)', 'border' => '#a5f3fc', 'iconBg' => 'linear-gradient(180deg, #e6fcff 0%, #cffafe 100%)', 'iconColor' => '#0e7490', 'labelColor' => '#164e63'],   // cyan
            'SAFPB' => ['bg' => 'linear-gradient(180deg, #fff8f8 0%, #fef2f2 100%)', 'border' => '#fecaca', 'iconBg' => 'linear-gradient(180deg, #fff1f1 0%, #fee2e2 100%)', 'iconColor' => '#dc2626', 'labelColor' => '#7f1d1d'], // red
            'ADM-LA' => ['bg' => 'linear-gradient(180deg, #fafafa 0%, #f3f4f6 100%)', 'border' => '#d1d5db', 'iconBg' => 'linear-gradient(180deg, #f9fafb 0%, #e5e7eb 100%)', 'iconColor' => '#4b5563', 'labelColor' => '#374151'], // slate
            'ADM-OT' => ['bg' => 'linear-gradient(180deg, #fafafa 0%, #f3f4f6 100%)', 'border' => '#d1d5db', 'iconBg' => 'linear-gradient(180deg, #f9fafb 0%, #e5e7eb 100%)', 'iconColor' => '#4b5563', 'labelColor' => '#374151'], // slate
            'ADM-PW' => ['bg' => 'linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%)', 'border' => '#fdba74', 'iconBg' => 'linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%)', 'iconColor' => '#c2410c', 'labelColor' => '#9a3412'], // amber
            'AM-DRR' => ['bg' => 'linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%)', 'border' => '#fda4af', 'iconBg' => 'linear-gradient(180deg, #ffe4e6 0%, #fecdd3 100%)', 'iconColor' => '#e11d48', 'labelColor' => '#9f1239'], // rose
            'AM-LA' => ['bg' => 'linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%)', 'border' => '#86efac', 'iconBg' => 'linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%)', 'iconColor' => '#15803d', 'labelColor' => '#166534'], // green
            'AM-PW' => ['bg' => 'linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%)', 'border' => '#fdba74', 'iconBg' => 'linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%)', 'iconColor' => '#c2410c', 'labelColor' => '#9a3412'], // amber
            'DILG-BLDGS' => ['bg' => 'linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%)', 'border' => '#93c5fd', 'iconBg' => 'linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%)', 'iconColor' => '#2563eb', 'labelColor' => '#1e3a8a'], // blue
            'DRRAP' => ['bg' => 'linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%)', 'border' => '#fdba74', 'iconBg' => 'linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%)', 'iconColor' => '#ea580c', 'labelColor' => '#9a3412'], // orange
            'DTEAP' => ['bg' => 'linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%)', 'border' => '#c4b5fd', 'iconBg' => 'linear-gradient(180deg, #ede9fe 0%, #ddd6fe 100%)', 'iconColor' => '#7c3aed', 'labelColor' => '#5b21b6'], // violet
            'KA' => ['bg' => 'linear-gradient(180deg, #fdf2f8 0%, #fce7f3 100%)', 'border' => '#f9a8d4', 'iconBg' => 'linear-gradient(180deg, #fce7f3 0%, #fbcfe8 100%)', 'iconColor' => '#db2777', 'labelColor' => '#9d174d'], // pink
            'LA' => ['bg' => 'linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%)', 'border' => '#86efac', 'iconBg' => 'linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%)', 'iconColor' => '#15803d', 'labelColor' => '#166534'], // green
            'LGSF' => ['bg' => 'linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%)', 'border' => '#c4b5fd', 'iconBg' => 'linear-gradient(180deg, #ede9fe 0%, #ddd6fe 100%)', 'iconColor' => '#6d28d9', 'labelColor' => '#5b21b6'], // violet
            'LO' => ['bg' => 'linear-gradient(180deg, #ecfeff 0%, #cffafe 100%)', 'border' => '#67e8f9', 'iconBg' => 'linear-gradient(180deg, #cffafe 0%, #a5f3fc 100%)', 'iconColor' => '#0891b2', 'labelColor' => '#155e75'], // cyan
            'LR' => ['bg' => 'linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%)', 'border' => '#93c5fd', 'iconBg' => 'linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%)', 'iconColor' => '#2563eb', 'labelColor' => '#1e3a8a'], // blue
            'OT' => ['bg' => 'linear-gradient(180deg, #fafafa 0%, #f3f4f6 100%)', 'border' => '#d1d5db', 'iconBg' => 'linear-gradient(180deg, #f9fafb 0%, #e5e7eb 100%)', 'iconColor' => '#4b5563', 'labelColor' => '#374151'], // slate
            'PA' => ['bg' => 'linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%)', 'border' => '#fcd34d', 'iconBg' => 'linear-gradient(180deg, #fef3c7 0%, #fde68a 100%)', 'iconColor' => '#b45309', 'labelColor' => '#92400e'], // gold
            'PM' => ['bg' => 'linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%)', 'border' => '#7dd3fc', 'iconBg' => 'linear-gradient(180deg, #e0f2fe 0%, #bae6fd 100%)', 'iconColor' => '#0284c7', 'labelColor' => '#0c4a6e'], // sky
            'PW' => ['bg' => 'linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%)', 'border' => '#fdba74', 'iconBg' => 'linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%)', 'iconColor' => '#c2410c', 'labelColor' => '#9a3412'], // amber
            'SA' => ['bg' => 'linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%)', 'border' => '#fda4af', 'iconBg' => 'linear-gradient(180deg, #ffe4e6 0%, #fecdd3 100%)', 'iconColor' => '#e11d48', 'labelColor' => '#9f1239'], // rose
            'SF' => ['bg' => 'linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%)', 'border' => '#c4b5fd', 'iconBg' => 'linear-gradient(180deg, #ede9fe 0%, #ddd6fe 100%)', 'iconColor' => '#6d28d9', 'labelColor' => '#5b21b6'], // violet
        ];

        $financialMetricStyleMap = [
            'allocation' => ['bg' => '#fff7ed', 'border' => '#fed7aa', 'iconBg' => '#ffedd5', 'iconColor' => '#c2410c', 'labelColor' => '#9a3412', 'valueColor' => '#9a3412'],
            'percentage' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'iconBg' => '#dbeafe', 'iconColor' => '#1d4ed8', 'labelColor' => '#1e3a8a', 'valueColor' => '#1e3a8a'],
            'obligation' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'iconBg' => '#fef3c7', 'iconColor' => '#b45309', 'labelColor' => '#92400e', 'valueColor' => '#92400e'],
            'disbursement' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'iconBg' => '#dcfce7', 'iconColor' => '#15803d', 'labelColor' => '#14532d', 'valueColor' => '#14532d'],
            'balance' => ['bg' => '#f5f3ff', 'border' => '#ddd6fe', 'iconBg' => '#ede9fe', 'iconColor' => '#6d28d9', 'labelColor' => '#5b21b6', 'valueColor' => '#5b21b6'],
        ];

        $asCollection = static function ($value) {
            if ($value instanceof \Illuminate\Support\Collection) {
                return $value;
            }

            if (is_array($value) || $value instanceof \Traversable) {
                return collect($value);
            }

            return collect();
        };

        $statusSubaybayanCounts = $asCollection($statusSubaybayanCounts ?? []);
        $fundSourceCounts = $asCollection($fundSourceCounts ?? []);
        $balanceProjectsModalId = 'financial-balance-projects-modal';
        $balanceProjectsModalTitleId = $balanceProjectsModalId . '-title';
    @endphp

    @include('projects.partials.project-section-tabs', ['activeTab' => $activeProjectTab ?? 'locally-funded'])

    <div class="dashboard-main-layout">
        <form method="GET" action="{{ route('dashboard') }}" class="dashboard-card project-filter-form dashboard-main-layout-filter collapsed" data-page-loading="true" data-loading-label="Updating dashboard" data-loading-detail="Applying the selected dashboard filters." style="background: #ffffff; padding: 16px 18px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 0;">
            <button type="button" class="project-filter-toggle" onclick="toggleProjectFilter(this)" aria-expanded="false" aria-controls="project-filter-body">
                <i class="fas fa-filter" aria-hidden="true" style="font-size: 16px;"></i>
                <span>PROJECT FILTER</span>
                <span class="project-filter-chevron">
                    <i class="fas fa-chevron-up"></i>
                </span>
            </button>

            <div id="project-filter-body" class="project-filter-body">
                <div class="dashboard-filter-grid" style="display: grid; grid-template-columns: repeat(3, minmax(200px, 1fr)); gap: 12px 16px; align-items: end;">
                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="province"
                    data-badge-container-id="province_badges"
                    data-dropdown-toggle-id="province_dropdown_toggle"
                    data-dropdown-menu-id="province_dropdown_menu"
                    data-empty-badge-text="All"
                >
                    <label for="province_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Province</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="province_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="province_dropdown_menu"
                        >
                            <div id="province_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="province_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="province"
                        name="province[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="Province"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['provinces'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['province'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="city_municipality"
                    data-badge-container-id="city_municipality_badges"
                    data-dropdown-toggle-id="city_municipality_dropdown_toggle"
                    data-dropdown-menu-id="city_municipality_dropdown_menu"
                    data-empty-badge-text="All"
                    data-empty-menu-text="Select province first."
                >
                    <label for="city_municipality_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">City/Municipality</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="city_municipality_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="city_municipality_dropdown_menu"
                        >
                            <div id="city_municipality_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="city_municipality_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="city_municipality"
                        name="city_municipality[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="City/Municipality"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['cities'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['city_municipality'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="barangay"
                    data-badge-container-id="barangay_badges"
                    data-dropdown-toggle-id="barangay_dropdown_toggle"
                    data-dropdown-menu-id="barangay_dropdown_menu"
                    data-empty-badge-text="All"
                    data-empty-menu-text="Select city/municipality first."
                >
                    <label for="barangay_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Barangay</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="barangay_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="barangay_dropdown_menu"
                        >
                            <div id="barangay_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="barangay_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="barangay"
                        name="barangay[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="Barangay"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['barangays'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['barangay'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="program"
                    data-badge-container-id="program_badges"
                    data-dropdown-toggle-id="program_dropdown_toggle"
                    data-dropdown-menu-id="program_dropdown_menu"
                    data-empty-badge-text="No program selected."
                >
                    <label for="program_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Program</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="program_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="program_dropdown_menu"
                        >
                            <div id="program_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="program_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="program"
                        name="program[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="Program"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['programs'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['programs'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="funding_year"
                    data-badge-container-id="funding_year_badges"
                    data-dropdown-toggle-id="funding_year_dropdown_toggle"
                    data-dropdown-menu-id="funding_year_dropdown_menu"
                    data-empty-badge-text="All"
                >
                    <label for="funding_year_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Funding Year</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="funding_year_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="funding_year_dropdown_menu"
                        >
                            <div id="funding_year_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="funding_year_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="funding_year"
                        name="funding_year[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="Funding Year"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['funding_years'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['funding_year'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="project_type"
                    data-badge-container-id="project_type_badges"
                    data-dropdown-toggle-id="project_type_dropdown_toggle"
                    data-dropdown-menu-id="project_type_dropdown_menu"
                    data-empty-badge-text="All"
                >
                    <label for="project_type_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Project Type</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="project_type_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="project_type_dropdown_menu"
                        >
                            <div id="project_type_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="project_type_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="project_type"
                        name="project_type[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="Project Type"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['project_types'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['project_type'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="dashboard-stacked-filter"
                    data-stacked-filter
                    data-source-select-id="project_status"
                    data-badge-container-id="project_status_badges"
                    data-dropdown-toggle-id="project_status_dropdown_toggle"
                    data-dropdown-menu-id="project_status_dropdown_menu"
                    data-empty-badge-text="All"
                >
                    <label for="project_status_dropdown_toggle" style="display: block; color: #1f2937; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Project Status</label>
                    <div class="dashboard-stacked-filter-dropdown">
                        <div
                            id="project_status_dropdown_toggle"
                            class="dashboard-stacked-filter-toggle"
                            role="button"
                            tabindex="0"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="project_status_dropdown_menu"
                        >
                            <div id="project_status_badges" class="dashboard-filter-badge-list" aria-live="polite"></div>
                            <span class="dashboard-stacked-filter-chevron">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <div id="project_status_dropdown_menu" class="dashboard-stacked-filter-menu" role="listbox" aria-multiselectable="true"></div>
                    </div>
                    <select
                        id="project_status"
                        name="project_status[]"
                        multiple
                        class="dashboard-stacked-filter-source"
                        data-filter-label="Project Status"
                        aria-hidden="true"
                    >
                        @foreach (($filterOptions['project_statuses'] ?? collect()) as $option)
                            <option value="{{ $option }}" @selected(in_array((string) $option, ($filters['project_status'] ?? []), true))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-filter-reset" style="display: flex; align-items: end; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('dashboard') }}" class="dashboard-filter-reset-link" data-page-loading="true" data-loading-label="Resetting dashboard filters" data-loading-detail="Reloading the dashboard with the default filters." style="height: 34px; min-width: 150px; border-radius: 7px; background: linear-gradient(180deg, #003a99 0%, #002c76 100%); color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 600; padding: 0 14px;">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                        Reset Filter
                    </a>
                    <button
                        type="submit"
                        class="dashboard-filter-apply-btn"
                    >
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Apply Filter
                    </button>
                    <button
                        type="button"
                        class="dashboard-filter-export-btn"
                        onclick="exportDashboardOverviewToExcel(this)"
                        data-export-filename="status-of-projects-by-location.xls"
                    >
                        <i class="fas fa-file-excel" aria-hidden="true"></i>
                        Export Report
                    </button>
                </div>
            </div>
            </div>
        </form>

        <div class="dashboard-top-cards" style="display: grid; gap: 20px; margin-bottom: 0;">
            <div class="dashboard-card total-projects-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                <h2 style="color: #002C76; font-size: 16px; margin: 0 0 16px; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 22px; height: 22px; border-radius: 999px; background-color: #e0f2fe; color: #0ea5e9; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">
                        <i class="fas fa-project-diagram"></i>
                    </span>
                    TOTAL PROJECTS
                </h2>
                <div class="dashboard-tile clickable-dashboard-card" data-card-url="{{ route('projects.locally-funded') }}" style="padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb; text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em;">
                        <span style="width: 20px; height: 20px; border-radius: 999px; background-color: #e5e7eb; color: #4b5563; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">
                            <i class="fas fa-hashtag"></i>
                        </span>
                        Total Number of Projects
                    </div>
                    <div style="font-size: 36px; font-weight: 700; color: #002C76; text-align: center;">{{ $totalProjects }}</div>
                </div>
            </div>
        @if (!empty($fundSourceCounts) && $fundSourceCounts->count() > 0)
            <div class="dashboard-card fund-source-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="color: #002C76; font-size: 16px; margin: 0 0 16px; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 22px; height: 22px; border-radius: 999px; background-color: #e0f2fe; color: #0ea5e9; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">
                        <i class="fas fa-layer-group"></i>
                    </span>
                    PROJECTS BY FUND SOURCE
                </h2>
                <div
                    class="fund-source-grid"
                    @style([
                        'display: grid',
                        'grid-template-columns: repeat(auto-fit, minmax(120px, 1fr))',
                        'gap: 12px',
                    ])
                >
                    @foreach ($fundSourceCounts as $fundSource => $count)
                        @php
                            $fundSourceIcon = $fundSourceIconMap[$fundSource] ?? 'fa-coins';
                            $fundSourceStyles = $fundSourceStyleMap[$fundSource] ?? ['bg' => 'linear-gradient(180deg, #ffffff 0%, #f9fafb 100%)', 'border' => '#e5e7eb', 'iconBg' => 'linear-gradient(180deg, #f3f4f6 0%, #e5e7eb 100%)', 'iconColor' => '#4b5563', 'labelColor' => '#6b7280'];
                            $fundSourceModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $fundSource), '-');
                            $fundSourceModalId = 'fund-source-' . ($fundSourceModalKey !== '' ? $fundSourceModalKey : 'unspecified') . '-modal';
                            $knownFundSourceProjectCodeKeywords = [
                                'SBDP' => 'SBDP',
                                'FALGU' => 'FA',
                                'CMGP' => 'CMGP',
                                'GEF' => 'GEF',
                                'SAFPB' => 'SAFPB',
                            ];
                            $normalizedFundSource = strtoupper(trim((string) $fundSource));
                            $fundSourceFilterQuery = [
                                'search' => $fundSource,
                                'fund_source' => $fundSource,
                            ];
                            if (array_key_exists($normalizedFundSource, $knownFundSourceProjectCodeKeywords)) {
                                $fundSourceFilterQuery['project_code'] = $knownFundSourceProjectCodeKeywords[$normalizedFundSource];
                            }
                            $fundSourceFilterUrl = route('projects.locally-funded', $fundSourceFilterQuery);
                        @endphp
                        <div
                            class="dashboard-tile fund-source-link-tile clickable-dashboard-card"
                            data-card-url="{{ $fundSourceFilterUrl }}"
                            data-modal-target="{{ $fundSourceModalId }}"
                            @style([
                                'padding: 12px',
                                'border: 1px solid ' . $fundSourceStyles['border'],
                                'border-radius: 6px',
                                'background: ' . $fundSourceStyles['bg'],
                                'text-align: center',
                                'color: inherit',
                                'display: block',
                            ])
                        >
                            <div
                                @style([
                                    'display: flex',
                                    'align-items: center',
                                    'justify-content: center',
                                    'gap: 8px',
                                    'font-size: 13px',
                                    'font-weight: 600',
                                    'color: ' . $fundSourceStyles['labelColor'],
                                    'margin-bottom: 6px',
                                    'text-transform: uppercase',
                                    'letter-spacing: 0.04em',
                                ])
                            >
                                <span
                                    @style([
                                        'width: 20px',
                                        'height: 20px',
                                        'border-radius: 999px',
                                        'background: ' . $fundSourceStyles['iconBg'],
                                        'color: ' . $fundSourceStyles['iconColor'],
                                        'display: inline-flex',
                                        'align-items: center',
                                        'justify-content: center',
                                        'font-size: 10px',
                                    ])
                                >
                                    <i class="fas {{ $fundSourceIcon }}"></i>
                                </span>
                                {{ $fundSource }}
                            </div>
                            <div style="font-size: 20px; font-weight: 700; color: #002C76;">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="dashboard-card financial-status-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="color: #002C76; font-size: 16px; margin: 0 0 16px; display: flex; align-items: center; gap: 8px;">
                <span style="width: 22px; height: 22px; border-radius: 999px; background-color: #e0f2fe; color: #0ea5e9; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">
                    <i class="fas fa-chart-line"></i>
                </span>
                FINANCIAL ACCOMPLISHMENT STATUS
            </h2>

            <div class="financial-metrics-layout" style="display: grid; gap: 12px;">
                @php
                    $allocationStyle = $financialMetricStyleMap['allocation'];
                    $percentageStyle = $financialMetricStyleMap['percentage'];
                    $obligationStyle = $financialMetricStyleMap['obligation'];
                    $disbursementStyle = $financialMetricStyleMap['disbursement'];
                    $balanceStyle = $financialMetricStyleMap['balance'];
                @endphp
                <div
                    class="dashboard-tile financial-metric-tile financial-allocation-tile clickable-dashboard-card"
                    data-card-url="{{ route('fund-utilization.index') }}"
                    @style([
                        'padding: 12px',
                        'border: 1px solid ' . $allocationStyle['border'],
                        'border-radius: 6px',
                        'background-color: ' . $allocationStyle['bg'],
                        'text-align: center',
                    ])
                >
                    <div
                        class="financial-metric-label"
                        @style([
                            'display: flex',
                            'align-items: center',
                            'justify-content: center',
                            'gap: 8px',
                            'font-size: 13px',
                            'font-weight: 600',
                            'color: ' . $allocationStyle['labelColor'],
                            'margin-bottom: 6px',
                            'text-transform: uppercase',
                            'letter-spacing: 0.04em',
                        ])
                    >
                        <span
                            class="financial-metric-icon"
                            @style([
                                'width: 20px',
                                'height: 20px',
                                'border-radius: 999px',
                                'background-color: ' . $allocationStyle['iconBg'],
                                'color: ' . $allocationStyle['iconColor'],
                                'display: inline-flex',
                                'align-items: center',
                                'justify-content: center',
                                'font-size: 10px',
                            ])
                        >
                            <i class="fas fa-sack-dollar"></i>
                        </span>
                        LGSF Allocation
                    </div>
                    <div
                        class="financial-amount-value"
                        @style([
                            'font-weight: 700',
                            'color: ' . $allocationStyle['valueColor'],
                        ])
                    >
                        {{ number_format((float) ($totalLgsfAllocationAmount ?? 0), 2) }}
                    </div>
                </div>

                <div
                    class="dashboard-tile financial-metric-tile financial-percentage-tile clickable-dashboard-card"
                    data-card-url="{{ route('fund-utilization.index') }}"
                    @style([
                        'padding: 12px',
                        'border: 1px solid ' . $percentageStyle['border'],
                        'border-radius: 6px',
                        'background-color: ' . $percentageStyle['bg'],
                        'text-align: center',
                    ])
                >
                    <div
                        class="financial-metric-label"
                        @style([
                            'display: flex',
                            'align-items: center',
                            'justify-content: center',
                            'gap: 8px',
                            'font-size: 13px',
                            'font-weight: 600',
                            'color: ' . $percentageStyle['labelColor'],
                            'margin-bottom: 6px',
                            'text-transform: uppercase',
                            'letter-spacing: 0.04em',
                        ])
                    >
                        <span
                            class="financial-metric-icon"
                            @style([
                                'width: 20px',
                                'height: 20px',
                                'border-radius: 999px',
                                'background-color: ' . $percentageStyle['iconBg'],
                                'color: ' . $percentageStyle['iconColor'],
                                'display: inline-flex',
                                'align-items: center',
                                'justify-content: center',
                                'font-size: 10px',
                            ])
                        >
                            <i class="fas fa-chart-pie"></i>
                        </span>
                        Percentage
                    </div>
                    <div
                        class="financial-percentage-value"
                        @style([
                            'font-size: 22px',
                            'font-weight: 700',
                            'color: ' . $percentageStyle['valueColor'],
                        ])
                    >
                        {{ number_format((float) ($utilizationPercentage ?? 0), 2) }}%
                    </div>
                </div>

                <div
                    class="dashboard-tile financial-metric-tile financial-obligation-tile clickable-dashboard-card"
                    data-card-url="{{ route('fund-utilization.index') }}"
                    @style([
                        'padding: 12px',
                        'border: 1px solid ' . $obligationStyle['border'],
                        'border-radius: 6px',
                        'background-color: ' . $obligationStyle['bg'],
                        'text-align: center',
                    ])
                >
                    <div
                        class="financial-metric-label"
                        @style([
                            'display: flex',
                            'align-items: center',
                            'justify-content: center',
                            'gap: 8px',
                            'font-size: 13px',
                            'font-weight: 600',
                            'color: ' . $obligationStyle['labelColor'],
                            'margin-bottom: 6px',
                            'text-transform: uppercase',
                            'letter-spacing: 0.04em',
                        ])
                    >
                        <span
                            class="financial-metric-icon"
                            @style([
                                'width: 20px',
                                'height: 20px',
                                'border-radius: 999px',
                                'background-color: ' . $obligationStyle['iconBg'],
                                'color: ' . $obligationStyle['iconColor'],
                                'display: inline-flex',
                                'align-items: center',
                                'justify-content: center',
                                'font-size: 10px',
                            ])
                        >
                            <i class="fas fa-receipt"></i>
                        </span>
                        Obligation
                    </div>
                    <div
                        class="financial-amount-value"
                        @style([
                            'font-weight: 700',
                            'color: ' . $obligationStyle['valueColor'],
                        ])
                    >
                        {{ number_format((float) ($totalObligationAmount ?? 0), 2) }}
                    </div>
                </div>

                <div
                    class="dashboard-tile financial-metric-tile financial-disbursement-tile clickable-dashboard-card"
                    data-card-url="{{ route('fund-utilization.index') }}"
                    @style([
                        'padding: 12px',
                        'border: 1px solid ' . $disbursementStyle['border'],
                        'border-radius: 6px',
                        'background-color: ' . $disbursementStyle['bg'],
                        'text-align: center',
                    ])
                >
                    <div
                        class="financial-metric-label"
                        @style([
                            'display: flex',
                            'align-items: center',
                            'justify-content: center',
                            'gap: 8px',
                            'font-size: 13px',
                            'font-weight: 600',
                            'color: ' . $disbursementStyle['labelColor'],
                            'margin-bottom: 6px',
                            'text-transform: uppercase',
                            'letter-spacing: 0.04em',
                        ])
                    >
                        <span
                            class="financial-metric-icon"
                            @style([
                                'width: 20px',
                                'height: 20px',
                                'border-radius: 999px',
                                'background-color: ' . $disbursementStyle['iconBg'],
                                'color: ' . $disbursementStyle['iconColor'],
                                'display: inline-flex',
                                'align-items: center',
                                'justify-content: center',
                                'font-size: 10px',
                            ])
                        >
                            <i class="fas fa-money-check-dollar"></i>
                        </span>
                        Disbursement
                    </div>
                    <div
                        class="financial-amount-value"
                        @style([
                            'font-weight: 700',
                            'color: ' . $disbursementStyle['valueColor'],
                        ])
                    >
                        {{ number_format((float) ($totalDisbursementAmount ?? 0), 2) }}
                    </div>
                </div>

                <div
                    class="dashboard-tile financial-metric-tile financial-balance-tile clickable-dashboard-card"
                    data-card-url="{{ route('fund-utilization.index') }}"
                    data-modal-target="{{ $balanceProjectsModalId }}"
                    @style([
                        'padding: 12px',
                        'border: 1px solid ' . $balanceStyle['border'],
                        'border-radius: 6px',
                        'background-color: ' . $balanceStyle['bg'],
                        'text-align: center',
                    ])
                >
                    <div
                        class="financial-metric-label"
                        @style([
                            'display: flex',
                            'align-items: center',
                            'justify-content: center',
                            'gap: 8px',
                            'font-size: 13px',
                            'font-weight: 600',
                            'color: ' . $balanceStyle['labelColor'],
                            'margin-bottom: 6px',
                            'text-transform: uppercase',
                            'letter-spacing: 0.04em',
                        ])
                    >
                        <span
                            class="financial-metric-icon"
                            @style([
                                'width: 20px',
                                'height: 20px',
                                'border-radius: 999px',
                                'background-color: ' . $balanceStyle['iconBg'],
                                'color: ' . $balanceStyle['iconColor'],
                                'display: inline-flex',
                                'align-items: center',
                                'justify-content: center',
                                'font-size: 10px',
                            ])
                        >
                            <i class="fas fa-wallet"></i>
                        </span>
                        Balance
                    </div>
                    <div
                        class="financial-amount-value"
                        @style([
                            'font-weight: 700',
                            'color: ' . $balanceStyle['valueColor'],
                        ])
                    >
                        {{ number_format((float) ($totalBalanceAmount ?? 0), 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card expected-completion-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: 583px; display: flex; flex-direction: column;">
            @php
                $dueProjects = $asCollection($projectsExpectedCompletionThisMonth ?? null);
            @endphp
            <h2 style="color: #002C76; font-size: 16px; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;">
                <span style="width: 22px; height: 22px; border-radius: 999px; background-color: #dbeafe; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">
                    <i class="fas fa-calendar-check"></i>
                </span>
                PROJECTS EXPECTED TO BE COMPLETED ({{ strtoupper((string) ($expectedCompletionMonthLabel ?? now()->format('F Y'))) }})
            </h2>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 8px;">
            </div>

            @if ($dueProjects->isEmpty())
                <div style="padding: 14px; border: 1px dashed #bfdbfe; border-radius: 8px; background-color: #f8fbff; color: #1e3a8a; font-size: 13px;">
                    No projects are scheduled for completion this month.
                </div>
            @else
                <div class="expected-completion-list">
                    @foreach ($dueProjects as $dueProject)
                        @php
                            $projectCode = trim((string) ($dueProject->project_code ?? ''));
                            $projectTitle = trim((string) ($dueProject->project_title ?? ''));
                            $province = trim((string) ($dueProject->province ?? ''));
                            $cityMunicipality = trim((string) ($dueProject->city_municipality ?? ''));
                            $locationLabel = implode(' | ', array_values(array_filter([$province, $cityMunicipality], function ($value) {
                                return $value !== '';
                            })));
                            $completionDateLabel = 'N/A';

                            if (!empty($dueProject->expected_completion_date)) {
                                try {
                                    $completionDateLabel = \Illuminate\Support\Carbon::parse($dueProject->expected_completion_date)->format('M d, Y');
                                } catch (\Throwable $error) {
                                    $completionDateLabel = (string) $dueProject->expected_completion_date;
                                }
                            }

                            $searchTerm = $projectCode !== '' ? $projectCode : $projectTitle;
                        @endphp
                        <div
                            @class([
                                'expected-completion-item',
                                'clickable-dashboard-card' => $searchTerm !== '',
                            ])
                            @if ($searchTerm !== '')
                                data-card-url="{{ route('projects.locally-funded', ['search' => $searchTerm]) }}"
                            @endif
                        >
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div class="expected-completion-item-code">{{ $projectCode !== '' ? $projectCode : 'NO PROJECT CODE' }}</div>
                                <div class="expected-completion-item-date">{{ $completionDateLabel }}</div>
                            </div>
                            <div class="expected-completion-item-title">{{ $projectTitle !== '' ? $projectTitle : 'Untitled project' }}</div>
                            @if ($locationLabel !== '')
                                <div class="expected-completion-item-location">
                                    <i class="fas fa-location-dot" aria-hidden="true"></i>
                                    <span>{{ $locationLabel }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @php
            $statusSubaybayanSorted = collect($statusSubaybayanCounts)->sortDesc();
            $topStatusSubaybayanCount = (int) $statusSubaybayanSorted->max();
            $statusSubaybayanTotalCount = (int) $statusSubaybayanSorted->sum();
        @endphp
        <section class="dashboard-card sglgif-card status-subaybayan-card">
            <div class="sglgif-card-head">
                <div>
                    <h2>STATUS OF PROJECT (SUBAYBAYAN STATUS)</h2>
                    <p>Click a bar to open the filtered locally funded project list for that status.</p>
                </div>
            </div>

            <div class="status-subaybayan-grid" data-dashboard-status-bars>
                @forelse($statusSubaybayanSorted as $status => $count)
                    @php
                        $iconConfig = $statusIconMap[$status] ?? ['color' => '#2563eb', 'bg' => '#dbeafe', 'tileBg' => '#f8fbff', 'tileBorder' => '#bfdbfe', 'labelColor' => '#1e3a8a'];
                        $statusModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $status), '-');
                        $statusModalId = 'status-subaybayan-' . ($statusModalKey !== '' ? $statusModalKey : 'unspecified') . '-modal';
                        $statusFilterUrl = route('projects.locally-funded', [
                            'status' => $status,
                        ]);
                        $barWidth = $topStatusSubaybayanCount > 0 ? round((((int) $count) / $topStatusSubaybayanCount) * 100, 2) : 0;
                        $statusPercentage = $statusSubaybayanTotalCount > 0 ? round((((int) $count) / $statusSubaybayanTotalCount) * 100, 2) : 0;
                    @endphp
                    <div
                        class="sglgif-bar-row sglgif-bar-trigger clickable-dashboard-card status-subaybayan-bar-row"
                        data-card-url="{{ $statusFilterUrl }}"
                        data-modal-target="{{ $statusModalId }}"
                        data-sg-bar-animate="status-subaybayan"
                        style="--status-row-bg: {{ $iconConfig['tileBg'] }}; --status-row-border: {{ $iconConfig['tileBorder'] }}; --status-title-color: {{ $iconConfig['labelColor'] }};"
                    >
                        <div class="sglgif-bar-head">
                            <span>{{ $status }}</span>
                            <strong>
                                <span class="status-subaybayan-count" data-sg-bar-number data-format="integer" data-value="{{ (int) $count }}">{{ number_format((int) $count) }}</span>
                                <span class="status-subaybayan-percentage">({{ number_format($statusPercentage, 2) }}%)</span>
                            </strong>
                        </div>
                        <div class="sglgif-bar-track">
                            <div data-sg-bar-fill data-target-width="{{ $barWidth }}" style="width: {{ $barWidth }}%; background: linear-gradient(90deg, {{ $iconConfig['color'] }}, {{ $iconConfig['labelColor'] }});"></div>
                        </div>
                    </div>
                @empty
                    <p class="sglgif-empty">No status data for the current filter set.</p>
                @endforelse
            </div>
        </section>

    </div>

    @php
        $projectAtRiskChartOrder = ['Ahead', 'No Risk', 'On Schedule', 'High Risk', 'Moderate Risk', 'Low Risk'];
        $projectAtRiskSummaryOrder = ['Ahead', 'High Risk', 'Moderate Risk', 'Low Risk', 'No Risk', 'On Schedule'];
        $projectAtRiskLegendOrder = ['Ahead', 'On Schedule', 'No Risk', 'Low Risk', 'Moderate Risk', 'High Risk'];
        $projectAtRiskStyles = [
            'Ahead' => ['bg' => '#3f9142', 'badgeBg' => '#dcfce7', 'badgeColor' => '#166534'],
            'No Risk' => ['bg' => '#2f84cf', 'badgeBg' => '#dbeafe', 'badgeColor' => '#1d4ed8'],
            'On Schedule' => ['bg' => '#a3a3a3', 'badgeBg' => '#f3f4f6', 'badgeColor' => '#4b5563'],
            'High Risk' => ['bg' => '#c81d1d', 'badgeBg' => '#fee2e2', 'badgeColor' => '#b91c1c'],
            'Moderate Risk' => ['bg' => '#fb6f41', 'badgeBg' => '#ffedd5', 'badgeColor' => '#c2410c'],
            'Low Risk' => ['bg' => '#f6c000', 'badgeBg' => '#fef3c7', 'badgeColor' => '#b45309'],
        ];
        $projectAtRiskCriteria = [
            'Ahead' => 'Slippage is greater than 0%',
            'On Schedule' => 'Slippage is exactly 0%',
            'No Risk' => 'Slippage is from -0.01% to -4.99%',
            'Low Risk' => 'Slippage is from -5% to -9.99%',
            'Moderate Risk' => 'Slippage is from -10% to -14.99%',
            'High Risk' => 'Slippage is at or below -15%',
        ];
        $projectAtRiskAgingChartOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $projectAtRiskAgingSummaryOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $projectAtRiskAgingLegendOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $projectAtRiskAgingStyles = [
            'High Risk' => ['bg' => '#c81d1d', 'badgeBg' => '#fee2e2', 'badgeColor' => '#b91c1c'],
            'Low Risk' => ['bg' => '#f59e0b', 'badgeBg' => '#fef3c7', 'badgeColor' => '#b45309'],
            'No Risk' => ['bg' => '#16a34a', 'badgeBg' => '#dcfce7', 'badgeColor' => '#15803d'],
        ];
        $projectAtRiskAgingCriteria = [
            'High Risk' => 'Aging is greater than or equal to 60 days',
            'Low Risk' => 'Aging is greater than 30 days but less than 60 days',
            'No Risk' => 'Aging is less than or equal to 30 days',
        ];
        $projectUpdateStatusOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $projectUpdateStatusStyles = [
            'High Risk' => ['bg' => '#dc2626', 'badgeBg' => '#fee2e2', 'badgeColor' => '#b91c1c'],
            'Low Risk' => ['bg' => '#f59e0b', 'badgeBg' => '#fef3c7', 'badgeColor' => '#b45309'],
            'No Risk' => ['bg' => '#16a34a', 'badgeBg' => '#dcfce7', 'badgeColor' => '#15803d'],
        ];
        $projectUpdateStatusCriteria = [
            'High Risk' => 'For projects that are not completed, aging is greater than or equal to 60 days',
            'Low Risk' => 'For projects that are not completed, aging is greater than 30 days but less than 60 days',
            'No Risk' => 'For projects that are not completed, aging is less than or equal to 30 days',
        ];
        $projectUpdateStatusTotal = 0;
        foreach ($projectUpdateStatusOrder as $riskLabel) {
            $projectUpdateStatusTotal += (int) ($projectUpdateStatusCounts[$riskLabel] ?? 0);
        }
        $projectUpdateAllStatusModalId = 'project-update-all-status-modal';
        $projectUpdateAllStatusModalTitleId = $projectUpdateAllStatusModalId . '-title';
        $projectUpdateAllStatusModalSubtitle = 'Projects that are not completed, grouped into High Risk, Low Risk, and No Risk based on aging from SubayBAYAN date up to today.';
    @endphp

    <div class="dashboard-status-row" style="display: grid; gap: 20px;">
        <div class="dashboard-status-stack">
            <div class="dashboard-card project-update-status-card" style="background: white; padding: 12px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="color: #b91c1c; font-size: 14px; margin: 0 0 10px; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 18px; height: 18px; border-radius: 999px; background-color: #fee2e2; color: #dc2626; display: inline-flex; align-items: center; justify-content: center; font-size: 9px;">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                    <span class="dashboard-card-title-with-info">
                        <span>PROJECT UPDATE STATUS DASHBOARD</span>
                        <span class="dashboard-info-tooltip-wrap">
                            <button type="button" class="dashboard-info-tooltip-trigger" aria-label="Show project update status legend" title="Show project update status legend">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                            </button>
                            <div class="dashboard-info-tooltip" role="tooltip">
                                <div class="dashboard-info-tooltip-title">Legend</div>
                                @foreach ($projectUpdateStatusOrder as $riskLabel)
                                    @php
                                        $riskStyle = $projectUpdateStatusStyles[$riskLabel] ?? ['bg' => '#6b7280', 'badgeColor' => '#374151'];
                                        $criteriaText = $projectUpdateStatusCriteria[$riskLabel] ?? '';
                                    @endphp
                                    <div class="dashboard-info-tooltip-item">
                                        <span class="dashboard-info-tooltip-dot" @style(['background-color: ' . $riskStyle['bg']])></span>
                                        <div class="dashboard-info-tooltip-text">
                                            <strong @style(['color: ' . $riskStyle['badgeColor']])>{{ $riskLabel }}:</strong> {{ $criteriaText }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </span>
                    </span>
                </h2>
                <p class="project-update-status-description">
                    This dashboard shows the aging of ongoing projects.
                </p>
                <div class="project-update-status-chart">
                    @php
                        $projectUpdateStatusPieSegments = [];
                        $projectUpdateStatusGapPercent = 0.8;
                        $projectUpdateStatusSweepDurationMs = 1400.0;
                        $projectUpdateStatusMinSegmentDurationMs = 120.0;
                        $projectUpdateStatusCalloutStepMs = 140.0;
                        $projectUpdateStatusSweepEndMs = 0.0;

                        if ($projectUpdateStatusTotal > 0) {
                            $projectUpdateStatusBaseSegments = [];
                            foreach ($projectUpdateStatusOrder as $riskLabel) {
                                $riskCount = (int) ($projectUpdateStatusCounts[$riskLabel] ?? 0);
                                if ($riskCount < 1) {
                                    continue;
                                }

                                $riskStyle = $projectUpdateStatusStyles[$riskLabel] ?? ['bg' => '#6b7280'];
                                $projectUpdateStatusBaseSegments[] = [
                                    'label' => $riskLabel,
                                    'count' => $riskCount,
                                    'color' => $riskStyle['bg'],
                                ];
                            }

                            $projectUpdateStatusSegmentCount = count($projectUpdateStatusBaseSegments);
                            $projectUpdateStatusGapPercent = $projectUpdateStatusSegmentCount > 1 ? 0.8 : 0.0;
                            $projectUpdateStatusAvailablePercent = max(
                                0.0,
                                100.0 - ($projectUpdateStatusSegmentCount * $projectUpdateStatusGapPercent)
                            );
                            $projectUpdateStatusRunningPercent = 0.0;

                            foreach ($projectUpdateStatusBaseSegments as $segment) {
                                $segmentRawPercent = ($segment['count'] / $projectUpdateStatusTotal) * 100;
                                $segmentLength = ($segmentRawPercent / 100) * $projectUpdateStatusAvailablePercent;
                                if ($segmentLength <= 0.01) {
                                    continue;
                                }
                                $segmentDelayMs = ($projectUpdateStatusRunningPercent / 100) * $projectUpdateStatusSweepDurationMs;
                                $segmentDurationMs = max(
                                    $projectUpdateStatusMinSegmentDurationMs,
                                    ($segmentLength / 100) * $projectUpdateStatusSweepDurationMs
                                );
                                $projectUpdateStatusSweepEndMs = max(
                                    $projectUpdateStatusSweepEndMs,
                                    $segmentDelayMs + $segmentDurationMs
                                );

                                $projectUpdateStatusPieSegments[] = [
                                    'start' => $projectUpdateStatusRunningPercent,
                                    'length' => $segmentLength,
                                    'color' => $segment['color'],
                                    'label' => $segment['label'],
                                    'count' => $segment['count'],
                                    'percentage' => $segmentRawPercent,
                                    'segmentDelayMs' => $segmentDelayMs,
                                    'segmentDurationMs' => $segmentDurationMs,
                                ];

                                $projectUpdateStatusRunningPercent += $segmentLength + $projectUpdateStatusGapPercent;
                            }
                        }
                        $projectUpdateStatusCalloutStartDelayMs = $projectUpdateStatusSweepEndMs + 120.0;
                    @endphp
                    <div class="project-update-status-pie-layout">
                        <div
                            class="project-update-status-pie-wrap clickable-dashboard-card"
                            data-card-url="{{ route('projects.locally-funded') }}"
                            data-modal-target="{{ $projectUpdateAllStatusModalId }}"
                            aria-label="Open project update status project list"
                        >
                            <svg
                                class="project-update-status-pie"
                                viewBox="0 0 100 100"
                                aria-label="Project update status donut chart"
                                @style([
                                    '--callout-start-delay: ' . number_format($projectUpdateStatusCalloutStartDelayMs, 2, '.', '') . 'ms',
                                    '--callout-step-delay: ' . number_format($projectUpdateStatusCalloutStepMs, 2, '.', '') . 'ms',
                                ])
                            >
                                <circle class="project-update-status-pie-track" cx="50" cy="50" r="36" pathLength="100"></circle>
                                @foreach ($projectUpdateStatusPieSegments as $segment)
                                    @php
                                        $segmentMidAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                        $segmentMidRadians = deg2rad($segmentMidAngle);
                                        $segmentHoverOffset = 2.2;
                                        $segmentHoverX = $segmentHoverOffset * cos($segmentMidRadians);
                                        $segmentHoverY = $segmentHoverOffset * sin($segmentMidRadians);
                                    @endphp
                                    <circle
                                        class="project-update-status-pie-segment"
                                        cx="50"
                                        cy="50"
                                        r="36"
                                        pathLength="100"
                                        @style([
                                            '--segment-length: ' . number_format($segment['length'], 4, '.', ''),
                                            '--segment-delay: ' . number_format($segment['segmentDelayMs'], 2, '.', '') . 'ms',
                                            '--segment-duration: ' . number_format($segment['segmentDurationMs'], 2, '.', '') . 'ms',
                                            '--segment-hover-x: ' . number_format($segmentHoverX, 3, '.', '') . 'px',
                                            '--segment-hover-y: ' . number_format($segmentHoverY, 3, '.', '') . 'px',
                                            'stroke: ' . $segment['color'],
                                            'stroke-dashoffset: -' . number_format($segment['start'], 4, '.', ''),
                                        ])
                                    >
                                        <title>{{ $segment['label'] }}: {{ number_format($segment['percentage'], 2) }}%</title>
                                    </circle>
                                @endforeach
                                @foreach ($projectUpdateStatusPieSegments as $segment)
                                    @php
                                        $calloutAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                        $calloutRadians = deg2rad($calloutAngle);
                                        $calloutStartX = 50 + (46 * cos($calloutRadians));
                                        $calloutStartY = 50 + (46 * sin($calloutRadians));
                                        $calloutBendX = 50 + (52 * cos($calloutRadians));
                                        $calloutBendY = 50 + (52 * sin($calloutRadians));
                                        $calloutIsRight = cos($calloutRadians) >= 0;
                                        $calloutEndX = $calloutBendX + ($calloutIsRight ? 12 : -12);
                                        $calloutTextX = $calloutEndX + ($calloutIsRight ? 2.6 : -2.6);
                                        $calloutTextAnchor = $calloutIsRight ? 'start' : 'end';
                                    @endphp
                                    <g class="dashboard-donut-callout" aria-hidden="true" @style(['--callout-index: ' . $loop->index])>
                                        <polyline
                                            class="dashboard-donut-callout-line"
                                            points="{{ number_format($calloutStartX, 3, '.', '') }},{{ number_format($calloutStartY, 3, '.', '') }} {{ number_format($calloutBendX, 3, '.', '') }},{{ number_format($calloutBendY, 3, '.', '') }} {{ number_format($calloutEndX, 3, '.', '') }},{{ number_format($calloutBendY, 3, '.', '') }}"
                                        ></polyline>
                                        <text
                                            class="dashboard-donut-callout-text"
                                            x="{{ number_format($calloutTextX, 3, '.', '') }}"
                                            y="{{ number_format($calloutBendY, 3, '.', '') }}"
                                            text-anchor="{{ $calloutTextAnchor }}"
                                        >
                                            <tspan class="dashboard-donut-callout-label">{{ $segment['label'] }}</tspan>
                                            <tspan
                                                class="dashboard-donut-callout-value"
                                                x="{{ number_format($calloutTextX, 3, '.', '') }}"
                                                dy="1.15em"
                                            >
                                                {{ number_format((int) ($segment['count'] ?? 0)) }} ({{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%)
                                            </tspan>
                                        </text>
                                    </g>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="project-update-status-grid">
                    @foreach ($projectUpdateStatusOrder as $riskLabel)
                        @php
                            $riskCount = (int) ($projectUpdateStatusCounts[$riskLabel] ?? 0);
                            $riskStyle = $projectUpdateStatusStyles[$riskLabel] ?? ['bg' => '#6b7280', 'badgeBg' => '#f3f4f6', 'badgeColor' => '#374151'];
                            $riskModalId = 'project-update-' . strtolower(str_replace(' ', '-', $riskLabel)) . '-modal';
                            $projectUpdateStatusFilterUrl = route('projects.locally-funded', ['project_update_status' => $riskLabel]);
                        @endphp
                        <div
                            class="dashboard-tile project-update-status-tile clickable-dashboard-card"
                            data-card-url="{{ $projectUpdateStatusFilterUrl }}"
                            data-modal-target="{{ $riskModalId }}"
                        >
                            <div class="project-update-status-label">
                                <span
                                    class="project-update-status-badge"
                                    @style([
                                        'background-color: ' . $riskStyle['badgeBg'],
                                        'color: ' . $riskStyle['badgeColor'],
                                    ])
                                >
                                    {{ $riskLabel }}
                                </span>
                            </div>
                            <div class="project-update-status-value" @style(['background-color: ' . $riskStyle['bg']])>
                                {{ $riskCount }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dashboard-card project-risk-card project-risk-slippage-card" style="background: white; padding: 12px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="color: #b91c1c; font-size: 14px; margin: 0 0 10px; display: flex; align-items: center; gap: 8px;">
                <span style="width: 18px; height: 18px; border-radius: 999px; background-color: #fee2e2; color: #dc2626; display: inline-flex; align-items: center; justify-content: center; font-size: 9px;">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
                <span class="dashboard-card-title-with-info">
                    <span>PROJECT AT RISK AS TO SLIPPAGE</span>
                    <span class="dashboard-info-tooltip-wrap">
                        <button type="button" class="dashboard-info-tooltip-trigger" aria-label="Show slippage legend" title="Show slippage legend">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                        </button>
                        <div class="dashboard-info-tooltip" role="tooltip">
                            <div class="dashboard-info-tooltip-title">Legend</div>
                            @foreach ($projectAtRiskLegendOrder as $riskLabel)
                                @php
                                    $riskStyle = $projectAtRiskStyles[$riskLabel] ?? ['bg' => '#6b7280', 'badgeColor' => '#374151'];
                                    $criteriaText = $projectAtRiskCriteria[$riskLabel] ?? '';
                                @endphp
                                <div class="dashboard-info-tooltip-item">
                                    <span class="dashboard-info-tooltip-dot" @style(['background-color: ' . $riskStyle['bg']])></span>
                                    <div class="dashboard-info-tooltip-text">
                                        <strong @style(['color: ' . $riskStyle['badgeColor']])>{{ $riskLabel }}:</strong> {{ $criteriaText }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </span>
                </span>
            </h2>
            <p class="project-risk-slippage-description">
                These are the projects with slippages extracted in the SubayBAYAN Portal.
            </p>

            <div class="project-risk-donut-layout">
                @php
                    $projectAtRiskTotal = 0;
                    foreach ($projectAtRiskChartOrder as $riskLabel) {
                        $projectAtRiskTotal += (int) ($projectAtRiskCounts[$riskLabel] ?? 0);
                    }

                    $projectAtRiskDonutSegments = [];
                    $projectAtRiskSweepDurationMs = 1400.0;
                    $projectAtRiskMinSegmentDurationMs = 120.0;
                    $projectAtRiskCalloutStepMs = 140.0;
                    $projectAtRiskSweepEndMs = 0.0;

                    if ($projectAtRiskTotal > 0) {
                        $projectAtRiskBaseSegments = [];
                        foreach ($projectAtRiskChartOrder as $riskLabel) {
                            $riskCount = (int) ($projectAtRiskCounts[$riskLabel] ?? 0);
                            if ($riskCount < 1) {
                                continue;
                            }

                            $riskStyle = $projectAtRiskStyles[$riskLabel] ?? ['bg' => '#6b7280'];
                            $projectAtRiskBaseSegments[] = [
                                'label' => $riskLabel,
                                'count' => $riskCount,
                                'color' => $riskStyle['bg'],
                            ];
                        }

                        $projectAtRiskSegmentCount = count($projectAtRiskBaseSegments);
                        $projectAtRiskGapPercent = $projectAtRiskSegmentCount > 1 ? 0.8 : 0.0;
                        $projectAtRiskAvailablePercent = max(0.0, 100.0 - ($projectAtRiskSegmentCount * $projectAtRiskGapPercent));
                        $projectAtRiskRunningPercent = 0.0;

                        foreach ($projectAtRiskBaseSegments as $segment) {
                            $segmentRawPercent = ($segment['count'] / $projectAtRiskTotal) * 100;
                            $segmentLength = ($segmentRawPercent / 100) * $projectAtRiskAvailablePercent;
                            if ($segmentLength <= 0.01) {
                                continue;
                            }
                            $segmentDelayMs = ($projectAtRiskRunningPercent / 100) * $projectAtRiskSweepDurationMs;
                            $segmentDurationMs = max(
                                $projectAtRiskMinSegmentDurationMs,
                                ($segmentLength / 100) * $projectAtRiskSweepDurationMs
                            );
                            $projectAtRiskSweepEndMs = max(
                                $projectAtRiskSweepEndMs,
                                $segmentDelayMs + $segmentDurationMs
                            );

                            $projectAtRiskDonutSegments[] = [
                                'start' => $projectAtRiskRunningPercent,
                                'length' => $segmentLength,
                                'color' => $segment['color'],
                                'label' => $segment['label'],
                                'count' => $segment['count'],
                                'percentage' => $segmentRawPercent,
                                'segmentDelayMs' => $segmentDelayMs,
                                'segmentDurationMs' => $segmentDurationMs,
                            ];

                            $projectAtRiskRunningPercent += $segmentLength + $projectAtRiskGapPercent;
                        }
                    }
                    $projectAtRiskCalloutStartDelayMs = $projectAtRiskSweepEndMs + 120.0;
                @endphp
                <div class="project-risk-donut-wrap">
                    <svg
                        class="project-risk-donut"
                        viewBox="0 0 100 100"
                        aria-label="Project at risk as to slippage donut chart"
                        @style([
                            '--callout-start-delay: ' . number_format($projectAtRiskCalloutStartDelayMs, 2, '.', '') . 'ms',
                            '--callout-step-delay: ' . number_format($projectAtRiskCalloutStepMs, 2, '.', '') . 'ms',
                        ])
                    >
                        <circle class="project-risk-donut-track" cx="50" cy="50" r="36" pathLength="100"></circle>
                        @foreach ($projectAtRiskDonutSegments as $segment)
                            @php
                                $segmentMidAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                $segmentMidRadians = deg2rad($segmentMidAngle);
                                $segmentHoverOffset = 2.2;
                                $segmentHoverX = $segmentHoverOffset * cos($segmentMidRadians);
                                $segmentHoverY = $segmentHoverOffset * sin($segmentMidRadians);
                            @endphp
                            <circle
                                class="project-risk-donut-segment"
                                cx="50"
                                cy="50"
                                r="36"
                                pathLength="100"
                                @style([
                                    '--segment-length: ' . number_format($segment['length'], 4, '.', ''),
                                    '--segment-delay: ' . number_format($segment['segmentDelayMs'], 2, '.', '') . 'ms',
                                    '--segment-duration: ' . number_format($segment['segmentDurationMs'], 2, '.', '') . 'ms',
                                    '--segment-hover-x: ' . number_format($segmentHoverX, 3, '.', '') . 'px',
                                    '--segment-hover-y: ' . number_format($segmentHoverY, 3, '.', '') . 'px',
                                    'stroke: ' . $segment['color'],
                                    'stroke-dashoffset: -' . number_format($segment['start'], 4, '.', ''),
                                ])
                            >
                                <title>{{ $segment['label'] }}: {{ number_format($segment['percentage'], 2) }}%</title>
                            </circle>
                        @endforeach
                        @foreach ($projectAtRiskDonutSegments as $segment)
                            @php
                                $calloutAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                $calloutRadians = deg2rad($calloutAngle);
                                $calloutStartX = 50 + (46 * cos($calloutRadians));
                                $calloutStartY = 50 + (46 * sin($calloutRadians));
                                $calloutBendX = 50 + (52 * cos($calloutRadians));
                                $calloutBendY = 50 + (52 * sin($calloutRadians));
                                $calloutIsRight = cos($calloutRadians) >= 0;
                                $calloutEndX = $calloutBendX + ($calloutIsRight ? 12 : -12);
                                $calloutTextX = $calloutEndX + ($calloutIsRight ? 2.6 : -2.6);
                                $calloutTextAnchor = $calloutIsRight ? 'start' : 'end';
                            @endphp
                            <g class="dashboard-donut-callout" aria-hidden="true" @style(['--callout-index: ' . $loop->index])>
                                <polyline
                                    class="dashboard-donut-callout-line"
                                    points="{{ number_format($calloutStartX, 3, '.', '') }},{{ number_format($calloutStartY, 3, '.', '') }} {{ number_format($calloutBendX, 3, '.', '') }},{{ number_format($calloutBendY, 3, '.', '') }} {{ number_format($calloutEndX, 3, '.', '') }},{{ number_format($calloutBendY, 3, '.', '') }}"
                                ></polyline>
                                <text
                                    class="dashboard-donut-callout-text"
                                    x="{{ number_format($calloutTextX, 3, '.', '') }}"
                                    y="{{ number_format($calloutBendY, 3, '.', '') }}"
                                    text-anchor="{{ $calloutTextAnchor }}"
                                >
                                    <tspan class="dashboard-donut-callout-label">{{ $segment['label'] }}</tspan>
                                    <tspan
                                        class="dashboard-donut-callout-value"
                                        x="{{ number_format($calloutTextX, 3, '.', '') }}"
                                        dy="1.15em"
                                    >
                                        {{ number_format((int) ($segment['count'] ?? 0)) }} ({{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%)
                                    </tspan>
                                </text>
                            </g>
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="project-risk-summary-grid">
                @foreach ($projectAtRiskSummaryOrder as $riskLabel)
                    @php
                        $riskCount = (int) ($projectAtRiskCounts[$riskLabel] ?? 0);
                        $riskStyle = $projectAtRiskStyles[$riskLabel] ?? ['bg' => '#6b7280', 'badgeBg' => '#f3f4f6', 'badgeColor' => '#374151'];
                        $projectAtRiskFilterUrl = route('projects.at-risk', [
                            'risk_level' => $riskLabel,
                        ]);
                    @endphp
                    <div class="dashboard-tile clickable-dashboard-card project-update-status-tile project-risk-status-tile" data-card-url="{{ $projectAtRiskFilterUrl }}">
                        <div class="project-update-status-label project-risk-summary-label">
                            <span
                                class="project-update-status-badge project-risk-summary-badge"
                                @style([
                                    'background-color: ' . $riskStyle['badgeBg'],
                                    'color: ' . $riskStyle['badgeColor'],
                                ])
                            >
                                {{ $riskLabel }}
                            </span>
                        </div>
                        <div class="project-update-status-value project-risk-summary-value" @style(['background-color: ' . $riskStyle['bg']])>
                            {{ $riskCount }}
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        <div class="dashboard-card project-risk-card project-risk-aging-card" style="background: white; padding: 12px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="color: #b45309; font-size: 14px; margin: 0 0 10px; display: flex; align-items: center; gap: 8px;">
                <span style="width: 18px; height: 18px; border-radius: 999px; background-color: #ffedd5; color: #b45309; display: inline-flex; align-items: center; justify-content: center; font-size: 9px;">
                    <i class="fas fa-hourglass-half"></i>
                </span>
                <span class="dashboard-card-title-with-info">
                    <span>AGING OF THE PROJECTS WITH SLIPPAGE</span>
                    <span class="dashboard-info-tooltip-wrap">
                        <button type="button" class="dashboard-info-tooltip-trigger" aria-label="Show aging legend" title="Show aging legend">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                        </button>
                        <div class="dashboard-info-tooltip" role="tooltip">
                            <div class="dashboard-info-tooltip-title">Legend</div>
                            @foreach ($projectAtRiskAgingLegendOrder as $riskLabel)
                                @php
                                    $riskStyle = $projectAtRiskAgingStyles[$riskLabel] ?? ['bg' => '#6b7280', 'badgeColor' => '#374151'];
                                    $criteriaText = $projectAtRiskAgingCriteria[$riskLabel] ?? '';
                                @endphp
                                <div class="dashboard-info-tooltip-item">
                                    <span class="dashboard-info-tooltip-dot" @style(['background-color: ' . $riskStyle['bg']])></span>
                                    <div class="dashboard-info-tooltip-text">
                                        <strong @style(['color: ' . $riskStyle['badgeColor']])>{{ $riskLabel }}:</strong> {{ $criteriaText }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </span>
                </span>
            </h2>
            <p class="project-risk-aging-description">
                These are the risk levels created by the RO to identify the risk level of aging of projects with slippages.
            </p>

            <div class="project-risk-donut-layout">
                @php
                    $projectAtRiskAgingTotal = 0;
                    foreach ($projectAtRiskAgingChartOrder as $riskLabel) {
                        $projectAtRiskAgingTotal += (int) ($projectAtRiskAgingCounts[$riskLabel] ?? 0);
                    }

                    $projectAtRiskAgingDonutSegments = [];
                    $projectAtRiskAgingSweepDurationMs = 1400.0;
                    $projectAtRiskAgingMinSegmentDurationMs = 120.0;
                    $projectAtRiskAgingCalloutStepMs = 140.0;
                    $projectAtRiskAgingSweepEndMs = 0.0;
                    if ($projectAtRiskAgingTotal > 0) {
                        $projectAtRiskAgingBaseSegments = [];
                        foreach ($projectAtRiskAgingChartOrder as $riskLabel) {
                            $riskCount = (int) ($projectAtRiskAgingCounts[$riskLabel] ?? 0);
                            if ($riskCount < 1) {
                                continue;
                            }

                            $riskStyle = $projectAtRiskAgingStyles[$riskLabel] ?? ['bg' => '#6b7280'];
                            $projectAtRiskAgingBaseSegments[] = [
                                'label' => $riskLabel,
                                'count' => $riskCount,
                                'color' => $riskStyle['bg'],
                            ];
                        }

                        $projectAtRiskAgingSegmentCount = count($projectAtRiskAgingBaseSegments);
                        $projectAtRiskAgingGapPercent = $projectAtRiskAgingSegmentCount > 1 ? 0.8 : 0.0;
                        $projectAtRiskAgingAvailablePercent = max(0.0, 100.0 - ($projectAtRiskAgingSegmentCount * $projectAtRiskAgingGapPercent));
                        $projectAtRiskAgingRunningPercent = 0.0;

                        foreach ($projectAtRiskAgingBaseSegments as $segment) {
                            $segmentRawPercent = ($segment['count'] / $projectAtRiskAgingTotal) * 100;
                            $segmentLength = ($segmentRawPercent / 100) * $projectAtRiskAgingAvailablePercent;
                            if ($segmentLength <= 0.01) {
                                continue;
                            }
                            $segmentDelayMs = ($projectAtRiskAgingRunningPercent / 100) * $projectAtRiskAgingSweepDurationMs;
                            $segmentDurationMs = max(
                                $projectAtRiskAgingMinSegmentDurationMs,
                                ($segmentLength / 100) * $projectAtRiskAgingSweepDurationMs
                            );
                            $projectAtRiskAgingSweepEndMs = max(
                                $projectAtRiskAgingSweepEndMs,
                                $segmentDelayMs + $segmentDurationMs
                            );

                            $projectAtRiskAgingDonutSegments[] = [
                                'start' => $projectAtRiskAgingRunningPercent,
                                'length' => $segmentLength,
                                'color' => $segment['color'],
                                'label' => $segment['label'],
                                'count' => $segment['count'],
                                'percentage' => $segmentRawPercent,
                                'segmentDelayMs' => $segmentDelayMs,
                                'segmentDurationMs' => $segmentDurationMs,
                            ];

                            $projectAtRiskAgingRunningPercent += $segmentLength + $projectAtRiskAgingGapPercent;
                        }
                    }
                    $projectAtRiskAgingCalloutStartDelayMs = $projectAtRiskAgingSweepEndMs + 120.0;
                @endphp
                <div class="project-risk-donut-wrap">
                    <svg
                        class="project-risk-donut"
                        viewBox="0 0 100 100"
                        aria-label="Aging of the projects with slippage donut chart"
                        @style([
                            '--callout-start-delay: ' . number_format($projectAtRiskAgingCalloutStartDelayMs, 2, '.', '') . 'ms',
                            '--callout-step-delay: ' . number_format($projectAtRiskAgingCalloutStepMs, 2, '.', '') . 'ms',
                        ])
                    >
                        <circle class="project-risk-donut-track" cx="50" cy="50" r="36" pathLength="100"></circle>
                        @foreach ($projectAtRiskAgingDonutSegments as $segment)
                            @php
                                $segmentMidAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                $segmentMidRadians = deg2rad($segmentMidAngle);
                                $segmentHoverOffset = 2.2;
                                $segmentHoverX = $segmentHoverOffset * cos($segmentMidRadians);
                                $segmentHoverY = $segmentHoverOffset * sin($segmentMidRadians);
                            @endphp
                            <circle
                                class="project-risk-donut-segment"
                                cx="50"
                                cy="50"
                                r="36"
                                pathLength="100"
                                @style([
                                    '--segment-length: ' . number_format($segment['length'], 4, '.', ''),
                                    '--segment-delay: ' . number_format($segment['segmentDelayMs'], 2, '.', '') . 'ms',
                                    '--segment-duration: ' . number_format($segment['segmentDurationMs'], 2, '.', '') . 'ms',
                                    '--segment-hover-x: ' . number_format($segmentHoverX, 3, '.', '') . 'px',
                                    '--segment-hover-y: ' . number_format($segmentHoverY, 3, '.', '') . 'px',
                                    'stroke: ' . $segment['color'],
                                    'stroke-dashoffset: -' . number_format($segment['start'], 4, '.', ''),
                                ])
                            >
                                <title>{{ $segment['label'] }}: {{ number_format($segment['percentage'], 2) }}%</title>
                            </circle>
                        @endforeach
                        @foreach ($projectAtRiskAgingDonutSegments as $segment)
                            @php
                                $calloutAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                $calloutRadians = deg2rad($calloutAngle);
                                $calloutStartX = 50 + (46 * cos($calloutRadians));
                                $calloutStartY = 50 + (46 * sin($calloutRadians));
                                $calloutBendX = 50 + (52 * cos($calloutRadians));
                                $calloutBendY = 50 + (52 * sin($calloutRadians));
                                $calloutIsRight = cos($calloutRadians) >= 0;
                                $calloutEndX = $calloutBendX + ($calloutIsRight ? 12 : -12);
                                $calloutTextX = $calloutEndX + ($calloutIsRight ? 2.6 : -2.6);
                                $calloutTextAnchor = $calloutIsRight ? 'start' : 'end';
                            @endphp
                            <g class="dashboard-donut-callout" aria-hidden="true" @style(['--callout-index: ' . $loop->index])>
                                <polyline
                                    class="dashboard-donut-callout-line"
                                    points="{{ number_format($calloutStartX, 3, '.', '') }},{{ number_format($calloutStartY, 3, '.', '') }} {{ number_format($calloutBendX, 3, '.', '') }},{{ number_format($calloutBendY, 3, '.', '') }} {{ number_format($calloutEndX, 3, '.', '') }},{{ number_format($calloutBendY, 3, '.', '') }}"
                                ></polyline>
                                <text
                                    class="dashboard-donut-callout-text"
                                    x="{{ number_format($calloutTextX, 3, '.', '') }}"
                                    y="{{ number_format($calloutBendY, 3, '.', '') }}"
                                    text-anchor="{{ $calloutTextAnchor }}"
                                >
                                    <tspan class="dashboard-donut-callout-label">{{ $segment['label'] }}</tspan>
                                    <tspan
                                        class="dashboard-donut-callout-value"
                                        x="{{ number_format($calloutTextX, 3, '.', '') }}"
                                        dy="1.15em"
                                    >
                                        {{ number_format((int) ($segment['count'] ?? 0)) }} ({{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%)
                                    </tspan>
                                </text>
                            </g>
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="project-risk-summary-grid">
                @foreach ($projectAtRiskAgingSummaryOrder as $riskLabel)
                    @php
                        $riskCount = (int) ($projectAtRiskAgingCounts[$riskLabel] ?? 0);
                        $riskStyle = $projectAtRiskAgingStyles[$riskLabel] ?? ['bg' => '#6b7280', 'badgeBg' => '#f3f4f6', 'badgeColor' => '#374151'];
                        $agingModalId = 'project-risk-aging-' . strtolower(str_replace(' ', '-', $riskLabel)) . '-modal';
                    @endphp
                    <div
                        class="dashboard-tile clickable-dashboard-card project-update-status-tile project-risk-status-tile"
                        data-card-url="{{ route('projects.at-risk') }}"
                        data-modal-target="{{ $agingModalId }}"
                    >
                        <div class="project-update-status-label project-risk-summary-label">
                            <span
                                class="project-update-status-badge project-risk-summary-badge"
                                @style([
                                    'background-color: ' . $riskStyle['badgeBg'],
                                    'color: ' . $riskStyle['badgeColor'],
                                ])
                            >
                                {{ $riskLabel }}
                            </span>
                        </div>
                        <div class="project-update-status-value project-risk-summary-value" @style(['background-color: ' . $riskStyle['bg']])>
                            {{ $riskCount }}
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
    </div>

    @php
        $projectAtRiskAgingProjectsModal = $projectAtRiskAgingProjects ?? [];
        $statusSubaybayanProjectsModalMap = $statusSubaybayanProjectsMap ?? [];
        $fundSourceProjectsModalMap = $fundSourceProjectsMap ?? [];
        $balanceProjectsModal = $asCollection($projectsWithBalance ?? null);
        $balanceProjectsModalSubtitle = 'Projects with remaining balance from SubayBAYAN. Balance formula: Original Allocation - (Disbursement + Reverted Allocation). LGU Counterpart is shown as a separate column.';
        $projectAtRiskAgingModalSubtitles = [
            'High Risk' => 'Aging is greater than or equal to 60 days based on the latest Project at Risk extraction data.',
            'Low Risk' => 'Aging is greater than 30 days but less than 60 days based on the latest Project at Risk extraction data.',
            'No Risk' => 'Aging is less than or equal to 30 days based on the latest Project at Risk extraction data.',
        ];
        $projectUpdateRiskProjectsModal = $projectUpdateRiskProjects ?? [];
        $projectUpdateModalSubtitles = [
            'High Risk' => 'Projects that are not completed with aging greater than or equal to 60 days based on SubayBAYAN date compared with today.',
            'Low Risk' => 'Projects that are not completed with aging greater than 30 days but less than 60 days based on SubayBAYAN date compared with today.',
            'No Risk' => 'Projects that are not completed with aging less than or equal to 30 days based on SubayBAYAN date compared with today.',
        ];
    @endphp
    @foreach ($statusSubaybayanCounts as $status => $count)
        @php
            $statusModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $status), '-');
            $statusModalId = 'status-subaybayan-' . ($statusModalKey !== '' ? $statusModalKey : 'unspecified') . '-modal';
            $statusModalTitleId = $statusModalId . '-title';
            $statusModalProjects = $asCollection($statusSubaybayanProjectsModalMap[$status] ?? null);
        @endphp
        <div id="{{ $statusModalId }}" class="dashboard-modal" aria-hidden="true">
            <div class="dashboard-modal-backdrop" data-close-modal></div>
            <div class="dashboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $statusModalTitleId }}">
                <div class="dashboard-modal-header">
                    <h3 id="{{ $statusModalTitleId }}">{{ $status }} Projects</h3>
                    <button type="button" class="dashboard-modal-close" data-close-modal aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="dashboard-modal-subtitle">
                    Projects with {{ $status }} status in SubayBAYAN, based on the current dashboard filters.
                </p>
                <div class="dashboard-modal-body">
                    @if ($statusModalProjects->isNotEmpty())
                        <div class="dashboard-modal-table-wrap">
                            <table class="dashboard-modal-table">
                                <thead>
                                    <tr>
                                        <th>Project Code</th>
                                        <th>Project Title</th>
                                        <th>Province</th>
                                        <th>City/Municipality</th>
                                        <th>Funding Year</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statusModalProjects as $projectRow)
                                        <tr>
                                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                                            <td>{{ $projectRow->province ?: '-' }}</td>
                                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                                            <td>{{ $projectRow->funding_year ?: '-' }}</td>
                                            <td>{{ $projectRow->status ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="dashboard-modal-empty-state">
                            No {{ strtolower($status) }} projects found for the current dashboard filters.
                        </div>
                    @endif
                </div>
                <div class="dashboard-modal-footer">
                    <button
                        type="button"
                        class="dashboard-modal-export-btn"
                        onclick="exportDashboardModalTableToExcel(this)"
                        data-export-filename="status-subaybayan-{{ $statusModalKey !== '' ? $statusModalKey : 'unspecified' }}.xls"
                        @disabled($statusModalProjects->isEmpty())
                    >
                        Export Excel
                    </button>
                </div>
            </div>
        </div>
    @endforeach
    @if (!empty($fundSourceCounts) && $fundSourceCounts->count() > 0)
        @foreach ($fundSourceCounts as $fundSource => $count)
            @php
                $fundSourceModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $fundSource), '-');
                $fundSourceModalId = 'fund-source-' . ($fundSourceModalKey !== '' ? $fundSourceModalKey : 'unspecified') . '-modal';
                $fundSourceModalTitleId = $fundSourceModalId . '-title';
                $fundSourceModalProjects = $asCollection(
                    $fundSourceProjectsModalMap[$fundSource]
                        ?? $fundSourceProjectsModalMap[strtoupper(trim((string) $fundSource))]
                        ?? null
                );
            @endphp
            <div id="{{ $fundSourceModalId }}" class="dashboard-modal" aria-hidden="true">
                <div class="dashboard-modal-backdrop" data-close-modal></div>
                <div class="dashboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $fundSourceModalTitleId }}">
                    <div class="dashboard-modal-header">
                        <h3 id="{{ $fundSourceModalTitleId }}">{{ $fundSource }} Projects</h3>
                        <button type="button" class="dashboard-modal-close" data-close-modal aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="dashboard-modal-subtitle">
                        Projects under {{ $fundSource }} based on the current dashboard filters.
                    </p>
                    <div class="dashboard-modal-body">
                        @if ($fundSourceModalProjects->isNotEmpty())
                            <div class="dashboard-modal-table-wrap">
                                <table class="dashboard-modal-table">
                                    <thead>
                                        <tr>
                                            <th>Project Code</th>
                                            <th>Project Title</th>
                                            <th>Province</th>
                                            <th>City/Municipality</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fundSourceModalProjects as $projectRow)
                                            <tr>
                                                <td>{{ $projectRow->project_code ?? '-' }}</td>
                                                <td>{{ $projectRow->project_title ?: '-' }}</td>
                                                <td>{{ $projectRow->province ?: '-' }}</td>
                                                <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                                                <td>{{ $projectRow->status ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="dashboard-modal-empty-state">
                                No projects found for {{ $fundSource }} under the current dashboard filters.
                            </div>
                        @endif
                    </div>
                    <div class="dashboard-modal-footer">
                        <button
                            type="button"
                            class="dashboard-modal-export-btn"
                            onclick="exportDashboardModalTableToExcel(this)"
                            data-export-filename="projects-by-fund-source-{{ $fundSourceModalKey !== '' ? $fundSourceModalKey : 'unspecified' }}.xls"
                            @disabled($fundSourceModalProjects->isEmpty())
                        >
                            Export Excel
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
    <div id="{{ $balanceProjectsModalId }}" class="dashboard-modal" aria-hidden="true">
        <div class="dashboard-modal-backdrop" data-close-modal></div>
        <div class="dashboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $balanceProjectsModalTitleId }}">
            <div class="dashboard-modal-header">
                <h3 id="{{ $balanceProjectsModalTitleId }}">Projects With Balance</h3>
                <button type="button" class="dashboard-modal-close" data-close-modal aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="dashboard-modal-subtitle">
                {{ $balanceProjectsModalSubtitle }}
            </p>
            <div class="dashboard-modal-body">
                @if ($balanceProjectsModal->isNotEmpty())
                    <div class="dashboard-modal-table-wrap">
                        @php
                            $balanceTotals = [
                                'original_allocation' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->original_allocation ?? 0)),
                                'lgu_counterpart' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->lgu_counterpart ?? 0)),
                                'reverted_allocation' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->reverted_allocation ?? 0)),
                                'obligation' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->obligation ?? 0)),
                                'disbursement' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->disbursement ?? 0)),
                                'balance' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->balance ?? 0)),
                            ];
                        @endphp
                        <table class="dashboard-modal-table">
                            <thead>
                                <tr>
                                    <th>Project Code</th>
                                    <th>Project Title</th>
                                    <th>Status</th>
                                    <th>Original Allocation</th>
                                    <th>LGU Counterpart</th>
                                    <th>Reverted Allocation</th>
                                    <th>Obligation</th>
                                    <th>Disbursement</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($balanceProjectsModal as $projectRow)
                                    @php
                                        $originalAllocation = $projectRow->original_allocation ?? null;
                                        $lguCounterpart = $projectRow->lgu_counterpart ?? null;
                                        $revertedAllocation = $projectRow->reverted_allocation ?? null;
                                        $obligationAmount = $projectRow->obligation ?? null;
                                        $disbursementAmount = $projectRow->disbursement ?? null;
                                        $balanceAmount = $projectRow->balance ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $projectRow->project_code ?? '-' }}</td>
                                        <td>{{ $projectRow->project_title ?: '-' }}</td>
                                        <td>{{ $projectRow->status ?: '-' }}</td>
                                        <td>{{ $originalAllocation !== null ? number_format((float) $originalAllocation, 2) : '-' }}</td>
                                        <td>{{ $lguCounterpart !== null ? number_format((float) $lguCounterpart, 2) : '-' }}</td>
                                        <td>{{ $revertedAllocation !== null ? number_format((float) $revertedAllocation, 2) : '-' }}</td>
                                        <td>{{ $obligationAmount !== null ? number_format((float) $obligationAmount, 2) : '-' }}</td>
                                        <td>{{ $disbursementAmount !== null ? number_format((float) $disbursementAmount, 2) : '-' }}</td>
                                        <td>{{ $balanceAmount !== null ? number_format((float) $balanceAmount, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="dashboard-modal-total-row">
                                    <td colspan="3">Total</td>
                                    <td>{{ number_format($balanceTotals['original_allocation'], 2) }}</td>
                                    <td>{{ number_format($balanceTotals['lgu_counterpart'], 2) }}</td>
                                    <td>{{ number_format($balanceTotals['reverted_allocation'], 2) }}</td>
                                    <td>{{ number_format($balanceTotals['obligation'], 2) }}</td>
                                    <td>{{ number_format($balanceTotals['disbursement'], 2) }}</td>
                                    <td>{{ number_format($balanceTotals['balance'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dashboard-modal-empty-state">
                        No projects with balance found for the current dashboard filters.
                    </div>
                @endif
            </div>
            <div class="dashboard-modal-footer">
                <button
                    type="button"
                    class="dashboard-modal-export-btn"
                    onclick="exportDashboardModalTableToExcel(this)"
                    data-export-filename="projects-with-balance.xls"
                    @disabled($balanceProjectsModal->isEmpty())
                >
                    Export Excel
                </button>
            </div>
        </div>
    </div>
    @foreach ($projectAtRiskAgingSummaryOrder as $riskLabel)
        @php
            $riskKey = strtolower(str_replace(' ', '-', $riskLabel));
            $modalId = 'project-risk-aging-' . $riskKey . '-modal';
            $modalTitleId = $modalId . '-title';
            $modalProjects = $asCollection($projectAtRiskAgingProjectsModal[$riskLabel] ?? null);
            $subtitleText = $projectAtRiskAgingModalSubtitles[$riskLabel] ?? '';
        @endphp
        <div id="{{ $modalId }}" class="dashboard-modal" aria-hidden="true">
            <div class="dashboard-modal-backdrop" data-close-modal></div>
            <div class="dashboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $modalTitleId }}">
                <div class="dashboard-modal-header">
                    <h3 id="{{ $modalTitleId }}">{{ $riskLabel }} Aging Projects</h3>
                    <button type="button" class="dashboard-modal-close" data-close-modal aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="dashboard-modal-subtitle">
                    {{ $subtitleText }}
                </p>
                <div class="dashboard-modal-body">
                    @if ($modalProjects->isNotEmpty())
                        <div class="dashboard-modal-table-wrap">
                            <table class="dashboard-modal-table">
                                <thead>
                                    <tr>
                                        <th>Project Code</th>
                                        <th>Project Title</th>
                                        <th>Province</th>
                                        <th>City/Municipality</th>
                                        <th>Extraction Date</th>
                                        <th>Aging (Days)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modalProjects as $projectRow)
                                        @php
                                            $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                                            $latestUpdateDate = $latestUpdateDateRaw !== ''
                                                ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                                : '-';
                                        @endphp
                                        <tr>
                                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                                            <td>{{ $projectRow->province ?: '-' }}</td>
                                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                                            <td>{{ $latestUpdateDate }}</td>
                                            <td>{{ $projectRow->aging_days ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="dashboard-modal-empty-state">
                            No {{ strtolower($riskLabel) }} aging projects found for the current dashboard filters.
                        </div>
                    @endif
                </div>
                <div class="dashboard-modal-footer">
                    <button
                        type="button"
                        class="dashboard-modal-export-btn"
                        onclick="exportDashboardModalTableToExcel(this)"
                        data-export-filename="aging-of-projects-with-slippage-{{ $riskKey }}.xls"
                        @disabled($modalProjects->isEmpty())
                    >
                        Export Excel
                    </button>
                </div>
            </div>
        </div>
    @endforeach
    @php
        $projectUpdateAllStatusHasProjects = false;
        foreach ($projectUpdateStatusOrder as $riskLabel) {
            if (collect($projectUpdateRiskProjectsModal[$riskLabel] ?? [])->isNotEmpty()) {
                $projectUpdateAllStatusHasProjects = true;
                break;
            }
        }
    @endphp
    <div id="{{ $projectUpdateAllStatusModalId }}" class="dashboard-modal" aria-hidden="true">
        <div class="dashboard-modal-backdrop" data-close-modal></div>
        <div class="dashboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $projectUpdateAllStatusModalTitleId }}">
            <div class="dashboard-modal-header">
                <h3 id="{{ $projectUpdateAllStatusModalTitleId }}">Project Update Status - All Risk Levels</h3>
                <button type="button" class="dashboard-modal-close" data-close-modal aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="dashboard-modal-subtitle">
                {{ $projectUpdateAllStatusModalSubtitle }}
            </p>
            <div class="dashboard-modal-body">
                @if ($projectUpdateAllStatusHasProjects)
                    <div class="dashboard-modal-table-wrap">
                        <table class="dashboard-modal-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Project Code</th>
                                    <th>Project Title</th>
                                    <th>Province</th>
                                    <th>City/Municipality</th>
                                    <th>Latest Date</th>
                                    <th>Aging (Days)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($projectUpdateStatusOrder as $riskLabel)
                                    @php
                                        $riskStyle = $projectUpdateStatusStyles[$riskLabel] ?? ['badgeBg' => '#f3f4f6', 'badgeColor' => '#374151'];
                                        $riskProjects = $asCollection($projectUpdateRiskProjectsModal[$riskLabel] ?? null);
                                    @endphp
                                    @foreach ($riskProjects as $projectRow)
                                        @php
                                            $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                                            $latestUpdateDate = $latestUpdateDateRaw !== ''
                                                ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                                : '-';
                                        @endphp
                                        <tr>
                                            <td>
                                                <span
                                                    class="project-update-status-badge"
                                                    @style([
                                                        'background-color: ' . $riskStyle['badgeBg'],
                                                        'color: ' . $riskStyle['badgeColor'],
                                                    ])
                                                >
                                                    {{ $riskLabel }}
                                                </span>
                                            </td>
                                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                                            <td>{{ $projectRow->province ?: '-' }}</td>
                                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                                            <td>{{ $latestUpdateDate }}</td>
                                            <td>{{ $projectRow->aging_days ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dashboard-modal-empty-state">
                        No project update status projects found for the current dashboard filters.
                    </div>
                @endif
            </div>
            <div class="dashboard-modal-footer">
                <button
                    type="button"
                    class="dashboard-modal-export-btn"
                    onclick="exportDashboardModalTableToExcel(this)"
                    data-export-filename="project-update-all-risk-levels.xls"
                    @disabled(!$projectUpdateAllStatusHasProjects)
                >
                    Export Excel
                </button>
            </div>
        </div>
    </div>
    @foreach ($projectUpdateStatusOrder as $riskLabel)
        @php
            $riskKey = strtolower(str_replace(' ', '-', $riskLabel));
            $modalId = 'project-update-' . $riskKey . '-modal';
            $modalTitleId = $modalId . '-title';
            $modalProjects = $asCollection($projectUpdateRiskProjectsModal[$riskLabel] ?? null);
            $subtitleText = $projectUpdateModalSubtitles[$riskLabel] ?? '';
        @endphp
        <div id="{{ $modalId }}" class="dashboard-modal" aria-hidden="true">
            <div class="dashboard-modal-backdrop" data-close-modal></div>
            <div class="dashboard-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $modalTitleId }}">
                <div class="dashboard-modal-header">
                    <h3 id="{{ $modalTitleId }}">{{ $riskLabel }} Projects</h3>
                    <button type="button" class="dashboard-modal-close" data-close-modal aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="dashboard-modal-subtitle">
                    {{ $subtitleText }}
                </p>
                <div class="dashboard-modal-body">
                    @if ($modalProjects->isNotEmpty())
                        <div class="dashboard-modal-table-wrap">
                            <table class="dashboard-modal-table">
                                <thead>
                                    <tr>
                                        <th>Project Code</th>
                                        <th>Project Title</th>
                                        <th>Province</th>
                                        <th>City/Municipality</th>
                                        <th>Latest Date</th>
                                        <th>Aging (Days)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modalProjects as $projectRow)
                                        @php
                                            $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                                            $latestUpdateDate = $latestUpdateDateRaw !== ''
                                                ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                                : '-';
                                        @endphp
                                        <tr>
                                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                                            <td>{{ $projectRow->province ?: '-' }}</td>
                                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                                            <td>{{ $latestUpdateDate }}</td>
                                            <td>{{ $projectRow->aging_days ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="dashboard-modal-empty-state">
                            No {{ strtolower($riskLabel) }} projects found for the current dashboard filters.
                        </div>
                    @endif
                </div>
                <div class="dashboard-modal-footer">
                    <button
                        type="button"
                        class="dashboard-modal-export-btn"
                        onclick="exportDashboardModalTableToExcel(this)"
                        data-export-filename="project-update-{{ $riskKey }}-projects.xls"
                        @disabled($modalProjects->isEmpty())
                    >
                        Export Excel
                    </button>
                </div>
            </div>
        </div>
    @endforeach

    <style>
        .project-filter-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            color: #002C76;
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 14px;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
            cursor: pointer;
        }

        .project-filter-chevron {
            margin-left: auto;
            color: #6b7280;
            font-size: 14px;
            transition: transform 0.2s ease;
        }

        .dashboard-stacked-filter-source {
            display: none;
        }

        .dashboard-stacked-filter-dropdown {
            position: relative;
        }

        .dashboard-stacked-filter-toggle {
            min-height: 34px;
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #ffffff;
            color: #111827;
            padding: 5px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .dashboard-stacked-filter-toggle:hover {
            border-color: #9ca3af;
        }

        .dashboard-stacked-filter-toggle:focus-visible {
            outline: 0;
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2);
        }

        .dashboard-stacked-filter-toggle.is-open {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2);
        }

        .dashboard-filter-badge-list {
            margin-top: 0;
            min-height: 20px;
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .dashboard-filter-summary-text {
            display: block;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #374151;
            font-size: 12px;
            line-height: 1.35;
        }

        .dashboard-filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            color: #374151;
            font-size: 11px;
            font-weight: 500;
            line-height: 1;
            padding: 3px 6px;
            max-width: 100%;
        }

        .dashboard-filter-badge-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-filter-badge-remove {
            border: 0;
            background: transparent;
            color: #6b7280;
            width: auto;
            height: auto;
            border-radius: 0;
            font-size: 11px;
            line-height: 1;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .dashboard-filter-badge-remove:hover {
            color: #111827;
        }

        .dashboard-filter-badge-remove:focus-visible {
            outline: 2px solid #60a5fa;
            outline-offset: 1px;
        }

        .dashboard-filter-badge-empty {
            display: block;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12px;
            color: #6b7280;
            padding: 0;
        }

        .dashboard-stacked-filter-chevron {
            margin-left: auto;
            color: #6b7280;
            font-size: 11px;
            transition: transform 0.2s ease;
            flex: 0 0 auto;
        }

        .dashboard-stacked-filter-toggle.is-open .dashboard-stacked-filter-chevron {
            transform: rotate(180deg);
        }

        .dashboard-stacked-filter-menu {
            position: fixed;
            left: 0;
            top: 0;
            display: none;
            width: auto;
            margin-top: 0;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
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
            background: #ffffff;
            padding: 2px 2px 6px;
            z-index: 1;
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
            height: 30px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #ffffff;
            color: #1f2937;
            padding: 0 10px 0 30px;
            font-size: 12px;
            box-sizing: border-box;
        }

        .dashboard-stacked-filter-search-input:focus {
            outline: 0;
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.18);
        }

        .dashboard-stacked-filter-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 4px;
            color: #1f2937;
            padding: 7px 8px;
            font-size: 12px;
            font-weight: 400;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .dashboard-stacked-filter-option:hover {
            background: #f3f4f6;
        }

        .dashboard-stacked-filter-option:focus-visible {
            outline: 2px solid #60a5fa;
            outline-offset: 1px;
        }

        .dashboard-stacked-filter-option.is-selected {
            background: #2f6fae;
            color: #ffffff;
            font-weight: 600;
        }

        .dashboard-stacked-filter-option-check {
            visibility: hidden;
            color: inherit;
            font-size: 11px;
            font-weight: 700;
            flex: 0 0 auto;
        }

        .dashboard-stacked-filter-option.is-selected .dashboard-stacked-filter-option-check {
            visibility: visible;
        }

        .dashboard-stacked-filter-menu-empty {
            color: #6b7280;
            font-size: 12px;
            padding: 6px 8px;
        }

        .dashboard-filter-helper-note {
            margin-top: 4px;
            font-size: 11px;
            color: #6b7280;
        }

        .project-filter-body {
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease;
            will-change: max-height, opacity, transform;
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

        .dashboard-main-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(360px, 1fr);
            gap: 20px;
            align-items: start;
            padding: 24px;
            border: 1px solid #dbe4ff;
            border-radius: 16px;
            background: linear-gradient(180deg, #fbfdff 0%, #f3f7fb 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            margin-bottom: 24px;
        }

        .dashboard-main-layout-filter {
            grid-column: 1 / -1;
            grid-row: 1;
        }

        .dashboard-main-layout > * {
            min-width: 0;
        }

        .dashboard-top-cards {
            grid-column: 1;
            grid-row: 2;
            grid-template-columns: 1fr;
        }

        .dashboard-main-layout > .dashboard-status-row {
            grid-column: 2;
            grid-row: 2;
            align-self: start;
        }

        .dashboard-top-cards .total-projects-card {
            order: 1;
        }

        .dashboard-top-cards .fund-source-card {
            order: 2;
        }

        .dashboard-top-cards .financial-status-card {
            order: 4;
        }

        .dashboard-top-cards .expected-completion-card {
            order: 5;
        }

        .dashboard-top-cards .status-subaybayan-card {
            order: 3;
        }

        .dashboard-status-row {
            grid-template-columns: 1fr;
        }

        .fund-source-grid,
        .status-subaybayan-grid {
            width: 100%;
            min-width: 0;
        }

        .fund-source-grid {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)) !important;
        }

        .dashboard-status-stack {
            display: grid;
            gap: 20px;
            align-content: start;
        }

        .dashboard-status-stack .project-update-status-card {
            order: 1;
        }

        .dashboard-status-row .project-risk-slippage-card {
            order: 1;
        }

        .dashboard-status-row .project-risk-aging-card {
            order: 2;
        }

        .dashboard-status-row .dashboard-status-stack {
            order: 3;
        }

        .project-risk-card {
            max-width: none;
            width: 100%;
            justify-self: stretch;
            display: flex;
            flex-direction: column;
        }

        .project-update-status-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .dashboard-card-title-with-info {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dashboard-info-tooltip-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .dashboard-info-tooltip-trigger {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            padding: 0;
            line-height: 1;
            cursor: help;
        }

        .dashboard-info-tooltip-trigger:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: 2px;
        }

        .dashboard-info-tooltip {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: min(360px, calc(100vw - 36px));
            background: #ffffff;
            border: 1px solid #fecaca;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
            padding: 10px 12px;
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            max-height: min(60vh, 420px);
            overflow: auto;
            transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease;
        }

        .dashboard-status-row .dashboard-info-tooltip {
            left: auto;
            right: 0;
            transform-origin: top right;
        }

        .dashboard-info-tooltip-wrap:hover .dashboard-info-tooltip,
        .dashboard-info-tooltip-wrap:focus-within .dashboard-info-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dashboard-info-tooltip-title {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 700;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .dashboard-info-tooltip-item {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            font-size: 11px;
            color: #374151;
            line-height: 1.35;
        }

        .dashboard-info-tooltip-item + .dashboard-info-tooltip-item {
            margin-top: 6px;
        }

        .dashboard-info-tooltip-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            margin-top: 4px;
            flex: 0 0 8px;
        }

        .dashboard-info-tooltip-text {
            flex: 1;
        }

        .project-update-status-chart {
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: #ffffff;
            padding: 10px;
            margin-bottom: 8px;
        }

        .project-update-status-description {
            margin: 0 0 8px;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.35;
        }

        .project-risk-slippage-description {
            margin: 0 0 8px;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.35;
        }

        .project-risk-aging-description {
            margin: 0 0 8px;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.35;
        }

        .project-update-status-pie-layout {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .project-update-status-pie-wrap {
            position: relative;
            width: min(300px, 100%);
            height: auto;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .project-update-status-pie {
            width: 220px;
            height: 220px;
            display: block;
            overflow: visible;
        }

        .project-update-status-pie-track {
            fill: none;
            stroke: #ffffff;
            stroke-width: 20;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .project-update-status-pie-segment {
            fill: none;
            stroke-width: 20;
            stroke-linecap: butt;
            shape-rendering: geometricPrecision;
            cursor: pointer;
            filter: drop-shadow(0 1.4px 1.5px rgba(15, 23, 42, 0.24)) drop-shadow(0 0 3px rgba(15, 23, 42, 0.16));
            stroke-dasharray: 0 100;
            animation-name: dashboard-donut-sweep;
            animation-timing-function: linear;
            animation-fill-mode: forwards;
            animation-duration: var(--segment-duration, 0ms);
            animation-delay: var(--segment-delay, 0ms);
            transition: transform 180ms ease-out, filter 180ms ease-out;
            transform: translate(var(--segment-shift-x, 0px), var(--segment-shift-y, 0px)) rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .project-update-status-pie-segment:hover {
            --segment-shift-x: var(--segment-hover-x, 0px);
            --segment-shift-y: var(--segment-hover-y, 0px);
            filter: drop-shadow(0 2.2px 2.4px rgba(15, 23, 42, 0.26)) drop-shadow(0 0 4.5px rgba(15, 23, 42, 0.18));
        }

        .dashboard-donut-callout {
            pointer-events: none;
            opacity: 0;
            transform: translateY(1.2px);
            transform-box: fill-box;
            transform-origin: center;
            animation-name: dashboard-donut-callout-reveal;
            animation-duration: 220ms;
            animation-timing-function: ease-out;
            animation-fill-mode: forwards;
            animation-delay: calc(var(--callout-start-delay, 1520ms) + (var(--callout-index, 0) * var(--callout-step-delay, 140ms)));
        }

        .dashboard-donut-callout-line {
            fill: none;
            stroke: #6b7280;
            stroke-width: 0.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .dashboard-donut-callout-text {
            fill: #374151;
            font-size: 4.2px;
            font-weight: 600;
            letter-spacing: 0.01em;
            dominant-baseline: middle;
        }

        .dashboard-donut-callout-value {
            fill: #111827;
            font-weight: 700;
        }

        .project-update-status-tile {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #f9fafb;
            text-align: center;
        }

        .project-update-status-label {
            margin-bottom: 8px;
        }

        .project-update-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            border-radius: 999px;
            padding: 0 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .project-update-status-value {
            height: 40px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.25);
        }

        .dashboard-legend-block {
            margin-top: 10px;
        }

        .dashboard-legend-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            padding: 0 0 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-align: left;
        }

        .dashboard-legend-toggle-chevron {
            margin-left: auto;
            color: #6b7280;
            font-size: 11px;
            transition: transform 0.2s ease;
        }

        .dashboard-legend-body {
            overflow: hidden;
            max-height: 420px;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.28s ease, opacity 0.2s ease, transform 0.2s ease;
        }

        .dashboard-legend-block.collapsed .dashboard-legend-toggle-chevron {
            transform: rotate(180deg);
        }

        .dashboard-legend-block.collapsed .dashboard-legend-body {
            max-height: 0;
            opacity: 0;
            transform: translateY(-4px);
            pointer-events: none;
        }

        .dashboard-legend-block .project-update-status-legend,
        .dashboard-legend-block .project-risk-legend {
            margin-top: 0;
        }

        .project-update-status-legend {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: #f9fafb;
            padding: 10px 12px;
            display: grid;
            gap: 8px;
        }

        .project-update-status-legend-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            color: #374151;
            line-height: 1.35;
        }

        .project-update-status-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            margin-top: 3px;
            flex: 0 0 10px;
        }

        .project-update-status-legend-text {
            flex: 1;
        }

        .project-risk-donut-layout {
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: #ffffff;
            padding: 10px;
            margin-bottom: 8px;
        }

        .project-risk-donut-wrap {
            width: min(300px, 100%);
            height: auto;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .project-risk-donut {
            width: 220px;
            height: 220px;
            display: block;
            overflow: visible;
        }

        @keyframes dashboard-donut-sweep {
            from {
                stroke-dasharray: 0 100;
            }

            to {
                stroke-dasharray: var(--segment-length, 0) 100;
            }
        }

        @keyframes dashboard-donut-callout-reveal {
            from {
                opacity: 0;
                transform: translateY(1.2px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .project-update-status-pie-segment,
            .project-risk-donut-segment {
                animation: none;
                stroke-dasharray: var(--segment-length, 0) 100;
                transition: none;
            }

            .dashboard-donut-callout {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }

        .project-risk-donut-track {
            fill: none;
            stroke: #ffffff;
            stroke-width: 20;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .project-risk-donut-segment {
            fill: none;
            stroke-width: 20;
            stroke-linecap: butt;
            shape-rendering: geometricPrecision;
            cursor: pointer;
            filter: drop-shadow(0 1.4px 1.5px rgba(15, 23, 42, 0.24)) drop-shadow(0 0 3px rgba(15, 23, 42, 0.16));
            stroke-dasharray: 0 100;
            animation-name: dashboard-donut-sweep;
            animation-timing-function: linear;
            animation-fill-mode: forwards;
            animation-duration: var(--segment-duration, 0ms);
            animation-delay: var(--segment-delay, 0ms);
            transition: transform 180ms ease-out, filter 180ms ease-out;
            transform: translate(var(--segment-shift-x, 0px), var(--segment-shift-y, 0px)) rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .project-risk-donut-segment:hover {
            --segment-shift-x: var(--segment-hover-x, 0px);
            --segment-shift-y: var(--segment-hover-y, 0px);
            filter: drop-shadow(0 2.2px 2.4px rgba(15, 23, 42, 0.26)) drop-shadow(0 0 4.5px rgba(15, 23, 42, 0.18));
        }

        .project-risk-chart {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 4px;
            align-items: end;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: #f9fafb;
            padding: 8px 6px;
            margin-bottom: 8px;
        }

        .project-risk-chart.project-risk-chart-aging {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .project-risk-chart-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }

        .project-risk-chart-count {
            font-size: 11px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }

        .project-risk-chart-track {
            width: 100%;
            height: 74px;
            border-bottom: 1px solid #cfd4dd;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .project-risk-chart-bar {
            width: min(12px, 100%);
            border-radius: 4px 4px 0 0;
            background-color: #111827;
        }

        .project-risk-chart-label {
            min-height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 10px;
            color: #374151;
            line-height: 1.1;
        }

        .project-risk-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(84px, 1fr));
            gap: 8px;
        }

        .project-risk-status-tile {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #f9fafb;
            text-align: center;
        }

        .project-risk-summary-label {
            text-align: center;
            margin-bottom: 8px;
        }

        .project-risk-summary-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            border-radius: 999px;
            padding: 0 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .project-risk-summary-value {
            height: 40px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.25);
        }

        .project-risk-legend {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: #f9fafb;
            padding: 10px 12px;
            display: grid;
            gap: 6px;
        }

        .project-risk-legend-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            color: #374151;
            line-height: 1.35;
        }

        .project-risk-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            margin-top: 3px;
            flex: 0 0 10px;
        }

        .project-risk-legend-text {
            flex: 1;
        }

        .project-risk-slippage-card .project-risk-legend {
            margin-top: 5px;
        }

        .expected-completion-list {
            display: grid;
            gap: 10px;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
        }

        .expected-completion-item {
            padding: 10px 12px;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background-color: #f8fbff;
            transform: translateY(0);
            box-shadow: 0 0 0 rgba(37, 99, 235, 0);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
        }

        .expected-completion-item.clickable-dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.16);
            border-color: #93c5fd;
            background-color: #ffffff;
        }

        .expected-completion-item-code {
            font-size: 12px;
            font-weight: 700;
            color: #1e3a8a;
            line-height: 1.25;
        }

        .expected-completion-item-date {
            font-size: 12px;
            font-weight: 600;
            color: #0f766e;
            line-height: 1.25;
        }

        .expected-completion-item-title {
            margin-top: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            line-height: 1.35;
        }

        .expected-completion-item-location {
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #64748b;
        }

        .financial-status-card .financial-metrics-layout {
            grid-template-columns: repeat(6, minmax(0, 1fr));
            align-items: stretch;
            gap: 8px !important;
            justify-items: stretch;
            width: 100%;
            min-width: 0;
        }

        .financial-allocation-tile {
            grid-column: 1 / span 3;
            grid-row: 1;
        }

        .financial-percentage-tile {
            grid-column: 4 / span 3;
            grid-row: 1;
        }

        .financial-obligation-tile {
            grid-column: 1 / span 2;
            grid-row: 2;
        }

        .financial-disbursement-tile {
            grid-column: 3 / span 2;
            grid-row: 2;
        }

        .financial-balance-tile {
            grid-column: 5 / span 2;
            grid-row: 2;
        }

        .financial-metric-tile {
            min-height: 76px;
            padding: 6px 7px !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
            width: 100%;
            min-width: 0;
            justify-self: stretch;
        }

        .financial-metric-label {
            min-height: 20px;
            font-size: 11px !important;
            gap: 5px !important;
            margin-bottom: 0 !important;
            line-height: 1.15;
        }

        .financial-metric-icon {
            flex: 0 0 15px;
            width: 15px !important;
            height: 15px !important;
            font-size: 8px !important;
        }

        .financial-metric-icon i {
            font-size: 8px !important;
        }

        .financial-amount-value {
            display: block;
            width: 100%;
            text-align: center;
            font-size: clamp(11px, 0.9vw, 15px);
            line-height: 1.1;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.01em;
            font-variant-numeric: tabular-nums;
        }

        .financial-percentage-value {
            display: block;
            width: 100%;
            text-align: center;
            font-size: clamp(13px, 1vw, 17px) !important;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 900px) {
            .financial-status-card .financial-metrics-layout {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                justify-items: stretch;
            }

            .financial-allocation-tile,
            .financial-percentage-tile,
            .financial-obligation-tile,
            .financial-disbursement-tile,
            .financial-balance-tile {
                grid-column: auto;
                grid-row: auto;
            }
        }

        @media (max-width: 620px) {
            .financial-status-card .financial-metrics-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .dashboard-main-layout {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .dashboard-top-cards,
            .dashboard-main-layout > .dashboard-status-row {
                grid-column: 1;
                grid-row: auto;
            }

            .dashboard-top-cards {
                grid-template-columns: 1fr;
            }

            .dashboard-info-tooltip {
                left: auto;
                right: 0;
                width: min(320px, calc(100vw - 28px));
            }

            .dashboard-status-row .dashboard-info-tooltip {
                top: auto;
                bottom: calc(100% + 8px);
            }

            .dashboard-status-row {
                grid-template-columns: 1fr;
            }

            .fund-source-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .status-subaybayan-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .project-update-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .project-risk-card {
                max-width: none;
                justify-self: stretch;
            }

            .financial-status-card {
                grid-column: 1 / -1;
            }

            .dashboard-filter-grid {
                grid-template-columns: repeat(2, minmax(200px, 1fr)) !important;
            }

            .dashboard-filter-reset {
                grid-column: auto !important;
                justify-content: flex-start !important;
            }
        }

        @media (max-width: 1450px) and (min-width: 1101px) {
            .dashboard-status-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .dashboard-main-layout {
                padding: 16px;
                border-radius: 12px;
            }

            .dashboard-info-tooltip {
                width: min(280px, calc(100vw - 20px));
            }

            .dashboard-top-cards {
                grid-template-columns: 1fr;
            }

            .financial-status-card {
                grid-column: auto;
            }

            .project-risk-donut-wrap {
                width: min(240px, 100%);
            }

            .project-risk-donut {
                width: 170px;
                height: 170px;
            }

            .project-risk-chart {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .project-risk-chart-track {
                height: 68px;
            }

            .project-risk-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .project-risk-summary-value {
                font-size: 22px;
                height: 38px;
            }

            .project-update-status-grid {
                grid-template-columns: 1fr;
            }

            .fund-source-grid,
            .status-subaybayan-grid {
                grid-template-columns: 1fr !important;
            }

            .project-update-status-pie-layout {
                justify-content: center;
            }

            .project-update-status-pie-wrap {
                width: min(240px, 100%);
            }

            .project-update-status-pie {
                width: 170px;
                height: 170px;
            }

            .dashboard-donut-callout-text {
                font-size: 3.5px;
            }

            .financial-amount-value {
                font-size: clamp(10px, 3.4vw, 13px);
            }

            .dashboard-filter-grid {
                grid-template-columns: 1fr !important;
            }

            .dashboard-filter-reset a,
            .dashboard-filter-apply-btn,
            .dashboard-filter-export-btn {
                width: 100%;
            }
        }

        .dashboard-filter-reset {
            grid-column: 3;
        }

        .dashboard-filter-reset-link {
            box-shadow: 0 4px 10px rgba(0, 44, 118, 0.18);
            transition: box-shadow 0.18s ease, transform 0.18s ease;
        }

        .dashboard-filter-reset-link:hover {
            box-shadow: 0 6px 14px rgba(0, 44, 118, 0.24);
            transform: translateY(-1px);
        }

        .dashboard-filter-reset-link:focus-visible {
            outline: 2px solid rgba(96, 165, 250, 0.9);
            outline-offset: 2px;
            box-shadow: 0 6px 14px rgba(0, 44, 118, 0.24);
        }

        .dashboard-filter-apply-btn {
            height: 34px;
            min-width: 150px;
            border-radius: 7px;
            border: 0;
            background: linear-gradient(180deg, #1d4ed8 0%, #1e3a8a 100%);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 0 14px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(29, 78, 216, 0.22);
            transition: box-shadow 0.18s ease, transform 0.18s ease;
        }

        .dashboard-filter-apply-btn:hover {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 6px 14px rgba(29, 78, 216, 0.28);
            transform: translateY(-1px);
        }

        .dashboard-filter-apply-btn:focus-visible {
            outline: 2px solid rgba(96, 165, 250, 0.9);
            outline-offset: 2px;
            box-shadow: 0 6px 14px rgba(29, 78, 216, 0.28);
        }

        .dashboard-filter-export-btn {
            height: 34px;
            min-width: 150px;
            border-radius: 7px;
            border: 0;
            background: linear-gradient(180deg, #0a8a52 0%, #007542 100%);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 0 14px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 117, 66, 0.18);
            transition: background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .dashboard-filter-export-btn:hover {
            background: linear-gradient(180deg, #0b9a5b 0%, #00693b 100%);
            box-shadow: 0 6px 14px rgba(0, 117, 66, 0.24);
            transform: translateY(-1px);
        }

        .clickable-dashboard-card {
            cursor: pointer;
        }

        .clickable-dashboard-card:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        .dashboard-card,
        .dashboard-tile {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        }

        .dashboard-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .dashboard-modal {
            position: fixed;
            inset: 0;
            z-index: 1300;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .dashboard-modal.is-open {
            display: flex;
        }

        .dashboard-modal-backdrop {
            position: absolute;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.45);
        }

        .dashboard-modal-dialog {
            position: relative;
            width: min(1520px, calc(100vw - 40px));
            max-height: calc(100vh - 40px);
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .dashboard-modal-header {
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .dashboard-modal-header h3 {
            margin: 0;
            font-size: 16px;
            color: #111827;
            font-weight: 700;
        }

        .dashboard-modal-close {
            margin-left: auto;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #4b5563;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .dashboard-modal-close:hover {
            background: #f3f4f6;
            color: #111827;
            border-color: #cbd5e1;
        }

        .dashboard-modal-subtitle {
            margin: 0;
            padding: 10px 16px;
            font-size: 12px;
            color: #374151;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .dashboard-modal-body {
            padding: 0;
            overflow: auto;
        }

        .dashboard-modal-table-wrap {
            overflow: auto;
        }

        .dashboard-modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .dashboard-modal-table thead th {
            position: sticky;
            top: 0;
            background: #f3f4f6;
            color: #374151;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #d1d5db;
            white-space: nowrap;
        }

        .dashboard-modal-table tbody td {
            padding: 10px 12px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .dashboard-modal-table tbody .dashboard-modal-total-row td {
            font-weight: 700;
            background: #f9fafb;
            border-top: 2px solid #d1d5db;
        }

        .dashboard-modal-empty-state {
            padding: 20px 16px;
            color: #6b7280;
            font-size: 13px;
        }

        .dashboard-modal-footer {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .dashboard-modal-export-btn {
            border: 0;
            color: #ffffff;
            background: #16a34a;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .dashboard-modal-export-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        /* ================================================================
           DESIGN UPGRADE — Enhanced Dashboard Visual System
           ================================================================ */

        /* ---- Content Header ---- */
        .content-header {
            position: relative !important;
            padding: 22px 26px !important;
            background: linear-gradient(135deg, #ffffff 0%, #eef4ff 100%) !important;
            border-radius: 16px !important;
            border: 1px solid #c7d7f5 !important;
            box-shadow: 0 4px 18px rgba(0, 44, 118, 0.08) !important;
            overflow: hidden !important;
            margin-bottom: 24px !important;
        }

        .content-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: linear-gradient(180deg, #002C76, #3b82f6);
            border-radius: 0 3px 3px 0;
        }

        .content-header h1 {
            font-size: 22px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin-bottom: 6px !important;
        }

        .content-header p {
            color: #475569 !important;
            font-size: 13px !important;
        }

        /* ---- Card Base Upgrade ---- */
        .dashboard-main-layout .dashboard-card,
        .dashboard-status-row .dashboard-card {
            border-radius: 14px !important;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
        }

        .dashboard-main-layout .dashboard-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(0, 0, 0, 0.05) !important;
        }

        /* ---- Filter Form Upgrade ---- */
        .project-filter-form.dashboard-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
            border: 1px solid #dbe4ff !important;
            box-shadow: 0 4px 16px rgba(0, 44, 118, 0.07) !important;
        }

        .project-filter-toggle {
            font-weight: 800 !important;
            letter-spacing: 0.04em !important;
            font-size: 12px !important;
            color: #002C76 !important;
        }

        /* ---- Card Heading Accent Line ---- */
        .dashboard-card h2 {
            position: relative !important;
            padding-bottom: 12px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            margin-bottom: 16px !important;
        }

        .dashboard-card h2::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 36px;
            height: 2px;
            background: linear-gradient(90deg, #002C76, #3b82f6);
            border-radius: 2px;
        }

        .project-update-status-card h2::after,
        .project-risk-card h2::after {
            background: linear-gradient(90deg, #b91c1c, #f87171) !important;
        }

        /* ---- Total Projects Card ---- */
        .total-projects-card {
            background: linear-gradient(145deg, #ffffff 0%, #f0f5ff 100%) !important;
        }

        .total-projects-card .dashboard-tile {
            background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%) !important;
            border-color: #93c5fd !important;
            border-radius: 12px !important;
            padding: 22px 12px !important;
        }

        .total-projects-card .dashboard-tile > div:last-child {
            font-size: 52px !important;
            font-weight: 600 !important;
            background: linear-gradient(135deg, #002C76, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.05 !important;
        }

        /* ---- Fund Source Tiles ---- */
        .fund-source-link-tile {
            border-radius: 12px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        }

        .fund-source-link-tile:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.10) !important;
        }

        .fund-source-link-tile > div:last-child {
            font-size: 26px !important;
            font-weight: 600 !important;
        }

        /* ---- Financial Metric Tiles ---- */
        .financial-metric-tile {
            border-radius: 12px !important;
        }

        .financial-metric-tile:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08) !important;
        }

        .financial-percentage-value {
            font-size: clamp(18px, 1.5vw, 24px) !important;
            font-weight: 600 !important;
        }

        .financial-amount-value {
            font-size: clamp(12px, 1vw, 16px) !important;
            font-weight: 600 !important;
        }

        /* ---- Expected Completion Card ---- */
        .expected-completion-card {
            background: linear-gradient(145deg, #ffffff 0%, #f0fdf4 100%) !important;
            border-color: #bbf7d0 !important;
        }

        .expected-completion-card h2 {
            border-bottom-color: #dcfce7 !important;
        }

        .expected-completion-card h2::after {
            background: linear-gradient(90deg, #16a34a, #4ade80) !important;
        }

        .expected-completion-item {
            border-radius: 12px !important;
        }

        /* ---- Status Subaybayan Card ---- */
        .status-subaybayan-card {
            background: #ffffff !important;
            border-radius: 16px !important;
            border: 1px solid rgba(191, 219, 254, 0.9) !important;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08) !important;
            padding: 20px !important;
        }

        .status-subaybayan-grid {
            display: grid;
            grid-template-columns: 1fr !important;
            gap: 12px;
        }

        .status-subaybayan-card .sglgif-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .status-subaybayan-card .sglgif-card-head h2 {
            margin: 0;
            color: #002C76;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .status-subaybayan-card .sglgif-card-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .status-subaybayan-card .sglgif-bar-row {
            margin-bottom: 0;
        }

        .status-subaybayan-card .sglgif-bar-trigger {
            padding: 10px 12px;
            border: 1px solid var(--status-row-border, transparent);
            border-radius: 12px;
            background: var(--status-row-bg, #f8fbff);
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .status-subaybayan-card .sglgif-bar-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
            border-color: #bfdbfe;
            background: #f0f7ff;
        }

        .status-subaybayan-card .sglgif-bar-trigger:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .status-subaybayan-card .sglgif-bar-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
            color: #334155;
            font-size: 12px;
            align-items: center;
        }

        .status-subaybayan-card .sglgif-bar-head span {
            font-weight: 700;
            color: var(--status-title-color, #334155);
            overflow-wrap: anywhere;
        }

        .status-subaybayan-card .sglgif-bar-head strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .status-subaybayan-card .status-subaybayan-count {
            display: inline-block;
        }

        .status-subaybayan-card .status-subaybayan-percentage {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
        }

        .status-subaybayan-card .sglgif-bar-track {
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .status-subaybayan-card .sglgif-bar-track > div {
            height: 100%;
            border-radius: 999px;
        }

        .status-subaybayan-card .sglgif-empty {
            margin: 0;
            padding: 18px 16px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .status-subaybayan-card .sglgif-card-head h2 {
                font-size: 14px;
            }

            .status-subaybayan-card .sglgif-card-head p {
                font-size: 11px;
            }

            .status-subaybayan-card .sglgif-bar-head {
                font-size: 11px;
            }

            .status-subaybayan-card .status-subaybayan-percentage {
                font-size: 10px;
            }
        }

        /* ---- Project Update Status & Risk Cards ---- */
        .project-update-status-card {
            background: linear-gradient(145deg, #ffffff 0%, #fff5f5 100%) !important;
            border: 1px solid rgba(254, 202, 202, 0.65) !important;
        }

        .project-risk-slippage-card,
        .project-risk-aging-card {
            background: linear-gradient(145deg, #ffffff 0%, #fff8f8 100%) !important;
            border: 1px solid rgba(254, 202, 202, 0.65) !important;
        }

        .project-update-status-tile {
            border-radius: 12px !important;
            transition: transform 0.18s ease, box-shadow 0.18s ease !important;
        }

        .project-update-status-tile:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08) !important;
        }

        .project-update-status-value {
            border-radius: 10px !important;
            font-size: 28px !important;
            height: 48px !important;
        }

        .project-risk-status-tile {
            border-radius: 12px !important;
        }

        .project-risk-summary-value {
            border-radius: 10px !important;
            font-size: 26px !important;
        }

        /* ---- Chart Containers ---- */
        .project-update-status-chart {
            border-radius: 12px !important;
            background: linear-gradient(180deg, #fcfcfd 0%, #f8fafc 100%) !important;
            border-color: #f1f5f9 !important;
        }

        .project-risk-donut-layout {
            border-radius: 12px !important;
            background: linear-gradient(180deg, #fcfcfd 0%, #f8fafc 100%) !important;
            border-color: #f1f5f9 !important;
        }

        .project-risk-chart {
            border-radius: 12px !important;
            background: linear-gradient(180deg, #fdfdfe 0%, #f9fafb 100%) !important;
        }

        /* ---- Dashboard Status Row — styled wrapper ---- */
        .dashboard-status-row {
            padding: 24px !important;
            border: 1px solid #dbe4ff !important;
            border-radius: 18px !important;
            background:
                radial-gradient(circle at bottom right, rgba(219, 234, 254, 0.25), transparent 42%),
                linear-gradient(180deg, #fbfdff 0%, #f3f7fb 100%) !important;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06) !important;
        }

        /* ---- Responsive ---- */
        @media (max-width: 700px) {
            .content-header {
                padding: 16px 18px !important;
                border-radius: 12px !important;
            }

            .content-header h1 {
                font-size: 18px !important;
            }

            .total-projects-card .dashboard-tile > div:last-child {
                font-size: 44px !important;
            }

            .dashboard-status-row {
                padding: 14px !important;
                border-radius: 14px !important;
            }
        }

    </style>

    <script>
        const PROJECT_FILTER_STATE_KEY = 'dashboard.project_filter_collapsed';
        const DASHBOARD_LEGEND_STATE_PREFIX = 'dashboard.legend_collapsed.';
        const STATUS_LOCATION_EXPORT_ROWS = @json($statusSubaybayanLocationReport ?? []);
        const PROVINCE_FUNDING_YEAR_PROGRAM_STATUS_EXPORT_ROWS = @json($provinceFundingYearProgramStatusReport ?? []);
        const PROVINCE_FUNDING_YEAR_PROGRAM_STATUS_SOURCE_ROWS = @json($provinceFundingYearProgramStatusSourceRows ?? []);
        const STATUS_LOCATION_EXPORT_STATUSES = @json($statusDisplayOrder ?? array_keys($statusSubaybayanCounts ?? []));
        const DASHBOARD_PROVINCE_CITY_OPTIONS = @json($filterOptions['province_city_map'] ?? []);
        const DASHBOARD_CITY_BARANGAY_OPTIONS = @json($filterOptions['city_barangay_map'] ?? []);
        const EXCEL_MAX_CELL_TEXT_LENGTH = 32767;
        const truncateExcelCellText = (rawValue) => {
            const stringValue = String(rawValue ?? '');
            if (stringValue.length <= EXCEL_MAX_CELL_TEXT_LENGTH) {
                return stringValue;
            }

            return `${stringValue.slice(0, EXCEL_MAX_CELL_TEXT_LENGTH - 3)}...`;
        };

        function serializeDashboardTableForExcel(sourceTable) {
            const exportTable = document.createElement('table');
            exportTable.setAttribute('border', '1');
            exportTable.style.borderCollapse = 'collapse';

            const appendRowsToSection = (rowSource, sectionElement) => {
                rowSource.querySelectorAll('tr').forEach((row) => {
                    const exportRow = document.createElement('tr');
                    row.querySelectorAll('th, td').forEach((cell) => {
                        const exportCell = document.createElement(cell.tagName.toLowerCase() === 'th' ? 'th' : 'td');
                        const normalizedText = truncateExcelCellText((cell.textContent || '').replace(/\s+/g, ' ').trim());
                        exportCell.textContent = normalizedText;

                        const colspanValue = cell.getAttribute('colspan');
                        if (colspanValue) {
                            exportCell.setAttribute('colspan', colspanValue);
                        }

                        const rowspanValue = cell.getAttribute('rowspan');
                        if (rowspanValue) {
                            exportCell.setAttribute('rowspan', rowspanValue);
                        }

                        exportRow.appendChild(exportCell);
                    });
                    sectionElement.appendChild(exportRow);
                });
            };

            let hasStructuredSections = false;
            ['thead', 'tbody', 'tfoot'].forEach((sectionTag) => {
                const sectionNode = sourceTable.querySelector(sectionTag);
                if (!sectionNode) {
                    return;
                }

                hasStructuredSections = true;
                const exportSection = document.createElement(sectionTag);
                appendRowsToSection(sectionNode, exportSection);
                exportTable.appendChild(exportSection);
            });

            if (!hasStructuredSections) {
                const exportBody = document.createElement('tbody');
                appendRowsToSection(sourceTable, exportBody);
                exportTable.appendChild(exportBody);
            }

            return exportTable.outerHTML;
        }

        function readProjectFilterCollapsedState() {
            try {
                const storedState = window.localStorage.getItem(PROJECT_FILTER_STATE_KEY);
                if (storedState === null) {
                    return true;
                }

                return storedState === '1';
            } catch (error) {
                return true;
            }
        }

        function writeProjectFilterCollapsedState(isCollapsed) {
            try {
                window.localStorage.setItem(PROJECT_FILTER_STATE_KEY, isCollapsed ? '1' : '0');
            } catch (error) {
                // Ignore storage errors and keep UI behavior functional.
            }
        }

        function readDashboardLegendCollapsedState(legendKey) {
            if (!legendKey) {
                return false;
            }

            try {
                return window.localStorage.getItem(`${DASHBOARD_LEGEND_STATE_PREFIX}${legendKey}`) === '1';
            } catch (error) {
                return false;
            }
        }

        function writeDashboardLegendCollapsedState(legendKey, isCollapsed) {
            if (!legendKey) {
                return;
            }

            try {
                window.localStorage.setItem(`${DASHBOARD_LEGEND_STATE_PREFIX}${legendKey}`, isCollapsed ? '1' : '0');
            } catch (error) {
                // Ignore storage errors and keep UI behavior functional.
            }
        }

        function setProjectFilterBodyHeight(form) {
            const body = form.querySelector('.project-filter-body');
            if (!body) {
                return;
            }

            if (form.classList.contains('collapsed')) {
                body.style.maxHeight = '0px';
                return;
            }

            body.style.maxHeight = `${body.scrollHeight}px`;
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

        function setDashboardLegendBodyHeight(legendBlock) {
            const body = legendBlock.querySelector('.dashboard-legend-body');
            if (!body) {
                return;
            }

            if (legendBlock.classList.contains('collapsed')) {
                body.style.maxHeight = '0px';
                return;
            }

            body.style.maxHeight = `${body.scrollHeight}px`;
        }

        function toggleDashboardLegend(button) {
            const legendBlock = button.closest('.dashboard-legend-block');
            if (!legendBlock) {
                return;
            }

            const body = legendBlock.querySelector('.dashboard-legend-body');
            if (!body) {
                return;
            }

            const isCollapsed = legendBlock.classList.contains('collapsed');
            if (isCollapsed) {
                legendBlock.classList.remove('collapsed');
                requestAnimationFrame(() => {
                    body.style.maxHeight = `${body.scrollHeight}px`;
                });
            } else {
                body.style.maxHeight = `${body.scrollHeight}px`;
                requestAnimationFrame(() => {
                    legendBlock.classList.add('collapsed');
                    body.style.maxHeight = '0px';
                });
            }

            const nextCollapsed = !isCollapsed;
            button.setAttribute('aria-expanded', nextCollapsed ? 'false' : 'true');
            writeDashboardLegendCollapsedState(legendBlock.dataset.legendKey || '', nextCollapsed);
            requestAnimationFrame(() => {
                syncRiskCardHeightsWithStatusCard();
            });
        }

        function syncRiskCardHeightsWithStatusCard() {
            const projectUpdateCard = document.querySelector('.project-update-status-card');
            const slippageCard = document.querySelector('.project-risk-slippage-card');
            const agingCard = document.querySelector('.project-risk-aging-card');
            const shouldSkipSync = window.matchMedia('(max-width: 1100px)').matches;
            const statusRow = document.querySelector('.dashboard-status-row');
            const cardsToSync = [projectUpdateCard, slippageCard, agingCard].filter(Boolean);
            const minimumCardHeight = 465;
            const hasSingleStatusColumn = statusRow
                ? window.getComputedStyle(statusRow).gridTemplateColumns.trim().split(/\s+/).length <= 1
                : true;

            cardsToSync.forEach((card) => {
                card.style.height = '';
                card.style.minHeight = '';
            });

            if (cardsToSync.length < 3 || shouldSkipSync || hasSingleStatusColumn) {
                return;
            }

            const tallestContentHeight = Math.max(...cardsToSync.map((card) => card.scrollHeight));
            const targetHeight = Math.max(minimumCardHeight, tallestContentHeight);

            cardsToSync.forEach((card) => {
                card.style.height = `${targetHeight}px`;
                card.style.minHeight = `${targetHeight}px`;
            });
        }

        function openDashboardModal(modalElement) {
            if (!modalElement) {
                return;
            }

            modalElement.classList.add('is-open');
            modalElement.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeDashboardModal(modalElement) {
            if (!modalElement) {
                return;
            }

            modalElement.classList.remove('is-open');
            modalElement.setAttribute('aria-hidden', 'true');

            const hasOpenModal = document.querySelector('.dashboard-modal.is-open');
            if (!hasOpenModal) {
                document.body.style.overflow = '';
            }
        }

        function triggerDashboardExcelDownload(blob, filename) {
            const downloadUrl = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();
            URL.revokeObjectURL(downloadUrl);
        }

        function normalizeDashboardExcelFilename(rawFilename, fallbackName) {
            const candidate = (rawFilename || '').toString().trim();
            const safeFallback = `${fallbackName}.xls`;
            if (!candidate) {
                return safeFallback;
            }

            return candidate.toLowerCase().endsWith('.xls') ? candidate : `${candidate}.xls`;
        }

        function escapeDashboardExcelXml(rawValue) {
            return truncateExcelCellText(rawValue)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        }

        function normalizeDashboardWorksheetName(rawName, fallbackName) {
            const fallback = (fallbackName || 'Sheet1').toString().trim() || 'Sheet1';
            const candidate = truncateExcelCellText(rawName || fallback)
                .replace(/[\u0000-\u001F]/g, ' ')
                .replace(/[\\\/\?\*\[\]:]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/^'+|'+$/g, '');

            return (candidate || fallback).slice(0, 31);
        }

        function buildDashboardSpreadsheetCellXml(cell = {}) {
            const styleId = cell?.styleId || 'Cell';
            const cellType = cell?.type === 'Number' ? 'Number' : 'String';
            const mergeAcrossValue = Number(cell?.mergeAcross);
            const mergeAcrossAttribute = Number.isFinite(mergeAcrossValue) && mergeAcrossValue > 0
                ? ` ss:MergeAcross="${Math.trunc(mergeAcrossValue)}"`
                : '';
            const numericValue = Number(cell?.value ?? 0);
            const serializedValue = cellType === 'Number' && Number.isFinite(numericValue)
                ? String(numericValue)
                : String(cell?.value ?? '');

            return `<Cell ss:StyleID="${styleId}"${mergeAcrossAttribute}><Data ss:Type="${cellType}">${escapeDashboardExcelXml(serializedValue)}</Data></Cell>`;
        }

        function buildDashboardSpreadsheetRowXml(cells = []) {
            const safeCells = Array.isArray(cells) && cells.length > 0
                ? cells
                : [{ value: '', styleId: 'Cell' }];

            return `<Row>${safeCells.map((cell) => buildDashboardSpreadsheetCellXml(cell)).join('')}</Row>`;
        }

        function buildDashboardSpreadsheetWorksheetXml(worksheet = {}) {
            const worksheetName = normalizeDashboardWorksheetName(worksheet?.name, 'Sheet1');
            const columns = Array.isArray(worksheet?.columns) ? worksheet.columns : [];
            const rows = Array.isArray(worksheet?.rows) ? worksheet.rows : [];
            const columnsXml = columns.map((width) => {
                const numericWidth = Number(width);
                return Number.isFinite(numericWidth) && numericWidth > 0
                    ? `<Column ss:AutoFitWidth="1" ss:Width="${Math.trunc(numericWidth)}"/>`
                    : '<Column ss:AutoFitWidth="1"/>';
            }).join('');

            return `<Worksheet ss:Name="${escapeDashboardExcelXml(worksheetName)}"><Table>${columnsXml}${rows.map((row) => buildDashboardSpreadsheetRowXml(row)).join('')}</Table></Worksheet>`;
        }

        function buildDashboardSpreadsheetXml(worksheets = []) {
            const safeWorksheets = Array.isArray(worksheets) && worksheets.length > 0
                ? worksheets
                : [{ name: 'Sheet1', rows: [[{ value: 'No data available.', styleId: 'Cell' }]] }];

            return `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Styles>
        <Style ss:ID="Title">
            <Font ss:Bold="1" ss:Size="14"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        </Style>
        <Style ss:ID="SheetTitle">
            <Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#1D4ED8" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="SheetSubtitle">
            <Font ss:Bold="1" ss:Color="#1E3A8A"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="SheetMeta">
            <Font ss:Italic="1" ss:Color="#475569"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Meta">
            <Font ss:Italic="1" ss:Color="#4B5563"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        </Style>
        <Style ss:ID="AnalysisSection">
            <Font ss:Bold="1" ss:Color="#FFFFFF"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#0F766E" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="AnalysisHeader">
            <Font ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#CCFBF1" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="AnalysisCell">
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="AnalysisCellRight">
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
            <NumberFormat ss:Format="#,##0"/>
        </Style>
        <Style ss:ID="AnalysisTextRight">
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="SectionHeader">
            <Font ss:Bold="1"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Header">
            <Font ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="PlainHeader">
            <Font ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
        </Style>
        <Style ss:ID="HierarchyHeader">
            <Font ss:Bold="1" ss:Color="#FFFFFF"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#2563EB" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Cell">
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        </Style>
        <Style ss:ID="CellRight">
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
        </Style>
        <Style ss:ID="GroupCell">
            <Font ss:Bold="1"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F9FAFB" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="GroupCellRight">
            <Font ss:Bold="1"/>
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F9FAFB" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="HierarchyProvince">
            <Font ss:Bold="1" ss:Color="#FFFFFF"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#1E40AF" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="HierarchyProvinceMetric">
            <Font ss:Bold="1" ss:Color="#FFFFFF"/>
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#1E40AF" ss:Pattern="Solid"/>
            <NumberFormat ss:Format="#,##0"/>
        </Style>
        <Style ss:ID="HierarchyFundingYear">
            <Font ss:Bold="1" ss:Color="#92400E"/>
            <Alignment ss:Vertical="Center" ss:WrapText="1" ss:Indent="1"/>
            <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="HierarchyFundingYearMetric">
            <Font ss:Bold="1" ss:Color="#92400E"/>
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
            <NumberFormat ss:Format="#,##0"/>
        </Style>
        <Style ss:ID="HierarchyProgram">
            <Alignment ss:Vertical="Center" ss:WrapText="1" ss:Indent="2"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="HierarchyProgramMetric">
            <Alignment ss:Horizontal="Right" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
            <NumberFormat ss:Format="#,##0"/>
        </Style>
    </Styles>
    ${safeWorksheets.map((worksheet) => buildDashboardSpreadsheetWorksheetXml(worksheet)).join('')}
</Workbook>`;
        }

        function exportDashboardModalTableToExcel(button) {
            if (!button || button.disabled) {
                return;
            }

            const modalElement = button.closest('.dashboard-modal');
            if (!modalElement) {
                return;
            }

            const sourceTable = modalElement.querySelector('.dashboard-modal-table');
            if (!sourceTable) {
                return;
            }

            const modalTitle = modalElement.querySelector('.dashboard-modal-header h3')?.textContent?.trim() || 'Projects';
            const modalSubtitle = modalElement.querySelector('.dashboard-modal-subtitle')?.textContent?.trim() || '';
            const filename = normalizeDashboardExcelFilename(button.dataset.exportFilename, 'dashboard-projects');
            const tableHtml = serializeDashboardTableForExcel(sourceTable);

            const workbookHtml = `<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h3>${modalTitle}</h3>
    <p>${modalSubtitle}</p>
    ${tableHtml}
</body>
</html>`;

            const blob = new Blob(['\ufeff', workbookHtml], {
                type: 'application/vnd.ms-excel;charset=utf-8;',
            });
            triggerDashboardExcelDownload(blob, filename);
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

                const sourceSelectId = stackedFilter.dataset.sourceSelectId || '';
                const badgeContainerId = stackedFilter.dataset.badgeContainerId || '';
                const dropdownToggleId = stackedFilter.dataset.dropdownToggleId || '';
                const dropdownMenuId = stackedFilter.dataset.dropdownMenuId || '';
                const sourceSelect = document.getElementById(sourceSelectId);
                const badgeContainer = document.getElementById(badgeContainerId);
                const dropdownToggle = document.getElementById(dropdownToggleId);
                const dropdownMenu = document.getElementById(dropdownMenuId);
                const isMultiple = Boolean(sourceSelect?.multiple);
                const filterLabel = String(
                    sourceSelect?.dataset?.filterLabel
                    || stackedFilter.querySelector('label')?.textContent
                    || 'Filter'
                ).trim();
                const defaultEmptyBadgeText = stackedFilter.dataset.emptyBadgeText
                    || (isMultiple ? `No ${filterLabel.toLowerCase()} selected.` : 'All');
                const defaultEmptyMenuText = stackedFilter.dataset.emptyMenuText
                    || `No ${filterLabel.toLowerCase()} options available.`;
                const searchState = { value: '' };

                if (!sourceSelect || !badgeContainer || !dropdownToggle || !dropdownMenu) {
                    return;
                }

                dropdownMenu.setAttribute('aria-multiselectable', isMultiple ? 'true' : 'false');

                if (dropdownMenu.dataset.overlayAttached !== '1') {
                    document.body.appendChild(dropdownMenu);
                    dropdownMenu.dataset.overlayAttached = '1';
                }

                const updateFilterBodyHeight = () => {
                    const parentForm = stackedFilter.closest('.project-filter-form');
                    if (!parentForm || parentForm.classList.contains('collapsed')) {
                        return;
                    }

                    requestAnimationFrame(() => {
                        setProjectFilterBodyHeight(parentForm);
                    });
                };

                const getSelectOptions = () => Array.from(sourceSelect.options || []);
                const getOptionLabel = (optionEl) => String(optionEl?.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim();
                const getEmptyBadgeText = () => stackedFilter.dataset.emptyBadgeText || defaultEmptyBadgeText;
                const getEmptyMenuText = () => stackedFilter.dataset.emptyMenuText || defaultEmptyMenuText;
                const getEmptyOption = () => getSelectOptions().find((optionEl) => String(optionEl.value || '').trim() === '');
                const ensureSelectionOrder = () => {
                    if (!isMultiple) {
                        return;
                    }

                    if (!Array.isArray(sourceSelect.__selectionOrder)) {
                        sourceSelect.__selectionOrder = getSelectOptions()
                            .filter((optionEl) => optionEl.selected && optionEl.value.trim() !== '')
                            .map((optionEl) => optionEl.value);
                    }
                };
                const updateSelectionOrderForValue = (value, isSelected) => {
                    if (!isMultiple) {
                        return;
                    }

                    ensureSelectionOrder();
                    sourceSelect.__selectionOrder = sourceSelect.__selectionOrder.filter((item) => item !== value);
                    if (isSelected) {
                        sourceSelect.__selectionOrder.push(value);
                    }
                };
                const syncSelectionOrderFromSelect = () => {
                    if (!isMultiple) {
                        return;
                    }

                    ensureSelectionOrder();
                    const selectedValueSet = new Set(
                        getSelectOptions()
                            .filter((optionEl) => optionEl.selected && optionEl.value.trim() !== '')
                            .map((optionEl) => optionEl.value)
                    );

                    sourceSelect.__selectionOrder = sourceSelect.__selectionOrder.filter((value) => selectedValueSet.has(value));
                    getSelectOptions().forEach((optionEl) => {
                        if (
                            optionEl.selected
                            && optionEl.value.trim() !== ''
                            && !sourceSelect.__selectionOrder.includes(optionEl.value)
                        ) {
                            sourceSelect.__selectionOrder.push(optionEl.value);
                        }
                    });
                };
                const getSelectedOptionsInOrder = () => {
                    const selectedOptions = getSelectOptions()
                        .filter((optionEl) => optionEl.selected && optionEl.value.trim() !== '');

                    if (!isMultiple) {
                        return selectedOptions;
                    }

                    ensureSelectionOrder();
                    const optionByValue = new Map(selectedOptions.map((optionEl) => [optionEl.value, optionEl]));
                    const orderedOptions = sourceSelect.__selectionOrder
                        .map((value) => optionByValue.get(value))
                        .filter(Boolean);

                    selectedOptions.forEach((optionEl) => {
                        if (!orderedOptions.includes(optionEl)) {
                            orderedOptions.push(optionEl);
                        }
                    });

                    return orderedOptions;
                };
                const resetSingleSelectToDefault = () => {
                    if (isMultiple) {
                        return;
                    }

                    const selectOptions = getSelectOptions();
                    const emptyOption = getEmptyOption();
                    if (emptyOption) {
                        selectOptions.forEach((optionEl) => {
                            optionEl.selected = optionEl === emptyOption;
                        });
                        return;
                    }

                    sourceSelect.selectedIndex = -1;
                };

                const positionDropdownMenu = () => {
                    if (!dropdownMenu.classList.contains('is-open')) {
                        return;
                    }

                    const viewportMargin = 8;
                    const menuGap = 4;
                    const toggleRect = dropdownToggle.getBoundingClientRect();
                    const availableBelow = Math.max(0, window.innerHeight - toggleRect.bottom - viewportMargin);
                    const availableAbove = Math.max(0, toggleRect.top - viewportMargin);
                    const preferredHeight = Math.min(dropdownMenu.scrollHeight, 220);
                    const shouldOpenUpward = availableBelow < Math.min(preferredHeight, 160) && availableAbove > availableBelow;
                    const availableHeight = Math.max(
                        96,
                        Math.min(
                            Math.max(96, window.innerHeight - (viewportMargin * 2)),
                            (shouldOpenUpward ? availableAbove : availableBelow) - menuGap
                        )
                    );
                    const renderedHeight = Math.min(dropdownMenu.scrollHeight, availableHeight);
                    const renderedWidth = Math.min(toggleRect.width, window.innerWidth - (viewportMargin * 2));
                    const top = shouldOpenUpward
                        ? Math.max(viewportMargin, toggleRect.top - renderedHeight - menuGap)
                        : Math.min(window.innerHeight - viewportMargin - renderedHeight, toggleRect.bottom + menuGap);
                    const left = Math.min(
                        Math.max(viewportMargin, toggleRect.left),
                        window.innerWidth - viewportMargin - renderedWidth
                    );

                    dropdownMenu.style.left = `${left}px`;
                    dropdownMenu.style.top = `${Math.max(viewportMargin, top)}px`;
                    dropdownMenu.style.width = `${renderedWidth}px`;
                    dropdownMenu.style.maxHeight = `${availableHeight}px`;
                };

                const syncDropdownMenuPosition = () => {
                    if (!dropdownMenu.classList.contains('is-open')) {
                        return;
                    }

                    requestAnimationFrame(positionDropdownMenu);
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
                    syncDropdownMenuPosition();
                    const searchInput = dropdownMenu.querySelector('.dashboard-stacked-filter-search-input');
                    if (searchInput) {
                        requestAnimationFrame(() => {
                            searchInput.focus();
                            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                        });
                    }
                };

                const toggleDropdown = () => {
                    if (dropdownMenu.classList.contains('is-open')) {
                        closeDropdown();
                    } else {
                        openDropdown();
                    }
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

                const renderDropdownOptions = () => {
                    const selectOptions = getSelectOptions();
                    const availableOptions = isMultiple
                        ? selectOptions.filter((optionEl) => optionEl.value.trim() !== '')
                        : selectOptions;
                    const normalizedSearch = searchState.value.trim().toLowerCase();
                    const filteredOptions = normalizedSearch === ''
                        ? availableOptions
                        : availableOptions.filter((optionEl) => getOptionLabel(optionEl).toLowerCase().includes(normalizedSearch));

                    dropdownMenu.innerHTML = '';
                    if (availableOptions.length > 0) {
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
                        searchInput.addEventListener('click', (event) => {
                            event.stopPropagation();
                        });
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
                            syncDropdownMenuPosition();
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
                        const activeSearchInput = dropdownMenu.querySelector('.dashboard-stacked-filter-search-input');
                        if (activeSearchInput && dropdownMenu.classList.contains('is-open')) {
                            requestAnimationFrame(() => {
                                activeSearchInput.focus();
                                activeSearchInput.setSelectionRange(activeSearchInput.value.length, activeSearchInput.value.length);
                            });
                        }
                        return;
                    }

                    filteredOptions.forEach((optionEl) => {
                        const optionIndex = selectOptions.indexOf(optionEl);
                        const optionButton = document.createElement('button');
                        optionButton.type = 'button';
                        optionButton.className = 'dashboard-stacked-filter-option';
                        optionButton.dataset.optionIndex = String(optionIndex);
                        optionButton.setAttribute('role', 'option');
                        optionButton.setAttribute('aria-selected', optionEl.selected ? 'true' : 'false');
                        if (optionEl.selected) {
                            optionButton.classList.add('is-selected');
                        }

                        const optionLabel = document.createElement('span');
                        optionLabel.className = 'dashboard-stacked-filter-option-label';
                        optionLabel.textContent = getOptionLabel(optionEl);

                        const optionCheck = document.createElement('span');
                        optionCheck.className = 'dashboard-stacked-filter-option-check';
                        optionCheck.textContent = '✓';

                        optionButton.appendChild(optionLabel);
                        optionButton.appendChild(optionCheck);
                        dropdownMenu.appendChild(optionButton);
                    });

                    const activeSearchInput = dropdownMenu.querySelector('.dashboard-stacked-filter-search-input');
                    if (activeSearchInput && dropdownMenu.classList.contains('is-open')) {
                        requestAnimationFrame(() => {
                            activeSearchInput.focus();
                            activeSearchInput.setSelectionRange(activeSearchInput.value.length, activeSearchInput.value.length);
                        });
                    }
                };

                dropdownToggle.addEventListener('click', (event) => {
                    if (event.target.closest('.dashboard-filter-badge-remove')) {
                        return;
                    }

                    toggleDropdown();
                });

                dropdownToggle.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        toggleDropdown();
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
                    if (!Number.isInteger(optionIndex) || optionIndex < 0) {
                        return;
                    }

                    const matchingOption = sourceSelect.options[optionIndex];
                    if (!matchingOption) {
                        return;
                    }

                    if (isMultiple) {
                        matchingOption.selected = !matchingOption.selected;
                        updateSelectionOrderForValue(matchingOption.value, matchingOption.selected);
                    } else {
                        getSelectOptions().forEach((optionEl) => {
                            optionEl.selected = optionEl === matchingOption;
                        });
                    }

                    renderBadges();
                    renderDropdownOptions();
                    sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    if (!isMultiple) {
                        closeDropdown();
                    }
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
                sourceSelect.addEventListener('change', () => {
                    syncSelectionOrderFromSelect();
                    renderBadges();
                    renderDropdownOptions();
                });

                syncSelectionOrderFromSelect();
                renderBadges();
                renderDropdownOptions();
                stackedFilter.__closeDropdown = closeDropdown;
                stackedFilter.__refreshFilterUi = () => {
                    renderBadges();
                    renderDropdownOptions();
                };
                stackedFilter.dataset.stackedFilterInitialized = '1';
            });
        }

        function initializeDashboardLocationDependencies() {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city_municipality');
            const barangaySelect = document.getElementById('barangay');
            const cityStackedFilter = document.querySelector('[data-stacked-filter][data-source-select-id="city_municipality"]');
            const barangayStackedFilter = document.querySelector('[data-stacked-filter][data-source-select-id="barangay"]');

            if (!provinceSelect || !citySelect || !barangaySelect || !cityStackedFilter || !barangayStackedFilter) {
                return;
            }

            const rebuildBarangayOptions = () => {
                const selectedCityOptions = Array.from(citySelect.selectedOptions || [])
                    .map((optionEl) => String(optionEl.value || '').trim())
                    .filter((value) => value !== '');
                const selectedCityValueSet = new Set(selectedCityOptions);
                const selectedCityValues = Array.isArray(citySelect.__selectionOrder)
                    ? citySelect.__selectionOrder.filter((value) => selectedCityValueSet.has(value))
                    : selectedCityOptions;
                const selectedBarangayValues = new Set(
                    Array.from(barangaySelect.selectedOptions || [])
                        .map((optionEl) => String(optionEl.value || '').trim())
                        .filter((value) => value !== '')
                );
                const orderedBarangays = [];
                const seenBarangays = new Set();

                selectedCityValues.forEach((cityValue) => {
                    const barangays = Array.isArray(DASHBOARD_CITY_BARANGAY_OPTIONS?.[cityValue])
                        ? DASHBOARD_CITY_BARANGAY_OPTIONS[cityValue]
                        : [];

                    barangays.forEach((barangayValue) => {
                        const normalizedBarangay = String(barangayValue || '').trim();
                        if (normalizedBarangay === '') {
                            return;
                        }

                        const dedupeKey = normalizedBarangay.toLowerCase();
                        if (seenBarangays.has(dedupeKey)) {
                            return;
                        }

                        seenBarangays.add(dedupeKey);
                        orderedBarangays.push(normalizedBarangay);
                    });
                });

                barangaySelect.innerHTML = '';
                orderedBarangays.forEach((barangayValue) => {
                    const optionEl = document.createElement('option');
                    optionEl.value = barangayValue;
                    optionEl.textContent = barangayValue;
                    optionEl.selected = selectedBarangayValues.has(barangayValue);
                    barangaySelect.appendChild(optionEl);
                });

                barangayStackedFilter.dataset.emptyBadgeText = selectedCityValues.length > 0
                    ? 'All'
                    : 'Select city/municipality first';
                barangayStackedFilter.dataset.emptyMenuText = selectedCityValues.length > 0
                    ? 'No barangay options available.'
                    : 'Select city/municipality first.';

                if (typeof barangayStackedFilter.__refreshFilterUi === 'function') {
                    barangayStackedFilter.__refreshFilterUi();
                }
            };

            const rebuildCityOptions = () => {
                const selectedProvinceOptions = Array.from(provinceSelect.selectedOptions || [])
                    .map((optionEl) => String(optionEl.value || '').trim())
                    .filter((value) => value !== '');
                const selectedProvinceValueSet = new Set(selectedProvinceOptions);
                const selectedProvinceValues = Array.isArray(provinceSelect.__selectionOrder)
                    ? provinceSelect.__selectionOrder.filter((value) => selectedProvinceValueSet.has(value))
                    : selectedProvinceOptions;
                const selectedCityValues = new Set(
                    Array.from(citySelect.selectedOptions || [])
                        .map((optionEl) => String(optionEl.value || '').trim())
                        .filter((value) => value !== '')
                );
                const orderedCities = [];
                const seenCities = new Set();

                selectedProvinceValues.forEach((provinceValue) => {
                    const cities = Array.isArray(DASHBOARD_PROVINCE_CITY_OPTIONS?.[provinceValue])
                        ? DASHBOARD_PROVINCE_CITY_OPTIONS[provinceValue]
                        : [];

                    cities.forEach((cityValue) => {
                        const normalizedCity = String(cityValue || '').trim();
                        if (normalizedCity === '') {
                            return;
                        }

                        const dedupeKey = normalizedCity.toLowerCase();
                        if (seenCities.has(dedupeKey)) {
                            return;
                        }

                        seenCities.add(dedupeKey);
                        orderedCities.push(normalizedCity);
                    });
                });

                citySelect.innerHTML = '';
                orderedCities.forEach((cityValue) => {
                    const optionEl = document.createElement('option');
                    optionEl.value = cityValue;
                    optionEl.textContent = cityValue;
                    optionEl.selected = selectedCityValues.has(cityValue);
                    citySelect.appendChild(optionEl);
                });

                cityStackedFilter.dataset.emptyBadgeText = selectedProvinceValues.length > 0
                    ? 'All'
                    : 'Select province first';
                cityStackedFilter.dataset.emptyMenuText = selectedProvinceValues.length > 0
                    ? 'No city/municipality options available.'
                    : 'Select province first.';

                if (typeof cityStackedFilter.__refreshFilterUi === 'function') {
                    cityStackedFilter.__refreshFilterUi();
                }

                citySelect.dispatchEvent(new Event('change', { bubbles: true }));
            };

            provinceSelect.addEventListener('change', rebuildCityOptions);
            citySelect.addEventListener('change', rebuildBarangayOptions);
            rebuildCityOptions();
        }

        function collectDashboardExportFilters() {
            const dashboardForm = document.querySelector('.project-filter-form');
            if (!dashboardForm) {
                return [];
            }

            const filters = [];
            dashboardForm.querySelectorAll('select').forEach((selectEl) => {
                if (selectEl.dataset.filterHelper === '1') {
                    return;
                }

                let selectedText = 'All';
                if (selectEl.multiple) {
                    const selectedValues = Array.from(selectEl.selectedOptions || [])
                        .map((optionEl) => optionEl.textContent.replace(/\s+/g, ' ').trim())
                        .filter((value) => value !== '');
                    selectedText = selectedValues.length > 0 ? selectedValues.join(', ') : 'All';
                } else {
                    const selectedOption = selectEl.options[selectEl.selectedIndex];
                    selectedText = selectedOption ? selectedOption.textContent.replace(/\s+/g, ' ').trim() : 'All';
                }
                const labelText = selectEl.dataset.filterLabel || dashboardForm.querySelector(`label[for="${selectEl.id}"]`)?.textContent?.trim() || selectEl.name;
                filters.push([labelText, selectedText || 'All']);
            });

            return filters;
        }

        function exportDashboardOverviewToExcel(button) {
            if (!button || button.disabled) {
                return;
            }

            const exportButtonOriginalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-file-excel" aria-hidden="true"></i> Exporting...';

            try {
                const filename = normalizeDashboardExcelFilename(button.dataset.exportFilename, 'status-of-projects-by-location');
                const generatedAt = new Date().toLocaleString();
                const selectedFilters = collectDashboardExportFilters();
                const statusColumns = (Array.isArray(STATUS_LOCATION_EXPORT_STATUSES) ? STATUS_LOCATION_EXPORT_STATUSES : [])
                    .map((statusLabel) => String(statusLabel || '').trim())
                    .filter((statusLabel) => statusLabel !== '');
                const reportRows = Array.isArray(STATUS_LOCATION_EXPORT_ROWS) ? STATUS_LOCATION_EXPORT_ROWS : [];
                const groupedStatusRows = Array.isArray(PROVINCE_FUNDING_YEAR_PROGRAM_STATUS_EXPORT_ROWS)
                    ? PROVINCE_FUNDING_YEAR_PROGRAM_STATUS_EXPORT_ROWS
                    : [];
                const groupedStatusSourceRows = Array.isArray(PROVINCE_FUNDING_YEAR_PROGRAM_STATUS_SOURCE_ROWS)
                    ? PROVINCE_FUNDING_YEAR_PROGRAM_STATUS_SOURCE_ROWS
                    : [];

                const toNumber = (value) => {
                    const numeric = Number(value);
                    return Number.isFinite(numeric) ? numeric : 0;
                };
                const toInt = (value) => Math.trunc(toNumber(value));
                const createMergedRow = (value, styleId, columnCount) => ([
                    {
                        value,
                        styleId,
                        mergeAcross: Math.max(0, Math.trunc(columnCount) - 1),
                    },
                ]);
                const createBlankRow = (columnCount) => createMergedRow('', 'Cell', columnCount);

                const normalizedRows = reportRows.map((row) => ({
                    row_type: String(row?.row_type || '').toLowerCase(),
                    province: String(row?.province || '').trim(),
                    city_municipality: String(row?.city_municipality || '').trim(),
                    counts: row && typeof row === 'object' && row.counts && typeof row.counts === 'object'
                        ? row.counts
                        : {},
                }));

                const provinceSummaryRows = normalizedRows.filter((row) => row.row_type === 'province');
                const citySummaryRows = normalizedRows.filter((row) => row.row_type === 'city');
                const summaryRows = provinceSummaryRows.length > 0 ? provinceSummaryRows : citySummaryRows;
                const statusTotals = {};
                statusColumns.forEach((statusLabel) => {
                    statusTotals[statusLabel] = 0;
                });
                summaryRows.forEach((row) => {
                    statusColumns.forEach((statusLabel) => {
                        statusTotals[statusLabel] += toNumber(row.counts?.[statusLabel] ?? 0);
                    });
                });
                const totalProjects = statusColumns.reduce((carry, statusLabel) => carry + toNumber(statusTotals[statusLabel] ?? 0), 0);
                const normalizedGroupedStatusRows = (() => {
                    if (groupedStatusSourceRows.length > 0) {
                        const aggregatedRows = new Map();

                        groupedStatusSourceRows.forEach((row) => {
                            const provinceLabel = String(row?.province || '').trim() || 'Unspecified Province';
                            const fundingYearLabel = String(row?.funding_year || '').trim() || 'Unspecified Funding Year';
                            const programLabel = String(row?.program || '').trim() || 'Unspecified Program';
                            const projectStatusLabel = String(row?.project_status || '').trim() || 'Unspecified Status';
                            const rowKey = [provinceLabel, fundingYearLabel, programLabel, projectStatusLabel].join('||');

                            if (!aggregatedRows.has(rowKey)) {
                                aggregatedRows.set(rowKey, {
                                    province: provinceLabel,
                                    funding_year: fundingYearLabel,
                                    program: programLabel,
                                    project_status: projectStatusLabel,
                                    total: 0,
                                });
                            }

                            aggregatedRows.get(rowKey).total += 1;
                        });

                        return Array.from(aggregatedRows.values());
                    }

                    return groupedStatusRows.map((row) => ({
                        province: String(row?.province || '').trim() || 'Unspecified Province',
                        funding_year: String(row?.funding_year || '').trim() || 'Unspecified Funding Year',
                        program: String(row?.program || '').trim() || 'Unspecified Program',
                        project_status: String(row?.project_status || '').trim() || 'Unspecified Status',
                        total: toInt(row?.total ?? 0),
                    }));
                })();
                const overviewColumnCount = Math.max(statusColumns.length + 3, 3);
                const overviewColumnWidths = [
                    140,
                    180,
                    ...Array.from({ length: statusColumns.length }, () => 88),
                    80,
                ];

                const filterRows = selectedFilters.length > 0
                    ? selectedFilters.map(([label, value]) => ([
                        { value: label, styleId: 'Cell' },
                        { value: value || 'All', styleId: 'Cell' },
                    ]))
                    : [[{ value: 'No filters', styleId: 'Cell', mergeAcross: 1 }]];

                const summarySheetRows = [
                    createMergedRow('Status Of Projects By Province And City/Municipality', 'Title', overviewColumnCount),
                    createMergedRow(`Generated at: ${generatedAt}`, 'Meta', overviewColumnCount),
                    createBlankRow(overviewColumnCount),
                    createMergedRow('Applied Filters', 'SectionHeader', overviewColumnCount),
                    [
                        { value: 'Filter', styleId: 'Header' },
                        { value: 'Value', styleId: 'Header' },
                    ],
                    ...filterRows,
                    createBlankRow(overviewColumnCount),
                    createMergedRow('Status Summary (All Projects)', 'SectionHeader', overviewColumnCount),
                    [
                        { value: 'Status', styleId: 'Header' },
                        { value: 'Count', styleId: 'Header' },
                        { value: '% of Projects', styleId: 'Header' },
                    ],
                ];

                if (statusColumns.length > 0) {
                    statusColumns.forEach((statusLabel) => {
                        const countValue = toNumber(statusTotals[statusLabel] ?? 0);
                        const shareValue = totalProjects > 0 ? `${((countValue / totalProjects) * 100).toFixed(2)}%` : '0.00%';
                        summarySheetRows.push([
                            { value: statusLabel, styleId: 'Cell' },
                            { value: toInt(countValue), type: 'Number', styleId: 'CellRight' },
                            { value: shareValue, styleId: 'CellRight' },
                        ]);
                    });
                } else {
                    summarySheetRows.push(createMergedRow('No status data found for the selected filters.', 'Cell', 3));
                }

                summarySheetRows.push([
                    { value: 'Total Projects', styleId: 'GroupCell' },
                    { value: toInt(totalProjects), type: 'Number', styleId: 'GroupCellRight' },
                    { value: totalProjects > 0 ? '100.00%' : '0.00%', styleId: 'GroupCellRight' },
                ]);
                summarySheetRows.push(createBlankRow(overviewColumnCount));
                summarySheetRows.push(createMergedRow('Province and City/Municipality Breakdown', 'SectionHeader', overviewColumnCount));
                summarySheetRows.push([
                    { value: 'Province', styleId: 'Header' },
                    { value: 'City/Municipality', styleId: 'Header' },
                    ...statusColumns.map((statusLabel) => ({ value: statusLabel, styleId: 'Header' })),
                    { value: 'Total', styleId: 'Header' },
                ]);

                if (normalizedRows.length > 0) {
                    normalizedRows.forEach((row) => {
                        const isProvinceRow = row.row_type === 'province';
                        let rowTotal = 0;
                        const leftStyleId = isProvinceRow ? 'GroupCell' : 'Cell';
                        const rightStyleId = isProvinceRow ? 'GroupCellRight' : 'CellRight';
                        const detailRow = [
                            { value: row.province || '-', styleId: leftStyleId },
                            { value: isProvinceRow ? 'All Cities/Municipalities' : (row.city_municipality || '-'), styleId: leftStyleId },
                        ];

                        statusColumns.forEach((statusLabel) => {
                            const countValue = toNumber(row.counts?.[statusLabel] ?? 0);
                            rowTotal += countValue;
                            detailRow.push({
                                value: toInt(countValue),
                                type: 'Number',
                                styleId: rightStyleId,
                            });
                        });

                        detailRow.push({
                            value: toInt(rowTotal),
                            type: 'Number',
                            styleId: rightStyleId,
                        });
                        summarySheetRows.push(detailRow);
                    });
                } else {
                    summarySheetRows.push(createMergedRow('No province/city status rows found for the selected filters.', 'Cell', overviewColumnCount));
                }

                const hierarchyColumnCount = statusColumns.length + 2;
                const compareAlphaLabels = (leftValue, rightValue) =>
                    String(leftValue || '').localeCompare(String(rightValue || ''), undefined, {
                        numeric: true,
                        sensitivity: 'base',
                    });
                const compareFundingYearLabels = (leftValue, rightValue) => {
                    const leftLabel = String(leftValue || '').trim();
                    const rightLabel = String(rightValue || '').trim();
                    const leftIsNumeric = /^\d+$/.test(leftLabel);
                    const rightIsNumeric = /^\d+$/.test(rightLabel);

                    if (leftIsNumeric && rightIsNumeric) {
                        const numericCompare = Number(rightLabel) - Number(leftLabel);
                        if (numericCompare !== 0) {
                            return numericCompare;
                        }
                    }

                    return compareAlphaLabels(leftLabel, rightLabel);
                };
                const createEmptyStatusCounts = () => statusColumns.reduce((carry, statusLabel) => {
                    carry[statusLabel] = 0;
                    return carry;
                }, {});
                const incrementStatusCounts = (counts, statusLabel, countValue) => {
                    if (!Object.prototype.hasOwnProperty.call(counts, statusLabel)) {
                        counts[statusLabel] = 0;
                    }

                    counts[statusLabel] += toInt(countValue);
                };
                const computeCountsTotal = (counts) => statusColumns.reduce(
                    (carry, statusLabel) => carry + toInt(counts?.[statusLabel] ?? 0),
                    0
                );
                const createHierarchyMetricCells = (counts, metricStyleId) => {
                    let total = 0;
                    const metricCells = statusColumns.map((statusLabel) => {
                        const countValue = toInt(counts?.[statusLabel] ?? 0);
                        total += countValue;

                        if (countValue > 0) {
                            return {
                                value: countValue,
                                type: 'Number',
                                styleId: metricStyleId,
                            };
                        }

                        return {
                            value: '',
                            styleId: metricStyleId,
                        };
                    });

                    metricCells.push(total > 0
                        ? {
                            value: total,
                            type: 'Number',
                            styleId: metricStyleId,
                        }
                        : {
                            value: '',
                            styleId: metricStyleId,
                        });

                    return metricCells;
                };
                const completedCountLabel = statusColumns.find((statusLabel) => statusLabel.toLowerCase() === 'completed')
                    || statusColumns[0]
                    || 'Completed';
                const activeFiltersText = selectedFilters
                    .filter(([, value]) => String(value || '').trim() !== '' && String(value || '').trim() !== 'All')
                    .map(([label, value]) => `${label}: ${value}`)
                    .join(' | ') || 'All dashboard filters';
                const hierarchyTree = new Map();
                const fundingYearSummaryMap = new Map();

                normalizedGroupedStatusRows.forEach((row) => {
                    const countValue = toInt(row.total ?? 0);

                    if (!hierarchyTree.has(row.province)) {
                        hierarchyTree.set(row.province, {
                            counts: createEmptyStatusCounts(),
                            fundingYears: new Map(),
                        });
                    }

                    const provinceData = hierarchyTree.get(row.province);
                    incrementStatusCounts(provinceData.counts, row.project_status, countValue);

                    if (!provinceData.fundingYears.has(row.funding_year)) {
                        provinceData.fundingYears.set(row.funding_year, {
                            counts: createEmptyStatusCounts(),
                            programs: new Map(),
                        });
                    }

                    const fundingYearData = provinceData.fundingYears.get(row.funding_year);
                    incrementStatusCounts(fundingYearData.counts, row.project_status, countValue);

                    if (!fundingYearData.programs.has(row.program)) {
                        fundingYearData.programs.set(row.program, createEmptyStatusCounts());
                    }

                    const programCounts = fundingYearData.programs.get(row.program);
                    incrementStatusCounts(programCounts, row.project_status, countValue);

                    if (!fundingYearSummaryMap.has(row.funding_year)) {
                        fundingYearSummaryMap.set(row.funding_year, {
                            counts: createEmptyStatusCounts(),
                            programs: new Map(),
                            provinces: new Set(),
                        });
                    }

                    const fundingYearSummary = fundingYearSummaryMap.get(row.funding_year);
                    incrementStatusCounts(fundingYearSummary.counts, row.project_status, countValue);
                    fundingYearSummary.provinces.add(row.province);

                    if (!fundingYearSummary.programs.has(row.program)) {
                        fundingYearSummary.programs.set(row.program, createEmptyStatusCounts());
                    }

                    const fundingYearProgramCounts = fundingYearSummary.programs.get(row.program);
                    incrementStatusCounts(fundingYearProgramCounts, row.project_status, countValue);
                });

                const sortedFundingYearEntries = Array.from(fundingYearSummaryMap.entries())
                    .sort(([leftFundingYear], [rightFundingYear]) => compareFundingYearLabels(leftFundingYear, rightFundingYear));
                const hierarchySheetRows = [
                    createMergedRow('Province, Funding Year, and Program Status Analysis', 'SheetTitle', hierarchyColumnCount),
                    createMergedRow('Styled matrix with subtotals and funding-year analysis', 'SheetSubtitle', hierarchyColumnCount),
                    createMergedRow(`Generated at: ${generatedAt} | Filters: ${activeFiltersText}`, 'SheetMeta', hierarchyColumnCount),
                    createBlankRow(hierarchyColumnCount),
                    createMergedRow('Funding Year Analysis', 'AnalysisSection', hierarchyColumnCount),
                    [
                        { value: 'Funding Year', styleId: 'AnalysisHeader' },
                        { value: 'Total Projects', styleId: 'AnalysisHeader' },
                        { value: 'Programs', styleId: 'AnalysisHeader' },
                        { value: 'Provinces', styleId: 'AnalysisHeader' },
                        { value: 'Completed', styleId: 'AnalysisHeader' },
                    ],
                ];

                if (sortedFundingYearEntries.length > 0) {
                    sortedFundingYearEntries.forEach(([fundingYearLabel, fundingYearSummary]) => {
                        const fundingYearTotal = computeCountsTotal(fundingYearSummary.counts);
                        const completedProjects = toInt(fundingYearSummary.counts?.[completedCountLabel] ?? 0);

                        hierarchySheetRows.push([
                            { value: fundingYearLabel, styleId: 'AnalysisCell' },
                            { value: fundingYearTotal, type: 'Number', styleId: 'AnalysisCellRight' },
                            { value: fundingYearSummary.programs.size, type: 'Number', styleId: 'AnalysisCellRight' },
                            { value: fundingYearSummary.provinces.size, type: 'Number', styleId: 'AnalysisCellRight' },
                            { value: completedProjects, type: 'Number', styleId: 'AnalysisCellRight' },
                        ]);
                    });
                } else {
                    hierarchySheetRows.push(createMergedRow('No funding year analysis rows found for the selected filters.', 'AnalysisCell', hierarchyColumnCount));
                }

                hierarchySheetRows.push(createBlankRow(hierarchyColumnCount));
                hierarchySheetRows.push(createMergedRow('Status Summary of All Projects', 'AnalysisSection', hierarchyColumnCount));
                hierarchySheetRows.push([
                    { value: 'Status', styleId: 'AnalysisHeader' },
                    { value: 'Projects', styleId: 'AnalysisHeader' },
                    { value: '% of Total', styleId: 'AnalysisHeader' },
                ]);

                if (statusColumns.length > 0) {
                    statusColumns.forEach((statusLabel) => {
                        const countValue = toInt(statusTotals[statusLabel] ?? 0);
                        const shareValue = totalProjects > 0
                            ? `${((countValue / totalProjects) * 100).toFixed(1)}%`
                            : '0.0%';

                        hierarchySheetRows.push([
                            { value: statusLabel, styleId: 'AnalysisCell' },
                            { value: countValue, type: 'Number', styleId: 'AnalysisCellRight' },
                            { value: shareValue, styleId: 'AnalysisTextRight' },
                        ]);
                    });

                    hierarchySheetRows.push([
                        { value: 'Total Projects', styleId: 'AnalysisCell' },
                        { value: toInt(totalProjects), type: 'Number', styleId: 'AnalysisCellRight' },
                        { value: totalProjects > 0 ? '100.0%' : '0.0%', styleId: 'AnalysisTextRight' },
                    ]);
                } else {
                    hierarchySheetRows.push(createMergedRow('No status summary rows found for the selected filters.', 'AnalysisCell', hierarchyColumnCount));
                }

                hierarchySheetRows.push(createBlankRow(hierarchyColumnCount));
                hierarchySheetRows.push(createMergedRow('Province > Funding Year > Program Status Matrix', 'AnalysisSection', hierarchyColumnCount));
                hierarchySheetRows.push([
                    { value: 'Province', styleId: 'HierarchyHeader' },
                    ...statusColumns.map((statusLabel) => ({ value: statusLabel, styleId: 'HierarchyHeader' })),
                    { value: 'Total', styleId: 'HierarchyHeader' },
                ]);

                if (hierarchyTree.size > 0) {
                    Array.from(hierarchyTree.entries())
                        .sort(([leftProvince], [rightProvince]) => compareAlphaLabels(leftProvince, rightProvince))
                        .forEach(([provinceLabel, provinceData]) => {
                            hierarchySheetRows.push([
                                { value: provinceLabel, styleId: 'HierarchyProvince' },
                                ...createHierarchyMetricCells(provinceData.counts, 'HierarchyProvinceMetric'),
                            ]);

                            Array.from(provinceData.fundingYears.entries())
                                .sort(([leftFundingYear], [rightFundingYear]) => compareFundingYearLabels(leftFundingYear, rightFundingYear))
                                .forEach(([fundingYearLabel, fundingYearData]) => {
                                    hierarchySheetRows.push([
                                        { value: fundingYearLabel, styleId: 'HierarchyFundingYear' },
                                        ...createHierarchyMetricCells(fundingYearData.counts, 'HierarchyFundingYearMetric'),
                                    ]);

                                    Array.from(fundingYearData.programs.entries())
                                        .sort(([leftProgram], [rightProgram]) => compareAlphaLabels(leftProgram, rightProgram))
                                        .forEach(([programLabel, programCounts]) => {
                                            hierarchySheetRows.push([
                                                { value: programLabel, styleId: 'HierarchyProgram' },
                                                ...createHierarchyMetricCells(programCounts, 'HierarchyProgramMetric'),
                                            ]);
                                        });
                                });
                        });
                } else {
                    hierarchySheetRows.push(createMergedRow('No province, funding year, or program rows found for the selected filters.', 'Cell', hierarchyColumnCount));
                }

                const workbookXml = buildDashboardSpreadsheetXml([
                    {
                        name: 'Status Overview',
                        columns: overviewColumnWidths,
                        rows: summarySheetRows,
                    },
                    {
                        name: 'Province FY Program',
                        columns: [220, ...statusColumns.map((statusLabel) => {
                            if (statusLabel.length >= 18) {
                                return 130;
                            }

                            if (statusLabel.length >= 12) {
                                return 110;
                            }

                            return 90;
                        }), 90],
                        rows: hierarchySheetRows,
                    },
                ]);

                const blob = new Blob([workbookXml], {
                    type: 'application/vnd.ms-excel;charset=utf-8;',
                });
                triggerDashboardExcelDownload(blob, filename);
            } catch (error) {
                console.error('Dashboard export failed.', error);
                window.alert('Unable to export dashboard report right now. Please try again.');
            } finally {
                button.disabled = false;
                button.innerHTML = exportButtonOriginalHtml;
            }
        }

        function formatDashboardAnimatedBarValue(value, format) {
            if (format === 'currency') {
                return new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value || 0);
            }

            if (format === 'decimal') {
                return Number(value || 0).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            return Number(Math.round(value || 0)).toLocaleString('en-PH');
        }

        function initializeStatusSubaybayanBars() {
            const barContainers = document.querySelectorAll('[data-dashboard-status-bars]');
            if (!barContainers.length) {
                return;
            }

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            barContainers.forEach((barContainer) => {
                if (barContainer.dataset.statusBarsInitialized === '1') {
                    return;
                }

                barContainer.dataset.statusBarsInitialized = '1';
                const animatedRows = Array.from(barContainer.querySelectorAll('[data-sg-bar-animate]'));

                animatedRows.forEach((rowElement, index) => {
                    const fillElement = rowElement.querySelector('[data-sg-bar-fill]');
                    const numberElements = Array.from(rowElement.querySelectorAll('[data-sg-bar-number]'));
                    const targetWidth = fillElement ? Number.parseFloat(fillElement.getAttribute('data-target-width') || '0') : 0;

                    if (prefersReducedMotion) {
                        if (fillElement) {
                            fillElement.style.width = `${targetWidth}%`;
                        }

                        numberElements.forEach((numberElement) => {
                            const targetValue = Number.parseFloat(numberElement.getAttribute('data-value') || '0');
                            const format = numberElement.getAttribute('data-format') || 'integer';
                            numberElement.textContent = formatDashboardAnimatedBarValue(targetValue, format);
                        });

                        return;
                    }

                    const durationMs = 1000;
                    const delayMs = index * 90;

                    window.setTimeout(() => {
                        const startTime = performance.now();
                        const easeOutCubic = (progress) => 1 - Math.pow(1 - progress, 3);

                        if (fillElement) {
                            fillElement.style.width = '0%';
                        }

                        numberElements.forEach((numberElement) => {
                            const format = numberElement.getAttribute('data-format') || 'integer';
                            numberElement.textContent = formatDashboardAnimatedBarValue(0, format);
                        });

                        const updateFrame = (now) => {
                            const rawProgress = Math.min((now - startTime) / durationMs, 1);
                            const easedProgress = easeOutCubic(rawProgress);

                            if (fillElement) {
                                fillElement.style.width = `${(targetWidth * easedProgress).toFixed(2)}%`;
                            }

                            numberElements.forEach((numberElement) => {
                                const targetValue = Number.parseFloat(numberElement.getAttribute('data-value') || '0');
                                const format = numberElement.getAttribute('data-format') || 'integer';
                                numberElement.textContent = formatDashboardAnimatedBarValue(targetValue * easedProgress, format);
                            });

                            if (rawProgress < 1) {
                                window.requestAnimationFrame(updateFrame);
                            }
                        };

                        window.requestAnimationFrame(updateFrame);
                    }, delayMs);
                });
            });
        }

        function initializeDashboardModals() {
            const modalElements = document.querySelectorAll('.dashboard-modal');
            if (!modalElements.length) {
                return;
            }

            modalElements.forEach((modalElement) => {
                modalElement.querySelectorAll('[data-close-modal]').forEach((closeControl) => {
                    if (closeControl.dataset.closeModalInitialized === '1') {
                        return;
                    }

                    closeControl.dataset.closeModalInitialized = '1';
                    closeControl.addEventListener('click', () => {
                        closeDashboardModal(modalElement);
                    });
                });
            });

            if (document.body.dataset.dashboardModalEscInitialized === '1') {
                return;
            }

            document.body.dataset.dashboardModalEscInitialized = '1';
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                const openModal = document.querySelector('.dashboard-modal.is-open');
                if (openModal) {
                    closeDashboardModal(openModal);
                }
            });
        }

        function initializeClickableDashboardCards() {
            const clickableCards = document.querySelectorAll('.clickable-dashboard-card[data-card-url]');
            clickableCards.forEach((card) => {
                if (card.dataset.cardLinkInitialized === '1') {
                    return;
                }

                card.dataset.cardLinkInitialized = '1';
                card.setAttribute('role', 'link');
                card.setAttribute('tabindex', '0');

                card.addEventListener('click', (event) => {
                    if (event.target.closest('a, button, input, select, textarea, label, summary, [role="button"]')) {
                        return;
                    }

                    const modalTargetId = card.dataset.modalTarget;
                    if (modalTargetId) {
                        const modalElement = document.getElementById(modalTargetId);
                        if (modalElement) {
                            openDashboardModal(modalElement);
                            return;
                        }
                    }

                    const destinationUrl = card.dataset.cardUrl;
                    if (destinationUrl) {
                        if (window.AppUI && typeof window.AppUI.showPageLoader === 'function') {
                            window.AppUI.showPageLoader({
                                title: 'Loading dashboard details',
                                detail: 'Preparing the selected dashboard records.',
                            });
                        }
                        window.location.href = destinationUrl;
                    }
                });

                card.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    const modalTargetId = card.dataset.modalTarget;
                    if (modalTargetId) {
                        const modalElement = document.getElementById(modalTargetId);
                        if (modalElement) {
                            openDashboardModal(modalElement);
                            return;
                        }
                    }

                    const destinationUrl = card.dataset.cardUrl;
                    if (destinationUrl) {
                        if (window.AppUI && typeof window.AppUI.showPageLoader === 'function') {
                            window.AppUI.showPageLoader({
                                title: 'Loading dashboard details',
                                detail: 'Preparing the selected dashboard records.',
                            });
                        }
                        window.location.href = destinationUrl;
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('.project-filter-form');
            const legendBlocks = document.querySelectorAll('.dashboard-legend-block');
            initializeStackedFilters();
            initializeDashboardLocationDependencies();

            forms.forEach((form) => {
                const shouldStartCollapsed = readProjectFilterCollapsedState();
                const toggleButton = form.querySelector('.project-filter-toggle');

                form.classList.toggle('collapsed', shouldStartCollapsed);
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', shouldStartCollapsed ? 'false' : 'true');
                }

                setProjectFilterBodyHeight(form);
            });

            legendBlocks.forEach((legendBlock) => {
                const legendKey = legendBlock.dataset.legendKey || '';
                const shouldStartCollapsed = readDashboardLegendCollapsedState(legendKey);
                const toggleButton = legendBlock.querySelector('.dashboard-legend-toggle');

                legendBlock.classList.toggle('collapsed', shouldStartCollapsed);
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', shouldStartCollapsed ? 'false' : 'true');
                }

                setDashboardLegendBodyHeight(legendBlock);
            });

            initializeDashboardModals();
            initializeClickableDashboardCards();
            initializeStatusSubaybayanBars();
            syncRiskCardHeightsWithStatusCard();

            window.addEventListener('resize', () => {
                forms.forEach((form) => {
                    if (!form.classList.contains('collapsed')) {
                        setProjectFilterBodyHeight(form);
                    }
                });

                legendBlocks.forEach((legendBlock) => {
                    if (!legendBlock.classList.contains('collapsed')) {
                        setDashboardLegendBodyHeight(legendBlock);
                    }
                });

                syncRiskCardHeightsWithStatusCard();
            });
        });
    </script>
@endsection
