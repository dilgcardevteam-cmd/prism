@extends('layouts.dashboard')

@section('title', 'Confirmation of Fund Receipt')
@section('page-title', 'Confirmation of Fund Receipt')

@section('content')
<div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
    <div>
        <h1>Confirmation of Fund Receipt - {{ $officeName }}</h1>
        <p>Uploaded NADAI documents from NADAI Management for this LGU/PLGU.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

@if (session('success'))
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Province</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $province }}</p>
        </div>
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">City/Municipality or PLGU</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $officeName }}</p>
        </div>
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Total NADAI Files</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $documents->count() }}</p>
        </div>
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Source</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">NADAI Management uploads</p>
        </div>
    </div>
</div>

<div style="background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap;">
        <div>
            <h2 style="color: #002C76; font-size: 18px; margin: 0; font-weight: 600;">Uploaded NADAI Documents</h2>
            <p style="margin: 4px 0 0; color: #6b7280; font-size: 13px;">This page adopts the uploaded NADAI records from NADAI Management. Provincial and LGU users can view the attached NADAI, and LGU users can accept the received record for their office.</p>
        </div>
        <div style="font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 999px; padding: 8px 12px;">
            Uploads are managed in NADAI Management.
        </div>
    </div>

    <div class="report-table-scroll">
        <table style="width: 100%; border-collapse: collapse; min-width: 1080px;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Project Title</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">NADAI Date</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Document</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Uploaded By</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Uploaded At</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Acceptance</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    @php
                        $uploader = $document->uploaded_by ? ($usersById[$document->uploaded_by] ?? null) : null;
                        $uploaderName = $uploader ? trim(($uploader->fname ?? '') . ' ' . ($uploader->lname ?? '')) : 'Unknown';
                        $acceptor = $document->confirmation_accepted_by ? ($usersById[$document->confirmation_accepted_by] ?? null) : null;
                        $acceptorName = $acceptor ? trim(($acceptor->fname ?? '') . ' ' . ($acceptor->lname ?? '')) : 'Unknown';
                        $isAccepted = (bool) $document->confirmation_accepted_at;
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; color: #111827; font-size: 13px;">{{ $document->project_title }}</td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">{{ $document->nadai_date?->format('M d, Y') }}</td>
                        <td style="padding: 12px; color: #111827; font-size: 13px;">
                            <div style="font-weight: 600;">{{ $document->original_filename }}</div>
                            <div style="font-size: 11px; color: #6b7280;">PDF document</div>
                        </td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px;">{{ $uploaderName !== '' ? $uploaderName : 'Unknown' }}</td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">
                            {{ $document->uploaded_at ? $document->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px;">
                            @if ($isAccepted)
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; border: 1px solid #6ee7b7; background-color: #ecfdf5; color: #047857; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                    Accepted
                                </span>
                                <div style="font-size: 11px; color: #6b7280; margin-top: 6px;">
                                    {{ $acceptorName !== '' ? $acceptorName : 'Unknown' }}<br>
                                    {{ $document->confirmation_accepted_at?->setTimezone(config('app.timezone'))->format('M d, Y h:i A') }}
                                </div>
                            @else
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; border: 1px solid #fcd34d; background-color: #fffbeb; color: #92400e; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                    Pending Acceptance
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap; justify-content: center;">
                                <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.document', ['office' => $officeName, 'docId' => $document->id]) }}" target="_blank" style="display: inline-flex; padding: 8px 12px; background-color: #2563eb; color: white; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; align-items: center; gap: 5px;">
                                    <i class="fas fa-file-pdf"></i> View
                                </a>
                                @if ($canAccept && !$isAccepted)
                                    <form method="POST" action="{{ route('reports.one-time.confirmation-of-fund-receipt.accept', ['office' => $officeName, 'docId' => $document->id]) }}" onsubmit="return confirm('Accept this uploaded NADAI document for Confirmation of Fund Receipt?');">
                                        @csrf
                                        <button type="submit" style="display: inline-flex; padding: 8px 12px; background-color: #059669; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; align-items: center; gap: 5px;">
                                            <i class="fas fa-check-circle"></i> Accept
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                            <i class="fas fa-file-circle-xmark" style="font-size: 30px; margin-bottom: 8px; display: block;"></i>
                            No NADAI documents uploaded for this LGU/PLGU yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
