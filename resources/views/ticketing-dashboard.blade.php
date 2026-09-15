@extends('layouts.dashboard')

@section('title', 'Ticketing Dashboard')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    @php($user = auth()->user())

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <header class="ticketing-command-header">
            <div>
                <div class="ticketing-eyebrow">Operations / Ticketing</div>
                <h1 class="ticketing-page-title">Good day, {{ $user->fname ?: 'there' }}</h1>
                <p class="ticketing-page-subtitle">A focused view of the work assigned to {{ strtolower($userRoleLabel) }}.</p>
            </div>
            <div class="ticketing-command-actions">
                @can('ticketing.submit')
                    <a href="{{ route('ticketing.create') }}" class="ticketing-btn ticketing-btn--primary">
                        <i class="fas fa-plus"></i>
                        New ticket
                    </a>
                @endcan
                @if ($user->isSuperAdmin())
                    <a href="{{ route('ticketing.admin.index') }}" class="ticketing-btn ticketing-btn--secondary">
                        <i class="fas fa-sliders"></i>
                        Manage tickets
                    </a>
                @elseif ($user->isRegionalUser())
                    <a href="{{ route('ticketing.region.index') }}" class="ticketing-btn ticketing-btn--secondary">
                        <i class="fas fa-inbox"></i>
                        Open queue
                    </a>
                @elseif ($user->isProvincialUser())
                    <a href="{{ route('ticketing.province.index') }}" class="ticketing-btn ticketing-btn--secondary">
                        <i class="fas fa-inbox"></i>
                        Open queue
                    </a>
                @endif
            </div>
        </header>

        <div class="ticketing-kpi-grid">
            @foreach ($cards as $card)
                <div class="ticketing-summary-card" style="background: linear-gradient(135deg, {{ $card['color'] }} 0%, {{ $card['color'] }}dd 100%);">
                    <span class="ticketing-summary-icon">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="ticketing-summary-label">{{ $card['label'] }}</div>
                        <div class="ticketing-summary-value">{{ number_format($card['count']) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <nav class="ticketing-quick-links" aria-label="Ticketing shortcuts">
            @can('ticketing.submit')
                <a href="{{ route('ticketing.my-tickets') }}"><i class="fas fa-list"></i><span>My tickets</span></a>
                <a href="{{ route('ticketing.track') }}"><i class="fas fa-route"></i><span>Track status</span></a>
            @endcan
            <a href="{{ route('ticketing.dashboard') }}"><i class="fas fa-chart-line"></i><span>Overview</span></a>
        </nav>

        <div class="ticketing-dashboard-grid">
            <section class="ticketing-card ticketing-dashboard-primary">
                <div class="ticketing-section-heading">
                    <div>
                        <div class="ticketing-eyebrow">Work queue</div>
                        <h2 class="ticketing-card-title">Recent tickets</h2>
                        <p class="ticketing-card-subtitle">The latest work visible to your role.</p>
                    </div>
                    <span class="ticketing-section-count">{{ $recentTickets->count() }} shown</span>
                </div>

                @if ($recentTickets->isEmpty())
                    <div class="ticketing-empty">No ticket activity is available yet.</div>
                @else
                    <div class="ticketing-table-wrap">
                        <table class="ticketing-table ticketing-dashboard-table">
                            <thead>
                                <tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Updated</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTickets as $ticket)
                                    <tr>
                                        <td><a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-ticket-link">{{ $ticket->ticket_number }}</a><div class="ticketing-kicker">{{ $ticket->title }}</div></td>
                                        <td><span class="ticketing-badge" style="background: {{ $ticket->status_color }};">{{ $ticket->status }}</span></td>
                                        <td><span class="ticketing-badge" style="background: {{ $ticket->priority_color }};">{{ $ticket->priority }}</span></td>
                                        <td class="ticketing-table-secondary">{{ optional($ticket->last_status_changed_at ?? $ticket->updated_at)->format('M d, h:i A') }}</td>
                                        <td><a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-icon-link" aria-label="Open {{ $ticket->ticket_number }}" title="Open ticket"><i class="fas fa-arrow-up-right-from-square"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
 
            <aside class="ticketing-dashboard-rail">
                <section class="ticketing-card">
                    <div class="ticketing-section-heading">
                        <div><div class="ticketing-eyebrow">Completed work</div><h2 class="ticketing-card-title">Resolved tasks</h2></div>
                        <span class="ticketing-section-count">{{ $resolvedTickets->count() }}</span>
                    </div>

                    @if ($resolvedTickets->isEmpty())
                        <div class="ticketing-empty">No resolved tasks are available yet.</div>
                    @else
                        <div class="ticketing-compact-list">
                                @foreach ($resolvedTickets as $ticket)
                                <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-compact-item"><span><strong>{{ $ticket->ticket_number }}</strong><small>{{ $ticket->title }}</small></span><i class="fas fa-chevron-right"></i></a>
                                @endforeach
                        </div>
                    @endif
                </section>

                <section class="ticketing-card">
                    <div class="ticketing-eyebrow">Audit trail</div>
                    <h2 class="ticketing-card-title">Recent activity</h2>
                    <p class="ticketing-card-subtitle" style="margin-bottom: 12px;">Latest workflow events.</p>

                    @if ($recentActivity->isEmpty())
                        <div class="ticketing-empty">No ticket activity has been logged yet.</div>
                    @else
                        @foreach ($recentActivity as $activity)
                            <div class="ticketing-activity-item"><div class="ticketing-activity-title">{{ $activity->description }}</div><div class="ticketing-activity-meta">{{ $activity->ticket?->ticket_number ?? 'N/A' }} · {{ $activity->actor?->fullName() ?? 'System' }}</div><div class="ticketing-activity-meta">{{ optional($activity->created_at)->format('M d, Y h:i A') }}</div></div>
                        @endforeach
                    @endif
                </section>
            </aside>
        </div>
    </div>
@endsection
