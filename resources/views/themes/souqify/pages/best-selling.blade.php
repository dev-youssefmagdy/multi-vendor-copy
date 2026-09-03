@php
    $pageTitle = $isSearchRoute ? __('Search Results') : __('Best-Selling Items');
    $dayOptions = [30, 14, 7];
@endphp

<main class="bg-zinc-100 pt-8 pb-16 w-full">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb / heading -->
        <div class="mb-6">
            <nav class="text-xs text-neutral-500 flex items-center gap-2 mb-2">
                <a href="{{ route('tenant.home') }}" class="hover:text-blue-700">{{ __('Home') }}</a>
                <span>/</span>
                <span class="text-slate-900">{{ $pageTitle }}</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $pageTitle }}</h1>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-5 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

                {{-- Day-range pills (best-selling only) --}}
                @if (!$isSearchRoute)
                    <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar pb-1">
                        @foreach ($dayOptions as $option)
                            <button wire:click="setDays({{ $option }})"
                                class="px-4 py-2 rounded-full border text-xs sm:text-sm transition shrink-0
                                                            {{ $days === $option ? 'border-blue-700 bg-blue-700 text-white font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700 hover:text-blue-700' }}">
                                {{ __('Last :days days', ['days' => $option]) }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Keyword search --}}
                <div class="flex items-center gap-2 ml-auto">
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search items') }}"
                        class="px-4 py-2 rounded-full border border-neutral-200 text-sm bg-white focus:outline-none focus:border-blue-700 min-w-[180px]">
                    @if ($search !== '')
                        <button wire:click="$set('search', '')"
                            class="px-4 py-2 rounded-full border border-neutral-200 text-neutral-500 text-sm hover:border-red-400 hover:text-red-500 transition">
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Category pills --}}
            <div
                class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar pb-1 mt-4 pt-4 border-t border-neutral-100">
                <button wire:click="setCategory('')"
                    class="px-4 py-1.5 rounded-full border text-xs transition shrink-0
                        {{ !$currentCategoryId ? 'border-blue-700 bg-blue-50 text-blue-700 font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700' }}">
                    {{ __('All categories') }}
                </button>
                @foreach ($categories as $category)
                    <button wire:click="setCategory('{{ $category->id }}')"
                        class="px-4 py-1.5 rounded-full border text-xs transition shrink-0
                                        {{ $currentCategoryId === $category->id ? 'border-blue-700 bg-blue-50 text-blue-700 font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700' }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                @foreach ($products as $product)
                    @include('themes.souqify.pages._product-card', ['product' => $product, 'badge' => $isSearchRoute ? null : 'Best-Selling'])
                @endforeach
            </div>

            {{-- Infinite scroll sentinel --}}
            @if ($hasMore)
                <div id="bsLoadMoreSentinel" class="w-full h-4 mt-4"></div>
            @endif

            {{-- Spinner shown during Livewire updates --}}
            <div wire:loading.flex class="w-full justify-center py-8">
                <div class="w-8 h-8 border-4 border-blue-700 border-t-transparent rounded-full animate-spin"></div>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-2xl py-16 text-center text-neutral-500">
                <svg class="w-14 h-14 mx-auto mb-4 text-neutral-300" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 6.7 6.7a7.5 7.5 0 0 0 9.95 9.95Z" />
                </svg>
                <p class="text-base">{{ __('No products matched your current filters.') }}</p>
            </div>
        @endif
    </div>
</main>

@script
<script>
    (function () {
        let _observer = null;
        let _loading = false;

        function attachObserver() {
            if (_observer) { _observer.disconnect(); _observer = null; }
            const sentinel = document.getElementById('bsLoadMoreSentinel');
            if (!sentinel) return;

            _observer = new IntersectionObserver(function (entries) {
                if (!entries[0].isIntersecting || _loading) return;
                _loading = true;
                $wire.loadMore().then(function () { _loading = false; });
            }, { rootMargin: '400px' });

            _observer.observe(sentinel);
        }

        attachObserver();

        Livewire.hook('morph.updated', ({ component }) => {
            if (component.el.contains(document.getElementById('bsLoadMoreSentinel')) ||
                document.getElementById('bsLoadMoreSentinel')) {
                attachObserver();
            }
        });
    })();
</script>
@endscript