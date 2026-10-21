@extends('layouts.dashboard')

@section('title', 'FUR Upload Batches')
@section('page-title', 'FUR Upload Batches')

@section('styles')
<style>
    .fur-review-page { color: #0f172a; }
    .fur-review-hero, .fur-review-panel {
        background: #fff;
        border: 1px solid #dbe5f1;
        border-radius: 16px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }
    .fur-review-hero {
        padding: 24px;
        margin-bottom: 18px;
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 72%);
    }
    .fur-review-eyebrow { color:#2563eb; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .fur-review-title { margin:6px 0; font-size:26px; font-weight:800; color:#0b1f52; }
    .fur-review-copy { margin:0; color:#64748b; font-size:13px; }
    .fur-review-panel { overflow:hidden; }
    .fur-review-table { width:100%; border-collapse:collapse; }
    .fur-review-table th { background:#f8fafc; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:.04em; text-align:left; }
    .fur-review-table th, .fur-review-table td { padding:14px 16px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
    .fur-review-table td { font-size:13px; color:#334155; }
    .fur-review-batch-number { color:#1d4ed8; font-weight:800; text-decoration:none; }
    .fur-review-batch-number:hover { text-decoration:underline; }
    .fur-review-badge { display:inline-flex; padding:4px 9px; border-radius:999px; font-size:11px; font-weight:700; background:#dbeafe; color:#1d4ed8; }
    .fur-review-empty { padding:56px 20px; text-align:center; color:#64748b; }
    .fur-review-empty i { color:#93c5fd; font-size:36px; margin-bottom:10px; }
    @media (max-width: 760px) { .fur-review-table { min-width:720px; } .fur-review-panel { overflow-x:auto; } }
</style>
@endsection

@section('content')
<div class="fur-review-page">
    <section class="fur-review-hero">
        <div class="fur-review-eyebrow"><i class="fas fa-layer-group"></i> Batch Upload Workspace</div>
        <h1 class="fur-review-title">FUR Upload Batches</h1>
        <p class="fur-review-copy">Open a batch to review its projects, uploaded document items, and validator selection checklist.</p>
    </section>

    <section class="fur-review-panel">
        @if($batches->isNotEmpty())
            <table class="fur-review-table">
                <thead><tr><th>Batch Number</th><th>Quarter</th><th>Submitted</th><th>Projects</th><th>Items</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($batches as $batch)
                        <tr>
                            <td><a class="fur-review-batch-number" href="{{ route('fund-utilization.upload-batches.show', $batch->id) }}">{{ $batch->batch_number }}</a></td>
                            <td>{{ $batch->quarter }}</td>
                            <td>{{ optional($batch->submitted_at)->format('M d, Y h:i A') ?: '—' }}</td>
                            <td>{{ $batch->project_count }}</td>
                            <td>{{ $batch->item_count }}</td>
                            <td><span class="fur-review-badge">{{ str_replace('_', ' ', ucfirst($batch->status)) }}</span></td>
                            <td><a class="task-action-btn" href="{{ route('fund-utilization.upload-batches.show', $batch->id) }}">Open <i class="fas fa-arrow-right"></i></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:16px;">{{ $batches->links() }}</div>
        @else
            <div class="fur-review-empty"><i class="fas fa-inbox"></i><div><strong>No batch uploads yet</strong></div><div>Submitted FUR batches will appear here for validator review.</div></div>
        @endif
    </section>
</div>
@endsection
