    {{-- ── Flash Sale Strip ─────────────────────────────────────────────────────── --}}
    @if ($flashSales->isNotEmpty())
    @php $firstSale = $flashSales->first(); @endphp
    <section  wire:ignore>
        <div class="w-full overflow-hidden">
            @if ($flashBanner)
            <div>
                <img loading="lazy" src="{{ $flashBanner }}" alt="{{ __('Flash Sale') }}" class="w-full h-auto object-cover"
                    style="max-height:180px;object-position:center;" />
            </div>
            @endif
            <div class="py-5 sm:py-6"
                style="background:linear-gradient(89.62deg,#C94F1A,#2DCF9C 23%,#E0A340 58%,#F23F3F 72%,#BA3800 100%);">
                <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
                        {{-- Countdown timer --}}
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            @if ($firstSale->end_date)
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl nexo-fs-h"
                                data-countdown="{{ $firstSale->end_date->timestamp }}">--h</div>
                            <span class="text-white font-bold text-lg sm:text-2xl">:</span>
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl nexo-fs-m">
                                --m</div>
                            <span class="text-white font-bold text-lg sm:text-2xl">:</span>
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl nexo-fs-s">
                                --s</div>
                            @else
                            <span
                                class="text-white font-bold text-2xl sm:text-3xl">{{ __('Flash Sale On Now!') }}</span>
                            @endif
                        </div>
                        {{-- CTA --}}
                        <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                            <p class="text-white/90 text-xs sm:text-sm">
                                {{ __('Save on everything.') }}<br />
                                {{ __('Best seller + more') }}
                            </p>
                            <a href="{{ route('tenant.storefront.best-selling') }}"
                                class="bg-charcoal text-white text-xs sm:text-sm px-3 sm:px-5 py-2 rounded-full hover:bg-black transition-colors whitespace-nowrap">
                                {{ __('Shop flash deals') }}
                            </a>
                        </div>
                    </div>
                    {{-- products slide --}}
                    <div class="swiper product-slid-swiper">
                        <div class="swiper-wrapper">
                            @forelse ($flashProducts as $product)
                            <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                                @include('themes.nexo.pages._product-card', ['product' => $product, 'badge' =>
                                __('Flash
                                Sale')])
                            </div>
                            @empty
                            <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
