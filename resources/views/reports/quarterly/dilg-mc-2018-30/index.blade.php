@extends('layouts.dashboard')

@section('title', 'DILG MC No. 2018-30')
@section('page-title', 'DILG MC No. 2018-30')

@section('content')
    <div class="content-header">
        <h1>DILG MC No. 2018-30</h1>
        <p>Report on Monitoring of Local Government Projects on Contractor's Compliance to Inform the Public before Commencement of Road Projects</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="padding: 28px;">
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:20px;border:1px solid #dbe3ee;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);">
                        <div style="width:48px;height:48px;border-radius:14px;background:#dcfce7;color:#15803d;display:flex;align-items:center;justify-content:center;flex:0 0 48px;">
                            <i class="fas fa-bullhorn" style="font-size:20px;"></i>
                        </div>
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:6px;">Quarterly Compliance Monitoring Workspace</div>
                            <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.7;">
                                This page is dedicated to monitoring contractor compliance with public information requirements before road project
                                commencement under <strong>DILG MC No. 2018-30</strong>.
                            </p>
                        </div>
                    </div>

                    <div style="margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                        <div style="padding:18px 20px;border:1px solid #e5e7eb;border-radius:14px;background:#ffffff;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:8px;">Coverage</div>
                            <p style="margin:0;color:#334155;font-size:14px;line-height:1.7;">
                                This module is intended for quarterly reporting on whether contractors properly informed the public before the
                                commencement of covered local government road projects.
                            </p>
                        </div>
                        <div style="padding:18px 20px;border:1px solid #e5e7eb;border-radius:14px;background:#ffffff;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:8px;">Implementation Status</div>
                            <p style="margin:0;color:#334155;font-size:14px;line-height:1.7;">
                                The specific document upload, review, and approval workflow for this circular has not been implemented yet. This page
                                now exists as its own dedicated report page.
                            </p>
                        </div>
                    </div>

                    <div style="margin-top:18px;padding:18px 20px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;">
                        <div style="font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:8px;">Next Build Step</div>
                        <p style="margin:0;color:#334155;font-size:14px;line-height:1.7;">
                            The next step is to define the exact compliance checklist, required attachments, validation stages, and access rules for
                            DILG MC No. 2018-30.
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
