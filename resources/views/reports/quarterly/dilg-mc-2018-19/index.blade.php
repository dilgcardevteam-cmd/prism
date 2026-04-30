@extends('layouts.dashboard')

@section('title', 'DILG MC No. 2018-19')
@section('page-title', 'DILG MC No. 2018-19')

@section('content')
    @php
        $activeFilters = array_merge([
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
            ->map(fn ($city) => trim((string) $city))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    @endphp

    <div class="content-header">
        <h1>DILG MC No. 2018-19</h1>
        <p>Quarterly uploading workspace for monitoring of roads and other similar public works.</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:20px;border:1px solid #dbe3ee;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);margin-bottom:20px;">
                        <div style="width:48px;height:48px;border-radius:14px;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;flex:0 0 48px;">
                            <i class="fas fa-road" style="font-size:20px;"></i>
                        </div>
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:6px;">Quarterly Upload Monitoring</div>
                            <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.7;">
                                Open an LGU workspace below to upload or review the quarterly PDF submission required under
                                <strong>DILG MC No. 2018-19</strong>.
                            </p>
                        </div>
                    </div>

                    <div style="background:#ffffff;padding:16px 20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px;border:1px solid #e5e7eb;">
                        <form method="GET" action="{{ route('reports.quarterly.dilg-mc-2018-19') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                            <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
                            <div style="flex:0 0 120px;min-width:120px;">
                                <label style="display:block;margin-bottom:6px;color:#374151;font-size:12px;font-weight:600;">Year</label>
                                <select name="year" style="width:100%;height:42px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#f9fafb;color:#374151;">
                                    @for ($yearOption = now()->year + 1; $yearOption >= now()->year - 5; $yearOption--)
                                        <option value="{{ $yearOption }}" @selected($reportingYear === $yearOption)>{{ $yearOption }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div style="flex:1 1 180px;min-width:180px;">
                                <label style="display:block;margin-bottom:6px;color:#374151;font-size:12px;font-weight:600;">Province</label>
                                <select id="dilg-mc-2018-19-province" name="province" style="width:100%;height:42px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#f9fafb;color:#374151;">
                                    <option value="">All Provinces</option>
                                    @foreach (($filterOptions['provinces'] ?? []) as $option)
                                        <option value="{{ $option }}" @selected((string) $activeFilters['province'] === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex:1 1 220px;min-width:220px;">
                                <label style="display:block;margin-bottom:6px;color:#374151;font-size:12px;font-weight:600;">City / Municipality</label>
                                <select id="dilg-mc-2018-19-city" name="city" data-selected-city="{{ $activeFilters['city'] }}" style="width:100%;height:42px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#f9fafb;color:#374151;">
                                    <option value="">All Cities / Municipalities</option>
                                    @foreach ($cityOptions as $city)
                                        <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" style="height:42px;padding:0 18px;background:#2563eb;color:#fff;border:1px solid #2563eb;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                                Apply
                            </button>
                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19', ['year' => $reportingYear, 'per_page' => $perPage ?? 15]) }}"
                                style="height:42px;padding:0 18px;background:#6b7280;color:#fff;border:1px solid #6b7280;border-radius:8px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;text-decoration:none;">
                                Clear
                            </a>
                        </form>
                    </div>

                    <div class="report-table-shell" style="background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;min-width:1040px;">
                            <thead>
                                <tr style="background:#f3f4f6;border-bottom:2px solid #e5e7eb;">
                                    <th style="padding:12px;text-align:left;color:#374151;font-weight:600;font-size:14px;">Province</th>
                                    <th style="padding:12px;text-align:left;color:#374151;font-weight:600;font-size:14px;">City / Municipality</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Q1</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Q2</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Q3</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Q4</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Latest Upload</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Latest Status</th>
                                    <th style="padding:12px;text-align:center;color:#374151;font-weight:600;font-size:14px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($officeRows as $row)
                                    @php
                                        $officeDocs = $documentsByOffice[$row['city_municipality']] ?? [];
                                        $latestDocument = collect($officeDocs)
                                            ->flatten(1)
                                            ->filter()
                                            ->sortByDesc(fn ($document) => optional($document->uploaded_at)->getTimestamp() ?? 0)
                                            ->first();
                                        $statusIcon = function ($document) {
                                            if (!$document) {
                                                return '<i class="fas fa-minus-circle" style="color:#d1d5db;" title="No upload"></i>';
                                            }

                                            $status = strtolower(trim((string) ($document->status ?? 'pending')));

                                            return match ($status) {
                                                'approved' => '<i class="fas fa-check-circle" style="color:#10b981;" title="Approved"></i>',
                                                'returned' => '<i class="fas fa-undo-alt" style="color:#dc2626;" title="Returned"></i>',
                                                'pending_ro' => '<i class="fas fa-clock" style="color:#2563eb;" title="For DILG Regional Office"></i>',
                                                default => '<i class="fas fa-clock" style="color:#f59e0b;" title="For DILG Provincial Office"></i>',
                                            };
                                        };
                                        $latestStatusLabel = 'No upload';
                                        $latestStatusBackground = '#f3f4f6';
                                        $latestStatusColor = '#4b5563';
                                        $latestStatusBorder = '#d1d5db';

                                        if ($latestDocument) {
                                            $latestStatus = strtolower(trim((string) ($latestDocument->status ?? 'pending')));
                                            if ($latestStatus === 'approved') {
                                                $latestStatusLabel = 'Approved';
                                                $latestStatusBackground = '#ecfdf5';
                                                $latestStatusColor = '#047857';
                                                $latestStatusBorder = '#6ee7b7';
                                            } elseif ($latestStatus === 'returned') {
                                                $latestStatusLabel = 'Returned';
                                                $latestStatusBackground = '#fef2f2';
                                                $latestStatusColor = '#b91c1c';
                                                $latestStatusBorder = '#fca5a5';
                                            } elseif ($latestStatus === 'pending_ro') {
                                                $latestStatusLabel = 'For DILG Regional Office';
                                                $latestStatusBackground = '#dbeafe';
                                                $latestStatusColor = '#1d4ed8';
                                                $latestStatusBorder = '#93c5fd';
                                            } else {
                                                $latestStatusLabel = 'For DILG Provincial Office';
                                                $latestStatusBackground = '#fef3c7';
                                                $latestStatusColor = '#92400e';
                                                $latestStatusBorder = '#fcd34d';
                                            }
                                        }
                                    @endphp
                                    <tr style="border-bottom:1px solid #e5e7eb;">
                                        <td style="padding:12px;color:#111827;font-size:14px;">{{ $row['province'] }}</td>
                                        <td style="padding:12px;color:#111827;font-size:14px;">{{ $row['city_municipality'] }}</td>
                                        <td style="padding:12px;text-align:center;">{!! $statusIcon(!empty($officeDocs['Q1'] ?? []) ? ($officeDocs['Q1'][0] ?? null) : null) !!}</td>
                                        <td style="padding:12px;text-align:center;">{!! $statusIcon(!empty($officeDocs['Q2'] ?? []) ? ($officeDocs['Q2'][0] ?? null) : null) !!}</td>
                                        <td style="padding:12px;text-align:center;">{!! $statusIcon(!empty($officeDocs['Q3'] ?? []) ? ($officeDocs['Q3'][0] ?? null) : null) !!}</td>
                                        <td style="padding:12px;text-align:center;">{!! $statusIcon(!empty($officeDocs['Q4'] ?? []) ? ($officeDocs['Q4'][0] ?? null) : null) !!}</td>
                                        <td style="padding:12px;text-align:center;color:#111827;font-size:12px;white-space:nowrap;">
                                            {{ $latestDocument && $latestDocument->uploaded_at ? $latestDocument->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                                        </td>
                                        <td style="padding:12px;text-align:center;">
                                            <span style="display:inline-block;max-width:220px;padding:4px 10px;border-radius:999px;border:1px solid {{ $latestStatusBorder }};background:{{ $latestStatusBackground }};color:{{ $latestStatusColor }};font-size:11px;font-weight:700;line-height:1.25;">
                                                {{ $latestStatusLabel }}
                                            </span>
                                        </td>
                                        <td style="padding:12px;text-align:center;">
                                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.show', ['office' => $row['city_municipality'], 'year' => $reportingYear]) }}"
                                                style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#002c76;color:#fff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;">
                                                <i class="fas fa-eye"></i>
                                                <span>Open Workspace</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" style="padding:40px;text-align:center;color:#6b7280;">No records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($officeRows->count() > 0)
                        <div class="table-pagination-row" style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <div style="font-size:12px;color:#6b7280;">
                                    Page {{ $officeRows->currentPage() }} of {{ $officeRows->lastPage() }} ·
                                    Showing {{ $officeRows->firstItem() ?? 0 }}-{{ $officeRows->lastItem() ?? 0 }} of {{ $officeRows->total() }}
                                </div>
                                <form method="GET" style="display:inline-flex;align-items:center;">
                                    @foreach (request()->except(['page', 'per_page']) as $queryKey => $queryValue)
                                        @if (is_array($queryValue))
                                            @foreach ($queryValue as $nestedValue)
                                                <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                        @endif
                                    @endforeach
                                    <select name="per_page" onchange="this.form.submit()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;">
                                        @foreach ([10, 15, 25, 50] as $option)
                                            <option value="{{ $option }}" @selected((int) ($perPage ?? 15) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                                @if ($officeRows->onFirstPage())
                                    <span style="padding:8px 12px;background:#e5e7eb;color:#9ca3af;border-radius:6px;font-size:12px;font-weight:600;">Back</span>
                                @else
                                    <a href="{{ $officeRows->previousPageUrl() }}" style="padding:8px 12px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">Back</a>
                                @endif

                                @if ($officeRows->hasMorePages())
                                    <a href="{{ $officeRows->nextPageUrl() }}" style="padding:8px 12px;background:#002c76;color:#fff;border:1px solid #002c76;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">Next</a>
                                @else
                                    <span style="padding:8px 12px;background:#e5e7eb;color:#9ca3af;border-radius:6px;font-size:12px;font-weight:600;">Next</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const provinceSelect = document.getElementById('dilg-mc-2018-19-province');
            const citySelect = document.getElementById('dilg-mc-2018-19-city');
            const locationData = @json($provinceMunicipalities ?? []);

            if (!provinceSelect || !citySelect) {
                return;
            }

            const getAllCities = () => Object.keys(locationData).reduce((all, province) => {
                return all.concat(locationData[province] || []);
            }, []);

            const populateCities = (province, selectedCity) => {
                const cities = province && locationData[province] ? locationData[province] : getAllCities();
                const uniqueCities = Array.from(new Set(cities.map(city => String(city).trim()).filter(Boolean))).sort((left, right) => left.localeCompare(right));
                citySelect.innerHTML = '<option value=\"\">All Cities / Municipalities</option>';

                uniqueCities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    option.selected = selectedCity === city;
                    citySelect.appendChild(option);
                });
            };

            populateCities(provinceSelect.value, citySelect.dataset.selectedCity || '');
            provinceSelect.addEventListener('change', function () {
                populateCities(this.value, '');
            });
        })();
    </script>
@endsection
