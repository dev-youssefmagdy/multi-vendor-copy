{{-- ── Flash Sale Strip V2 (violet gradient, large text) ──────────────────── --}}
@if ($flashSales->isNotEmpty())
@php $firstSale = $flashSales->first(); @endphp
<section wire:ignore>
    <div class="w-full overflow-hidden">
        @if ($flashBanner)
        <div>
            <img loading="lazy" src="{{ $flashBanner }}" alt="{{ __('Flash Sale') }}"
                 class="w-full h-auto object-cover" style="max-height:180px;object-position:center;"/>
        </div>
        @endif
        <div class="py-5 sm:py-6"
             style="background:linear-gradient(89.62deg,#6B21A8 0%,#8A38F5 57.6%,#5B21B6 100%);">
            <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
                <div class="hidden sm:flex items-center justify-between gap-8 mb-5">
                    <div class="flex-1 min-w-0">
                        <h2 class="font-black text-white leading-none mb-2"
                            style="font-family:'Outfit',sans-serif;font-size:clamp(40px,6vw,96px);">
                            {{ __('Flash Sale') }}
                        </h2>
                        <p class="text-white/80 font-bold" style="font-family:'Outfit',sans-serif;font-size:clamp(18px,3vw,40px);">
                            {{ __('up to 50%') }}
                        </p>
                        @if ($firstSale->end_date)
                        <div class="flex items-center gap-2 mt-4">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                                <circle cx="20" cy="22" r="14" stroke="white" stroke-width="2"/>
                                <path d="M20 14v8l5 3" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                <path d="M15 4h10M20 4v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <div class="flex items-center gap-1 text-white font-bold text-3xl sm:text-5xl" style="font-family:'Outfit',sans-serif;">
                                <span class="countdown-box elora-fs-h">--</span>
                                <span class="countdown-sep">:</span>
                                <span class="countdown-box elora-fs-m">--</span>
                                <span class="countdown-sep">:</span>
                                <span class="countdown-box elora-fs-s" data-countdown="{{ $firstSale->end_date->timestamp }}">--</span>
                            </div>
                        </div>
                        @else
                        <p class="text-white font-bold text-2xl mt-4" style="font-family:'Outfit',sans-serif;">{{ __('Flash Sale On Now!') }}</p>
                        @endif
                        <a href="{{ route('tenant.storefront.best-selling') }}"
                           class="inline-flex items-center justify-center mt-5 px-8 font-semibold text-lg rounded-full transition-colors"
                           style="background:#1B1B1B;color:#fff;font-family:'Outfit',sans-serif;height:56px;min-width:200px;">
                            {{ __('Explore all') }}
                        </a>
                    </div>
                    <div class="flex-1 min-w-0 overflow-hidden">
                        <div class="swiper product-slid-swiper">
                            <div class="swiper-wrapper">
                                @forelse ($flashProducts as $product)
                                <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('Flash Sale')])
                                </div>
                                @empty
                                <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sm:hidden">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <h2 class="font-black text-white text-3xl leading-none" style="font-family:'Outfit',sans-serif;">{{ __('Flash Sale') }}</h2>
                            <p class="text-white/80 font-semibold text-base" style="font-family:'Outfit',sans-serif;">{{ __('up to 50%') }}</p>
                        </div>
                        @if ($firstSale->end_date)
                        <div class="flex items-center gap-1 text-white font-bold text-2xl" style="font-family:'Outfit',sans-serif;">
                            <span class="countdown-box elora-fs-h">--</span>
                            <span>:</span>
                            <span class="countdown-box elora-fs-m">--</span>
                            <span>:</span>
                            <span class="countdown-box elora-fs-s" data-countdown="{{ $firstSale->end_date->timestamp }}">--</span>
                        </div>
                        @endif
                    </div>
                    <div class="swiper product-slid-swiper">
                        <div class="swiper-wrapper">
                            @forelse ($flashProducts as $product)
                            <div class="swiper-slide w-[132px]">
                                @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('Flash Sale')])
                            </div>
                            @empty
                            <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <a href="{{ route('tenant.storefront.best-selling') }}"
                       class="inline-flex items-center justify-center mt-4 w-full font-semibold text-base rounded-full"
                       style="background:#1B1B1B;color:#fff;font-family:'Outfit',sans-serif;height:48px;">
                        {{ __('Explore all') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
