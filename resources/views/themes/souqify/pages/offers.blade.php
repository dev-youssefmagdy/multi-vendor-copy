@php
    $banners = $banners ?? collect();
    $flashSales = $flashSales ?? collect();
    $flashBanner = $flashBanner ?? null;
    $megaSaleProducts = $megaSaleProducts ?? collect();
    $seasonSaleProducts = $seasonSaleProducts ?? collect();
    $superSaleProducts = $superSaleProducts ?? collect();

    // Banner slices
    $carouselBanners = $banners->take(3);
    $heroBanner = $banners->skip(3)->first();

    // Mid-banners
    $midBanner1Url = $flashBanner ?? ($banners->skip(4)->first()?->image_path ?? $banners->first()?->image_path);

    $midBanner2Url =
        $banners->skip(5)->first()?->image_path ??
        ($banners->skip(1)->first()?->image_path ?? $banners->first()?->image_path);

    // Real countdown target (null => no active timed sale, so the timer is hidden
    // instead of showing a clock that never actually counts down).
    $saleEndsAtIso = $saleEndsAtIso ?? null;

    // Currency
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);

    // Real max discount among the season sale products, so the "% off" banner
    // never advertises a discount the products don't actually have.
$seasonMaxDiscountPct = $seasonSaleProducts->reduce(function ($max, $product) {
    $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
    $pricing = $product->storefrontPricing($v);
    $pct = $pricing['has_discount'] ? (int) round((float) $pricing['discount_percentage']) : 0;
        return max($max, $pct);
    }, 0);
@endphp

<main class="bg-[#F1F1F1] w-full" style="font-family: 'Outfit', sans-serif;">

    <!-- ===== PAGE HEADER ===== -->
    <div class="sticky lg:static top-0 z-30 bg-white border-b border-neutral-100 px-4 lg:px-8 py-3 lg:py-5">
        <div class="lg:max-w-[1440px] lg:mx-auto lg:w-full flex items-center gap-2">
            <h1 class="text-xl lg:text-3xl font-semibold text-[#001537] text-start">{{ __('Offers') }}</h1>
            <span class="hidden lg:inline text-neutral-400 mx-2">/</span>
            <span class="hidden lg:inline text-sm text-neutral-500">{{ __('Exclusive deals & seasonal savings') }}</span>
        </div>
    </div>

    {{-- ===== MOBILE WRAPPER (max 480px, as-is) ===== --}}
    <div class="max-w-[480px] mx-auto lg:hidden">

        {{-- BANNER CAROUSEL --}}
        <div class="pt-4">
            <div class="flex gap-3 overflow-x-auto no-scrollbar px-4 pb-1">
                @forelse ($carouselBanners as $banner)
                    <a href="{{ $banner->url ?? '#' }}" class="shrink-0">
                        <img loading="lazy"
                            src="{{ $banner->image_path ?? 'https://placehold.co/340x132/0159ED/ffffff?text=Offer' }}"
                            alt="{{ $banner->translationValue('title') ?? __('Offer') }}"
                            class="rounded-2xl w-[340px] h-[132px] object-cover">
                    </a>
                @empty
                    <div class="rounded-2xl shrink-0 w-[340px] h-[132px] bg-neutral-200"></div>
                    <div class="rounded-2xl shrink-0 w-[340px] h-[132px] bg-neutral-200"></div>
                    <div class="rounded-2xl shrink-0 w-[340px] h-[132px] bg-neutral-200"></div>
                @endforelse
            </div>
        </div>

        {{-- HERO BANNER --}}
        <div class="px-4 pt-4">
            @if ($heroBanner && $heroBanner->image_path)
                <a href="{{ $heroBanner->url ?? '#' }}">
                    <img loading="lazy" src="{{ $heroBanner->image_path }}"
                        alt="{{ $heroBanner->translationValue('title') ?? __('Offer') }}"
                        class="w-full rounded-xl object-cover h-[171px]">
                </a>
            @else
                <div class="w-full rounded-xl bg-neutral-200 h-[171px]"></div>
            @endif
        </div>

        {{-- MEGA SALE --}}
        <div class="pt-6">
            <div class="flex items-center justify-between px-4 mb-3">
                <span class="text-xl font-semibold text-[#001537]">{{ __('Mega Sale') }}</span>
                <a href="{{ route('tenant.storefront.new-in') }}" class="text-sm font-medium">{{ __('view all') }}
                    →</a>
            </div>
            <div class="flex gap-3 overflow-x-auto no-scrollbar px-4 pb-2">
                @forelse ($megaSaleProducts as $product)
                    @php
                        $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                        $pricing = $product->storefrontPricing($v);
                        $sell = (float) $pricing['current_price'];
                        $orig = $pricing['original_price'];
                        $hasDisc = (bool) $pricing['has_discount'];
                        $discPct = $hasDisc ? (int) round((float) $pricing['discount_percentage']) : 0;
                        $dSell = $symbol . number_format($sell * $rate, 2);
                        $dOrig = $hasDisc && $orig ? $symbol . number_format($orig * $rate, 2) : null;
                        $img = $product->centralProduct?->primary_image_url ?? ($product->primary_image_url ?? null);
                        $rating = round((float) ($product->average_rating ?? 0));
                        $rateCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;
                        $wg = $product->centralProduct?->weight_grams ?? ($product?->weight_grams ?? null);
                        $wLabel = $wg ? ($wg >= 1000 ? number_format($wg / 1000, 1) . __('kg') : $wg . __('g')) : null;
                        $pName = $product->translationValue('name') ?? $product->slug;
                        $pUrl = route('tenant.storefront.product', $product->slug);
                        $discBg = match ($product->id % 2) {
                            0 => 'bg-[#FF570F]',
                            default => 'bg-[#DE1709]',
                        };
                    @endphp
                    <div class="shrink-0 w-[160px] rounded-[4.87px] overflow-hidden shadow-sm">
                        <div class="relative bg-[#F3F3FE] h-[183px] flex items-center justify-center overflow-hidden">
                            <a href="{{ $pUrl }}" class="flex items-center justify-center w-full h-full">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $pName }}"
                                        class="object-cover w-full h-full">
                                @else
                                    <div
                                        class="w-[80px] h-[80px] text-4xl flex items-center justify-center text-neutral-300">
                                        🛍️</div>
                                @endif
                            </a>
                            <button
                                class="souqify-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center"
                                data-slug="{{ $product->slug }}" aria-label="{{ __('Wishlist') }}">
                                <svg width="14" height="13" viewBox="0 0 22 20" fill="none" stroke="#8F8F8F"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button wire:click="addToCart({{ $product->id }})"
                                class="absolute bottom-8 right-2 w-[38px] h-[38px] bg-white shadow-md rounded-full border border-[#0159ED] flex items-center justify-center"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="21" height="21" viewBox="0 0 21 21" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8.69531 8.15657H13.0286M10.862 5.98991V10.3232M1.33948 3.17324H17.8213C19.3141 3.17324 20.3921 4.54907 19.9826 5.93357L18.1907 12.0002C17.9145 12.9319 17.0326 13.5732 16.0295 13.5732H6.10831C5.10406 13.5732 4.22114 12.9308 3.94598 12.0002L1.33948 3.17324ZM1.33948 3.17324L0.570312 0.573242M15.1953 20.0732C15.6263 20.0732 16.0396 19.902 16.3444 19.5973C16.6491 19.2925 16.8203 18.8792 16.8203 18.4482C16.8203 18.0173 16.6491 17.6039 16.3444 17.2992C16.0396 16.9944 15.6263 16.8232 15.1953 16.8232C14.7643 16.8232 14.351 16.9944 14.0463 17.2992C13.7415 17.6039 13.5703 18.0173 13.5703 18.4482C13.5703 18.8792 13.7415 19.2925 14.0463 19.5973C14.351 19.902 14.7643 20.0732 15.1953 20.0732ZM6.52864 20.0732C6.95962 20.0732 7.37294 19.902 7.67769 19.5973C7.98244 19.2925 8.15364 18.8792 8.15364 18.4482C8.15364 18.0173 7.98244 17.6039 7.67769 17.2992C7.37294 16.9944 6.95962 16.8232 6.52864 16.8232C6.09767 16.8232 5.68434 16.9944 5.37959 17.2992C5.07485 17.6039 4.90364 18.0173 4.90364 18.4482C4.90364 18.8792 5.07485 19.2925 5.37959 19.5973C5.68434 19.902 6.09767 20.0732 6.52864 20.0732Z"
                                        stroke="#0159ED" stroke-width="1.14706" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>

                            </button>
                        </div>
                        @if($hasFreeShipping ?? false)
                        <div class="absolute bottom-0 left-0 w-[112px] h-4 flex items-center justify-start px-2 rounded-tr-2xl text-white text-[8px] font-medium"
                            dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
                            style="background: linear-gradient(90deg, #AC0202, #E30000, #9E0000);">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="white" class="mr-0.5">
                                <path
                                    d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                            </svg>
                            {{ __('Free Delivery') }}
                        </div>
                        @endif

                        <div class="bg-[#0159ED] h-[17px] shadow flex items-center overflow-hidden px-2">
                            <span class="text-white text-[8px] whitespace-nowrap">🔥 {{ __('Hot Deal') }} •
                                {{ __('Best Price') }} • {{ __('Limited Stock') }}</span>
                        </div>
                        <div class="bg-white px-[6.5px] pt-[5px] pb-[6px]">
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <a href="{{ $pUrl }}"
                                    class="text-[11px] font-medium text-[#001537] leading-tight line-clamp-2 flex-1">{{ $pName }}</a>
                                @if ($wLabel)
                                    <span
                                        class="shrink-0 text-[#FF4D00] text-[8px] font-semibold rounded px-1 py-0.5">{{ $wLabel }}</span>
                                @endif
                            </div>
                            <div class="rounded px-2 py-0.5 flex items-center justify-between mb-1.5 bg-[#712AE2]">
                                <span
                                    class="text-[#FFB00A] text-[8px] font-semibold uppercase">{{ __('Mega Sale') }}</span>
                                @if ($saleEndsAtIso)
                                    <span class="sq-offer-countdown text-[#FFB00A] text-[8px] font-medium"
                                        data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-1.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="8" height="8" viewBox="0 0 24 24"
                                        fill="{{ $i <= $rating ? '#FFB00A' : '#D1D5DB' }}">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                @endfor
                                <span
                                    class="text-[#8F8F8F] text-[8px] ml-0.5">{{ number_format((float) ($product->average_rating ?? 0), 1) }}
                                    ({{ $rateCount > 0 ? '+' . $rateCount : '0' }})
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[#004AC6] text-xs font-bold">{{ $dSell }}</span>
                                @if ($dOrig)
                                    <span
                                        class="text-[#8F8F8F] text-[9px] line-through">{{ $dOrig }}</span><span
                                        class="{{ $discBg }} text-white text-[8px] font-semibold rounded px-1 py-0.5 ml-auto">{{ $discPct }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400 px-1 py-4">{{ __('No offers available right now.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- MID-BANNER 1 --}}
        <div class="pt-6">
            @if ($midBanner1Url)
                <img loading="lazy" src="{{ $midBanner1Url }}" alt="{{ __('Exclusive Deals') }}"
                    class="w-full object-cover h-[131px]">
            @else
                <div
                    class="w-full bg-gradient-to-r from-[#FF570F] to-[#DE1709] h-[131px] flex items-center justify-center">
                    <span class="text-white text-lg font-semibold">{{ __('Exclusive Deals – Shop Now') }}</span>
                </div>
            @endif
        </div>

        {{-- SEASON SALE --}}
        <div class="pt-6">
            <div class="flex items-center justify-between px-4 mb-3">
                <span class="text-xl font-semibold text-[#001537]">{{ __('Season Sale') }}</span>
                <a href="{{ route('tenant.storefront.new-in') }}" class="text-sm font-medium">{{ __('view all') }}
                    →</a>
            </div>
            <div class="flex gap-3 overflow-x-auto no-scrollbar px-4 pb-2">
                @forelse ($seasonSaleProducts as $product)
                    @php
                        $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                        $pricing = $product->storefrontPricing($v);
                        $sell = (float) $pricing['current_price'];
                        $orig = $pricing['original_price'];
                        $hasDisc = (bool) $pricing['has_discount'];
                        $discPct = $hasDisc ? (int) round((float) $pricing['discount_percentage']) : 0;
                        $dSell = $symbol . number_format($sell * $rate, 2);
                        $dOrig = $hasDisc && $orig ? $symbol . number_format($orig * $rate, 2) : null;
                        $img = $product->centralProduct?->primary_image_url ?? ($product->primary_image_url ?? null);
                        $rating = round((float) ($product->average_rating ?? 0));
                        $rateCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;
                        $wg = $product->centralProduct?->weight_grams ?? null;
                        $wLabel = $wg ? ($wg >= 1000 ? number_format($wg / 1000, 1) . __('kg') : $wg . __('g')) : null;
                        $pName = $product->translationValue('name') ?? $product->slug;
                        $pUrl = route('tenant.storefront.product', $product->slug);
                        $discBg = match ($product->id % 2) {
                            0 => 'bg-[#FF570F]',
                            default => 'bg-[#DE1709]',
                        };
                    @endphp
                    <div class="shrink-0 w-[160px] rounded-[4.87px] overflow-hidden shadow-sm">
                        <div class="relative bg-[#F3F3FE] h-[183px] flex items-center justify-center overflow-hidden">
                            <a href="{{ $pUrl }}" class="flex items-center justify-center w-full h-full">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $pName }}"
                                        class="object-cover w-full h-full">
                                @else
                                    <div
                                        class="w-[80px] h-[80px] text-4xl flex items-center justify-center text-neutral-300">
                                        🛍️</div>
                                @endif
                            </a>
                            <button
                                class="souqify-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center"
                                data-slug="{{ $product->slug }}" aria-label="{{ __('Wishlist') }}">
                                <svg width="14" height="13" viewBox="0 0 22 20" fill="none"
                                    stroke="#8F8F8F" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button wire:click="addToCart({{ $product->id }})"
                                class="absolute bottom-8 right-2 w-[38px] h-[38px] bg-white shadow-md rounded-full border border-[#0159ED] flex items-center justify-center"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="21" height="21" viewBox="0 0 21 21" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8.69531 8.15657H13.0286M10.862 5.98991V10.3232M1.33948 3.17324H17.8213C19.3141 3.17324 20.3921 4.54907 19.9826 5.93357L18.1907 12.0002C17.9145 12.9319 17.0326 13.5732 16.0295 13.5732H6.10831C5.10406 13.5732 4.22114 12.9308 3.94598 12.0002L1.33948 3.17324ZM1.33948 3.17324L0.570312 0.573242M15.1953 20.0732C15.6263 20.0732 16.0396 19.902 16.3444 19.5973C16.6491 19.2925 16.8203 18.8792 16.8203 18.4482C16.8203 18.0173 16.6491 17.6039 16.3444 17.2992C16.0396 16.9944 15.6263 16.8232 15.1953 16.8232C14.7643 16.8232 14.351 16.9944 14.0463 17.2992C13.7415 17.6039 13.5703 18.0173 13.5703 18.4482C13.5703 18.8792 13.7415 19.2925 14.0463 19.5973C14.351 19.902 14.7643 20.0732 15.1953 20.0732ZM6.52864 20.0732C6.95962 20.0732 7.37294 19.902 7.67769 19.5973C7.98244 19.2925 8.15364 18.8792 8.15364 18.4482C8.15364 18.0173 7.98244 17.6039 7.67769 17.2992C7.37294 16.9944 6.95962 16.8232 6.52864 16.8232C6.09767 16.8232 5.68434 16.9944 5.37959 17.2992C5.07485 17.6039 4.90364 18.0173 4.90364 18.4482C4.90364 18.8792 5.07485 19.2925 5.37959 19.5973C5.68434 19.902 6.09767 20.0732 6.52864 20.0732Z"
                                        stroke="#0159ED" stroke-width="1.14706" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>

                            </button>
                            @if($hasFreeShipping ?? false)
                            <div class="absolute bottom-0 left-0 w-[112px] h-4 flex items-center justify-start px-2 rounded-tr-2xl text-white text-[8px] font-medium"
                                dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
                                style="background: linear-gradient(90deg, #AC0202, #E30000, #9E0000);">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="white" class="mr-0.5">
                                    <path
                                        d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                                {{ __('Free Delivery') }}
                            </div>
                            @endif
                        </div>
                        <div class="bg-[#0159ED] h-[17px] shadow flex items-center overflow-hidden px-2">
                            <span class="text-white text-[8px] whitespace-nowrap">🌸 {{ __('Season Special') }} •
                                {{ __('Fresh Arrivals') }}</span>
                        </div>
                        <div class="bg-white px-[6.5px] pt-[5px] pb-[6px]">
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <a href="{{ $pUrl }}"
                                    class="text-[11px] font-medium text-[#001537] leading-tight line-clamp-2 flex-1">{{ $pName }}</a>
                                @if ($wLabel)
                                    <span
                                        class="shrink-0 text-[#FF4D00] text-[8px] font-semibold rounded px-1 py-0.5">{{ $wLabel }}</span>
                                @endif
                            </div>
                            <div class="rounded px-2 py-0.5 flex items-center justify-between mb-1.5 bg-[#FFB00A]">
                                <span
                                    class="text-[#FDFDFD] text-[8px] font-semibold uppercase">{{ __('Season Sale') }}</span>
                                @if ($saleEndsAtIso)
                                    <span class="sq-offer-countdown text-[#FDFDFD] text-[8px] font-medium"
                                        data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-1.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="8" height="8" viewBox="0 0 24 24"
                                        fill="{{ $i <= $rating ? '#FFB00A' : '#D1D5DB' }}">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                @endfor
                                <span
                                    class="text-[#8F8F8F] text-[8px] ml-0.5">{{ number_format((float) ($product->average_rating ?? 0), 1) }}
                                    ({{ $rateCount > 0 ? '+' . $rateCount : '0' }})
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[#004AC6] text-xs font-bold">{{ $dSell }}</span>
                                @if ($dOrig)
                                    <span
                                        class="text-[#8F8F8F] text-[9px] line-through">{{ $dOrig }}</span><span
                                        class="{{ $discBg }} text-white text-[8px] font-semibold rounded px-1 py-0.5 ml-auto">{{ $discPct }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400 px-1 py-4">{{ __('No season sale products right now.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- MID-BANNER 2 --}}
        <div class="pt-6">
            @if ($midBanner2Url)
                <img loading="lazy" src="{{ $midBanner2Url }}" alt="{{ __('Season Collection') }}"
                    class="w-full object-cover h-[132px]">
            @else
                <div
                    class="w-full bg-gradient-to-r from-[#712AE2] to-[#0159ED] h-[132px] flex items-center justify-center">
                    <span
                        class="text-white text-lg font-semibold">{{ $seasonMaxDiscountPct > 0 ? __('Season Collection – Up to :pct% Off', ['pct' => $seasonMaxDiscountPct]) : __('Season Collection') }}</span>
                </div>
            @endif
        </div>

        {{-- SUPER SALE --}}
        <div class="pt-6 pb-8">
            <div class="flex items-center justify-between px-4 mb-3">
                <span class="text-xl font-semibold text-[#001537]">{{ __('Super Sale') }}</span>
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="text-sm font-medium">{{ __('view all') }}
                    →</a>
            </div>
            <div class="flex gap-3 overflow-x-auto no-scrollbar px-4 pb-2">
                @forelse ($superSaleProducts as $product)
                    @php
                        $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                        $pricing = $product->storefrontPricing($v);
                        $sell = (float) $pricing['current_price'];
                        $orig = $pricing['original_price'];
                        $hasDisc = (bool) $pricing['has_discount'];
                        $discPct = $hasDisc ? (int) round((float) $pricing['discount_percentage']) : 0;
                        $dSell = $symbol . number_format($sell * $rate, 2);
                        $dOrig = $hasDisc && $orig ? $symbol . number_format($orig * $rate, 2) : null;
                        $img = $product->centralProduct?->primary_image_url ?? ($product->primary_image_url ?? null);
                        $rating = round((float) ($product->average_rating ?? 0));
                        $rateCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;
                        $wg = $product->centralProduct?->weight_grams ?? ($product?->weight_grams ?? null);
                        $wLabel = $wg ? ($wg >= 1000 ? number_format($wg / 1000, 1) . __('kg') : $wg . __('g')) : null;
                        $pName = $product->translationValue('name') ?? $product->slug;
                        $pUrl = route('tenant.storefront.product', $product->slug);
                        $discBg = match ($product->id % 2) {
                            0 => 'bg-[#FF570F]',
                            default => 'bg-[#DE1709]',
                        };
                    @endphp
                    <div class="shrink-0 w-[160px] rounded-[4.87px] overflow-hidden shadow-sm">
                        <div class="relative bg-[#F3F3FE] h-[183px] flex items-center justify-center overflow-hidden">
                            <a href="{{ $pUrl }}" class="flex items-center justify-center w-full h-full">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $pName }}"
                                        class="object-cover w-full h-full">
                                @else
                                    <div
                                        class="w-[80px] h-[80px] text-4xl flex items-center justify-center text-neutral-300">
                                        🛍️</div>
                                @endif
                            </a>
                            <button
                                class="souqify-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center"
                                data-slug="{{ $product->slug }}" aria-label="{{ __('Wishlist') }}">
                                <svg width="14" height="13" viewBox="0 0 22 20" fill="none"
                                    stroke="#8F8F8F" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button wire:click="addToCart({{ $product->id }})"
                                class="absolute bottom-8 right-2 w-[38px] h-[38px] bg-white shadow-md rounded-full border border-[#0159ED] flex items-center justify-center"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="21" height="21" viewBox="0 0 21 21" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8.69531 8.15657H13.0286M10.862 5.98991V10.3232M1.33948 3.17324H17.8213C19.3141 3.17324 20.3921 4.54907 19.9826 5.93357L18.1907 12.0002C17.9145 12.9319 17.0326 13.5732 16.0295 13.5732H6.10831C5.10406 13.5732 4.22114 12.9308 3.94598 12.0002L1.33948 3.17324ZM1.33948 3.17324L0.570312 0.573242M15.1953 20.0732C15.6263 20.0732 16.0396 19.902 16.3444 19.5973C16.6491 19.2925 16.8203 18.8792 16.8203 18.4482C16.8203 18.0173 16.6491 17.6039 16.3444 17.2992C16.0396 16.9944 15.6263 16.8232 15.1953 16.8232C14.7643 16.8232 14.351 16.9944 14.0463 17.2992C13.7415 17.6039 13.5703 18.0173 13.5703 18.4482C13.5703 18.8792 13.7415 19.2925 14.0463 19.5973C14.351 19.902 14.7643 20.0732 15.1953 20.0732ZM6.52864 20.0732C6.95962 20.0732 7.37294 19.902 7.67769 19.5973C7.98244 19.2925 8.15364 18.8792 8.15364 18.4482C8.15364 18.0173 7.98244 17.6039 7.67769 17.2992C7.37294 16.9944 6.95962 16.8232 6.52864 16.8232C6.09767 16.8232 5.68434 16.9944 5.37959 17.2992C5.07485 17.6039 4.90364 18.0173 4.90364 18.4482C4.90364 18.8792 5.07485 19.2925 5.37959 19.5973C5.68434 19.902 6.09767 20.0732 6.52864 20.0732Z"
                                        stroke="#0159ED" stroke-width="1.14706" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>

                            </button>
                            @if($hasFreeShipping ?? false)
                            <div class="absolute bottom-0 left-0 w-[112px] h-4 flex items-center justify-start px-2 rounded-tr-2xl text-white text-[8px] font-medium"
                                dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
                                style="background: linear-gradient(90deg, #AC0202, #E30000, #9E0000);">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="white" class="mr-0.5">
                                    <path
                                        d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                                {{ __('Free Delivery') }}
                            </div>
                            @endif
                        </div>
                        <div class="bg-[#0159ED] h-[17px] shadow flex items-center overflow-hidden px-2">
                            <span class="text-white text-[8px] whitespace-nowrap">💥 {{ __('Super Deal') }} •
                                {{ __('Max Savings') }} • {{ __('Today Only') }}</span>
                        </div>
                        <div class="bg-white px-[6.5px] pt-[5px] pb-[6px]">
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <a href="{{ $pUrl }}"
                                    class="text-[11px] font-medium text-[#001537] leading-tight line-clamp-2 flex-1">{{ $pName }}</a>
                                @if ($wLabel)
                                    <span
                                        class="shrink-0 text-[#FF4D00] text-[8px] font-semibold rounded px-1 py-0.5">{{ $wLabel }}</span>
                                @endif
                            </div>
                            <div class="rounded px-2 py-0.5 flex items-center justify-between mb-1.5"
                                style="background: linear-gradient(91.49deg, #002460 14.29%, #003A9A 39.73%, #002E7B 58.01%, #004AC6 77.97%, #002460 91.14%);">
                                <span
                                    class="text-[#FFB00A] text-[8px] font-semibold uppercase">{{ __('Super Sale') }}</span>
                                @if ($saleEndsAtIso)
                                    <span class="sq-offer-countdown text-[#FFB00A] text-[8px] font-medium"
                                        data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-1.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="8" height="8" viewBox="0 0 24 24"
                                        fill="{{ $i <= $rating ? '#FFB00A' : '#D1D5DB' }}">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                @endfor
                                <span
                                    class="text-[#8F8F8F] text-[8px] ml-0.5">{{ number_format((float) ($product->average_rating ?? 0), 1) }}
                                    ({{ $rateCount > 0 ? '+' . $rateCount : '0' }})
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[#004AC6] text-xs font-bold">{{ $dSell }}</span>
                                @if ($dOrig)
                                    <span
                                        class="text-[#8F8F8F] text-[9px] line-through">{{ $dOrig }}</span><span
                                        class="{{ $discBg }} text-white text-[8px] font-semibold rounded px-1 py-0.5 ml-auto">{{ $discPct }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400 px-1 py-4">{{ __('No super sale products right now.') }}</p>
                @endforelse
            </div>
        </div>

    </div>{{-- end mobile wrapper --}}

    {{-- ===== DESKTOP LAYOUT (lg+) ===== --}}
    <div class="hidden lg:block max-w-[1440px] mx-auto px-8 pt-6 pb-12">

        {{-- DESKTOP: BANNER ROW (carousel 3 + hero 1) --}}
        <div class="grid grid-cols-4 gap-5 mb-8">
            @forelse ($carouselBanners as $banner)
                <a href="{{ $banner->url ?? '#' }}" class="block">
                    <img loading="lazy"
                        src="{{ $banner->image_path ?? 'https://placehold.co/360x200/0159ED/ffffff?text=Offer' }}"
                        alt="{{ $banner->translationValue('title') ?? __('Offer') }}"
                        class="rounded-2xl w-full h-[200px] object-cover">
                </a>
            @empty
                <div class="rounded-2xl w-full h-[200px] bg-neutral-200"></div>
                <div class="rounded-2xl w-full h-[200px] bg-neutral-200"></div>
                <div class="rounded-2xl w-full h-[200px] bg-neutral-200"></div>
            @endforelse
            {{-- Hero banner occupies 1 column --}}
            @if ($heroBanner && $heroBanner->image_path)
                <a href="{{ $heroBanner->url ?? '#' }}" class="block">
                    <img loading="lazy" src="{{ $heroBanner->image_path }}"
                        alt="{{ $heroBanner->translationValue('title') ?? __('Offer') }}"
                        class="rounded-2xl w-full h-[200px] object-cover">
                </a>
            @else
                <div class="rounded-2xl w-full h-[200px] bg-neutral-200"></div>
            @endif
        </div>

        {{-- DESKTOP: MEGA SALE --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-[#001537]">{{ __('Mega Sale') }}</span>
                    @if ($saleEndsAtIso)
                        <span
                            class="sq-offer-countdown inline-flex items-center gap-1 bg-[#712AE2] text-[#FFB00A] text-xs font-semibold px-3 py-1 rounded-full"
                            data-sq-countdown data-emoji="🔥" data-end="{{ $saleEndsAtIso }}">🔥 --:--:--</span>
                    @endif
                </div>
                <a href="{{ route('tenant.storefront.new-in') }}"
                    class="text-sm text-[#0159ED] font-medium hover:underline">{{ __('view all') }} →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse ($megaSaleProducts as $product)
                    @php
                        $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                        $pricing = $product->storefrontPricing($v);
                        $sell = (float) $pricing['current_price'];
                        $orig = $pricing['original_price'];
                        $hasDisc = (bool) $pricing['has_discount'];
                        $discPct = $hasDisc ? (int) round((float) $pricing['discount_percentage']) : 0;
                        $dSell = $symbol . number_format($sell * $rate, 2);
                        $dOrig = $hasDisc && $orig ? $symbol . number_format($orig * $rate, 2) : null;
                        $img = $product->centralProduct?->primary_image_url ?? ($product->primary_image_url ?? null);
                        $rating = round((float) ($product->average_rating ?? 0));
                        $rateCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;
                        $wg = $product->centralProduct?->weight_grams ?? ($product?->weight_grams ?? null);
                        $wLabel = $wg ? ($wg >= 1000 ? number_format($wg / 1000, 1) . __('kg') : $wg . __('g')) : null;
                        $pName = $product->translationValue('name') ?? $product->slug;
                        $pUrl = route('tenant.storefront.product', $product->slug);
                        $discBg = match ($product->id % 2) {
                            0 => 'bg-[#FF570F]',
                            default => 'bg-[#DE1709]',
                        };
                    @endphp
                    <div class="rounded-[4.87px] overflow-hidden shadow-sm bg-white flex flex-col">
                        <div class="relative bg-[#F3F3FE] h-[220px] flex items-center justify-center overflow-hidden">
                            <a href="{{ $pUrl }}" class="flex items-center justify-center w-full h-full">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $pName }}"
                                        class="object-contain w-full h-full">
                                @else
                                    <div class="text-5xl text-neutral-300">🛍️</div>
                                @endif
                            </a>
                            <button
                                class="souqify-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center"
                                data-slug="{{ $product->slug }}" aria-label="{{ __('Wishlist') }}">
                                <svg width="14" height="13" viewBox="0 0 22 20" fill="none"
                                    stroke="#8F8F8F" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button wire:click="addToCart({{ $product->id }})"
                                class="absolute bottom-10 right-2 w-[38px] h-[38px] bg-white shadow-md rounded-full border border-[#0159ED] flex items-center justify-center"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                    stroke="#0159ED" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <line x1="3" y1="6" x2="21" y2="6"
                                        stroke-width="1.5" />
                                    <path d="M16 10a4 4 0 01-8 0" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        <div class="bg-[#0159ED] h-[20px] flex items-center overflow-hidden px-3">
                            <span class="text-white text-[9px] whitespace-nowrap">🔥 {{ __('Hot Deal') }} •
                                {{ __('Best Price') }} • {{ __('Limited Stock') }}</span>
                        </div>
                        <div class="bg-white px-3 pt-2 pb-3 flex-1 flex flex-col">
                            <div class="flex items-start justify-between gap-1 mb-2">
                                <a href="{{ $pUrl }}"
                                    class="text-[13px] font-medium text-[#001537] leading-tight line-clamp-2 flex-1 hover:text-[#0159ED] transition">{{ $pName }}</a>
                                @if ($wLabel)
                                    <span
                                        class="shrink-0 bg-[#FF4D00] text-white text-[8px] font-semibold rounded px-1 py-0.5">{{ $wLabel }}</span>
                                @endif
                            </div>
                            <div class="rounded px-2 py-1 flex items-center justify-between mb-2 bg-[#712AE2]">
                                <span
                                    class="text-[#FFB00A] text-[9px] font-semibold uppercase">{{ __('Mega Sale') }}</span>
                                @if ($saleEndsAtIso)
                                    <span class="sq-offer-countdown text-[#FFB00A] text-[9px] font-medium"
                                        data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="10" height="10" viewBox="0 0 24 24"
                                        fill="{{ $i <= $rating ? '#FFB00A' : '#D1D5DB' }}">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                @endfor
                                <span
                                    class="text-[#8F8F8F] text-[9px] ml-1">{{ number_format((float) ($product->average_rating ?? 0), 1) }}
                                    ({{ $rateCount > 0 ? '+' . $rateCount : '0' }})
                                </span>
                            </div>
                            <div class="flex items-center gap-1 mt-auto">
                                <span class="text-[#004AC6] text-sm font-bold">{{ $dSell }}</span>
                                @if ($dOrig)
                                    <span class="text-[#8F8F8F] text-xs line-through">{{ $dOrig }}</span><span
                                        class="{{ $discBg }} text-white text-[9px] font-semibold rounded px-1 py-0.5 ml-auto">{{ $discPct }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full text-sm text-neutral-400 py-6">{{ __('No offers available right now.') }}
                    </p>
                @endforelse
            </div>
        </div>

        {{-- DESKTOP: MID-BANNER 1 --}}
        <div class="mb-8">
            @if ($midBanner1Url)
                <img loading="lazy" src="{{ $midBanner1Url }}" alt="{{ __('Exclusive Deals') }}"
                    class="w-full object-cover h-[180px] rounded-2xl">
            @else
                <div
                    class="w-full bg-gradient-to-r from-[#FF570F] to-[#DE1709] h-[180px] rounded-2xl flex items-center justify-center">
                    <span class="text-white text-2xl font-semibold">{{ __('Exclusive Deals – Shop Now') }}</span>
                </div>
            @endif
        </div>

        {{-- DESKTOP: SEASON SALE --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-[#001537]">{{ __('Season Sale') }}</span>
                    @if ($saleEndsAtIso)
                        <span
                            class="sq-offer-countdown inline-flex items-center gap-1 bg-[#FFB00A] text-white text-xs font-semibold px-3 py-1 rounded-full"
                            data-sq-countdown data-emoji="🌸" data-end="{{ $saleEndsAtIso }}">🌸 --:--:--</span>
                    @endif
                </div>
                <a href="{{ route('tenant.storefront.new-in') }}"
                    class="text-sm text-[#0159ED] font-medium hover:underline">{{ __('view all') }} →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse ($seasonSaleProducts as $product)
                    @php
                        $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                        $pricing = $product->storefrontPricing($v);
                        $sell = (float) $pricing['current_price'];
                        $orig = $pricing['original_price'];
                        $hasDisc = (bool) $pricing['has_discount'];
                        $discPct = $hasDisc ? (int) round((float) $pricing['discount_percentage']) : 0;
                        $dSell = $symbol . number_format($sell * $rate, 2);
                        $dOrig = $hasDisc && $orig ? $symbol . number_format($orig * $rate, 2) : null;
                        $img = $product->centralProduct?->primary_image_url ?? ($product->primary_image_url ?? null);
                        $rating = round((float) ($product->average_rating ?? 0));
                        $rateCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;
                        $wg = $product->centralProduct?->weight_grams ?? ($product?->weight_grams ?? null);
                        $wLabel = $wg ? ($wg >= 1000 ? number_format($wg / 1000, 1) . __('kg') : $wg . __('g')) : null;
                        $pName = $product->translationValue('name') ?? $product->slug;
                        $pUrl = route('tenant.storefront.product', $product->slug);
                        $discBg = match ($product->id % 2) {
                            0 => 'bg-[#FF570F]',
                            default => 'bg-[#DE1709]',
                        };
                    @endphp
                    <div class="rounded-[4.87px] overflow-hidden shadow-sm bg-white flex flex-col">
                        <div class="relative bg-[#F3F3FE] h-[220px] flex items-center justify-center overflow-hidden">
                            <a href="{{ $pUrl }}" class="flex items-center justify-center w-full h-full">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $pName }}"
                                        class="object-contain w-full h-full">
                                @else
                                    <div class="text-5xl text-neutral-300">🛍️</div>
                                @endif
                            </a>
                            <button
                                class="souqify-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center"
                                data-slug="{{ $product->slug }}" aria-label="{{ __('Wishlist') }}">
                                <svg width="14" height="13" viewBox="0 0 22 20" fill="none"
                                    stroke="#8F8F8F" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button wire:click="addToCart({{ $product->id }})"
                                class="absolute bottom-10 right-2 w-[38px] h-[38px] bg-white shadow-md rounded-full border border-[#0159ED] flex items-center justify-center"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                    stroke="#0159ED" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <line x1="3" y1="6" x2="21" y2="6"
                                        stroke-width="1.5" />
                                    <path d="M16 10a4 4 0 01-8 0" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                            @if($hasFreeShipping ?? false)
                            <div class="absolute bottom-0 left-0 w-[112px] h-4 flex items-center justify-start px-2 rounded-tr-2xl text-white text-[8px] font-medium"
                                dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
                                style="background: linear-gradient(90deg, #AC0202, #E30000, #9E0000);">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="white" class="mr-0.5">
                                    <path
                                        d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                                {{ __('Free Delivery') }}
                            </div>
                            @endif
                        </div>
                        <div class="bg-[#0159ED] h-[20px] flex items-center overflow-hidden px-3">
                            <span class="text-white text-[9px] whitespace-nowrap">🌸 {{ __('Season Special') }} •
                                {{ __('Fresh Arrivals') }}</span>
                        </div>
                        <div class="bg-white px-3 pt-2 pb-3 flex-1 flex flex-col">
                            <div class="flex items-start justify-between gap-1 mb-2">
                                <a href="{{ $pUrl }}"
                                    class="text-[13px] font-medium text-[#001537] leading-tight line-clamp-2 flex-1 hover:text-[#0159ED] transition">{{ $pName }}</a>
                                @if ($wLabel)
                                    <span
                                        class="shrink-0 bg-[#FF4D00] text-white text-[8px] font-semibold rounded px-1 py-0.5">{{ $wLabel }}</span>
                                @endif
                            </div>
                            <div class="rounded px-2 py-1 flex items-center justify-between mb-2 bg-[#FFB00A]">
                                <span
                                    class="text-[#FDFDFD] text-[9px] font-semibold uppercase">{{ __('Season Sale') }}</span>
                                @if ($saleEndsAtIso)
                                    <span class="sq-offer-countdown text-[#FDFDFD] text-[9px] font-medium"
                                        data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="10" height="10" viewBox="0 0 24 24"
                                        fill="{{ $i <= $rating ? '#FFB00A' : '#D1D5DB' }}">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                @endfor
                                <span
                                    class="text-[#8F8F8F] text-[9px] ml-1">{{ number_format((float) ($product->average_rating ?? 0), 1) }}
                                    ({{ $rateCount > 0 ? '+' . $rateCount : '0' }})
                                </span>
                            </div>
                            <div class="flex items-center gap-1 mt-auto">
                                <span class="text-[#004AC6] text-sm font-bold">{{ $dSell }}</span>
                                @if ($dOrig)
                                    <span class="text-[#8F8F8F] text-xs line-through">{{ $dOrig }}</span><span
                                        class="{{ $discBg }} text-white text-[9px] font-semibold rounded px-1 py-0.5 ml-auto">{{ $discPct }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full text-sm text-neutral-400 py-6">
                        {{ __('No season sale products right now.') }}
                    </p>
                @endforelse
            </div>
        </div>

        {{-- DESKTOP: MID-BANNER 2 --}}
        <div class="mb-8">
            @if ($midBanner2Url)
                <img loading="lazy" src="{{ $midBanner2Url }}" alt="{{ __('Season Collection') }}"
                    class="w-full object-cover h-[180px] rounded-2xl">
            @else
                <div
                    class="w-full bg-gradient-to-r from-[#712AE2] to-[#0159ED] h-[180px] rounded-2xl flex items-center justify-center">
                    <span
                        class="text-white text-2xl font-semibold">{{ $seasonMaxDiscountPct > 0 ? __('Season Collection – Up to :pct% Off', ['pct' => $seasonMaxDiscountPct]) : __('Season Collection') }}</span>
                </div>
            @endif
        </div>

        {{-- DESKTOP: SUPER SALE --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-[#001537]">{{ __('Super Sale') }}</span>
                    @if ($saleEndsAtIso)
                        <span
                            class="sq-offer-countdown inline-flex items-center gap-1 text-[#FFB00A] text-xs font-semibold px-3 py-1 rounded-full"
                            style="background: linear-gradient(91.49deg, #002460 14.29%, #003A9A 39.73%, #002E7B 58.01%, #004AC6 77.97%, #002460 91.14%);"
                            data-sq-countdown data-emoji="💥" data-end="{{ $saleEndsAtIso }}">💥 --:--:--</span>
                    @endif
                </div>
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="text-sm text-[#0159ED] font-medium hover:underline">{{ __('view all') }} →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse ($superSaleProducts as $product)
                    @php
                        $v = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                        $pricing = $product->storefrontPricing($v);
                        $sell = (float) $pricing['current_price'];
                        $orig = $pricing['original_price'];
                        $hasDisc = (bool) $pricing['has_discount'];
                        $discPct = $hasDisc ? (int) round((float) $pricing['discount_percentage']) : 0;
                        $dSell = $symbol . number_format($sell * $rate, 2);
                        $dOrig = $hasDisc && $orig ? $symbol . number_format($orig * $rate, 2) : null;
                        $img = $product->centralProduct?->primary_image_url ?? ($product->primary_image_url ?? null);
                        $rating = round((float) ($product->average_rating ?? 0));
                        $rateCount = $product->relationLoaded('rates') ? $product->rates->count() : 0;
                        $wg = $product->centralProduct?->weight_grams ?? ($product?->weight_grams ?? null);
                        $wLabel = $wg ? ($wg >= 1000 ? number_format($wg / 1000, 1) . __('kg') : $wg . __('g')) : null;
                        $pName = $product->translationValue('name') ?? $product->slug;
                        $pUrl = route('tenant.storefront.product', $product->slug);
                        $discBg = match ($product->id % 2) {
                            0 => 'bg-[#FF570F]',
                            default => 'bg-[#DE1709]',
                        };
                    @endphp
                    <div class="rounded-[4.87px] overflow-hidden shadow-sm bg-white flex flex-col">
                        <div class="relative bg-[#F3F3FE] h-[220px] flex items-center justify-center overflow-hidden">
                            <a href="{{ $pUrl }}" class="flex items-center justify-center w-full h-full">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $pName }}"
                                        class="object-contain w-full h-full">
                                @else
                                    <div class="text-5xl text-neutral-300">🛍️</div>
                                @endif
                            </a>
                            <button
                                class="souqify-fav-btn absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center"
                                data-slug="{{ $product->slug }}" aria-label="{{ __('Wishlist') }}">
                                <svg width="14" height="13" viewBox="0 0 22 20" fill="none"
                                    stroke="#8F8F8F" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button wire:click="addToCart({{ $product->id }})"
                                class="absolute bottom-10 right-2 w-[38px] h-[38px] bg-white shadow-md rounded-full border border-[#0159ED] flex items-center justify-center"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                    stroke="#0159ED" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <line x1="3" y1="6" x2="21" y2="6"
                                        stroke-width="1.5" />
                                    <path d="M16 10a4 4 0 01-8 0" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                            @if($hasFreeShipping ?? false)
                            <div class="absolute bottom-0 left-0 w-[112px] h-4 flex items-center justify-start px-2 rounded-tr-2xl text-white text-[8px] font-medium"
                                dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
                                style="background: linear-gradient(90deg, #AC0202, #E30000, #9E0000);">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="white" class="mr-0.5">
                                    <path
                                        d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                                {{ __('Free Delivery') }}
                            </div>
                            @endif
                        </div>
                        <div class="bg-[#0159ED] h-[20px] flex items-center overflow-hidden px-3">
                            <span class="text-white text-[9px] whitespace-nowrap">💥 {{ __('Super Deal') }} •
                                {{ __('Max Savings') }} • {{ __('Today Only') }}</span>
                        </div>
                        <div class="bg-white px-3 pt-2 pb-3 flex-1 flex flex-col">
                            <div class="flex items-start justify-between gap-1 mb-2">
                                <a href="{{ $pUrl }}"
                                    class="text-[13px] font-medium text-[#001537] leading-tight line-clamp-2 flex-1 hover:text-[#0159ED] transition">{{ $pName }}</a>
                                @if ($wLabel)
                                    <span
                                        class="shrink-0 bg-[#FF4D00] text-white text-[8px] font-semibold rounded px-1 py-0.5">{{ $wLabel }}</span>
                                @endif
                            </div>
                            <div class="rounded px-2 py-1 flex items-center justify-between mb-2"
                                style="background: linear-gradient(91.49deg, #002460 14.29%, #003A9A 39.73%, #002E7B 58.01%, #004AC6 77.97%, #002460 91.14%);">
                                <span
                                    class="text-[#FFB00A] text-[9px] font-semibold uppercase">{{ __('Super Sale') }}</span>
                                @if ($saleEndsAtIso)
                                    <span class="sq-offer-countdown text-[#FFB00A] text-[9px] font-medium"
                                        data-sq-countdown data-end="{{ $saleEndsAtIso }}">--:--:--</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg width="10" height="10" viewBox="0 0 24 24"
                                        fill="{{ $i <= $rating ? '#FFB00A' : '#D1D5DB' }}">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                @endfor
                                <span
                                    class="text-[#8F8F8F] text-[9px] ml-1">{{ number_format((float) ($product->average_rating ?? 0), 1) }}
                                    ({{ $rateCount > 0 ? '+' . $rateCount : '0' }})
                                </span>
                            </div>
                            <div class="flex items-center gap-1 mt-auto">
                                <span class="text-[#004AC6] text-sm font-bold">{{ $dSell }}</span>
                                @if ($dOrig)
                                    <span class="text-[#8F8F8F] text-xs line-through">{{ $dOrig }}</span><span
                                        class="{{ $discBg }} text-white text-[9px] font-semibold rounded px-1 py-0.5 ml-auto">{{ $discPct }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full text-sm text-neutral-400 py-6">
                        {{ __('No super sale products right now.') }}</p>
                @endforelse
            </div>
        </div>

    </div>{{-- end desktop wrapper --}}

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
