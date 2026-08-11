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
            <span class="px-5 py-2 rounded-full border border-[#e5e5e5] text-[#b0b0b0] text-[12px] font-semibold">
                {{ __('Previous') }}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
                class="px-5 py-2 rounded-full border border-[#d7d7d7] text-[#242424] text-[12px] font-semibold hover:border-[#242424] transition">
                {{ __('Previous') }}
            </a>
        @endif

        @if ($startPage > 1)
            <a href="{{ $paginator->url(1) }}"
                class="w-9 h-9 rounded-full border border-[#e5e5e5] text-[12px] font-semibold text-[#555] flex items-center justify-center hover:border-[#242424] transition">
                1
            </a>
            @if ($startPage > 2)
                <span class="px-1 text-[#999] text-[12px] font-semibold">...</span>
            @endif
        @endif

        @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $currentPage)
                <span
                    class="w-9 h-9 rounded-full border border-[#242424] bg-[#242424] text-white text-[12px] font-semibold flex items-center justify-center">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}"
                    class="w-9 h-9 rounded-full border border-[#e5e5e5] text-[12px] font-semibold text-[#555] flex items-center justify-center hover:border-[#242424] transition">
                    {{ $page }}
                </a>
            @endif
        @endfor

        @if ($endPage < $lastPage)
            @if ($endPage < $lastPage - 1)
                <span class="px-1 text-[#999] text-[12px] font-semibold">...</span>
            @endif
            <a href="{{ $paginator->url($lastPage) }}"
                class="w-9 h-9 rounded-full border border-[#e5e5e5] text-[12px] font-semibold text-[#555] flex items-center justify-center hover:border-[#242424] transition">
                {{ $lastPage }}
            </a>
        @endif

        <span
            class="text-[12px] font-semibold text-[#666] px-2">{{ __('Page :current of :last', ['current' => $currentPage, 'last' => $lastPage]) }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
                class="px-5 py-2 rounded-full border border-[#242424] text-[#242424] text-[12px] font-semibold hover:bg-[#242424] hover:text-white transition">
                {{ __('Next') }}
            </a>
        @else
            <span class="px-5 py-2 rounded-full border border-[#e5e5e5] text-[#b0b0b0] text-[12px] font-semibold">
                {{ __('Next') }}
            </span>
        @endif
    </nav>
@endif
