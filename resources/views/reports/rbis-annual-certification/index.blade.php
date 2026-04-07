@extends('layouts.dashboard')

@section('page-title', 'RBIS Annual Certification')

@section('content')
<div class="content-header">
    <h1>RBIS Annual Certification</h1>
    <p>Each city/municipality and PLGU has its own profile page for document uploads.</p>
</div>

@if (session('success'))
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@php
    $activeFilters = array_merge([
        'province' => '',
        'city' => '',
    ], $filters ?? []);
    $hasConfiguredDeadline = is_array($configuredDeadline ?? null);
    $hasConfiguredDeadlineIso = $hasConfiguredDeadline && !empty($configuredDeadline['deadline_iso']);
    $deadlineDateValue = $hasConfiguredDeadline ? ($configuredDeadline['display'] ?? '—') : 'No deadline set yet';
    $deadlineTimeValue = null;

    if ($hasConfiguredDeadlineIso) {
        try {
            $deadlineMoment = \Carbon\Carbon::parse($configuredDeadline['deadline_iso'])->setTimezone(config('app.timezone'));
            $deadlineDateValue = $deadlineMoment->format('M j, Y');
            $deadlineTimeValue = $deadlineMoment->format('h:i A');
        } catch (\Throwable) {
            $deadlineDateValue = $configuredDeadline['display'] ?? '—';
            $deadlineTimeValue = 'Unavailable';
        }
    }

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

<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 9999px; padding: 8px 14px; font-size: 12px; font-weight: 600; color: #374151;">
        Provinces: {{ $totalProvinces }}
    </div>
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 9999px; padding: 8px 14px; font-size: 12px; font-weight: 600; color: #374151;">
        Offices: {{ $totalOffices }}
    </div>
</div>

<div style="margin-bottom: 20px; padding: 18px 22px; border-radius: 14px; border: 1px solid {{ $hasConfiguredDeadline ? '#cfdff8' : '#f3d38e' }}; background: {{ $hasConfiguredDeadline ? 'linear-gradient(140deg, #f4f8ff 0%, #ffffff 60%, #f8fbff 100%)' : 'linear-gradient(140deg, #fffcf2 0%, #fff9e6 100%)' }}; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);">
    <div style="display: flex; justify-content: space-between; align-items: stretch; gap: 14px; flex-wrap: wrap;">
        <div style="flex: 1 1 280px; padding: 16px 18px; min-width: 240px; border-radius: 12px; border: 1px solid {{ $hasConfiguredDeadline ? '#d7e3fa' : '#f1d7a3' }}; background: #ffffff; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);">
            <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: {{ $hasConfiguredDeadline ? '#1e3a8a' : '#92400e' }}; margin-bottom: 12px;">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                <span>Deadline for CY {{ $reportingYear }}</span>
            </div>
            @if ($hasConfiguredDeadlineIso)
                <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280; margin-bottom: 5px;">
                    <i class="fas fa-calendar-day" aria-hidden="true"></i>
                    <span>Date</span>
                </div>
                <div style="font-size: 26px; font-weight: 700; color: #0f172a; line-height: 1.08;">
                    {{ $deadlineDateValue }}
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280; margin-bottom: 5px;">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>Time</span>
                </div>
                <div style="font-size: 19px; font-weight: 700; color: #1e3a8a; line-height: 1.1;">
                    {{ $deadlineTimeValue }}
                </div>
            @else
                <div style="font-size: 14px; font-weight: 700; color: {{ $hasConfiguredDeadline ? '#111827' : '#92400e' }}; line-height: 1.2;">
                    {{ $deadlineDateValue }}
                </div>
                @if ($hasConfiguredDeadline)
                    <div style="display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 11px; color: #6b7280;">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span>Time not available.</span>
                    </div>
                @endif
            @endif
        </div>
        @if ($hasConfiguredDeadlineIso)
            <div id="rbis-deadline-countdown-card" style="flex: 0 1 300px; padding: 16px 18px; min-width: 240px; border-radius: 12px; border: 1px solid #d7e3fa; background: #ffffff; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9); transition: background-color 0.2s ease, border-color 0.2s ease;">
                <div id="rbis-deadline-countdown-title" style="display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #1e3a8a; margin-bottom: 12px; transition: color 0.2s ease;">
                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                    <span>Countdown</span>
                </div>
                <div id="rbis-deadline-countdown" data-deadline-iso="{{ $configuredDeadline['deadline_iso'] }}" style="font-size: 28px; font-weight: 700; color: #1e3a8a; line-height: 1.05; letter-spacing: 0.01em; transition: color 0.2s ease;">
                    Syncing...
                </div>
                <div data-pagasa-time data-rbis-deadline-source style="display: none;" aria-hidden="true"></div>
            </div>
        @elseif ($hasConfiguredDeadline)
            <div style="flex: 0 1 300px; padding: 16px 18px; min-width: 240px; border-radius: 12px; border: 1px solid #d7e3fa; background: #ffffff; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);">
                <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #1e3a8a; margin-bottom: 12px;">
                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                    <span>Countdown</span>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #92400e; line-height: 1.2;">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <span>Unavailable</span>
                </div>
                <div style="margin-top: 6px; font-size: 11px; color: #6b7280;">
                    Countdown needs a saved deadline date and time.
                </div>
            </div>
        @endif
    </div>
</div>

<div style="background: #ffffff; padding: 16px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px; border: 1px solid #e5e7eb;">
    <form id="rbis-filters-form" method="GET" action="{{ route('rbis-annual-certification.index') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
        <div style="flex: 0 0 120px; min-width: 120px;">
            <label for="rbis-reporting-year" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Year</label>
            <select id="rbis-reporting-year" name="year" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                @for ($yearOption = now()->year + 1; $yearOption >= now()->year - 5; $yearOption--)
                    <option value="{{ $yearOption }}" @selected($reportingYear === $yearOption)>{{ $yearOption }}</option>
                @endfor
            </select>
        </div>
        <div style="flex: 1 1 180px; min-width: 180px;">
            <label for="rbis-filter-province" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Province</label>
            <select id="rbis-filter-province" name="province" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Provinces</option>
                @foreach(($filterOptions['provinces'] ?? []) as $option)
                    <option value="{{ $option }}" @selected((string) $activeFilters['province'] === (string) $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1 1 220px; min-width: 220px;">
            <label for="rbis-filter-city" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">City / Municipality</label>
            <select id="rbis-filter-city" name="city" data-selected-city="{{ $activeFilters['city'] }}" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Cities / Municipalities</option>
                @foreach($cityOptions as $city)
                    <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #2563eb; color: white; border: 1px solid #2563eb; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fas fa-filter"></i> Apply
        </button>
        <a href="{{ route('rbis-annual-certification.index', ['year' => $reportingYear, 'per_page' => $perPage ?? 15]) }}" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #6b7280; color: white; border: 1px solid #6b7280; border-radius: 8px; font-size: 13px; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; text-decoration: none;">
            Clear
        </a>
    </form>
</div>

<div class="report-table-card" style="background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <div class="report-table-scroll">
        <table id="rbis-office-table" style="width: 100%; border-collapse: collapse; min-width: 1220px;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Province</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">City / Municipality / PLGU</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Uploaded Files (CY {{ $reportingYear }})</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Document Status</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Approval Status</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Actions</th>
                </tr>
            </thead>
            <tbody id="rbis-office-table-body">
                @forelse ($officeRows as $row)
                    @php
                        $latestDocument = $latestDocumentsByOffice->get($row['city_municipality']);
                        $hasFile = $latestDocument && $latestDocument->file_path;
                        $isReturned = $latestDocument && $latestDocument->status === 'returned';
                        $isApprovedRo = $latestDocument && $latestDocument->approved_at_dilg_ro;
                        $isPendingDilgRoValidation = $latestDocument
                            && $latestDocument->approved_at_dilg_po
                            && !$latestDocument->approved_at_dilg_ro
                            && !$isReturned;

                        $documentStatusLabel = 'Pending Upload';
                        $documentStatusTextColor = '#92400e';
                        $documentStatusBackgroundColor = '#fffbeb';
                        $documentStatusBorderColor = '#fcd34d';

                        $approvalStatusLabel = 'Awaiting Upload';
                        $approvalStatusTextColor = '#4b5563';
                        $approvalStatusBackgroundColor = '#f3f4f6';
                        $approvalStatusBorderColor = '#d1d5db';

                        if ($hasFile) {
                            $documentStatusLabel = 'Uploaded';
                            $documentStatusTextColor = '#1d4ed8';
                            $documentStatusBackgroundColor = '#eff6ff';
                            $documentStatusBorderColor = '#93c5fd';

                            $approvalStatusLabel = 'For DILG Provincial Office Validation';
                            $approvalStatusTextColor = '#1d4ed8';
                            $approvalStatusBackgroundColor = '#eff6ff';
                            $approvalStatusBorderColor = '#93c5fd';
                        }

                        if ($isPendingDilgRoValidation) {
                            $approvalStatusLabel = 'For DILG Regional Office Validation';
                            $approvalStatusTextColor = '#1d4ed8';
                            $approvalStatusBackgroundColor = '#dbeafe';
                            $approvalStatusBorderColor = '#60a5fa';
                        }

                        if ($isApprovedRo) {
                            $approvalStatusLabel = 'Approved';
                            $approvalStatusTextColor = '#047857';
                            $approvalStatusBackgroundColor = '#ecfdf5';
                            $approvalStatusBorderColor = '#6ee7b7';
                        }

                        if ($isReturned) {
                            $approvalStatusLabel = 'Returned';
                            $approvalStatusTextColor = '#b91c1c';
                            $approvalStatusBackgroundColor = '#fef2f2';
                            $approvalStatusBorderColor = '#fca5a5';
                        }
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; color: #111827; font-size: 13px;">{{ $row['province'] }}</td>
                        <td style="padding: 12px; color: #111827; font-size: 13px;">{{ $row['city_municipality'] }}</td>
                        <td style="padding: 12px; color: #111827; font-size: 13px; text-align: center;">
                            {{ (int) ($uploadCountsByOffice[$row['city_municipality']] ?? 0) }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; border: 1px solid {{ $documentStatusBorderColor }}; background-color: {{ $documentStatusBackgroundColor }}; color: {{ $documentStatusTextColor }}; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                {{ $documentStatusLabel }}
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="display: inline-block; max-width: 220px; padding: 4px 10px; border-radius: 999px; border: 1px solid {{ $approvalStatusBorderColor }}; background-color: {{ $approvalStatusBackgroundColor }}; color: {{ $approvalStatusTextColor }}; font-size: 11px; font-weight: 700; white-space: normal; line-height: 1.25; text-align: center;">
                                {{ $approvalStatusLabel }}
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('rbis-annual-certification.edit', ['office' => $row['city_municipality'], 'year' => $reportingYear]) }}" style="display: inline-block; padding: 8px 14px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; text-decoration: none;">
                                <i class="fas fa-eye" style="margin-right: 4px;"></i> View Profile
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #6b7280;">
                            <i class="fas fa-inbox" style="font-size: 30px; margin-bottom: 8px; display: block;"></i>
                            No offices found.
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

<style>
    .report-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #rbis-office-table tbody tr:hover {
        background-color: #eef4ff !important;
    }

    @media (max-width: 768px) {
        .report-table-card {
            padding: 16px !important;
        }

        .table-pagination-row {
            flex-direction: column;
            align-items: flex-start !important;
        }
    }
</style>

    <script>
    (function () {
        const provinceSelect = document.getElementById('rbis-filter-province');
        const citySelect = document.getElementById('rbis-filter-city');
        const locationData = @json($provinceMunicipalities ?? []);

        if (!provinceSelect || !citySelect) return;

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

    (function () {
        const countdownEl = document.getElementById('rbis-deadline-countdown');
        const countdownCardEl = document.getElementById('rbis-deadline-countdown-card');
        const countdownTitleEl = document.getElementById('rbis-deadline-countdown-title');
        const pagasaTimeEl = document.querySelector('[data-rbis-deadline-source]');

        if (!countdownEl || !pagasaTimeEl) return;

        const deadlineIso = countdownEl.dataset.deadlineIso || '';
        const deadlineMs = Date.parse(deadlineIso);

        const getCountdownTheme = (remainingMs) => {
            if (remainingMs <= 0) {
                return {
                    cardBorder: '#fca5a5',
                    cardBackground: '#fef2f2',
                    titleColor: '#b91c1c',
                    countdownColor: '#b91c1c',
                };
            }

            if (remainingMs <= 24 * 60 * 60 * 1000) {
                return {
                    cardBorder: '#fca5a5',
                    cardBackground: '#fff1f2',
                    titleColor: '#b91c1c',
                    countdownColor: '#b91c1c',
                };
            }

            if (remainingMs <= 3 * 24 * 60 * 60 * 1000) {
                return {
                    cardBorder: '#fdba74',
                    cardBackground: '#fff7ed',
                    titleColor: '#c2410c',
                    countdownColor: '#c2410c',
                };
            }

            if (remainingMs <= 7 * 24 * 60 * 60 * 1000) {
                return {
                    cardBorder: '#fcd34d',
                    cardBackground: '#fffbeb',
                    titleColor: '#b45309',
                    countdownColor: '#b45309',
                };
            }

            return {
                cardBorder: '#d7e3fa',
                cardBackground: '#ffffff',
                titleColor: '#1e3a8a',
                countdownColor: '#1e3a8a',
            };
        };

        const applyCountdownTheme = (theme) => {
            if (countdownCardEl) {
                countdownCardEl.style.borderColor = theme.cardBorder;
                countdownCardEl.style.background = theme.cardBackground;
            }

            if (countdownTitleEl) {
                countdownTitleEl.style.color = theme.titleColor;
            }

            countdownEl.style.color = theme.countdownColor;
        };

        const unavailableTheme = {
            cardBorder: '#fcd34d',
            cardBackground: '#fffbeb',
            titleColor: '#92400e',
            countdownColor: '#92400e',
        };

        const setCountdownState = (label, color) => {
            countdownEl.textContent = label;
            countdownEl.style.color = color;
        };

        const pad = (value) => String(value).padStart(2, '0');

        const formatRemaining = (remainingMs) => {
            const totalSeconds = Math.max(0, Math.floor(remainingMs / 1000));
            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            return `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
        };

        if (Number.isNaN(deadlineMs)) {
            applyCountdownTheme(unavailableTheme);
            setCountdownState('Unavailable', '#92400e');
            return;
        }

        const renderCountdown = () => {
            const serverIso = pagasaTimeEl.dataset.pagasaIso || '';
            const serverMs = Date.parse(serverIso);

            if (!serverIso || Number.isNaN(serverMs)) {
                applyCountdownTheme(getCountdownTheme(8 * 24 * 60 * 60 * 1000));
                setCountdownState('Syncing...', '#1e3a8a');
                return;
            }

            const remainingMs = deadlineMs - serverMs;
            applyCountdownTheme(getCountdownTheme(remainingMs));

            if (remainingMs <= 0) {
                setCountdownState('Deadline reached', '#b91c1c');
                return;
            }

            setCountdownState(formatRemaining(remainingMs), '#1e3a8a');
        };

        renderCountdown();
        const intervalId = window.setInterval(renderCountdown, 1000);
        window.addEventListener('beforeunload', function () {
            window.clearInterval(intervalId);
        }, { once: true });
    })();
</script>
@endsection
