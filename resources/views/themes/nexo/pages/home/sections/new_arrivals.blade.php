    {{-- ── New Arrivals ─────────────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="hidden sm:block"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.4016 1.60254L9.60156 2.39754L19.6016 12.4L20.4016 11.6L10.4016 1.60254ZM8.40156 7.60004L8.00156 8.00004L7.60156 8.40004L17.6016 18.4L18.4016 17.6L8.40156 7.60004ZM2.39656 9.60004L1.60156 10.4L11.6016 20.4L12.4016 19.6L2.39656 9.60004ZM22.0703 15.0125L20.2328 20.2688L14.8953 21.8938L19.3328 25.2688L19.2266 30.8438L23.8016 27.6688L29.0766 29.4875L27.4703 24.1563L30.8328 19.7063L25.2641 19.5813L22.0703 15.0125Z"
                            fill="url(#paint0_linear_3_1254)" />
                        <defs>
                            <linearGradient id="paint0_linear_3_1254" x1="1.60156" y1="27.8961" x2="31.0068"
                                y2="27.7001" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('New Arrivals') }}</h2>
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
            <div class="swiper product-slid-swiper">
                <div class="swiper-wrapper">
                    @forelse ($newInProducts as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.nexo.pages._product-card', ['product' => $product, 'badge' => __('New In')])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No new arrivals yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
