@extends('layouts.dashboard')

@section('title', 'Deadlines Configuration')
@section('page-title', 'Deadlines Configuration')

@section('content')
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

    <div class="content-header">
        <h1>Deadlines Configuration</h1>
        <p>Central access point for deadline-related setup areas and reportorial requirement configuration.</p>
    </div>

    <section class="deadline-shell">
        <div class="deadline-hero">
            <div class="deadline-hero__main">
                <div class="deadline-hero__icon" aria-hidden="true">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h2>Configuration Overview</h2>
                    <p>
                        Manage reportorial timelines from focused modules. Open a card to review requirements, tune schedules,
                        and keep tracking rules consistent across LGU and DILG workflows.
                    </p>
                </div>
            </div>

            <div class="deadline-hero__meta">
                <div class="deadline-hero__meta-item">
                    <span class="deadline-hero__meta-label">Configuration Areas</span>
                    <strong>{{ count($deadlineCards) }}</strong>
                </div>
                <div class="deadline-hero__meta-item">
                    <span class="deadline-hero__meta-label">Status</span>
                    <strong>Ready</strong>
                </div>
            </div>
        </div>

        <div class="deadline-grid">
            @foreach ($deadlineCards as $card)
                @php
                    $titleKey = strtolower((string) ($card['title'] ?? ''));
                    $cardToneClass = str_contains($titleKey, 'lgu')
                        ? 'deadline-card--lgu'
                        : (str_contains($titleKey, 'dilg') ? 'deadline-card--dilg' : '');
                @endphp
                @if (!empty($card['route'] ?? null))
                    <a href="{{ $card['route'] }}" class="deadline-card deadline-card--link {{ $cardToneClass }}">
                        <div class="deadline-card__icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="deadline-card__content">
                            <h3>{{ $card['title'] }}</h3>
                            <p>{{ $card['description'] }}</p>
                        </div>
                        <div class="deadline-card__footer">
                            <span>Open Configuration</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </div>
                    </a>
                @else
                    <article class="deadline-card deadline-card--static {{ $cardToneClass }}">
                        <div class="deadline-card__icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="deadline-card__content">
                            <h3>{{ $card['title'] }}</h3>
                            <p>{{ $card['description'] }}</p>
                        </div>
                        <div class="deadline-card__footer deadline-card__footer--pending">
                            <span>Module Coming Soon</span>
                            <i class="fas fa-clock" aria-hidden="true"></i>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    </section>

    <style>
        .deadline-shell {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 56%, #f1f5ff 100%);
            border: 1px solid #dbe4f0;
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
            padding: 24px;
            margin-bottom: 20px;
        }

        .deadline-shell::after {
            content: '';
            position: absolute;
            top: -140px;
            right: -90px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle at center, rgba(37, 99, 235, 0.18) 0%, rgba(37, 99, 235, 0) 72%);
            pointer-events: none;
        }

        .deadline-hero {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 16px;
            margin-bottom: 18px;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid #dbeafe;
            background: linear-gradient(150deg, #eff6ff 0%, #f8fbff 60%, #ffffff 100%);
        }

        .deadline-hero__main {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .deadline-hero__icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            background: linear-gradient(160deg, #1d4ed8 0%, #2563eb 100%);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
            flex: 0 0 auto;
        }

        .deadline-hero h2 {
            margin: 0;
            color: #002C76;
            font-size: 20px;
            line-height: 1.3;
        }

        .deadline-hero p {
            margin: 6px 0 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
            max-width: 720px;
        }

        .deadline-hero__meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(110px, 1fr));
            gap: 10px;
            align-self: center;
        }

        .deadline-hero__meta-item {
            border-radius: 12px;
            border: 1px solid #bfdbfe;
            background: #ffffff;
            padding: 10px 12px;
            text-align: center;
            min-width: 110px;
        }

        .deadline-hero__meta-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .deadline-hero__meta-item strong {
            color: #0f172a;
            font-size: 18px;
            line-height: 1.2;
        }

        .deadline-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .deadline-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 18px;
            min-height: 205px;
        }

        .deadline-card--lgu {
            border-color: #93b7f3;
            background: linear-gradient(180deg, #ffffff 0%, #eef4ff 100%);
        }

        .deadline-card--dilg {
            border-color: #fca5a5;
            background: linear-gradient(180deg, #ffffff 0%, #fff1f2 100%);
        }

        .deadline-card--link {
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            will-change: transform;
        }

        .deadline-card--link:hover {
            transform: translateY(-6px);
            border-color: #93c5fd;
            box-shadow: 0 18px 30px rgba(30, 64, 175, 0.2);
        }

        .deadline-card--lgu.deadline-card--link:hover {
            border-color: #002c76;
            box-shadow: 0 18px 30px rgba(0, 44, 118, 0.2);
        }

        .deadline-card--dilg.deadline-card--link:hover {
            border-color: #dc2626;
            box-shadow: 0 18px 30px rgba(220, 38, 38, 0.2);
        }

        .deadline-card--link:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.3);
            outline-offset: 2px;
            transform: translateY(-4px);
        }

        .deadline-card__icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .deadline-card--lgu .deadline-card__icon {
            background: #dbeafe;
            color: #002c76;
        }

        .deadline-card--dilg .deadline-card__icon {
            background: #fee2e2;
            color: #b91c1c;
        }

        .deadline-card__content h3 {
            margin: 0 0 8px;
            color: #002C76;
            font-size: 16px;
            line-height: 1.35;
        }

        .deadline-card__content p {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.68;
        }

        .deadline-card__footer {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #1d4ed8;
            border-top: 1px dashed #bfdbfe;
            padding-top: 12px;
        }

        .deadline-card--lgu .deadline-card__footer {
            color: #002c76;
            border-top-color: #93b7f3;
        }

        .deadline-card--dilg .deadline-card__footer {
            color: #b91c1c;
            border-top-color: #fca5a5;
        }

        .deadline-card__footer--pending {
            color: #64748b;
            border-top-color: #dbe4f0;
        }

        @media (max-width: 880px) {
            .deadline-shell {
                padding: 18px;
            }

            .deadline-hero {
                flex-direction: column;
            }

            .deadline-hero__meta {
                width: 100%;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .deadline-shell {
                padding: 14px;
                border-radius: 12px;
            }

            .deadline-hero {
                padding: 12px;
            }

            .deadline-hero__main {
                gap: 10px;
            }

            .deadline-hero__icon {
                width: 46px;
                height: 46px;
                font-size: 17px;
            }

            .deadline-hero h2 {
                font-size: 18px;
            }

            .deadline-grid {
                grid-template-columns: 1fr;
            }

            .deadline-card {
                min-height: auto;
            }
        }
    </style>
@endsection
