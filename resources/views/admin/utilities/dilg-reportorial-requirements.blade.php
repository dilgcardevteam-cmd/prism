@extends('layouts.dashboard')

@section('title', 'DILG Reportorial Requirements')
@section('page-title', 'DILG Reportorial Requirements')

@section('content')
    <div class="content-header">
        <h1>DILG Reportorial Requirements</h1>
        <p>Manage DILG-specific reportorial requirement settings and future workflow controls.</p>
    </div>

    <section class="reportorial-shell reportorial-shell--dilg">
        <div class="reportorial-header">
            <div class="reportorial-icon" aria-hidden="true">
                <i class="fas fa-file-signature"></i>
            </div>
            <div>
                <h2>DILG Configuration Workspace</h2>
                <p>
                    This module is ready for requirement setup screens, compliance criteria, and milestone tracking rules
                    aligned with DILG reportorial oversight.
                </p>
            </div>
        </div>

        <div class="reportorial-grid">
            <article class="reportorial-card">
                <h3>Requirement Definitions</h3>
                <p>Maintain DILG reportorial forms, supporting document sets, and verification checkpoints.</p>
            </article>
            <article class="reportorial-card">
                <h3>Compliance Schedule</h3>
                <p>Configure period-based reporting deadlines and reminder windows for participating offices.</p>
            </article>
            <article class="reportorial-card">
                <h3>Submission Monitoring</h3>
                <p>Prepare summary metrics for pending, overdue, and completed DILG-related requirements.</p>
            </article>
        </div>

        <a href="{{ route('utilities.deadlines-configuration.index') }}" class="reportorial-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Deadlines Configuration</span>
        </a>
    </section>

    <style>
        .reportorial-shell {
            background: #ffffff;
            border: 1px solid #dbe4f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .reportorial-shell--dilg {
            background: linear-gradient(180deg, #ffffff 0%, #fff1f2 100%);
            border-color: #fca5a5;
        }

        .reportorial-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 18px;
        }

        .reportorial-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: #fee2e2;
            color: #b91c1c;
            flex: 0 0 auto;
        }

        .reportorial-header h2 {
            margin: 0;
            color: #991b1b;
            font-size: 20px;
        }

        .reportorial-header p {
            margin: 6px 0 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
        }

        .reportorial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .reportorial-card {
            background: #ffffff;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 14px;
        }

        .reportorial-card h3 {
            margin: 0 0 8px;
            color: #991b1b;
            font-size: 15px;
        }

        .reportorial-card p {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .reportorial-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #fca5a5;
            background: #ffffff;
            color: #991b1b;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .reportorial-back-link:hover {
            background: #fff1f2;
        }

        @media (max-width: 640px) {
            .reportorial-shell {
                padding: 16px;
            }

            .reportorial-header {
                flex-direction: column;
            }
        }
    </style>
@endsection
