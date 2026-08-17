    {{-- ── Trending Now ─────────────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="hidden sm:block"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="51" height="30" viewBox="0 0 51 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M31.3102 2.73516L31.5496 8.3245L39.1349 8.0053L26.8475 19.1788L16.6723 8.95057L0 23.8739L5.88802 30L17.1278 19.5878L27.2723 29.1001L43.7628 12.2196L43.7658 20.4386L51 20.1333L50.8473 0L31.3102 2.73516Z"
                            fill="url(#paint0_linear_3_1149)" />
                        <defs>
                            <linearGradient id="paint0_linear_3_1149" x1="-1.10226e-08" y1="26.9758" x2="51.2992"
                                y2="26.3943" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('Trending Now') }}</h2>
                </div>
                <a href="{{ route('tenant.storefront.new-in') }}"
                    class="flex items-center gap-1.5 text-xs sm:text-sm text-main hover:underline font-medium">
                    {{ __('See all') }}
                    <svg width="5" height="12" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.75 0.75L5.34317 5.68939C5.88561 6.27273 5.88561 7.22727 5.34317 7.81061L0.75 12.75"
                            stroke="url(#paint0_linear_13_11426)" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_13_11426" x1="0.75" y1="1.95968" x2="5.77994" y2="1.97365"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>
                </a>
            </div>
            {{-- products slide --}}
            <div class="swiper product-slid-swiper" wire:ignore>
                <div class="swiper-wrapper">
                    @forelse ($trendingNowProducts as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('New In')])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No trending products yet.') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    </section>
