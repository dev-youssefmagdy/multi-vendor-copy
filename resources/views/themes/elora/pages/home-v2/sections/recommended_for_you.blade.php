{{-- ── Recommended For You V2 (paginated infinite scroll) ─────────────────── --}}
<section class="py-5 sm:py-8 bg-white" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-center mb-4 sm:mb-5">
            <div class="flex items-center gap-2 sm:gap-3">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <path d="M16 10.667H27.333C27.864 10.667 28.373 10.878 28.748 11.253C29.123 11.628 29.333 12.137 29.333 12.667C29.333 13.197 29.123 13.706 28.748 14.081C28.373 14.456 27.864 14.667 27.333 14.667H17.333M18 14.667H20.667C21.197 14.667 21.706 14.878 22.081 15.253C22.456 15.628 22.667 16.137 22.667 16.667C22.667 17.197 22.456 17.706 22.081 18.081C21.706 18.456 21.197 18.667 20.667 18.667H17.333M19.333 18.667C19.864 18.667 20.373 18.878 20.748 19.253C21.123 19.628 21.333 20.137 21.333 20.667C21.333 21.197 21.123 21.706 20.748 22.081C20.373 22.456 19.864 22.667 19.333 22.667H17.333"
                          stroke="#8A38F5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18 22.667C18.53 22.667 19.039 22.878 19.414 23.253C19.789 23.628 20 24.137 20 24.667C20 25.197 19.789 25.706 19.414 26.081C19.039 26.456 18.53 26.667 18 26.667H12C9.878 26.667 7.843 25.824 6.343 24.324C4.843 22.824 4 20.789 4 18.667V16V16.278C3.999 14.953 4.329 13.649 4.957 12.482C5.585 11.316 6.494 10.324 7.6 9.595L8 9.334C8.638 8.918 11.184 7.457 15.637 4.952C16.091 4.697 16.627 4.629 17.131 4.762C17.634 4.896 18.066 5.22 18.333 5.667C18.92 6.646 18.767 7.899 17.96 8.707L16 10.667"
                          stroke="#8A38F5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h2 class="text-lg sm:text-2xl font-bold" style="color:#8A38F5;font-family:'Outfit',sans-serif;">{{ __('Recommended For You') }}</h2>
            </div>
        </div>
        <div class="swiper product-slid-swiper">
            <div class="swiper-wrapper">
                @forelse (($recommendedProducts->isEmpty() ? $bestSelling : $recommendedProducts) as $product)
                <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
                </div>
                @empty
                <p class="text-sm text-gray-400 py-6 w-full">{{ __('No recommended products yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- Infinite-scroll all products --}}
<section class="py-5 sm:py-8 bg-white" id="all-products-section" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-between px-3 sm:px-5 py-2.5 rounded-lg mb-4 overflow-hidden"
             style="background:linear-gradient(89.62deg,#6B21A8 0.07%,#8A38F5 57.6%,#5B21B6 100%);">
            <a href="{{ route('tenant.storefront.best-selling') }}"
               class="text-white text-xs sm:text-sm hover:underline">← {{ __('See all') }}</a>
            <span class="text-white text-xs sm:text-sm hidden sm:block">
                {{ __('Free shipping when you purchase 1.2 kg') }}
            </span>
            <div></div>
        </div>
        <div id="all-products-grid"
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
            @forelse ($paginatedProducts as $product)
                @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
            @empty
                <p class="col-span-full text-sm text-gray-400 py-6">{{ __('No products yet.') }}</p>
            @endforelse
        </div>
        <div id="all-products-sentinel" class="flex justify-center py-8">
            <svg id="all-products-spinner" class="hidden animate-spin h-7 w-7"
                 style="color:#8A38F5;"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </div>
    </div>
</section>
