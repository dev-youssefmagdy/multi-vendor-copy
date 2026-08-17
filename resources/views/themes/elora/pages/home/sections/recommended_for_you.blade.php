    {{-- ── Recommended For You ──────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-center mb-4 sm:mb-5">
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M16 10.667H27.3333C27.8638 10.667 28.3725 10.8777 28.7475 11.2528C29.1226 11.6279 29.3333 12.1366 29.3333 12.667C29.3333 13.1974 29.1226 13.7061 28.7475 14.0812C28.3725 14.4563 27.8638 14.667 27.3333 14.667H17.3333M18 14.667H20.6667C21.1971 14.667 21.7058 14.8777 22.0809 15.2528C22.456 15.6279 22.6667 16.1366 22.6667 16.667C22.6667 17.1974 22.456 17.7061 22.0809 18.0812C21.7058 18.4563 21.1971 18.667 20.6667 18.667H17.3333M19.3333 18.667C19.8638 18.667 20.3725 18.8777 20.7475 19.2528C21.1226 19.6279 21.3333 20.1366 21.3333 20.667C21.3333 21.1974 21.1226 21.7061 20.7475 22.0812C20.3725 22.4563 19.8638 22.667 19.3333 22.667H17.3333"
                            stroke="url(#paint0_linear_3_1304)" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M18 22.6669C18.5304 22.6669 19.0391 22.8776 19.4142 23.2527C19.7893 23.6278 20 24.1365 20 24.6669C20 25.1973 19.7893 25.706 19.4142 26.0811C19.0391 26.4562 18.5304 26.6669 18 26.6669H12C9.87827 26.6669 7.84344 25.8241 6.34315 24.3238C4.84286 22.8235 4 20.7886 4 18.6669V16.0002V16.2776C3.99978 14.9527 4.3286 13.6485 4.95695 12.4821C5.5853 11.3157 6.4935 10.3236 7.6 9.59491L8 9.33358C8.63822 8.91758 11.184 7.45713 15.6373 4.95224C16.0913 4.69684 16.627 4.62862 17.1305 4.76209C17.6339 4.89555 18.0655 5.22018 18.3333 5.66691C18.92 6.64558 18.7667 7.89891 17.96 8.70691L16 10.6669"
                            stroke="url(#paint1_linear_3_1304)" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_3_1304" x1="16" y1="21.4573" x2="29.4125" y2="21.3579"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                            <linearGradient id="paint1_linear_3_1304" x1="4" y1="24.452" x2="20.0955" y2="24.3739"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('Recommended For You') }}</h2>
                </div>
            </div>
            {{-- products slide --}}
            <div class="swiper product-slid-swiper">
                <div class="swiper-wrapper">
                    @forelse (($recommendedProducts->isEmpty() ? $bestSelling : $recommendedProducts) as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No recommended products yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 sm:py-8 bg-white" id="all-products-section" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between px-3 sm:px-5 py-2.5 rounded-lg mb-4 overflow-hidden"
                style="background:linear-gradient(89.62deg,#C94F1A 0.07%,#F56323 57.6%,#BA3800 100%);">
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="text-white text-xs sm:text-sm hover:underline">← {{ __('See all') }}</a>
                <span class="text-white text-xs sm:text-sm hidden sm:block">
                    {{ __('Free shipping when you purchase 1.2 kg') }}
                </span>
                <div></div>
            </div>
            {{-- infinite scroll grid --}}
            <div id="all-products-grid"
                 class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
                @forelse ($paginatedProducts as $product)
                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
                @empty
                    <p class="col-span-full text-sm text-gray-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
            {{-- sentinel / loader --}}
            <div id="all-products-sentinel" class="flex justify-center py-8">
                <svg id="all-products-spinner" class="hidden animate-spin h-7 w-7 text-orange-500"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>
        </div>
    </section>
