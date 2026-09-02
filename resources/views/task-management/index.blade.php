@extends('layouts.dashboard')

@section('title', 'Task Management')
@section('page-title', 'Task Management')

@section('styles')
<style>
    .task-mgmt-page {
        color: #0f172a;
    }

    .task-mgmt-shell {
        display: grid;
        gap: 24px;
    }

    .task-mgmt-hero {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 30px;
        background:
            radial-gradient(circle at top right, rgba(125, 211, 252, 0.22), transparent 34%),
            linear-gradient(135deg, #0b1f52 0%, #12398d 52%, #1d4ed8 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
    }

    .task-mgmt-hero::after {
        content: '';
        position: absolute;
        inset: auto -60px -60px auto;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        filter: blur(8px);
    }

    .task-mgmt-hero-content {
        position: relative;
        z-index: 1;
    }

    .task-mgmt-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.9);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .task-mgmt-title {
        margin: 12px 0 6px;
        color: #fff;
        font-size: clamp(24px, 3.5vw, 32px);
        line-height: 1.15;
        font-weight: 800;
    }

    .task-mgmt-description {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        max-width: 600px;
    }

    .task-pool-section {
        margin-bottom: 12px;
    }

    .task-pool-section-title {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin: 12px 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
    }

    /* Cards Grid */
    .menu-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .menu-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 160px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .menu-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px -8px rgba(15, 23, 42, 0.15);
        border-color: #cbd5e1;
    }

    .menu-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .menu-card-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .menu-card-icon-wrapper.primary {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .menu-card-icon-wrapper.danger {
        background: #fef2f2;
        color: #dc2626;
    }

    .menu-card-badge {
        font-size: 14px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 999px;
    }

    .menu-card-badge.danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .menu-card-title {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.4;
    }

    .menu-card-footer {
        margin-top: 14px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .menu-card-footer.primary {
        color: #1d4ed8;
    }

    .menu-card-footer.danger {
        color: #dc2626;
    }

    /* Modal Styling */
    .task-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1040;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .task-modal {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: min(1400px, 96vw);
        max-height: 85vh;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: modalSlideUp 0.25s ease-out;
    }

    @keyframes modalSlideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .task-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }

    .task-modal-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .task-modal-title i {
        color: #1d4ed8;
    }

    .task-modal-close {
        border: none;
        background: none;
        font-size: 20px;
        color: #64748b;
        cursor: pointer;
        transition: color 0.15s ease;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .task-modal-close:hover {
        color: #0f172a;
    }

    /* Modal Filtering Bar */
    .task-modal-filter-bar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 24px;
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-input-wrapper {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .filter-input-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
    }

    .modal-search-input {
        width: 100%;
        padding: 9px 12px 9px 36px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        color: #1e293b;
        outline: none;
        transition: border-color 0.15s ease;
    }

    .modal-search-input:focus {
        border-color: #1d4ed8;
    }

    .modal-select-filter {
        width: auto;
        padding: 10px 30px 10px 36px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        color: #1e293b;
        outline: none;
        background-color: #fff;
        cursor: pointer;
        min-width: 160px;
    }

    .modal-filter-field {
        position: relative;
        min-width: 170px;
    }

    .modal-filter-field > i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #2563eb;
        font-size: 13px;
        pointer-events: none;
        z-index: 1;
    }

    .modal-filter-field .modal-select-filter {
        width: 100%;
    }

    .modal-select-filter:focus {
        border-color: #1d4ed8;
    }

    .modal-filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .modal-filter-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 9px 15px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .modal-filter-btn:hover {
        transform: translateY(-1px);
    }

    .modal-filter-btn.apply {
        background: #1d4ed8;
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.2);
    }

    .modal-filter-btn.reset {
        background: #fff;
        color: #475569;
        border-color: #cbd5e1;
    }

    .modal-filter-btn.reset:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    @media (max-width: 680px) {
        .modal-filter-field,
        .modal-filter-actions {
            width: 100%;
        }

        .modal-filter-actions .modal-filter-btn {
            flex: 1;
        }
    }

    .task-modal-body {
        overflow-y: auto;
        flex: 1;
        padding: 0;
    }

    /* Table inside modal */
    .table-container {
        overflow-x: auto;
    }

    .task-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .task-table th {
        background: #fff;
        padding: 12px 24px;
        text-align: center;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .task-table td {
        padding: 14px 24px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .task-uploader-cell {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        justify-content: center;
        text-align: center;
        min-width: 150px;
    }

    .task-uploader-name {
        color: #0f172a;
        font-size: 12px;
        font-weight: 750;
        line-height: 1.3;
    }

    .task-uploader-province {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin-top: 3px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.2;
    }

    .task-uploader-province i {
        color: #2563eb;
        font-size: 10px;
    }

    .task-table tr:last-child td {
        border-bottom: none;
    }

    .task-table tr:hover {
        background: #f8fafc;
    }

    .proj-title-cell {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
        max-width: 320px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .proj-meta-cell {
        font-size: 11px;
        color: #64748b;
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .task-message-cell {
        color: #334155;
        line-height: 1.4;
        max-width: 350px;
    }

    .task-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #0b1f52 0%, #1d4ed8 100%);
        color: #fff;
        padding: 8px 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 4px 6px -1px rgba(29, 78, 216, 0.2);
    }

    .task-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 12px -1px rgba(29, 78, 216, 0.35);
        color: #fff;
    }

    .task-action-btn.btn-danger {
        background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
    }

    .task-action-btn.btn-danger:hover {
        box-shadow: 0 6px 12px -1px rgba(220, 38, 38, 0.35);
    }

    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #64748b;
        background: #fff;
    }

    .empty-icon {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .empty-title {
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .empty-desc {
        font-size: 13px;
        max-width: 360px;
        margin: 0 auto;
        line-height: 1.6;
    }
</style>
@endsection

@section('content')
@php
    $user = Auth::user();
    $hasValidatorRole = $user->isSuperAdmin() || $user->isRegionalUser() || $user->isProvincialUser();

    // Module icon mapper based on label/key
    $moduleIcons = [
        'fund-utilization' => 'fas fa-coins',
        'pre-implementation' => 'fas fa-file-signature',
        'road-maintenance-status' => 'fas fa-road',
        'rbis-annual-certification' => 'fas fa-award',
        'local-project-monitoring-committee' => 'fas fa-users-cog',
        'lgsf-pcr' => 'fas fa-file-invoice',
        'sglgif-pcr' => 'fas fa-file-invoice-dollar',
        'annual-maintenance-work-program' => 'fas fa-calendar-alt',
        'pd-no-pbbm-2025-1572-1573' => 'fas fa-file-invoice',
        'monitoring-evaluation-lfp' => 'fas fa-file-lines',
        'monitoring-evaluation-rlip-lime' => 'fas fa-chart-line',
        'swa-annex-f' => 'fas fa-file-invoice',
    ];
@endphp

<div class="task-mgmt-page">
    <div class="task-mgmt-shell">
        <!-- Hero Header -->
        <section class="task-mgmt-hero">
            <div class="task-mgmt-hero-content">
                <div class="task-mgmt-eyebrow">
                    <i class="fas fa-list-check"></i>
                    Workflow Tasks
                </div>
                <h1 class="task-mgmt-title">Task Management Hub</h1>
                <p class="task-mgmt-description">
                    Track the accomplishments, review pending uploads, and action documents that have been returned for revision. Click a program card to open its task list.
                </p>
            </div>
        </section>

        <!-- Pool Section 1: CENTRALIZED APPROVALS POOL (Only visible to validators) -->
        @if($hasValidatorRole)
            <div class="task-pool-section">
                <h2 class="task-pool-section-title">
                    <i class="fas fa-inbox"></i>
                    @if($user->isRegionalUser())
                        <span>Centralized Regional Approvals Pool</span>
                    @elseif($user->isProvincialUser())
                        <span>Provincial Approvals Pool</span>
                    @else
                        <span>All Pending System Approvals</span>
                    @endif
                </h2>
                
                @if($pendingByModule->isNotEmpty())
                    <div class="menu-cards-grid">
                        @foreach($pendingByModule as $moduleLabel => $tasks)
                            @php
                                $firstTask = $tasks->first();
                                $moduleKey = $firstTask['module_key'] ?? 'default';
                                $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
                                $slug = 'modal-pending-' . Str::slug($moduleLabel);
                            @endphp
                            <div class="menu-card" onclick="openTaskModal('{{ $slug }}')">
                                <div class="menu-card-top">
                                    <div class="menu-card-icon-wrapper primary">
                                        <i class="{{ $iconClass }}"></i>
                                    </div>
                                    <span class="menu-card-badge danger">{{ $tasks->count() }}</span>
                                </div>
                                <div>
                                    <h3 class="menu-card-title">{{ $moduleLabel }}</h3>
                                    <div class="menu-card-footer primary">
                                        Open Pool <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                        <div class="empty-icon"><i class="fas fa-circle-check" style="color: #10b981;"></i></div>
                        <div class="empty-title">All Caught Up!</div>
                        <p class="empty-desc">There are no document submissions waiting for your approval in this pool.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Pool Section 3: UNASSIGNED TICKETS POOL (Visible to validators/agents) -->
        @if(($user->isSuperAdmin() || $user->isRegionalUser() || $user->isProvincialUser()))
            <div class="task-pool-section" style="margin-top: 14px;">
                <h2 class="task-pool-section-title">
                    <i class="fas fa-ticket"></i>
                    <span>Unassigned Tickets Pool</span>
                </h2>
                
                @if($ticketsByCategory->isNotEmpty())
                    <div class="menu-cards-grid">
                        @foreach($ticketsByCategory as $categoryName => $categoryTickets)
                            @php
                                $slug = 'modal-ticket-' . Str::slug($categoryName);
                            @endphp
                            <div class="menu-card" onclick="openTaskModal('{{ $slug }}')">
                                <div class="menu-card-top">
                                    <div class="menu-card-icon-wrapper info" style="background: #e0f2fe; color: #0284c7;">
                                        <i class="fas fa-ticket"></i>
                                    </div>
                                    <span class="menu-card-badge info" style="background: #0284c7; color: #fff;">{{ $categoryTickets->count() }}</span>
                                </div>
                                <div>
                                    <h3 class="menu-card-title">{{ $categoryName }}</h3>
                                    <div class="menu-card-footer info" style="color: #0284c7;">
                                        Open Pool <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                        <div class="empty-icon"><i class="fas fa-circle-check" style="color: #10b981;"></i></div>
                        <div class="empty-title">All Caught Up!</div>
                        <p class="empty-desc">There are no unassigned tickets waiting in your queue.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Pool Section 2: RETURNED DOCUMENTS (Visible to all uploaders, e.g., LGUs) -->
        <div class="task-pool-section" style="margin-top: 14px;">
            <h2 class="task-pool-section-title">
                <i class="fas fa-rotate-left"></i>
                <span>Returned Documents Awaiting Action</span>
            </h2>
            
            @if($returnedByModule->isNotEmpty())
                <div class="menu-cards-grid">
                    @foreach($returnedByModule as $moduleLabel => $tasks)
                        @php
                            $firstTask = $tasks->first();
                            $moduleKey = $firstTask['module_key'] ?? 'default';
                            $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
                            $slug = 'modal-returned-' . Str::slug($moduleLabel);
                        @endphp
                        <div class="menu-card" onclick="openTaskModal('{{ $slug }}')">
                            <div class="menu-card-top">
                                <div class="menu-card-icon-wrapper danger">
                                    <i class="{{ $iconClass }}"></i>
                                </div>
                                <span class="menu-card-badge danger">{{ $tasks->count() }}</span>
                            </div>
                            <div>
                                <h3 class="menu-card-title">{{ $moduleLabel }}</h3>
                                <div class="menu-card-footer danger">
                                    Act & Resubmit <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                    <div class="empty-icon"><i class="fas fa-thumbs-up" style="color: #3b82f6;"></i></div>
                    <div class="empty-title">No Returned Documents</div>
                    <p class="empty-desc">Great job! You have no document submissions that are returned or pending revisions.</p>
                </div>
            @endif
        </div>

    </div>
</div>

<!-- MODAL DIALOGS CONTAINER -->
<!-- A. Approvals Modals -->
@if($hasValidatorRole && $pendingByModule->isNotEmpty())
    @foreach($pendingByModule as $moduleLabel => $tasks)
        @php
            $firstTask = $tasks->first();
            $moduleKey = $firstTask['module_key'] ?? 'default';
            $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
            $slug = 'modal-pending-' . Str::slug($moduleLabel);
            $provinces = $tasks->pluck('province')->filter()->unique()->sort();
            $periods = $tasks->pluck('period')->filter()->unique()->sort();
            $years = $tasks->pluck('year')->filter()->unique()->sortDesc();
        @endphp
        <div id="{{ $slug }}" class="task-modal-backdrop" onclick="closeTaskModalOnBackdrop(event, '{{ $slug }}')">
            <div class="task-modal">
                <!-- Modal Header -->
                <div class="task-modal-header">
                    <h3 class="task-modal-title">
                        <i class="{{ $iconClass }}"></i>
                        <span>{{ $moduleLabel }} - Approvals Pool</span>
                    </h3>
                    <button class="task-modal-close" onclick="closeTaskModal('{{ $slug }}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Bar inside Modal -->
                <div class="task-modal-filter-bar">
                    @if($provinces->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="province" aria-label="Filter by province">
                                <option value="">All Provinces</option>
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($periods->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-calendar-days" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="period" aria-label="Filter by period">
                                <option value="">All Periods</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period }}">{{ $period }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($years->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="year" aria-label="Filter by year">
                                <option value="">All Years</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="modal-filter-actions">
                        <button type="button" class="modal-filter-btn apply" onclick="filterModalRows('{{ $slug }}')">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button type="button" class="modal-filter-btn reset" onclick="resetModalFilters('{{ $slug }}')">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="task-modal-body">
                    <div class="table-container">
                        <table class="task-table">
                            <thead>
                                <tr>
                                    @if($moduleKey === 'pre-implementation')
                                        <th>Project Code</th>
                                        <th>Province</th>
                                        <th>City/Municipality</th>
                                    @elseif($moduleKey === 'monitoring-evaluation-lfp')
                                        <th>Province</th>
                                    @elseif($moduleKey === 'local-project-monitoring-committee')
                                        <th>Province</th>
                                        <th>City/Municipality</th>
                                    @else
                                        <th>Office</th>
                                    @endif
                                    <th>Period</th>
                                    <th>Task Details</th>
                                    <th>Submitted By</th>
                                    <th>Date Received</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $task)
                                    <tr data-province="{{ $task['province'] ?? '' }}" data-period="{{ $task['period'] ?? $task['quarter'] ?? '' }}" data-year="{{ $task['year'] ?? '' }}">
                                        @if($moduleKey === 'pre-implementation')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_code'] ?? '' }}">{{ $task['project_code'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @elseif($moduleKey === 'monitoring-evaluation-lfp')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                        @elseif($moduleKey === 'local-project-monitoring-committee')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @else
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @endif
                                        <td>
                                            <strong>{{ strtoupper($task['quarter'] ?? 'N/A') }}</strong>
                                        </td>
                                        <td>
                                            <div class="task-message-cell">
                                                <div style="font-weight: 700; color: #1e293b;">{{ $task['task_title'] ?? ($task['document_label'] ?? $task['message']) }}</div>
                                                @if(isset($task['task_title']))
                                                    <div style="margin-top: 4px; color: #64748b;">{{ $task['message'] }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $uploaderName = $task['sender_name'] ?: 'Unknown uploader';
                                            @endphp
                                            <div class="task-uploader-cell">
                                                <span>
                                                    <span class="task-uploader-name">{{ $uploaderName }}</span>
                                                    @if(!empty($task['uploader_province']))
                                                        <span class="task-uploader-province"><i class="fas fa-map-marker-alt"></i> {{ $task['uploader_province'] }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ !empty($task['created_at']) ? \Illuminate\Support\Carbon::parse($task['created_at'])->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td>
                                            <a href="{{ $task['url'] }}" class="task-action-btn">
                                                <i class="fas fa-clipboard-check"></i>
                                                Review & Validate
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- B. Returned Modals -->
@if($returnedByModule->isNotEmpty())
    @foreach($returnedByModule as $moduleLabel => $tasks)
        @php
            $firstTask = $tasks->first();
            $moduleKey = $firstTask['module_key'] ?? 'default';
            $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
            $slug = 'modal-returned-' . Str::slug($moduleLabel);
            $provinces = $tasks->pluck('province')->filter()->unique()->sort();
        @endphp
        <div id="{{ $slug }}" class="task-modal-backdrop" onclick="closeTaskModalOnBackdrop(event, '{{ $slug }}')">
            <div class="task-modal">
                <!-- Modal Header -->
                <div class="task-modal-header">
                    <h3 class="task-modal-title">
                        <i class="{{ $iconClass }}" style="color: #dc2626;"></i>
                        <span>{{ $moduleLabel }} - Returned Documents</span>
                    </h3>
                    <button class="task-modal-close" onclick="closeTaskModal('{{ $slug }}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Bar inside Modal -->
                <div class="task-modal-filter-bar">
                    <div class="filter-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" class="modal-search-input" placeholder="Search project or remarks..." oninput="filterModalRows('{{ $slug }}')">
                    </div>
                    @if($provinces->isNotEmpty())
                        <select class="modal-select-filter" onchange="filterModalRows('{{ $slug }}')">
                            <option value="">All Provinces</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov }}">{{ $prov }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Modal Body -->
                <div class="task-modal-body">
                    <div class="table-container">
                        <table class="task-table">
                            <thead>
                                <tr>
                                    <th>Project / Location</th>
                                    <th>Period</th>
                                    <th>Instructions / Remarks</th>
                                    <th>Returned By</th>
                                    <th>Date Returned</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $task)
                                    <tr data-province="{{ $task['province'] ?? '' }}">
                                        <td>
                                            <div class="proj-title-cell" title="{{ $task['project_code'] }}">{{ $task['project_code'] }}</div>
                                            <div class="proj-meta-cell">
                                                @if(!empty($task['province']))
                                                    <span><i class="fas fa-map-marker-alt"></i> {{ $task['province'] }}</span>
                                                @endif
                                                @if(!empty($task['city_municipality']))
                                                    <span>&bull;</span>
                                                    <span>{{ $task['city_municipality'] }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ strtoupper($task['quarter'] ?? 'N/A') }}</strong>
                                        </td>
                                        <td>
                                            <div class="task-message-cell" style="font-style: italic;">{{ $task['message'] }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;">{{ $task['sender_name'] ?: 'System' }}</div>
                                        </td>
                                        <td>
                                            {{ !empty($task['created_at']) ? \Illuminate\Support\Carbon::parse($task['created_at'])->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td>
                                            <a href="{{ url('/notifications/' . $task['id'] . '/read') }}" class="task-action-btn btn-danger">
                                                <i class="fas fa-edit"></i>
                                                Act & Resubmit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- C. Ticket Modals -->
@if(($user->isSuperAdmin() || $user->isRegionalUser() || $user->isProvincialUser()) && $ticketsByCategory->isNotEmpty())
    @foreach($ticketsByCategory as $categoryName => $categoryTickets)
        @php
            $slug = 'modal-ticket-' . Str::slug($categoryName);
            $priorities = $categoryTickets->pluck('priority')->filter()->unique()->sort();
            $provinces = $categoryTickets->pluck('province_scope')->filter()->unique()->sort();
        @endphp
        <div id="{{ $slug }}" class="task-modal-backdrop" onclick="closeTaskModalOnBackdrop(event, '{{ $slug }}')">
            <div class="task-modal" style="max-width: 900px; width: 90%;">
                <!-- Modal Header -->
                <div class="task-modal-header">
                    <h3 class="task-modal-title">
                        <i class="fas fa-ticket"></i>
                        <span>{{ $categoryName }} - Ticket Pool</span>
                    </h3>
                    <button class="task-modal-close" onclick="closeTaskModal('{{ $slug }}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Bar inside Modal -->
                <div class="task-modal-filter-bar">
                    @if($provinces->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="province" aria-label="Filter by province">
                                <option value="">All Provinces</option>
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($priorities->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="priority" aria-label="Filter by priority">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority }}">{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="modal-filter-actions">
                        <button type="button" class="modal-filter-btn apply" onclick="filterModalRows('{{ $slug }}')">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button type="button" class="modal-filter-btn reset" onclick="resetModalFilters('{{ $slug }}')">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="task-modal-body">
                    <div class="table-container">
                        <table class="task-table">
                            <thead>
                                <tr>
                                    <th>Ticket No.</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Submitted By</th>
                                    <th>Date Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryTickets as $ticket)
                                    <tr data-province="{{ $ticket->province_scope ?? '' }}" data-priority="{{ $ticket->priority ?? '' }}">
                                        <td>
                                            <div class="proj-title-cell">
                                                <a href="{{ route('ticketing.show', $ticket) }}" style="font-weight: 700; color: #1e3a8a; text-decoration: underline;">
                                                    {{ $ticket->ticket_number }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="task-message-cell">
                                                <div style="font-weight: 700; color: #1e293b;">{{ $ticket->title }}</div>
                                                <div style="margin-top: 4px; color: #64748b; font-size: 12px;">{{ \Illuminate\Support\Str::limit($ticket->description, 80) }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="ticketing-badge" style="background: {{ $ticket->priority_color }}; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                                {{ $ticket->priority }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="task-uploader-cell">
                                                <span>
                                                    <span class="task-uploader-name">{{ $ticket->submitter?->fullName() ?? 'N/A' }}</span>
                                                    @if($ticket->province_scope)
                                                        <span class="task-uploader-province"><i class="fas fa-map-marker-alt"></i> {{ $ticket->province_scope }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ optional($ticket->date_submitted ?? $ticket->created_at)->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                @php
                                                    $acceptRoute = '';
                                                    if ($ticket->current_level === \App\Models\Ticket::LEVEL_PROVINCIAL) {
                                                        $acceptRoute = route('ticketing.province.accept', $ticket);
                                                    } elseif ($ticket->current_level === \App\Models\Ticket::LEVEL_REGIONAL) {
                                                        $acceptRoute = route('ticketing.region.accept', $ticket);
                                                    }
                                                @endphp
                                                @if($acceptRoute)
                                                    <form method="POST" action="{{ $acceptRoute }}" style="margin: 0;">
                                                        @csrf
                                                        <button type="submit" class="task-action-btn" style="background: #10b981; border: none; cursor: pointer; color: #fff;">
                                                            <i class="fas fa-hand"></i> Accept
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('ticketing.show', $ticket) }}" class="task-action-btn" style="background: #64748b; color: #fff;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<script>
    function openTaskModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus on search input automatically
            const searchInput = modal.querySelector('.modal-search-input');
            if (searchInput) searchInput.focus();
        }
    }

    // Reset filters and inputs when closing the modal
    function closeTaskModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';

            resetModalFilters(id);
        }
    }

    function resetModalFilters(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const searchInput = modal.querySelector('.modal-search-input');
        if (searchInput) searchInput.value = '';
        modal.querySelectorAll('.modal-select-filter').forEach(select => select.value = '');
        modal.querySelectorAll('.task-table tbody tr').forEach(row => row.style.display = '');
    }

    function closeTaskModalOnBackdrop(event, id) {
        if (event.target === event.currentTarget) {
            closeTaskModal(id);
        }
    }

    function filterModalRows(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const searchInput = modal.querySelector('.modal-search-input');
        const searchVal = searchInput ? searchInput.value.toLowerCase() : '';
        const provinceSelect = modal.querySelector('[data-filter="province"]');
        const periodSelect = modal.querySelector('[data-filter="period"]');
        const yearSelect = modal.querySelector('[data-filter="year"]');
        const prioritySelect = modal.querySelector('[data-filter="priority"]');
        
        const provinceVal = provinceSelect ? provinceSelect.value.toLowerCase() : '';
        const periodVal = periodSelect ? periodSelect.value.toLowerCase() : '';
        const yearVal = yearSelect ? yearSelect.value.toLowerCase() : '';
        const priorityVal = prioritySelect ? prioritySelect.value.toLowerCase() : '';
        
        const rows = modal.querySelectorAll('.task-table tbody tr');
        rows.forEach(row => {
            const projTitleEl = row.querySelector('.proj-title-cell');
            const projCode = projTitleEl ? projTitleEl.innerText.toLowerCase() : '';
            const projMetaElement = row.querySelector('.proj-meta-cell');
            const projMeta = projMetaElement ? projMetaElement.innerText.toLowerCase() : '';
            const messageEl = row.querySelector('.task-message-cell');
            const details = messageEl ? messageEl.innerText.toLowerCase() : '';
            
            const provinceText = row.dataset.province ? row.dataset.province.toLowerCase() : '';
            const periodText = row.dataset.period ? row.dataset.period.toLowerCase() : '';
            const yearText = row.dataset.year ? row.dataset.year.toLowerCase() : '';
            const priorityText = row.dataset.priority ? row.dataset.priority.toLowerCase() : '';
            
            const matchesSearch = projCode.includes(searchVal) || projMeta.includes(searchVal) || details.includes(searchVal);
            const matchesProvince = provinceVal === '' || provinceText === provinceVal;
            const matchesPeriod = periodVal === '' || periodText === periodVal;
            const matchesYear = yearVal === '' || yearText === yearVal;
            const matchesPriority = priorityVal === '' || priorityText === priorityVal;
            
            if (matchesSearch && matchesProvince && matchesPeriod && matchesYear && matchesPriority) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Close modals on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.task-modal-backdrop[style*="display: flex"]');
            openModals.forEach(modal => {
                closeTaskModal(modal.id);
            });
        }
    });
</script>
@endsection
