    {{-- ── Tabbed Products (Flash / Best-selling / New In / Top-rated) ──────────────────── --}}
    <style>
    /* Beats the global body[data-page="index"] .cat-pill.active (dark) rule */
    body[data-page="index"] .tabbed-pill { background: transparent; color: #FF4D00; border-color: #FFAC88; }
    body[data-page="index"] .tabbed-pill.active { background: #FF4D00 !important; color: #fff !important; border-color: #FF4D00 !important; }
</style>
<section class="py-5 sm:py-8 bg-white" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">

            {{-- Tab pills --}}
            @php $pillBase = 'flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap flex-shrink-0 justify-center sm:justify-start max-sm:w-[calc((100%-16px)/2.75)] max-sm:px-2 max-sm:gap-1 snap-start'; @endphp
            <div class="flex items-center md:justify-center gap-2 mb-4 overflow-x-auto no-scrollbar pb-1 snap-x snap-mandatory">
                <button data-tab="flash" data-see-all="{{ route('tenant.storefront.offers') }}" type="button"
                    class="cat-pill tabbed-pill active {{ $pillBase }} border border-main bg-main text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ __('Flash products') }}
                </button>

                <button data-tab="bestselling" data-see-all="{{ route('tenant.storefront.best-selling') }}" type="button"
                    class="cat-pill tabbed-pill {{ $pillBase }} border border-main-light text-main">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    {{ __('Best-selling') }}
                </button>

                <button data-tab="newin" data-see-all="{{ route('tenant.storefront.new-in') }}" type="button"
                    class="cat-pill tabbed-pill {{ $pillBase }} border border-main-light text-main">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('New in') }}
                </button>

                <button data-tab="toprated" data-see-all="{{ route('tenant.storefront.category') }}" type="button"
                    class="cat-pill tabbed-pill {{ $pillBase }} border border-main-light text-main">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    {{ __('Top-rated') }}
                </button>
            </div>

        </div>

        {{-- Single product swiper — populated server-side on first load, then via AJAX.
             Desktop: full-bleed with a 38px start gutter and none at the end (ps/pe follow LTR/RTL). --}}
        <div id="tabbed-products-swiper-wrap" class="px-3 sm:px-4 lg:ps-[38px] lg:pe-0">
                <div class="swiper product-slid-swiper" id="tabbed-products-swiper" data-per-view="6.25" data-per-view-mobile="2.5">
                    <div class="swiper-wrapper" id="tabbed-products-wrapper">
                        @forelse ($flashProducts as $product)
                        <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                            @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('Flash Sale')])
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 py-6">{{ __('No flash sale products at the moment.') }}</p>
                        @endforelse
                    </div>
                </div>
        </div>

        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            {{-- Show More --}}
            <div class="hidden sm:flex justify-center mt-6 sm:mt-10" id="tabbed-products-more">
                <button type="button" id="tabbed-products-more-btn"
                    class="flex items-center justify-center gap-2 w-full max-w-[340px] h-[50px] rounded-lg border border-main text-main text-base font-medium hover:bg-main hover:text-white transition-colors">
                    {{ __('Show More') }}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>
    </section>
