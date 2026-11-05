@extends('layouts.dashboard')

@section('title', $announcement->title . ' - Announcement')
@section('page-title', 'Announcement Details')

@section('content')
<div class="announcement-detail-container" style="max-width: 860px; margin: 0 auto; padding: 10px 0 40px;">
    <div style="margin-bottom: 18px;">
        <a href="{{ route('announcements.index') }}" class="btn-back" style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #002C76; text-decoration: none; padding: 6px 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px;">
            <i class="fas fa-arrow-left"></i> Back to Announcements
        </a>
    </div>

    @php
        $typeBadge = $announcement->typeBadge();
        $priorityBadge = $announcement->priorityBadge();
    @endphp

    <div class="announcement-detail-card" style="background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #002C76 0%, #0040a8 100%); padding: 28px 32px; color: #ffffff;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                <span style="background: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; text-transform: uppercase;">
                    <i class="fas {{ $typeBadge['icon'] }}"></i> {{ $typeBadge['label'] }}
                </span>
                <span style="background: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px;">
                    {{ $priorityBadge['label'] }}
                </span>
                @if($announcement->is_pinned)
                    <span style="background: #fef08a; color: #854d0e; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px;">
                        <i class="fas fa-thumbtack"></i> Pinned
                    </span>
                @endif
            </div>

            <h1 style="margin: 0; font-size: 24px; font-weight: 700; line-height: 1.4; color: #ffffff;">
                {{ $announcement->title }}
            </h1>

            <div style="display: flex; align-items: center; gap: 16px; margin-top: 14px; font-size: 12px; color: #cbd5e1; flex-wrap: wrap;">
                <span><i class="fas fa-user-edit"></i> {{ $announcement->creator ? $announcement->creator->fname . ' ' . $announcement->creator->lname : 'Administrator' }}</span>
                <span><i class="far fa-clock"></i> {{ $announcement->published_at ? $announcement->published_at->format('F d, Y h:i A') : 'Draft' }}</span>
                <span><i class="fas fa-users"></i> {{ $announcement->audienceDescription() }}</span>
                @if($announcement->expires_at)
                    <span><i class="fas fa-hourglass-end"></i> Expires {{ $announcement->expires_at->format('M d, Y') }}</span>
                @endif
            </div>
        </div>

        @if($announcement->hasDeadline())
            @php $dl = $announcement->deadlineBadge(); @endphp
            @if($dl)
                <div style="margin: 24px 32px 0; padding: 14px 20px; border-radius: 10px; background: {{ $dl['bg'] }}; color: {{ $dl['color'] }}; border: 1px solid {{ $dl['border'] }}; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="font-size: 22px;"><i class="fas {{ $dl['icon'] }}"></i></div>
                        <div>
                            <strong style="display: block; font-size: 14px;">{{ $dl['label'] }} ({{ $dl['relative'] }})</strong>
                            <span style="font-size: 12px; opacity: 0.9;">Official Deadline Cutoff: {{ $dl['formatted'] }}</span>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div style="padding: 32px; font-size: 15px; line-height: 1.8; color: #334155; white-space: pre-wrap;">
            {{ $announcement->content }}
        </div>

        @if($announcement->attachment_path)
            <div style="padding: 0 32px 28px;">
                <div style="padding: 16px 20px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="font-size: 24px; color: #002C76;"><i class="fas fa-file-alt"></i></div>
                        <div>
                            <strong style="display: block; font-size: 14px; color: #0f172a;">{{ $announcement->attachment_name ?: 'Attached Document' }}</strong>
                            <span style="font-size: 12px; color: #64748b;">Official Attached File</span>
                        </div>
                    </div>
                    <a href="{{ route('announcements.download', $announcement->id) }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 8px; background: #002C76; color: #ffffff; text-decoration: none;">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
        @endif

        @if($announcement->action_url)
            <div style="padding: 0 32px 32px;">
                <a href="{{ $announcement->action_url }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; font-size: 14px; font-weight: 600; border-radius: 8px; background: #002C76; color: #ffffff; text-decoration: none;">
                    <i class="fas fa-external-link-alt"></i> {{ $announcement->action_text ?: 'Open Link' }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

