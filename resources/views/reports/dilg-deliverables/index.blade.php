@extends('layouts.dashboard')

@section('title', 'DILG Deliverables')
@section('page-title', 'DILG Deliverables')

@section('content')
    <div class="content-header">
        <h1>DILG Deliverables</h1>
        <p>Workspace for deliverables handled by DILG Provincial and Regional Office users.</p>
    </div>

    <section class="dilg-deliverables-shell">
        <div class="dilg-deliverables-hero">
            <div class="dilg-deliverables-icon" aria-hidden="true">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <h2>DILG Office Deliverables</h2>
                <p>
                    This page is reserved for DILG Provincial Office and DILG Regional Office users. You can extend
                    this workspace with deliverable tracking, compliance summaries, and document actions when the
                    functional requirements are ready.
                </p>
            </div>
        </div>

        <div class="dilg-deliverables-grid">
            <article class="dilg-deliverables-card">
                <h3>Provincial Office Queue</h3>
                <p>Stage deliverables that need provincial review, validation, or endorsement.</p>
            </article>
            <article class="dilg-deliverables-card">
                <h3>Regional Office Oversight</h3>
                <p>Track escalated deliverables, regional actions, and completion checkpoints.</p>
            </article>
            <article class="dilg-deliverables-card">
                <h3>Compliance Monitoring</h3>
                <p>Prepare deadline, status, and accomplishment summaries for DILG reporting workflows.</p>
            </article>
        </div>
    </section>

    <style>
        .dilg-deliverables-shell {
            background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .dilg-deliverables-hero {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .dilg-deliverables-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 22px;
            flex: 0 0 auto;
        }

        .dilg-deliverables-hero h2 {
            margin: 0;
            color: #1e3a8a;
            font-size: 22px;
        }

        .dilg-deliverables-hero p {
            margin: 8px 0 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
            max-width: 760px;
        }

        .dilg-deliverables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .dilg-deliverables-card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 16px;
        }

        .dilg-deliverables-card h3 {
            margin: 0 0 8px;
            color: #1e40af;
            font-size: 15px;
        }

        .dilg-deliverables-card p {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 640px) {
            .dilg-deliverables-shell {
                padding: 16px;
            }

            .dilg-deliverables-hero {
                flex-direction: column;
            }
        }
    </style>
@endsection
