    {{-- ── Flash Sale Strip ─────────────────────────────────────────────────────── --}}
    @if ($flashSales->isNotEmpty())
    @php
        $firstSale = $flashSales->first();
        // Same default the tenant panel assigns to flash sales saved without a banner.
        $flashBannerSrc = $flashBanner ?: asset('elora/assets/images/flash-sales-banner.png');
    @endphp
    <section  wire:ignore>
        <div class="w-full overflow-hidden">
            <div class="mb-1 sm:mb-0">
                <img loading="lazy" src="{{ $flashBannerSrc }}" alt="{{ __('Flash Sale') }}" class="block w-full h-auto object-cover"
                    style="max-height:180px;object-position:center;" />
            </div>
            <div class="py-5 sm:py-6"
                style="background:linear-gradient(89.62deg,#C94F1A,#2DCF9C 23%,#E0A340 58%,#F23F3F 72%,#BA3800 100%);">
                {{-- Header keeps the page gutter; the carousel below runs edge to edge --}}
                <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
                        {{-- Countdown timer --}}
                        <div class="flex items-center gap-1 sm:gap-1.5">
                            @if ($firstSale->end_date)
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl elora-fs-h"
                                data-countdown="{{ $firstSale->end_date->timestamp }}">--h</div>
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl elora-fs-m">
                                --m</div>
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl elora-fs-s">
                                --s</div>
                            @else
                            <span
                                class="text-white font-bold text-2xl sm:text-3xl">{{ __('Flash Sale On Now!') }}</span>
                            @endif
                        </div>
                        {{-- CTA --}}
                        <div class="flex flex-wrap items-center gap-2 sm:gap-6">
                            <p class="hidden sm:block text-white/90 text-xs sm:text-sm">
                                {{ __('Save on electronics,') }}<br />
                                {{ __('best seller + more') }}
                            </p>
                            {{-- FOR DESIGN PURPOSE: "FLASH30" is a placeholder shown until the store has an active coupon --}}
                            @php $flashCtaCode = ($flashCouponCode ?? null) ?: 'FLASH30'; @endphp
                            {{-- <button type="button" data-copy-code="{{ $flashCtaCode }}"
                                onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.copyCode)"
                                title="{{ __('Copy code') }}"
                                class="inline-flex items-center justify-center gap-1.5 bg-[#121212] text-white text-sm sm:text-xl font-normal px-4 sm:px-6 h-9 sm:h-[50px] rounded-full hover:bg-black transition-colors whitespace-nowrap">
                                <span>{{ __('Use Code:') }}</span>
                                <span class="text-[#F56323]">{{ $flashCtaCode }}</span>
                            </button> --}}
                        </div>
                    </div>
                </div>
                {{-- products slide: section gutter on mobile/tablet, edge to edge on desktop --}}
                <div class="px-3 sm:px-4 lg:px-0">
                <div class="swiper product-slid-swiper" data-per-view-mobile="2.5">
                    <div class="swiper-wrapper">
                        @forelse ($flashProducts as $product)
                        <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                            @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('Flash Sale')])
                        </div>
                        @empty
                        <p class="text-sm text-white/70 py-4 lg:px-6">{{ __('No flash sale products at the moment.') }}</p>
                        @endforelse
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>
    @endif
