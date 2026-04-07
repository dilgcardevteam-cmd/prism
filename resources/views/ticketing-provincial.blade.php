@extends('layouts.dashboard')

@section('title', 'Provincial Ticket List')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    <div class="content-header">
        <h1>Provincial Ticket List</h1>
        <p>Review the provincial queue, accept tickets into your name, then resolve or escalate them to the Regional User.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <div class="ticketing-card">
            <div class="ticketing-toolbar">
                <div>
                    <h3 class="ticketing-card-title">Provincial Workflow Rules</h3>
                    <p class="ticketing-card-subtitle">New LGU tickets enter a shared provincial queue first. A Provincial User must accept the ticket before review, resolution, escalation, or remarks at the provincial level.</p>
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
            'emptyMessage' => 'No provincial tickets matched the current filters.',
        ])
    </div>
@endsection
