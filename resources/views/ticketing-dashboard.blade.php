@extends('layouts.dashboard')

@section('title', 'Ticketing Dashboard')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    @php($user = auth()->user())

    <div class="content-header">
        <h1>Ticketing System Dashboard</h1>
        <p>Role-based ticket monitoring and workflow overview for {{ $userRoleLabel }}.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <div class="ticketing-grid ticketing-grid--4">
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

        <div class="ticketing-card">
            <div class="ticketing-toolbar">
                <div>
                    <h3 class="ticketing-card-title">Quick Access</h3>
                    <p class="ticketing-card-subtitle">Open the pages and queues available for your role.</p>
                </div>
                <div class="ticketing-toolbar-actions">
                    @if ($user->isLguUser())
                        <a href="{{ route('ticketing.create') }}" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-plus"></i>
                            Submit Ticket
                        </a>
                        <a href="{{ route('ticketing.my-tickets') }}" class="ticketing-btn ticketing-btn--secondary">
                            <i class="fas fa-list"></i>
                            View My Tickets
                        </a>
                        <a href="{{ route('ticketing.track') }}" class="ticketing-btn ticketing-btn--secondary">
                            <i class="fas fa-route"></i>
                            Track Status
                        </a>
                    @elseif ($user->isProvincialUser())
                        <a href="{{ route('ticketing.province.index') }}" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-inbox"></i>
                            Provincial Queue
                        </a>
                    @elseif ($user->isRegionalUser())
                        <a href="{{ route('ticketing.region.index') }}" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-inbox"></i>
                            Regional Queue
                        </a>
                    @else
                        <a href="{{ route('ticketing.admin.index') }}" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-chart-pie"></i>
                            Admin Monitoring
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="ticketing-grid ticketing-grid--2">
            <div class="ticketing-card">
                <h3 class="ticketing-card-title">Recent Tickets</h3>
                <p class="ticketing-card-subtitle" style="margin-bottom: 16px;">Latest tickets visible to your role.</p>

                @if ($recentTickets->isEmpty())
                    <div class="ticketing-empty">No ticket activity is available yet.</div>
                @else
                    <div class="ticketing-table-wrap">
                        <table class="ticketing-table" style="min-width: 100%;">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTickets as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-ticket-link">{{ $ticket->ticket_number }}</a>
                                            <div class="ticketing-kicker">{{ $ticket->title }}</div>
                                        </td>
                                        <td><span class="ticketing-badge" style="background: {{ $ticket->status_color }};">{{ $ticket->status }}</span></td>
                                        <td><span class="ticketing-badge" style="background: {{ $ticket->priority_color }};">{{ $ticket->priority }}</span></td>
                                        <td>
                                            <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-btn ticketing-btn--secondary" style="padding: 9px 12px;">
                                                <i class="fas fa-arrow-right"></i>
                                                Open
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="ticketing-card">
                <h3 class="ticketing-card-title">Recent Activity</h3>
                <p class="ticketing-card-subtitle" style="margin-bottom: 16px;">Latest workflow events from the audit trail.</p>

                @if ($recentActivity->isEmpty())
                    <div class="ticketing-empty">No ticket activity has been logged yet.</div>
                @else
                    @foreach ($recentActivity as $activity)
                        <div class="ticketing-activity-item">
                            <div class="ticketing-activity-title">{{ $activity->description }}</div>
                            <div class="ticketing-activity-meta">
                                Ticket: {{ $activity->ticket?->ticket_number ?? 'N/A' }}
                                @if ($activity->ticket?->title)
                                    • {{ $activity->ticket->title }}
                                @endif
                            </div>
                            <div class="ticketing-activity-meta">
                                Actor: {{ $activity->actor?->fullName() ?? 'System' }}
                                • {{ optional($activity->created_at)->format('M d, Y h:i A') }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
