{{-- ── Best Seller V2 ──────────────────────────────────────────────────────── --}}
<section class="py-5 sm:py-8 bg-white" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-between mb-4 sm:mb-5">
            <div class="hidden sm:block"></div>
            <div class="flex items-center gap-2 sm:gap-3">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none">
                    <path d="M5.32 13.77V19.99C5.32 22.26 5.32 22.26 7.47 23.71L13.39 27.12C14.27 27.64 15.72 27.64 16.61 27.12L22.52 23.71C24.67 22.26 24.67 22.26 24.67 19.99V13.77C24.67 11.5 24.67 11.5 22.52 10.05L16.61 6.64C15.72 6.12 14.27 6.12 13.39 6.64L7.47 10.05C5.32 11.5 5.32 11.5 5.32 13.77Z"
                          stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21.88 9.54V6.25C21.88 3.75 20.63 2.5 18.13 2.5H11.88C9.38 2.5 8.13 3.75 8.13 6.25V9.45"
                          stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15.79 13.74L16.5 14.85C16.61 15.03 16.86 15.2 17.05 15.25L18.32 15.58C19.11 15.78 19.32 16.45 18.81 17.08L17.97 18.09C17.85 18.25 17.75 18.54 17.76 18.74L17.84 20.05C17.89 20.86 17.31 21.28 16.56 20.98L15.34 20.49C15.15 20.41 14.84 20.41 14.65 20.49L13.42 20.98C12.67 21.28 12.1 20.85 12.15 20.05L12.22 18.74C12.24 18.54 12.14 18.24 12.01 18.09L11.17 17.08C10.66 16.45 10.87 15.78 11.66 15.58L12.94 15.25C13.14 15.2 13.39 15.01 13.49 14.85L14.2 13.74C14.65 13.06 15.35 13.06 15.79 13.74Z"
                          stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h2 class="text-lg sm:text-2xl font-bold" style="color:#8A38F5;font-family:'Outfit',sans-serif;">{{ __('Best Seller') }}</h2>
            </div>
            <a href="{{ route('tenant.storefront.best-selling') }}"
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
                @forelse ($bestSelling as $product)
                <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
                </div>
                @empty
                <p class="text-sm text-gray-400 py-6 w-full">{{ __('No best sellers yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
