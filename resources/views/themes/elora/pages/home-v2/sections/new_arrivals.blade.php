{{-- ── New In V2 ───────────────────────────────────────────────────────────── --}}
<section class="py-5 sm:py-8 bg-white" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-between mb-4 sm:mb-5">
            <div class="hidden sm:block"></div>
            <div class="flex items-center gap-2 sm:gap-3">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <path d="M10.4 1.6L9.6 2.4l10 10 .8-.8-10-10zm-2 6l-.8.4-.4.8 10 10 .8-.8-9.6-9.6zM2.4 9.6l-.8.8 10 10 .8-.8-10-10zm19.67 5.41l-1.84 5.26-5.34 1.62 4.44 3.38-.1 5.57 4.57-3.18 5.28 1.82-1.6-5.33 3.36-4.45-5.57-.12-4.6-4.57z"
                          fill="#8A38F5"/>
                </svg>
                <h2 class="text-lg sm:text-2xl font-bold" style="color:#8A38F5;font-family:'Outfit',sans-serif;">{{ __('New In') }}</h2>
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
        <div class="swiper product-slid-swiper">
            <div class="swiper-wrapper">
                @forelse ($newInProducts as $product)
                <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('New In')])
                </div>
                @empty
                <p class="text-sm text-gray-400 py-6 w-full">{{ __('No new arrivals yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
