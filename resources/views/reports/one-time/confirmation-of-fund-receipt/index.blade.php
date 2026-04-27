@extends('layouts.dashboard')

@section('title', 'Confirmation of Fund Receipt')
@section('page-title', 'Confirmation of Fund Receipt')

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
    <h1>Confirmation of Fund Receipt</h1>
    <p>Adopt the uploaded NADAI records from NADAI Management and track LGU acceptance plus Confirmation of Fund Receipt attachments per LGU and PLGU.</p>
</div>

@if (session('success'))
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 9999px; padding: 8px 14px; font-size: 12px; font-weight: 600; color: #374151;">
        Provinces: {{ $totalProvinces }}
    </div>
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 9999px; padding: 8px 14px; font-size: 12px; font-weight: 600; color: #374151;">
        Offices: {{ $totalOffices }}
    </div>
</div>

<div style="background: #ffffff; padding: 16px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px; border: 1px solid #e5e7eb;">
    <form method="GET" action="{{ route('reports.one-time.confirmation-of-fund-receipt.index') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
        <div style="flex: 1 1 180px; min-width: 180px;">
            <label for="cfr-filter-province" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Province</label>
            <select id="cfr-filter-province" name="province" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Provinces</option>
                @foreach(($filterOptions['provinces'] ?? []) as $option)
                    <option value="{{ $option }}" @selected((string) $activeFilters['province'] === (string) $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1 1 220px; min-width: 220px;">
            <label for="cfr-filter-city" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">City / Municipality</label>
            <select id="cfr-filter-city" name="city" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Cities / Municipalities</option>
                @foreach($cityOptions as $city)
                    <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #2563eb; color: white; border: 1px solid #2563eb; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fas fa-filter"></i> Apply
        </button>
        <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.index', ['per_page' => $perPage ?? 15]) }}" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #6b7280; color: white; border: 1px solid #6b7280; border-radius: 8px; font-size: 13px; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; text-decoration: none;">
            Clear
        </a>
    </form>
</div>

<div class="report-table-card" style="background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <div class="report-table-scroll">
        <table style="width: 100%; border-collapse: collapse; min-width: 1260px;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Province</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">City / Municipality / PLGU</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Total NADAI Submissions</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Latest NADAI Date</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Last Uploaded</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Acceptance Status</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Latest Submission of CFR</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($officeRows as $row)
                    @php
                        $latestDocument = $latestDocumentsByOffice->get($row['city_municipality']);
                        $submissionCount = (int) ($submissionCountsByOffice[$row['city_municipality']] ?? 0);
                        $accepted = (bool) $latestDocument?->confirmation_accepted_at;
                        $latestConfirmationDocument = $latestConfirmationDocumentsByOffice->get($row['city_municipality']);
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; color: #111827; font-size: 13px;">{{ $row['province'] }}</td>
                        <td style="padding: 12px; color: #111827; font-size: 13px;">{{ $row['city_municipality'] }}</td>
                        <td style="padding: 12px; color: #111827; font-size: 13px; text-align: center;">{{ $submissionCount }}</td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">
                            {{ $latestDocument?->nadai_date ? $latestDocument->nadai_date->format('M d, Y') : '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">
                            {{ $latestDocument?->uploaded_at ? $latestDocument->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if ($latestDocument)
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; border: 1px solid {{ $accepted ? '#6ee7b7' : '#fcd34d' }}; background-color: {{ $accepted ? '#ecfdf5' : '#fffbeb' }}; color: {{ $accepted ? '#047857' : '#92400e' }}; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                    {{ $accepted ? 'Accepted by LGU' : 'Pending LGU Acceptance' }}
                                </span>
                            @else
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; border: 1px solid #d1d5db; background-color: #f3f4f6; color: #6b7280; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                    No Upload
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">
                            {{ $latestConfirmationDocument?->uploaded_at ? $latestConfirmationDocument->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.show', ['office' => $row['city_municipality']]) }}" style="display: inline-block; padding: 8px 14px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; text-decoration: none;">
                                <i class="fas fa-eye" style="margin-right: 4px;"></i> View Profile
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 40px; text-align: center; color: #6b7280;">
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
@endsection
