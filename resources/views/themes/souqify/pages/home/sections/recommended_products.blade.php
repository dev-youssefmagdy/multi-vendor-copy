    <!-- =========== RECOMMENDED PRODUCTS (infinite scroll) =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12 lg:pb-16" id="sqRecommendedSection">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Recommended Products') }}
            </h2>
            <a href="{{ route('tenant.storefront.category') }}?section=recommended"
                class="flex items-center gap-1 text-slate-900 hover:text-[var(--sq-primary)] transition text-sm sm:text-base">
                {{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

        {{-- Badge-first list (interaction-based fallback) — next page loads when the sentinel scrolls into view --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-6" id="sqRecommendedGrid">
            @forelse (($recommendedProducts ?? collect()) as $product)
                <div wire:key="recommended-{{ $product->id }}">
                    @include('themes.souqify.pages._product-card', [])
                </div>
            @empty
                <p class="text-sm text-slate-400 py-6 col-span-full">{{ __('No recommended products yet.') }}</p>
            @endforelse
        </div>

        @if ($hasMoreRecommended ?? false)
            <div wire:intersect="loadMoreRecommended" class="flex items-center justify-center py-6">
                <div wire:loading wire:target="loadMoreRecommended"
                    class="w-8 h-8 border-4 border-[var(--sq-primary)] border-t-transparent rounded-full animate-spin"></div>
            </div>
        @endif
    </section>
