@extends('layouts.dashboard')

@section('title', 'Bulk Notification')
@section('page-title', 'Bulk Notification')

@section('content')
    @php
        $selectedScope = old('target_scope', 'selected_users');
        $selectedUserIds = collect(old('user_ids', []))->map(fn ($value) => (string) $value)->all();
        $selectedRole = old('role');
    @endphp

    <div class="content-header">
        <h1>Bulk Notification</h1>
        <p>Broadcast one message through both email and system notifications to selected users, one user level, or all active users.</p>
    </div>

    @if (session('success'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #166534; font-size: 13px; font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 600;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff7f7; color: #991b1b;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="bulk-notification-shell" style="background: #ffffff; padding: 28px; border-radius: 14px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; flex-wrap: wrap; margin-bottom: 22px;">
            <div style="display: flex; align-items: flex-start; gap: 14px;">
                <div style="width: 54px; height: 54px; border-radius: 16px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div>
                    <h2 style="margin: 0; color: #002C76; font-size: 22px;">Broadcast Center</h2>
                    <p style="margin: 6px 0 0; color: #64748b; font-size: 14px; line-height: 1.7; max-width: 840px;">
                        Every send creates a system notification for each matched recipient and also sends the same announcement by email when a valid address is available.
                    </p>
                </div>
            </div>

            <div style="display: inline-flex; gap: 8px; flex-wrap: wrap;">
                <span style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700;">
                    <i class="fas fa-bell"></i>
                    System Notification
                </span>
                <span style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700;">
                    <i class="fas fa-envelope"></i>
                    Email Delivery
                </span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 22px;">
            <div style="border: 1px solid #bfdbfe; border-radius: 14px; padding: 16px 18px; background: linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%);">
                <span style="display: block; font-size: 12px; font-weight: 700; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.08em;">Active Users</span>
                <strong style="display: block; margin-top: 10px; color: #002C76; font-size: 30px; line-height: 1;">{{ number_format((int) $totalActiveUsers) }}</strong>
            </div>

            @foreach ($roleGroups as $group)
                <div style="border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 18px; background: #ffffff;">
                    <span style="display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.08em;">{{ $group['label'] }}</span>
                    <strong style="display: block; margin-top: 10px; color: #0f172a; font-size: 28px; line-height: 1;">{{ number_format((int) $group['count']) }}</strong>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('utilities.notifications.broadcast') }}">
            @csrf

            <div style="margin-bottom: 18px;">
                <h3 style="margin: 0 0 10px; color: #002C76; font-size: 18px;">Audience</h3>
                <div class="bulk-notification-scope-grid">
                    <label class="bulk-notification-scope-card" data-scope-card>
                        <input type="radio" name="target_scope" value="selected_users" {{ $selectedScope === 'selected_users' ? 'checked' : '' }} data-scope-input>
                        <span class="bulk-notification-scope-card__icon" style="background: #eff6ff; color: #1d4ed8;">
                            <i class="fas fa-user-check"></i>
                        </span>
                        <span class="bulk-notification-scope-card__copy">
                            <strong>Selected User(s)</strong>
                            <small>Pick one or more active users manually.</small>
                        </span>
                    </label>

                    <label class="bulk-notification-scope-card" data-scope-card>
                        <input type="radio" name="target_scope" value="selected_role" {{ $selectedScope === 'selected_role' ? 'checked' : '' }} data-scope-input>
                        <span class="bulk-notification-scope-card__icon" style="background: #f5f3ff; color: #6d28d9;">
                            <i class="fas fa-layer-group"></i>
                        </span>
                        <span class="bulk-notification-scope-card__copy">
                            <strong>User Level</strong>
                            <small>Send to every active account under one role level.</small>
                        </span>
                    </label>

                    <label class="bulk-notification-scope-card" data-scope-card>
                        <input type="radio" name="target_scope" value="all_users" {{ $selectedScope === 'all_users' ? 'checked' : '' }} data-scope-input>
                        <span class="bulk-notification-scope-card__icon" style="background: #ecfdf5; color: #15803d;">
                            <i class="fas fa-users"></i>
                        </span>
                        <span class="bulk-notification-scope-card__copy">
                            <strong>All Active Users</strong>
                            <small>Broadcast to the whole active user base.</small>
                        </span>
                    </label>
                </div>
            </div>

            <div class="bulk-notification-panel {{ $selectedScope === 'selected_users' ? 'is-active' : '' }}" data-scope-panel="selected_users">
                <label for="user_ids" style="display: block; margin-bottom: 8px; color: #0f172a; font-size: 13px; font-weight: 700;">
                    Select Recipients
                </label>
                <select
                    id="user_ids"
                    name="user_ids[]"
                    multiple
                    size="10"
                    style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; color: #0f172a; background: #ffffff;"
                >
                    @foreach ($activeUsers as $user)
                        @php
                            $userLabel = trim(($user->lname ?? '') . ', ' . ($user->fname ?? ''));
                            $userMeta = collect([
                                $roleOptions[$user->normalizedRole()] ?? $user->role,
                                $user->province,
                                $user->emailaddress,
                            ])->filter(fn ($value) => filled($value))->implode(' • ');
                        @endphp
                        <option value="{{ $user->idno }}" @selected(in_array((string) $user->idno, $selectedUserIds, true))>
                            {{ $userLabel }}{{ $userMeta !== '' ? ' • ' . $userMeta : '' }}
                        </option>
                    @endforeach
                </select>
                <p style="margin: 8px 0 0; color: #64748b; font-size: 12px; line-height: 1.6;">
                    Hold `Ctrl` or `Cmd` while clicking to choose multiple active users.
                </p>
            </div>

            <div class="bulk-notification-panel {{ $selectedScope === 'selected_role' ? 'is-active' : '' }}" data-scope-panel="selected_role">
                <label for="role" style="display: block; margin-bottom: 8px; color: #0f172a; font-size: 13px; font-weight: 700;">
                    Select User Level
                </label>
                <select
                    id="role"
                    name="role"
                    style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; color: #0f172a; background: #ffffff;"
                >
                    <option value="">-- Select user level --</option>
                    @foreach ($roleGroups as $group)
                        <option value="{{ $group['role'] }}" @selected($selectedRole === $group['role'])>
                            {{ $group['label'] }} ({{ number_format((int) $group['count']) }} active)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="bulk-notification-panel {{ $selectedScope === 'all_users' ? 'is-active' : '' }}" data-scope-panel="all_users">
                <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; font-size: 13px; line-height: 1.7;">
                    This broadcast will target all <strong>{{ number_format((int) $totalActiveUsers) }}</strong> active users currently registered in the system.
                </div>
            </div>

            <div style="display: grid; grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr); gap: 16px; margin-top: 20px;">
                <div>
                    <label for="title" style="display: block; margin-bottom: 8px; color: #0f172a; font-size: 13px; font-weight: 700;">
                        Notification Title
                    </label>
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        maxlength="120"
                        placeholder="Example: Monthly submission reminder"
                        style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; color: #0f172a; background: #ffffff;"
                    >
                </div>

                <div>
                    <label for="redirect_path" style="display: block; margin-bottom: 8px; color: #0f172a; font-size: 13px; font-weight: 700;">
                        Optional Redirect Path
                    </label>
                    <input
                        id="redirect_path"
                        type="text"
                        name="redirect_path"
                        value="{{ old('redirect_path', '/dashboard') }}"
                        placeholder="/dashboard"
                        style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; color: #0f172a; background: #ffffff;"
                    >
                    <p style="margin: 8px 0 0; color: #64748b; font-size: 12px; line-height: 1.6;">
                        This internal path opens when a user clicks the system notification or email action button.
                    </p>
                </div>
            </div>

            <div style="margin-top: 16px;">
                <label for="message" style="display: block; margin-bottom: 8px; color: #0f172a; font-size: 13px; font-weight: 700;">
                    Message
                </label>
                <textarea
                    id="message"
                    name="message"
                    rows="8"
                    placeholder="Write the announcement that should be delivered to the selected audience."
                    style="width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 14px; font-size: 14px; color: #0f172a; background: #ffffff; resize: vertical;"
                >{{ old('message') }}</textarea>
                <p style="margin: 8px 0 0; color: #64748b; font-size: 12px; line-height: 1.6;">
                    The full message is sent by email. A shortened title-and-message version also appears in the in-app notification bell.
                </p>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-top: 22px;">
                <div style="color: #475569; font-size: 13px; line-height: 1.7;">
                    Delivery happens immediately after submit. Large audiences may take a little longer because email is sent per recipient.
                </div>

                <button
                    type="submit"
                    data-confirm="Broadcast this message through both email and system notifications?"
                    style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 18px; border: none; border-radius: 12px; background: linear-gradient(135deg, #002c76 0%, #0a4fb3 100%); color: #ffffff; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 12px 24px rgba(0, 44, 118, 0.18);"
                >
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Bulk Notification</span>
                </button>
            </div>
        </form>
    </section>

    <style>
        .bulk-notification-scope-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .bulk-notification-scope-card {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f8fbff;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .bulk-notification-scope-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .bulk-notification-scope-card.is-active {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
        }

        .bulk-notification-scope-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .bulk-notification-scope-card__icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex: 0 0 auto;
        }

        .bulk-notification-scope-card__copy {
            display: grid;
            gap: 4px;
        }

        .bulk-notification-scope-card__copy strong {
            color: #0f172a;
            font-size: 14px;
        }

        .bulk-notification-scope-card__copy small {
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .bulk-notification-panel {
            display: none;
            margin-top: 12px;
        }

        .bulk-notification-panel.is-active {
            display: block;
        }

        @media (max-width: 768px) {
            .bulk-notification-shell {
                padding: 20px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script>
        (function initializeBulkNotificationAudience() {
            const scopeInputs = Array.from(document.querySelectorAll('[data-scope-input]'));
            const scopeCards = Array.from(document.querySelectorAll('[data-scope-card]'));
            const scopePanels = Array.from(document.querySelectorAll('[data-scope-panel]'));

            if (!scopeInputs.length) {
                return;
            }

            function syncAudiencePanels() {
                const activeInput = scopeInputs.find(function(input) {
                    return input.checked;
                });

                const activeValue = activeInput ? activeInput.value : '';

                scopeCards.forEach(function(card) {
                    const input = card.querySelector('[data-scope-input]');
                    card.classList.toggle('is-active', !!input && input.checked);
                });

                scopePanels.forEach(function(panel) {
                    panel.classList.toggle('is-active', panel.getAttribute('data-scope-panel') === activeValue);
                });
            }

            scopeInputs.forEach(function(input) {
                input.addEventListener('change', syncAudiencePanels);
            });

            syncAudiencePanels();
        })();
    </script>
@endsection
