{{--
    Link-based twin of <x-pagination> (same markup / CSS classes: .app-pagination, .pagination-chip) for plain Blade
    admin pages without Livewire (dedup plan §7). Pages are real <a href> links built by the paginator, so the
    current query string (filters) is kept when the paginator was created with ->withQueryString().
--}}
@props([
    'paginator',
])

@php
    $paginator ??= null;

    if ($paginator) {
        $currentPage = $paginator->currentPage();
        $lastPage = max($paginator->lastPage(), 1);

        $basePages = collect([1, 2, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage - 1, $lastPage])
            ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
            ->unique()
            ->sort()
            ->values();

        $pages = collect();
        $previousPage = null;
        foreach ($basePages as $page) {
            if ($previousPage !== null && $page - $previousPage > 1) {
                $pages->push('ellipsis-' . $page);
            }
            $pages->push($page);
            $previousPage = $page;
        }
    }
@endphp

@if ($paginator && $paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="app-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="pagination-chip pagination-chip-nav is-disabled">{{ __('Previous') }}</span>
            @else
                <a class="pagination-chip pagination-chip-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">{{ __('Previous') }}</a>
            @endif

            <span class="pagination-chip pagination-chip-status">{{ $currentPage }} / {{ $lastPage }}</span>

            @if ($paginator->hasMorePages())
                <a class="pagination-chip pagination-chip-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">{{ __('Next') }}</a>
            @else
                <span class="pagination-chip pagination-chip-nav is-disabled">{{ __('Next') }}</span>
            @endif
        </div>

        <div class="app-pagination-desktop">
            @if ($paginator->onFirstPage())
                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8249;</span>
            @else
                <a class="pagination-chip pagination-chip-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">&#8249;</a>
            @endif

            <div class="app-pagination-pages">
                @foreach ($pages as $page)
                    @if (is_string($page))
                        <span class="pagination-chip pagination-chip-ellipsis" aria-hidden="true">&hellip;</span>
                    @elseif ($page === $currentPage)
                        <span class="pagination-chip active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-chip" href="{{ $paginator->url($page) }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a class="pagination-chip pagination-chip-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">&#8250;</a>
            @else
                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8250;</span>
            @endif
        </div>
    </nav>
@endif
