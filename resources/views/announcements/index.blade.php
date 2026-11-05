@extends('layouts.dashboard')

@section('title', 'Announcements & Advisories')
@section('page-title', 'Announcements')

@section('content')
<div class="announcements-container">
    {{-- Header Banner --}}
    <div class="announcements-header-card">
        <div class="announcements-header-content">
            <div class="announcements-header-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="announcements-header-text">
                <h1>Announcements & Advisories</h1>
                <p>Official notices, circulars, system maintenance advisories, and policy updates across PRISM.</p>
            </div>
        </div>

        <div class="announcements-header-actions">
            @if($canManage)
                <button type="button" class="btn-announcement primary" onclick="openCreateAnnouncementModal()">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Announcement</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="announcement-alert success">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="announcement-alert error">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="announcement-alert error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Please resolve the following errors:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Tabs (if manager) --}}
    @if($canManage)
        <div class="announcement-nav-tabs">
            <a href="{{ route('announcements.index', ['tab' => 'feed']) }}" class="announcement-tab-link {{ $activeTab === 'feed' ? 'active' : '' }}">
                <i class="fas fa-newspaper"></i>
                <span>Announcements Feed</span>
                <span class="tab-badge">{{ $feedAnnouncements->total() }}</span>
            </a>
            <a href="{{ route('announcements.index', ['tab' => 'manage']) }}" class="announcement-tab-link {{ $activeTab === 'manage' ? 'active' : '' }}">
                <i class="fas fa-tasks"></i>
                <span>Management Center</span>
                @if(isset($stats['total']))
                    <span class="tab-badge">{{ $stats['total'] }}</span>
                @endif
            </a>
        </div>
    @endif

    @if($activeTab === 'feed')
        {{-- ================= FEED VIEW ================= --}}
        <div class="announcement-toolbar">
            <form method="GET" action="{{ route('announcements.index') }}" class="announcement-filter-form">
                <input type="hidden" name="tab" value="feed">
                <div class="announcement-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search announcements by title or content...">
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="type" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        @foreach(\App\Modules\Announcements\Models\Announcement::typeOptions() as $k => $label)
                            <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="priority" onchange="this.form.submit()">
                        <option value="all">All Priorities</option>
                        @foreach(\App\Modules\Announcements\Models\Announcement::priorityOptions() as $k => $label)
                            <option value="{{ $k }}" {{ request('priority') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="read_state" onchange="this.form.submit()">
                        <option value="all" {{ request('read_state') === 'all' || !request('read_state') ? 'selected' : '' }}>All Read Status</option>
                        <option value="unread" {{ request('read_state') === 'unread' ? 'selected' : '' }}>Unread Only</option>
                        <option value="read" {{ request('read_state') === 'read' ? 'selected' : '' }}>Read Only</option>
                    </select>
                </div>

                <button type="submit" class="btn-announcement secondary">
                    <i class="fas fa-filter"></i> Filter
                </button>

                @if(request()->hasAny(['search', 'type', 'priority', 'read_state']))
                    <a href="{{ route('announcements.index', ['tab' => 'feed']) }}" class="btn-announcement subtle" title="Reset Filters">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                @endif
            </form>

            <button type="button" class="btn-announcement subtle" id="pageMarkAllReadBtn" onclick="markAllAnnouncementsReadGlobally()">
                <i class="fas fa-check-double"></i>
                <span>Mark All as Read</span>
            </button>
        </div>

        @if($feedAnnouncements->isEmpty())
            <div class="announcement-empty-state">
                <div class="empty-icon"><i class="fas fa-bullhorn"></i></div>
                <h3>No Announcements Found</h3>
                <p>There are no active announcements matching your selected filters at this time.</p>
                @if(request()->hasAny(['search', 'type', 'priority', 'read_state']))
                    <a href="{{ route('announcements.index', ['tab' => 'feed']) }}" class="btn-announcement secondary" style="margin-top: 12px;">Clear Filters</a>
                @endif
            </div>
        @else
            <div class="announcements-grid">
                @foreach($feedAnnouncements as $item)
                    @php
                        $isRead = $item->isReadByUser(Auth::user());
                        $typeBadge = $item->typeBadge();
                        $priorityBadge = $item->priorityBadge();
                    @endphp
                    <div class="announcement-card {{ $item->is_pinned ? 'is-pinned' : '' }} {{ !$isRead ? 'is-unread' : '' }}" id="announcement-card-{{ $item->id }}">
                        @if($item->is_pinned)
                            <div class="pinned-ribbon" title="Pinned Announcement">
                                <i class="fas fa-thumbtack"></i> Pinned
                            </div>
                        @endif

                        <div class="card-header-row">
                            <div class="badges-group">
                                <span class="type-pill" style="background: {{ $typeBadge['bg'] }}; color: {{ $typeBadge['color'] }}; border: 1px solid {{ $typeBadge['border'] }};">
                                    <i class="fas {{ $typeBadge['icon'] }}"></i>
                                    {{ $typeBadge['label'] }}
                                </span>
                                @if($item->priority === \App\Modules\Announcements\Models\Announcement::PRIORITY_URGENT || $item->priority === \App\Modules\Announcements\Models\Announcement::PRIORITY_HIGH)
                                    <span class="priority-pill" style="background: {{ $priorityBadge['bg'] }}; color: {{ $priorityBadge['color'] }};">
                                        {{ $priorityBadge['label'] }}
                                    </span>
                                @endif
                            </div>

                            @if(!$isRead)
                                <span class="unread-dot" title="Unread announcement" id="card-dot-{{ $item->id }}"></span>
                            @endif
                        </div>

                        <h3 class="card-title">
                            <a href="javascript:void(0)" onclick="openAnnouncementReaderModal({{ $item->id }})">
                                {{ $item->title }}
                            </a>
                        </h3>

                        @if($item->hasDeadline())
                            @php $dlBadge = $item->deadlineBadge(); @endphp
                            @if($dlBadge)
                                <div class="card-deadline-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 12px; margin-bottom: 10px; border-radius: 8px; font-size: 11px; font-weight: 600; background: {{ $dlBadge['bg'] }}; color: {{ $dlBadge['color'] }}; border: 1px solid {{ $dlBadge['border'] }};">
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas {{ $dlBadge['icon'] }}"></i>
                                        <span>{{ $dlBadge['label'] }}</span>
                                    </span>
                                    <span style="font-weight: 500; font-size: 10px; opacity: 0.9;">{{ $dlBadge['relative'] }}</span>
                                </div>
                            @endif
                        @endif

                        <div class="card-meta">
                            <span><i class="fas fa-user-edit"></i> {{ $item->creator ? $item->creator->fname . ' ' . $item->creator->lname : 'System Admin' }}</span>
                            <span><i class="far fa-clock"></i> {{ $item->published_at ? $item->published_at->format('M d, Y h:i A') : 'Immediately' }}</span>
                            <span><i class="fas fa-users"></i> {{ $item->audienceDescription() }}</span>
                        </div>

                        <div class="card-body-snippet">
                            {{ \Illuminate\Support\Str::limit(strip_tags($item->content), 180) }}
                        </div>

                        <div class="card-footer">
                            <div class="footer-links">
                                <button type="button" class="card-action-btn view" onclick="openAnnouncementReaderModal({{ $item->id }})">
                                    <i class="fas fa-book-open"></i> Read Notice
                                </button>
                                @if($item->attachment_path)
                                    <a href="{{ route('announcements.download', $item->id) }}" class="card-action-btn download" title="Download {{ $item->attachment_name }}">
                                        <i class="fas fa-paperclip"></i> Attachment
                                    </a>
                                @endif
                                @if($item->action_url)
                                    <a href="{{ $item->action_url }}" target="_blank" rel="noopener noreferrer" class="card-action-btn link" title="{{ $item->action_text ?: 'Open Link' }}">
                                        <i class="fas fa-external-link-alt"></i> {{ $item->action_text ?: 'Open Link' }}
                                    </a>
                                @endif
                            </div>

                            @if($canManage)
                                <div class="admin-quick-actions">
                                    <button type="button" class="icon-btn edit" onclick="openEditAnnouncementModal({{ $item->id }})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="announcements-pagination">
                {{ $feedAnnouncements->links() }}
            </div>
        @endif

    @else
        {{-- ================= MANAGEMENT VIEW ================= --}}
        @if($stats)
            <div class="announcement-stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">{{ number_format($stats['total']) }}</span>
                        <span class="stat-label">Total Announcements</span>
                    </div>
                </div>
                <div class="stat-card published">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">{{ number_format($stats['published']) }}</span>
                        <span class="stat-label">Published / Active</span>
                    </div>
                </div>
                <div class="stat-card draft">
                    <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">{{ number_format($stats['draft']) }}</span>
                        <span class="stat-label">Drafts</span>
                    </div>
                </div>
                <div class="stat-card urgent">
                    <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">{{ number_format($stats['urgent']) }}</span>
                        <span class="stat-label">Urgent Priority</span>
                    </div>
                </div>
                <div class="stat-card reads">
                    <div class="stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">{{ number_format($stats['total_reads']) }}</span>
                        <span class="stat-label">Total User Reads</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="announcement-toolbar">
            <form method="GET" action="{{ route('announcements.index') }}" class="announcement-filter-form">
                <input type="hidden" name="tab" value="manage">
                <div class="announcement-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="manage_search" value="{{ request('manage_search') }}" placeholder="Search announcements...">
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="status" onchange="this.form.submit()">
                        <option value="all">All Statuses</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="type" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        @foreach(\App\Modules\Announcements\Models\Announcement::typeOptions() as $k => $label)
                            <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="priority" onchange="this.form.submit()">
                        <option value="all">All Priorities</option>
                        @foreach(\App\Modules\Announcements\Models\Announcement::priorityOptions() as $k => $label)
                            <option value="{{ $k }}" {{ request('priority') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="announcement-filter-select-wrap">
                    <select name="province" onchange="this.form.submit()">
                        <option value="all">All CAR Provinces</option>
                        @foreach($availableProvinces as $prov)
                            <option value="{{ $prov }}" {{ request('province') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-announcement secondary">
                    <i class="fas fa-filter"></i> Filter
                </button>

                @if(request()->hasAny(['manage_search', 'status', 'type', 'priority', 'province']))
                    <a href="{{ route('announcements.index', ['tab' => 'manage']) }}" class="btn-announcement subtle" title="Reset Filters">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                @endif
            </form>

            <button type="button" class="btn-announcement primary" onclick="openCreateAnnouncementModal()">
                <i class="fas fa-plus-circle"></i> Create Announcement
            </button>
        </div>

        @if(!$managementAnnouncements || $managementAnnouncements->isEmpty())
            <div class="announcement-empty-state">
                <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                <h3>No Announcements Recorded</h3>
                <p>Create your first announcement to notify PRISM users of important updates.</p>
                <button type="button" class="btn-announcement primary" style="margin-top: 14px;" onclick="openCreateAnnouncementModal()">
                    <i class="fas fa-plus"></i> Create Now
                </button>
            </div>
        @else
            <div class="announcement-table-container">
                <table class="announcement-table">
                    <thead>
                        <tr>
                            <th style="width: 32%;">Announcement</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Published Date</th>
                            <th>Reads</th>
                            <th style="text-align: right; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($managementAnnouncements as $announcement)
                            @php
                                $typeBadge = $announcement->typeBadge();
                                $priorityBadge = $announcement->priorityBadge();
                            @endphp
                            <tr>
                                <td>
                                    <div class="table-title-cell">
                                        @if($announcement->is_pinned)
                                            <span class="table-pinned-icon" title="Pinned Announcement"><i class="fas fa-thumbtack"></i></span>
                                        @endif
                                        <div>
                                            <a href="javascript:void(0)" class="table-announcement-link" onclick="openAnnouncementReaderModal({{ $announcement->id }})">
                                                {{ $announcement->title }}
                                            </a>
                                            <div class="table-author-sub">
                                                By {{ $announcement->creator ? $announcement->creator->fname . ' ' . $announcement->creator->lname : 'Administrator' }}
                                                @if($announcement->attachment_path)
                                                    &bull; <i class="fas fa-paperclip"></i> Attached
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-pill" style="background: {{ $typeBadge['bg'] }}; color: {{ $typeBadge['color'] }}; border: 1px solid {{ $typeBadge['border'] }};">
                                        <i class="fas {{ $typeBadge['icon'] }}"></i> {{ $typeBadge['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-pill" style="background: {{ $priorityBadge['bg'] }}; color: {{ $priorityBadge['color'] }};">
                                        {{ $priorityBadge['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="audience-text" title="{{ $announcement->audienceDescription() }}">
                                        {{ $announcement->audienceDescription() }}
                                    </span>
                                </td>
                                <td>
                                    @if($announcement->status === \App\Modules\Announcements\Models\Announcement::STATUS_PUBLISHED)
                                        @if($announcement->isExpired())
                                            <span class="status-pill expired">Expired</span>
                                        @else
                                            <span class="status-pill published">Published</span>
                                        @endif
                                    @elseif($announcement->status === \App\Modules\Announcements\Models\Announcement::STATUS_DRAFT)
                                        <span class="status-pill draft">Draft</span>
                                    @else
                                        <span class="status-pill archived">Archived</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="date-text">
                                        {{ $announcement->published_at ? $announcement->published_at->format('M d, Y') : 'Draft' }}
                                        <small>{{ $announcement->published_at ? $announcement->published_at->format('h:i A') : '' }}</small>
                                    </div>
                                    @if($announcement->hasDeadline())
                                        @php $tblDl = $announcement->deadlineBadge(); @endphp
                                        @if($tblDl)
                                            <div style="margin-top: 4px;">
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: {{ $tblDl['bg'] }}; color: {{ $tblDl['color'] }}; border: 1px solid {{ $tblDl['border'] }};" title="{{ $tblDl['formatted'] }}">
                                                    <i class="fas {{ $tblDl['icon'] }}"></i> {{ $tblDl['label'] }}
                                                </span>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="read-count-pill" onclick="openReadersModal({{ $announcement->id }})" title="View who read this notice">
                                        <i class="fas fa-eye"></i> {{ number_format($announcement->reads_count ?? 0) }}
                                    </button>
                                </td>
                                <td>
                                    <div class="table-actions-cell">
                                        <button type="button" class="btn-table-icon" onclick="openAnnouncementReaderModal({{ $announcement->id }})" title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn-table-icon" onclick="openEditAnnouncementModal({{ $announcement->id }})" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <form method="POST" action="{{ route('announcements.toggle-status', $announcement->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn-table-icon" title="{{ $announcement->status === 'published' ? 'Revert to Draft' : 'Publish' }}">
                                                <i class="fas {{ $announcement->status === 'published' ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn-table-icon danger" onclick="confirmDeleteAnnouncement({{ $announcement->id }}, '{{ addslashes($announcement->title) }}')" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="announcements-pagination">
                {{ $managementAnnouncements->links() }}
            </div>
        @endif
    @endif
</div>

{{-- ================= MODALS ================= --}}

{{-- 1. Create Announcement Modal --}}
@if($canManage)
<div id="createAnnouncementModal" class="announcement-modal" aria-hidden="true">
    <div class="announcement-modal-backdrop" onclick="closeCreateAnnouncementModal()"></div>
    <div class="announcement-modal-dialog">
        <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data" id="createAnnouncementForm">
            @csrf
            <div class="announcement-modal-header">
                <div>
                    <h3 class="modal-title"><i class="fas fa-bullhorn"></i> Create New Announcement</h3>
                    <p class="modal-subtitle">Publish system advisories, policy updates, or operational notices for PRISM users.</p>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeCreateAnnouncementModal()">&times;</button>
            </div>

            <div class="announcement-modal-body">
                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="create_title">Announcement Title <span class="required">*</span></label>
                        <input type="text" name="title" id="create_title" class="form-control" placeholder="e.g. System Maintenance Scheduled for Nov 10, 2026" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="create_type">Category / Type <span class="required">*</span></label>
                        <select name="type" id="create_type" class="form-control" required>
                            @foreach(\App\Modules\Announcements\Models\Announcement::typeOptions() as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-6">
                        <label for="create_priority">Priority Level <span class="required">*</span></label>
                        <select name="priority" id="create_priority" class="form-control" required>
                            @foreach(\App\Modules\Announcements\Models\Announcement::priorityOptions() as $val => $lbl)
                                <option value="{{ $val }}" {{ $val === 'normal' ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label>Target Audience <span class="required">*</span></label>
                        <div class="audience-radio-group">
                            <label class="radio-label">
                                <input type="radio" name="target_audience" value="all" checked onchange="handleAudienceTypeChange('create', 'all')">
                                <span>All PRISM Users</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="target_audience" value="role" onchange="handleAudienceTypeChange('create', 'role')">
                                <span>Specific User Roles</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="target_audience" value="province" onchange="handleAudienceTypeChange('create', 'province')">
                                <span>Specific Provinces (CAR)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Role Selection (hidden by default) --}}
                <div class="form-row audience-sub-section" id="create_roles_section" style="display: none;">
                    <div class="form-group col-12">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <label style="margin: 0; font-weight: 600;"><i class="fas fa-users-cog" style="color: #002C76;"></i> Select Target Roles:</label>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('create_roles_grid', true)">Select All</button>
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('create_roles_grid', false)">Clear</button>
                            </div>
                        </div>
                        <div class="checkbox-grid" id="create_roles_grid">
                            @foreach($availableRoles as $roleKey => $roleLabel)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="target_roles[]" value="{{ $roleKey }}">
                                    <span>{{ $roleLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Province Selection (CAR) (hidden by default) --}}
                <div class="form-row audience-sub-section" id="create_provinces_section" style="display: none;">
                    <div class="form-group col-12">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <label style="margin: 0; font-weight: 600;"><i class="fas fa-map-marker-alt" style="color: #002C76;"></i> Select CAR Provinces:</label>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('create_provinces_grid', true)">Select All</button>
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('create_provinces_grid', false)">Clear</button>
                            </div>
                        </div>
                        <div class="checkbox-grid" id="create_provinces_grid">
                            @foreach($availableProvinces as $provinceName)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="target_provinces[]" value="{{ $provinceName }}">
                                    <span>{{ $provinceName }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="create_content">Announcement Content <span class="required">*</span></label>
                        <textarea name="content" id="create_content" class="form-control" rows="6" placeholder="Write the complete announcement content, instructions, or advisory details..." required></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="create_expires_at"><i class="fas fa-eye-slash" style="color: #64748b;"></i> Notice Expiry Date (Optional)</label>
                        <input type="datetime-local" name="expires_at" id="create_expires_at" class="form-control">
                        <small class="form-hint">Leave blank if the notice does not expire.</small>
                    </div>
                </div>

                <div class="form-row checkbox-options-row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="popup_on_login" value="1" checked>
                        <span><i class="fas fa-window-restore"></i> Show popup modal when users log into the system</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_pinned" value="1">
                        <span><i class="fas fa-thumbtack"></i> Pin this announcement to the top</span>
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label>Publication Status</label>
                        <div class="audience-radio-group">
                            <label class="radio-label">
                                <input type="radio" name="status" value="published" checked>
                                <span>Publish Immediately</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="status" value="draft">
                                <span>Save as Draft</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="announcement-modal-footer">
                <button type="button" class="btn-announcement secondary" onclick="closeCreateAnnouncementModal()">Cancel</button>
                <button type="submit" class="btn-announcement primary">
                    <i class="fas fa-paper-plane"></i> Save & Publish
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 2. Edit Announcement Modal --}}
<div id="editAnnouncementModal" class="announcement-modal" aria-hidden="true">
    <div class="announcement-modal-backdrop" onclick="closeEditAnnouncementModal()"></div>
    <div class="announcement-modal-dialog">
        <form method="POST" action="" enctype="multipart/form-data" id="editAnnouncementForm">
            @csrf
            @method('PUT')
            <div class="announcement-modal-header">
                <div>
                    <h3 class="modal-title"><i class="fas fa-edit"></i> Edit Announcement</h3>
                    <p class="modal-subtitle">Update notice information, priority, audience, or attachments.</p>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeEditAnnouncementModal()">&times;</button>
            </div>

            <div class="announcement-modal-body">
                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="edit_title">Announcement Title <span class="required">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="edit_type">Category / Type <span class="required">*</span></label>
                        <select name="type" id="edit_type" class="form-control" required>
                            @foreach(\App\Modules\Announcements\Models\Announcement::typeOptions() as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-6">
                        <label for="edit_priority">Priority Level <span class="required">*</span></label>
                        <select name="priority" id="edit_priority" class="form-control" required>
                            @foreach(\App\Modules\Announcements\Models\Announcement::priorityOptions() as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label>Target Audience <span class="required">*</span></label>
                        <div class="audience-radio-group">
                            <label class="radio-label">
                                <input type="radio" name="target_audience" value="all" id="edit_aud_all" onchange="handleAudienceTypeChange('edit', 'all')">
                                <span>All PRISM Users</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="target_audience" value="role" id="edit_aud_role" onchange="handleAudienceTypeChange('edit', 'role')">
                                <span>Specific User Roles</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="target_audience" value="province" id="edit_aud_province" onchange="handleAudienceTypeChange('edit', 'province')">
                                <span>Specific Provinces (CAR)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Role Selection (hidden by default) --}}
                <div class="form-row audience-sub-section" id="edit_roles_section" style="display: none;">
                    <div class="form-group col-12">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <label style="margin: 0; font-weight: 600;"><i class="fas fa-users-cog" style="color: #002C76;"></i> Select Target Roles:</label>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('edit_roles_grid', true)">Select All</button>
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('edit_roles_grid', false)">Clear</button>
                            </div>
                        </div>
                        <div class="checkbox-grid" id="edit_roles_grid">
                            @foreach($availableRoles as $roleKey => $roleLabel)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="target_roles[]" value="{{ $roleKey }}" id="edit_role_{{ $roleKey }}">
                                    <span>{{ $roleLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Province Selection (CAR) (hidden by default) --}}
                <div class="form-row audience-sub-section" id="edit_provinces_section" style="display: none;">
                    <div class="form-group col-12">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <label style="margin: 0; font-weight: 600;"><i class="fas fa-map-marker-alt" style="color: #002C76;"></i> Select CAR Provinces:</label>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('edit_provinces_grid', true)">Select All</button>
                                <button type="button" class="btn-sm-text" onclick="selectAllCheckboxes('edit_provinces_grid', false)">Clear</button>
                            </div>
                        </div>
                        <div class="checkbox-grid" id="edit_provinces_grid">
                            @foreach($availableProvinces as $provinceName)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="target_provinces[]" value="{{ $provinceName }}" id="edit_prov_{{ \Illuminate\Support\Str::slug($provinceName) }}">
                                    <span>{{ $provinceName }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="edit_content">Announcement Content <span class="required">*</span></label>
                        <textarea name="content" id="edit_content" class="form-control" rows="6" required></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="edit_expires_at"><i class="fas fa-eye-slash" style="color: #64748b;"></i> Notice Expiry Date (Optional)</label>
                        <input type="datetime-local" name="expires_at" id="edit_expires_at" class="form-control">
                    </div>
                </div>

                <div class="form-row checkbox-options-row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="popup_on_login" id="edit_popup_on_login" value="1">
                        <span><i class="fas fa-window-restore"></i> Show popup modal when users log into the system</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_pinned" id="edit_is_pinned" value="1">
                        <span><i class="fas fa-thumbtack"></i> Pin this announcement to the top</span>
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="edit_status">Publication Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="announcement-modal-footer">
                <button type="button" class="btn-announcement secondary" onclick="closeEditAnnouncementModal()">Cancel</button>
                <button type="submit" class="btn-announcement primary">
                    <i class="fas fa-save"></i> Update Announcement
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 3. Delete Confirmation Form (Hidden) --}}
<form id="deleteAnnouncementForm" method="POST" action="" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- 4. Readers List Modal (Admin only) --}}
<div id="announcementReadersModal" class="announcement-modal" aria-hidden="true">
    <div class="announcement-modal-backdrop" onclick="closeReadersModal()"></div>
    <div class="announcement-modal-dialog">
        <div class="announcement-modal-header">
            <div>
                <h3 class="modal-title"><i class="fas fa-eye"></i> Read Receipts & Audience Audit</h3>
                <p class="modal-subtitle" id="readersModalSubtitle">Viewing users who have opened this announcement.</p>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeReadersModal()">&times;</button>
        </div>
        <div class="announcement-modal-body" style="padding: 0; max-height: 480px; overflow-y: auto;">
            <div id="readersModalLoading" style="padding: 30px; text-align: center; color: #64748b;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Loading read receipts...</p>
            </div>
            <table class="announcement-table" id="readersTable" style="display: none; margin: 0;">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Role</th>
                        <th>Province</th>
                        <th>Read Timestamp</th>
                    </tr>
                </thead>
                <tbody id="readersTableBody"></tbody>
            </table>
            <div id="readersEmptyState" style="display: none; padding: 40px; text-align: center; color: #64748b;">
                <i class="fas fa-eye-slash fa-2x" style="color: #94a3b8; margin-bottom: 8px;"></i>
                <p>No user has opened this announcement yet.</p>
            </div>
        </div>
        <div class="announcement-modal-footer">
            <button type="button" class="btn-announcement secondary" onclick="closeReadersModal()">Close</button>
        </div>
    </div>
</div>
@endif

{{-- 5. Universal Announcement Reader Modal --}}
<div id="announcementReaderModal" class="announcement-modal" aria-hidden="true">
    <div class="announcement-modal-backdrop" onclick="closeAnnouncementReaderModal()"></div>
    <div class="announcement-modal-dialog" style="max-width: 680px;">
        <div class="announcement-modal-header reader-header">
            <div style="flex: 1; min-width: 0;">
                <div class="badges-group" style="margin-bottom: 8px;" id="readerBadgesGroup"></div>
                <h2 class="modal-title" id="readerTitle" style="font-size: 20px; line-height: 1.4; color: #002C76;"></h2>
                <div class="card-meta" style="margin-top: 8px;" id="readerMeta"></div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeAnnouncementReaderModal()">&times;</button>
        </div>

        <div class="announcement-modal-body" style="max-height: 520px; overflow-y: auto;">
            <div id="readerDeadlineBox" style="display: none; align-items: center; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; border: 1px solid;"></div>
            <div id="readerContent" class="reader-body-text"></div>

            <div id="readerAttachmentBox" class="reader-attachment-card" style="display: none;">
                <div class="attachment-icon"><i class="fas fa-file-alt"></i></div>
                <div class="attachment-info">
                    <strong id="readerAttachmentName">Notice_Document.pdf</strong>
                    <span>Attached Document</span>
                </div>
                <a href="#" id="readerAttachmentBtn" class="btn-announcement secondary download-btn">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>

        <div class="announcement-modal-footer" style="justify-content: space-between;">
            <div>
                <a href="#" id="readerActionLink" target="_blank" rel="noopener noreferrer" class="btn-announcement primary" style="display: none;">
                    <i class="fas fa-external-link-alt"></i> <span id="readerActionText">Open Action</span>
                </a>
            </div>
            <button type="button" class="btn-announcement secondary" onclick="closeAnnouncementReaderModal()">Close Notice</button>
        </div>
    </div>
</div>

{{-- Styles --}}
<style>
    .announcements-container {
        padding: 8px 4px 40px;
    }

    .announcements-header-card {
        background: linear-gradient(135deg, #002C76 0%, #0040a8 100%);
        border-radius: 14px;
        padding: 24px 30px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
        box-shadow: 0 4px 18px rgba(0, 44, 118, 0.16);
    }

    .announcements-header-content {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .announcements-header-icon {
        width: 58px;
        height: 58px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: #ffffff;
        backdrop-filter: blur(4px);
    }

    .announcements-header-text h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
    }

    .announcements-header-text p {
        margin: 6px 0 0;
        font-size: 13px;
        color: #e2e8f0;
        max-width: 680px;
    }

    .announcement-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 18px;
        font-size: 13px;
        font-weight: 500;
    }

    .announcement-alert.success {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .announcement-alert.error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Tabs */
    .announcement-nav-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 22px;
    }

    .announcement-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
    }

    .announcement-tab-link:hover {
        color: #002C76;
    }

    .announcement-tab-link.active {
        color: #002C76;
        border-bottom-color: #002C76;
    }

    .tab-badge {
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
    }

    .announcement-tab-link.active .tab-badge {
        background: #dbeafe;
        color: #1e40af;
    }

    /* Stats Grid */
    .announcement-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .stat-card.total .stat-icon { background: #eff6ff; color: #1d4ed8; }
    .stat-card.published .stat-icon { background: #f0fdf4; color: #16a34a; }
    .stat-card.draft .stat-icon { background: #fffbeb; color: #d97706; }
    .stat-card.urgent .stat-icon { background: #fef2f2; color: #dc2626; }
    .stat-card.reads .stat-icon { background: #faf5ff; color: #9333ea; }

    .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        display: block;
    }

    .stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Toolbar */
    .announcement-toolbar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .announcement-filter-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        flex: 1;
    }

    .announcement-search-box {
        position: relative;
        min-width: 260px;
        flex: 1;
    }

    .announcement-search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
    }

    .announcement-search-box input {
        width: 100%;
        padding: 9px 12px 9px 34px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        outline: none;
        background: #f8fafc;
        color: #0f172a;
    }

    .announcement-search-box input:focus {
        border-color: #002C76;
        background: #ffffff;
    }

    .announcement-filter-select-wrap select {
        padding: 9px 14px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        color: #334155;
        outline: none;
        cursor: pointer;
    }

    .announcement-filter-select-wrap select:focus {
        border-color: #002C76;
        background: #ffffff;
    }

    /* Buttons */
    .btn-announcement {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-announcement.primary {
        background: #002C76;
        color: #ffffff;
    }

    .btn-announcement.primary:hover {
        background: #003d99;
        transform: translateY(-1px);
    }

    .btn-announcement.secondary {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .btn-announcement.secondary:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-announcement.subtle {
        background: transparent;
        color: #002C76;
        border: 1px solid transparent;
    }

    .btn-announcement.subtle:hover {
        background: #eff6ff;
    }

    /* Grid of Announcements */
    .announcements-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 20px;
    }

    .announcement-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .announcement-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 44, 118, 0.08);
        border-color: #cbd5e1;
    }

    .announcement-card.is-pinned {
        border-color: #fde047;
        background: linear-gradient(180deg, #fffdf2 0%, #ffffff 40px);
    }

    .announcement-card.is-unread {
        border-left: 4px solid #002C76;
    }

    .pinned-ribbon {
        position: absolute;
        top: 12px;
        right: 14px;
        font-size: 11px;
        font-weight: 700;
        color: #ca8a04;
        background: #fef9c3;
        padding: 2px 8px;
        border-radius: 10px;
        border: 1px solid #fef08a;
    }

    .card-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        min-height: 24px;
    }

    .badges-group {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .type-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
    }

    .priority-pill {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .unread-dot {
        width: 9px;
        height: 9px;
        background: #002C76;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.2);
    }

    .card-title {
        font-size: 16px;
        font-weight: 700;
        margin: 0 0 8px;
        line-height: 1.4;
    }

    .card-title a {
        color: #0f172a;
        text-decoration: none;
    }

    .card-title a:hover {
        color: #002C76;
    }

    .card-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 11px;
        color: #64748b;
        margin-bottom: 12px;
    }

    .card-meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .card-body-snippet {
        font-size: 13px;
        color: #475569;
        line-height: 1.6;
        margin-bottom: 18px;
        flex: 1;
    }

    .card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }

    .footer-links {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .card-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .card-action-btn.view {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .card-action-btn.view:hover {
        background: #dbeafe;
    }

    .card-action-btn.download {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .card-action-btn.download:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .card-action-btn.link {
        background: #faf5ff;
        color: #7e22ce;
    }

    .card-action-btn.link:hover {
        background: #f3e8ff;
    }

    .admin-quick-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .icon-btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: all 0.15s ease;
    }

    .icon-btn:hover {
        background: #f8fafc;
        color: #002C76;
        border-color: #cbd5e1;
    }

    /* Management Table */
    .announcement-table-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow-x: auto;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        margin-bottom: 22px;
    }

    .announcement-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }

    .announcement-table thead th {
        background: #f8fafc;
        padding: 12px 16px;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
    }

    .announcement-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }

    .announcement-table tbody tr:hover {
        background: #f8fafc;
    }

    .table-title-cell {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .table-pinned-icon {
        color: #eab308;
        font-size: 12px;
        margin-top: 2px;
    }

    .table-announcement-link {
        font-weight: 600;
        color: #0f172a;
        text-decoration: none;
    }

    .table-announcement-link:hover {
        color: #002C76;
    }

    .table-author-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 3px;
    }

    .status-pill {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
    }

    .status-pill.published { background: #f0fdf4; color: #16a34a; }
    .status-pill.draft { background: #fffbeb; color: #d97706; }
    .status-pill.expired { background: #fef2f2; color: #dc2626; }
    .status-pill.archived { background: #f1f5f9; color: #64748b; }

    .read-count-pill {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #002C76;
        border-radius: 12px;
        padding: 3px 9px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }

    .read-count-pill:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .table-actions-cell {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
    }

    .btn-table-icon {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: all 0.15s ease;
    }

    .btn-table-icon:hover {
        background: #eff6ff;
        color: #002C76;
        border-color: #bfdbfe;
    }

    .btn-table-icon.danger:hover {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    .announcement-empty-state {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 50px 20px;
        text-align: center;
        margin-bottom: 22px;
    }

    .announcement-empty-state .empty-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #eff6ff;
        color: #002C76;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin: 0 auto 16px;
    }

    .announcement-empty-state h3 {
        margin: 0 0 6px;
        font-size: 18px;
        color: #0f172a;
    }

    .announcement-empty-state p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }

    .announcements-pagination {
        display: flex;
        justify-content: center;
        margin-top: 24px;
    }

    /* Modals */
    .announcement-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1200;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .announcement-modal.is-open {
        display: flex;
    }

    .announcement-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(2px);
    }

    .announcement-modal-dialog {
        position: relative;
        background: #ffffff;
        border-radius: 14px;
        width: 100%;
        max-width: 640px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        max-height: 90vh;
        z-index: 10;
        overflow: hidden;
    }

    .announcement-modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        background: #f8fafc;
    }

    .modal-title {
        font-size: 17px;
        font-weight: 700;
        color: #002C76;
        margin: 0;
    }

    .modal-subtitle {
        font-size: 12px;
        color: #64748b;
        margin: 4px 0 0;
    }

    .modal-close-btn {
        background: transparent;
        border: none;
        font-size: 22px;
        color: #94a3b8;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .modal-close-btn:hover {
        color: #0f172a;
    }

    .announcement-modal-body {
        padding: 22px 24px;
        overflow-y: auto;
    }

    .announcement-modal-footer {
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    /* Form Styles */
    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group.col-12 { width: 100%; }
    .form-group.col-6 { width: calc(50% - 8px); }

    .form-group label {
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }

    .form-group .required {
        color: #dc2626;
    }

    .form-control {
        padding: 9px 12px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        outline: none;
        color: #0f172a;
    }

    .form-control:focus {
        border-color: #002C76;
        box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
    }

    .form-hint {
        font-size: 11px;
        color: #64748b;
    }

    .audience-radio-group {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .radio-label, .checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        color: #334155;
        cursor: pointer;
    }

    .audience-sub-section {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 12px;
        margin-top: -6px;
    }

    .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
        margin-top: 4px;
    }

    .reader-body-text {
        font-size: 14px;
        line-height: 1.7;
        color: #334155;
        white-space: pre-wrap;
    }

    .reader-attachment-card {
        margin-top: 20px;
        padding: 14px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .reader-attachment-card .attachment-icon {
        font-size: 22px;
        color: #002C76;
    }

    .reader-attachment-card .attachment-info {
        flex: 1;
        min-width: 0;
    }

    .reader-attachment-card .attachment-info strong {
        display: block;
        font-size: 13px;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .reader-attachment-card .attachment-info span {
        font-size: 11px;
        color: #64748b;
    }

    .btn-sm-text {
        background: transparent;
        border: none;
        color: #002C76;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        transition: background 0.15s;
    }

    .btn-sm-text:hover {
        background: #e0f2fe;
    }
</style>

{{-- Scripts --}}
<script>
    function handleAudienceTypeChange(prefix, type) {
        const rolesSection = document.getElementById(prefix + '_roles_section');
        const provincesSection = document.getElementById(prefix + '_provinces_section');

        if (rolesSection) {
            rolesSection.style.display = type === 'role' ? 'flex' : 'none';
            rolesSection.querySelectorAll('input').forEach(i => i.disabled = (type !== 'role'));
        }
        if (provincesSection) {
            provincesSection.style.display = type === 'province' ? 'flex' : 'none';
            provincesSection.querySelectorAll('input').forEach(i => i.disabled = (type !== 'province'));
        }
    }

    function selectAllCheckboxes(containerId, check) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!cb.disabled) cb.checked = check;
        });
    }

    function openCreateAnnouncementModal() {
        const modal = document.getElementById('createAnnouncementModal');
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeCreateAnnouncementModal() {
        const modal = document.getElementById('createAnnouncementModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    function openEditAnnouncementModal(id) {
        fetch(`/announcements/${id}/detail`)
            .then(res => res.json())
            .then(data => {
                const modal = document.getElementById('editAnnouncementModal');
                const form = document.getElementById('editAnnouncementForm');
                if (!modal || !form) return;

                form.action = `/announcements/${id}`;

                document.getElementById('edit_title').value = data.title || '';
                document.getElementById('edit_type').value = data.type || 'general';
                document.getElementById('edit_priority').value = data.priority || 'normal';
                document.getElementById('edit_content').value = data.content || '';
                document.getElementById('edit_is_pinned').checked = !!data.is_pinned;
                const popupCb = document.getElementById('edit_popup_on_login');
                if (popupCb) popupCb.checked = (data.popup_on_login !== undefined ? !!data.popup_on_login : true);

                if (document.getElementById('edit_expires_at')) {
                    document.getElementById('edit_expires_at').value = data.expires_at_raw || '';
                }

                // Status & Expiry
                if (data.status) {
                    document.getElementById('edit_status').value = data.status;
                }

                // Audience radio
                const audVal = data.target_audience || 'all';
                const audRadio = form.querySelector(`input[name="target_audience"][value="${audVal}"]`);
                if (audRadio) audRadio.checked = true;
                handleAudienceTypeChange('edit', audVal);

                // Roles checkboxes
                form.querySelectorAll('input[name="target_roles[]"]').forEach(cb => {
                    cb.checked = Array.isArray(data.target_roles) && data.target_roles.includes(cb.value);
                });

                // Provinces checkboxes
                form.querySelectorAll('input[name="target_provinces[]"]').forEach(cb => {
                    cb.checked = Array.isArray(data.target_provinces) && data.target_provinces.includes(cb.value);
                });

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            })
            .catch(err => {
                console.error(err);
                alert('Could not load announcement details.');
            });
    }

    function closeEditAnnouncementModal() {
        const modal = document.getElementById('editAnnouncementModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    function confirmDeleteAnnouncement(id, title) {
        if (confirm(`Are you sure you want to delete the announcement "${title}"? This cannot be undone.`)) {
            const form = document.getElementById('deleteAnnouncementForm');
            if (form) {
                form.action = `/announcements/${id}`;
                form.submit();
            }
        }
    }

    function openReadersModal(id) {
        const modal = document.getElementById('announcementReadersModal');
        const loading = document.getElementById('readersModalLoading');
        const table = document.getElementById('readersTable');
        const tbody = document.getElementById('readersTableBody');
        const empty = document.getElementById('readersEmptyState');
        const subtitle = document.getElementById('readersModalSubtitle');

        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        loading.style.display = 'block';
        table.style.display = 'none';
        empty.style.display = 'none';
        tbody.innerHTML = '';

        fetch(`/announcements/${id}/readers`)
            .then(res => res.json())
            .then(data => {
                loading.style.display = 'none';
                subtitle.textContent = `Audit for: "${data.title}" (${data.total_reads} total reads)`;

                if (!data.readers || data.readers.length === 0) {
                    empty.style.display = 'block';
                    return;
                }

                table.style.display = 'table';
                data.readers.forEach(reader => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${reader.name}</strong> <small style="color:#64748b;">(${reader.username})</small></td>
                        <td>${reader.role}</td>
                        <td>${reader.province}</td>
                        <td>${reader.read_at}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                loading.style.display = 'none';
                empty.style.display = 'block';
                empty.innerHTML = `<p style="color:#dc2626;">Failed to load readers list.</p>`;
            });
    }

    function closeReadersModal() {
        const modal = document.getElementById('announcementReadersModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    // Universal Reader Modal
    function openAnnouncementReaderModal(id) {
        fetch(`/announcements/${id}/detail`)
            .then(res => res.json())
            .then(data => {
                const modal = document.getElementById('announcementReaderModal');
                if (!modal) return;

                // Title & Content
                document.getElementById('readerTitle').textContent = data.title;
                document.getElementById('readerContent').textContent = data.content;

                // Deadline Banner
                const deadlineBox = document.getElementById('readerDeadlineBox');
                if (deadlineBox) {
                    if (data.has_deadline && data.deadline_badge) {
                        deadlineBox.style.display = 'flex';
                        deadlineBox.style.background = data.deadline_badge.bg;
                        deadlineBox.style.color = data.deadline_badge.color;
                        deadlineBox.style.borderColor = data.deadline_badge.border;
                        deadlineBox.innerHTML = `
                            <div style="font-size: 20px; margin-right: 12px;"><i class="fas ${data.deadline_badge.icon}"></i></div>
                            <div style="flex: 1;">
                                <strong style="display: block; font-size: 13px;">${data.deadline_badge.label} &bull; ${data.deadline_badge.relative}</strong>
                                <span style="font-size: 11px; opacity: 0.95;">Compliance Cutoff: ${data.deadline_badge.formatted}</span>
                            </div>
                        `;
                    } else {
                        deadlineBox.style.display = 'none';
                    }
                }

                // Badges
                const badgesGroup = document.getElementById('readerBadgesGroup');
                let badgesHtml = `<span class="type-pill" style="background:${data.type_badge.bg}; color:${data.type_badge.color}; border:1px solid ${data.type_badge.border};"><i class="fas ${data.type_badge.icon}"></i> ${data.type_badge.label}</span>`;
                if (data.priority === 'urgent' || data.priority === 'high') {
                    badgesHtml += ` <span class="priority-pill" style="background:${data.priority_badge.bg}; color:${data.priority_badge.color};">${data.priority_badge.label}</span>`;
                }
                if (data.is_pinned) {
                    badgesHtml += ` <span class="pinned-ribbon" style="position:static; margin-left:6px;"><i class="fas fa-thumbtack"></i> Pinned</span>`;
                }
                badgesGroup.innerHTML = badgesHtml;

                // Meta
                const meta = document.getElementById('readerMeta');
                meta.innerHTML = `
                    <span><i class="fas fa-user-edit"></i> ${data.author_name}</span>
                    <span><i class="far fa-clock"></i> ${data.published_at_formatted}</span>
                    <span><i class="fas fa-users"></i> ${data.audience_desc}</span>
                `;

                // Attachment
                const attachBox = document.getElementById('readerAttachmentBox');
                const attachName = document.getElementById('readerAttachmentName');
                const attachBtn = document.getElementById('readerAttachmentBtn');
                if (data.has_attachment) {
                    attachBox.style.display = 'flex';
                    attachName.textContent = data.attachment_name || 'Attached File';
                    attachBtn.href = data.attachment_download_url;
                } else {
                    attachBox.style.display = 'none';
                }

                // Action URL
                const actionLink = document.getElementById('readerActionLink');
                const actionText = document.getElementById('readerActionText');
                if (data.action_url) {
                    actionLink.style.display = 'inline-flex';
                    actionLink.href = data.action_url;
                    actionText.textContent = data.action_text || 'Open Action';
                } else {
                    actionLink.style.display = 'none';
                }

                // Update unread UI
                const dot = document.getElementById(`card-dot-${id}`);
                if (dot) dot.remove();
                const card = document.getElementById(`announcement-card-${id}`);
                if (card) card.classList.remove('is-unread');

                // Update global topbar announcement badge if present
                const topbarBadge = document.getElementById('announcementUnreadBadge');
                if (topbarBadge) {
                    const newCount = parseInt(data.unread_count, 10);
                    if (newCount > 0) {
                        topbarBadge.textContent = newCount > 99 ? '99+' : newCount;
                        topbarBadge.style.display = 'flex';
                    } else {
                        topbarBadge.style.display = 'none';
                    }
                }

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            })
            .catch(err => {
                console.error(err);
                alert('Could not open announcement.');
            });
    }

    function closeAnnouncementReaderModal() {
        const modal = document.getElementById('announcementReaderModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    function markAllAnnouncementsReadGlobally() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '{{ csrf_token() }}';

        fetch('{{ route("announcements.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Remove all unread dots
                document.querySelectorAll('.unread-dot').forEach(el => el.remove());
                document.querySelectorAll('.announcement-card.is-unread').forEach(el => el.classList.remove('is-unread'));

                // Hide topbar badge
                const topbarBadge = document.getElementById('announcementUnreadBadge');
                if (topbarBadge) {
                    topbarBadge.style.display = 'none';
                }

                // Refresh if on feed with unread filter
                if (window.location.search.includes('read_state=unread')) {
                    window.location.reload();
                }
            }
        })
        .catch(err => console.error(err));
    }
</script>
@endsection

