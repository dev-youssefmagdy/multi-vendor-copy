    {{-- ── Shop by Category ────────────────────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
    <section wire:ignore class="elora-cats-section flex flex-col items-center justify-center"
        style="background:linear-gradient(89.62deg,#C94F1A 0.07%,#F56323 57.6%,#BA3800 100%);">

        {{-- Title row --}}
        <div class="flex flex-row items-center justify-center" style="gap:16px;">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M16 10.667H27.3333C27.8638 10.667 28.3725 10.8777 28.7475 11.2528C29.1226 11.6279 29.3333 12.1366 29.3333 12.667C29.3333 13.1974 29.1226 13.7061 28.7475 14.0812C28.3725 14.4563 27.8638 14.667 27.3333 14.667H17.3333M18 14.667H20.6667C21.1971 14.667 21.7058 14.8777 22.0809 15.2528C22.456 15.6279 22.6667 16.1366 22.6667 16.667C22.6667 17.1974 22.456 17.7061 22.0809 18.0812C21.7058 18.4563 21.1971 18.667 20.6667 18.667H17.3333M19.3333 18.667C19.8638 18.667 20.3725 18.8777 20.7475 19.2528C21.1226 19.6279 21.3333 20.1366 21.3333 20.667C21.3333 21.1974 21.1226 21.7061 20.7475 22.0812C20.3725 22.4563 19.8638 22.667 19.3333 22.667H17.3333"
                    stroke="#FDFDFD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path
                    d="M18 22.6669C18.5304 22.6669 19.0391 22.8776 19.4142 23.2527C19.7893 23.6278 20 24.1365 20 24.6669C20 25.1973 19.7893 25.706 19.4142 26.0811C19.0391 26.4562 18.5304 26.6669 18 26.6669H12C9.87827 26.6669 7.84344 25.8241 6.34315 24.3238C4.84286 22.8235 4 20.7886 4 18.6669V16.0002V16.2776C3.99978 14.9527 4.3286 13.6485 4.95695 12.4821C5.5853 11.3157 6.4935 10.3236 7.6 9.59491L8 9.33358C8.63822 8.91758 11.184 7.45713 15.6373 4.95224C16.0913 4.69684 16.627 4.62862 17.1305 4.76209C17.6339 4.89555 18.0655 5.22018 18.3333 5.66691C18.92 6.64558 18.7667 7.89891 17.96 8.70691L16 10.6669"
                    stroke="#FDFDFD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <h2 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:24px; line-height:30px; color:#FDFDFD; margin:0;">
                {{ __('Shop by Category') }}
            </h2>
        </div>

        {{-- Cards row --}}
        <div class="swiper categories-slide elora-cats-swiper w-full">
            <div class="swiper-wrapper" style="align-items:stretch;">
                @foreach ($categories as $cat)
                <div class="swiper-slide elora-cats-slide">
                    <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                         class="elora-cat-card">
                        {{-- Full-cover image --}}
                        @if ($cat->thumb_url ?? null)
                            <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->translationValue('name') ?? $cat->name }}" class="elora-cat-img"
                                style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;" />
                        @else
                            <div style="position:absolute; inset:0; background:linear-gradient(135deg,#f0f0f0,#ddd); display:flex; align-items:center; justify-content:center;">
                                <span style="font-size:48px;">🛍️</span>
                            </div>
                        @endif
                        {{-- Label bar pinned to bottom --}}
                        <div class="elora-cat-label">
                            <span>{{ \Str::limit($cat->translationValue('name') ?? $cat->name, 22) }}</span>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>

    </section>
    @endif
    <!-- -------- buy together -------- -->
    @if (($buyTogetherProducts ?? collect())->count() === 2)
    @php
        $btProducts = $buyTogetherProducts;
    @endphp
    <section  wire:ignore class="py-5 md:hidden bg-gradient-to-r from-[#C94F1A] via-[#F56323] to-[#BA3800] mt-6 relative">
        <h2 class="text-white font-bold text-lg mb-2 text-center">{{ __('Buy together') }}</h2>
        <div class="flex flex-col items-stretch justify-center gap-2.5 px-7 relative z-20">
            @foreach ($btProducts as $btProduct)
            @php
                $btVariant = $btProduct->variants->firstWhere('active', true) ?? $btProduct->variants->first();
                $btPricing = $btProduct->storefrontPricing($btVariant);
                $btSellPrice = (float) $btPricing['current_price'];
                $btHasDiscount = (bool) $btPricing['has_discount'];
                $btDiscountPct = $btHasDiscount ? (int) round((float) $btPricing['discount_percentage']) : 0;
                $btDisplaySell = $symbol . number_format($btSellPrice * $rate, 2);
                $btImg = $btProduct->centralProduct?->primary_image_url ?? $btProduct->primary_image_url ?? null;
                $btName = $btProduct->translationValue('name') ?? $btProduct->slug;
                $btRating = round((float) ($btProduct->average_rating ?? 0), 1);
                $btRatingCount = $btProduct->relationLoaded('rates') ? $btProduct->rates->count() : 0;
                $btUrl = route('tenant.storefront.product', $btProduct->slug);
            @endphp
            <!-- product card -->
            <a href="{{ $btUrl }}"  class="group flex items-center gap-3 bg-white rounded-xl overflow-hidden p-2.5 h-[104px] shadow-sm transition-all duration-300 ease-out hover:-translate-y-0.5 hover:shadow-md" style="text-decoration:none">
                <!-- image -->
                <div class="relative shrink-0 w-[84px] h-full rounded-lg overflow-hidden bg-gray-50">
                    @if ($btImg)
                    <img loading="lazy" src="{{ $btImg }}" alt="{{ $btName }}" class="absolute inset-0 w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                    @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-2xl rounded">🛍️</div>
                    @endif
                </div>
                <!-- info body -->
                <div class="flex flex-col justify-center gap-1 flex-1 min-w-0 leading-snug">
                    <h3 class="text-[13px] font-semibold text-gray-900 line-clamp-2">{{ $btName }}</h3>
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-bold text-gray-900">{{ $btDisplaySell }}
                            @if ($btHasDiscount && $btDiscountPct > 0)
                            <span class="text-main text-[10px] font-semibold ms-1">{{ $btDiscountPct }}{{ __('% off') }}</span>
                            @endif
                        </p>
                    </div>
                    @if (false && $btRating > 0)
                    <div class="flex gap-1 items-center">
                        <svg width="11" height="11" viewBox="0 0 6 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M2.75622 4.3367L1.29019 5.22197C1.24711 5.24324 1.20713 5.25185 1.17024 5.24781C1.13362 5.24351 1.09795 5.23085 1.06322 5.20985C1.02822 5.18831 1.00183 5.15789 0.98406 5.11858C0.96629 5.07927 0.964675 5.03632 0.979214 4.98975L1.36935 3.32986L0.0789997 2.21116C0.0426519 2.18154 0.0186893 2.14614 0.00711185 2.10494C-0.0044656 2.06375 -0.00190777 2.0243 0.0147853 1.98661C0.0314784 1.94892 0.0536909 1.91795 0.0814229 1.89372C0.109424 1.8703 0.147118 1.85441 0.194505 1.84606L1.8972 1.69744L2.56115 0.125602C2.57946 0.0811769 2.60585 0.0491368 2.64031 0.0294821C2.67477 0.00982735 2.71341 0 2.75622 0C2.79903 0 2.8378 0.00982735 2.87253 0.0294821C2.90726 0.0491368 2.93351 0.0811769 2.95128 0.125602L3.61524 1.69744L5.31753 1.84606C5.36518 1.85414 5.40301 1.87016 5.43101 1.89412C5.45901 1.91782 5.48136 1.94865 5.49805 1.98661C5.51448 2.0243 5.5169 2.06375 5.50532 2.10494C5.49375 2.14614 5.46978 2.18154 5.43344 2.21116L4.14309 3.32986L4.53322 4.98975C4.5483 5.03579 4.54682 5.0786 4.52878 5.11817C4.51074 5.15775 4.48422 5.18818 4.44922 5.20945C4.41475 5.23099 4.37908 5.24378 4.34219 5.24781C4.30558 5.25185 4.26573 5.24324 4.22265 5.22197L2.75622 4.3367Z"
                                fill="#FFE100" />
                        </svg>
                        <p class="text-gray-600 text-xs">{{ $btRating }}{{ $btRatingCount > 0 ? ' (+' . $btRatingCount . ')' : '' }}</p>
                    </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        <p
            class="text-[40px] font-extrabold tracking-[10px] absolute z-10 -rotate-90 -translate-y-1/2 top-1/2 -left-9 text-transparent [-webkit-text-stroke:1px_white]">
            20%</p>
        <p
            class="text-[40px] font-extrabold tracking-[10px] absolute z-10 -rotate-90 -translate-y-1/2 top-1/2 -right-9 text-transparent [-webkit-text-stroke:1px_white]">
            20%</p>
    </section>
    @endif
