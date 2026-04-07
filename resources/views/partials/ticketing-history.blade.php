@if ($ticket->histories->isEmpty())
    <div class="ticketing-empty">No ticket history has been recorded yet.</div>
@else
    @php
        $historyToneMap = [
            'ticket_created' => 'ticketing-history-card--positive',
            'ticket_accepted_by_province' => 'ticketing-history-card--positive',
            'ticket_accepted_by_region' => 'ticketing-history-card--positive',
            'province_review_started' => 'ticketing-history-card--neutral',
            'region_review_started' => 'ticketing-history-card--neutral',
            'comment_added' => 'ticketing-history-card--neutral',
            'ticket_escalated_to_region' => 'ticketing-history-card--negative',
            'forwarded_to_central_office' => 'ticketing-history-card--negative',
            'province_resolved' => 'ticketing-history-card--positive',
            'region_resolved' => 'ticketing-history-card--positive',
            'central_office_resolved' => 'ticketing-history-card--positive',
            'ticket_closed' => 'ticketing-history-card--positive',
        ];
        $visibleHistories = $ticket->histories->take(2);
        $remainingHistories = $ticket->histories->slice(2);
    @endphp

    <div class="ticketing-history-timeline">
        @foreach ($visibleHistories as $history)
            @include('partials.ticketing-history-row', ['history' => $history, 'historyToneMap' => $historyToneMap])
        @endforeach
    </div>

    @if ($remainingHistories->isNotEmpty())
        <details class="ticketing-history-accordion">
            <summary class="ticketing-history-accordion-toggle">
                <span>Show older activity</span>
                <span class="ticketing-history-accordion-count">{{ $remainingHistories->count() }} more item{{ $remainingHistories->count() > 1 ? 's' : '' }}</span>
            </summary>

            <div class="ticketing-history-accordion-body">
                <div class="ticketing-history-timeline ticketing-history-timeline--nested">
                    @foreach ($remainingHistories as $history)
                        @include('partials.ticketing-history-row', ['history' => $history, 'historyToneMap' => $historyToneMap])
                    @endforeach
                </div>
            </div>
        </details>
    @endif
@endif
