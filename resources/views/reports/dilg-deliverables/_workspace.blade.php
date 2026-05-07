@php
    $workspaceLinks = $workspaceLinks ?? [];
    $cards = $cards ?? [];
    $heroIcon = $heroIcon ?? 'fas fa-clipboard-list';
@endphp

<div class="content-header">
    <h1>{{ $pageTitle }}</h1>
    <p>{{ $pageIntro }}</p>
</div>

<section class="dilg-deliverables-shell">
    <div class="dilg-deliverables-hero">
        <div class="dilg-deliverables-icon" aria-hidden="true">
            <i class="{{ $heroIcon }}"></i>
        </div>
        <div>
            <h2>{{ $heroTitle }}</h2>
            <p>{{ $heroCopy }}</p>
        </div>
    </div>

    <div class="dilg-deliverables-grid">
        @foreach ($cards as $card)
            <article class="dilg-deliverables-card">
                <h3>{{ $card['title'] }}</h3>
                <p>{{ $card['copy'] }}</p>
                @if (!empty($card['href']) && !empty($card['cta']))
                    <a href="{{ $card['href'] }}" class="dilg-deliverables-card-link">
                        {{ $card['cta'] }}
                        <i class="fas fa-arrow-right"></i>
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>

<style>
    .dilg-deliverables-shell {
        background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
        border: 1px solid #bfdbfe;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .dilg-deliverables-hero {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    .dilg-deliverables-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 22px;
        flex: 0 0 auto;
    }

    .dilg-deliverables-hero h2 {
        margin: 0;
        color: #1e3a8a;
        font-size: 22px;
    }

    .dilg-deliverables-hero p {
        margin: 8px 0 0;
        color: #475569;
        font-size: 14px;
        line-height: 1.7;
        max-width: 760px;
    }

    .dilg-deliverables-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
    }

    .dilg-deliverables-card {
        display: grid;
        gap: 12px;
        background: #ffffff;
        border: 1px solid #dbeafe;
        border-radius: 14px;
        padding: 16px;
    }

    .dilg-deliverables-card h3 {
        margin: 0;
        color: #1e40af;
        font-size: 15px;
    }

    .dilg-deliverables-card p {
        margin: 0;
        color: #475569;
        font-size: 13px;
        line-height: 1.6;
    }

    .dilg-deliverables-card-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #1d4ed8;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
    }

    @media (max-width: 640px) {
        .dilg-deliverables-shell {
            padding: 16px;
        }

        .dilg-deliverables-hero {
            flex-direction: column;
        }
    }
</style>
