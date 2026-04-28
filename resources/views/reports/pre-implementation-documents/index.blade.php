@extends('layouts.dashboard')

@section('title', $pageConfig['title'])
@section('page-title', $pageConfig['title'])

@section('content')
    <div class="content-header">
        <h1>{{ $pageConfig['index_heading'] }}</h1>
        <p>{{ $pageConfig['index_description'] }}</p>
    </div>

    <style>
        .project-filter-form.collapsed .project-filter-body {
            max-height: 0 !important;
            opacity: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .project-filter-form.collapsed .project-filter-chevron {
            transform: rotate(180deg);
        }

        .project-filter-body {
            overflow: hidden;
            transition: max-height 0.25s ease, opacity 0.2s ease;
        }

        .project-filter-chevron {
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        @media (max-width: 1100px) {
            .dashboard-filter-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr)) !important;
            }
        }

        @media (max-width: 700px) {
            .dashboard-filter-grid {
                grid-template-columns: 1fr !important;
            }

            .dashboard-filter-reset {
                grid-column: 1 / -1 !important;
                justify-content: stretch !important;
            }

            .dashboard-filter-reset a {
                width: 100%;
            }
        }
    </style>

    <form method="GET" action="{{ route($routeConfig['index'], $scopeQuery) }}" class="dashboard-card project-filter-form dashboard-main-layout-filter" style="background: #ffffff; padding: 18px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
        <button type="button" class="project-filter-toggle" onclick="toggleProjectFilter(this)" aria-expanded="true" aria-controls="project-filter-body" style="width: 100%; border: 0; background: transparent; color: #002c76; font-size: 13px; font-weight: 800; letter-spacing: 0.04em; margin: 0 0 14px; padding: 0; display: flex; align-items: center; gap: 10px; text-align: left; cursor: pointer;">
            <span style="font-size: 20px;">&#128269;</span>
            <span>PROJECT FILTER</span>
            <span class="project-filter-chevron">
                <i class="fas fa-chevron-up"></i>
            </span>
        </button>

        <div id="project-filter-body" class="project-filter-body">
            <div class="dashboard-filter-grid" style="display: grid; grid-template-columns: repeat(3, minmax(220px, 1fr)); gap: 16px 22px; align-items: end;">
                <div>
                    <label for="province" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Province</label>
                    <select id="province" name="province" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['provinces'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['province'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="city_municipality" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">City/Municipality</label>
                    <select id="city_municipality" name="city_municipality" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['cities'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['city_municipality'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="barangay" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Barangay</label>
                    <select id="barangay" name="barangay" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['barangays'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['barangay'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="program" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Program</label>
                    <select id="program" name="program" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['programs'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['program'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="funding_year" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Funding Year</label>
                    <select id="funding_year" name="funding_year" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['funding_years'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['funding_year'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="project_type" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Project Type</label>
                    <select id="project_type" name="project_type" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['project_types'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['project_type'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="project_status" style="display: block; color: #1f2937; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Project Status</label>
                    <select id="project_status" name="project_status" onchange="this.form.submit()" style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #ffffff; color: #111827; padding: 0 10px;">
                        <option value="">All</option>
                        @foreach(($filterOptions['project_statuses'] ?? []) as $option)
                            <option value="{{ $option }}" @selected((string) ($filters['project_status'] ?? '') === (string) $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dashboard-filter-reset" style="grid-column: span 2; display: flex; align-items: end; justify-content: flex-end;">
                    <a href="{{ route($routeConfig['index'], $scopeQuery) }}" style="height: 38px; min-width: 170px; border-radius: 8px; background-color: #3b82f6; color: #ffffff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; padding: 0 18px;">
                        Reset Filter
                    </a>
                </div>
            </div>
        </div>
    </form>

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
                            <a href="{{ route($routeConfig['show'], array_merge(['projectCode' => $project->project_code], $scopeQuery)) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; background-color: #002C76; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; transition: background-color 0.2s;"
                               onmouseover="this.style.backgroundColor='#003d9e'" onmouseout="this.style.backgroundColor='#002C76'">
                                <i class="fas fa-folder-open"></i> Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 60px 20px; text-align: center; color: #9ca3af;">
                            <i class="fas fa-inbox" style="font-size: 36px; margin-bottom: 12px; display: block; color: #d1d5db;"></i>
                            <div style="font-size: 14px; font-weight: 600; color: #6b7280;">{{ $pageConfig['empty_state'] }}</div>
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
                    <form method="GET" action="{{ route($routeConfig['index'], $scopeQuery) }}" style="display: inline-flex; align-items: center;">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="province" value="{{ $filters['province'] ?? '' }}">
                        <input type="hidden" name="city_municipality" value="{{ $filters['city_municipality'] ?? '' }}">
                        <input type="hidden" name="barangay" value="{{ $filters['barangay'] ?? '' }}">
                        <input type="hidden" name="program" value="{{ $filters['program'] ?? '' }}">
                        <input type="hidden" name="funding_year" value="{{ $filters['funding_year'] ?? '' }}">
                        <input type="hidden" name="project_type" value="{{ $filters['project_type'] ?? '' }}">
                        <input type="hidden" name="project_status" value="{{ $filters['project_status'] ?? '' }}">
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

    <script>
        function setProjectFilterBodyHeight(form) {
            const body = form ? form.querySelector('.project-filter-body') : null;
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

            form.classList.toggle('collapsed');
            const expanded = !form.classList.contains('collapsed');
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            setProjectFilterBodyHeight(form);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.project-filter-form').forEach(setProjectFilterBodyHeight);

            window.addEventListener('resize', () => {
                document.querySelectorAll('.project-filter-form').forEach(setProjectFilterBodyHeight);
            });
        });
    </script>
@endsection
