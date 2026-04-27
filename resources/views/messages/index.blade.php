@extends('layouts.dashboard')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
@php
    $threadItems = collect($threads->items());
    $conversationGroups = collect($conversationGroups ?? []);
    $selectedGroupMembers = collect($selectedGroupMembers ?? []);
    $selectedThreadId = (int) ($selectedThreadId ?? 0);
    $latestConversationId = (int) ($conversation->max('id') ?? 0);
    $renameGroupErrors = $errors->renameGroup ?? new \Illuminate\Support\MessageBag();

    $nameInitials = static function (?string $name): string {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $initials = strtoupper(substr((string) ($parts[0] ?? ''), 0, 1) . substr((string) ($parts[1] ?? ''), 0, 1));

        return $initials !== '' ? $initials : 'U';
    };

    $avatarClassFor = static function (?string $seed): string {
        return 'msg-avatar-theme-' . (abs((int) crc32((string) $seed)) % 6);
    };

    $selectedName = '';
    $selectedSubtitle = '';
    $selectedInitials = 'U';
    $selectedAvatarClass = 'msg-avatar-theme-0';
    $selectedUsesLogo = false;
    $selectedIsGroup = false;
    $selectedGroupCustomName = '';
    $selectedGroupPrimaryInitials = 'U';
    $selectedGroupSecondaryInitials = 'U';
    $selectedGroupPrimaryAvatarClass = 'msg-avatar-theme-0';
    $selectedGroupSecondaryAvatarClass = 'msg-avatar-theme-1';
    $selectedLogoUrl = asset('PRISM2.png');
    if ($selectedUser) {
        $selectedName = trim((string) (($selectedUser->fname ?? '') . ' ' . ($selectedUser->lname ?? '')));
        $selectedName = $selectedName !== '' ? $selectedName : 'Unknown User';
        $selectedPosition = trim((string) ($selectedUser->position ?? ''));
        $selectedIsGroup = $selectedPosition === 'Group chat';
        $selectedGroupCustomName = trim((string) ($selectedUser->custom_name ?? ''));
        $selectedLocation = collect([
            trim((string) ($selectedUser->office ?? '')),
            trim((string) ($selectedUser->province ?? '')),
            trim((string) ($selectedUser->region ?? '')),
        ])->filter()->unique(fn ($value) => mb_strtolower((string) $value))->implode(', ');

        if ($selectedIsGroup) {
            $selectedSubtitle = $selectedLocation !== '' ? $selectedLocation : 'Group chat';
        } elseif ($selectedPosition !== '' && $selectedLocation !== '') {
            $selectedSubtitle = $selectedPosition . ' · ' . $selectedLocation;
        } elseif ($selectedPosition !== '') {
            $selectedSubtitle = $selectedPosition;
        } elseif ($selectedLocation !== '') {
            $selectedSubtitle = $selectedLocation;
        } else {
            $selectedSubtitle = 'PDMU User';
        }
        $selectedInitials = $nameInitials($selectedName);
        $selectedAvatarClass = $avatarClassFor($selectedName . '|' . $selectedThreadId);
        $selectedUsesLogo = str_contains(strtolower($selectedName . ' ' . $selectedSubtitle), 'pdmu');

        if ($selectedIsGroup) {
            $selectedGroupAvatarNames = $selectedGroupMembers
                ->filter(fn ($member) => empty($member->is_me))
                ->map(fn ($member) => trim((string) ($member->name ?? '')))
                ->filter()
                ->unique(fn ($value) => mb_strtolower((string) $value))
                ->values();

            if ($selectedGroupAvatarNames->count() < 2) {
                $selectedGroupAvatarNames = $selectedGroupMembers
                    ->map(fn ($member) => trim((string) ($member->name ?? '')))
                    ->filter()
                    ->unique(fn ($value) => mb_strtolower((string) $value))
                    ->values();
            }

            if ($selectedGroupAvatarNames->count() < 2) {
                $selectedGroupAvatarNames = $selectedGroupMembers
                    ->map(fn ($member) => trim((string) ($member->name ?? '')))
                    ->filter()
                    ->values();
            }

            if ($selectedGroupAvatarNames->count() < 2) {
                $selectedGroupAvatarNames = collect(preg_split('/\s*,\s*/', (string) preg_replace('/\s+\+\d+$/', '', $selectedName)) ?: [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->values();
            }

            $selectedGroupPrimaryName = $selectedGroupAvatarNames->get(0, $selectedName);
            $selectedGroupSecondaryName = $selectedGroupAvatarNames->get(1, $selectedGroupPrimaryName);
            $selectedGroupPrimaryInitials = $nameInitials($selectedGroupPrimaryName);
            $selectedGroupSecondaryInitials = $nameInitials($selectedGroupSecondaryName);
            $selectedGroupPrimaryAvatarClass = $avatarClassFor($selectedGroupPrimaryName . '|' . $selectedThreadId . '|primary');
            $selectedGroupSecondaryAvatarClass = $avatarClassFor($selectedGroupSecondaryName . '|' . $selectedThreadId . '|secondary');
        }
    }

    $groupInfoShouldOpen = $selectedIsGroup && ($renameGroupErrors->isNotEmpty() || (string) old('group_rename_open') === '1');

    $latestConversationMessage = $conversation->last();
    $bannerAuthor = 'PRISM';
    $bannerPreview = 'Messages stay inside the platform so teams can coordinate from one workspace.';
    if ($latestConversationMessage) {
        $bannerAuthor = (int) ($latestConversationMessage->sender_id ?? 0) === (int) auth()->id() ? 'You' : $selectedName;
        $latestConversationText = trim((string) ($latestConversationMessage->message ?? ''));
        $bannerPreview = $latestConversationText !== ''
            ? \Illuminate\Support\Str::limit($latestConversationText, 180)
            : (!empty($latestConversationMessage->image_path) ? 'Sent a photo' : $bannerPreview);
    }

    $composeOpen = old('compose_mode') === '1';
@endphp

<div class="msg-page">
    @if(session('success'))
        <div class="msg-flash msg-flash-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="msg-flash msg-flash-error">{{ session('error') }}</div>
    @endif

    <section class="msg-shell">
        <aside class="msg-sidebar">
            <div class="msg-sidebar-head">
                <div class="msg-sidebar-title">
                    <h1>Chats</h1>
                </div>
                <div class="msg-sidebar-actions">
                    <button type="button" class="msg-icon-button msg-icon-button-muted" aria-label="Conversation options" title="Conversation options">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <button type="button" class="msg-icon-button" id="toggleCompose" aria-label="New message" title="New message">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
            </div>

            <label class="msg-search-bar" for="msgSearch">
                <i class="fas fa-search"></i>
                <input id="msgSearch" type="text" placeholder="Search Messenger">
            </label>

            <div class="msg-filter-row" aria-label="Conversation filters">
                <button type="button" class="msg-filter-chip is-active" id="msgFilterAll" data-thread-filter="all">All</button>
                <button type="button" class="msg-filter-chip msg-filter-chip-unread" id="msgFilterUnread" data-thread-filter="unread" aria-label="Unread conversations">
                    <span>Unread</span>
                    <span class="msg-filter-chip-count" data-unread-total {{ (int) ($unreadMessages ?? 0) > 0 ? '' : 'hidden' }}>
                        {{ (int) ($unreadMessages ?? 0) > 99 ? '99+' : (int) ($unreadMessages ?? 0) }}
                    </span>
                </button>
                <button type="button" class="msg-filter-chip" id="msgFilterGroups" data-thread-filter="groups" aria-label="Group conversations">Groups</button>
            </div>

            <div class="msg-list" id="msgList">
                @forelse($threadItems as $thread)
                    @php
                        $name = trim((string) ($thread['name'] ?? 'Unknown User'));
                        $name = $name !== '' ? $name : 'Unknown User';
                        $initials = $nameInitials($name);
                        $subtitle = trim((string) ($thread['subtitle'] ?? ''));
                        $preview = trim((string) ($thread['preview'] ?? ''));
                        $previewSender = trim((string) ($thread['preview_sender'] ?? ''));
                        $previewIsMine = (bool) ($thread['preview_is_mine'] ?? false);
                        $showUnreadLabel = (bool) ($thread['preview_show_unread_label'] ?? false);
                        $isGroupThread = (bool) ($thread['is_group'] ?? false);
                        $isActive = $selectedThreadId > 0 && $selectedThreadId === (int) ($thread['thread_id'] ?? 0);
                        $searchText = strtolower($name . ' ' . (string) ($thread['subtitle'] ?? '') . ' ' . (string) ($thread['preview'] ?? ''));
                        $latestAt = $thread['latest_at'] ?? null;
                        $time = trim((string) ($thread['time'] ?? ''));
                        $timeDateTime = !empty($latestAt) ? \Illuminate\Support\Carbon::parse($latestAt)->toIso8601String() : '';
                        $threadAvatarClass = $avatarClassFor($name . '|' . ($thread['thread_id'] ?? ''));
                        $isUnread = (int) ($thread['unread'] ?? 0) > 0;
                        $readStatusLabel = $isUnread ? 'Unread' : 'Read';
                        $threadDetail = $preview !== '' ? $preview : ($subtitle !== '' ? $subtitle : 'No message preview available.');
                        $previewPrefix = '';
                        if ($previewIsMine) {
                            $previewPrefix = 'You';
                        } elseif ($isGroupThread && $previewSender !== '') {
                            $previewPrefix = $previewSender;
                        }

                        $groupAvatarNames = collect($thread['avatar_members'] ?? [])
                            ->map(fn ($item) => trim((string) $item))
                            ->filter()
                            ->values();
                        if ($groupAvatarNames->isEmpty()) {
                            $groupAvatarNames = collect(preg_split('/\s*,\s*/', (string) preg_replace('/\s+\+\d+$/', '', $name)) ?: [])
                                ->map(fn ($item) => trim((string) $item))
                                ->filter()
                                ->values();
                        }
                        $groupPrimaryName = $groupAvatarNames->get(0, $name);
                        $groupSecondaryName = $groupAvatarNames->get(1, $groupPrimaryName);
                        $groupPrimaryInitials = $nameInitials($groupPrimaryName);
                        $groupSecondaryInitials = $nameInitials($groupSecondaryName);
                        $groupPrimaryAvatarClass = $avatarClassFor($groupPrimaryName . '|' . ($thread['thread_id'] ?? '') . '|primary');
                        $groupSecondaryAvatarClass = $avatarClassFor($groupSecondaryName . '|' . ($thread['thread_id'] ?? '') . '|secondary');
                    @endphp
                    <div class="msg-thread-card {{ $isActive ? 'is-active' : '' }} {{ $isUnread ? 'is-unread' : '' }}" data-search="{{ $searchText }}" data-thread-unread="{{ $isUnread ? '1' : '0' }}" data-thread-group="{{ $isGroupThread ? '1' : '0' }}">
                        <a href="{{ route('messages.index', ['thread' => $thread['thread_id']]) }}" class="msg-thread">
                            <span class="msg-thread-avatar-shell">
                                @if($isGroupThread)
                                    <span class="msg-avatar-stack">
                                        <span class="msg-avatar msg-avatar-group msg-avatar-group-back {{ $groupSecondaryAvatarClass }}">
                                            <span>{{ $groupSecondaryInitials }}</span>
                                        </span>
                                        <span class="msg-avatar msg-avatar-group msg-avatar-group-front {{ $groupPrimaryAvatarClass }}">
                                            <span>{{ $groupPrimaryInitials }}</span>
                                        </span>
                                    </span>
                                @else
                                    <span class="msg-avatar {{ $threadAvatarClass }}">
                                        <span>{{ $initials }}</span>
                                    </span>
                                @endif
                            </span>
                            <span class="msg-thread-body">
                                <span class="msg-thread-head">
                                    <strong>{{ $name }}</strong>
                                    <span class="msg-thread-time-stack">
                                        @if($time !== '')
                                            <time class="msg-thread-time" datetime="{{ $timeDateTime }}">{{ $time }}</time>
                                        @endif
                                        <span class="msg-thread-read-state {{ $isUnread ? 'is-unread' : 'is-read' }}">{{ $readStatusLabel }}</span>
                                    </span>
                                </span>
                                <span class="msg-thread-meta">
                                    @if($showUnreadLabel)
                                        <span class="msg-thread-unread-label">Unread message:</span>
                                    @endif
                                    @if($previewPrefix !== '')
                                        <span class="msg-thread-preview-prefix">{{ $previewPrefix }}:</span>
                                    @endif
                                    <span class="msg-thread-preview">{{ $threadDetail }}</span>
                                </span>
                            </span>
                        </a>
                        <div class="msg-thread-status">
                            <button
                                type="button"
                                class="msg-thread-more"
                                data-thread-menu-toggle
                                aria-label="Conversation options"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-controls="msgThreadMenu{{ (int) ($thread['thread_id'] ?? 0) }}"
                            >
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            @if($isUnread)
                                <span class="msg-thread-dot" aria-hidden="true"></span>
                            @endif
                            <div class="msg-thread-menu" id="msgThreadMenu{{ (int) ($thread['thread_id'] ?? 0) }}" data-thread-menu hidden>
                                <form method="POST" action="{{ route($isUnread ? 'messages.mark-read' : 'messages.mark-unread', ['thread' => $thread['thread_id']]) }}">
                                    @csrf
                                    <input type="hidden" name="current_thread" value="{{ $selectedThreadId }}">
                                    <button type="submit" class="msg-thread-menu-item">
                                        <i class="fas {{ $isUnread ? 'fa-envelope-open' : 'fa-envelope' }}"></i>
                                        <span>{{ $isUnread ? 'Mark as read' : 'Mark as unread' }}</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('messages.delete', ['thread' => $thread['thread_id']]) }}">
                                    @csrf
                                    <input type="hidden" name="current_thread" value="{{ $selectedThreadId }}">
                                    <button type="submit" class="msg-thread-menu-item is-danger">
                                        <i class="fas fa-trash-alt"></i>
                                        <span>Delete conversation</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="msg-thread-empty">
                        <div class="msg-thread-empty-icon"><i class="fas fa-comments"></i></div>
                        <h2>No conversations yet</h2>
                        <p>Click the new message button to start a direct message.</p>
                    </div>
                @endforelse
            </div>

            @if($threads->hasPages())
                <div class="msg-pager">{{ $threads->links() }}</div>
            @endif
        </aside>

        <div class="msg-chat" id="msgChatRoot" data-thread="{{ $selectedThreadId }}" data-auth-user="{{ (int) auth()->id() }}" data-latest-thread-id="{{ $latestConversationId }}" data-latest-global-id="{{ (int) ($latestGlobalMessageId ?? 0) }}">
            <div class="msg-chat-main" id="msgChatMain">
                <div class="msg-chat-compose-panel{{ $composeOpen ? ' is-open' : '' }}" id="msgChatComposePanel">
                    @php
                        $oldRecipientIds = collect(old('recipient_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
                    @endphp
                    <form method="POST" action="{{ route('messages.store') }}" class="msg-compose-form msg-compose-form-chat" id="msgComposeForm">
                        @csrf
                        <input type="hidden" name="compose_mode" value="1">

                        @if(old('compose_mode') === '1' && $errors->any())
                            <div class="msg-compose-errors" role="alert">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="msg-compose-recipient" id="composeRecipientPicker">
                            <div class="msg-compose-recipient-row">
                                <label for="composeUserSearch" class="msg-compose-recipient-label">To:</label>
                                <div class="msg-compose-recipient-field" id="composeRecipientField">
                                    <div class="msg-recipient-chips" id="composeRecipientChips"></div>
                                    <input
                                        id="composeUserSearch"
                                        class="msg-user-search-input"
                                        type="text"
                                        autocomplete="off"
                                        role="combobox"
                                        aria-expanded="false"
                                        aria-controls="composeUserSuggestions"
                                        aria-autocomplete="list"
                                    >
                                </div>
                            </div>
                            <div id="composeRecipientValues"></div>
                            <div class="msg-user-suggestions" id="composeUserSuggestions" role="listbox">
                                @foreach($availableUsers as $user)
                                    @php
                                        $displayName = trim((string) (($user->fname ?? '') . ' ' . ($user->lname ?? '')));
                                        $displayName = $displayName !== '' ? $displayName : 'Unknown User';
                                        $subtitle = trim((string) ($user->position ?: $user->office ?: 'PDMU User'));
                                        $label = $displayName . ($subtitle !== '' ? ' - ' . $subtitle : '');
                                        $initialsParts = preg_split('/\s+/', $displayName);
                                        $initialA = isset($initialsParts[0][0]) ? strtoupper($initialsParts[0][0]) : 'U';
                                        $initialB = isset($initialsParts[1][0]) ? strtoupper($initialsParts[1][0]) : '';
                                        $initials = $initialA . $initialB;
                                    @endphp
                                    <button
                                        type="button"
                                        class="msg-user-option"
                                        data-user-id="{{ $user->idno }}"
                                        data-user-label="{{ $label }}"
                                        data-user-subtitle="{{ $subtitle !== '' ? $subtitle : 'PDMU User' }}"
                                        data-search="{{ strtolower($displayName . ' ' . $subtitle) }}"
                                        role="option"
                                    >
                                        <span class="msg-user-option-avatar">{{ $initials }}</span>
                                        <span class="msg-user-option-copy">
                                            <strong>{{ $displayName }}</strong>
                                            <small>{{ $subtitle !== '' ? $subtitle : 'PDMU User' }}</small>
                                        </span>
                                    </button>
                                @endforeach
                                <div class="msg-user-empty" id="composeUserEmpty" hidden>No matching users found.</div>
                            </div>
                            @if($oldRecipientIds->isNotEmpty())
                                @foreach($oldRecipientIds as $oldRecipientId)
                                    <input type="hidden" name="recipient_ids[]" value="{{ $oldRecipientId }}" data-old-recipient="1">
                                @endforeach
                            @endif
                        </div>
                        <div class="msg-compose-draft{{ $oldRecipientIds->isNotEmpty() ? '' : ' is-hidden' }}" id="composeDraftSection">
                            <textarea id="composeMessage" name="message" rows="4" maxlength="2000" placeholder="Write your message..." {{ $oldRecipientIds->isNotEmpty() ? 'required' : '' }}>{{ old('message') }}</textarea>
                            <div class="msg-compose-actions">
                                <span>Messages will be sent to all selected recipients</span>
                                <div class="msg-compose-buttons">
                                    <button type="button" class="msg-compose-secondary" id="msgComposeCancel">Cancel</button>
                                    <button type="submit">
                                        <i class="fas fa-paper-plane"></i>
                                        <span>Send</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="msg-chat-conversation{{ $composeOpen ? ' is-hidden' : '' }}" id="msgChatConversation">
                @if($selectedUser)
                    <div class="msg-chat-head">
                        <div class="msg-chat-user">
                            @if($selectedIsGroup)
                                <span class="msg-chat-user-avatar-shell">
                                    <span class="msg-avatar-stack">
                                        <span class="msg-avatar msg-avatar-group msg-avatar-group-back {{ $selectedGroupSecondaryAvatarClass }}">
                                            <span>{{ $selectedGroupSecondaryInitials }}</span>
                                        </span>
                                        <span class="msg-avatar msg-avatar-group msg-avatar-group-front {{ $selectedGroupPrimaryAvatarClass }}">
                                            <span>{{ $selectedGroupPrimaryInitials }}</span>
                                        </span>
                                    </span>
                                </span>
                            @else
                                <span class="msg-chat-user-avatar {{ $selectedAvatarClass }}">
                                    @if($selectedUsesLogo)
                                        <img src="{{ $selectedLogoUrl }}" alt="{{ $selectedName }}">
                                    @else
                                        <span>{{ $selectedInitials }}</span>
                                    @endif
                                </span>
                            @endif
                            <div class="msg-chat-user-copy">
                                <strong>{{ $selectedName }}</strong>
                                <span>{{ $selectedSubtitle }}</span>
                            </div>
                        </div>
                        @if($selectedIsGroup)
                            <div class="msg-chat-actions">
                                <button type="button" id="msgGroupInfoButton" class="msg-chat-action msg-chat-action-group-info" title="Group info" aria-label="Group info" aria-controls="msgGroupInfoModal" aria-expanded="false">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                        @endif

                    </div>
                    @if($selectedIsGroup)
                        <div class="msg-group-info-modal" id="msgGroupInfoModal" data-auto-open="{{ $groupInfoShouldOpen ? '1' : '0' }}" {{ $groupInfoShouldOpen ? '' : 'hidden' }}>
                            <div class="msg-group-info-backdrop" data-group-info-close></div>
                            <div class="msg-group-info-dialog" role="dialog" aria-modal="true" aria-labelledby="msgGroupInfoTitle">
                                <div class="msg-group-info-head">
                                    <div class="msg-group-info-copy">
                                        <strong id="msgGroupInfoTitle">Group Members</strong>
                                        <span>{{ number_format($selectedGroupMembers->count()) }} people in this chat</span>
                                    </div>
                                    <button type="button" class="msg-group-info-close" data-group-info-close aria-label="Close group info">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('messages.rename-group', ['thread' => $selectedThreadId]) }}" class="msg-group-rename-form" id="msgGroupRenameForm" data-initial-editing="{{ $renameGroupErrors->isNotEmpty() ? '1' : '0' }}">
                                    @csrf
                                    <input type="hidden" name="group_rename_open" value="1">
                                    <label class="msg-group-rename-label" for="msgGroupNameInput">Group name</label>
                                    <div class="msg-group-rename-row">
                                        <input
                                            id="msgGroupNameInput"
                                            type="text"
                                            name="group_name"
                                            maxlength="120"
                                            value="{{ old('group_name', $selectedGroupCustomName) }}"
                                            placeholder="Enter group name"
                                            aria-readonly="{{ $renameGroupErrors->isNotEmpty() ? 'false' : 'true' }}"
                                            {{ $renameGroupErrors->isEmpty() ? 'readonly' : '' }}
                                            required
                                        >
                                        <button
                                            type="button"
                                            id="msgGroupRenameToggle"
                                            class="msg-group-rename-toggle{{ $renameGroupErrors->isNotEmpty() ? ' is-editing' : '' }}"
                                            title="{{ $renameGroupErrors->isNotEmpty() ? 'Save group name' : 'Edit group name' }}"
                                            aria-label="{{ $renameGroupErrors->isNotEmpty() ? 'Save group name' : 'Edit group name' }}"
                                            aria-controls="msgGroupNameInput"
                                            aria-pressed="{{ $renameGroupErrors->isNotEmpty() ? 'true' : 'false' }}"
                                        >
                                            <i class="fas {{ $renameGroupErrors->isNotEmpty() ? 'fa-save' : 'fa-pen' }}"></i>
                                        </button>
                                    </div>
                                    @if($renameGroupErrors->has('group_name'))
                                        <p class="msg-group-rename-error">{{ $renameGroupErrors->first('group_name') }}</p>
                                    @endif
                                </form>
                                <div class="msg-group-info-list">
                                    @forelse($selectedGroupMembers as $member)
                                        @php
                                            $memberName = trim((string) ($member->name ?? 'Unknown User'));
                                            $memberName = $memberName !== '' ? $memberName : 'Unknown User';
                                            $memberInitials = $nameInitials($memberName);
                                            $memberAvatarClass = $avatarClassFor($memberName . '|member|' . ($member->idno ?? ''));
                                            $memberPosition = trim((string) ($member->position ?? ''));
                                            $memberLocation = collect([
                                                trim((string) ($member->office ?? '')),
                                                trim((string) ($member->province ?? '')),
                                                trim((string) ($member->region ?? '')),
                                            ])->filter()->unique(fn ($value) => mb_strtolower((string) $value))->implode(', ');
                                        @endphp
                                        <div class="msg-group-member-card">
                                            <span class="msg-group-member-avatar {{ $memberAvatarClass }}">
                                                <span>{{ $memberInitials }}</span>
                                            </span>
                                            <div class="msg-group-member-copy">
                                                <div class="msg-group-member-head">
                                                    <strong>{{ $memberName }}</strong>
                                                    @if(!empty($member->is_me))
                                                        <span class="msg-group-member-badge">You</span>
                                                    @endif
                                                </div>
                                                <span>{{ $memberPosition !== '' ? $memberPosition : 'PDMU User' }}</span>
                                                <small>{{ $memberLocation !== '' ? $memberLocation : 'Office/location not set' }}</small>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="msg-group-info-empty">No member details available for this chat.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="msg-chat-body" id="msgChatBody">
                        @if($conversationGroups->isEmpty())
                            <div class="msg-empty-thread msg-empty-thread-chat">No messages yet. Start this conversation below.</div>
                        @endif

                        @foreach($conversationGroups as $group)
                            @php
                                $isMine = (bool) ($group['is_mine'] ?? false);
                                $messageTime = trim((string) ($group['time'] ?? ''));
                                $messageText = trim((string) ($group['message'] ?? ''));
                                $groupImages = collect($group['images'] ?? []);
                                $hasMultipleImages = $groupImages->count() > 1;
                            @endphp
                            <div class="msg-row {{ $isMine ? 'right' : 'left' }}">
                                @unless($isMine)
                                    <span class="msg-inline-avatar {{ $selectedAvatarClass }}">
                                        @if($selectedUsesLogo)
                                            <img src="{{ $selectedLogoUrl }}" alt="{{ $selectedName }}">
                                        @else
                                            <span>{{ $selectedInitials }}</span>
                                        @endif
                                    </span>
                                @endunless

                                <div class="msg-bubble-stack {{ $isMine ? 'outgoing' : 'incoming' }}">
                                    @if($groupImages->isNotEmpty())
                                        <div class="msg-message-gallery {{ $isMine ? 'outgoing' : 'incoming' }} {{ $hasMultipleImages ? 'is-multi' : 'is-single' }}">
                                            @foreach($groupImages as $groupImage)
                                                <a href="{{ $groupImage['url'] ?? '' }}" class="msg-message-image-link {{ $isMine ? 'outgoing' : 'incoming' }}" target="_blank" rel="noopener noreferrer">
                                                    <img src="{{ $groupImage['url'] ?? '' }}" alt="{{ $groupImage['name'] ?? 'Shared image' }}" class="msg-message-image">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($messageText !== '')
                                        <div class="msg-bubble {{ $isMine ? 'outgoing' : 'incoming' }}">
                                            <div class="msg-text">{{ $messageText }}</div>
                                        </div>
                                    @endif
                                    <div class="msg-meta {{ $isMine ? 'out' : '' }}"><span>{{ $messageTime }}</span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="msg-chat-input">
                        <div class="msg-image-preview-list" id="msgImagePreviewList" hidden></div>
                        <form method="POST" action="{{ route('messages.store') }}" class="msg-send-form" id="msgSendForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="thread_id" value="{{ (int) $selectedThreadId }}">
                            <input type="file" name="images[]" id="msgImageInput" class="msg-file-input" accept="image/*" multiple hidden>

                            <button type="button" class="msg-tool-button msg-tool-button-attach" title="Select image" aria-label="Select image" aria-controls="msgImageInput">
                                <i class="far fa-image"></i>
                            </button>

                            <div class="msg-composer-field">
                                <textarea name="message" rows="1" maxlength="2000" placeholder="Aa"></textarea>
                                <button type="button" class="msg-tool-button msg-tool-button-smile" title="Emoji picker is not available yet" aria-label="Emoji">
                                    <i class="far fa-smile"></i>
                                </button>
                            </div>

                            <button type="submit" class="msg-submit-button" aria-label="Send message">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <div class="msg-upload-notice" id="msgUploadNotice" hidden aria-live="polite"></div>
                    </div>
                    <div class="msg-image-modal" id="msgImageModal" hidden>
                        <div class="msg-image-modal-backdrop" data-image-modal-close></div>
                        <div class="msg-image-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="msgImageModalTitle">
                            <button type="button" class="msg-image-modal-close" id="msgImageModalClose" aria-label="Close image preview">
                                <i class="fas fa-times"></i>
                            </button>
                            <img src="" alt="" class="msg-image-modal-image" id="msgImageModalImage">
                            <div class="msg-image-modal-title" id="msgImageModalTitle"></div>
                        </div>
                    </div>
                @endif
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.msg-page{display:flex;flex-direction:column;gap:14px;width:100%;height:calc(100vh - 130px);min-height:720px;padding-bottom:0;box-sizing:border-box}
.msg-flash{margin-bottom:0;padding:14px 18px;border-radius:16px;border:1px solid transparent;font-size:13px;font-weight:700;line-height:1.5}
.msg-flash-success{background:#ecfdf5;border-color:#a7f3d0;color:#166534}
.msg-flash-error{background:#fff1f2;border-color:#fecdd3;color:#be123c}
.msg-shell{display:grid;grid-template-columns:350px minmax(0,1fr);flex:1 1 auto;width:100%;min-width:0;min-height:0;border-radius:28px;overflow:hidden;background:rgba(255,255,255,.96);border:1px solid rgba(148,163,184,.28);box-shadow:0 28px 70px rgba(15,23,42,.10)}
.msg-sidebar{display:flex;flex-direction:column;min-width:0;min-height:0;background:linear-gradient(180deg,#ffffff 0%,#f7faff 100%);border-right:1px solid #dbe3ee}
.msg-sidebar-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 16px 12px}
.msg-sidebar-title{display:flex;align-items:center;gap:10px;min-width:0}
.msg-sidebar-title h1{margin:0;color:#111827;font-size:34px;line-height:1;font-weight:800;letter-spacing:-.04em}
.msg-count-pill{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:28px;padding:0 10px;border-radius:999px;background:#e0ecff;color:#0a66ff;font-size:12px;font-weight:800}
.msg-sidebar-actions{display:flex;align-items:center;gap:10px}
.msg-icon-button{width:42px;height:42px;border:none;border-radius:999px;background:#e7f0fb;color:#0a66ff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 8px 18px rgba(10,102,255,.10);transition:transform .18s ease,background-color .18s ease,color .18s ease}
.msg-icon-button:hover{transform:translateY(-1px);background:#0a66ff;color:#fff}
.msg-icon-button.is-active{background:#0a66ff;color:#fff}
.msg-icon-button-muted{background:#eef2f7;color:#4b5563;box-shadow:none}
.msg-search-bar{display:flex;align-items:center;gap:10px;margin:0 16px;padding:12px 16px;border-radius:999px;background:#eef2f7;color:#6b7280;box-shadow:inset 0 1px 0 rgba(255,255,255,.8)}
.msg-search-bar input{flex:1;min-width:0;border:none;outline:none;background:transparent;color:#111827;font-size:15px}
.msg-search-bar input::placeholder{color:#6b7280}
.msg-filter-row{display:flex;align-items:center;gap:8px;padding:14px 16px 10px;overflow-x:auto;scrollbar-width:none}
.msg-filter-row::-webkit-scrollbar{display:none}
.msg-filter-chip{position:relative;border:none;background:rgba(255,255,255,.72);color:#111827;font-size:13px;font-weight:700;padding:9px 14px;border-radius:999px;cursor:pointer;white-space:nowrap;transition:background-color .18s ease,color .18s ease,box-shadow .18s ease;box-shadow:inset 0 0 0 1px #e2e8f0}
.msg-filter-chip.is-active{background:#deebff;color:#0a66ff;box-shadow:inset 0 0 0 1px #bfdbfe}
.msg-filter-chip:not(.is-active){color:#1f2937}
.msg-filter-chip-unread{padding-right:18px}
.msg-filter-chip-count{position:absolute;top:-4px;right:-3px;min-width:16px;height:16px;padding:0 4px;border-radius:999px;background:#dc2626;color:#fff;font-size:10px;font-weight:800;line-height:1;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 0 0 2px #fff}
.msg-compose-form{display:grid;gap:12px;padding:16px;border-radius:22px;border:1px solid #dfe8f3;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.05)}
.msg-compose-head{display:grid;gap:2px}
.msg-compose-head strong{font-size:15px;color:#0f172a}
.msg-compose-head span{font-size:12px;color:#64748b}
.msg-compose-errors{display:grid;gap:6px;padding:12px 14px;border-radius:16px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c;font-size:13px;line-height:1.5}
.msg-compose-errors p{margin:0}
.msg-compose-form textarea{width:100%;border:1px solid #d6e2f0;border-radius:16px;background:#f8fbff;color:#0f172a;padding:12px 14px;font-size:14px;outline:none;transition:border-color .18s ease,box-shadow .18s ease,background-color .18s ease}
.msg-compose-form textarea:focus,.msg-send-form textarea:focus{border-color:#8fb9ff;box-shadow:0 0 0 4px rgba(10,102,255,.10);background:#fff}
.msg-compose-recipient{position:relative;display:grid;gap:8px;padding:0 0 10px;border-bottom:1px solid #d9e1ea}
.msg-compose-recipient-row{display:flex;align-items:center;gap:10px;width:100%}
.msg-compose-recipient-label{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:flex-start;height:28px;margin:0;font-size:14px;font-weight:700;line-height:1;color:#1e293b;padding:0}
.msg-compose-recipient-field{flex:1 1 auto;width:100%;display:flex;align-items:center;gap:8px;flex-wrap:nowrap;min-height:28px;padding:0;border:none;border-radius:0;background:transparent;box-shadow:none;overflow:hidden}
.msg-compose-recipient-field:focus-within{border:none;box-shadow:none;background:transparent}
.msg-recipient-chips{display:flex;align-items:center;gap:6px;flex-wrap:wrap;max-width:100%}
.msg-recipient-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:10px;background:#dbeafe;color:#0b4ea2;font-size:14px;font-weight:700;line-height:1}
.msg-recipient-chip button{border:none;background:transparent;color:#0b4ea2;padding:0;line-height:1;cursor:pointer;font-size:16px}
.msg-user-search-input{flex:1 1 auto;min-width:120px;width:auto;padding:0;border:none !important;outline:none;appearance:none;-webkit-appearance:none;background:transparent;border-radius:0;box-shadow:none;line-height:1.35}
.msg-user-search-input:focus{border:none !important;outline:none;box-shadow:none}
.msg-user-suggestions{position:absolute;top:100%;left:0;right:0;z-index:25;margin-top:6px;max-height:360px;overflow-y:auto;border:1px solid #d6e2f0;border-radius:14px;background:#fff;box-shadow:0 16px 40px rgba(15,23,42,.14);display:none}
.msg-user-suggestions.is-open{display:grid}
.msg-user-option{display:grid;grid-template-columns:38px minmax(0,1fr);align-items:center;gap:10px;width:100%;padding:10px 12px;border:none;background:#fff;color:#0f172a;text-align:left;cursor:pointer}
.msg-user-option:hover,.msg-user-option.is-active{background:#eff6ff}
.msg-user-option-avatar{width:32px;height:32px;border-radius:999px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:800;letter-spacing:.02em}
.msg-user-option-copy{display:grid;min-width:0}
.msg-user-option-copy strong{font-size:15px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-user-option-copy small{font-size:13px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-user-empty{padding:12px 14px;font-size:13px;color:#64748b}
.msg-compose-draft{display:grid;gap:12px;margin-top:auto}
.msg-compose-draft.is-hidden{display:none}
.msg-compose-form textarea{resize:vertical;min-height:84px}
.msg-compose-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.msg-compose-actions > span{font-size:12px;color:#64748b;line-height:1.4}
.msg-compose-actions button{display:inline-flex;align-items:center;gap:8px;border:none;border-radius:999px;background:linear-gradient(135deg,#1580ff,#0a66ff);color:#fff;padding:10px 16px;font-size:13px;font-weight:800;cursor:pointer}
.msg-compose-buttons{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-left:auto}
.msg-compose-secondary{background:#eef2f7 !important;color:#334155 !important;box-shadow:none}
.msg-list#msgList{width:100%;display:flex;flex-direction:column;justify-content:flex-start;align-items:stretch;gap:8px;flex:1 1 auto;min-height:0;padding:10px;box-sizing:border-box;overflow-y:auto}
.msg-list#msgList::-webkit-scrollbar{width:8px}
.msg-list#msgList::-webkit-scrollbar-thumb{background:#b4c8ea;border-radius:999px}
#msgList > .msg-thread-card{position:relative;display:flex;align-items:center;width:100%;min-height:72px;padding:12px;border-radius:18px;background:#ffffff;border:1px solid #e5e7eb;box-sizing:border-box;margin:0;transition:background-color .18s ease,border-color .18s ease}
#msgList > .msg-thread-card:hover{background:#f9fafb;border-color:#d7dee8}
#msgList > .msg-thread-card.is-active{background:#edf5ff;border-color:#93c5fd}
.msg-thread{display:flex;align-items:center;flex:1;min-width:0;color:inherit;text-decoration:none}
.msg-thread-avatar-shell{flex:0 0 46px;width:46px;margin-right:10px}
.msg-avatar,.msg-chat-user-avatar,.msg-inline-avatar{display:inline-flex;align-items:center;justify-content:center;overflow:hidden;border-radius:999px;color:#fff;font-weight:800;letter-spacing:.02em;flex-shrink:0}
.msg-avatar{width:46px;height:46px;font-size:13px;font-weight:700;line-height:1;box-shadow:none}
.msg-avatar-stack{position:relative;display:block;width:46px;height:46px}
.msg-avatar-group{position:absolute;border:2px solid #ffffff;box-shadow:none}
.msg-avatar-group-back{width:28px;height:28px;top:0;right:0;z-index:1;font-size:9px}
.msg-avatar-group-front{width:34px;height:34px;left:0;bottom:0;z-index:2;font-size:10px}
.msg-chat-user-avatar-shell{display:flex;align-items:center;justify-content:center;flex:0 0 46px;width:46px}
.msg-chat-user-avatar{width:44px;height:44px;font-size:15px;box-shadow:0 8px 16px rgba(15,23,42,.08)}
.msg-inline-avatar{width:26px;height:26px;font-size:9px;margin-top:auto;box-shadow:0 4px 10px rgba(15,23,42,.10)}
.msg-avatar img,.msg-chat-user-avatar img,.msg-inline-avatar img{width:100%;height:100%;object-fit:cover}
.msg-avatar-theme-0{background:linear-gradient(135deg,#0ea5e9,#2563eb)}
.msg-avatar-theme-1{background:linear-gradient(135deg,#f97316,#ef4444)}
.msg-avatar-theme-2{background:linear-gradient(135deg,#10b981,#059669)}
.msg-avatar-theme-3{background:linear-gradient(135deg,#ec4899,#db2777)}
.msg-avatar-theme-4{background:linear-gradient(135deg,#6366f1,#4338ca)}
.msg-avatar-theme-5{background:linear-gradient(135deg,#14b8a6,#0f766e)}
.msg-thread-body{display:block;flex:1;min-width:0}
.msg-thread-head{display:flex;align-items:flex-start;gap:8px;min-width:0}
.msg-thread-head strong{display:block;flex:1 1 auto;min-width:0;color:#111827;font-size:12px;font-weight:700;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-thread-time-stack{display:grid;justify-items:end;gap:2px;flex:0 0 auto;max-width:128px;text-align:right}
.msg-thread-time{display:block;font-size:9px;font-weight:600;line-height:1.25;color:#6b7280}
.msg-thread-read-state{display:inline-flex;align-items:center;justify-content:center;padding:2px 6px;border-radius:999px;font-size:9px;font-weight:800;line-height:1;color:#475569;background:#eef2f7}
.msg-thread-read-state.is-unread{background:#dbeafe;color:#0a66ff}
.msg-thread-read-state.is-read{background:#eef2f7;color:#64748b}
.msg-thread-meta{display:flex;align-items:center;gap:3px;margin-top:2px;min-width:0;color:#6b7280;font-size:11px}
.msg-thread-unread-label,.msg-thread-preview-prefix,.msg-thread-separator{flex-shrink:0}
.msg-thread-unread-label,.msg-thread-preview-prefix{font-weight:600;color:inherit}
.msg-thread-separator{color:#94a3b8}
.msg-thread-preview{min-width:0;flex:1 1 auto;color:inherit;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-thread-status{position:relative;display:flex;align-items:center;flex:0 0 auto;gap:6px;margin-left:8px}
.msg-thread-more{width:24px;height:24px;border:none;border-radius:9999px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#6b7280;font-size:10px;cursor:pointer}
.msg-thread-dot{display:inline-flex;width:8px;height:8px;border-radius:999px;background:#1877f2}
.msg-thread-menu{position:absolute;top:calc(100% + 8px);right:0;z-index:8;display:grid;gap:4px;min-width:184px;padding:8px;border:1px solid #dbe3ee;border-radius:16px;background:#fff;box-shadow:0 20px 36px rgba(15,23,42,.16)}
.msg-thread-menu form{margin:0}
.msg-thread-menu-item{width:100%;border:none;border-radius:12px;background:transparent;color:#0f172a;display:flex;align-items:center;gap:10px;padding:10px 12px;font-size:12px;font-weight:700;line-height:1.3;cursor:pointer;text-align:left}
.msg-thread-menu-item:hover{background:#eef4ff;color:#002C76}
.msg-thread-menu-item.is-danger{color:#b91c1c}
.msg-thread-menu-item.is-danger:hover{background:#fef2f2;color:#991b1b}
.msg-thread-card.is-unread .msg-thread-head strong{font-weight:700}
.msg-thread-card.is-unread .msg-thread-time,.msg-thread-card.is-unread .msg-thread-meta{color:#111827}
@media (hover:hover) and (pointer:fine){
    .msg-thread-more{opacity:0;pointer-events:none;transform:translateY(2px);transition:opacity .18s ease,transform .18s ease}
    .msg-thread-card:hover .msg-thread-more,
    .msg-thread-card:focus-within .msg-thread-more,
    .msg-thread-more[aria-expanded="true"]{opacity:1;pointer-events:auto;transform:none}
}
.msg-thread-empty{display:grid;place-items:center;text-align:center;padding:44px 20px;gap:8px}
.msg-thread-empty-icon{width:70px;height:70px;border-radius:24px;background:#dfeafe;color:#0a66ff;display:inline-flex;align-items:center;justify-content:center;font-size:26px}
.msg-thread-empty h2{margin:0;color:#0f172a;font-size:22px}
.msg-thread-empty p{margin:0;max-width:260px;color:#64748b;font-size:14px;line-height:1.7}
.msg-pager{padding:14px 18px 18px;border-top:1px solid #dfe7f1;background:rgba(255,255,255,.88)}
.msg-pager .pagination{display:flex;align-items:center;justify-content:center;gap:8px;list-style:none;padding:0;margin:0;flex-wrap:wrap}
.msg-pager .pagination li{display:flex}
.msg-pager .pagination li span,.msg-pager .pagination li a{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:999px;border:1px solid #d8e3f0;background:#fff;color:#1e293b;font-size:13px;font-weight:700;text-decoration:none}
.msg-pager .pagination li.active span{background:#0a66ff;border-color:#0a66ff;color:#fff}
.msg-pager .pagination li.disabled span{background:#f8fafc;color:#94a3b8}
.msg-chat{display:flex;flex-direction:column;min-width:0;min-height:0;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%)}
.msg-chat-main{display:flex;flex:1 1 auto;min-height:0;flex-direction:column}
.msg-chat-conversation{display:flex;flex:1 1 auto;min-height:0;flex-direction:column}
.msg-chat-conversation.is-hidden{display:none}
.msg-chat-compose-panel{display:none;flex:1 1 auto;min-height:0}
.msg-chat-compose-panel.is-open{display:flex;flex-direction:column;align-items:stretch;gap:12px;padding:15px;background:#ffffff}
.msg-chat-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #dee7f2;background:rgba(255,255,255,.95);backdrop-filter:blur(10px)}
.msg-chat-user{display:flex;align-items:center;gap:10px;min-width:0}
.msg-chat-user-copy{display:grid;gap:2px;min-width:0}
.msg-chat-user-copy strong{display:block;min-width:0;color:#111827;font-size:17px;font-weight:800;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-chat-user-copy span{display:-webkit-box;color:#64748b;font-size:11px;line-height:1.35;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.msg-chat-actions{display:flex;align-items:center;gap:8px}
.msg-chat-action{width:32px;height:32px;border:none;border-radius:999px;background:transparent;color:#002C76;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;transition:background-color .18s ease,color .18s ease}
.msg-chat-action-group-info i{font-size:18px}
.msg-chat-action:hover{background:#e7eef9;color:#002C76}
body.msg-group-info-open{overflow:hidden}
.msg-group-info-modal{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:20px}
.msg-group-info-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.36);backdrop-filter:blur(3px)}
.msg-group-info-dialog{position:relative;z-index:1;width:min(100%,460px);max-height:min(80vh,720px);display:grid;grid-template-rows:auto minmax(0,1fr);overflow:hidden;border-radius:24px;border:1px solid #dbe3ee;background:#fff;box-shadow:0 28px 60px rgba(15,23,42,.18)}
.msg-group-info-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 18px 14px;border-bottom:1px solid #e6edf6}
.msg-group-info-copy{display:grid;gap:4px;min-width:0}
.msg-group-info-copy strong{font-size:18px;line-height:1.2;color:#0f172a}
.msg-group-info-copy span{font-size:12px;color:#64748b}
.msg-group-info-close{width:34px;height:34px;border:none;border-radius:999px;background:#eef2f7;color:#475569;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.msg-group-rename-form{display:grid;gap:8px;padding:14px 16px 12px;border-bottom:1px solid #e6edf6;background:#fff}
.msg-group-rename-label{font-size:12px;font-weight:700;color:#334155}
.msg-group-rename-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center}
.msg-group-rename-row input{width:100%;min-width:0;border:1px solid #d6e2f0;border-radius:12px;background:#f8fbff;color:#0f172a;padding:10px 12px;font-size:13px;outline:none}
.msg-group-rename-row input:focus{border-color:#8fb9ff;box-shadow:0 0 0 4px rgba(10,102,255,.10);background:#fff}
.msg-group-rename-row input[readonly]{background:#f1f5f9;color:#64748b;cursor:default}
.msg-group-rename-row input[readonly]:focus{border-color:#d6e2f0;box-shadow:none;background:#f1f5f9}
.msg-group-rename-toggle{width:40px;height:40px;border:none;border-radius:12px;background:#e7eef9;color:#002C76;padding:0;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;cursor:pointer;transition:background-color .18s ease,color .18s ease,transform .18s ease}
.msg-group-rename-toggle:hover{transform:translateY(-1px)}
.msg-group-rename-toggle.is-editing{background:#002C76;color:#fff}
.msg-group-rename-error{margin:0;color:#be123c;font-size:12px;line-height:1.4}
.msg-group-info-list{display:grid;gap:8px;padding:16px;overflow-y:auto;background:#f8fbff}
.msg-group-member-card{display:grid;grid-template-columns:36px minmax(0,1fr);align-items:start;gap:10px;padding:10px 12px;border:1px solid #dbe5f1;border-radius:16px;background:#fff}
.msg-group-member-avatar{width:36px;height:36px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:800;letter-spacing:.02em}
.msg-group-member-copy{display:grid;gap:3px;min-width:0}
.msg-group-member-head{display:flex;align-items:center;gap:8px;min-width:0}
.msg-group-member-head strong{font-size:12px;line-height:1.3;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-group-member-badge{display:inline-flex;align-items:center;justify-content:center;padding:2px 7px;border-radius:999px;background:#deebff;color:#0a66ff;font-size:9px;font-weight:800;flex-shrink:0}
.msg-group-member-copy span{font-size:11px;color:#334155;line-height:1.3}
.msg-group-member-copy small{font-size:10px;color:#64748b;line-height:1.35}
.msg-group-info-empty{padding:16px;border-radius:16px;border:1px dashed #cbd5e1;background:#fff;color:#64748b;font-size:13px;line-height:1.6;text-align:center}
.msg-chat-banner{display:flex;align-items:flex-start;gap:12px;padding:12px 22px;border-bottom:1px solid #e5edf7;background:#f9fbfe}
.msg-chat-banner-icon{width:28px;height:28px;border-radius:10px;background:#e8eef8;color:#5b6473;display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.msg-chat-banner-copy{display:grid;gap:2px;min-width:0}
.msg-chat-banner-copy span{font-size:12px;color:#64748b}
.msg-chat-banner-copy strong{font-size:14px;color:#0f172a;line-height:1.5;font-weight:700}
.msg-chat-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:22px 28px;background:radial-gradient(circle at top left,rgba(191,219,254,.35),transparent 24%),radial-gradient(circle at bottom right,rgba(224,231,255,.45),transparent 26%),linear-gradient(180deg,#ffffff 0%,#fbfcff 100%);scrollbar-width:thin;scrollbar-color:#b8cceb transparent}
.msg-chat-body::-webkit-scrollbar{width:8px}
.msg-chat-body::-webkit-scrollbar-thumb{background:#b8cceb;border-radius:999px}
.msg-row{display:flex;align-items:flex-end;gap:8px;margin-bottom:14px}
.msg-row.left{justify-content:flex-start}
.msg-row.right{justify-content:flex-end}
.msg-row.is-pending{opacity:.78}
.msg-bubble-stack{display:grid;gap:6px;max-width:min(720px,calc(100% - 48px))}
.msg-bubble-stack.outgoing{justify-items:end}
.msg-bubble-stack.incoming{justify-items:start;gap:4px;max-width:min(780px,calc(100% - 34px))}
.msg-bubble{display:inline-block;width:fit-content;max-width:100%;padding:12px 16px;border-radius:20px;box-shadow:0 12px 26px rgba(15,23,42,.06)}
.msg-bubble.incoming{background:#f3f4f6;border:none;border-radius:20px;color:#111827;box-shadow:none;padding:14px 18px}
.msg-bubble.outgoing{background:linear-gradient(135deg,#1580ff,#0a66ff);border-top-right-radius:8px;color:#fff}
.msg-message-gallery{display:grid;gap:6px;max-width:min(280px,100%)}
.msg-message-gallery.is-single{grid-template-columns:1fr}
.msg-message-gallery.is-multi{grid-template-columns:repeat(2,minmax(0,1fr))}
.msg-message-gallery.incoming{justify-items:start}
.msg-message-gallery.outgoing{justify-items:end}
.msg-message-gallery.is-multi .msg-message-image-link{width:100%;max-width:none}
.msg-message-image-link{display:block;width:fit-content;max-width:min(220px,100%);overflow:hidden;line-height:0;text-decoration:none;background:transparent}
.msg-message-image-link.incoming{justify-self:start;border-radius:20px;box-shadow:none}
.msg-message-image-link.outgoing{justify-self:end;border-radius:20px 8px 20px 20px;box-shadow:0 12px 26px rgba(15,23,42,.06)}
.msg-message-image{display:block;width:100%;max-width:100%;height:auto;max-height:280px;object-fit:contain}
.msg-message-gallery.is-multi .msg-message-image{height:120px;max-height:none;object-fit:cover}
.msg-text{font-size:15px;line-height:1.6;white-space:pre-wrap;word-break:break-word}
.msg-meta{font-size:11px;color:#64748b;padding:0 4px}
.msg-meta.out{color:#64748b}
.msg-empty-thread{padding:18px 20px;border-radius:18px;border:1px dashed #c7d7ea;background:rgba(255,255,255,.85);color:#64748b;font-size:14px;line-height:1.7}
.msg-empty-thread-chat{max-width:420px}
.msg-chat-input{display:grid;gap:10px;padding:14px 22px 18px;border-top:1px solid #dee7f2;background:#fff}
.msg-image-preview-list{display:flex;flex-wrap:wrap;gap:12px}
.msg-image-preview-item{position:relative;display:grid;gap:6px;width:96px}
.msg-image-preview-open{display:block;width:96px;height:96px;border:none;border-radius:18px;padding:0;overflow:hidden;background:linear-gradient(180deg,#f8fbff 0%,#eef5ff 100%);box-shadow:inset 0 0 0 1px #d6e4f5;cursor:pointer}
.msg-image-preview-thumb{display:block;width:100%;height:100%;object-fit:cover}
.msg-image-preview-name{font-size:11px;font-weight:700;line-height:1.35;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-image-preview-remove{position:absolute;top:6px;right:6px;width:26px;height:26px;border:none;border-radius:999px;background:rgba(15,23,42,.76);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;backdrop-filter:blur(4px);transition:background-color .18s ease,transform .18s ease}
.msg-image-preview-remove:hover{background:rgba(185,28,28,.92);transform:scale(1.04)}
.msg-send-form{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:12px;align-items:center}
.msg-file-input{display:none}
.msg-tool-button{width:38px;height:38px;border:none;border-radius:999px;background:transparent;color:#0a66ff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:20px;transition:background-color .18s ease,color .18s ease}
.msg-tool-button:hover{background:#e8f1ff}
.msg-tool-button-attach{background:#eef4ff;color:#0a66ff;box-shadow:inset 0 0 0 1px #d5e4ff;flex-shrink:0}
.msg-tool-button-attach:hover{background:#dce9ff}
.msg-composer-field{position:relative}
.msg-send-form textarea{width:100%;border:1px solid #d5e1ef;border-radius:18px;background:#f1f4f8;color:#0f172a;padding:10px 46px 10px 16px;font-size:15px;line-height:1.45;resize:none;overflow-y:hidden;min-height:0;max-height:80px;transition:height .16s ease}
.msg-tool-button-smile{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:32px;height:32px;font-size:18px;color:#1d9bf0}
.msg-submit-button{width:42px;height:42px;border:none;border-radius:999px;background:linear-gradient(135deg,#1580ff,#0a66ff);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 12px 24px rgba(10,102,255,.20);transition:transform .18s ease,box-shadow .18s ease}
.msg-submit-button:hover{transform:translateY(-1px);box-shadow:0 16px 28px rgba(10,102,255,.24)}
.msg-submit-button:disabled{opacity:.6;cursor:not-allowed;transform:none;box-shadow:none}
.msg-upload-notice{padding:10px 12px;border-radius:14px;border:1px solid #dbe7f5;background:#f8fbff;color:#33507a;font-size:12px;font-weight:700;line-height:1.45}
.msg-upload-notice.is-success{background:#eff6ff;border-color:#bfdbfe;color:#0a66ff}
.msg-upload-notice.is-error{background:#fff1f2;border-color:#fecdd3;color:#be123c}
.msg-image-modal{position:fixed;inset:0;z-index:90;display:grid;place-items:center;padding:20px}
.msg-image-modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.72);backdrop-filter:blur(4px)}
.msg-image-modal-dialog{position:relative;z-index:1;display:grid;gap:12px;max-width:min(92vw,960px);max-height:min(90vh,840px);padding:18px;border-radius:28px;background:#ffffff;box-shadow:0 28px 60px rgba(15,23,42,.24)}
.msg-image-modal-close{position:absolute;top:10px;right:10px;width:38px;height:38px;border:none;border-radius:999px;background:rgba(15,23,42,.76);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.msg-image-modal-image{display:block;max-width:min(88vw,900px);max-height:min(78vh,760px);width:auto;height:auto;border-radius:18px}
.msg-image-modal-title{font-size:13px;font-weight:700;line-height:1.45;color:#334155;word-break:break-word}
.msg-chat-placeholder{display:grid;place-items:center;flex:1 1 auto;padding:32px;background:radial-gradient(circle at top left,rgba(191,219,254,.35),transparent 28%),linear-gradient(180deg,#ffffff 0%,#f8fbff 100%)}
.msg-compose-form-chat{width:min(100%,750px);margin:0 auto;min-height:100%;display:flex;flex-direction:column;padding:0;border:none;border-radius:0;background:transparent;box-shadow:none;gap:14px;align-content:start}
.msg-chat-placeholder-card{display:grid;justify-items:center;gap:12px;max-width:380px;padding:34px 30px;border-radius:28px;background:rgba(255,255,255,.94);border:1px solid #dbe4ef;box-shadow:0 20px 50px rgba(15,23,42,.08);text-align:center}
.msg-chat-placeholder-card img{width:84px;height:84px;object-fit:contain}
.msg-chat-placeholder-card strong{color:#0f172a;font-size:24px}
.msg-chat-placeholder-card p{margin:0;color:#64748b;font-size:15px;line-height:1.7}
body.msg-image-modal-open{overflow:hidden}
#msgList > .msg-thread-card.is-hidden{display:none}
@media (max-width:1280px){
    .msg-shell{grid-template-columns:320px minmax(0,1fr)}
    .msg-sidebar-title h1{font-size:30px}
}
@media (max-width:1024px){
    .msg-page{height:auto;min-height:0}
    .msg-shell{grid-template-columns:1fr;height:auto;min-height:0}
    .msg-sidebar{border-right:none;border-bottom:1px solid #dbe3ee}
    .msg-chat{min-height:620px}
}
@media (max-width:720px){
    .msg-shell{border-radius:22px}
    .msg-sidebar-head,.msg-search-bar,.msg-filter-row,.msg-chat-head,.msg-chat-banner,.msg-chat-input{padding-left:16px;padding-right:16px}
    .msg-chat-body{padding:18px 16px}
    .msg-send-form{grid-template-columns:auto minmax(0,1fr) auto}
    .msg-chat-head{flex-direction:column;align-items:flex-start}
    .msg-chat-actions{width:100%;justify-content:flex-end}
    .msg-group-info-modal{padding:14px}
    .msg-group-info-dialog{width:min(100%,420px);border-radius:20px}
    .msg-group-info-head{padding:16px 16px 12px}
    .msg-group-rename-form{padding:12px}
    .msg-group-rename-row{grid-template-columns:minmax(0,1fr) auto}
    .msg-group-info-list{padding:12px}
    .msg-list#msgList{gap:8px;padding:8px}
    #msgList > .msg-thread-card{min-height:68px;padding:10px;border-radius:16px}
    .msg-thread-avatar-shell{flex-basis:42px;width:42px;margin-right:8px}
    .msg-avatar{width:42px;height:42px;font-size:12px}
    .msg-avatar-stack{width:42px;height:42px}
    .msg-avatar-group-back{width:26px;height:26px;font-size:8px}
    .msg-avatar-group-front{width:30px;height:30px;font-size:9px}
    .msg-chat-user-avatar-shell{flex-basis:42px;width:42px}
    .msg-thread-head strong{font-size:11px}
    .msg-thread-time-stack{max-width:104px}
    .msg-thread-time{font-size:8px}
    .msg-thread-read-state{font-size:8px;padding:2px 5px}
    .msg-thread-time{font-size:9px}
    .msg-thread-preview{font-size:10px}
    .msg-thread-meta{font-size:10px}
    .msg-thread-status{margin-left:8px}
    .msg-thread-more{width:22px;height:22px;font-size:9px}
    .msg-thread-menu{min-width:168px;right:-2px}
    .msg-chat-placeholder{padding:20px 16px}
    .msg-chat-compose-panel.is-open{padding:15px}
    .msg-compose-actions{align-items:stretch;flex-direction:column}
    .msg-compose-buttons{width:100%;justify-content:flex-end}
    .msg-compose-buttons button{flex:0 0 auto}
}
</style>
@endsection

@section('scripts')
<script>
(() => {
    const search = document.getElementById('msgSearch');
    const items = Array.from(document.querySelectorAll('.msg-thread-card[data-search]'));
    const filterAllButton = document.getElementById('msgFilterAll');
    const filterUnreadButton = document.getElementById('msgFilterUnread');
    const filterGroupsButton = document.getElementById('msgFilterGroups');
    const filterButtons = [filterAllButton, filterUnreadButton, filterGroupsButton].filter((button) => button instanceof HTMLButtonElement);
    let activeThreadFilter = 'all';

    const syncThreadFilterButtons = () => {
        filterButtons.forEach((button) => {
            const isActive = button.dataset.threadFilter === activeThreadFilter;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const applyThreadFilters = () => {
        const query = String(search?.value || '').trim().toLowerCase();

        items.forEach((item) => {
            const haystack = String(item.dataset.search || '');
            const matchedSearch = query === '' || haystack.includes(query);
            const matchedType = (
                activeThreadFilter === 'all'
                || (activeThreadFilter === 'unread' && item.dataset.threadUnread === '1')
                || (activeThreadFilter === 'groups' && item.dataset.threadGroup === '1')
            );

            item.classList.toggle('is-hidden', !(matchedSearch && matchedType));
        });
    };

    if (search && items.length) {
        search.addEventListener('input', applyThreadFilters);
    }

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeThreadFilter = ['unread', 'groups'].includes(String(button.dataset.threadFilter || ''))
                ? String(button.dataset.threadFilter)
                : 'all';
            syncThreadFilterButtons();
            applyThreadFilters();
        });
    });

    if (items.length) {
        syncThreadFilterButtons();
        applyThreadFilters();
    }

    const threadMenuToggles = Array.from(document.querySelectorAll('[data-thread-menu-toggle]'));
    const threadMenus = Array.from(document.querySelectorAll('[data-thread-menu]'));

    const closeThreadMenus = (activeMenu = null) => {
        threadMenus.forEach((menu) => {
            const shouldOpen = activeMenu instanceof HTMLElement && menu === activeMenu;
            menu.hidden = !shouldOpen;

            const toggle = menu.parentElement?.querySelector('[data-thread-menu-toggle]');
            if (toggle instanceof HTMLElement) {
                toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }
        });
    };

    threadMenuToggles.forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const menu = toggle.parentElement?.querySelector('[data-thread-menu]');
            if (!(menu instanceof HTMLElement)) {
                return;
            }

            closeThreadMenus(menu.hidden ? menu : null);
        });
    });

    if (threadMenus.length) {
        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                closeThreadMenus();
                return;
            }

            const clickedInsideMenu = threadMenus.some((menu) => menu.contains(target));
            const clickedToggle = threadMenuToggles.some((toggle) => toggle.contains(target));
            if (clickedInsideMenu || clickedToggle) {
                return;
            }

            closeThreadMenus();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeThreadMenus();
            }
        });
    }

    const groupInfoButton = document.getElementById('msgGroupInfoButton');
    const groupInfoModal = document.getElementById('msgGroupInfoModal');
    const groupInfoCloseButtons = Array.from(document.querySelectorAll('[data-group-info-close]'));
    const groupRenameForm = document.getElementById('msgGroupRenameForm');
    const groupNameInput = document.getElementById('msgGroupNameInput');
    const groupRenameToggle = document.getElementById('msgGroupRenameToggle');
    const groupRenameStartsEditing = groupRenameForm?.dataset.initialEditing === '1';

    const setGroupRenameEditing = (editing, options = {}) => {
        const shouldFocusInput = options.focusInput === true;

        if (!(groupNameInput instanceof HTMLInputElement) || !(groupRenameToggle instanceof HTMLElement)) {
            return;
        }

        groupNameInput.readOnly = !editing;
        groupNameInput.setAttribute('aria-readonly', editing ? 'false' : 'true');
        groupRenameToggle.classList.toggle('is-editing', editing);
        groupRenameToggle.setAttribute('title', editing ? 'Save group name' : 'Edit group name');
        groupRenameToggle.setAttribute('aria-label', editing ? 'Save group name' : 'Edit group name');
        groupRenameToggle.setAttribute('aria-pressed', editing ? 'true' : 'false');

        const groupRenameIcon = groupRenameToggle.querySelector('i');

        if (groupRenameIcon instanceof HTMLElement) {
            groupRenameIcon.classList.toggle('fa-save', editing);
            groupRenameIcon.classList.toggle('fa-pen', !editing);
        }

        if (shouldFocusInput && editing) {
            groupNameInput.focus();
            groupNameInput.select();
        }
    };

    const setGroupInfoOpen = (open) => {
        if (!groupInfoModal) {
            return;
        }

        groupInfoModal.hidden = !open;
        document.body.classList.toggle('msg-group-info-open', open);
        groupInfoButton?.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            setGroupRenameEditing(groupRenameStartsEditing, { focusInput: groupRenameStartsEditing });

            if (!groupRenameStartsEditing && groupRenameToggle instanceof HTMLElement) {
                groupRenameToggle.focus();
            }
            return;
        }

        if (groupInfoButton instanceof HTMLElement) {
            groupInfoButton.focus();
        }
    };

    if (groupInfoButton && groupInfoModal) {
        setGroupRenameEditing(groupRenameStartsEditing, { focusInput: false });

        groupInfoButton.addEventListener('click', () => {
            setGroupInfoOpen(true);
        });

        groupInfoCloseButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setGroupInfoOpen(false);
            });
        });

        if (groupRenameToggle instanceof HTMLElement && groupRenameForm instanceof HTMLFormElement && groupNameInput instanceof HTMLInputElement) {
            groupRenameToggle.addEventListener('click', () => {
                if (groupNameInput.readOnly) {
                    setGroupRenameEditing(true, { focusInput: true });
                    return;
                }

                groupRenameForm.requestSubmit();
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !groupInfoModal.hidden) {
                setGroupInfoOpen(false);
            }
        });

        if (groupInfoModal.dataset.autoOpen === '1') {
            setGroupInfoOpen(true);
        }
    }

    const composeToggle = document.getElementById('toggleCompose');
    const composePanel = document.getElementById('msgChatComposePanel');
    const chatMain = document.getElementById('msgChatMain');
    const chatConversation = document.getElementById('msgChatConversation');
    const composeForm = document.getElementById('msgComposeForm');
    const composeRecipientSearch = document.getElementById('composeUserSearch');
    const composeRecipientValues = document.getElementById('composeRecipientValues');
    const composeRecipientChips = document.getElementById('composeRecipientChips');
    const composeDraftSection = document.getElementById('composeDraftSection');
    const composeSuggestions = document.getElementById('composeUserSuggestions');
    const composeEmptyState = document.getElementById('composeUserEmpty');
    const composeUserOptions = Array.from(document.querySelectorAll('.msg-user-option'));
    const oldRecipientInputs = Array.from(document.querySelectorAll('input[data-old-recipient="1"]'));
    const composeCancel = document.getElementById('msgComposeCancel');
    const composeMessage = document.getElementById('composeMessage');
    let activeComposeOption = -1;
    const selectedRecipients = new Map();

    const escapePattern = (value) => String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const wildcardMatcher = (value) => {
        const query = String(value || '').trim();
        if (!query) {
            return null;
        }

        const source = '^' + query.split('*').map((token) => escapePattern(token)).join('.*') + '.*$';
        try {
            return new RegExp(source, 'i');
        } catch (error) {
            return null;
        }
    };

    const closeComposeSuggestions = () => {
        composeSuggestions?.classList.remove('is-open');
        composeRecipientSearch?.setAttribute('aria-expanded', 'false');
    };

    const openComposeSuggestions = () => {
        if (!composeSuggestions) {
            return;
        }
        composeSuggestions.classList.add('is-open');
        composeRecipientSearch?.setAttribute('aria-expanded', 'true');
    };

    const syncRecipientInputs = () => {
        if (!composeRecipientValues) {
            return;
        }

        composeRecipientValues.innerHTML = '';
        selectedRecipients.forEach((label, id) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'recipient_ids[]';
            hidden.value = String(id);
            composeRecipientValues.appendChild(hidden);
        });
    };

    const syncComposeDraftVisibility = () => {
        if (!composeDraftSection || !composeMessage) {
            return;
        }

        const hasRecipients = selectedRecipients.size > 0;
        composeDraftSection.classList.toggle('is-hidden', !hasRecipients);
        composeMessage.disabled = !hasRecipients;
        composeMessage.required = hasRecipients;
    };

    const renderRecipientChips = () => {
        if (!composeRecipientChips) {
            return;
        }

        composeRecipientChips.innerHTML = '';
        selectedRecipients.forEach((label, id) => {
            const chip = document.createElement('span');
            chip.className = 'msg-recipient-chip';

            const text = document.createElement('span');
            text.textContent = label;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'Remove recipient');
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selectedRecipients.delete(id);
                renderRecipientChips();
                syncRecipientInputs();
                syncComposeDraftVisibility();
                refreshComposeSuggestions(composeRecipientSearch?.value || '');
            });

            chip.appendChild(text);
            chip.appendChild(remove);
            composeRecipientChips.appendChild(chip);
        });
    };

    const addRecipient = (id, label) => {
        const numericId = Number(id || 0);
        if (numericId <= 0) {
            return;
        }

        selectedRecipients.set(numericId, String(label || 'Unknown User'));
        renderRecipientChips();
        syncRecipientInputs();
        syncComposeDraftVisibility();

        if (composeRecipientSearch) {
            composeRecipientSearch.value = '';
            composeRecipientSearch.setCustomValidity('');
        }

        refreshComposeSuggestions('');
        openComposeSuggestions();
    };

    const refreshComposeSuggestions = (query) => {
        if (!composeUserOptions.length) {
            return;
        }

        const pattern = wildcardMatcher(query);
        const cleanQuery = String(query || '').trim().toLowerCase();
        let visibleCount = 0;
        activeComposeOption = -1;

        composeUserOptions.forEach((option) => {
            const haystack = String(option.dataset.search || '').toLowerCase();
            const optionId = Number(option.dataset.userId || 0);
            const alreadySelected = selectedRecipients.has(optionId);
            const matched = !alreadySelected && (!cleanQuery || (pattern ? pattern.test(haystack) : haystack.includes(cleanQuery)));
            option.hidden = !matched;
            option.classList.remove('is-active');
            if (matched) {
                visibleCount += 1;
            }
        });

        if (composeEmptyState) {
            composeEmptyState.hidden = visibleCount > 0;
        }
    };

    const visibleComposeOptions = () => composeUserOptions.filter((option) => !option.hidden);

    const updateActiveComposeOption = (direction) => {
        const available = visibleComposeOptions();
        if (!available.length) {
            activeComposeOption = -1;
            return;
        }

        if (activeComposeOption < 0) {
            activeComposeOption = direction > 0 ? 0 : available.length - 1;
        } else {
            activeComposeOption = (activeComposeOption + direction + available.length) % available.length;
        }

        available.forEach((option, index) => {
            option.classList.toggle('is-active', index === activeComposeOption);
        });
        available[activeComposeOption].scrollIntoView({ block: 'nearest' });
    };

    if (composeRecipientSearch && composeSuggestions) {
        oldRecipientInputs.forEach((input) => {
            const id = Number(input.value || 0);
            if (id <= 0) {
                input.remove();
                return;
            }

            const option = composeUserOptions.find((entry) => Number(entry.dataset.userId || 0) === id);
            if (option) {
                selectedRecipients.set(id, String(option.dataset.userLabel || 'Unknown User'));
            }
            input.remove();
        });
        renderRecipientChips();
        syncRecipientInputs();
        syncComposeDraftVisibility();

        composeRecipientSearch.addEventListener('focus', () => {
            refreshComposeSuggestions(composeRecipientSearch.value);
            openComposeSuggestions();
        });

        composeRecipientSearch.addEventListener('input', () => {
            composeRecipientSearch.setCustomValidity('');
            refreshComposeSuggestions(composeRecipientSearch.value);
            openComposeSuggestions();
        });

        composeRecipientSearch.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openComposeSuggestions();
                updateActiveComposeOption(1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                openComposeSuggestions();
                updateActiveComposeOption(-1);
                return;
            }

            if (event.key === 'Escape') {
                closeComposeSuggestions();
                return;
            }

            if (event.key === 'Enter') {
                const available = visibleComposeOptions();
                if (!available.length) {
                    return;
                }
                event.preventDefault();
                const target = activeComposeOption >= 0 ? available[activeComposeOption] : available[0];
                target.click();
                return;
            }

            if (event.key === 'Backspace' && String(composeRecipientSearch.value || '') === '' && selectedRecipients.size > 0) {
                const keys = Array.from(selectedRecipients.keys());
                const lastId = keys[keys.length - 1];
                selectedRecipients.delete(lastId);
                renderRecipientChips();
                syncRecipientInputs();
                syncComposeDraftVisibility();
                refreshComposeSuggestions('');
            }
        });

        composeUserOptions.forEach((option) => {
            option.addEventListener('click', () => {
                addRecipient(String(option.dataset.userId || ''), String(option.dataset.userLabel || ''));
            });
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            if (target.closest('#composeRecipientPicker')) {
                return;
            }
            closeComposeSuggestions();
        });
    }
    const setComposeOpen = (open) => {
        if (!composePanel || !chatMain || !chatConversation) {
            return;
        }

        composePanel.classList.toggle('is-open', open);
        chatConversation.classList.toggle('is-hidden', open);

        if (composeToggle) {
            composeToggle.classList.toggle('is-active', open);
            composeToggle.setAttribute('aria-pressed', open ? 'true' : 'false');
            composeToggle.setAttribute('title', open ? 'Close new message' : 'New message');
        }

        if (!open) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (selectedRecipients.size === 0) {
                composeRecipientSearch?.focus();
                return;
            }

            composeMessage?.focus();
        });
    };

    if (composeToggle && composePanel && chatMain && chatConversation) {
        composeToggle.addEventListener('click', () => {
            setComposeOpen(!composePanel.classList.contains('is-open'));
        });
    }

    if (composeCancel) {
        composeCancel.addEventListener('click', () => {
            setComposeOpen(false);
        });
    }

    if (composePanel && chatMain && chatConversation) {
        setComposeOpen(composePanel.classList.contains('is-open'));
    }

    if (composeForm && composeRecipientSearch) {
        composeForm.addEventListener('submit', (event) => {
            if (selectedRecipients.size > 0) {
                composeRecipientSearch.setCustomValidity('');
                return;
            }

            event.preventDefault();
            composeRecipientSearch.setCustomValidity('Please select at least one recipient from the suggestions.');
            composeRecipientSearch.reportValidity();
            composeRecipientSearch.focus();
            openComposeSuggestions();
        });
    }

    const chatRoot = document.getElementById('msgChatRoot');
    const pollUrl = @json(route('messages.poll'));
    const conversationUrl = @json(route('messages.conversation'));
    const contactInitials = @json($selectedInitials);
    const contactAvatarClass = @json($selectedAvatarClass);
    const contactUsesLogo = @json($selectedUsesLogo);
    const contactLogoUrl = @json($selectedLogoUrl);
    if (!chatRoot || !pollUrl) {
        return;
    }

    let latestThreadId = Number(chatRoot.dataset.latestThreadId || 0);
    let latestGlobalId = Number(chatRoot.dataset.latestGlobalId || 0);
    const authUserId = Number(chatRoot.dataset.authUser || 0);
    const currentThread = Number(chatRoot.dataset.thread || 0);
    const unreadIndicators = Array.from(document.querySelectorAll('[data-unread-total]'));
    const chatBody = document.getElementById('msgChatBody');
    const sendForm = document.getElementById('msgSendForm');
    const sendMessageInput = sendForm?.querySelector('textarea[name="message"]');
    const sendImageInput = document.getElementById('msgImageInput');
    const sendImageButton = sendForm?.querySelector('.msg-tool-button-attach');
    const sendUploadNotice = document.getElementById('msgUploadNotice');
    const sendImagePreviewList = document.getElementById('msgImagePreviewList');
    const imageModal = document.getElementById('msgImageModal');
    const imageModalImage = document.getElementById('msgImageModalImage');
    const imageModalTitle = document.getElementById('msgImageModalTitle');
    const imageModalClose = document.getElementById('msgImageModalClose');
    const imageModalCloseTargets = Array.from(document.querySelectorAll('[data-image-modal-close]'));
    const POLL_MS = 1000;
    const CHAT_INPUT_MAX_HEIGHT = 80;
    const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    const MAX_PENDING_IMAGES = 10;
    let optimisticMessageId = 0;
    let uploadNoticeTimer = 0;
    let pendingImageId = 0;
    let pendingImages = [];

    const getSendMessageBaseHeight = () => {
        if (!(sendMessageInput instanceof HTMLTextAreaElement)) {
            return 0;
        }

        const styles = window.getComputedStyle(sendMessageInput);
        const lineHeight = parseFloat(styles.lineHeight) || parseFloat(styles.fontSize) || 15;
        const paddingTop = parseFloat(styles.paddingTop) || 0;
        const paddingBottom = parseFloat(styles.paddingBottom) || 0;
        const borderTop = parseFloat(styles.borderTopWidth) || 0;
        const borderBottom = parseFloat(styles.borderBottomWidth) || 0;

        return Math.ceil(lineHeight + paddingTop + paddingBottom + borderTop + borderBottom);
    };

    const syncSendMessageHeight = () => {
        if (!(sendMessageInput instanceof HTMLTextAreaElement)) {
            return;
        }

        sendMessageInput.style.height = 'auto';
        const baseHeight = getSendMessageBaseHeight();
        const nextHeight = Math.max(baseHeight, Math.min(CHAT_INPUT_MAX_HEIGHT, sendMessageInput.scrollHeight));
        sendMessageInput.style.height = `${nextHeight}px`;
        sendMessageInput.style.overflowY = sendMessageInput.scrollHeight > CHAT_INPUT_MAX_HEIGHT ? 'auto' : 'hidden';
    };

    const updateUnread = (value) => {
        const total = Math.max(0, Number(value || 0));

        unreadIndicators.forEach((indicator) => {
            indicator.textContent = total > 99 ? '99+' : total.toLocaleString();
            indicator.hidden = total <= 0;
        });
    };

    const clearUploadNoticeTimer = () => {
        if (uploadNoticeTimer > 0) {
            window.clearTimeout(uploadNoticeTimer);
            uploadNoticeTimer = 0;
        }
    };

    const setUploadNotice = (message = '', tone = 'info', autoHideMs = 0) => {
        if (!(sendUploadNotice instanceof HTMLElement)) {
            return;
        }

        clearUploadNoticeTimer();
        sendUploadNotice.className = 'msg-upload-notice';

        if (!message) {
            sendUploadNotice.hidden = true;
            sendUploadNotice.textContent = '';
            return;
        }

        if (tone === 'success') {
            sendUploadNotice.classList.add('is-success');
        } else if (tone === 'error') {
            sendUploadNotice.classList.add('is-error');
        }

        sendUploadNotice.textContent = message;
        sendUploadNotice.hidden = false;

        if (autoHideMs > 0) {
            uploadNoticeTimer = window.setTimeout(() => {
                setUploadNotice('');
            }, autoHideMs);
        }
    };

    const resetSelectedImageInput = () => {
        if (sendImageInput instanceof HTMLInputElement) {
            sendImageInput.value = '';
        }
    };

    const pendingImageFileName = (file) => {
        if (!(file instanceof File)) {
            return 'pasted-image.png';
        }

        const rawName = String(file.name || '').trim();
        if (rawName !== '') {
            return rawName;
        }

        const type = String(file.type || '').toLowerCase();
        const extension = ({
            'image/jpeg': 'jpg',
            'image/png': 'png',
            'image/gif': 'gif',
            'image/webp': 'webp',
            'image/bmp': 'bmp',
            'image/heic': 'heic',
            'image/heif': 'heif',
            'image/avif': 'avif',
        })[type] || 'png';

        return 'pasted-image.' + extension;
    };

    const revokePendingImageEntry = (entry) => {
        if (entry && typeof entry.previewUrl === 'string' && entry.previewUrl !== '') {
            URL.revokeObjectURL(entry.previewUrl);
        }
    };

    const revokeAllPendingImages = () => {
        pendingImages.forEach((entry) => {
            revokePendingImageEntry(entry);
        });
    };

    const renderPendingImages = () => {
        if (!(sendImagePreviewList instanceof HTMLElement)) {
            return;
        }

        if (pendingImages.length < 1) {
            sendImagePreviewList.hidden = true;
            sendImagePreviewList.innerHTML = '';
            return;
        }

        sendImagePreviewList.hidden = false;
        sendImagePreviewList.innerHTML = pendingImages.map((entry) => (
            '<div class="msg-image-preview-item" data-pending-image-id="' + entry.id + '">'
            + '<button type="button" class="msg-image-preview-open" data-open-pending-image="' + entry.id + '" aria-label="Open ' + escapeHtml(entry.name) + '">'
            + '<img src="' + escapeHtml(entry.previewUrl) + '" alt="' + escapeHtml(entry.name) + '" class="msg-image-preview-thumb">'
            + '</button>'
            + '<button type="button" class="msg-image-preview-remove" data-remove-pending-image="' + entry.id + '" aria-label="Discard ' + escapeHtml(entry.name) + '">'
            + '<i class="fas fa-times"></i>'
            + '</button>'
            + '<div class="msg-image-preview-name" title="' + escapeHtml(entry.name) + '">' + escapeHtml(entry.name) + '</div>'
            + '</div>'
        )).join('');
    };

    const clearPendingImages = (options = {}) => {
        const shouldClearInput = options.clearInput !== false;
        const shouldClearNotice = options.clearNotice === true;

        revokeAllPendingImages();
        pendingImages = [];
        renderPendingImages();

        if (shouldClearInput) {
            resetSelectedImageInput();
        }

        if (shouldClearNotice) {
            setUploadNotice('');
        }
    };

    const validateSelectedImage = (file) => {
        if (!(file instanceof File)) {
            return '';
        }

        const fileType = String(file.type || '').toLowerCase();
        const fileName = String(file.name || '').toLowerCase();
        const isImageFile = fileType.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp|svg|heic|heif|avif)$/i.test(fileName);

        if (!isImageFile) {
            return 'Only image files can be uploaded.';
        }

        if (Number(file.size || 0) > MAX_IMAGE_BYTES) {
            return 'Images must be 5 MB or smaller.';
        }

        return '';
    };

    const removePendingImage = (imageId) => {
        const numericId = Number(imageId || 0);
        if (numericId <= 0) {
            return;
        }

        const pendingIndex = pendingImages.findIndex((entry) => entry.id === numericId);
        if (pendingIndex < 0) {
            return;
        }

        const [removedEntry] = pendingImages.splice(pendingIndex, 1);
        revokePendingImageEntry(removedEntry);
        renderPendingImages();
        if (pendingImages.length < 1) {
            setUploadNotice('');
        }
    };

    const stagePendingImages = (files) => {
        const incomingFiles = Array.isArray(files) ? files : Array.from(files || []);
        if (incomingFiles.length < 1) {
            return false;
        }

        let lastError = '';
        let stagedCount = 0;

        for (const file of incomingFiles) {
            if (!(file instanceof File)) {
                continue;
            }

            const validationError = validateSelectedImage(file);
            if (validationError !== '') {
                lastError = validationError;
                continue;
            }

            if (pendingImages.length >= MAX_PENDING_IMAGES) {
                lastError = 'You can attach up to ' + MAX_PENDING_IMAGES + ' images at a time.';
                break;
            }

            pendingImages.push({
                id: ++pendingImageId,
                file,
                name: pendingImageFileName(file),
                previewUrl: URL.createObjectURL(file),
            });
            stagedCount += 1;
        }

        renderPendingImages();

        if (lastError !== '') {
            setUploadNotice(lastError, 'error');
        } else {
            setUploadNotice('');
        }

        return stagedCount > 0;
    };

    const hasUserInputFocus = () => {
        const active = document.activeElement;
        if (!active) {
            return false;
        }
        const tag = String(active.tagName || '').toLowerCase();
        return tag === 'textarea' || tag === 'input' || active.isContentEditable;
    };

    const escapeHtml = (value) => String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const withSocketHeaders = (headers = {}) => {
        const nextHeaders = { ...headers };
        const socketId = typeof window.Echo?.socketId === 'function' ? window.Echo.socketId() : '';

        if (socketId) {
            nextHeaders['X-Socket-ID'] = socketId;
        }

        return nextHeaders;
    };

    const setComposerBusy = (busy) => {
        const isBusy = busy === true;

        if (sendMessageInput instanceof HTMLTextAreaElement) {
            sendMessageInput.disabled = isBusy;
        }

        if (sendImageButton instanceof HTMLButtonElement) {
            sendImageButton.disabled = isBusy;
        }

        if (sendImageInput instanceof HTMLInputElement) {
            sendImageInput.disabled = isBusy;
        }
    };

    const isNearBottom = () => {
        if (!chatBody) {
            return false;
        }
        return (chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight) < 80;
    };

    const scrollToBottom = () => {
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    };

    const incomingAvatarMarkup = () => {
        if (contactUsesLogo) {
            return '<span class="msg-inline-avatar ' + contactAvatarClass + '"><img src="' + escapeHtml(contactLogoUrl) + '" alt=""></span>';
        }

        return '<span class="msg-inline-avatar ' + contactAvatarClass + '"><span>' + escapeHtml(contactInitials || 'U') + '</span></span>';
    };

    const openImageModal = (imageUrl, imageTitle = 'Image preview') => {
        if (
            !(imageModal instanceof HTMLElement)
            || !(imageModalImage instanceof HTMLImageElement)
            || !(imageModalTitle instanceof HTMLElement)
        ) {
            return;
        }

        imageModalImage.src = String(imageUrl || '');
        imageModalImage.alt = String(imageTitle || 'Image preview');
        imageModalTitle.textContent = String(imageTitle || 'Image preview');
        imageModal.hidden = false;
        document.body.classList.add('msg-image-modal-open');
    };

    const closeImageModal = () => {
        if (
            !(imageModal instanceof HTMLElement)
            || !(imageModalImage instanceof HTMLImageElement)
            || !(imageModalTitle instanceof HTMLElement)
        ) {
            return;
        }

        imageModal.hidden = true;
        imageModalImage.removeAttribute('src');
        imageModalImage.alt = '';
        imageModalTitle.textContent = '';
        document.body.classList.remove('msg-image-modal-open');
    };

    const renderImageGallery = (images, mine) => {
        const normalizedImages = Array.isArray(images) ? images : [];
        if (!normalizedImages.length) {
            return '';
        }

        const galleryClass = normalizedImages.length > 1 ? 'is-multi' : 'is-single';

        return [
            '<div class="msg-message-gallery ' + (mine ? 'outgoing' : 'incoming') + ' ' + galleryClass + '">',
            normalizedImages.map((image) => {
                const imageUrl = String(image?.url || '');
                const imageName = String(image?.name || 'Shared image');

                return '<a href="' + escapeHtml(imageUrl) + '" class="msg-message-image-link ' + (mine ? 'outgoing' : 'incoming') + '" target="_blank" rel="noopener noreferrer"><img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(imageName) + '" class="msg-message-image"></a>';
            }).join(''),
            '</div>',
        ].join('');
    };

    const messageRowMarkup = (entry) => {
        const mine = Boolean(entry?.is_mine);
        const pending = Boolean(entry?.is_pending);
        const images = Array.isArray(entry?.images) ? entry.images : [];
        const messageText = String(entry?.message || '');
        const rowAttributes = [
            'class="msg-row ' + (mine ? 'right' : 'left') + (pending ? ' is-pending' : '') + '"',
            pending && entry?.id ? 'data-optimistic-id="' + escapeHtml(entry.id) + '"' : '',
        ].filter(Boolean).join(' ');

        return [
            '<div ' + rowAttributes + '>',
            mine ? '' : incomingAvatarMarkup(),
            '<div class="msg-bubble-stack ' + (mine ? 'outgoing' : 'incoming') + '">',
            renderImageGallery(images, mine),
            messageText !== ''
                ? '<div class="msg-bubble ' + (mine ? 'outgoing' : 'incoming') + '"><div class="msg-text">' + escapeHtml(messageText).replaceAll('\n', '<br>') + '</div></div>'
                : '',
            '<div class="msg-meta ' + (mine ? 'out' : '') + '"><span>' + escapeHtml(entry?.time || '') + '</span></div>',
            '</div>',
            '</div>',
        ].join('');
    };

    const appendOptimisticMessage = (entry) => {
        if (!(chatBody instanceof HTMLElement)) {
            return '';
        }

        const optimisticId = 'optimistic-' + (++optimisticMessageId);
        const emptyThread = chatBody.querySelector('.msg-empty-thread');
        if (emptyThread) {
            emptyThread.remove();
        }

        chatBody.insertAdjacentHTML('beforeend', messageRowMarkup({
            ...entry,
            id: optimisticId,
            is_mine: true,
            is_pending: true,
        }));
        scrollToBottom();

        return optimisticId;
    };

    const removeOptimisticMessage = (id) => {
        if (!(chatBody instanceof HTMLElement) || !id) {
            return;
        }

        const selector = '[data-optimistic-id="' + CSS.escape(String(id)) + '"]';
        const row = chatBody.querySelector(selector);
        row?.remove();

        if (!chatBody.children.length) {
            chatBody.innerHTML = '<div class="msg-empty-thread msg-empty-thread-chat">No messages yet. Start this conversation below.</div>';
        }
    };

    const renderMessages = (messages) => {
        if (!chatBody) {
            return;
        }

        const keepAtBottom = isNearBottom();
        const normalized = Array.isArray(messages) ? messages : [];
        if (!normalized.length) {
            chatBody.innerHTML = '<div class="msg-empty-thread msg-empty-thread-chat">No messages yet. Start this conversation below.</div>';
            return;
        }

        chatBody.innerHTML = normalized.map((entry) => messageRowMarkup(entry)).join('');

        if (keepAtBottom) {
            scrollToBottom();
        }
    };

    const fetchConversation = async () => {
        if (currentThread <= 0 || !conversationUrl) {
            return false;
        }

        const query = new URLSearchParams({ thread: String(currentThread) });
        const response = await fetch(conversationUrl + '?' + query.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return false;
        }

        const payload = await response.json();
        renderMessages(payload.messages || []);

        const latestId = Number(payload.latest_thread_id || 0);
        if (latestId > latestThreadId) {
            latestThreadId = latestId;
        }
        updateUnread(payload.unread_count || 0);
        return true;
    };

    const poll = async (options = {}) => {
        const skipVisibilityCheck = options.skipVisibilityCheck === true;
        const forceConversationRefresh = options.forceConversationRefresh === true;

        if (!skipVisibilityCheck && document.visibilityState !== 'visible') {
            return;
        }

        try {
            const query = new URLSearchParams();
            if (currentThread > 0) {
                query.set('thread', String(currentThread));
            }

            const response = await fetch(pollUrl + (query.toString() ? ('?' + query.toString()) : ''), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const serverUnread = Number(payload.unread_count || 0);
            const serverLatestThreadId = Number(payload.latest_thread_id || 0);
            const serverLatestGlobalId = Number(payload.latest_global_id || 0);

            updateUnread(serverUnread);

            const shouldRefreshConversation = currentThread > 0
                && (serverLatestThreadId > latestThreadId || forceConversationRefresh);

            if (shouldRefreshConversation && (!hasUserInputFocus() || forceConversationRefresh)) {
                await fetchConversation();
            }

            if (serverLatestThreadId > latestThreadId) {
                latestThreadId = serverLatestThreadId;
            }
            if (serverLatestGlobalId > latestGlobalId) {
                latestGlobalId = serverLatestGlobalId;
            }
        } catch (error) {
            // keep polling silent on transient failures
        }
    };

    const handleRealtimeThreadUpdate = async (event) => {
        const incomingThreadId = Number(event?.thread_id || 0);
        const isCurrentThread = currentThread > 0 && incomingThreadId === currentThread;

        if (isCurrentThread) {
            await fetchConversation();
            return;
        }

        await poll({
            skipVisibilityCheck: true,
        });
    };

    if (authUserId > 0 && window.Echo && typeof window.Echo.private === 'function') {
        window.Echo.private(`users.${authUserId}.messages`)
            .listen('.message.thread.updated', (event) => {
                handleRealtimeThreadUpdate(event).catch(() => {
                    // ignore socket refresh failures and let polling recover
                });
            });
    }

    if (sendMessageInput instanceof HTMLTextAreaElement) {
        syncSendMessageHeight();
        sendMessageInput.addEventListener('input', syncSendMessageHeight);
        sendMessageInput.addEventListener('keydown', (event) => {
            if (
                event.key !== 'Enter'
                || event.shiftKey
                || event.altKey
                || event.ctrlKey
                || event.metaKey
                || event.isComposing
            ) {
                return;
            }

            const hasText = String(sendMessageInput.value || '').trim() !== '';
            const hasQueuedImages = pendingImages.length > 0;
            if (!hasText && !hasQueuedImages) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            sendForm?.requestSubmit();
        });
        sendMessageInput.addEventListener('paste', (event) => {
            const clipboard = event.clipboardData;
            if (!clipboard) {
                return;
            }

            const pastedImages = Array.from(clipboard.items || []).filter((item) => (
                item?.kind === 'file'
                && String(item.type || '').toLowerCase().startsWith('image/')
            )).map((item) => item.getAsFile()).filter((file) => file instanceof File);

            if (pastedImages.length < 1) {
                return;
            }

            event.preventDefault();
            stagePendingImages(pastedImages);
        });
    }

    if (sendImageButton instanceof HTMLElement && sendImageInput instanceof HTMLInputElement) {
        sendImageButton.addEventListener('click', () => {
            sendImageInput.click();
        });

        sendImageInput.addEventListener('change', () => {
            const selectedFiles = Array.from(sendImageInput.files || []);
            if (selectedFiles.length < 1) {
                return;
            }

            stagePendingImages(selectedFiles);
            resetSelectedImageInput();
        });
    }

    if (sendImagePreviewList instanceof HTMLElement) {
        sendImagePreviewList.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const removeButton = target.closest('[data-remove-pending-image]');
            if (removeButton instanceof HTMLElement) {
                event.preventDefault();
                removePendingImage(removeButton.getAttribute('data-remove-pending-image'));
                sendMessageInput?.focus();
                return;
            }

            const openButton = target.closest('[data-open-pending-image]');
            if (!(openButton instanceof HTMLElement)) {
                return;
            }

            event.preventDefault();
            const pendingId = Number(openButton.getAttribute('data-open-pending-image') || 0);
            const pendingEntry = pendingImages.find((entry) => entry.id === pendingId);
            if (pendingEntry) {
                openImageModal(pendingEntry.previewUrl, pendingEntry.name);
            }
        });
    }

    if (sendForm && currentThread > 0) {
        sendForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = sendForm.querySelector('button[type="submit"]');
            const messageInput = sendMessageInput;
            if (!messageInput) {
                return;
            }
            const content = String(messageInput.value || '').trim();
            const originalContent = String(messageInput.value || '');
            const selectedImages = pendingImages
                .map((entry) => entry.file)
                .filter((file) => file instanceof File);
            const optimisticImages = pendingImages.map((entry) => ({
                url: entry.previewUrl,
                name: entry.name,
            }));
            const hasImage = selectedImages.length > 0;

            if (!content && !hasImage) {
                setUploadNotice('Please type a message or select an image to send.', 'error');
                return;
            }

            if (hasImage) {
                for (const selectedImage of selectedImages) {
                    const validationError = validateSelectedImage(selectedImage);
                    if (validationError !== '') {
                        setUploadNotice(validationError, 'error');
                        return;
                    }
                }
            }

            const formData = new FormData(sendForm);
            formData.delete('images[]');
            formData.delete('image');
            selectedImages.forEach((selectedImage) => {
                formData.append('images[]', selectedImage, pendingImageFileName(selectedImage));
            });
            const optimisticId = appendOptimisticMessage({
                message: content,
                images: optimisticImages,
                time: hasImage && !content ? 'Sending image...' : 'Sending...',
            });
            messageInput.value = '';
            syncSendMessageHeight();
            try {
                if (submitButton) {
                    submitButton.disabled = true;
                }
                setComposerBusy(true);

                const response = await fetch(sendForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: withSocketHeaders({
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }),
                    credentials: 'same-origin',
                });

                if (response.status === 422) {
                    removeOptimisticMessage(optimisticId);
                    messageInput.value = originalContent;
                    syncSendMessageHeight();
                    const payload = await response.json().catch(() => null);
                    const messageError = payload?.errors?.message?.[0]
                        || payload?.errors?.images?.[0]
                        || payload?.errors?.['images.0']?.[0]
                        || payload?.errors?.image?.[0]
                        || payload?.message
                        || 'Unable to send this message.';
                    setUploadNotice(messageError, 'error');
                    return;
                }

                if (!response.ok) {
                    removeOptimisticMessage(optimisticId);
                    messageInput.value = originalContent;
                    syncSendMessageHeight();
                    window.location.reload();
                    return;
                }

                await response.json().catch(() => null);
                messageInput.value = '';
                closeImageModal();
                syncSendMessageHeight();
                setUploadNotice('');
                const synced = await fetchConversation();
                if (!synced) {
                    window.location.reload();
                    return;
                }
                clearPendingImages({ clearNotice: true });
                scrollToBottom();
            } catch (error) {
                removeOptimisticMessage(optimisticId);
                messageInput.value = originalContent;
                syncSendMessageHeight();
                window.location.reload();
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
                setComposerBusy(false);
            }
        });
    }

    if (chatBody && currentThread > 0) {
        window.requestAnimationFrame(scrollToBottom);
    }

    if (chatBody) {
        chatBody.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const imageLink = target.closest('.msg-message-image-link');
            if (!(imageLink instanceof HTMLAnchorElement)) {
                return;
            }

            event.preventDefault();
            const image = imageLink.querySelector('.msg-message-image');
            const imageTitle = image instanceof HTMLImageElement && image.alt
                ? image.alt
                : 'Image preview';

            openImageModal(imageLink.href, imageTitle);
        });
    }

    if (imageModal instanceof HTMLElement) {
        imageModalCloseTargets.forEach((closeTarget) => {
            closeTarget.addEventListener('click', closeImageModal);
        });

        if (imageModalClose instanceof HTMLButtonElement) {
            imageModalClose.addEventListener('click', closeImageModal);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !imageModal.hidden) {
                closeImageModal();
            }
        });
    }

    window.addEventListener('beforeunload', revokeAllPendingImages);

    window.setInterval(poll, POLL_MS);
})();
</script>
@endsection
