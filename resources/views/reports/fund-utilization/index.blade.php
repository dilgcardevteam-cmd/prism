@extends('layouts.dashboard')

@section('title', 'Fund Utilization Report')
@section('page-title', 'Fund Utilization Report')

@section('content')
    <div class="content-header">
        <h1>Fund Utilization Report</h1>
        <p>Manage fund utilization reports and project documents</p>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
            <button onclick="openExportModal('excel')" style="display: inline-block; padding: 10px 18px; background-color: #15803d; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(21, 128, 61, 0.2);">
                <i class="fas fa-file-excel" style="margin-right: 8px;"></i> Export Excel
            </button>
        </div>
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
            'program' => '',
            'fund_source' => '',
            'funding_year' => '',
            'province' => '',
            'city' => '',
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

    <div style="background: white; padding: 16px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px; border: 1px solid #e5e7eb;">
        <form id="fund-utilization-filters" method="GET" action="{{ route('fund-utilization.index') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
            <div style="position: relative; flex: 2 1 220px; min-width: 200px;">
                <i class="fas fa-search" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px; pointer-events: none;"></i>
                <input
                    type="text"
                    name="search"
                    value="{{ $activeFilters['search'] }}"
                    placeholder="Search project code, title, province..."
                    style="width: 100%; height: 42px; padding: 0 12px 0 34px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151; box-sizing: border-box; outline: none;"
                >
            </div>
            <select name="program" style="flex: 1 1 140px; min-width: 140px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Programs</option>
                @foreach(($filterOptions['programs'] ?? []) as $option)
                    <option value="{{ $option }}" {{ ($activeFilters['program'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <select name="fund_source" style="flex: 1 1 140px; min-width: 140px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Fund Sources</option>
                @foreach(($filterOptions['fund_sources'] ?? []) as $option)
                    <option value="{{ $option }}" {{ ($activeFilters['fund_source'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <select name="funding_year" style="flex: 1 1 120px; min-width: 120px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Years</option>
                @foreach(($filterOptions['funding_years'] ?? []) as $option)
                    <option value="{{ $option }}" {{ (string) ($activeFilters['funding_year'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <select id="fund-utilization-filter-province" name="province" style="flex: 1 1 140px; min-width: 140px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Provinces</option>
                @foreach(($filterOptions['provinces'] ?? []) as $option)
                    <option value="{{ $option }}" {{ ($activeFilters['province'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <select id="fund-utilization-filter-city" name="city" data-selected-city="{{ $activeFilters['city'] }}" style="flex: 1 1 170px; min-width: 170px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Cities / Municipalities</option>
                @foreach($cityOptions as $city)
                    <option value="{{ $city }}" {{ ($activeFilters['city'] ?? '') === $city ? 'selected' : '' }}>{{ $city }}</option>
                @endforeach
            </select>
            <button type="submit" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #2563eb; color: white; border: 1px solid #2563eb; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; hover: background-color: #e5e7eb; transition: background-color 0.2s;">
                <i class="fas fa-filter"></i> Apply
            </button>
            <a href="{{ route('fund-utilization.index', ['per_page' => $perPage ?? 10]) }}" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #6b7280; color: white; border: 1px solid #6b7280; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; text-decoration: none; transition: background-color 0.2s;">
                Reset
            </a>
        </form>
    </div>

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
                                            <li style="margin: 0; list-style: disc;">{{ $barangay }}</li>
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
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="program" value="{{ $filters['program'] ?? '' }}">
                        <input type="hidden" name="fund_source" value="{{ $filters['fund_source'] ?? '' }}">
                        <input type="hidden" name="funding_year" value="{{ $filters['funding_year'] ?? '' }}">
                        <input type="hidden" name="province" value="{{ $filters['province'] ?? '' }}">
                        <input type="hidden" name="city" value="{{ $filters['city'] ?? '' }}">
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

        document.getElementById('exportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const quarter = document.getElementById('quarter').value;
            if (!quarter) {
                alert('Please select a quarter.');
                return;
            }

            // Build the export URL with selected format and quarter
            const baseUrl = '{{ route("fund-utilization.export") }}';
            const url = new URL(baseUrl);
            url.searchParams.set('format', selectedFormat);
            url.searchParams.set('quarter', quarter);

            // Add current query parameters (search, fund_source, etc.)
            const currentUrl = new URL(window.location.href);
            for (let [key, value] of currentUrl.searchParams) {
                if (key !== 'format' && key !== 'quarter') {
                    url.searchParams.set(key, value);
                }
            }

            // Redirect to the export URL
            if (window.AppUI && typeof window.AppUI.suppressPageLoader === 'function') {
                window.AppUI.suppressPageLoader();
            }

            window.location.href = url.toString();
        });

        (function () {
            const provinceSelect = document.getElementById('fund-utilization-filter-province');
            const citySelect = document.getElementById('fund-utilization-filter-city');
            const locationData = @json($provinceMunicipalities ?? []);

            if (!provinceSelect || !citySelect) {
                return;
            }

            const getAllCities = () => Object.keys(locationData).reduce((all, province) => {
                return all.concat(locationData[province] || []);
            }, []);

            const rebuildCityOptions = (selectedProvince, preserveSelection = true) => {
                const selectedCity = preserveSelection ? (citySelect.value || citySelect.dataset.selectedCity || '') : '';
                const cityList = selectedProvince && Object.prototype.hasOwnProperty.call(locationData, selectedProvince)
                    ? (locationData[selectedProvince] || [])
                    : getAllCities();

                const uniqueCities = Array.from(new Set(cityList
                    .map((city) => (city || '').trim())
                    .filter(Boolean)))
                    .sort((left, right) => left.localeCompare(right));

                citySelect.innerHTML = '<option value="">All Cities / Municipalities</option>';

                uniqueCities.forEach((city) => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });

                if (selectedCity && uniqueCities.includes(selectedCity)) {
                    citySelect.value = selectedCity;
                }
            };

            provinceSelect.addEventListener('change', function () {
                rebuildCityOptions(this.value, false);
            });

            rebuildCityOptions(provinceSelect.value, true);
        })();
    </script>

    <style>
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
        select:focus {
            outline: none;
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.12);
            background-color: white;
        }

        #fund-utilization-table tbody td:last-child a:hover {
            background-color: #001f59 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        @media (max-width: 1100px) {
            #fund-utilization-filters {
                grid-template-columns: 1fr 1fr !important;
            }
        }

        @media (max-width: 768px) {
            .report-table-card {
                padding: 16px !important;
            }

            #fund-utilization-filters {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection
