@php
    $paginator = $paginator ?? null;
@endphp

@if ($paginator && $paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
    @endphp

    <nav class="flex items-center justify-center gap-2 mt-10 flex-wrap" aria-label="{{ __('Pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="px-5 py-2 rounded-full border border-neutral-200 text-neutral-300 text-[12px] font-semibold">
                {{ __('Previous') }}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
                class="px-5 py-2 rounded-full border border-neutral-300 text-slate-900 text-[12px] font-semibold hover:border-[color:var(--sq-primary)] hover:text-[color:var(--sq-primary)] transition">
                {{ __('Previous') }}
            </a>
        @endif

        @if ($startPage > 1)
            <a href="{{ $paginator->url(1) }}"
                class="w-9 h-9 rounded-full border border-neutral-200 text-[12px] font-semibold text-neutral-600 flex items-center justify-center hover:border-[color:var(--sq-primary)] hover:text-[color:var(--sq-primary)] transition">
                1
            </a>
            @if ($startPage > 2)
                <span class="px-1 text-neutral-400 text-[12px] font-semibold">...</span>
            @endif
        @endif

        @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $currentPage)
                <span
                    class="w-9 h-9 rounded-full border border-[color:var(--sq-primary)] bg-[color:var(--sq-primary)] text-white text-[12px] font-semibold flex items-center justify-center">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}"
                    class="w-9 h-9 rounded-full border border-neutral-200 text-[12px] font-semibold text-neutral-600 flex items-center justify-center hover:border-[color:var(--sq-primary)] hover:text-[color:var(--sq-primary)] transition">
                    {{ $page }}
                </a>
            @endif
        @endfor

        @if ($endPage < $lastPage)
            @if ($endPage < $lastPage - 1)
                <span class="px-1 text-neutral-400 text-[12px] font-semibold">...</span>
            @endif
            <a href="{{ $paginator->url($lastPage) }}"
                class="w-9 h-9 rounded-full border border-neutral-200 text-[12px] font-semibold text-neutral-600 flex items-center justify-center hover:border-[color:var(--sq-primary)] hover:text-[color:var(--sq-primary)] transition">
                {{ $lastPage }}
            </a>
        @endif

        <span class="text-[12px] font-semibold text-neutral-500 px-2">
            {{ __('Page :current of :last', ['current' => $currentPage, 'last' => $lastPage]) }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
                class="px-5 py-2 rounded-full border border-[color:var(--sq-primary)] text-[color:var(--sq-primary)] text-[12px] font-semibold hover:bg-[color:var(--sq-primary)] hover:text-white transition">
                {{ __('Next') }}
            </a>
        @else
            <span class="px-5 py-2 rounded-full border border-neutral-200 text-neutral-300 text-[12px] font-semibold">
                {{ __('Next') }}
            </span>
        @endif
    </nav>
@endif