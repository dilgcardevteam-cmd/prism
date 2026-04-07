@php
    $showSubmittedBy = $showSubmittedBy ?? true;
    $showAssignee = $showAssignee ?? true;
    $emptyMessage = $emptyMessage ?? 'No tickets found for the selected view.';
    $titleLabel = $titleLabel ?? 'Subject';
@endphp

<div class="ticketing-card">
    <div class="ticketing-table-wrap">
        <table class="ticketing-table">
            <thead>
                <tr>
                    <th>Ticket No.</th>
                    <th>{{ $titleLabel }}</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Current Level</th>
                    @if ($showSubmittedBy)
                        <th>Submitted By</th>
                    @endif
                    @if ($showAssignee)
                        <th>Assigned To</th>
                    @endif
                    <th>Last Update</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td>
                            <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-ticket-link">{{ $ticket->ticket_number }}</a>
                            <div class="ticketing-kicker">{{ optional($ticket->date_submitted)->format('M d, Y h:i A') }}</div>
                        </td>
                        <td>
                            <strong>{{ $ticket->title }}</strong>
                            <div class="ticketing-kicker">{{ \Illuminate\Support\Str::limit($ticket->description, 90) }}</div>
                        </td>
                        <td>{{ $ticket->category->name ?? 'Uncategorized' }}</td>
                        <td>
                            <span class="ticketing-badge" style="background: {{ $ticket->priority_color }};">{{ $ticket->priority }}</span>
                        </td>
                        <td>
                            <span class="ticketing-badge" style="background: {{ $ticket->status_color }};">{{ $ticket->status }}</span>
                        </td>
                        <td>{{ $ticket->current_level_label }}</td>
                        @if ($showSubmittedBy)
                            <td>
                                {{ $ticket->submitter?->fullName() ?? 'N/A' }}
                                <div class="ticketing-kicker">{{ $ticket->province_scope }}</div>
                            </td>
                        @endif
                        @if ($showAssignee)
                            <td>{{ $ticket->assignee?->fullName() ?? 'Unassigned' }}</td>
                        @endif
                        <td>{{ optional($ticket->last_status_changed_at ?? $ticket->updated_at)->format('M d, Y h:i A') }}</td>
                        <td>
                            <div class="ticketing-inline-actions">
                                @can('ticketing.acceptProvince', $ticket)
                                    <form method="POST" action="{{ route('ticketing.province.accept', $ticket) }}">
                                        @csrf
                                        <button type="submit" class="ticketing-btn ticketing-btn--primary" style="padding: 9px 12px;">
                                            <i class="fas fa-hand"></i>
                                            Accept
                                        </button>
                                    </form>
                                @endcan

                                @can('ticketing.acceptRegion', $ticket)
                                    <form method="POST" action="{{ route('ticketing.region.accept', $ticket) }}">
                                        @csrf
                                        <button type="submit" class="ticketing-btn ticketing-btn--primary" style="padding: 9px 12px;">
                                            <i class="fas fa-hand"></i>
                                            Accept
                                        </button>
                                    </form>
                                @endcan

                                <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-btn ticketing-btn--secondary" style="padding: 9px 12px;">
                                    <i class="fas fa-eye"></i>
                                    Open
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 8 + ($showSubmittedBy ? 1 : 0) + ($showAssignee ? 1 : 0) }}">
                            <div class="ticketing-empty">{{ $emptyMessage }}</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($tickets, 'links'))
        <div style="margin-top: 18px;">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
