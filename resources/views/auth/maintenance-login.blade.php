<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Temporary Superadmin Login - PRISM</title>
    <link rel="icon" type="image/png" href="{{ asset('DILG-Logo.png') }}">
    @include('partials.google-sans-font')
    <style>
        :root {
            --maintenance-blue: #002c76;
            --maintenance-blue-dark: #00173f;
            --maintenance-accent: #0f62fe;
            --maintenance-border: rgba(255, 255, 255, 0.18);
            --maintenance-surface: rgba(255, 255, 255, 0.96);
            --maintenance-text: #0f172a;
            --maintenance-muted: #475569;
            --maintenance-warning-bg: #fff7ed;
            --maintenance-warning-border: #fdba74;
            --maintenance-warning-text: #9a3412;
            --maintenance-error-bg: #fff1f2;
            --maintenance-error-border: #fecdd3;
            --maintenance-error-text: #b91c1c;
            --app-font-sans: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--app-font-sans);
            color: var(--maintenance-text);
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0) 28%),
                linear-gradient(140deg, #00173f 0%, #002c76 48%, #0b5bd3 100%);
        }

        .maintenance-login-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 16px;
        }

        .maintenance-login-card {
            width: min(100%, 980px);
            display: grid;
            grid-template-columns: minmax(280px, 1.05fr) minmax(320px, 0.95fr);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--maintenance-border);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 28px 56px rgba(0, 18, 56, 0.34);
            backdrop-filter: blur(14px);
        }

        .maintenance-panel {
            padding: 36px 34px;
        }

        .maintenance-panel--info {
            color: #ffffff;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.08) 0%, rgba(0, 0, 0, 0.18) 100%);
        }

        .maintenance-panel--form {
            background: var(--maintenance-surface);
        }

        .maintenance-logo {
            display: block;
            width: 84px;
            height: auto;
            margin-bottom: 18px;
        }

        .maintenance-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .maintenance-panel--info h1 {
            margin: 18px 0 10px;
            font-size: clamp(30px, 4vw, 42px);
            line-height: 1.08;
        }

        .maintenance-panel--info p {
            margin: 0;
            color: rgba(255, 255, 255, 0.86);
            font-size: 15px;
            line-height: 1.8;
        }

        .maintenance-list {
            margin: 26px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .maintenance-list li {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 12px;
            align-items: start;
        }

        .maintenance-list__badge {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            font-size: 16px;
            font-weight: 700;
        }

        .maintenance-list strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .maintenance-list span {
            display: block;
            color: rgba(255, 255, 255, 0.76);
            font-size: 13px;
            line-height: 1.65;
        }

        .maintenance-meta {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
            color: rgba(255, 255, 255, 0.76);
            font-size: 12px;
            line-height: 1.7;
        }

        .maintenance-panel--form h2 {
            margin: 0 0 8px;
            color: var(--maintenance-blue);
            font-size: 28px;
            line-height: 1.15;
        }

        .maintenance-panel--form p {
            margin: 0 0 18px;
            color: var(--maintenance-muted);
            font-size: 14px;
            line-height: 1.75;
        }

        .maintenance-alert {
            margin-bottom: 16px;
            padding: 13px 14px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.7;
        }

        .maintenance-alert--warning {
            background: var(--maintenance-warning-bg);
            border: 1px solid var(--maintenance-warning-border);
            color: var(--maintenance-warning-text);
        }

        .maintenance-alert--error {
            background: var(--maintenance-error-bg);
            border: 1px solid var(--maintenance-error-border);
            color: var(--maintenance-error-text);
        }

        .maintenance-form {
            display: grid;
            gap: 16px;
        }

        .maintenance-field {
            display: grid;
            gap: 8px;
        }

        .maintenance-field label {
            font-size: 13px;
            font-weight: 600;
            color: var(--maintenance-text);
        }

        .maintenance-field input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid #d8e2f0;
            background: #ffffff;
            color: var(--maintenance-text);
            font-size: 14px;
        }

        .maintenance-field input:focus {
            outline: none;
            border-color: rgba(15, 98, 254, 0.65);
            box-shadow: 0 0 0 4px rgba(15, 98, 254, 0.12);
        }

        .maintenance-submit {
            min-height: 48px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--maintenance-blue) 0%, var(--maintenance-accent) 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 16px 30px rgba(0, 44, 118, 0.2);
        }

        .maintenance-submit:hover {
            background: linear-gradient(135deg, var(--maintenance-blue-dark) 0%, #0a4db4 100%);
        }

        .maintenance-links {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .maintenance-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid #dbe4f0;
            background: #ffffff;
            color: var(--maintenance-blue);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .maintenance-link:hover {
            background: #eff6ff;
        }

        @media (max-width: 900px) {
            .maintenance-login-card {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 540px) {
            .maintenance-panel {
                padding: 24px 20px;
            }

            .maintenance-links {
                flex-direction: column;
            }

            .maintenance-link {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $lastUpdatedDisplay = $maintenanceState['updated_at_display'] ?? null;
    @endphp

    <div class="maintenance-login-shell">
        <div class="maintenance-login-card">
            <section class="maintenance-panel maintenance-panel--info">
                <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo" class="maintenance-logo">
                <span class="maintenance-pill">Temporary Superadmin Access</span>
                <h1>Maintenance mode is active. Superadmin access is available only through this temporary page.</h1>
                <p>This page is reserved for recovery, verification, and maintenance tasks while the public login route is redirected to the maintenance notice.</p>

                <ul class="maintenance-list">
                    <li>
                        <div class="maintenance-list__badge">1</div>
                        <div>
                            <strong>Public login is closed</strong>
                            <span>Requests to `/login` are redirected to the maintenance notice while maintenance mode remains enabled.</span>
                        </div>
                    </li>
                    <li>
                        <div class="maintenance-list__badge">2</div>
                        <div>
                            <strong>Only superadmins can continue</strong>
                            <span>Valid non-superadmin credentials remain blocked even if they reach a login form during maintenance.</span>
                        </div>
                    </li>
                    <li>
                        <div class="maintenance-list__badge">3</div>
                        <div>
                            <strong>Use this page temporarily</strong>
                            <span>Disable maintenance mode when system work is finished so the normal `/login` page becomes public again.</span>
                        </div>
                    </li>
                </ul>

                @if ($lastUpdatedDisplay)
                    <div class="maintenance-meta">
                        Maintenance mode last updated: {{ $lastUpdatedDisplay }}
                    </div>
                @endif
            </section>

            <section class="maintenance-panel maintenance-panel--form">
                <h2>Superadmin Login</h2>
                <p>Enter a superadmin username and password to access PRISM during the maintenance window.</p>

                <div class="maintenance-alert maintenance-alert--warning">
                    This is a temporary maintenance-only login page for superadmin accounts.
                </div>

                @if ($errors->any())
                    <div class="maintenance-alert maintenance-alert--error">
                        @if ($errors->has('login_error'))
                            {{ $errors->first('login_error') }}
                        @else
                            {{ $errors->first() }}
                        @endif
                    </div>
                @endif

                <form class="maintenance-form" action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="maintenance-field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required>
                    </div>

                    <div class="maintenance-field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="maintenance-submit">Sign In as Superadmin</button>
                </form>

                <div class="maintenance-links">
                    <a href="{{ route('maintenance.notice') }}" class="maintenance-link">Back to Maintenance Notice</a>
                    <a href="{{ url('/') }}" class="maintenance-link">Back to Landing Page</a>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
