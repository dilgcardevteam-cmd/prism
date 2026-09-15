@extends('layouts.dashboard')

@section('title', 'Regional Ticket List')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <header class="ticketing-command-header"><div><div class="ticketing-eyebrow">Support workspace / Regional</div><h1 class="ticketing-page-title">Regional queue</h1><p class="ticketing-page-subtitle">Work escalated requests and tickets assigned directly to the regional support level.</p></div><a href="{{ route('ticketing.dashboard') }}" class="ticketing-btn ticketing-btn--secondary"><i class="fas fa-chart-line"></i> Overview</a></header>

        @include('partials.ticketing-filters', [
            'categories' => $categories,
            'statuses' => $statuses,
            'priorities' => $priorities,
        ])

        @include('partials.ticketing-table', [
            'tickets' => $tickets,
            'showSubmittedBy' => true,
            'showAssignee' => true,
            'emptyMessage' => 'No regional tickets matched the current filters.',
        ])
    </div>
@endsection
