@php
    $banners = $banners ?? collect();
    $megaSaleProducts = $megaSaleProducts ?? collect();
    $superSaleProducts = $superSaleProducts ?? collect();
    $seasonSaleProducts = $seasonSaleProducts ?? collect();
    $saleEndsAtIso = $saleEndsAtIso ?? null;

    // One definition per section; the three product lists are already disjoint (see OffersPage).
    $sections = [
        ['key' => 'mega', 'title' => __('Mega Sale'), 'icon' => '🔥', 'products' => $megaSaleProducts],
        ['key' => 'super', 'title' => __('Super Sale'), 'icon' => '⚡', 'products' => $superSaleProducts],
        ['key' => 'season', 'title' => __('Season Sale'), 'icon' => '🎁', 'products' => $seasonSaleProducts],
    ];

    // Real discount of a product (0 when none): drives the "up to X%" figures and the card tag.
    $discountOf = function ($product) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);

        return $pricing['has_discount'] ? (int) round((float) $pricing['discount_percentage']) : 0;
    };

    // Tag shown on the card: the product's own badge, otherwise its real flash-sale discount.
    $tagFor = function ($product) use ($discountOf) {
        $badge = $product->badges->where('active', true)->first()?->text;
        if (filled($badge)) {
            return $badge;
        }
        $pct = $discountOf($product);

        return $pct > 0 ? $pct . '% ' . __('OFF') : __('Flash Sale');
    };

    $allProducts = $megaSaleProducts->concat($superSaleProducts)->concat($seasonSaleProducts);
    $maxDiscount = $allProducts->map($discountOf)->max() ?? 0;

    // Banners: first row of up to three, the rest sit between the sections.
    $topBanners = $banners->take(3);
    $midBanners = $banners->skip(3)->values();
@endphp

<main class="bg-[#F6F7F9] w-full" style="font-family: 'Outfit', sans-serif;">
    @unless (in_array($storefrontThemeVariant?->key, ['v2', 'v3', 'v4', 'v5', 'v6'], true))
        @include('themes.souqify.partials.mobile-home-header', ['tight' => true])
    @endunless

    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-4 lg:pt-8 pb-14 lg:pb-20 flex flex-col gap-8 lg:gap-12">

        {{-- ===== HERO: title, real max discount, live countdown ===== --}}
        <header class="relative overflow-hidden rounded-3xl text-white bg-gradient-to-br from-[#001537] via-[#0A2A66] to-[#0159ED] px-5 py-6 sm:px-8 lg:px-12 lg:py-10">
            <div class="absolute -top-16 -end-10 w-56 h-56 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div class="min-w-0">
                    <p class="text-xs lg:text-sm font-medium tracking-wide text-white/70">{{ __('Exclusive deals & seasonal savings') }}</p>
                    <h1 class="mt-1 text-3xl lg:text-5xl font-semibold leading-tight">{{ __('Offers') }}</h1>
                    @if ($maxDiscount > 0)
                        <p class="mt-2 text-base lg:text-xl text-white/90">{{ __('up to :pct%', ['pct' => $maxDiscount]) }} <span class="text-white/70">{{ __('OFF') }}</span></p>
                    @endif
                </div>

                @if ($saleEndsAtIso)
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 backdrop-blur px-4 py-3 self-start lg:self-auto">
                        <span class="text-sm text-white/80">{{ __('Flash sale ends in') }}</span>
                        <span class="rounded-xl bg-white text-[#001537] font-semibold tabular-nums text-lg lg:text-2xl px-3 py-1"
                            data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                    </div>
                @endif
            </div>
        </header>

        {{-- ===== BANNERS (only when the store has some) ===== --}}
        @if ($topBanners->isNotEmpty())
            <div class="flex lg:grid lg:grid-cols-{{ min(3, $topBanners->count()) }} gap-3 lg:gap-5 overflow-x-auto lg:overflow-visible snap-x snap-mandatory no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach ($topBanners as $banner)
                    <a href="{{ $banner->url ?? '#' }}" class="snap-start shrink-0 w-[88%] sm:w-[60%] lg:w-auto block overflow-hidden rounded-2xl bg-neutral-200">
                        <img loading="lazy" src="{{ $banner->image_path }}"
                            alt="{{ $banner->translationValue('title') ?? __('Offer') }}"
                            class="w-full aspect-[16/7] object-cover transition-transform duration-500 hover:scale-105">
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ===== PRODUCT SECTIONS ===== --}}
        @php $rendered = 0; @endphp
        @foreach ($sections as $section)
            @continue ($section['products']->isEmpty())
            @php
                $rendered++;
                $sectionMax = $section['products']->map($discountOf)->max() ?? 0;
                $midBanner = $midBanners->get($rendered - 1);
            @endphp

            <section aria-labelledby="offers-{{ $section['key'] }}">
                <div class="flex items-end justify-between gap-3 mb-4 lg:mb-6">
                    <div class="min-w-0">
                        <h2 id="offers-{{ $section['key'] }}" class="text-xl lg:text-3xl font-semibold text-[#001537] flex items-center gap-2">
                            <span aria-hidden="true">{{ $section['icon'] }}</span>{{ $section['title'] }}
                        </h2>
                        @if ($sectionMax > 0)
                            <p class="mt-1 text-sm text-neutral-500">{{ __('up to :pct%', ['pct' => $sectionMax]) }} {{ __('OFF') }}</p>
                        @endif
                    </div>
                    <a href="{{ route('tenant.storefront.category', ['on_sale' => 'flash_sale']) }}"
                        class="shrink-0 text-sm lg:text-base font-medium text-[color:var(--sq-primary)] hover:underline inline-flex items-center gap-1">
                        {{ __('view all') }}
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="flex lg:grid lg:grid-cols-4 xl:grid-cols-5 gap-3 lg:gap-5 overflow-x-auto lg:overflow-visible snap-x no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0 pb-2">
                    @foreach ($section['products']->take(10) as $product)
                        <div class="snap-start shrink-0 w-[62%] sm:w-[260px] lg:w-auto" wire:key="offer-{{ $section['key'] }}-{{ $product->id }}">
                            @include('themes.souqify.pages._product-card', ['product' => $product, 'badge' => $tagFor($product)])
                        </div>
                    @endforeach
                </div>

                @if ($midBanner && $midBanner->image_path)
                    <a href="{{ $midBanner->url ?? '#' }}" class="mt-6 lg:mt-10 block overflow-hidden rounded-2xl bg-neutral-200">
                        <img loading="lazy" src="{{ $midBanner->image_path }}"
                            alt="{{ $midBanner->translationValue('title') ?? __('Offer') }}"
                            class="w-full aspect-[16/5] lg:aspect-[16/3] object-cover">
                    </a>
                @endif
            </section>
        @endforeach

        {{-- ===== NOTHING ON OFFER ===== --}}
        @if ($rendered === 0)
            <div class="rounded-3xl bg-white border border-neutral-200 py-16 px-6 text-center">
                <div class="text-5xl mb-3" aria-hidden="true">🏷️</div>
                <h2 class="text-xl font-semibold text-[#001537]">{{ __('No offers available right now.') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}"
                    class="mt-5 inline-block rounded-full bg-[color:var(--sq-primary)] hover:bg-[color:var(--sq-primary-hover)] text-white text-sm font-semibold px-7 py-3 transition">
                    {{ __('Start Shopping') }}
                </a>
            </div>
        @endif
    </div>
</main>

@once
    <script>
        (function() {
            if (window.__sqOfferCountdownInit) return;
            window.__sqOfferCountdownInit = true;

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function tick() {
                var nodes = document.querySelectorAll('[data-sq-countdown]');
                var now = Date.now();

                nodes.forEach(function(el) {
                    var end = Date.parse(el.getAttribute('data-end'));
                    if (isNaN(end)) return;

                    var remaining = Math.max(0, end - now);
                    var totalSeconds = Math.floor(remaining / 1000);
                    var hours = Math.floor(totalSeconds / 3600);
                    var minutes = Math.floor((totalSeconds % 3600) / 60);
                    var seconds = totalSeconds % 60;

                    var text = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
                    var emoji = el.getAttribute('data-emoji');
                    el.textContent = emoji ? (emoji + ' ' + text) : text;

                    if (remaining <= 0) {
                        el.style.display = 'none';
                    } else {
                        el.style.display = '';
                    }
                });
            }

            function init() {
                tick();
                if (window.__sqOfferCountdownTimer) clearInterval(window.__sqOfferCountdownTimer);
                window.__sqOfferCountdownTimer = setInterval(tick, 1000);
            }

            document.addEventListener('DOMContentLoaded', init);
            document.addEventListener('livewire:navigated', init);
            init();
        })
        ();
    </script>
@endonce
