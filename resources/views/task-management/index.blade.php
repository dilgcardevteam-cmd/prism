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
        max-width: 1000px;
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
        padding: 9px 30px 9px 12px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        color: #1e293b;
        outline: none;
        background-color: #fff;
        cursor: pointer;
        min-width: 160px;
    }

    .modal-select-filter:focus {
        border-color: #1d4ed8;
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
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .task-table th {
        background: #fff;
        padding: 12px 24px;
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
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
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
                    <div class="filter-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" class="modal-search-input" placeholder="Search project or details..." oninput="filterModalRows('{{ $slug }}')">
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
                                    <th>Task Details</th>
                                    <th>Submitted By</th>
                                    <th>Date Received</th>
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
                                            <div class="task-message-cell">{{ $task['message'] }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;">{{ $task['sender_name'] ?: 'System' }}</div>
                                        </td>
                                        <td>
                                            {{ !empty($task['created_at']) ? \Illuminate\Support\Carbon::parse($task['created_at'])->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td>
                                            <a href="{{ url('/notifications/' . $task['id'] . '/read') }}" class="task-action-btn">
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

            const searchInput = modal.querySelector('.modal-search-input');
            const selectEl = modal.querySelector('.modal-select-filter');
            if (searchInput) searchInput.value = '';
            if (selectEl) selectEl.value = '';

            // Reset rows display
            const rows = modal.querySelectorAll('.task-table tbody tr');
            rows.forEach(row => row.style.display = '');
        }
    }

    function closeTaskModalOnBackdrop(event, id) {
        if (event.target === event.currentTarget) {
            closeTaskModal(id);
        }
    }

    function filterModalRows(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const searchVal = modal.querySelector('.modal-search-input').value.toLowerCase();
        const selectEl = modal.querySelector('.modal-select-filter');
        const provinceVal = selectEl ? selectEl.value.toLowerCase() : '';
        
        const rows = modal.querySelectorAll('.task-table tbody tr');
        rows.forEach(row => {
            const projCode = row.querySelector('.proj-title-cell').innerText.toLowerCase();
            const projMeta = row.querySelector('.proj-meta-cell').innerText.toLowerCase();
            const details = row.querySelector('.task-message-cell').innerText.toLowerCase();
            const provinceText = row.dataset.province ? row.dataset.province.toLowerCase() : '';
            
            const matchesSearch = projCode.includes(searchVal) || projMeta.includes(searchVal) || details.includes(searchVal);
            const matchesProvince = provinceVal === '' || provinceText === provinceVal;
            
            if (matchesSearch && matchesProvince) {
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
