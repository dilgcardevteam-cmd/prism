@extends('layouts.dashboard')

@section('title', 'My Tickets')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <header class="ticketing-command-header">
            <div><div class="ticketing-eyebrow">Requester portal / Work history</div><h1 class="ticketing-page-title">My tickets</h1><p class="ticketing-page-subtitle">Track requests, read remarks, and follow each resolution through its audit trail.</p></div>
            <div class="ticketing-command-actions">
                <a href="{{ route('ticketing.create') }}" class="ticketing-btn ticketing-btn--primary">
                    <i class="fas fa-plus"></i>
                    New ticket
                </a>
                <a href="{{ route('ticketing.track') }}" class="ticketing-btn ticketing-btn--secondary">
                    <i class="fas fa-route"></i>
                    Track status
                </a>
            </div>
        </header>

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
