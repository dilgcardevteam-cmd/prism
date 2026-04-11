@extends('layouts.dashboard')

@section('title', 'System Maintenance')
@section('page-title', 'System Maintenance')

@section('content')
    @php
        $isMaintenanceEnabled = (bool) ($maintenanceState['enabled'] ?? false);
        $lastUpdatedDisplay = $maintenanceState['updated_at_display'] ?? now()->format('M d, Y h:i A');
        $lastUpdatedBy = $maintenanceState['updated_by_name'] ?? 'Superadmin';
    @endphp

    @if (session('success'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #166534; font-size: 13px; font-weight: 600;">
            {{ session('success') }}
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

    <div class="content-header">
        <h1>System Maintenance</h1>
        <p>Control whether PRISM stays open to all users or is temporarily limited to superadmin access only.</p>
    </div>

    <section class="maintenance-shell">
        <div class="maintenance-shell__glow maintenance-shell__glow--one" aria-hidden="true"></div>
        <div class="maintenance-shell__glow maintenance-shell__glow--two" aria-hidden="true"></div>

        <div class="maintenance-hero">
            <div class="maintenance-hero__main">
                <div class="maintenance-hero__icon" aria-hidden="true">
                    <i class="fas fa-tools"></i>
                </div>
                <div>
                    <div class="maintenance-hero__eyebrow {{ $isMaintenanceEnabled ? 'is-live' : '' }}">
                        {{ $isMaintenanceEnabled ? 'Maintenance Active' : 'System Operational' }}
                    </div>
                    <h2>{{ $isMaintenanceEnabled ? 'Restricted Access is now live' : 'Operational access is currently open' }}</h2>
                    <p>
                        @if ($isMaintenanceEnabled)
                            Public `/login` requests are redirected to the maintenance notice page. Only the temporary `/admin/login` page accepts superadmin sign-ins during maintenance.
                        @else
                            All users can access PRISM normally. Enable maintenance mode when you need to temporarily restrict access to superadmin accounts only.
                        @endif
                    </p>
                </div>
            </div>

            <div class="maintenance-hero__actions">
                <a href="{{ route('utilities.system-setup.index') }}" class="maintenance-button maintenance-button--secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to System Setup</span>
                </a>

                <form
                    method="POST"
                    action="{{ route('utilities.system-maintenance.toggle') }}"
                    onsubmit="return confirm('{{ $isMaintenanceEnabled ? 'Disable maintenance mode and restore regular access for all users?' : 'Enable maintenance mode? Only superadmin accounts will be able to sign in and access PRISM.' }}')"
                    style="margin: 0;"
                >
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $isMaintenanceEnabled ? '0' : '1' }}">
                    <button type="submit" class="maintenance-button maintenance-button--primary {{ $isMaintenanceEnabled ? 'maintenance-button--danger' : '' }}">
                        <i class="fas {{ $isMaintenanceEnabled ? 'fa-lock-open' : 'fa-power-off' }}"></i>
                        <span>{{ $isMaintenanceEnabled ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' }}</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="maintenance-banner {{ $isMaintenanceEnabled ? 'maintenance-banner--live' : '' }}">
            <div class="maintenance-banner__icon">
                <i class="fas {{ $isMaintenanceEnabled ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
            </div>
            <div class="maintenance-banner__content">
                <strong>{{ $isMaintenanceEnabled ? 'System lock is active.' : 'System is available.' }}</strong>
                <span>
                    @if ($isMaintenanceEnabled)
                        During this period, only superadmin accounts can sign in and use internal pages. Everyone else is limited to the landing page and maintenance notice page, while superadmins use the temporary `/admin/login` page.
                    @else
                        PRISM is accepting normal sign-ins and regular page navigation for authorized users.
                    @endif
                </span>
            </div>
        </div>

        <div class="maintenance-status-grid">
            <article class="maintenance-stat maintenance-stat--accent">
                <span class="maintenance-stat__label">System State</span>
                <strong class="maintenance-stat__value">{{ $isMaintenanceEnabled ? 'Under Maintenance' : 'Operational' }}</strong>
                <p class="maintenance-stat__meta">Primary status indicator used by the global access guard.</p>
            </article>

            <article class="maintenance-stat">
                <span class="maintenance-stat__label">Login Access</span>
                <strong class="maintenance-stat__value">{{ $isMaintenanceEnabled ? 'Superadmin Only' : 'Allowed' }}</strong>
                <p class="maintenance-stat__meta">Non-superadmin sign-in attempts are refused while maintenance mode is enabled.</p>
            </article>

            <article class="maintenance-stat">
                <span class="maintenance-stat__label">Allowed Public Pages</span>
                <strong class="maintenance-stat__value">{{ $isMaintenanceEnabled ? 'Landing + Notice' : 'Normal Routing' }}</strong>
                <p class="maintenance-stat__meta">The temporary `/admin/login` page remains available for superadmin access while maintenance is active.</p>
            </article>

            <article class="maintenance-stat">
                <span class="maintenance-stat__label">Last Updated</span>
                <strong class="maintenance-stat__value">{{ $lastUpdatedDisplay }}</strong>
                <p class="maintenance-stat__meta">Last changed by {{ $lastUpdatedBy }}.</p>
            </article>
        </div>

        <div class="maintenance-grid">
            <article class="maintenance-card maintenance-card--highlight">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="maintenance-chip {{ $isMaintenanceEnabled ? 'maintenance-chip--danger' : '' }}">
                        {{ $isMaintenanceEnabled ? 'Active Guard' : 'Ready' }}
                    </span>
                </div>
                <h3>Access Lock Policy</h3>
                <p>When enabled, every route except the landing page, logout route, maintenance notice page, and temporary `/admin/login` route is blocked for non-superadmin users.</p>
                <div class="maintenance-card__footer">
                    <span>{{ $isMaintenanceEnabled ? 'Restriction enforced' : 'Restriction available' }}</span>
                    <i class="fas fa-user-lock" aria-hidden="true"></i>
                </div>
            </article>

            <article class="maintenance-card">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-right-to-bracket"></i>
                    </div>
                    <span class="maintenance-chip">Login Flow</span>
                </div>
                <h3>Authentication Behavior</h3>
                <p>Superadmin credentials continue to work through the temporary `/admin/login` page. Public `/login` requests are redirected to the maintenance notice, and valid non-superadmin credentials stay blocked.</p>
                <div class="maintenance-preview">
                    <div class="maintenance-preview__title">Current Result</div>
                    <div class="maintenance-preview__body">{{ $isMaintenanceEnabled ? 'Regular users are blocked from sign-in.' : 'All active users can sign in.' }}</div>
                </div>
            </article>

            <article class="maintenance-card">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <span class="maintenance-chip">Redirect Rule</span>
                </div>
                <h3>Active Session Redirect</h3>
                <p>Users who are already signed in are not destroyed automatically. Instead, their next blocked request is redirected to the maintenance notice page.</p>
                <ul class="maintenance-checklist">
                    <li><i class="fas fa-circle"></i> Existing superadmin sessions stay inside PRISM</li>
                    <li><i class="fas fa-circle"></i> Existing non-superadmin sessions are intercepted on the next request</li>
                    <li><i class="fas fa-circle"></i> Logout remains available for blocked users</li>
                </ul>
            </article>

            <article class="maintenance-card">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-file-lines"></i>
                    </div>
                    <span class="maintenance-chip">Notice Page</span>
                </div>
                <h3>Public Maintenance Notice</h3>
                <p>A dedicated maintenance page explains the restriction, keeps the landing page accessible, and points superadmins to the temporary `/admin/login` page.</p>
                <div class="maintenance-card__footer">
                    <span>Route: `/system-under-maintenance`</span>
                    <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </div>
            </article>
        </div>
    </section>

    <style>
        .maintenance-shell {
            --maintenance-primary: #002c76;
            --maintenance-primary-soft: #dbeafe;
            --maintenance-surface: #ffffff;
            --maintenance-text: #0f172a;
            --maintenance-muted: #64748b;
            --maintenance-border: #dbe4f0;
            --maintenance-success: #166534;
            --maintenance-success-bg: #ecfdf5;
            --maintenance-danger: #b91c1c;
            --maintenance-danger-bg: #fff1f2;
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 54%, #eef4ff 100%);
            border: 1px solid var(--maintenance-border);
            border-radius: 18px;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
            padding: 24px;
            margin-bottom: 20px;
        }

        .maintenance-shell__glow {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(8px);
            opacity: 0.9;
        }

        .maintenance-shell__glow--one {
            top: -90px;
            right: -40px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle at center, rgba(37, 99, 235, 0.18) 0%, rgba(37, 99, 235, 0) 72%);
        }

        .maintenance-shell__glow--two {
            bottom: -120px;
            left: -70px;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle at center, rgba(14, 165, 233, 0.14) 0%, rgba(14, 165, 233, 0) 74%);
        }

        .maintenance-hero,
        .maintenance-banner,
        .maintenance-status-grid,
        .maintenance-grid {
            position: relative;
            z-index: 1;
        }

        .maintenance-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid #cfe0fb;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
            margin-bottom: 16px;
        }

        .maintenance-hero__main {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            max-width: 760px;
        }

        .maintenance-hero__icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #ffffff;
            background: linear-gradient(160deg, #002c76 0%, #1d4ed8 100%);
            box-shadow: 0 12px 24px rgba(29, 78, 216, 0.28);
            flex: 0 0 auto;
        }

        .maintenance-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .maintenance-hero__eyebrow.is-live {
            background: #fee2e2;
            color: #b91c1c;
        }

        .maintenance-hero h2 {
            margin: 0;
            color: var(--maintenance-primary);
            font-size: 24px;
            line-height: 1.2;
        }

        .maintenance-hero p {
            margin: 8px 0 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.75;
        }

        .maintenance-hero__actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .maintenance-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .maintenance-button--secondary {
            color: var(--maintenance-primary);
            background: rgba(255, 255, 255, 0.88);
            border-color: #cbd5e1;
        }

        .maintenance-button--primary {
            color: #ffffff;
            background: linear-gradient(135deg, #002c76 0%, #1d4ed8 100%);
            box-shadow: 0 12px 22px rgba(0, 44, 118, 0.2);
        }

        .maintenance-button--danger {
            background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
            box-shadow: 0 12px 22px rgba(153, 27, 27, 0.22);
        }

        .maintenance-banner {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #bbf7d0;
            background: var(--maintenance-success-bg);
            color: var(--maintenance-success);
            margin-bottom: 16px;
        }

        .maintenance-banner--live {
            border-color: #fecaca;
            background: var(--maintenance-danger-bg);
            color: var(--maintenance-danger);
        }

        .maintenance-banner__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.72);
            font-size: 16px;
            flex: 0 0 auto;
        }

        .maintenance-banner__content {
            display: grid;
            gap: 4px;
        }

        .maintenance-banner__content strong {
            font-size: 14px;
        }

        .maintenance-banner__content span {
            font-size: 13px;
            line-height: 1.7;
        }

        .maintenance-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .maintenance-stat {
            border: 1px solid var(--maintenance-border);
            border-radius: 16px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.9);
        }

        .maintenance-stat--accent {
            border-color: #93c5fd;
            background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
        }

        .maintenance-stat__label {
            display: block;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .maintenance-stat__value {
            display: block;
            margin-top: 10px;
            color: var(--maintenance-text);
            font-size: 24px;
            line-height: 1.18;
        }

        .maintenance-stat__meta {
            margin: 10px 0 0;
            color: var(--maintenance-muted);
            font-size: 12px;
            line-height: 1.65;
        }

        .maintenance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 14px;
        }

        .maintenance-card {
            display: flex;
            flex-direction: column;
            min-height: 230px;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid var(--maintenance-border);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
        }

        .maintenance-card--highlight {
            border-color: #93c5fd;
            background: linear-gradient(180deg, #ffffff 0%, #eef4ff 100%);
        }

        .maintenance-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .maintenance-card__icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .maintenance-chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
        }

        .maintenance-chip--danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .maintenance-card h3 {
            margin: 0 0 10px;
            color: var(--maintenance-primary);
            font-size: 18px;
            line-height: 1.35;
        }

        .maintenance-card p {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.72;
        }

        .maintenance-card__footer {
            margin-top: auto;
            padding-top: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            border-top: 1px dashed #bfdbfe;
        }

        .maintenance-preview {
            margin-top: auto;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .maintenance-preview__title {
            color: #9a3412;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .maintenance-preview__body {
            color: #7c2d12;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
        }

        .maintenance-checklist {
            list-style: none;
            margin: auto 0 0;
            padding: 0;
            display: grid;
            gap: 8px;
        }

        .maintenance-checklist li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: #334155;
            font-size: 13px;
            line-height: 1.65;
        }

        .maintenance-checklist i {
            color: #1d4ed8;
            font-size: 8px;
            margin-top: 7px;
        }

        @media (max-width: 920px) {
            .maintenance-hero {
                flex-direction: column;
            }

            .maintenance-hero__actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .maintenance-shell {
                padding: 14px;
                border-radius: 14px;
            }

            .maintenance-hero {
                padding: 14px;
            }

            .maintenance-hero__main {
                gap: 12px;
            }

            .maintenance-hero__icon {
                width: 48px;
                height: 48px;
                font-size: 18px;
            }

            .maintenance-hero h2 {
                font-size: 20px;
            }

            .maintenance-grid,
            .maintenance-status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
