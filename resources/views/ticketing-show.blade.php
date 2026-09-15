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
                \App\Models\Ticket::STATUS_PENDING,
                \App\Models\Ticket::STATUS_REOPENED,
                \App\Models\Ticket::STATUS_RESOLVED_BY_PROVINCE,
                \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                \App\Models\Ticket::STATUS_CLOSED,
            ], true), 'active' => $status === \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE],
            ['label' => 'Regional Review', 'done' => in_array($status, [
                \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                \App\Models\Ticket::STATUS_CLOSED,
            ], true), 'active' => in_array($status, [
                \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                \App\Models\Ticket::STATUS_PENDING,
                \App\Models\Ticket::STATUS_REOPENED,
            ], true)],
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
                    <p class="ticketing-card-subtitle">Ticket summary and current workflow status.</p>
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
                    ], true))
                        <button type="button" class="ticketing-btn ticketing-btn--success" data-ticketing-open="resolveRegionModal">
                            <i class="fas fa-circle-check"></i>
                            Resolve Ticket
                        </button>
                    @endif

                @endif

                @if ($canManageAdmin && in_array($ticket->status, [
                    \App\Models\Ticket::STATUS_RESOLVED_BY_PROVINCE,
                    \App\Models\Ticket::STATUS_RESOLVED_BY_REGION,
                ], true))
                    <button type="button" class="ticketing-btn ticketing-btn--dark" data-ticketing-open="closeTicketModal">
                        <i class="fas fa-box-archive"></i>
                        Close Ticket
                    </button>
                @endif

                @if ($canManageAdminTicket)
                    @if ($ticket->status === \App\Models\Ticket::STATUS_ESCALATED_TO_REGION)
                        <form method="POST" action="{{ route('ticketing.admin.start-review', $ticket) }}">
                            @csrf
                            <button type="submit" class="ticketing-btn ticketing-btn--primary">
                                <i class="fas fa-play"></i>
                                Start Superadmin Review
                            </button>
                        </form>
                    @endif

                    @if (in_array($ticket->status, [
                        \App\Models\Ticket::STATUS_ESCALATED_TO_REGION,
                        \App\Models\Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                    ], true))
                        <button type="button" class="ticketing-btn ticketing-btn--success" data-ticketing-open="resolveAdminModal">
                            <i class="fas fa-circle-check"></i>
                            Resolve Ticket
                        </button>
                    @endif
                @endif

                @if ($canMarkPending)
                    <button type="button" class="ticketing-btn ticketing-btn--warning" data-ticketing-open="pendingTicketModal">
                        <i class="fas fa-pause"></i>
                        Put on Hold
                    </button>
                @endif

                @if ($canResume)
                    <form method="POST" action="{{ route('ticketing.resume', $ticket) }}">
                        @csrf
                        <button type="submit" class="ticketing-btn ticketing-btn--primary">
                            <i class="fas fa-play"></i>
                            Resume Ticket
                        </button>
                    </form>
                @endif

                @if ($canReopen)
                    <button type="button" class="ticketing-btn ticketing-btn--primary" data-ticketing-open="reopenTicketModal">
                        <i class="fas fa-rotate-left"></i>
                        Reopen Ticket
                    </button>
                @endif
            </div>
        </div>

        <div class="ticketing-card ticketing-description-panel">
            <div class="ticketing-eyebrow">Request description</div>
            <h3 class="ticketing-card-title">What the requester reported</h3>
            <div class="ticketing-description-body">{{ $ticket->description }}</div>
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
                            <div class="ticketing-meta-label">
                                {{ $ticket->category?->isProgramRelated() ? 'Program' : 'Please Specify' }}
                            </div>
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
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button 
                                        type="button" 
                                        class="ticketing-btn ticketing-btn--primary ticketing-view-trigger"
                                        data-file-url="{{ route('ticketing.attachments.view', [$ticket, $attachment]) }}"
                                        data-download-url="{{ route('ticketing.attachments.download', [$ticket, $attachment]) }}"
                                        data-filename="{{ $attachment->original_name }}"
                                        data-mime-type="{{ $attachment->mime_type }}"
                                    >
                                        <i class="fas fa-eye"></i>
                                        View Proof
                                    </button>
                                    <a href="{{ route('ticketing.attachments.download', [$ticket, $attachment]) }}" class="ticketing-btn ticketing-btn--secondary">
                                        <i class="fas fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="ticketing-grid ticketing-grid--2">
            <div class="ticketing-card">
                <div class="ticketing-chat-header">
                    <div>
                        <div class="ticketing-eyebrow">Ticket-only conversation · {{ $ticket->ticket_number }}</div>
                        <h3 class="ticketing-card-title">Ticket chat</h3>
                        <p class="ticketing-card-subtitle">This conversation belongs only to this ticket and is separate from general Messages.</p>
                    </div>
                    <span class="ticketing-chat-count"><i class="fas fa-comments"></i> {{ $ticket->comments->count() }} replies</span>
                </div>

                @if ($ticket->comments->isEmpty())
                    <div class="ticketing-chat-empty"><i class="fas fa-message"></i><strong>No messages yet</strong><span>Start the conversation by sharing an update or question about this ticket.</span></div>
                @else
                    <div class="ticketing-chat-thread" aria-label="Ticket conversation">
                        @foreach ($ticket->comments as $comment)
                            @php($isOwnMessage = (int) ($comment->user_id ?? 0) === (int) auth()->id())
                            <div class="ticketing-chat-message @if($isOwnMessage) is-own @endif">
                                <div class="ticketing-chat-avatar" aria-hidden="true">
                                    {{ strtoupper(substr($comment->user?->fname ?? 'S', 0, 1) . substr($comment->user?->lname ?? 'U', 0, 1)) }}
                                </div>
                                <div class="ticketing-chat-content">
                                    <div class="ticketing-chat-meta"><strong>{{ $comment->user?->fullName() ?? 'System User' }}</strong><time datetime="{{ optional($comment->created_at)->toIso8601String() }}">{{ optional($comment->created_at)->format('M d, Y h:i A') }}</time></div>
                                    <div class="ticketing-chat-bubble">{{ $comment->comment }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @can('ticketing.addComment', $ticket)
                    <form method="POST" action="{{ route('ticketing.comments.store', $ticket) }}" class="ticketing-grid">
                        @csrf
                        <div class="ticketing-field">
                            <label for="comment">Write a message</label>
                            <textarea id="comment" name="comment" maxlength="5000" placeholder="Type your message about this ticket...">{{ old('comment') }}</textarea>
                        </div>
                        <button type="submit" class="ticketing-btn ticketing-btn--primary" style="justify-self: start;">
                            <i class="fas fa-paper-plane"></i>
                            Send message
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

    <div class="ticketing-modal" id="resolveAdminModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Resolve Ticket as Superadmin</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="resolveAdminModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.admin.resolve', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="resolution_note_admin">Resolution Note</label>
                    <textarea id="resolution_note_admin" name="resolution_note" placeholder="Optional note describing the applied resolution.">{{ old('resolution_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--success">
                    <i class="fas fa-circle-check"></i>
                    Confirm Resolution
                </button>
            </form>
        </div>
    </div>

    <div class="ticketing-modal" id="pendingTicketModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Put Ticket on Hold</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="pendingTicketModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.pending', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="pending_note">Hold Reason</label>
                    <textarea id="pending_note" name="resolution_note" required placeholder="Explain what information or action is pending.">{{ old('resolution_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--warning">
                    <i class="fas fa-pause"></i>
                    Put on Hold
                </button>
            </form>
        </div>
    </div>

    <div class="ticketing-modal" id="reopenTicketModal" aria-hidden="true">
        <div class="ticketing-modal-dialog">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title">Reopen Ticket</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="reopenTicketModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('ticketing.reopen', $ticket) }}" class="ticketing-grid">
                @csrf
                <div class="ticketing-field">
                    <label for="reopen_note">Reopen Reason</label>
                    <textarea id="reopen_note" name="resolution_note" required placeholder="Explain why this ticket needs follow-up.">{{ old('resolution_note') }}</textarea>
                </div>
                <button type="submit" class="ticketing-btn ticketing-btn--primary">
                    <i class="fas fa-rotate-left"></i>
                    Reopen Ticket
                </button>
            </form>
        </div>
    </div>

    <!-- View Attachment Preview Modal -->
    <div class="ticketing-modal" id="viewAttachmentModal" aria-hidden="true">
        <div class="ticketing-modal-dialog" style="width: min(900px, 95%); max-height: 90vh; display: flex; flex-direction: column;">
            <div class="ticketing-modal-header">
                <h3 class="ticketing-card-title" id="viewAttachmentModalTitle">Proof / MOV / Sample Preview</h3>
                <button type="button" class="ticketing-modal-close" data-ticketing-close="viewAttachmentModal">&times;</button>
            </div>
            <div class="ticketing-modal-body" style="flex-grow: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 450px;">
                <div id="viewAttachmentModalContent" style="width: 100%; display: flex; align-items: center; justify-content: center;">
                    <!-- Content will be injected dynamically -->
                </div>
            </div>
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

            // View attachment preview handler
            const viewTriggers = document.querySelectorAll('.ticketing-view-trigger');
            const previewModal = document.getElementById('viewAttachmentModal');
            const previewContent = document.getElementById('viewAttachmentModalContent');
            const previewTitle = document.getElementById('viewAttachmentModalTitle');

            if (previewModal && previewContent && previewTitle) {
                const closePreviewModal = () => {
                    previewModal.classList.remove('is-open');
                    previewModal.setAttribute('aria-hidden', 'true');
                    previewContent.innerHTML = '';
                };

                viewTriggers.forEach((trigger) => {
                    trigger.addEventListener('click', () => {
                        const fileUrl = trigger.getAttribute('data-file-url');
                        const downloadUrl = trigger.getAttribute('data-download-url');
                        const filename = trigger.getAttribute('data-filename');
                        const mimeType = (trigger.getAttribute('data-mime-type') || '').toLowerCase();

                        previewTitle.textContent = filename;
                        previewContent.innerHTML = '';

                        let previewEl = null;

                        if (mimeType.startsWith('image/')) {
                            previewEl = document.createElement('img');
                            previewEl.src = fileUrl;
                            previewEl.className = 'ticketing-modal-preview';
                            previewEl.alt = filename;
                        } else if (mimeType === 'application/pdf') {
                            previewEl = document.createElement('iframe');
                            previewEl.src = fileUrl;
                            previewEl.className = 'ticketing-modal-preview-pdf';
                        } else if (mimeType.startsWith('video/')) {
                            previewEl = document.createElement('video');
                            previewEl.src = fileUrl;
                            previewEl.controls = true;
                            previewEl.className = 'ticketing-modal-preview-video';
                        } else {
                            const fallbackContainer = document.createElement('div');
                            fallbackContainer.className = 'ticketing-modal-fallback';
                            fallbackContainer.innerHTML = `
                                <div class="ticketing-modal-fallback-icon">
                                    <i class="fas fa-file-arrow-down"></i>
                                </div>
                                <h4 style="margin: 0 0 8px; color: #0f172a; font-weight: 700;">No Preview Available</h4>
                                <p style="margin: 0 0 16px; color: #64748b; font-size: 13px;">This file format (${mimeType || 'unknown'}) cannot be previewed directly in the browser.</p>
                                <a href="${downloadUrl}" class="ticketing-btn ticketing-btn--primary">
                                    <i class="fas fa-download"></i>
                                    Download to View
                                </a>
                            `;
                            previewEl = fallbackContainer;
                        }

                        previewContent.appendChild(previewEl);
                        previewModal.classList.add('is-open');
                        previewModal.setAttribute('aria-hidden', 'false');
                    });
                });

                // Clear/Stop video when closing preview modal
                previewModal.querySelectorAll('[data-ticketing-close]').forEach((closer) => {
                    closer.addEventListener('click', (e) => {
                        e.stopPropagation();
                        closePreviewModal();
                    });
                });

                previewModal.addEventListener('click', (event) => {
                    if (event.target === previewModal) {
                        closePreviewModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && previewModal.classList.contains('is-open')) {
                        closePreviewModal();
                    }
                });
            }
        })();
    </script>
@endsection
