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

        .activity-logs-shell {
            gap: 18px;
            max-width: 1800px;
            margin: 0 auto;
            color: #172b4d;
        }

        .activity-logs-card {
            overflow: hidden;
            border-color: #dce5f1;
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(15, 41, 77, 0.08);
        }

        .activity-logs-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 28px 30px 22px;
        }

        .activity-logs-heading {
            min-width: 0;
        }

        .activity-logs-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
            color: #4771a7;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }

        .activity-logs-eyebrow i {
            color: #1d4ed8;
        }

        .activity-logs-header h1 {
            color: #102b57;
            font-size: clamp(24px, 2vw, 32px);
            letter-spacing: -0.035em;
            line-height: 1.15;
        }

        .activity-logs-header p {
            max-width: 780px;
            margin-top: 9px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.65;
        }

        .activity-logs-filter-form {
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px 12px;
            padding: 22px 30px 24px;
            border-top: 1px solid #edf2f8;
            background: #fbfcfe;
        }

        .activity-logs-field {
            grid-column: span 2;
            gap: 7px;
            min-width: 0;
        }

        .activity-logs-field label {
            color: #5b6d84;
            font-size: 10px;
            letter-spacing: 0.09em;
        }

        .activity-logs-field input,
        .activity-logs-field select {
            height: 42px;
            border-color: #d5dfeb;
            border-radius: 9px;
            padding: 0 12px;
            outline: none;
            font-family: inherit;
            font-size: 12px;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .activity-logs-field input::placeholder {
            color: #9aa8bb;
        }

        .activity-logs-field input:focus,
        .activity-logs-field select:focus {
            border-color: #4b79c7;
            box-shadow: 0 0 0 3px rgba(59, 111, 190, 0.12);
        }

        .activity-logs-actions {
            grid-column: 1 / -1;
            justify-content: flex-end;
            gap: 9px;
        }

        .activity-logs-btn {
            min-width: 112px;
            min-height: 40px;
            border-radius: 9px;
            padding: 9px 14px;
            font-family: inherit;
            font-size: 12px;
            transition: transform 150ms ease, box-shadow 150ms ease, background 150ms ease;
        }

        .activity-logs-btn:hover {
            transform: translateY(-1px);
        }

        .activity-logs-btn--primary {
            background: #103f88;
            box-shadow: 0 4px 10px rgba(16, 63, 136, 0.16);
        }

        .activity-logs-btn--primary:hover {
            background: #0b3474;
            box-shadow: 0 6px 14px rgba(16, 63, 136, 0.22);
        }

        .activity-logs-btn--secondary {
            border-color: #d5dfeb;
            color: #40536b;
        }

        .activity-logs-btn--secondary:hover {
            border-color: #a9bad0;
            background: #f7f9fc;
        }

        .activity-logs-header > .activity-logs-btn {
            flex-shrink: 0;
            margin-top: 14px;
        }

        .activity-logs-meta {
            min-height: 54px;
            padding: 12px 30px;
            border-top: 1px solid #e8eef5;
            border-bottom: 1px solid #e8eef5;
            background: #f5f8fc;
            color: #50627a;
            font-size: 12px;
        }

        .activity-logs-meta > div:first-child {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #334a66;
            font-weight: 700;
        }

        .activity-logs-meta > div:first-child i {
            color: #5279ad;
        }

        .activity-logs-meta > div:last-child {
            color: #718198;
        }

        .activity-logs-table-wrap {
            overflow: auto;
            scrollbar-color: #b7c5d8 #f1f5f9;
            scrollbar-width: thin;
        }

        .activity-logs-table {
            min-width: 1180px;
            table-layout: fixed;
        }

        .activity-logs-table th:nth-child(1) { width: 12%; }
        .activity-logs-table th:nth-child(2) { width: 16%; }
        .activity-logs-table th:nth-child(3) { width: 16%; }
        .activity-logs-table th:nth-child(4) { width: 9%; }
        .activity-logs-table th:nth-child(5) { width: 27%; }
        .activity-logs-table th:nth-child(6) { width: 9%; }
        .activity-logs-table th:nth-child(7) { width: 11%; }

        .activity-logs-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 13px 16px;
            border-bottom-color: #e2e9f2;
            background: #f8fafc;
            color: #65768d;
            font-size: 10px;
            letter-spacing: 0.1em;
        }

        .activity-logs-table tbody td {
            padding: 15px 16px;
            border-bottom-color: #edf1f6;
            color: #40516a;
            font-size: 12px;
            line-height: 1.55;
            vertical-align: middle;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .activity-logs-table tbody tr {
            transition: background-color 140ms ease;
        }

        .activity-logs-table tbody tr:nth-child(even) {
            background: #fbfcfe;
        }

        .activity-logs-table tbody tr:hover {
            background: #f7faff;
        }

        .activity-logs-table tbody tr.activity-logs-row--critical,
        .activity-logs-table tbody tr.activity-logs-row--critical:hover {
            background: #fff7f6;
        }

        .activity-logs-table tbody tr.activity-logs-row--warning,
        .activity-logs-table tbody tr.activity-logs-row--warning:hover {
            background: #fffbf2;
        }

        .activity-logs-title {
            color: #1b2f4d;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.5;
        }

        .activity-logs-subtle {
            margin-top: 3px;
            color: #8290a4;
            font-size: 10px;
            line-height: 1.5;
        }

        .activity-logs-user-link {
            color: #2459a6;
            font-size: 12px;
            font-weight: 700;
        }

        .activity-logs-user-link:hover {
            color: #123b7d;
            text-decoration: underline;
        }

        .activity-logs-badge {
            padding: 5px 9px;
            font-size: 9px;
            letter-spacing: 0.07em;
        }

        .activity-logs-badge--neutral {
            background: #e8f0fd;
            color: #2459a6;
        }

        .activity-logs-table tbody td:first-child,
        .activity-logs-table tbody td:nth-child(6) {
            white-space: nowrap;
        }

        .activity-logs-table tbody td:nth-child(6) .activity-logs-title {
            color: #334e70;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11px;
        }

        .activity-logs-page-indicator {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid #e1e8f1;
            border-radius: 8px;
            background: #f7f9fc;
            color: #536780;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .activity-logs-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 30px 20px;
            background: #fff;
        }

        .activity-logs-pagination nav {
            gap: 7px;
        }

        .activity-logs-pagination a,
        .activity-logs-pagination span {
            min-width: 38px;
            height: 36px;
            border-color: #dbe3ed;
            border-radius: 8px;
            color: #40536b;
            font-size: 11px;
        }

        .activity-logs-pagination a:hover {
            border-color: #9fb3d0;
            background: #f6f9fd;
        }

        .activity-logs-empty {
            padding: 64px 24px;
            background: #fff;
        }

        .activity-logs-empty h3 {
            color: #263d5b;
        }

        .activity-logs-empty p {
            margin: 0;
            color: #7a8aa0;
            font-size: 13px;
        }

        @media (max-width: 1100px) {
            .activity-logs-field {
                grid-column: span 4;
            }
        }

        @media (max-width: 700px) {
            .activity-logs-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
                padding: 24px 20px 20px;
            }

            .activity-logs-header > .activity-logs-btn {
                margin-top: 0;
            }

            .activity-logs-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 13px 10px;
                padding: 18px 20px 20px;
            }

            .activity-logs-field {
                grid-column: span 1;
            }

            .activity-logs-actions {
                justify-content: stretch;
            }

            .activity-logs-actions .activity-logs-btn {
                flex: 1 1 0;
                min-width: 0;
                padding-right: 9px;
                padding-left: 9px;
            }

            .activity-logs-meta {
                align-items: flex-start;
                flex-direction: column;
                padding: 13px 20px;
            }

            .activity-logs-pagination {
                justify-content: center;
                padding: 16px 20px 20px;
            }
        }

        @media (max-width: 420px) {
            .activity-logs-field {
                grid-column: 1 / -1;
            }

            .activity-logs-actions {
                flex-wrap: wrap;
            }

            .activity-logs-actions .activity-logs-btn {
                flex-basis: calc(50% - 5px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .activity-logs-btn,
            .activity-logs-table tbody tr {
                transition: none;
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
                <div class="activity-logs-heading">
                    <div class="activity-logs-eyebrow"><i class="fas fa-shield-halved"></i> Security &amp; compliance</div>
                    <h1>Activity Logs</h1>
                    <p>
                        Review authentication, user, and system events in one place. Sensitive values are masked before log entries are stored.
                    </p>
                </div>
                @if ($tableReady)
                    <a href="{{ route('utilities.activity-logs.export', $filters) }}" class="activity-logs-btn activity-logs-btn--secondary">
                        <i class="fas fa-file-csv"></i>
                        <span>Export CSV</span>
                    </a>
                @endif
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
                </div>
            </form>

            <div class="activity-logs-meta">
                <div>
                    @if ($tableReady)
                        <i class="fas fa-list-check"></i>
                        <span>
                            {{ $logs->count() }} entr{{ $logs->count() === 1 ? 'y' : 'ies' }} on this page
                            <span aria-hidden="true">·</span>
                            Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
                        </span>
                    @else
                        The activity logs table is not available yet.
                    @endif
                </div>
                <div>
                    <i class="fas fa-circle-info"></i> Latest events first <span aria-hidden="true">·</span> Critical events highlighted
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
                                <th scope="col">Timestamp</th>
                                <th scope="col">User</th>
                                <th scope="col">Location</th>
                                <th scope="col">Action</th>
                                <th scope="col">Description</th>
                                <th scope="col">IP Address</th>
                                <th scope="col">Device / Browser</th>
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
                                        @if ($log->user)
                                            <div class="activity-logs-title">
                                                {{ trim($log->user->fname . ' ' . $log->user->lname) ?: ($log->username ?: 'Unknown user') }}
                                            </div>
                                            @if ($log->username)
                                                <a href="{{ route('utilities.activity-logs.index', array_merge($filters, ['user' => $log->username])) }}" class="activity-logs-user-link">
                                                    {{ $log->username }}
                                                </a>
                                            @endif
                                        @elseif ($log->username)
                                            <a href="{{ route('utilities.activity-logs.index', array_merge($filters, ['user' => $log->username])) }}" class="activity-logs-user-link">
                                                {{ $log->username }}
                                            </a>
                                        @else
                                            <span class="activity-logs-title">System</span>
                                        @endif
                                        <div class="activity-logs-subtle">ID: {{ $log->user_id ?: 'N/A' }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $location = array_values(array_unique(array_filter([
                                                $log->user?->office,
                                                $log->user?->agency,
                                                $log->user?->province,
                                                $log->user?->region,
                                            ], static fn ($value) => filled($value))));
                                        @endphp
                                        @if ($location !== [])
                                            <div class="activity-logs-title">{{ implode(', ', $location) }}</div>
                                        @else
                                            <span class="activity-logs-subtle">Not available</span>
                                        @endif
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
                    <span class="activity-logs-page-indicator">
                        Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
                    </span>
                    @if ($logs->hasPages())
                        <nav aria-label="Activity logs pagination">
                            @if ($logs->onFirstPage())
                                <span aria-disabled="true">Previous</span>
                            @else
                                <a href="{{ $logs->previousPageUrl() }}" rel="prev">Previous</a>
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
