@extends('layouts.dashboard')

@section('title', 'PISAT Dashboard')
@section('page-title', 'PISAT Dashboard')

@section('content')
    <div class="content-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <h1 style="color:#0f172a;font-size:24px;font-weight:800;margin:0;">PISAT Completed Projects</h1>
            <p style="color:#4b5563;font-size:14px;margin:4px 0 0 0;">List of completed locally funded projects requiring Post-Project Impact & Sustainability Assessment (PISAT).</p>
        </div>
        <div>
            <a href="{{ route('reports.one-time.pisat.create') }}" 
               style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:#002c76;color:#ffffff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 2px 4px rgba(0,0,0,0.1);transition:background 0.2s;"
               onmouseover="this.style.background='#001f54'" onmouseout="this.style.background='#002c76'">
                <i class="fas fa-plus"></i>
                <span>New Assessment</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:14px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
            <i class="fas fa-check-circle" style="font-size:16px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
            <i class="fas fa-exclamation-circle" style="font-size:16px;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session('info'))
        <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:14px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
            <i class="fas fa-info-circle" style="font-size:16px;"></i>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    <!-- Filtering panel -->
    <div style="background:#ffffff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-bottom:24px;border:1px solid #e5e7eb;">
        <form method="GET" action="{{ route('reports.one-time.pisat') }}" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">
            <div style="flex:1 1 240px;min-width:240px;">
                <label for="filter-search" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Search Project</label>
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:12px;top:13px;color:#9ca3af;"></i>
                    <input type="text" id="filter-search" name="search" value="{{ request('search') }}" placeholder="Search by name or project code..." 
                           style="width:100%;height:40px;padding:0 12px 0 34px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                </div>
            </div>

            @if (!Auth::user()->isLguScopedUser())
                <div style="flex:1 1 180px;min-width:180px;">
                    <label for="filter-province" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Province</label>
                    <select id="filter-province" name="province" style="width:100%;height:40px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                        <option value="">All Provinces</option>
                        @foreach($provinces as $prov)
                            <option value="{{ $prov }}" @selected(request('province') === $prov)>{{ $prov }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1 1 200px;min-width:200px;">
                    <label for="filter-city" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">City / Municipality</label>
                    <select id="filter-city" name="city_municipality" style="width:100%;height:40px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                        <option value="">All Cities / Municipalities</option>
                        @foreach($officesByProvince as $prov => $cities)
                            <optgroup label="{{ $prov }}">
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" @selected(request('city_municipality') === $city)>{{ $city }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="flex:1 1 160px;min-width:160px;">
                <label for="filter-status" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">PISAT Status</label>
                <select id="filter-status" name="status" style="width:100%;height:40px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                    <option value="">All Statuses</option>
                    <option value="none" @selected(request('status') === 'none')>No Assessment</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="returned" @selected(request('status') === 'returned')>Returned</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" style="height:40px;padding:0 18px;background-color:#2563eb;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:background 0.2s;"
                        onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <a href="{{ route('reports.one-time.pisat') }}" style="height:40px;padding:0 18px;background-color:#4b5563;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;text-decoration:none;transition:background 0.2s;"
                   onmouseover="this.style.background='#374151'" onmouseout="this.style.background='#4b5563'">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Table list of completed LFP projects -->
    <div style="background:#ffffff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);border:1px solid #e5e7eb;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:1000px;text-align:left;">
                <thead>
                    <tr style="background-color:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;width:160px;">Project Code</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;width:340px;">Project Title</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">LGU / Location</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:80px;">Year</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:140px;">PISAT Status</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:160px;">Impact Classification</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody style="divide-y: 1px solid #e2e8f0;">
                    @forelse ($projects as $project)
                        @php
                            $statusColors = [
                                'draft' => ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#d1d5db'],
                                'submitted' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#93c5fd'],
                                'approved' => ['bg' => '#ecfdf5', 'text' => '#047857', 'border' => '#6ee7b7'],
                                'returned' => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fca5a5'],
                            ];
                            $colors = $statusColors[$project->assessment_status] ?? ['bg' => '#fafafa', 'text' => '#6b7280', 'border' => '#e5e7eb'];
                        @endphp
                        <tr style="border-bottom:1px solid #e2e8f0;transition:background 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding:14px 20px;color:#64748b;font-size:13px;font-family:Consolas, monospace;font-weight:600;">
                                {{ $project->project_code }}
                            </td>
                            <td style="padding:14px 20px;color:#0f172a;font-weight:600;font-size:13px;line-height:1.4;">
                                {{ $project->project_title }}
                            </td>
                            <td style="padding:14px 20px;color:#334155;font-size:13px;line-height:1.4;">
                                <strong>{{ $project->city_municipality }}</strong>, {{ $project->province }}
                                @if($project->barangay)
                                    <div style="font-size:11px;color:#64748b;margin-top:2px;">Brgy. {{ $project->barangay }}</div>
                                @endif
                            </td>
                            <td style="padding:14px 20px;text-align:center;color:#475569;font-size:13px;font-weight:500;">
                                {{ $project->funding_year ?: '—' }}
                            </td>
                            <td style="padding:14px 20px;text-align:center;">
                                <span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid {{ $colors['border'] }};background:{{ $colors['bg'] }};color:{{ $colors['text'] }};text-transform:uppercase;letter-spacing:0.04em;">
                                    {{ $project->assessment_status ?: 'No Assessment' }}
                                </span>
                            </td>
                            <td style="padding:14px 20px;text-align:center;font-size:13px;font-weight:700;color:#0f172a;">
                                {{ $project->assessment_impact ? ucfirst($project->assessment_impact) : '—' }}
                            </td>
                            <td style="padding:14px 20px;text-align:center;white-space:nowrap;">
                                <div style="display:inline-flex;gap:8px;align-items:center;justify-content:center;">
                                    @if ($project->assessment_id)
                                        <!-- View PISAT button -->
                                        <a href="{{ route('reports.one-time.pisat.show', $project->assessment_id) }}" 
                                           style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;"
                                           onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'" title="View PISAT Details">
                                            <i class="fas fa-eye"></i> View PISAT
                                        </a>

                                        @if ($project->assessment_status === 'submitted' && !Auth::user()->isLguScopedUser())
                                            <!-- Validate button for DILG users -->
                                            <a href="{{ route('reports.one-time.pisat.edit', $project->assessment_id) }}" 
                                               style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#10b981;color:#ffffff;border:none;text-decoration:none;font-size:12px;font-weight:600;box-shadow:0 1px 2px rgba(16,185,129,0.2);transition:background 0.2s;"
                                               onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'" title="Validate Assessment">
                                                <i class="fas fa-check-circle"></i> Validate
                                            </a>
                                        @elseif (in_array($project->assessment_status, ['draft', 'returned']))
                                            <!-- Edit button for LGU/creator -->
                                            <a href="{{ route('reports.one-time.pisat.edit', $project->assessment_id) }}" 
                                               style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;"
                                               onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'" title="Edit Assessment">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        @endif
                                    @else
                                        <!-- Create PISAT button (visible to all authorized users) -->
                                        <a href="{{ route('reports.one-time.pisat.create', ['project_code' => $project->project_code]) }}" 
                                           style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#2563eb;color:#ffffff;border:none;text-decoration:none;font-size:12px;font-weight:600;box-shadow:0 1px 2px rgba(37,99,235,0.2);transition:background 0.2s;"
                                           onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'" title="Start Assessment">
                                            <i class="fas fa-plus"></i> Create PISAT
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;background:#f8fafc;">
                                <i class="fas fa-folder-open" style="font-size:36px;margin-bottom:12px;color:#cbd5e1;display:block;"></i>
                                <span style="font-weight:600;color:#64748b;">No completed projects found</span>
                                <p style="margin:4px 0 0 0;font-size:12px;color:#94a3b8;">There are no completed locally funded projects matching your criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div style="padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@endsection
