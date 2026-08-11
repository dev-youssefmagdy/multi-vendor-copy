@props([
    'paginator',
])

@php
    $paginator ??= null;

    if ($paginator) {
        $currentPage = $paginator->currentPage();
        $lastPage = max($paginator->lastPage(), 1);
        $pageName = $paginator->getPageName();

        $basePages = collect([
            1,
            2,
            $currentPage - 1,
            $currentPage,
            $currentPage + 1,
            $lastPage - 1,
            $lastPage,
        ])
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
    <nav class="app-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="app-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="pagination-chip pagination-chip-nav is-disabled">Previous</span>
            @else
                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" aria-label="{{ __('pagination.previous') }}">
                    Previous
                </button>
            @endif

            <span class="pagination-chip pagination-chip-status">
                {{ $currentPage }} / {{ $lastPage }}
            </span>

            @if ($paginator->hasMorePages())
                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" aria-label="{{ __('pagination.next') }}">
                    Next
                </button>
            @else
                <span class="pagination-chip pagination-chip-nav is-disabled">Next</span>
            @endif
        </div>

        <div class="app-pagination-desktop">
            @if ($paginator->onFirstPage())
                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8249;</span>
            @else
                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" aria-label="{{ __('pagination.previous') }}">
                    &#8249;
                </button>
            @endif

            <div class="app-pagination-pages">
                @foreach ($pages as $page)
                    @if (is_string($page))
                        <span class="pagination-chip pagination-chip-ellipsis" aria-hidden="true">&hellip;</span>
                    @elseif ($page === $currentPage)
                        <span class="pagination-chip active" aria-current="page">{{ $page }}</span>
                    @else
                        <button type="button" class="pagination-chip" wire:key="paginator-{{ $pageName }}-page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" wire:loading.attr="disabled" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" aria-label="{{ __('pagination.next') }}">
                    &#8250;
                </button>
            @else
                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8250;</span>
            @endif
        </div>
    </nav>
@endif
