{{-- ── Trending Now V2 ─────────────────────────────────────────────────────── --}}
<section class="py-5 sm:py-8 bg-white" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-between mb-4 sm:mb-5">
            <div class="hidden sm:block"></div>
            <div class="flex items-center gap-2 sm:gap-3">
                <svg width="51" height="30" viewBox="0 0 51 30" fill="none">
                    <path d="M31.31 2.74L31.55 8.32l7.58-.32L26.85 19.18l-10.18-10.23L0 23.87 5.89 30l11.24-10.41 10.14 9.51 16.49-16.88.003 8.22L51 20.13 50.85 0l-19.54 2.74z"
                          fill="#8A38F5"/>
                </svg>
                <h2 class="text-lg sm:text-2xl font-bold" style="color:#8A38F5;font-family:'Outfit',sans-serif;">{{ __('Trending Now') }}</h2>
            </div>
            <a href="{{ route('tenant.storefront.new-in') }}"
               class="flex items-center gap-1.5 text-xs sm:text-sm font-medium hover:underline" style="color:#8A38F5;">
                {{ __('See all') }}
                <svg width="5" height="12" viewBox="0 0 7 14" fill="none">
                    <path d="M0.75 0.75L5.34317 5.68939C5.88561 6.27273 5.88561 7.22727 5.34317 7.81061L0.75 12.75"
                          stroke="#8A38F5" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
        <div class="swiper product-slid-swiper" wire:ignore>
            <div class="swiper-wrapper">
                @forelse ($trendingNowProducts as $product)
                <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('New In')])
                </div>
                @empty
                <p class="text-sm text-gray-400 py-6 w-full">{{ __('No trending products yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
