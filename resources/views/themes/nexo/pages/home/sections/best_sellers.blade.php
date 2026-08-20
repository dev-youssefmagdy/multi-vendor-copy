    {{-- ── Best Seller ──────────────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="hidden sm:block"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5.32422 13.7748V19.9873C5.32422 22.2623 5.32422 22.2623 7.47422 23.7123L13.3867 27.1248C14.2742 27.6373 15.7242 27.6373 16.6117 27.1248L22.5242 23.7123C24.6742 22.2623 24.6742 22.2623 24.6742 19.9873V13.7748C24.6742 11.4998 24.6742 11.4998 22.5242 10.0498L16.6117 6.6373C15.7242 6.1248 14.2742 6.1248 13.3867 6.6373L7.47422 10.0498C5.32422 11.4998 5.32422 11.4998 5.32422 13.7748Z"
                            stroke="url(#paint0_linear_23_638)" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M21.875 9.5375V6.25C21.875 3.75 20.625 2.5 18.125 2.5H11.875C9.375 2.5 8.125 3.75 8.125 6.25V9.45"
                            stroke="url(#paint1_linear_23_638)" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M15.7858 13.7377L16.4983 14.8502C16.6108 15.0252 16.8608 15.2002 17.0483 15.2502L18.3233 15.5752C19.1108 15.7752 19.3233 16.4502 18.8108 17.0752L17.9733 18.0877C17.8483 18.2502 17.7483 18.5377 17.7608 18.7377L17.8358 20.0502C17.8858 20.8627 17.3108 21.2752 16.5608 20.9752L15.3358 20.4877C15.1483 20.4127 14.8358 20.4127 14.6483 20.4877L13.4233 20.9752C12.6733 21.2752 12.0983 20.8502 12.1483 20.0502L12.2233 18.7377C12.2358 18.5377 12.1358 18.2377 12.0108 18.0877L11.1733 17.0752C10.6608 16.4502 10.8733 15.7752 11.6608 15.5752L12.9358 15.2502C13.1358 15.2002 13.3858 15.0127 13.4858 14.8502L14.1983 13.7377C14.6483 13.0627 15.3483 13.0627 15.7858 13.7377Z"
                            stroke="url(#paint2_linear_23_638)" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_23_638" x1="5.32422" y1="25.3664" x2="24.7895"
                                y2="25.2483" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                            <linearGradient id="paint1_linear_23_638" x1="8.125" y1="8.82807" x2="21.9551" y2="8.64791"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                            <linearGradient id="paint2_linear_23_638" x1="10.8945" y1="20.2826" x2="19.1382"
                                y2="20.2251" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('Best Seller') }}</h2>
                </div>
                <a href="{{ route('tenant.storefront.best-selling') }}"
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
                    @forelse ($bestSelling as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.nexo.pages._product-card', ['product' => $product, 'badge' =>
                        __('Best-Selling')])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No best sellers yet.') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    </section>
