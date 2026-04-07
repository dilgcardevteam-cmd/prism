@extends('layouts.dashboard')

@section('title', 'Track Ticket Status')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="content-header">
        <h1>Track Ticket Status</h1>
        <p>Follow where each ticket currently sits in the LGU → Province → Region → Central Office workflow.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        @include('partials.ticketing-filters', [
            'categories' => $categories,
            'statuses' => $statuses,
            'priorities' => $priorities,
        ])

        @forelse ($tickets as $ticket)
            @php
                $steps = [
                    ['label' => 'Submitted', 'done' => true, 'active' => $ticket->status === \App\Models\Ticket::STATUS_SUBMITTED],
                    ['label' => 'Provincial Review', 'done' => in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
                        \App\Models\Ticket::STATUS_RESOLVED_BY_PROVINCE,
                        \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                        \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                        \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                        \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                        \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                        \App\Models\Ticket::STATUS_CLOSED,
                    ], true), 'active' => $ticket->status === \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE],
                    ['label' => 'Regional Review', 'done' => in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                        \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                        \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                        \App\Models\Ticket::STATUS_CLOSED,
                    ], true), 'active' => in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                        \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                    ], true)],
                    ['label' => 'Central Office', 'done' => in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                        \App\Models\Ticket::STATUS_CLOSED,
                    ], true), 'active' => $ticket->status === \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE],
                    ['label' => 'Closed', 'done' => $ticket->status === \App\Models\Ticket::STATUS_CLOSED, 'active' => $ticket->status === \App\Models\Ticket::STATUS_CLOSED],
                ];
            @endphp

            <div class="ticketing-card">
                <div class="ticketing-toolbar">
                    <div>
                        <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-ticket-link">{{ $ticket->ticket_number }}</a>
                        <h3 class="ticketing-card-title" style="margin-top: 8px;">{{ $ticket->title }}</h3>
                        <p class="ticketing-card-subtitle">
                            Category: {{ $ticket->category->name ?? 'Uncategorized' }}
                            • Priority: {{ $ticket->priority }}
                            • Current level: {{ $ticket->current_level_label }}
                        </p>
                    </div>
                    <span class="ticketing-badge" style="background: {{ $ticket->status_color }};">{{ $ticket->status }}</span>
                </div>

                <div class="ticketing-progress" style="margin: 18px 0 12px;">
                    @foreach ($steps as $step)
                        <span class="ticketing-progress-step @if($step['active']) is-active @elseif($step['done']) is-done @endif">
                            {{ $step['label'] }}
                        </span>
                    @endforeach
                </div>

                <div class="ticketing-kicker" style="margin-bottom: 12px;">
                    Last update: {{ optional($ticket->last_status_changed_at ?? $ticket->updated_at)->format('M d, Y h:i A') }}
                </div>

                <a href="{{ route('ticketing.show', $ticket) }}" class="ticketing-btn ticketing-btn--secondary">
                    <i class="fas fa-eye"></i>
                    View Ticket Details
                </a>
            </div>
        @empty
            <div class="ticketing-empty">There are no tickets to track right now.</div>
        @endforelse

        @if (method_exists($tickets, 'links'))
            <div class="ticketing-card">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endsection
