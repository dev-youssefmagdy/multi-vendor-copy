@props([
    'paginator' => null,
    'mode' => 'link',
    'target' => null,
])

@php
    $paginator ??= null;

    if ($paginator) {
        $currentPage = $paginator->currentPage();
        $lastPage = max($paginator->lastPage(), 1);
        $pageName = $paginator->getPageName();

        $basePages = collect([
            1, 2, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage - 1, $lastPage,
        ])
            ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
            ->unique()
            ->sort()
            ->values();

        $pages = collect();
        $previousPage = null;

        foreach ($basePages as $page) {
            if ($previousPage !== null && $page - $previousPage > 1) {
                $pages->push('ellipsis-'.$page);
            }
            $pages->push($page);
            $previousPage = $page;
        }

        $urlFor = function (int $page) use ($paginator, $mode) {
            if ($mode === 'link') {
                return $paginator->withQueryString()->url($page);
            }

            return $paginator->url($page);
        };
    }
@endphp

@if($paginator && $paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="Pagination Navigation" @if($mode === 'ajax') data-ajax-target="{{ $target }}" @endif>
        <div class="app-pagination-mobile">
            @if($paginator->onFirstPage())
                <span class="pagination-chip pagination-chip-nav is-disabled">Previous</span>
            @else
                <a href="{{ $urlFor($currentPage - 1) }}" rel="prev" class="pagination-chip pagination-chip-nav" @if($mode === 'ajax') data-ajax-page @endif>Previous</a>
            @endif

            <span class="pagination-chip pagination-chip-status">{{ $currentPage }} / {{ $lastPage }}</span>

            @if($paginator->hasMorePages())
                <a href="{{ $urlFor($currentPage + 1) }}" rel="next" class="pagination-chip pagination-chip-nav" @if($mode === 'ajax') data-ajax-page @endif>Next</a>
            @else
                <span class="pagination-chip pagination-chip-nav is-disabled">Next</span>
            @endif
        </div>

        <div class="app-pagination-desktop">
            @if($paginator->onFirstPage())
                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8249;</span>
            @else
                <a href="{{ $urlFor($currentPage - 1) }}" rel="prev" class="pagination-chip pagination-chip-nav" @if($mode === 'ajax') data-ajax-page @endif>&#8249;</a>
            @endif

            <div class="app-pagination-pages">
                @foreach($pages as $page)
                    @if(is_string($page))
                        <span class="pagination-chip pagination-chip-ellipsis" aria-hidden="true">&hellip;</span>
                    @elseif($page === $currentPage)
                        <span class="pagination-chip active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $urlFor($page) }}" class="pagination-chip" aria-label="Go to page {{ $page }}" @if($mode === 'ajax') data-ajax-page @endif>{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            @if($paginator->hasMorePages())
                <a href="{{ $urlFor($currentPage + 1) }}" rel="next" class="pagination-chip pagination-chip-nav" @if($mode === 'ajax') data-ajax-page @endif>&#8250;</a>
            @else
                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8250;</span>
            @endif
        </div>

        <p class="t-pagination-info">Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}</p>
    </nav>
@endif
