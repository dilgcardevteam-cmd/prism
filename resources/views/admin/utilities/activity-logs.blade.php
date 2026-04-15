@extends('layouts.dashboard')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
    @php
        $criticalActions = ['FAILED_LOGIN', 'DELETE'];
        $warningActions = ['ROLE_CHANGE', 'PERMISSION_CHANGE', 'STATUS_CHANGE', 'VALIDATION_FAILED', 'MAINTENANCE_MODE_CHANGE'];
        $successActions = ['LOGIN', 'LOGOUT', 'REGISTER', 'PASSWORD_CHANGE', 'PASSWORD_RESET', 'CREATE', 'EXPORT', 'UPLOAD'];
    @endphp

    <style>
        .activity-logs-shell {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .activity-logs-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .activity-logs-header {
            padding: 24px 24px 10px;
        }

        .activity-logs-header h1 {
            margin: 0;
            color: #002c76;
            font-size: 28px;
            font-weight: 800;
        }

        .activity-logs-header p {
            margin: 10px 0 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
            max-width: 900px;
        }

        .activity-logs-filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            padding: 0 24px 24px;
            align-items: end;
        }

        .activity-logs-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .activity-logs-field label {
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .activity-logs-field input,
        .activity-logs-field select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 13px;
            color: #0f172a;
            background: #fff;
        }

        .activity-logs-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .activity-logs-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 130px;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .activity-logs-btn--primary {
            border: none;
            background: #002c76;
            color: #fff;
        }

        .activity-logs-btn--secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        .activity-logs-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 24px;
            border-top: 1px solid #eef2f7;
            border-bottom: 1px solid #eef2f7;
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
        }

        .activity-logs-table-wrap {
            overflow-x: auto;
        }

        .activity-logs-table {
            width: 100%;
            min-width: 1120px;
            border-collapse: collapse;
        }

        .activity-logs-table thead th {
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-align: left;
        }

        .activity-logs-table tbody td {
            padding: 18px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
            color: #334155;
            font-size: 13px;
            line-height: 1.7;
        }

        .activity-logs-table tbody tr.activity-logs-row--critical {
            background: #fff7f7;
        }

        .activity-logs-table tbody tr.activity-logs-row--warning {
            background: #fffbeb;
        }

        .activity-logs-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .activity-logs-badge--critical {
            background: #fee2e2;
            color: #b91c1c;
        }

        .activity-logs-badge--warning {
            background: #fef3c7;
            color: #b45309;
        }

        .activity-logs-badge--success {
            background: #dcfce7;
            color: #166534;
        }

        .activity-logs-badge--neutral {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .activity-logs-title {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .activity-logs-subtle {
            color: #64748b;
            font-size: 12px;
        }

        .activity-logs-user-link {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 700;
        }

        .activity-logs-empty {
            padding: 42px 24px;
            text-align: center;
            color: #64748b;
        }

        .activity-logs-empty h3 {
            margin: 0 0 8px;
            color: #334155;
            font-size: 18px;
        }

        .activity-logs-pagination {
            padding: 18px 24px 24px;
        }

        .activity-logs-pagination nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .activity-logs-pagination a,
        .activity-logs-pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .activity-logs-pagination .active > span,
        .activity-logs-pagination .active span {
            background: #002c76;
            border-color: #002c76;
            color: #fff;
        }

        @media (max-width: 768px) {
            .activity-logs-header,
            .activity-logs-filter-form,
            .activity-logs-meta,
            .activity-logs-pagination {
                padding-left: 16px;
                padding-right: 16px;
            }

            .activity-logs-header h1 {
                font-size: 24px;
            }
        }
    </style>

    <div class="activity-logs-shell">
        @if (session('success'))
            <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #166534; font-size: 13px; font-weight: 600;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 600;">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="padding: 14px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff7f7; color: #991b1b;">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="activity-logs-card">
            <div class="activity-logs-header">
                <h1>Activity Logs</h1>
                <p>
                    Central audit trail for authentication events, CRUD activity, role and permission changes, password operations,
                    uploads, maintenance mode changes, and failed validations. Sensitive request data such as passwords, tokens, and OTP values
                    is masked before any log entry is stored.
                </p>
            </div>

            <form method="GET" action="{{ route('utilities.activity-logs.index') }}" class="activity-logs-filter-form">
                <div class="activity-logs-field">
                    <label for="activity-user">User</label>
                    <input id="activity-user" type="text" name="user" value="{{ $filters['user'] }}" placeholder="User ID or username">
                </div>

                <div class="activity-logs-field">
                    <label for="activity-action">Action</label>
                    <select id="activity-action" name="action">
                        <option value="">All actions</option>
                        @foreach ($actionOptions as $actionOption)
                            <option value="{{ $actionOption }}" @selected($filters['action'] === $actionOption)>{{ $actionOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="activity-logs-field">
                    <label for="activity-date-from">Date From</label>
                    <input id="activity-date-from" type="date" name="date_from" value="{{ $filters['date_from'] }}">
                </div>

                <div class="activity-logs-field">
                    <label for="activity-date-to">Date To</label>
                    <input id="activity-date-to" type="date" name="date_to" value="{{ $filters['date_to'] }}">
                </div>

                <div class="activity-logs-field">
                    <label for="activity-sort">Sort</label>
                    <select id="activity-sort" name="sort">
                        <option value="latest" @selected($filters['sort'] === 'latest')>Latest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                    </select>
                </div>

                <div class="activity-logs-field">
                    <label for="activity-search">Search</label>
                    <input id="activity-search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Description, IP, device...">
                </div>

                <div class="activity-logs-actions">
                    <button type="submit" class="activity-logs-btn activity-logs-btn--primary">
                        <i class="fas fa-filter"></i>
                        <span>Apply Filters</span>
                    </button>
                    <a href="{{ route('utilities.activity-logs.index') }}" class="activity-logs-btn activity-logs-btn--secondary">
                        <i class="fas fa-rotate-left"></i>
                        <span>Reset</span>
                    </a>
                    @if ($tableReady)
                        <a href="{{ route('utilities.activity-logs.export', $filters) }}" class="activity-logs-btn activity-logs-btn--secondary" data-page-loading="false">
                            <i class="fas fa-file-csv"></i>
                            <span>Export CSV</span>
                        </a>
                    @endif
                </div>
            </form>

            <div class="activity-logs-meta">
                <div>
                    @if ($tableReady)
                        Showing {{ number_format($logs->total()) }} log entr{{ $logs->total() === 1 ? 'y' : 'ies' }}.
                    @else
                        The activity logs table is not available yet.
                    @endif
                </div>
                <div>
                    Default sort: latest first. Critical events are highlighted.
                </div>
            </div>

            @if (! $tableReady)
                <div class="activity-logs-empty">
                    <h3>Activity log storage is not ready.</h3>
                    <p>Run the `activity_logs` migration first, then reload this page.</p>
                </div>
            @elseif ($logs->isEmpty())
                <div class="activity-logs-empty">
                    <h3>No activity logs matched the current filters.</h3>
                    <p>Try widening the date range, clearing filters, or waiting for new activity to be recorded.</p>
                </div>
            @else
                <div class="activity-logs-table-wrap">
                    <table class="activity-logs-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Device / Browser</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                @php
                                    $rowClass = in_array($log->action, $criticalActions, true)
                                        ? 'activity-logs-row--critical'
                                        : (in_array($log->action, $warningActions, true) ? 'activity-logs-row--warning' : '');
                                    $badgeClass = in_array($log->action, $criticalActions, true)
                                        ? 'activity-logs-badge--critical'
                                        : (in_array($log->action, $warningActions, true)
                                            ? 'activity-logs-badge--warning'
                                            : (in_array($log->action, $successActions, true)
                                                ? 'activity-logs-badge--success'
                                                : 'activity-logs-badge--neutral'));
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>
                                        <div class="activity-logs-title">{{ optional($log->created_at)->format('M d, Y') }}</div>
                                        <div class="activity-logs-subtle">{{ optional($log->created_at)->format('h:i:s A') }} {{ $log->timezone ?: config('app.timezone') }}</div>
                                    </td>
                                    <td>
                                        @if ($log->username)
                                            <a href="{{ route('utilities.activity-logs.index', array_merge($filters, ['user' => $log->username])) }}" class="activity-logs-user-link">
                                                {{ $log->username }}
                                            </a>
                                        @else
                                            <span class="activity-logs-title">System</span>
                                        @endif
                                        <div class="activity-logs-subtle">ID: {{ $log->user_id ?: 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="activity-logs-badge {{ $badgeClass }}">{{ $log->action }}</span>
                                    </td>
                                    <td>
                                        <div class="activity-logs-title">{{ $log->description }}</div>
                                        @if (!empty($log->properties))
                                            <div class="activity-logs-subtle">
                                                @if (!empty($log->properties['route_name']))
                                                    Route: {{ $log->properties['route_name'] }}
                                                @endif
                                                @if (!empty($log->properties['reason']))
                                                    @if (!empty($log->properties['route_name'])) | @endif
                                                    Reason: {{ $log->properties['reason'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="activity-logs-title">{{ $log->ip_address ?: 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="activity-logs-title">{{ $log->device ?: 'Unknown device' }}</div>
                                        <div class="activity-logs-subtle">{{ \Illuminate\Support\Str::limit($log->user_agent ?: 'No user agent captured.', 90) }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="activity-logs-pagination">
                    @php
                        $currentPage = $logs->currentPage();
                        $lastPage = $logs->lastPage();
                        $windowStart = max(1, $currentPage - 2);
                        $windowEnd = min($lastPage, $currentPage + 2);
                    @endphp

                    @if ($logs->hasPages())
                        <nav aria-label="Activity logs pagination">
                            @if ($logs->onFirstPage())
                                <span aria-disabled="true">Previous</span>
                            @else
                                <a href="{{ $logs->previousPageUrl() }}" rel="prev">Previous</a>
                            @endif

                            @if ($windowStart > 1)
                                <a href="{{ $logs->url(1) }}">1</a>
                                @if ($windowStart > 2)
                                    <span>...</span>
                                @endif
                            @endif

                            @for ($page = $windowStart; $page <= $windowEnd; $page++)
                                @if ($page === $currentPage)
                                    <span class="active"><span>{{ $page }}</span></span>
                                @else
                                    <a href="{{ $logs->url($page) }}">{{ $page }}</a>
                                @endif
                            @endfor

                            @if ($windowEnd < $lastPage)
                                @if ($windowEnd < $lastPage - 1)
                                    <span>...</span>
                                @endif
                                <a href="{{ $logs->url($lastPage) }}">{{ $lastPage }}</a>
                            @endif

                            @if ($logs->hasMorePages())
                                <a href="{{ $logs->nextPageUrl() }}" rel="next">Next</a>
                            @else
                                <span aria-disabled="true">Next</span>
                            @endif
                        </nav>
                    @endif
                </div>
            @endif
        </section>
    </div>
@endsection
