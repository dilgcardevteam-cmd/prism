@extends('layouts.dashboard')

@section('title', 'Ticket Details')
@section('page-title', 'Ticketing System')

@section('styles')
    @include('partials.ticketing-styles')
@endsection

@section('content')
    @php
        $status = $ticket->status;
        $steps = [
            ['label' => 'Submitted', 'done' => true, 'active' => $status === \App\Models\Ticket::STATUS_SUBMITTED],
            ['label' => 'Provincial Review', 'done' => in_array($status, [
                \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
                \App\Models\Ticket::STATUS_RESOLVED_BY_PROVINCE,
                \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                \App\Models\Ticket::STATUS_CLOSED,
            ], true), 'active' => $status === \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE],
            ['label' => 'Regional Review', 'done' => in_array($status, [
                \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                \App\Models\Ticket::STATUS_CLOSED,
            ], true), 'active' => in_array($status, [
                \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
            ], true)],
            ['label' => 'Central Office', 'done' => in_array($status, [
                \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                \App\Models\Ticket::STATUS_CLOSED,
            ], true), 'active' => $status === \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE],
            ['label' => 'Closed', 'done' => $status === \App\Models\Ticket::STATUS_CLOSED, 'active' => $status === \App\Models\Ticket::STATUS_CLOSED],
        ];
    @endphp

    <div class="content-header">
        <h1>Ticket Details</h1>
        <p>{{ $ticket->ticket_number }} • Full ticket information, remarks, attachments, and workflow history.</p>
    </div>

    <div class="ticketing-shell">
        @include('partials.ticketing-flash')

        <div class="ticketing-card">
            <div class="ticketing-toolbar">
                <div>
                    <span class="ticketing-ticket-link">{{ $ticket->ticket_number }}</span>
                    <h2 class="ticketing-card-title" style="margin-top: 10px;">{{ $ticket->title }}</h2>
                    <p class="ticketing-card-subtitle">{{ $ticket->description }}</p>
                </div>
                <div class="ticketing-toolbar-actions">
                    <span class="ticketing-badge" style="background: {{ $ticket->status_color }};">{{ $ticket->status }}</span>
                    <span class="ticketing-badge" style="background: {{ $ticket->priority_color }};">{{ $ticket->priority }}</span>
                </div>
            </div>

            <div class="ticketing-progress" style="margin-top: 18px;">
                @foreach ($steps as $step)
                    <span class="ticketing-progress-step @if($step['active']) is-active @elseif($step['done']) is-done @endif">
                        {{ $step['label'] }}
                    </span>
                @endforeach
            </div>

            <div class="ticketing-inline-actions" style="margin-top: 20px;">
                @if ($canAcceptProvince)
                    <form method="POST" action="{{ route('ticketing.province.accept', $ticket) }}">
                        @csrf
                        <button type="submit" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-hand"></i>
                            Accept Ticket
                        </button>
                    </form>
                @endif

                @if ($canAcceptRegion)
                    <form method="POST" action="{{ route('ticketing.region.accept', $ticket) }}">
                        @csrf
                        <button type="submit" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-hand"></i>
                            Accept Ticket
                        </button>
                    </form>
                @endif

                @if ($canManageProvince)
                    @if ($ticket->status === \App\Models\Ticket::STATUS_SUBMITTED)
                        <form method="POST" action="{{ route('ticketing.province.start-review', $ticket) }}">
                            @csrf
                            <button type="submit" class="ticketing-btn ticketing-btn--primary">
                                <i class="fas fa-play"></i>
                                Start Provincial Review
                            </button>
                        </form>
                    @endif

                    @if (in_array($ticket->status, [\App\Models\Ticket::STATUS_SUBMITTED, \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE], true))
                        <button type="button" class="ticketing-btn ticketing-btn--success" data-ticketing-open="resolveProvinceModal">
                            <i class="fas fa-circle-check"></i>
                            Resolve Ticket
                        </button>
                        <button type="button" class="ticketing-btn ticketing-btn--warning" data-ticketing-open="escalateProvinceModal">
                            <i class="fas fa-arrow-up-right-dots"></i>
                            Escalate to Region
                        </button>
                    @endif
                @endif

                @if ($canManageRegion)
                    @if ($ticket->status === \App\Models\Ticket::STATUS_ESCALATED_TO_REGION)
                        <form method="POST" action="{{ route('ticketing.region.start-review', $ticket) }}">
                            @csrf
                            <button type="submit" class="ticketing-btn ticketing-btn--primary">
                                <i class="fas fa-play"></i>
                                Start Regional Review
                            </button>
                        </form>
                    @endif

                    @if (in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                        \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                        \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                    ], true))
                        <button type="button" class="ticketing-btn ticketing-btn--success" data-ticketing-open="resolveRegionModal">
                            <i class="fas fa-circle-check"></i>
                            Resolve Ticket
                        </button>
                    @endif

                    @if (in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                        \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                    ], true))
                        <button type="button" class="ticketing-btn ticketing-btn--warning" data-ticketing-open="forwardRegionModal">
                            <i class="fas fa-building-columns"></i>
                            Forward to Central Office
                        </button>
                    @endif
                @endif

                @if ($canManageAdmin && in_array($ticket->status, [
                    \App\Models\Ticket::STATUS_RESOLVED_BY_PROVINCE,
                    \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                    \App\Models\Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                    \App\Models\Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                ], true))
                    <button type="button" class="ticketing-btn ticketing-btn--dark" data-ticketing-open="closeTicketModal">
                        <i class="fas fa-box-archive"></i>
                        Close Ticket
                    </button>
                @endif
            </div>
        </div>

        <div class="ticketing-grid ticketing-grid--2">
            <div class="ticketing-card">
                <h3 class="ticketing-card-title">Ticket Information</h3>
                <p class="ticketing-card-subtitle" style="margin-bottom: 16px;">Snapshot of the ticket routing, owner, and current assignment.</p>

                <div class="ticketing-meta-list">
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Category</div>
                        <div class="ticketing-meta-value">{{ $ticket->category->name ?? 'Uncategorized' }}</div>
                    </div>
                    @if ($ticket->subcategory)
                        <div class="ticketing-meta-item">
                            <div class="ticketing-meta-label">Please Specify</div>
                            <div class="ticketing-meta-value">{{ $ticket->subcategory }}</div>
                        </div>
                    @endif
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Current Level</div>
                        <div class="ticketing-meta-value">{{ $ticket->current_level_label }}</div>
                    </div>
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Submitted By</div>
                        <div class="ticketing-meta-value">{{ $ticket->submitter?->fullName() ?? 'N/A' }}</div>
                    </div>
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Assigned To</div>
                        <div class="ticketing-meta-value">
                            @if ($ticket->assignee)
                                {{ $ticket->assignee->fullName() }}
                            @elseif ($ticket->current_level === \App\Models\Ticket::LEVEL_PROVINCIAL)
                                Unassigned - waiting for a Provincial User to accept the ticket
                            @elseif ($ticket->current_level === \App\Models\Ticket::LEVEL_REGIONAL)
                                Unassigned - waiting for a Regional User to accept the ticket
                            @else
                                Unassigned
                            @endif
                        </div>
                    </div>
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Contact Information</div>
                        <div class="ticketing-meta-value">{{ $ticket->contact_information ?: 'Not provided' }}</div>
                    </div>
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Date Submitted</div>
                        <div class="ticketing-meta-value">{{ optional($ticket->date_submitted ?? $ticket->created_at)->format('F d, Y h:i A') }}</div>
                    </div>
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Province Scope</div>
                        <div class="ticketing-meta-value">{{ $ticket->province_scope ?: 'N/A' }}</div>
                    </div>
                    <div class="ticketing-meta-item">
                        <div class="ticketing-meta-label">Region Scope</div>
                        <div class="ticketing-meta-value">{{ $ticket->region_scope ?: 'N/A' }}</div>
                    </div>
                </div>

                @if ($ticket->escalation_reason)
                    <div class="ticketing-meta-item" style="margin-top: 16px;">
                        <div class="ticketing-meta-label">Escalation Reason</div>
                        <div class="ticketing-meta-value" style="white-space: pre-line;">{{ $ticket->escalation_reason }}</div>
                    </div>
                @endif

                @if (auth()->user()?->isProvincialUser() && $ticket->current_level === \App\Models\Ticket::LEVEL_PROVINCIAL && $ticket->assigned_to && ! $canManageProvince)
                    <div class="ticketing-empty" style="margin-top: 16px;">
                        This ticket is currently being handled by {{ $ticket->assignee?->fullName() ?? 'another Provincial User' }}.
                    </div>
                @endif

                @if (auth()->user()?->isRegionalUser() && $ticket->current_level === \App\Models\Ticket::LEVEL_REGIONAL && $ticket->assigned_to && ! $canManageRegion)
                    <div class="ticketing-empty" style="margin-top: 16px;">
                        This ticket is currently being handled by {{ $ticket->assignee?->fullName() ?? 'another Regional User' }}.
                    </div>
                @endif
            </div>

            <div class="ticketing-card">
                <h3 class="ticketing-card-title">Attachments</h3>
                <p class="ticketing-card-subtitle" style="margin-bottom: 16px;">Files uploaded with the ticket submission.</p>

                @if ($ticket->attachments->isEmpty())
                    <div class="ticketing-empty">No attachments were uploaded with this ticket.</div>
                @else
                    <div class="ticketing-attachment-list">
                        @foreach ($ticket->attachments as $attachment)
                            <div class="ticketing-attachment-item">
                                <div class="ticketing-comment-author">
                                    <strong>{{ $attachment->original_name }}</strong>
                                    <span class="ticketing-comment-time">{{ optional($attachment->created_at)->format('M d, Y h:i A') }}</span>
                                </div>
                                <div class="ticketing-kicker" style="margin-bottom: 10px;">
                                    {{ $attachment->mime_type ?: 'Unknown type' }}
                                    @if ($attachment->file_size)
                                        • {{ number_format($attachment->file_size / 1024, 1) }} KB
                                    @endif
                                </div>
                                <a href="{{ route('ticketing.attachments.download', [$ticket, $attachment]) }}" class="ticketing-btn ticketing-btn--secondary">
                                    <i class="fas fa-download"></i>
                                    Download Attachment
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="ticketing-grid ticketing-grid--2">
            <div class="ticketing-card">
                <div class="ticketing-toolbar" style="margin-bottom: 16px;">
                    <div>
                        <h3 class="ticketing-card-title">Comments / Remarks</h3>
                        <p class="ticketing-card-subtitle">Visible updates and remarks saved by the users handling this ticket.</p>
                    </div>
                </div>

                @if ($ticket->comments->isEmpty())
                    <div class="ticketing-empty" style="margin-bottom: 16px;">No remarks saved yet.</div>
                @else
                    <div class="ticketing-comment-list" style="margin-bottom: 16px;">
                        @foreach ($ticket->comments as $comment)
                            <div class="ticketing-comment-item">
                                <div class="ticketing-comment-author">
                                    <strong>{{ $comment->user?->fullName() ?? 'System User' }}</strong>
                                    <span class="ticketing-comment-time">{{ optional($comment->created_at)->format('M d, Y h:i A') }}</span>
                                </div>
                                <div class="ticketing-comment-body">{{ $comment->comment }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @can('ticketing.addComment', $ticket)
                    <form method="POST" action="{{ route('ticketing.comments.store', $ticket) }}" class="ticketing-grid">
                        @csrf
                        <div class="ticketing-field">
                            <label for="comment">Add Remark / Comment</label>
                            <textarea id="comment" name="comment" placeholder="Add a visible update or remark for this ticket.">{{ old('comment') }}</textarea>
                        </div>
                        <button type="submit" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-comment-dots"></i>
                            Save Comment
                        </button>
                    </form>
                @elseif (auth()->user()?->isProvincialUser() && $ticket->current_level === \App\Models\Ticket::LEVEL_PROVINCIAL)
                    <div class="ticketing-empty">Accept the ticket first before adding remarks at the provincial level.</div>
                @elseif (auth()->user()?->isRegionalUser() && $ticket->current_level === \App\Models\Ticket::LEVEL_REGIONAL)
                    <div class="ticketing-empty">Accept the ticket first before adding remarks at the regional level.</div>
                @endif
            </div>

            <div class="ticketing-card">
                <div class="ticketing-toolbar" style="margin-bottom: 16px;">
                    <div>
                        <h3 class="ticketing-card-title">Timeline / Audit Trail</h3>
                        <p class="ticketing-card-subtitle">Every significant workflow action is recorded here with the actor and timestamps.</p>
                    </div>
                </div>

                @include('partials.ticketing-history', ['ticket' => $ticket])
            </div>
        </div>
    </div>

    <div class="ticketing-modal" id="resolveProvinceModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Resolve Ticket at Provincial Level</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="resolveProvinceModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.province.resolve', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="resolution_note_province">Resolution Note</label>
                    <textarea id="resolution_note_province" name="resolution_note" placeholder="Optional note describing the applied fix or guidance.">{{ old('resolution_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--success">
                    <i class="fas fa-circle-check"></i>
                    Confirm Resolution
                </button>
            </form>
        </div>
    </div>

    <div class="ticketing-modal" id="escalateProvinceModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Escalate Ticket to Region</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="escalateProvinceModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.province.escalate', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="escalation_reason">Escalation Reason</label>
                    <textarea id="escalation_reason" name="escalation_reason" placeholder="Required reason for escalating this ticket to the Regional User.">{{ old('escalation_reason') }}</textarea>
                </div>
                <div class="ticketing-field">
                    <label for="province_escalation_comment">Additional Comment</label>
                    <textarea id="province_escalation_comment" name="comment" placeholder="Optional remark visible in the ticket discussion.">{{ old('comment') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--warning">
                    <i class="fas fa-arrow-up-right-dots"></i>
                    Escalate Ticket
                </button>
            </form>
        </div>
    </div>

    <div class="ticketing-modal" id="resolveRegionModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Resolve Ticket at Regional Level</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="resolveRegionModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.region.resolve', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="resolution_note_region">Resolution Note</label>
                    <textarea id="resolution_note_region" name="resolution_note" placeholder="Optional note describing the regional resolution.">{{ old('resolution_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--success">
                    <i class="fas fa-circle-check"></i>
                    Confirm Resolution
                </button>
            </form>
        </div>
    </div>

    <div class="ticketing-modal" id="forwardRegionModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Forward to Central Office</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="forwardRegionModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.region.forward', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="forward_note">Forwarding Note</label>
                    <textarea id="forward_note" name="forward_note" placeholder="Optional note for Central Office / Admin.">{{ old('forward_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--warning">
                    <i class="fas fa-building-columns"></i>
                    Mark as Forwarded to Central Office
                </button>
            </form>
        </div>
    </div>

    <div class="ticketing-modal" id="closeTicketModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Close Ticket</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="closeTicketModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.admin.close', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="resolution_note_close">Closing Note</label>
                    <textarea id="resolution_note_close" name="resolution_note" placeholder="Optional closing summary for the audit trail.">{{ old('resolution_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--dark">
                    <i class="fas fa-box-archive"></i>
                    Close Ticket
                </button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function initializeTicketingUi() {
            const dropdownTriggers = document.querySelectorAll('[data-ticketing-dropdown]');
            const modalOpeners = document.querySelectorAll('[data-ticketing-open]');
            const modalClosers = document.querySelectorAll('[data-ticketing-close]');

            dropdownTriggers.forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const targetId = trigger.getAttribute('data-ticketing-dropdown');
                    const menu = document.getElementById(targetId);
                    if (!menu) return;

                    document.querySelectorAll('.ticketing-dropdown-menu.is-open').forEach((openMenu) => {
                        if (openMenu !== menu) {
                            openMenu.classList.remove('is-open');
                        }
                    });

                    menu.classList.toggle('is-open');
                });
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('.ticketing-dropdown')) {
                    document.querySelectorAll('.ticketing-dropdown-menu.is-open').forEach((menu) => {
                        menu.classList.remove('is-open');
                    });
                }
            });

            modalOpeners.forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-ticketing-open');
                    const modal = document.getElementById(modalId);
                    if (!modal) return;
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                });
            });

            modalClosers.forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-ticketing-close');
                    const modal = document.getElementById(modalId);
                    if (!modal) return;
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                });
            });

            document.querySelectorAll('.ticketing-modal').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        modal.classList.remove('is-open');
                        modal.setAttribute('aria-hidden', 'true');
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('.ticketing-modal.is-open').forEach((modal) => {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                });
            });
        })();
    </script>
@endsection
