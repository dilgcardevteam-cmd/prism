@extends('layouts.dashboard')

@section('title', 'Regional Ticket List')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="content-header">
        <h1>Regional Ticket List</h1>
        <p>Handle the regional queue, accept escalated tickets into your name, then resolve them or forward them to Central Office.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <div class="ticketing-card">
            <div class="ticketing-toolbar">
                <div>
                    <h3 class="ticketing-card-title">Regional Workflow Rules</h3>
                    <p class="ticketing-card-subtitle">Escalated tickets enter a shared regional queue first. A Regional User must accept the ticket before review, resolution, forwarding, or remarks at the regional level.</p>
                </div>
                <a href="{{ route('ticketing.dashboard') }}" class="ticketing-btn ticketing-btn--secondary">
                    <i class="fas fa-chart-line"></i>
                    Back to Dashboard
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
            'showSubmittedBy' => true,
            'showAssignee' => true,
            'emptyMessage' => 'No regional tickets matched the current filters.',
        ])
    </div>
@endsection
