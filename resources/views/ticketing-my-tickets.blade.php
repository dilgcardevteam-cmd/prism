@extends('layouts.dashboard')

@section('title', 'My Tickets')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="content-header">
        <h1>My Tickets</h1>
        <p>Review every ticket you submitted and open the full audit trail for each item.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <div class="ticketing-toolbar">
            <div class="ticketing-card" style="flex: 1;">
                <h3 class="ticketing-card-title">LGU Ticket Workspace</h3>
                <p class="ticketing-card-subtitle">New tickets route first to the Provincial User, then to the Regional User if escalation is needed.</p>
            </div>
            <div class="ticketing-toolbar-actions">
                <a href="{{ route('ticketing.create') }}" class="ticketing-btn ticketing-btn--primary">
                    <i class="fas fa-plus"></i>
                    Submit Ticket
                </a>
                <a href="{{ route('ticketing.track') }}" class="ticketing-btn ticketing-btn--secondary">
                    <i class="fas fa-route"></i>
                    Track Status
                </a>
            </div>
        </div>

        @include('partials.ticketing-filters', [
            'categories' => $categories,
            'statuses' => $statuses,
            'priorities' => $priorities,
        ])

        @include('partials.ticketing-table', [
            'tickets' => $tickets,
            'showSubmittedBy' => false,
            'emptyMessage' => 'You have not submitted any tickets yet.',
        ])
    </div>
@endsection
