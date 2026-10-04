@if ($paginator->hasPages())
    <nav class="admin-pagination-nav" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="admin-pagination-summary">
            {{ __('Showing') }} {{ $paginator->firstItem() }} {{ __('to') }} {{ $paginator->lastItem() }} {{ __('of') }} {{ $paginator->total() }}
        </p>
        <div class="admin-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination-link admin-pagination-arrow is-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">←</span>
            @else
                <a class="admin-pagination-link admin-pagination-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">←</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="admin-pagination-link admin-pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="admin-pagination-link is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-pagination-link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="admin-pagination-link admin-pagination-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">→</a>
            @else
                <span class="admin-pagination-link admin-pagination-arrow is-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">→</span>
            @endif
        </div>
    </nav>
@endif
