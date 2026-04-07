@extends('layouts.dashboard')

@section('title', 'Pre-Implementation Documents')
@section('page-title', 'Pre-Implementation Documents')

@section('content')
    <div class="content-header">
        <h1>Pre-Implementation Documents</h1>
        <p>View accessible SubayBayan LFP projects from 2024 onward and open each project profile to manage pre-implementation records.</p>
    </div>

    <div style="background: white; padding: 16px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px; border: 1px solid #e5e7eb;">
        <form method="GET" action="{{ route('pre-implementation-documents.index') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
            <div style="position: relative; flex: 2 1 220px; min-width: 200px;">
                <i class="fas fa-search" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px; pointer-events: none;"></i>
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search project code, title, location..."
                    style="width: 100%; height: 42px; padding: 0 12px 0 34px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151; box-sizing: border-box; outline: none;"
                >
            </div>
            <select name="province" style="flex: 1 1 150px; min-width: 140px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Provinces</option>
                @foreach(($filterOptions['provinces'] ?? []) as $option)
                    <option value="{{ $option }}" {{ ($filters['province'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <select name="funding_year" style="flex: 1 1 120px; min-width: 110px; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                <option value="">All Years</option>
                @foreach(($filterOptions['funding_years'] ?? []) as $option)
                    <option value="{{ $option }}" {{ (string) ($filters['funding_year'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <button type="submit" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #2563eb; color: white; border: 1px solid #2563eb; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; transition: background-color 0.2s;">
                <i class="fas fa-filter"></i> Apply
            </button>
            <a href="{{ route('pre-implementation-documents.index') }}" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #6b7280; color: white; border: 1px solid #6b7280; border-radius: 8px; font-size: 13px; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; text-decoration: none; transition: background-color 0.2s;">
                Reset
            </a>
        </form>
    </div>

    <div style="background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb; overflow: hidden;">
        <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; min-width: 860px;">
            <thead>
                <tr style="background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                    <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Project Code</th>
                    <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Project Title</th>
                    <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Fund Source</th>
                    <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Year</th>
                    <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">City / Municipality</th>
                    <th style="padding: 14px 16px; text-align: left; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Province</th>
                    <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                    <th style="padding: 14px 16px; text-align: center; color: #ffffff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $index => $project)
                    @php
                        $status = strtolower($project->status ?? '');
                        if (str_contains($status, 'complet') || str_contains($status, 'done') || str_contains($status, 'approved')) {
                            $badgeBg = '#d1fae5'; $badgeColor = '#065f46'; $dotColor = '#10b981';
                        } elseif (str_contains($status, 'ongoing') || str_contains($status, 'progress') || str_contains($status, 'active')) {
                            $badgeBg = '#dbeafe'; $badgeColor = '#1e40af'; $dotColor = '#3b82f6';
                        } elseif (str_contains($status, 'pending') || str_contains($status, 'review')) {
                            $badgeBg = '#fef3c7'; $badgeColor = '#92400e'; $dotColor = '#f59e0b';
                        } elseif (str_contains($status, 'cancel') || str_contains($status, 'reject') || str_contains($status, 'suspend')) {
                            $badgeBg = '#fee2e2'; $badgeColor = '#991b1b'; $dotColor = '#ef4444';
                        } else {
                            $badgeBg = '#f3f4f6'; $badgeColor = '#4b5563'; $dotColor = '#9ca3af';
                        }
                        $rowBg = $index % 2 === 0 ? '#ffffff' : '#f9fafb';
                    @endphp
                    <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s;"
                        onmouseover="this.style.backgroundColor='#eff6ff'" onmouseout="this.style.backgroundColor='{{ $rowBg }}'">
                        <td style="padding: 14px 16px; font-size: 13px; font-weight: 700; color: #002C76; white-space: nowrap;">{{ $project->project_code }}</td>
                        <td style="padding: 14px 16px; font-size: 13px; color: #111827; max-width: 260px;">
                            <div style="white-space: normal; line-height: 1.4;">{{ $project->project_title ?: '-' }}</div>
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
                            <a href="{{ route('pre-implementation-documents.show', $project->project_code) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; background-color: #002C76; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; transition: background-color 0.2s;"
                               onmouseover="this.style.backgroundColor='#003d9e'" onmouseout="this.style.backgroundColor='#002C76'">
                                <i class="fas fa-folder-open"></i> Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 60px 20px; text-align: center; color: #9ca3af;">
                            <i class="fas fa-inbox" style="font-size: 36px; margin-bottom: 12px; display: block; color: #d1d5db;"></i>
                            <div style="font-size: 14px; font-weight: 600; color: #6b7280;">No SubayBayan LFP projects found from 2024 onward.</div>
                            <div style="font-size: 12px; margin-top: 4px;">Try adjusting your filters.</div>
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
                    <form method="GET" action="{{ route('pre-implementation-documents.index') }}" style="display: inline-flex; align-items: center;">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="province" value="{{ $filters['province'] ?? '' }}">
                        <input type="hidden" name="funding_year" value="{{ $filters['funding_year'] ?? '' }}">
                        <select id="per-page" name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                            @foreach([10, 15, 25, 50] as $option)
                                <option value="{{ $option }}" {{ (int) ($perPage ?? 10) === $option ? 'selected' : '' }}>{{ $option }}</option>
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
