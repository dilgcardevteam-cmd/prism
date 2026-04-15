@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- ALL ORIGINAL CONTENT FROM PREVIOUS read_file -->
    <!-- ... (keep all existing dashboard content exactly as is) ... -->

    {{-- Add cascading data attributes to existing stacked filters --}}
    <div class="dashboard-filter-grid" style="display: grid; grid-template-columns: repeat(3, minmax(200px, 1fr)); gap: 12px 16px; align-items: end;" data-dashboard-filter-dependencies='{
        "province": ["city_municipality", "barangay"],
        "city_municipality": ["barangay"],
        "barangay": [],
        "program": [],
        "funding_year": [],
        "project_type": [],
        "project_status": []
    }'>
        <input type="hidden" name="filter_cascading" value="enabled" data-current-filters="{{ json_encode($filters ?? []) }}">
        
        {{-- Province filter with data-depends-on --}}
        <div
            class="dashboard-stacked-filter"
            data-stacked-filter
            data-depends-on=""
            data-trigger-for="city_municipality,barangay"
            data-source-select-id="province"
            data-badge-container-id="province_badges"
            data-dropdown-toggle-id="province_dropdown_toggle"
            data-dropdown-menu-id="province_dropdown_menu"
            data-empty-badge-text="All"
        >
            <!-- existing province filter content -->
        </div>

        {{-- City/Municipality filter depends on province --}}
        <div
            class="dashboard-stacked-filter"
            data-stacked-filter
            data-depends-on="province"
            data-trigger-for="barangay"
            data-source-select-id="city_municipality"
            <!-- ... rest unchanged ... -->
        >
            <!-- existing content -->
        </div>

        {{-- Barangay filter depends on province + city --}}
        <div
            class="dashboard-stacked-filter"
            data-stacked-filter
            data-depends-on="province,city_municipality"
            data-source-select-id="barangay"
            <!-- ... rest unchanged ... -->
        >
            <!-- existing content -->
        </div>

        {{-- Other filters independent --}}
        <div class="dashboard-stacked-filter" data-stacked-filter data-depends-on="" data-trigger-for="">
            <!-- program, funding_year, project_type, project_status unchanged but add data attrs -->
        </div>
    </div>

    {{-- Rest of dashboard unchanged --}}
</section>

@push('scripts')
    {{-- Load cascading JS AFTER existing dashboard JS --}}
    <script src="{{ asset('js/dashboard-filters.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger rebuild of stacked filter UI after JS load
            document.querySelectorAll('[data-stacked-filter]').forEach(stackedFilter => {
                stackedFilter.dispatchEvent(new CustomEvent('stacked-filter-init'));
            });
            
            console.log('Dashboard cascading filters ready ✅');
        });
    </script>
@endpush

@endsection
