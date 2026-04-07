@extends('layouts.dashboard')

@section('title', 'Local Project Monitoring Committee - Details')
@section('page-title', 'Local Project Monitoring Committee Details')

@section('content')
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
        <div>
            <h1>{{ $officeName }}</h1>
            <p>Committee documents and activities for the selected office.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('local-project-monitoring-committee.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <h2 style="color: #002C76; font-size: 18px; margin-bottom: 20px; font-weight: 600;">Office Information</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Office</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $officeName }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Province</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $province ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-top: 24px;">
        <h2 style="color: #002C76; font-size: 18px; margin-bottom: 20px; font-weight: 600;">Uploaded Documents</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Document</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Year</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Quarter</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Uploaded At</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">File</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $docLabels = [
                        'eo' => 'Executive Order',
                        'awfp' => 'Annual Work and Financial Plan',
                        'mep' => 'Monitoring and Evaluation Plan',
                        'meetings' => 'Meetings Conducted',
                        'monitoring' => 'Monitoring Conducted',
                        'training' => 'Training Conducted',
                    ];
                @endphp
                @forelse ($documents as $doc)
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; color: #111827; font-size: 14px;">
                            {{ $docLabels[$doc->doc_type] ?? $doc->doc_type }}
                        </td>
                        <td style="padding: 12px; color: #111827; font-size: 14px;">
                            {{ $doc->year ?? '—' }}
                        </td>
                        <td style="padding: 12px; color: #111827; font-size: 14px;">
                            {{ $doc->quarter ?? '—' }}
                        </td>
                        <td style="padding: 12px; color: #111827; font-size: 14px;">
                            {{ $doc->uploaded_at ? $doc->uploaded_at->format('M d, Y H:i') : '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if ($doc->file_path)
                                <a href="{{ route('local-project-monitoring-committee.document', [$officeName, $doc->id]) }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 8px 16px; background-color: #002C76; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
                                    <i class="fas fa-eye" style="margin-right: 4px;"></i> View
                                </a>
                            @else
                                <span style="color: #6b7280; font-size: 13px;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #6b7280;">
                            <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                            No documents uploaded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
