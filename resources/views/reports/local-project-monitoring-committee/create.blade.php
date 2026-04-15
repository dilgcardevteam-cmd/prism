@extends('layouts.dashboard')

@section('title', 'Local Project Monitoring Committee - Create')
@section('page-title', 'Create Local Project Monitoring Committee Entry')

@section('content')
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
        <div>
            <h1>Create Local Project Monitoring Committee Entry</h1>
            <p>Select an office from the records table, then open its Update page to upload documents.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('local-project-monitoring-committee.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-list"></i> Open LPMC List
            </a>
        </div>
    </div>

    <div style="background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
        <h2 style="color: #002C76; font-size: 18px; margin-bottom: 12px; font-weight: 600;">No Direct Create Form</h2>
        <p style="margin: 0; color: #4b5563; line-height: 1.6;">
            This module creates or updates entries when a document is uploaded for a specific office.
            Use the <strong>Open LPMC List</strong> button and choose an office to proceed.
        </p>
    </div>
@endsection
