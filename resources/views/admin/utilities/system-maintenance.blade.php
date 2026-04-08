@extends('layouts.dashboard')

@section('title', 'System Maintenance')
@section('page-title', 'System Maintenance')

@section('content')
    <div class="content-header">
        <h1>System Maintenance</h1>
        <p>Preview workspace for maintenance tools, recovery actions, and service safeguards before backend controls are connected.</p>
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
                    <div class="maintenance-hero__eyebrow">Preview Only</div>
                    <h2>Operational Control Deck</h2>
                    <p>
                        This page is intentionally design-first. The layout is ready for maintenance mode controls, service restarts,
                        cache cleanup, and emergency recovery workflows, but no actions are active yet.
                    </p>
                </div>
            </div>

            <div class="maintenance-hero__actions">
                <a href="{{ route('utilities.system-setup.index') }}" class="maintenance-button maintenance-button--secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to System Setup</span>
                </a>
                <button type="button" class="maintenance-button maintenance-button--primary" disabled>
                    <i class="fas fa-power-off"></i>
                    <span>Maintenance Mode</span>
                </button>
            </div>
        </div>

        <div class="maintenance-status-grid">
            <article class="maintenance-stat maintenance-stat--accent">
                <span class="maintenance-stat__label">System State</span>
                <strong class="maintenance-stat__value">Operational</strong>
                <p class="maintenance-stat__meta">Live status indicator placeholder for future maintenance toggles.</p>
            </article>

            <article class="maintenance-stat">
                <span class="maintenance-stat__label">Public Access</span>
                <strong class="maintenance-stat__value">Allowed</strong>
                <p class="maintenance-stat__meta">Will reflect whether public-facing routes stay open during service work.</p>
            </article>

            <article class="maintenance-stat">
                <span class="maintenance-stat__label">Queued Tasks</span>
                <strong class="maintenance-stat__value">0 Pending</strong>
                <p class="maintenance-stat__meta">Reserved area for jobs, recovery tasks, and maintenance automation events.</p>
            </article>

            <article class="maintenance-stat">
                <span class="maintenance-stat__label">Last Review</span>
                <strong class="maintenance-stat__value">{{ now()->format('M d, Y') }}</strong>
                <p class="maintenance-stat__meta">Static placeholder stamp so the page looks complete while features are pending.</p>
            </article>
        </div>

        <div class="maintenance-grid">
            <article class="maintenance-card maintenance-card--highlight">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <span class="maintenance-chip">Coming Soon</span>
                </div>
                <h3>Service Access Control</h3>
                <p>Future controls for maintenance lockouts, read-only mode, and selective access for administrators during live interventions.</p>
                <div class="maintenance-card__footer">
                    <span>Planned controls</span>
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                </div>
            </article>

            <article class="maintenance-card">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <span class="maintenance-chip maintenance-chip--warning">Design Ready</span>
                </div>
                <h3>Maintenance Advisory</h3>
                <p>Placeholder area for an announcement banner, outage message, countdown window, and return-to-service notice.</p>
                <div class="maintenance-preview">
                    <div class="maintenance-preview__title">Scheduled Window</div>
                    <div class="maintenance-preview__body">Friday 10:00 PM to Saturday 1:00 AM</div>
                </div>
            </article>

            <article class="maintenance-card">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-broom"></i>
                    </div>
                    <span class="maintenance-chip">Placeholder</span>
                </div>
                <h3>Cache and Session Cleanup</h3>
                <p>Reserved for future actions such as clearing caches, invalidating stale sessions, and refreshing shared runtime state.</p>
                <div class="maintenance-action-row">
                    <button type="button" disabled>Clear Cache</button>
                    <button type="button" disabled>End Sessions</button>
                </div>
            </article>

            <article class="maintenance-card">
                <div class="maintenance-card__header">
                    <div class="maintenance-card__icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <span class="maintenance-chip">Preview</span>
                </div>
                <h3>Health Checks and Recovery</h3>
                <p>Designed for future database health checks, storage verification, and guided recovery procedures after incidents.</p>
                <ul class="maintenance-checklist">
                    <li><i class="fas fa-circle"></i> Application connectivity status</li>
                    <li><i class="fas fa-circle"></i> Database heartbeat and queue health</li>
                    <li><i class="fas fa-circle"></i> Storage integrity and backup readiness</li>
                </ul>
            </article>
        </div>

        <section class="maintenance-roadmap">
            <div>
                <span class="maintenance-roadmap__label">Implementation Roadmap</span>
                <h3>Suggested modules for the actual backend phase</h3>
                <p>When you are ready to wire behavior, this page can host controlled maintenance mode switches, broadcast notices, cache tools, and recovery commands behind stricter confirmations.</p>
            </div>

            <div class="maintenance-roadmap__list">
                <div class="maintenance-roadmap__item">
                    <strong>Global maintenance toggle</strong>
                    <span>Enable or disable a site-wide restricted state.</span>
                </div>
                <div class="maintenance-roadmap__item">
                    <strong>Custom outage messaging</strong>
                    <span>Publish planned downtime notices to users before and during maintenance windows.</span>
                </div>
                <div class="maintenance-roadmap__item">
                    <strong>Operational cleanup tools</strong>
                    <span>Expose cache clear, queue retry, and stale session cleanup actions with audit logging.</span>
                </div>
            </div>
        </section>
    </section>

    <style>
        .maintenance-shell {
            --maintenance-primary: #002c76;
            --maintenance-primary-soft: #dbeafe;
            --maintenance-surface: #ffffff;
            --maintenance-surface-alt: #f8fbff;
            --maintenance-text: #0f172a;
            --maintenance-muted: #64748b;
            --maintenance-border: #dbe4f0;
            --maintenance-warning: #b45309;
            --maintenance-warning-bg: #fef3c7;
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
        .maintenance-status-grid,
        .maintenance-grid,
        .maintenance-roadmap {
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
            margin-bottom: 18px;
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
            background: rgba(255, 255, 255, 0.75);
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
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
            height: 42px;
            padding: 0 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid transparent;
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
            cursor: not-allowed;
            opacity: 0.65;
        }

        .maintenance-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .maintenance-stat {
            border: 1px solid var(--maintenance-border);
            border-radius: 16px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(6px);
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
            font-size: 26px;
            line-height: 1.05;
        }

        .maintenance-stat__meta {
            margin: 10px 0 0;
            color: var(--maintenance-muted);
            font-size: 12px;
            line-height: 1.65;
        }

        .maintenance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
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

        .maintenance-chip--warning {
            background: var(--maintenance-warning-bg);
            color: var(--maintenance-warning);
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
        }

        .maintenance-action-row {
            margin-top: auto;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .maintenance-action-row button {
            min-width: 120px;
            height: 40px;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 700;
            cursor: not-allowed;
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

        .maintenance-roadmap {
            display: grid;
            grid-template-columns: minmax(260px, 1.15fr) minmax(260px, 1fr);
            gap: 18px;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid #dbe4f0;
            background: rgba(255, 255, 255, 0.88);
        }

        .maintenance-roadmap__label {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        .maintenance-roadmap h3 {
            margin: 0 0 10px;
            color: var(--maintenance-primary);
            font-size: 20px;
            line-height: 1.35;
        }

        .maintenance-roadmap p {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.75;
        }

        .maintenance-roadmap__list {
            display: grid;
            gap: 12px;
        }

        .maintenance-roadmap__item {
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #dbeafe;
            background: linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%);
        }

        .maintenance-roadmap__item strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .maintenance-roadmap__item span {
            display: block;
            color: #64748b;
            font-size: 12px;
            line-height: 1.65;
        }

        @media (max-width: 920px) {
            .maintenance-hero,
            .maintenance-roadmap {
                grid-template-columns: 1fr;
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

            .maintenance-hero,
            .maintenance-roadmap {
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
