@extends('layouts.dashboard')

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('content')
    <div class="content-header">
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageSubtitle }}</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="padding: 28px;">
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:20px;border:1px solid #dbe3ee;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);">
                        <div style="width:48px;height:48px;border-radius:14px;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;flex:0 0 48px;">
                            <i class="fas fa-file-circle-check" style="font-size:20px;"></i>
                        </div>
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:6px;">One-Time Report Workspace</div>
                            <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.7;">
                                This page is now dedicated to <strong>{{ $pageTitle }}</strong>. The full submission, document, and approval workflow
                                for this one-time report has not been implemented yet.
                            </p>
                        </div>
                    </div>

                    <div style="margin-top:18px;padding:18px 20px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;">
                        <div style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:8px;">Next Build Step</div>
                        <p style="margin:0;color:#334155;font-size:14px;line-height:1.7;">
                            Define the data fields, attachments, validation rules, and LGU workflow needed for <strong>{{ $pageTitle }}</strong>.
                        </p>
                    </div>

                    <div style="margin-top:18px;">
                        <a href="{{ route('dashboard') }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#002c76;color:#ffffff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
