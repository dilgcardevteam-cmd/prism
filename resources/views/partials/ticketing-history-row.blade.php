@php
    $toneClass = $historyToneMap[$history->action] ?? 'ticketing-history-card--neutral';
    $timestamp = $history->created_at;
    $transitionSummary = collect([
        ($history->from_status || $history->to_status)
            ? 'Status: ' . ($history->from_status ?? 'None') . ' -> ' . ($history->to_status ?? 'None')
            : null,
        ($history->from_level || $history->to_level)
            ? 'Level: '
                . ($history->from_level ? (\App\Models\Ticket::levelLabels()[$history->from_level] ?? \Illuminate\Support\Str::headline($history->from_level)) : 'None')
                . ' -> '
                . ($history->to_level ? (\App\Models\Ticket::levelLabels()[$history->to_level] ?? \Illuminate\Support\Str::headline($history->to_level)) : 'None')
            : null,
    ])->filter()->implode(' • ');
@endphp

<div class="ticketing-history-row">
    <div class="ticketing-history-date">
        <div class="ticketing-history-day">{{ optional($timestamp)->format('d') }}</div>
        <div class="ticketing-history-month">{{ optional($timestamp)->format('M') }}</div>
        <div class="ticketing-history-clock">{{ optional($timestamp)->format('h:i A') }}</div>
    </div>
    <div class="ticketing-history-marker-wrap">
        <span class="ticketing-history-marker {{ $toneClass }}"></span>
    </div>
    <div class="ticketing-history-card {{ $toneClass }}">
        <h4 class="ticketing-history-title">{{ $history->description }}</h4>
        <div class="ticketing-history-meta">
            <span class="ticketing-history-actor">
                <i class="fas fa-user"></i>
                {{ $history->actor?->fullName() ?? 'System' }}
            </span>
            <span class="ticketing-history-chip">
                {{ \Illuminate\Support\Str::headline($history->action) }}
            </span>
        </div>

        @if ($transitionSummary !== '')
            <div class="ticketing-history-summary">{{ $transitionSummary }}</div>
        @endif

        @if (!empty($history->metadata))
            <div class="ticketing-history-metadata">
                @foreach ($history->metadata as $metaKey => $metaValue)
                    @if ($metaValue !== null && $metaValue !== '')
                        <div class="ticketing-history-metadata-item">
                            <strong>{{ \Illuminate\Support\Str::headline((string) $metaKey) }}:</strong>
                            <span>{{ is_scalar($metaValue) ? $metaValue : json_encode($metaValue) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
