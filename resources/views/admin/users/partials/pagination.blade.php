@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $windowStart = max(1, $currentPage - 2);
    $windowEnd = min($lastPage, $currentPage + 2);
@endphp

@if ($paginator->hasPages())
    <nav class="users-pagination" role="navigation" aria-label="Pagination Navigation">
        @if ($paginator->onFirstPage())
            <span class="users-pagination__button users-pagination__button--disabled" aria-disabled="true">Previous</span>
        @else
            <a class="users-pagination__button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        @if ($windowStart > 1)
            <a class="users-pagination__page" href="{{ $paginator->url(1) }}">1</a>
            @if ($windowStart > 2)
                <span class="users-pagination__ellipsis" aria-hidden="true">...</span>
            @endif
        @endif

        @for ($page = $windowStart; $page <= $windowEnd; $page++)
            @if ($page === $currentPage)
                <span class="users-pagination__page users-pagination__page--active" aria-current="page">{{ $page }}</span>
            @else
                <a class="users-pagination__page" href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($windowEnd < $lastPage)
            @if ($windowEnd < $lastPage - 1)
                <span class="users-pagination__ellipsis" aria-hidden="true">...</span>
            @endif
            <a class="users-pagination__page" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="users-pagination__button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="users-pagination__button users-pagination__button--disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
