@extends('layouts.dashboard')

@section('title', 'NADAI Management')
@section('page-title', 'NADAI Management')

@section('content')
    <div class="content-header">
        <h1>NADAI Management</h1>
        <p>Dedicated workspace for NADAI-related management and document processing.</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="padding: 28px;">
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:20px;border:1px solid #dbe3ee;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);">
                        <div style="width:48px;height:48px;border-radius:14px;background:#dbeafe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;flex:0 0 48px;">
                            <i class="fas fa-folder-open" style="font-size:20px;"></i>
                        </div>
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:6px;">NADAI Management Module</div>
                            <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.7;">
                                This page is now available as the dedicated landing page for <strong>NADAI Management</strong>.
                                The detailed workflow for uploads, tracking, and validation has not been implemented yet.
                            </p>
                        </div>
                    </div>

                    <div style="margin-top:18px;padding:18px 20px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;">
                        <div style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:8px;">Next Build Step</div>
                        <p style="margin:0;color:#334155;font-size:14px;line-height:1.7;">
                            Define the NADAI-specific records, required documents, submission flow, and approval rules for this module.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
