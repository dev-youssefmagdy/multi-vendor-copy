{{-- PHP logic moved to App\Livewire\Tenant\Storefront\ProductPage.php (render method) --}}

@push('head')
@php
    $jsonLdProduct = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->translationValue('name') ?? $product->slug,
        'url' => route('tenant.storefront.product', $product->slug),
        'image' => $mediaItems->pluck('src')->filter()->values()->toArray(),
        'description' => strip_tags($product->translationValue('description') ?? $product->centralProduct?->translationValue('description') ?? ''),
        'sku' => $product->sku ?? $product->slug,
        'brand' => ['@type' => 'Brand', 'name' => $storeName ?? config('app.name')],
        'offers' => [
            '@type' => 'Offer',
            'url' => route('tenant.storefront.product', $product->slug),
            'priceCurrency' => data_get($currentCurrency ?? null, 'code', 'USD'),
            'price' => number_format($sellPrice * $rate, 2, '.', ''),
            'availability' => $isInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'priceValidUntil' => now()->addDays(30)->toDateString(),
        ],
    ];
    if ($avgRating > 0 && $reviewCount > 0) {
        $jsonLdProduct['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $avgRating,
            'reviewCount' => $reviewCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    $breadcrumbItems = [['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => route('tenant.home')]];
    $pos = 2;
    foreach ($categoryAncestors as $ancestor) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $ancestor->translationValue('name') ?? $ancestor->slug,
            'item' => route('tenant.storefront.category', $ancestor->slug),
        ];
    }
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $pos,
        'name' => $product->translationValue('name') ?? $product->slug,
        'item' => route('tenant.storefront.product', $product->slug),
    ];
    $jsonLdBreadcrumb = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLdProduct, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($jsonLdBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

<main class="flex-grow bg-white pt-4 pb-14 w-full">
    <div class="max-w-[1280px] mx-auto px-5 sm:px-8">
        <!-- Mobile Header -->
        <div class="sm:hidden mb-4 flex items-center gap-3">
            <!-- Back Button -->
            <button class="w-12 h-12 flex justify-center items-center rounded-full border hover:bg-gray-200"
                    aria-label="{{ __('Back') }}" onclick="window.location.href='{{ url('/') }}'">
                <svg width=" 8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.66797 0.666992L0.667968 6.66699L6.66797 12.667" stroke="#0A0A0A" stroke-width="1.33333"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="flex-1">
            </div>

            <!-- ---- search field ---------- -->
            {{-- <div class="flex-1 overflow-hidden">
                <form action=" {{ route('tenant.storefront.search') }}" method="GET"
                      data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                    <div class="bg-[#F6F5F5] border flex items-center bg-bg rounded-full px-3 sm:px-5 py-2 gap-2 h-14"
                         style="position:relative">
                        <svg class="w-6 h-4  flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" name="q" value="{{ request('q') }}"
                               placeholder="{{ __('Search for products, brands...') }}" autocomplete="off"
                               class="bg-transparent flex-1 text-xs sm:text-sm text-gray-600 placeholder-gray-400 min-w-0 outline-none"/>
                        <!-- <button type="submit"
                            class="bg-main text-white text-xs font-medium px-1 sm:px-3 py-0.5 rounded-full flex-shrink-0">{{ __('Search') }}</button> -->
                    </div>
                </form>
            </div> --}}

            <a href="{{ route('tenant.storefront.cart') }}"
                class="flex items-center justify-center transition-colors rounded-full w-12 h-12 border hover:bg-gray-200">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="{{ $cartCount > 0 ? '' : 'hidden lg:block' }}">
                    <span id="elora-cart-badge"
                        class=" text-red-500 text-[10px] rounded-full w-full h-[18px] flex items-center justify-center font-semibold leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                    <span class="text-xs hidden lg:block">{{ __('Cart') }}</span>
                </div>
            </a>
            <!-- Favorite -->
            <a href="{{ route('tenant.storefront.favorites') }}"
               class="flex items-center justify-center transition-colors rounded-full w-12 h-12 border hover:bg-gray-200"
               aria-label="{{ __('Favorites') }}">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <button
                class="flex items-center justify-center transition-colors rounded-full w-12 h-12 border hover:bg-gray-200"
                aria-label="{{ __('Share') }}">
                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8 2V10.5M11 4L8 1L5 4M1 9V14C1 14.5304 1.21071 15.0391 1.58579 15.4142C1.96086 15.7893 2.46957 16 3 16H13C13.5304 16 14.0391 15.7893 14.4142 15.4142C14.7893 15.0391 15 14.5304 15 14V9"
                        stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <!-- free shipping par -->
        @if($shippingThreshold > 0)
        <div id="elora-shipping-widget" class="hidden sm:block mb-4 p-3 bg-[#ff4d0016] rounded-2xl border border-[#ff4d00]">
            <p id="elora-shipping-message" class="text-center text-[15px] text-[#FF4D00] font-medium mb-2">
                @if ($remainingForFreeShipping <= 0)
                    {{ __("You've reached free shipping!") }}
                @else
                    {{ __('Add :weight more to qualify for free shipping', ['weight' => $remainingForFreeShipping >= 1000 ? number_format($remainingForFreeShipping / 1000, 2) . __('kg') : number_format($remainingForFreeShipping) . __('g')]) }}
                @endif
            </p>
            <div class="h-2 bg-[#D9D9D9] rounded-full overflow-hidden">
                <div id="elora-shipping-bar" class="h-full bg-[#FF4D00] rounded-full transition-all duration-500" style="width: {{ $shippingPct }}%"></div>
            </div>
        </div>
        @endif

        {{-- Breadcrumb --}}
        <nav aria-label="{{ __('Breadcrumb') }}" class="hidden lg:flex text-[12px] text-[#787878] mb-6 items-center gap-1 flex-wrap">
            <ol class="flex items-center gap-1 flex-wrap list-none p-0 m-0">
                <li><a href="{{ route('tenant.home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                @foreach ($categoryAncestors as $ancestor)
                    <li aria-hidden="true" class="mx-1">&rsaquo;</li>
                    <li><a href="{{ route('tenant.storefront.category', $ancestor->slug) }}" class="hover:underline">
                        {{ $ancestor->translationValue('name') ?? $ancestor->slug }}
                    </a></li>
                @endforeach
                <li aria-hidden="true" class="mx-1">&rsaquo;</li>
                <li aria-current="page" class="text-[#222] truncate max-w-50">{{ $product->translationValue('name') ?? $product->slug }}</li>
            </ol>
        </nav>

        {{-- Main layout --}}
        <div class="flex flex-col lg:grid lg:grid-cols-2 gap-3 lg:gap-10">

            {{-- ── LEFT: Gallery (wire:ignore protects JS-managed DOM) ─── --}}
            <div id="mantiProductMediaSlider" data-media-items='@json($mediaItems)' class="flex flex-col gap-4 lg:gap-6"
                 wire:ignore>

                <div class="relative select-none w-[500px] h-[500px] max-w-full mx-auto">
                    <!-- top left badge -->
                    @if($badgeLabel)
                        <span
                            class="absolute top-0 left-0 z-10 bg-main text-white text-[10px] lg:text-[11px] font-bold px-2.5 py-1 rounded-ee-xl rounded-ss-xl flex items-center gap-1 shrink-0 uppercase tracking-wide">
                        {{ __($badgeLabel) }}
                    </span>
                    @endif
                    <!-- mobile screen bottom left badges -->
                    <div
                        class="flex items-center gap-2 sm:hidden ps-12 absolute bottom-0 left-0 z-10 bg-[#FFB00A] py-2 rounded-se-xl max-w-[80%] overflow-hidden">
                        <span class="text-xs flex items-center gap-1">
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7.652 0.153329C7.14102 -0.0511098 6.57098 -0.0511098 6.06 0.153329L0.987429 2.1819C0.695928 2.29859 0.446069 2.49993 0.27007 2.75996C0.0940715 3.01998 4.72448e-06 3.32677 9.56756e-08 3.64076V9.80761C-0.00010948 10.1217 0.093904 10.4286 0.26991 10.6888C0.445916 10.9489 0.695838 11.1503 0.987429 11.267L6.06057 13.2956C6.50011 13.4717 6.98557 13.4972 7.44114 13.3682C7.2197 13.1322 7.0257 12.8719 6.86286 12.5922C6.69717 12.5923 6.53295 12.5611 6.37886 12.5002L3.80171 11.4693L3.78571 11.463L1.30571 10.471C1.1733 10.418 1.05981 10.3265 0.979867 10.2083C0.89992 10.0902 0.857176 9.95083 0.857143 9.80819V3.64076C0.857143 3.59504 0.861524 3.5499 0.870286 3.50533L6.42743 5.72876V9.33219C6.58938 8.7272 6.88196 8.16506 7.28457 7.68533V5.72876L12.8423 3.5059C12.8507 3.54971 12.8549 3.59466 12.8549 3.64076V6.81219C13.1714 7.01219 13.4594 7.25219 13.712 7.52533V3.64133C13.712 3.32734 13.6179 3.02055 13.4419 2.76053C13.2659 2.5005 13.0161 2.29916 12.7246 2.18247L7.652 0.153329ZM6.37829 0.948758C6.68494 0.826033 7.02706 0.826033 7.33371 0.948758L12.1291 2.86761L10.0691 3.69162L4.796 1.5819L6.37829 0.948758ZM3.64229 2.04361L8.91657 4.15333L6.856 4.9779L1.58229 2.86761L3.64229 2.04361ZM14.2834 10.439C14.2834 11.4241 13.8921 12.3689 13.1955 13.0654C12.499 13.762 11.5542 14.1533 10.5691 14.1533C9.58405 14.1533 8.63931 13.762 7.94275 13.0654C7.24618 12.3689 6.85486 11.4241 6.85486 10.439C6.85486 9.45395 7.24618 8.50921 7.94275 7.81265C8.63931 7.11608 9.58405 6.72476 10.5691 6.72476C11.5542 6.72476 12.499 7.11608 13.1955 7.81265C13.8921 8.50921 14.2834 9.45395 14.2834 10.439ZM9.91429 12.0699L12.7714 9.21276C12.8251 9.15911 12.8552 9.08634 12.8552 9.01047C12.8552 8.9346 12.8251 8.86184 12.7714 8.80819C12.7178 8.75454 12.645 8.7244 12.5691 8.7244C12.4933 8.7244 12.4205 8.75454 12.3669 8.80819L9.712 11.4636L8.77143 10.5225C8.74486 10.4959 8.71333 10.4748 8.67862 10.4605C8.64391 10.4461 8.60671 10.4387 8.56914 10.4387C8.53158 10.4387 8.49438 10.4461 8.45967 10.4605C8.42496 10.4748 8.39342 10.4959 8.36686 10.5225C8.34029 10.549 8.31922 10.5806 8.30484 10.6153C8.29047 10.65 8.28307 10.6872 8.28307 10.7248C8.28307 10.7623 8.29047 10.7995 8.30484 10.8342C8.31922 10.8689 8.34029 10.9005 8.36686 10.927L9.50972 12.0699C9.53625 12.0965 9.56778 12.1176 9.6025 12.132C9.63721 12.1464 9.67442 12.1538 9.712 12.1538C9.74958 12.1538 9.78679 12.1464 9.82151 12.132C9.85622 12.1176 9.88775 12.0965 9.91429 12.0699Z"
                                    fill="#121212"/>
                            </svg>
                            <span class="whitespace-nowrap">{{ __('Free shipping') }}</span>
                        </span>
                        <span class="text-xs flex items-center gap-1">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.6843 4.93158C12.5231 4.74533 13.6156 3.91283 13.7818 3.50283C13.8793 3.26283 13.3931 2.88783 13.2631 2.73658C12.9656 2.39158 13.0856 2.22158 13.2006 1.81033C13.3318 1.34283 13.0206 0.826585 12.5793 0.624085C12.1381 0.421585 11.6106 0.485335 11.1668 0.682835C10.7231 0.880335 10.3443 1.19783 9.97305 1.51033C9.7018 1.33908 9.2693 0.582835 8.5693 1.07408C8.08555 1.41283 8.0418 2.15033 8.1043 2.73908C8.25055 4.09783 8.58555 4.80283 9.2168 4.99283C10.0143 5.23283 10.8968 5.10659 11.6843 4.93158Z"
                                    fill="#121212"/>
                                <path
                                    d="M13.0469 1.02344C12.9406 2.85469 11.1544 4.06969 10.3069 4.60469L10.8569 5.07719C10.8569 5.07719 11.2056 5.08469 11.6831 4.93219C12.5019 4.67219 13.6981 3.93719 13.7806 3.50344C13.9019 2.87219 13.2544 3.00969 13.1081 2.51094C13.0319 2.24719 13.4794 1.74844 13.0469 1.02344ZM9.97438 1.51219C9.97438 1.51219 9.65563 1.18594 9.41938 1.03719C9.30188 1.25844 9.21813 1.49844 9.17688 1.74594C9.10313 2.18094 9.17688 2.79844 9.35063 3.25844C9.37813 3.32969 9.48063 3.31844 9.49188 3.24344C9.64188 2.25469 9.97438 1.51219 9.97438 1.51219Z"
                                    fill="#FFB00A"/>
                                <path
                                    d="M7.74307 4.77056C7.74307 4.77056 3.84432 5.19181 2.08557 8.57681C0.326818 11.9618 1.82182 14.0143 3.40432 14.7618C4.98682 15.5093 8.97432 15.7731 11.5681 15.1581C14.1618 14.5431 14.8106 13.2681 14.6893 12.0368C14.5131 10.2393 12.8431 9.13431 12.8431 9.13431C12.8431 9.13431 12.9093 6.91431 11.1868 5.47306C9.65807 4.19306 7.74307 4.77056 7.74307 4.77056Z"
                                    fill="#121212"/>
                                <path
                                    d="M9.34405 10.4664C8.5078 9.41137 7.5203 9.27012 7.20405 8.89137C7.03155 8.68512 6.9303 8.47637 6.96905 8.21762C7.0103 7.94262 7.32905 7.75262 7.57655 7.70637C7.8653 7.65137 8.55405 7.68012 9.1328 8.23137C9.2703 8.36137 9.2203 8.56387 9.21655 8.74512C9.20655 9.13387 9.7628 9.51137 10.2128 9.18637C10.6641 8.86012 10.3178 8.13387 10.0266 7.78137C9.80655 7.51512 9.00905 6.92887 8.0178 6.83637C7.73905 6.81012 6.6178 6.64387 5.9703 7.87512C5.78405 8.22887 5.7153 9.08387 6.6903 9.80637C6.89405 9.95762 7.95155 10.4951 8.2453 10.8439C8.75405 11.4476 8.4053 11.9789 8.0078 12.0489C6.92405 12.2389 6.3103 11.6526 6.19655 11.3314C6.1153 11.1026 6.2003 10.8551 6.0953 10.6401C5.9878 10.4189 5.7678 10.3314 5.5353 10.4051C4.7728 10.6476 5.0153 11.4814 5.3528 11.9401C5.71405 12.4314 6.1578 12.7276 6.65655 12.8901C8.5178 13.4964 9.4128 12.5389 9.56905 11.8376C9.68405 11.3239 9.67155 10.8789 9.34405 10.4664Z"
                                    fill="#FFB00A"/>
                                <path d="M8.89531 6.12305C7.31156 9.50555 7.03906 13.7768 7.03906 13.7768"
                                      stroke="#FFB00A" stroke-width="0.625" stroke-miterlimit="10"/>
                                <path
                                    d="M10.209 3.99507C11.264 4.33882 11.4977 5.29257 11.4215 5.55257C11.3302 5.85757 10.1615 4.67007 8.42398 4.74132C7.81648 4.76632 7.99273 4.39382 8.27398 4.17882C8.64523 3.89507 9.26898 3.69007 10.209 3.99507Z"
                                    fill="#FFB00A"/>
                                <path
                                    d="M10.209 3.99507C11.264 4.33882 11.4977 5.29257 11.4215 5.55257C11.3302 5.85757 10.1615 4.67007 8.42398 4.74132C7.81648 4.76632 7.99273 4.39382 8.27398 4.17882C8.64523 3.89507 9.26898 3.69007 10.209 3.99507Z"
                                    fill="#FFB00A"/>
                                <path
                                    d="M12.0622 7.35678C12.1947 7.26553 12.6397 7.42303 12.7647 8.29428C12.8259 8.72053 12.8447 9.13303 12.8447 9.13303C12.8447 9.13303 12.3197 8.66178 12.1459 8.33053C11.9272 7.91178 11.8422 7.50678 12.0622 7.35678Z"
                                    fill="#FFB00A"/>
                            </svg>

                            <span class="whitespace-nowrap">{{ __('$20 credit in case of delay') }}</span>
                        </span>
                    </div>
                    <div
                        class="sm:hidden font-bold absolute bottom-0 left-0 z-20 bg-main p-1 rounded-[0%100%0%100%/100%49%51%0%] text-white text-xs">
                        <span class="text-black block">{{ __('Day') }}</span>
                        <span>{{ __('Salary') }}</span>
                        <span class="absolute top-0 right-0"><svg width="19" height="21" viewBox="0 0 19 21" fill="none"
                                                                  xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7.94192 0.000382314C7.76424 -0.000216644 7.58605 -0.000111828 7.40735 0.000696766C6.54723 0.00469481 5.67336 0.0272905 4.79204 0.0599936L5.04702 1.17823C5.96482 1.07446 6.97102 0.997199 7.97714 0.958566C8.52222 0.937632 9.06398 0.926581 9.58947 0.929367C11.166 0.937587 12.5964 1.06997 13.5323 1.43258L13.9324 1.58774L13.7538 1.97789C12.4483 4.82975 12.1464 7.58108 12.351 10.2806C12.4519 7.07688 13.7836 4.2274 15.5319 0.574439C13.153 0.179126 10.6069 0.00923178 7.94192 0.000337246V0.000382314ZM9.20126 1.7429C8.80864 1.74322 8.40839 1.75117 8.00818 1.76653C6.13619 1.83841 4.27072 2.05807 3.05491 2.30991C4.41303 4.82908 5.51169 7.35405 6.22114 9.81554C6.30939 9.8126 6.39772 9.81328 6.48591 9.81756C6.66641 9.82641 6.84492 9.8509 7.01936 9.88899C6.29724 7.58014 5.47472 5.45071 4.45225 3.32451L4.22122 2.84412L4.74617 2.75122C6.0649 2.51771 7.42603 2.42733 8.72149 2.42693C8.80789 2.42693 8.89396 2.42729 8.9797 2.42801C9.80285 2.43519 10.5962 2.47778 11.3312 2.54188L11.8278 2.58523L11.6829 3.06217C10.8429 5.82769 10.6783 8.7977 11.1533 11.6235C11.1613 11.6307 11.1691 11.6381 11.1769 11.6455C11.3482 11.5855 11.5084 11.5243 11.6578 11.4589C11.287 8.43141 11.4513 5.30804 12.8361 2.06117C12.0633 1.86428 10.8853 1.76136 9.59095 1.74519C9.46106 1.74359 9.33116 1.74279 9.20126 1.7429ZM15.7125 2.07465C14.3219 5.01955 13.3342 7.37947 13.1723 9.92713C14.0724 6.92626 15.8123 4.2362 17.7949 2.50792C17.1067 2.33624 16.412 2.19171 15.7125 2.07465ZM2.46226 2.90701C1.61952 3.08077 0.796285 3.30133 0 3.60608C0.387496 3.9421 1.08801 4.56718 1.93793 5.39797C2.32911 4.99534 2.72806 4.59755 3.14417 4.23584C2.92378 3.78939 2.69644 3.34639 2.46226 2.90701ZM8.8422 3.24311C8.23036 3.24015 7.60406 3.25767 6.97551 3.3012C6.90957 3.91582 6.54606 4.49082 6.11189 5.05629C6.80508 6.69455 7.40375 8.36627 7.94273 10.1412C8.76817 10.3847 9.5848 10.6725 10.2463 11.0173C10.0006 9.11004 10.0342 7.15572 10.3532 5.24452C9.7744 4.64661 9.4729 3.98266 9.44873 3.25268C9.24659 3.2473 9.0444 3.24411 8.8422 3.24311ZM17.0188 4.45533C15.9079 5.77154 14.929 7.42004 14.2619 9.21309C15.4867 7.47093 16.9927 6.11205 18.5959 5.15216C18.0736 4.91224 17.5478 4.67994 17.0188 4.45533ZM3.50898 4.99255C2.85339 5.58498 2.20562 6.29605 1.53103 6.97347C2.98151 7.83557 4.21695 8.85651 5.22477 10.0129C5.28735 9.98925 5.35055 9.96801 5.41412 9.94865C4.94253 8.32715 4.29624 6.66787 3.50898 4.99255ZM13.9932 10.5966C13.2566 11.4856 12.5631 11.9549 11.6083 12.3293C11.7157 12.7435 11.5562 13.219 11.189 13.5826C10.973 13.7965 10.7016 14.0403 10.3047 14.1987C10.362 14.2326 10.4204 14.2645 10.4799 14.2943C11.0353 14.5715 12.0166 14.4821 12.9282 14.0675C13.8369 13.6542 14.6488 12.9403 14.9496 12.2171C15.1004 11.8106 14.9928 11.5985 14.7288 11.2876C14.5408 11.0663 14.2663 10.8433 13.9932 10.5967V10.5966ZM6.3529 10.623C5.87156 10.6186 5.38272 10.7575 4.97874 11.0614C4.40418 11.4937 3.96126 12.2646 3.9573 13.5741V13.5831L3.95685 13.592C3.82568 16.3494 4.02873 17.6215 4.431 18.3118C4.78607 18.921 5.37661 19.2126 6.24046 19.749C6.2816 19.6409 6.32446 19.508 6.36467 19.3403C6.46695 18.9136 6.55159 18.3275 6.60522 17.6793C6.71254 16.3829 6.69686 14.8263 6.5174 13.7062L6.42257 13.1137L7.00727 13.2482C8.42748 13.5749 9.23989 13.6135 9.71157 13.5303C10.1833 13.447 10.3432 13.2822 10.6199 13.0081C10.88 12.7506 10.8752 12.658 10.8201 12.5131C10.765 12.3681 10.5439 12.1292 10.1687 11.9006C9.41841 11.4435 8.14344 11.0197 6.9517 10.7036C6.75624 10.6519 6.55508 10.6248 6.3529 10.623ZM14.8476 13.7413C14.3978 14.1794 13.8443 14.5391 13.2629 14.8035C12.3203 15.2322 11.2835 15.4283 10.4234 15.1428C10.1197 15.2559 9.94328 15.3857 9.85429 15.4932C9.73219 15.6407 9.72455 15.7493 9.76592 15.9047C9.8073 16.0601 9.93056 16.2447 10.083 16.3775C10.2354 16.5103 10.4166 16.5792 10.4855 16.5825C12.5876 16.6823 13.4973 16.079 14.6307 15.3793C14.9613 15.1753 15.0878 14.762 15.0561 14.3492C15.0402 14.1429 14.9802 13.9489 14.9109 13.8268C14.8936 13.7956 14.8724 13.7669 14.8476 13.7413ZM7.38219 14.1499C7.50469 15.276 7.50671 16.5898 7.41103 17.746C7.35501 18.4231 7.2684 19.0388 7.15098 19.5287C7.0999 19.7419 7.0437 19.931 6.97578 20.0974L13.5657 19.807C13.4913 19.5308 13.4155 19.2534 13.3397 18.9758C13.0865 19.0601 12.8277 19.1265 12.5652 19.1746L12.5672 19.2074L12.1936 19.2306C11.9636 19.2582 11.7241 19.2734 11.4757 19.2752L7.7085 19.5092L7.82049 18.9878C8.17749 17.3254 8.21042 15.8618 7.99021 14.2605C7.78682 14.2276 7.5841 14.1907 7.38215 14.1499H7.38219ZM8.81843 14.3603C8.99578 15.7948 8.97445 17.1639 8.71035 18.6369L9.57573 18.5831C9.43998 18.3829 9.34672 18.157 9.3017 17.9193C9.24348 17.6102 9.27587 17.2662 9.47807 16.9817C9.48701 16.9691 9.49666 16.957 9.50623 16.9449C9.27057 16.7272 9.07372 16.4479 8.98451 16.1127C8.88954 15.7559 8.94884 15.319 9.23144 14.9776C9.32484 14.8648 9.43773 14.763 9.57029 14.6714C9.45304 14.5779 9.35026 14.4789 9.2615 14.3757C9.12386 14.3766 8.97647 14.3715 8.81843 14.3603ZM14.778 16.2374C13.7244 16.8776 12.5405 17.4896 10.4471 17.3902C10.3788 17.3869 10.3109 17.3776 10.2442 17.3626C10.1888 17.3874 10.1592 17.419 10.1372 17.4501C10.0939 17.5109 10.0683 17.6208 10.0963 17.7696C10.1524 18.0673 10.4344 18.3935 10.6969 18.4241C12.556 18.6403 13.8405 18.0321 14.6205 17.3245C14.9798 16.9985 14.9829 16.7899 14.93 16.563C14.9035 16.4497 14.8451 16.3398 14.778 16.2374ZM14.4778 18.4413C14.353 18.5195 14.2249 18.5924 14.0939 18.6596C14.168 18.9314 14.2423 19.2033 14.3157 19.4751L15.2105 19.3147C14.9796 19.1176 14.7989 18.9124 14.6539 18.7084C14.5891 18.6173 14.532 18.5287 14.4778 18.4413Z"
                                    fill="white"/>
                            </svg></span>
                    </div>
                    <!-- product preview button -->
                    <button id="product-preview-button"
                            class="bg-white w-6 h-6 md:w-12 md:h-12 text-2xl rounded-full flex items-center justify-center absolute top-3 right-3 z-10">
                        <svg
                            class="w-3 h-3 md:w-6 md:h-6" viewBox="0 0 11 11" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 10.4983V7.2176H0.656145V9.37763L3.01827 7.01551L3.48282 7.48006L1.1207 9.84218H3.28073V10.4983H0ZM7.48006 3.48282L7.01551 3.01827L9.37763 0.656145H7.2176V0H10.4983V3.28073H9.84218V1.1207L7.48006 3.48282Z"
                                fill="#242424"/>
                        </svg>
                    </button>
                    <!-- main swiper -->
                    <div class="swiper product-preview-swiper w-full h-full rounded-xl bg-[#E6E6E6]">
                        <div class="swiper-wrapper">
                            @foreach($mediaItems as $idx => $item)
                                @if($item['type'] === 'video')
                                    <div class="swiper-slide flex items-center justify-center bg-[#E6E6E6]">
                                                <video autoplay controls class="w-full h-full object-contain"
                                    src="{{ $item['src'] }}" @if(!empty($item['poster'])) poster="{{ $item['poster'] }}"
                                    @endif muted loop playsinline preload="none"></video>
                                    </div>
                                @else
                                    <div class="swiper-slide flex items-center justify-center bg-[#E6E6E6]">
                                        <img {{ $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy"' }} class="w-full h-full object-contain" src="{{ $item['src'] }}"
                                             alt="{{ $item['alt'] ?? ($product->translationValue('name') ?? '') }}">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <!-- Pagination -->
                        <div
                            class="swiper-pagination absolute !right-0 bottom-0 !left-auto bg-black text-white w-fit rounded-ss-xl px-2 sm:hidden"></div>
                    </div>
                    <!-- Navigation buttons -->
                    <div class="swiper-button-prev w-10 h-10 rounded-full bg-white -translate-x-5 drop-shadow-2xl">
                        <svg class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
                                stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                                stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="swiper-button-next w-10 h-10 rounded-full bg-white translate-x-5 drop-shadow-2xl">
                        <svg class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
                                stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                                stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <!-- thumbs strip -->
                <div class="swiper product-preview-thumbs w-full select-none">
                    <div class="swiper-wrapper">
                        @foreach($mediaItems as $idx => $item)
                            @if($item['type'] === 'video')
                                <div
                                    class="swiper-slide !w-[88px] !h-20 rounded-lg overflow-hidden cursor-pointer relative bg-[#E6E6E6] flex items-center justify-center border-2 border-transparent">
                                    @if(!empty($item['poster']))
                                        <img loading="lazy" class="w-full h-full object-cover opacity-60" src="{{ $item['poster'] }}"
                                             alt="">
                                    @else
                                        <div class="absolute inset-0 bg-[#E6E6E6] opacity-60"></div>
                                    @endif
                                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path d="M5 3l14 9-14 9V3z" fill="#121212"/>
                                        </svg>
                                        <span
                                            class="text-[11px] font-medium tracking-wide text-[#121212] leading-tight">{{ __('Play Video') }}</span>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="swiper-slide !w-[88px] !h-20 rounded-lg overflow-hidden cursor-pointer border-2 border-transparent">
                                    <img loading="lazy" class="w-full h-full object-cover opacity-60" src="{{ $item['src'] }}"
                                         alt="{{ $item['alt'] ?? '' }}">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Desktop details section --}}
                <div class="hidden lg:flex flex-col px-1">
                    <h3 class="font-bold text-3xl text-[#242424] mb-3">{{ __('Product details') }}</h3>
                    <div class="flex flex-col gap-0.5 text-[14px] text-[#808080] mb-5">
                        @if ($primaryCategory)
                            <span>{{ __('Category: :category', ['category' => $primaryCategory->translationValue('name') ?? $primaryCategory->slug]) }}</span>
                        @endif
                        @if ($product->translationValue('description') ?? null)
                            <span class="leading-5">{!! $product->translationValue('description') !!}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── RIGHT: Product info ──────────────────────────────────── --}}
            <div class="flex flex-col gap-3 lg:gap-6">
                {{-- Product name --}}
                <h1 class="text-[#222222] text-[24px] lg:text-[32px] font-medium leading-[24px] lg:leading-[28px] mb-3">
                    <button type="button" onclick="eloraOpenDeliveryModal()"
                            class="px-1 text-xs text-white bg-[#2AAF2F] rounded-3xl inline-flex gap-1 items-center p-1 lg:hidden cursor-pointer">
                        <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0.481 7.998C0.344333 7.998 0.23 7.95 0.138 7.854C0.046 7.758 0 7.639 0 7.497C0 7.355 0.0480001 7.23633 0.144 7.141C0.24 7.04567 0.358667 6.998 0.5 6.998H3.98C4.122 6.998 4.241 7.046 4.337 7.142C4.433 7.238 4.481 7.357 4.481 7.499C4.481 7.641 4.433 7.75967 4.337 7.855C4.241 7.95033 4.12233 7.998 3.981 7.998H0.481ZM3.923 13.271C3.43567 12.7837 3.192 12.1933 3.192 11.5H1.808C1.58467 11.5 1.40633 11.4167 1.273 11.25C1.13967 11.0833 1.09533 10.893 1.14 10.679L1.461 9.498H3.956C4.51467 9.498 4.987 9.30467 5.373 8.918C5.759 8.53133 5.952 8.058 5.952 7.498C5.952 7.19133 5.88533 6.908 5.752 6.648C5.61867 6.388 5.43333 6.17267 5.196 6.002H7.011C7.571 6.002 8.04433 5.80867 8.431 5.422C8.81767 5.03533 9.011 4.56233 9.011 4.003C9.011 3.44367 8.81767 2.97033 8.431 2.583C8.04433 2.19567 7.571 2.002 7.011 2.002H3.519L3.823 0.902C3.88433 0.64 4.01433 0.424333 4.213 0.255C4.40967 0.0850001 4.64733 0 4.926 0H14.98C15.236 0 15.445 0.102667 15.607 0.308C15.7683 0.513333 15.82 0.742 15.762 0.994L15.19 3.5H16.461C16.717 3.5 16.9593 3.55733 17.188 3.672C17.4173 3.786 17.606 3.944 17.754 4.146L19.551 6.544C19.6963 6.73867 19.7907 6.94167 19.834 7.153C19.878 7.36367 19.8813 7.585 19.844 7.817L19.246 10.854C19.2087 11.0487 19.114 11.205 18.962 11.323C18.81 11.441 18.6373 11.5 18.444 11.5H17.961C17.961 12.1927 17.7183 12.7827 17.233 13.27C16.7477 13.7573 16.1577 14.0007 15.463 14C14.7683 13.9993 14.178 13.756 13.692 13.27C13.2053 12.7847 12.962 12.1947 12.962 11.5H8.192C8.192 12.1927 7.94933 12.7827 7.464 13.27C6.97867 13.7573 6.38867 14.0007 5.694 14C4.99933 13.9993 4.409 13.757 3.923 13.271ZM2.481 4.503C2.339 4.503 2.22 4.455 2.124 4.359C2.028 4.263 1.98033 4.144 1.981 4.002C1.98167 3.86 2.02933 3.74133 2.124 3.646C2.21867 3.55067 2.33767 3.503 2.481 3.503H6.981C7.12233 3.503 7.241 3.551 7.337 3.647C7.433 3.743 7.481 3.86167 7.481 4.003C7.481 4.14433 7.433 4.26333 7.337 4.36C7.241 4.45667 7.12233 4.50433 6.981 4.503H2.481ZM5.692 13C6.10333 13 6.45633 12.853 6.751 12.559C7.045 12.2643 7.192 11.9113 7.192 11.5C7.192 11.0887 7.045 10.7357 6.751 10.441C6.457 10.1463 6.104 9.99933 5.692 10C5.28 10.0007 4.927 10.1477 4.633 10.441C4.339 10.7357 4.192 11.0887 4.192 11.5C4.192 11.9113 4.339 12.2643 4.633 12.559C4.92767 12.853 5.28067 13 5.692 13ZM15.462 13C15.8733 13 16.226 12.853 16.52 12.559C16.814 12.2643 16.961 11.9113 16.961 11.5C16.961 11.0887 16.814 10.7357 16.52 10.441C16.226 10.1463 15.873 9.99933 15.461 10C15.049 10.0007 14.6963 10.1477 14.403 10.441C14.1083 10.7357 13.961 11.0887 13.961 11.5C13.961 11.9113 14.108 12.2643 14.402 12.559C14.6967 12.853 15.05 13 15.462 13ZM14.078 8.25H18.731L18.907 7.36L16.769 4.5H14.951L14.078 8.25Z"
                                fill="#FDFDFD"/>
                        </svg>

                        {{ __('Fastest delivery') }}: {{ $deliveryMinDays }}–{{ $deliveryMaxDays }} {{ __('days') }}
                    </button> {{ $product->translationValue('name') ?? $product->slug }}
                </h1>

                {{-- rating row --}}
                <div class="flex gap-2 lg:gap-4 items-center -mt-4">
                    <p class="text-base flex items-center gap-1">
                        <svg width="17" height="17" viewBox="0 0 6 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M2.75622 4.3367L1.29019 5.22197C1.24711 5.24324 1.20713 5.25185 1.17024 5.24781C1.13362 5.24351 1.09795 5.23085 1.06322 5.20985C1.02822 5.18831 1.00183 5.15789 0.98406 5.11858C0.96629 5.07927 0.964675 5.03632 0.979214 4.98975L1.36935 3.32986L0.0789997 2.21116C0.0426519 2.18154 0.0186893 2.14614 0.00711185 2.10494C-0.0044656 2.06375 -0.00190777 2.0243 0.0147853 1.98661C0.0314784 1.94892 0.0536909 1.91795 0.0814229 1.89372C0.109424 1.8703 0.147118 1.85441 0.194505 1.84606L1.8972 1.69744L2.56115 0.125602C2.57946 0.0811769 2.60585 0.0491368 2.64031 0.0294821C2.67477 0.00982735 2.71341 0 2.75622 0C2.79903 0 2.8378 0.00982735 2.87253 0.0294821C2.90726 0.0491368 2.93351 0.0811769 2.95128 0.125602L3.61524 1.69744L5.31753 1.84606C5.36518 1.85414 5.40301 1.87016 5.43101 1.89412C5.45901 1.91782 5.48136 1.94865 5.49805 1.98661C5.51448 2.0243 5.5169 2.06375 5.50532 2.10494C5.49375 2.14614 5.46978 2.18154 5.43344 2.21116L4.14309 3.32986L4.53322 4.98975C4.5483 5.03579 4.54682 5.0786 4.52878 5.11817C4.51074 5.15775 4.48422 5.18818 4.44922 5.20945C4.41475 5.23099 4.37908 5.24378 4.34219 5.24781C4.30558 5.25185 4.26573 5.24324 4.22265 5.22197L2.75622 4.3367Z"
                                fill="#FFE100"/>
                        </svg>
                        {{ $avgRating }}
                    </p>
                    <p class="text-[#8F8F8F] text-base">(+{{ $reviewCount }})</p>

                    <p id="elora-weight-display" class="text-main text-base">{{ $weightDisplay }}</p>
                    @if ($pricing['is_flash_sale'] && ($pricing['flash_sale']?->end_date ?? null))
                        <p class="text-sm bg-main rounded-3xl p-1 flex items-center gap-1 text-white">
                            <svg width="16"
                                 height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.41875 7.36875V4.66875C8.41875 4.45625 8.34675 4.27825 8.20275 4.13475C8.05875 3.99125 7.88075 3.91925 7.66875 3.91875C7.45675 3.91825 7.27875 3.99025 7.13475 4.13475C6.99075 4.27925 6.91875 4.45725 6.91875 4.66875V7.65C6.91875 7.75 6.9375 7.847 6.975 7.941C7.0125 8.035 7.06875 8.11925 7.14375 8.19375L9.24375 10.2938C9.38125 10.4313 9.55625 10.5 9.76875 10.5C9.98125 10.5 10.1562 10.4313 10.2938 10.2938C10.4313 10.1562 10.5 9.98125 10.5 9.76875C10.5 9.55625 10.4313 9.38125 10.2938 9.24375L8.41875 7.36875ZM5.03475 13.8848C4.21575 13.5283 3.50325 13.047 2.89725 12.441C2.29125 11.835 1.81 11.1225 1.4535 10.3035C1.097 9.4845 0.91875 8.60625 0.91875 7.66875C0.91875 6.73125 1.097 5.85325 1.4535 5.03475C1.81 4.21625 2.291 3.50375 2.8965 2.89725C3.502 2.29075 4.2145 1.8095 5.034 1.4535C5.8535 1.0975 6.73175 0.91925 7.66875 0.91875C8.60575 0.91825 9.484 1.0965 10.3035 1.4535C11.123 1.8105 11.8355 2.29175 12.441 2.89725C13.0465 3.50275 13.5278 4.21525 13.8848 5.03475C14.2418 5.85425 14.4198 6.73225 14.4188 7.66875C14.4178 8.60525 14.2397 9.4835 13.8848 10.3035C13.5298 11.1235 13.0485 11.836 12.441 12.441C11.8335 13.046 11.121 13.5273 10.3035 13.8848C9.486 14.2423 8.60775 14.4202 7.66875 14.4188C6.72975 14.4173 5.85175 14.2393 5.03475 13.8848ZM0.20625 3.39375C0.06875 3.25625 0 3.08125 0 2.86875C0 2.65625 0.06875 2.48125 0.20625 2.34375L2.34375 0.20625C2.48125 0.0687499 2.65625 0 2.86875 0C3.08125 0 3.25625 0.0687499 3.39375 0.20625C3.53125 0.34375 3.6 0.51875 3.6 0.73125C3.6 0.94375 3.53125 1.11875 3.39375 1.25625L1.25625 3.39375C1.11875 3.53125 0.94375 3.6 0.73125 3.6C0.51875 3.6 0.34375 3.53125 0.20625 3.39375ZM15.1313 3.39375C14.9938 3.53125 14.8188 3.6 14.6063 3.6C14.3938 3.6 14.2188 3.53125 14.0812 3.39375L11.9438 1.25625C11.8063 1.11875 11.7375 0.94375 11.7375 0.73125C11.7375 0.51875 11.8063 0.34375 11.9438 0.20625C12.0813 0.0687499 12.2563 0 12.4688 0C12.6812 0 12.8563 0.0687499 12.9938 0.20625L15.1313 2.34375C15.2688 2.48125 15.3375 2.65625 15.3375 2.86875C15.3375 3.08125 15.2688 3.25625 15.1313 3.39375ZM7.66875 12.9188C9.13125 12.9188 10.372 12.4095 11.391 11.391C12.41 10.3725 12.9193 9.13175 12.9188 7.66875C12.9183 6.20575 12.409 4.96525 11.391 3.94725C10.373 2.92925 9.13225 2.41975 7.66875 2.41875C6.20525 2.41775 4.96475 2.92725 3.94725 3.94725C2.92975 4.96725 2.42025 6.20775 2.41875 7.66875C2.41725 9.12975 2.92675 10.3705 3.94725 11.391C4.96775 12.4115 6.20825 12.9208 7.66875 12.9188Z"
                                    fill="#FDFDFD"/>
                            </svg>
                            <span id="product-flash-countdown"
                                  data-countdown="{{ $pricing['flash_sale']->end_date->timestamp }}">--:--:--</span></p>
                    @endif
                </div>

                {{-- Price block --}}
                <div class="flex flex-wrap items-end gap-2.5">
                    <span class="text-[#222] text-[28px] lg:text-[32px] font-bold leading-none tracking-tight">
                        {{ $symbol }}<span id="elora-sell-price">{{ $displaySell }}</span>
                    </span>
                    <span id="elora-real-price"
                          class="{{ $hasDiscount ? '' : 'hidden' }} text-[#989898] text-[16px] font-medium line-through leading-none">
                        {{ $symbol }}<span id="elora-real-price-val">{{ $displayReal }}</span>
                    </span>
                    <span id="elora-discount-badge"
                          class="{{ $hasDiscount ? '' : 'hidden' }} bg-primary text-white text-[11px] font-bold px-2 py-1 rounded-md leading-none">
                        -<span id="elora-discount-pct">{{ $discountPct }}</span>%
                    </span>
                </div>

                {{-- Stock badge --}}
                <div id="elora-stock-badge" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[12px] font-semibold w-fit {{ $isInStock ? 'bg-[#e8fbe8] text-[#166534]' : 'bg-red-100 text-red-700' }}">
                    <span id="elora-stock-dot" class="w-2 h-2 rounded-full {{ $isInStock ? 'bg-[#22c55e]' : 'bg-red-500' }}"></span>
                    <span id="elora-stock-text">{{ $isInStock ? __('In Stock') : __('Out of Stock') }}</span>
                    @if ($manageStock && $isInStock && $stockValue <= 10)
                        <span class="text-[10px] opacity-70">({{ $stockValue }} {{ __('left') }})</span>
                    @endif
                </div>

                {{-- Badge / estimated delivery --}}
                <div class="flex items-center gap-2 ">
                    <svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M15.75 16.75C16.8546 16.75 17.75 15.8546 17.75 14.75C17.75 13.6454 16.8546 12.75 15.75 12.75C14.6454 12.75 13.75 13.6454 13.75 14.75C13.75 15.8546 14.6454 16.75 15.75 16.75Z"
                            stroke="#2AAF2F" stroke-width="1.5"/>
                        <path
                            d="M5.75 16.75C6.85457 16.75 7.75 15.8546 7.75 14.75C7.75 13.6454 6.85457 12.75 5.75 12.75C4.64543 12.75 3.75 13.6454 3.75 14.75C3.75 15.8546 4.64543 16.75 5.75 16.75Z"
                            stroke="#2AAF2F" stroke-width="1.5"/>
                        <path
                            d="M3.75 14.722C2.653 14.668 1.97 14.505 1.482 14.018C0.994 13.531 0.832 12.847 0.778 11.75M7.75 14.75H13.75M17.75 14.722C18.847 14.668 19.53 14.505 20.018 14.018C20.75 13.285 20.75 12.107 20.75 9.75V7.75H16.05C15.305 7.75 14.933 7.75 14.632 7.652C14.3318 7.55447 14.059 7.38728 13.8359 7.16412C13.6127 6.94096 13.4455 6.66815 13.348 6.368C13.25 6.067 13.25 5.695 13.25 4.95C13.25 3.833 13.25 3.275 13.103 2.823C12.9567 2.37277 12.7059 1.96356 12.3712 1.62882C12.0364 1.29408 11.6272 1.0433 11.177 0.897C10.725 0.75 10.167 0.75 9.05 0.75H0.75M0.75 4.75H6.75M0.75 7.75H4.75"
                            stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path
                            d="M13.25 2.75H15.071C16.527 2.75 17.254 2.75 17.846 3.104C18.439 3.457 18.784 4.098 19.474 5.38L20.75 7.75"
                            stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                    <button type="button" onclick="eloraOpenDeliveryModal()"
                            class="text-sm text-[#2AAF2F] hover:underline cursor-pointer">{{ __('Estimated delivery') }}
                        : {{ $deliveryFrom }} - {{ $deliveryTo }}</button>
                </div>

                {{-- Shipping countries --}}
                @if($shippingCountries->isNotEmpty())
                <div class="border border-neutral-200 rounded-xl p-3 flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                        <svg class="w-4 h-4 text-[#2AAF2F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        <span>{{ __('Ships to') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($shippingCountries as $country)
                        <span class="inline-flex items-center gap-1 bg-neutral-100 text-neutral-700 text-xs px-2 py-1 rounded-full">
                            @if($country->flag_emoji)<span>{{ $country->flag_emoji }}</span>@endif
                            {{ $country->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- <!-- color variant selector -->
                <div>
                    <h3 class="font-bold text-sm">Color: white</h3>
                    <ul class="flex items-center gap-3 mt-2">
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-white border border-[#8F8F8F] text-[#8F8F8F] rounded-sm text-sm min-w-12">blue</button>
                        </li>
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-main border border-main text-white rounded-sm text-sm min-w-12">white</button>
                        </li>
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-white border border-[#8F8F8F] text-[#8F8F8F] rounded-sm text-sm min-w-12">red</button>
                        </li>
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-white border border-[#8F8F8F] text-[#8F8F8F] rounded-sm text-sm min-w-12">gray</button>
                        </li>
                    </ul>
                </div>
                <!-- size variant selector -->
                <div>
                    <h3 class="font-bold text-sm">Size: M</h3>
                    <ul class="flex items-center gap-3 mt-2">
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-white border border-[#8F8F8F] text-[#8F8F8F] rounded-sm text-sm min-w-12">S</button>
                        </li>
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-main border border-main text-white rounded-sm text-sm min-w-12">M</button>
                        </li>
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-white border border-[#8F8F8F] text-[#8F8F8F] rounded-sm text-sm min-w-12">L</button>
                        </li>
                        <li>
                            <button type="button"
                                class="px-2 py-1 text-center bg-white border border-[#8F8F8F] text-[#8F8F8F] rounded-sm text-sm min-w-12">XL</button>
                        </li>
                    </ul>
                </div>--}}


                <!-- free shipping par -->
                @if($shippingThreshold > 0)
                <div id="elora-shipping-widget-mobile" class="sm:hidden p-3 bg-[#ff4d0016] -mx-6">
                    <p id="elora-shipping-message-mobile" class="text-center text-sm text-[#FF4D00] font-medium mb-2">
                        @if ($remainingForFreeShipping <= 0)
                            {{ __("You've reached free shipping!") }}
                        @else
                            {{ __('Add :weight more to qualify for free shipping', ['weight' => $remainingForFreeShipping >= 1000 ? number_format($remainingForFreeShipping / 1000, 2) . __('kg') : number_format($remainingForFreeShipping) . __('g')]) }}
                        @endif
                    </p>
                    <div class="h-2 bg-[#D9D9D9] rounded-full overflow-hidden mx-6">
                        <div id="elora-shipping-bar-mobile" class="h-full bg-[#FF4D00] rounded-full transition-all duration-500" style="width: {{ $shippingPct }}%"></div>
                    </div>
                </div>
                @endif


                {{-- Variant selector --}}
                @if ($variants->isNotEmpty())
                    <div class="flex flex-col gap-6 mb-6 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-[14px] font-semibold text-[#222]">{{ __('Choose option') }}</h3>
                            @if ($activeVariant)
                                <span id="elora-variant-title"
                                      class="text-[13px] text-[#666] font-medium">{{ $activeVariant->display_label ?? '' }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($variants as $variant)
                                <button type="button"
                                        data-variant-id="{{ $variant->id }}"
                                        onclick="eloraSelectVariant({{ $variant->id }})"
                                        class="flex flex-col items-center gap-1 cursor-pointer transition-all active:scale-95">
                                    @php
                                        $vThumb = $variant->thumbnail_url ?? $variant->centralVariant?->thumbnail_url ?? null;
                                        $vTitle = $variant->display_label ?? __('Not available');
                                        $isActive = $activeVariant && $activeVariant->id === $variant->id;
                                        $variantPricing = $product->storefrontPricing($variant);
                                        $vSell = (float) $variantPricing['current_price'];
                                        $vDisplay = number_format($vSell * $rate, 2);
                                        $vIsInStock = !$manageStock || (($variant->stock ?? 9999) > 0);
                                    @endphp
                                    <div data-variant-ring
                                         class="w-[60px] h-[60px] rounded-[8px] border-2 {{ $isActive ? 'border-[#222]' : 'border-transparent ring-1 ring-[#e5e5e5]' }} overflow-hidden bg-white p-[2px] {{ !$vIsInStock ? 'opacity-40' : '' }} relative">
                                        @if ($vThumb)
                                            <img loading="lazy" src="{{ $vThumb }}" alt="{{ $vTitle }}"
                                                 class="w-full h-full object-cover rounded-[4px]">
                                        @else
                                            <div
                                                class="w-full h-full flex items-center justify-center text-[9px] text-[#888] text-center leading-tight p-0.5">
                                                {{ $vTitle }}</div>
                                        @endif
                                        @if (!$vIsInStock)
                                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                <div class="w-[110%] h-px bg-[#aaa] rotate-45 origin-center"></div>
                                            </div>
                                        @endif
                                    </div>
                                    <span
                                        class="text-[10px] {{ $vIsInStock ? 'text-[#555]' : 'text-[#aaa] line-through' }} font-medium">{{ $symbol }}{{ $vDisplay }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Qty controls --}}
                <div
                    class="hidden sm:flex items-center justify-between sm:justify-start sm:gap-6 p-3 sm:p-0 bg-gray-50 sm:bg-transparent rounded-xl">
                    <span class="text-[14px] font-semibold text-[#222] ml-1 sm:ml-0">{{ __('Qty') }}</span>
                    <div
                        class="flex items-center bg-white border border-[#e5e5e5] rounded-full overflow-hidden shadow-sm h-[40px]">
                        <button type="button" onclick="eloraDecrementQty()"
                                class="w-[40px] h-full flex items-center justify-center text-[#222] text-lg hover:bg-gray-50 active:bg-gray-100 transition">
                            −
                        </button>
                        <input id="elora-qty-input" oninput="eloraSetQty(this.value)" type="number" min="1"
                               value="{{ $qty }}"
                               class="w-[48px] h-full text-center text-[14px] font-semibold text-[#222] outline-none border-x border-[#f0f0f0]">
                        <button type="button" onclick="eloraIncrementQty()"
                                class="w-[40px] h-full flex items-center justify-center text-[#222] text-lg hover:bg-gray-50 active:bg-gray-100 transition">
                            +
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="hidden sm:flex items-center gap-3 mb-8">
                    <button id="elora-add-to-cart-btn" type="button" onclick="eloraAddToCart()"
                            @if (!$isInStock) disabled @endif
                            class="bg-[#242424] text-white py-3.5 flex-1 rounded-full text-[15px] font-bold shadow-[0_4px_12px_rgba(0,0,0,0.1)] hover:bg-black active:scale-[0.98] transition-all w-full flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <span id="elora-add-to-cart-label">{{ __('Add to Cart') }}</span>
                    </button>
                    {{-- Favorite / heart button --}}
                    @php
                        $favData = json_encode([
                            'id'        => $product->id,
                            'name'      => $product->translationValue('name') ?? $product->slug,
                            'image'     => $mediaItems->first()['src'] ?? null,
                            'url'       => url()->current(),
                            'price'     => $displaySell,
                            'old_price' => $displayReal,
                            'discount'  => $hasDiscount && $discountPct ? '-' . $discountPct . '%' : null,
                            'badge'     => $badgeLabel,
                            'rating'    => $avgRating,
                            'reviews'   => $reviewCount,
                            'weight'    => $weightDisplay,
                            'added'     => now()->timestamp,
                        ]);
                        $isLoggedIn = auth()->guard('storefront')->check();
                    @endphp
                    <button type="button"
                            id="elora-fav-btn"
                            onclick="eloraHeartToggle(this)"
                            data-fav="{{ $favData }}"
                            data-logged-in="{{ $isLoggedIn ? 'true' : 'false' }}"
                            data-product-id="{{ $product->id }}"
                            aria-label="{{ __('Add to Favorites') }}"
                            class="w-12 h-12 bg-white rounded-full flex items-center justify-center z-10 transition-transform hover:scale-110"
                            style="box-shadow:0 4px 4px rgba(0,0,0,.15)">
                        {{-- outline heart (default) --}}
                        <svg id="elora-fav-icon-outline" class="w-3 h-3 sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round"/>
                        </svg>
                        {{-- filled heart (when favorited) --}}
                        <svg id="elora-fav-icon-filled" class="w-3 h-3 sm:w-5 sm:h-5 hidden" viewBox="0 0 24 24" fill="#FF4D00">
                            <path
                                d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z"/>
                        </svg>
                    </button>
                    <!-- share button -->
                    <button type="button" onclick="eloraOpenShareModal()"
                            class="w-12 h-12 bg-white rounded-full flex items-center justify-center z-10
                        transition-transform hover:scale-110" style="box-shadow:0 4px 4px rgba(0,0,0,.15)">
                        <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8 2V10.5M11 4L8 1L5 4M1 9V14C1 14.5304 1.21071 15.0391 1.58579 15.4142C1.96086 15.7893 2.46957 16 3 16H13C13.5304 16 14.0391 15.7893 14.4142 15.4142C14.7893 15.0391 15 14.5304 15 14V9"
                                stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <!-- mobile screen actions -->
                <div
                    class="flex items-center gap-3 sm:hidden fixed bottom-0 left-0 right-0 pt-2 pb-6 px-4 bg-white border-t border-gray-200 z-50">
                    <div
                        class="flex items-center justify-between sm:justify-start sm:gap-6 p-3 sm:p-0 bg-gray-50 sm:bg-transparent rounded-xl">
                        <div
                            class="flex items-center bg-white border border-[#e5e5e5] rounded-full overflow-hidden shadow-sm h-[40px]">
                            <button type="button" onclick="eloraDecrementQty()"
                                    class="w-[30px] h-full flex items-center justify-center text-[#222] text-lg hover:bg-gray-50 active:bg-gray-100 transition">
                                −
                            </button>
                            <input id="elora-qty-input-mobile" oninput="eloraSetQty(this.value)" type="number" min="1"
                                   value="{{ $qty }}"
                                   class="w-[38px] h-full text-center text-[14px] font-semibold text-[#222] outline-none border-x border-[#f0f0f0]">
                            <button type="button" onclick="eloraIncrementQty()"
                                    class="w-[30px] h-full flex items-center justify-center text-[#222] text-lg hover:bg-gray-50 active:bg-gray-100 transition">
                                +
                            </button>
                        </div>
                    </div>
                    <button id="elora-mobile-add-to-cart-btn" type="button" onclick="eloraAddToCart()"
                            @if (!$isInStock) disabled @endif
                            class="bg-main text-white py-3.5 flex-1 rounded-full text-[15px] font-bold shadow-[0_4px_12px_rgba(0,0,0,0.1)] hover:bg-black active:scale-[0.98] transition-all w-full flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8.25 7.75H12.25M10.25 5.75V9.75M1.46 3.15H16.674C18.052 3.15 19.047 4.42 18.669 5.698L17.015 11.298C16.76 12.158 15.946 12.75 15.02 12.75H5.862C4.935 12.75 4.12 12.157 3.866 11.298L1.46 3.15ZM1.46 3.15L0.75 0.75M14.25 18.75C14.6478 18.75 15.0294 18.592 15.3107 18.3107C15.592 18.0294 15.75 17.6478 15.75 17.25C15.75 16.8522 15.592 16.4706 15.3107 16.1893C15.0294 15.908 14.6478 15.75 14.25 15.75C13.8522 15.75 13.4706 15.908 13.1893 16.1893C12.908 16.4706 12.75 16.8522 12.75 17.25C12.75 17.6478 12.908 18.0294 13.1893 18.3107C13.4706 18.592 13.8522 18.75 14.25 18.75ZM6.25 18.75C6.64782 18.75 7.02936 18.592 7.31066 18.3107C7.59196 18.0294 7.75 17.6478 7.75 17.25C7.75 16.8522 7.59196 16.4706 7.31066 16.1893C7.02936 15.908 6.64782 15.75 6.25 15.75C5.85218 15.75 5.47064 15.908 5.18934 16.1893C4.90804 16.4706 4.75 16.8522 4.75 17.25C4.75 17.6478 4.90804 18.0294 5.18934 18.3107C5.47064 18.592 5.85218 18.75 6.25 18.75Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <span id="elora-mobile-add-to-cart-label">{{ __('Add to Cart') }}</span>
                    </button>
                </div>

                {{-- Trust badge --}}
                <div class="grid grid-cols-4  gap-4">
                    <!-- trust card 1 -->
                    <div class="border border-[#EEEEEE] rounded-lg px-4 py-2 col-span-2 order-3 lg:order-first">
                        <h3 class="text-[14px] font-bold text-[#222] lg:mb-2">{{ __('Payment methods') }}:</h3>
                        <div class="flex items-center gap-3 lg:py-2">
                            <!-- apple pay svg -->
                            <svg width="56" height="37" viewBox="0 0 56 37" fill="none"
                                 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <rect width="56" height="37" fill="url(#pattern0_23_12117)"/>
                                <defs>
                                    <pattern id="pattern0_23_12117" patternContentUnits="objectBoundingBox" width="1"
                                             height="1">
                                        <use xlink:href="#image0_23_12117"
                                             transform="matrix(0.00833333 0 0 0.0126126 0 -0.0045045)"/>
                                    </pattern>
                                    <image id="image0_23_12117" width="120" height="80" preserveAspectRatio="none"
                                           xlink:href="data:image/png;base64,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"/>
                                </defs>
                            </svg>
                            <!-- fawry pay svg -->
                            <svg width="56" height="38" viewBox="0 0 56 38" fill="none"
                                 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <rect width="56" height="38" fill="url(#pattern0_23_12118)"/>
                                <defs>
                                    <pattern id="pattern0_23_12118" patternContentUnits="objectBoundingBox" width="1"
                                             height="1">
                                        <use xlink:href="#image0_23_12118"
                                             transform="matrix(0.00837743 0 0 0.0123457 -0.0026455 0)"/>
                                    </pattern>
                                    <image id="image0_23_12118" width="120" height="81" preserveAspectRatio="none"
                                           xlink:href="data:image/png;base64,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"/>
                                </defs>
                            </svg>
                            <!-- payment mastercard svg -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 42" width="72" height="42">
                                <rect width="72" height="42" rx="5" fill="#fff"/>
                                <rect width="72" height="42" rx="5" fill="none" stroke="#E0E0E0" stroke-width="1"/>
                                <circle cx="27" cy="21" r="12" fill="#EB001B"/>
                                <circle cx="45" cy="21" r="12" fill="#F79E1B"/>
                                <path d="M36 11.8a12 12 0 0 1 0 18.4A12 12 0 0 1 36 11.8z" fill="#FF5F00"/>
                            </svg>
                            <!-- payment visa svg  -->
                            <svg width="56" height="37" viewBox="0 0 56 37" fill="none"
                                 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <rect width="56" height="37" fill="url(#pattern0_23_12115)"/>
                                <defs>
                                    <pattern id="pattern0_23_12115" patternContentUnits="objectBoundingBox" width="1"
                                             height="1">
                                        <use xlink:href="#image0_23_12115"
                                             transform="matrix(0.00833333 0 0 0.0126126 0 -0.0045045)"/>
                                    </pattern>
                                    <image id="image0_23_12115" width="120" height="80" preserveAspectRatio="none"
                                           xlink:href="data:image/png;base64,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"/>
                                </defs>
                            </svg>
                        </div>
                    </div>
                    <!-- trust card 2 -->
                    <div class="border border-[#EEEEEE] rounded-lg px-4 py-2 col-span-4 lg:col-span-2 order-2">
                        <h3 class="text-[14px] font-bold text-[#2AAF2F] mb-2 flex items-center gap-1">
                            <svg width="24"
                                 height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 4.65V12C3 19.35 12 22.5 12 22.5C12 22.5 21 19.35 21 12V4.65L12 1.5L3 4.65Z"
                                      stroke="#2AAF2F" stroke-width="2" stroke-linecap="square"/>
                                <path d="M8.17188 11.1718L10.9989 13.9998L16.6559 8.34277" stroke="#2AAF2F"
                                      stroke-width="2" stroke-linecap="square"/>
                            </svg>
                            {{ __('Privacy & Secure:') }}</h3>
                            <div class="flex items-center gap-2">
                                <ul class="flex items-center gap-2 lg:block flex-1">
                                    <li class="flex items-center gap-2 text-[10px] lg:text-sm flex-1">
                                        <svg width="11" height="8"
                                             viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        {{ __('Secure payment') }}
                                    </li>
                                    <li class="flex items-center gap-2 text-[10px] lg:text-sm flex-1">
                                        <svg width="11" height="8"
                                            viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                            {{ __('Privacy protection') }}
                                    </li>
                                </ul>
                                <span class="lg:hidden" onclick="eloraOpenPrivacyModal()"><svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 11L6 6L1 1" stroke="#8F8F8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                    </div>
                    <!-- trust card 3 -->
                    <div class="border border-[#EEEEEE] rounded-lg px-4 py-2 col-span-4 lg:col-span-2 order-first lg:order-3">
                        <h3 class="text-[14px] font-bold text-[#2AAF2F] mb-2 flex items-center gap-1">
                            <svg width="22"
                                 height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M18.25 14.25C18.25 14.913 17.9866 15.5489 17.5178 16.0178C17.0489 16.4866 16.413 16.75 15.75 16.75C15.087 16.75 14.4511 16.4866 13.9822 16.0178C13.5134 15.5489 13.25 14.913 13.25 14.25C13.25 13.587 13.5134 12.9511 13.9822 12.4822C14.4511 12.0134 15.087 11.75 15.75 11.75C16.413 11.75 17.0489 12.0134 17.5178 12.4822C17.9866 12.9511 18.25 13.587 18.25 14.25ZM8.25 14.25C8.25 14.913 7.98661 15.5489 7.51777 16.0178C7.04893 16.4866 6.41304 16.75 5.75 16.75C5.08696 16.75 4.45107 16.4866 3.98223 16.0178C3.51339 15.5489 3.25 14.913 3.25 14.25C3.25 13.587 3.51339 12.9511 3.98223 12.4822C4.45107 12.0134 5.08696 11.75 5.75 11.75C6.41304 11.75 7.04893 12.0134 7.51777 12.4822C7.98661 12.9511 8.25 13.587 8.25 14.25Z"
                                    stroke="#2AAF2F" stroke-width="1.5"/>
                                <path
                                    d="M13.25 14.25H8.25M18.25 14.25H19.013C19.233 14.25 19.343 14.25 19.435 14.238C19.766 14.1968 20.0739 14.0464 20.3099 13.8106C20.5459 13.5748 20.6965 13.267 20.738 12.936C20.75 12.843 20.75 12.733 20.75 12.513V9.75C20.75 8.02609 20.0652 6.37279 18.8462 5.15381C17.6272 3.93482 15.9739 3.25 14.25 3.25M0.75 0.75H10.75C12.164 0.75 12.871 0.75 13.31 1.19C13.75 1.628 13.75 2.335 13.75 3.75V12.25M0.75 9.5V11.75C0.75 12.685 0.75 13.152 0.951 13.5C1.08265 13.728 1.27199 13.9174 1.5 14.049C1.848 14.25 2.315 14.25 3.25 14.25M0.75 3.75H6.75M0.75 6.75H4.75"
                                    stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round"/>
                            </svg>

                            {{ __('Free returns:') }}</h3>
                            <div class="flex gap-2 items-center">
                        <ul class="flex lg:block items-center gap-2 flex-1">
                            <li class="flex items-center gap-2 text-[10px] lg:text-sm flex-1">
                                <svg width="11" height="8"
                                     viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ __('Refund for lost packages') }}
                            </li>
                            <li class="flex items-center gap-2 text-[10px] lg:text-sm flex-1">
                                <svg width="11" height="8"
                                     viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ __('Coupon for late delivery') }}
                            </li>
                        </ul>
                                      <span class="lg:hidden" onclick="eloraOpenPrivacyModal()"><svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 11L6 6L1 1" stroke="#8F8F8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        </span>
                        </div>
                    </div>
                    <!-- trust card 4 -->
                    <div class="border border-[#EEEEEE] rounded-lg px-4 py-2 col-span-2 order-4 lg:order-4">
                        <h3 class="text-[14px] font-bold text-[#2AAF2F] mb-2 flex items-center gap-1">
                            <svg width="21"
                                 height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M0.75 5.75V11.75C0.75 15.521 0.75 17.407 1.92 18.578C3.093 19.75 4.979 19.75 8.75 19.75H12.25M0.75 5.75L2.118 3.565C2.979 2.189 3.411 1.501 4.089 1.125C4.767 0.75 5.579 0.75 7.203 0.75H13.353C15.013 0.75 15.842 0.75 16.53 1.139C17.218 1.529 17.644 2.24 18.498 3.663L19.75 5.75M0.75 5.75H19.75M19.75 12.25V5.75M10.25 5.75V0.75M14.25 12.75C14.25 12.75 11.75 14.591 11.75 15.25C11.75 15.909 14.25 17.75 14.25 17.75M12.25 15.25H17.5C18.0967 15.25 18.669 15.4871 19.091 15.909C19.5129 16.331 19.75 16.9033 19.75 17.5C19.75 18.0967 19.5129 18.669 19.091 19.091C18.669 19.5129 18.0967 19.75 17.5 19.75H16.75M8.25 8.75H12.25"
                                    stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round"/>
                            </svg>

                            {{ __('Free returns:') }}</h3>
                            <div class="flex items-center gap-2">

                                <ul class="flex-1">
                                    <li class="flex items-center gap-2 text-[10px] lg:text-sm">
                                <svg width="11" height="8"
                                     viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ __('Easy and free return in 15 days.') }}
                            </li>

                        </ul>
                                          <span class="lg:hidden" onclick="eloraOpenPrivacyModal()"><svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 11L6 6L1 1" stroke="#8F8F8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- mobile details section --}}
        <div class="flex lg:hidden flex-col px-1 mt-4">
            <h3 class="font-bold text-lg text-[#242424] mb-3">{{ __('Product details') }}</h3>
            <div class="flex flex-col gap-0.5 text-[12px] text-[#808080] mb-5">
                @if ($primaryCategory)
                    <span>{{ __('Category: :category', ['category' => $primaryCategory->translationValue('name') ?? $primaryCategory->slug]) }}</span>
                @endif
                @if ($product->translationValue('description') ?? null)
                    <span class="leading-5">{!! $product->translationValue('description') !!}</span>
                @endif
            </div>
        </div>
        <!-- reviews -->
        <div>
            <h2 class="lg:hidden mb-2 text-lg">{{ __('Reviews & rating') }}</h2>
            <!-- reviews header -->
            <div class="flex items-center gap-3 border-b border-[#e5e5e5] pb-3 mb-4 flex-wrap">
                <h2 class="text-[#000000] text-[15px] border-r border-[#e5e5e5] pr-3">
                    {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}
                </h2>
                <div class="flex items-center gap-1">
                    <span class="text-[#000000] text-[14px]">{{ $avgRating }}</span>
                    <div class="flex">
                        @for ($s = 1; $s <= 5; $s++)
                            <svg
                                class="w-8 h-8 {{ $s <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300' }}"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                </div>
                <div
                    class="flex items-center gap-2 bg-[#2aaf2e43] text-[#2AAF2F] px-2 py-3 text-sm rounded-lg order-first lg:order-last w-full lg:w-auto">
                    <svg width="19" height="16" viewBox="0 0 19 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M7.42812 9.25C10.5063 9.25 13 11.7437 13 14.8219C13 15.3344 12.5844 15.75 12.0719 15.75H0.928125C0.415625 15.75 0 15.3344 0 14.8219C0 11.7437 2.49375 9.25 5.57188 9.25H7.42812ZM16.7938 3.05937C17.0375 2.725 17.5063 2.65 17.8406 2.89375C18.175 3.1375 18.25 3.60625 18.0063 3.94063L14.8062 8.34062C14.675 8.51875 14.4719 8.63438 14.2531 8.64688C14.0344 8.65938 13.8156 8.57812 13.6625 8.41875L12.2125 6.91875C11.925 6.62187 11.9313 6.14687 12.2313 5.85938C12.5281 5.57188 13.0031 5.58125 13.2906 5.87813L14.1188 6.73438L16.7938 3.05625V3.05937ZM6.5 7.5C4.42812 7.5 2.75 5.82188 2.75 3.75C2.75 1.67812 4.42812 0 6.5 0C8.57187 0 10.25 1.67812 10.25 3.75C10.25 5.82188 8.57187 7.5 6.5 7.5Z"
                            fill="#2AAF2F"/>
                    </svg>
                    <p>{{ __('All reviews are from verified purchases') }}</p>
                </div>
            </div>
            <!-- reviews list -->
            <ul class="flex flex-col">
                @forelse ($latestReviews as $review)
                    <li class="border-b border-[#e5e5e5] py-4 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-full bg-main text-white flex items-center justify-center text-xs font-bold shrink-0">
                                {{ strtoupper(substr($review->customer?->full_name ?? __('?'), 0, 1)) }}
                            </div>
                            <span
                                class="text-[#000000] text-[14px]">{{ $review->customer?->full_name ?? __('Anonymous') }}</span>
                            <span
                                class="text-[#808080] text-[12px] ml-auto">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex">
                            @for ($s = 1; $s <= 5; $s++)
                                <svg class="w-4 h-4 {{ $s <= $review->stars ? 'text-yellow-400' : 'text-gray-300' }}"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        @if ($review->comment)
                            <p class="text-black text-[16px]">{{ $review->comment }}</p>
                        @endif
                    </li>
                @empty
                    <li class="py-6 text-center text-[#808080] text-sm">{{ __('No reviews yet. Be the first to review this product!') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    </div>
    {{-- ── Product sliders ── --}}
    @foreach ([
    ['title' => __('Similar items'), 'items' => $related],
    ['title' => __('Shoppers also viewed'), 'items' => $alsoViewed],
    ['title' => __('Recommended For You'), 'items' => $recommended],
    ] as $sliderIdx => $slider)
        @if ($slider['items']->isNotEmpty())
            <section class="mt-12 max-w-[1400px] mx-auto px-4 2xl:px-0" wire:ignore>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-[20px] font-bold text-[#222]">{{ $slider['title'] }} :</h2>
                </div>
                {{-- Scrollable row --}}
                <div class="swiper product-slid-swiper w-full select-none">
                    <div class="swiper-wrapper">
                        @foreach ($slider['items'] as $sliderProduct)
                            <div class="swiper-slide w-fit">
                                @include($this->pageView('_product-card'), ['product' => $sliderProduct, 'badge' => null])
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach
    <style>
        .product-slider-row::-webkit-scrollbar {
            display: none
        }

        /* Thumb strip: inactive = 58% white overlay, active = orange border, no overlay */
        .product-preview-thumbs .swiper-slide img,
        .fs-product-preview-thumbs .swiper-slide img {
            transition: opacity 0.2s;
        }

        .product-preview-thumbs .swiper-slide-thumb-active,
        .fs-product-preview-thumbs .swiper-slide-thumb-active {
            border-color: #FF4D00 !important;
        }

        .product-preview-thumbs .swiper-slide-thumb-active img,
        .fs-product-preview-thumbs .swiper-slide-thumb-active img {
            opacity: 1 !important;
        }

        /* hide swiper's own arrow SVGs since we supply our own */
        .swiper-button-prev::after,
        .swiper-button-next::after {
            display: none;
        }
    </style>
    <!-- full screen images viewer -->
    <div id="product-preview"
         class="hidden fixed top-0 left-0 w-screen h-screen bg-black/80 z-[999] items-center justify-center flex-col overflow-auto py-6">
        <button id="close-product-preview-button"
                class="bg-white w-6 h-6 md:w-12 md:h-12 text-2xl rounded-full flex items-center justify-center fixed top-3 right-3 z-10">
            X
        </button>
        <div class="relative select-none mb-8 w-full max-w-4xl h-[95%] mx-auto">
            @if($badgeLabel)
                <span
                    class="absolute top-0 left-0 z-10 bg-main text-white text-[10px] lg:text-[11px] font-bold px-2.5 py-1 rounded-ee-xl rounded-ss-xl flex items-center gap-1 shrink-0 uppercase tracking-wide">
                {{ __($badgeLabel) }}
            </span>
            @endif
            <div class="swiper fs-product-preview-swiper w-full h-full max-h-screen rounded-xl bg-[#E6E6E6] overflow-hidden">
                <div class="swiper-wrapper">
                    @foreach($mediaItems as $idx => $item)
                        @if($item['type'] === 'video')
                            <div class="swiper-slide relative flex items-center justify-center bg-[#1a1a1a]">
                                <video
                                    class="w-full h-full object-contain elora-fs-gallery-video"
                                    src="{{ $item['src'] }}"
                                    @if(!empty($item['poster'])) poster="{{ $item['poster'] }}" @endif
                                    muted loop playsinline controls preload="none">
                                </video>
                            </div>
                        @else
                            <div class="swiper-slide flex items-center justify-center">
                                <img loading="lazy" class="w-full h-full object-contain" src="{{ $item['src'] }}"
                                     alt="{{ $item['alt'] ?? '' }}">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <!-- Navigation buttons -->
            <div class="swiper-button-prev w-10 h-10 rounded-full bg-white !left-0 drop-shadow-2xl">
                <svg
                    class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
                          stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>

            </div>
            <div class="swiper-button-next w-10 h-10 rounded-full bg-white !right-0 drop-shadow-2xl">
                <svg
                    class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
                          stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>

            </div>
        </div>
        <!-- swiper thumb -->
        <div class="swiper fs-product-preview-thumbs w-full select-none mt-4">
            <div class="swiper-wrapper">
                @foreach($mediaItems as $idx => $item)
                    @if($item['type'] === 'video')
                        <div
                            class="swiper-slide !w-[88px] !h-20 rounded-lg overflow-hidden cursor-pointer relative bg-[#333] flex items-center justify-center border-2 border-transparent">
                            @if(!empty($item['poster']))
                                <img loading="lazy" class="w-full h-full object-cover opacity-60" src="{{ $item['poster'] }}" alt="">
                            @else
                                <div class="absolute inset-0 bg-[#444] opacity-60"></div>
                            @endif
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-1">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 3l14 9-14 9V3z" fill="#fff"/>
                                </svg>
                                <span
                                    class="text-[11px] font-medium tracking-wide text-white leading-tight">{{ __('Play Video') }}</span>
                            </div>
                        </div>
                    @else
                        <div
                            class="swiper-slide !w-[88px] !h-20 rounded-lg overflow-hidden cursor-pointer border-2 border-transparent">
                            <img loading="lazy" class="w-full h-full object-cover opacity-60" src="{{ $item['src'] }}"
                                 alt="{{ $item['alt'] ?? '' }}">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    {{-- ─────────────────────────── DELIVERY POPUP MODAL ─────────────────────────── --}}
    @if ($deliveryPopupDays->isNotEmpty())
        @php
            $maxPct = $deliveryPopupDays->max('percentage') ?: 1;
            $fastestDay = $deliveryPopupDays->sortBy('day_number')->first();
            $minDay = (int) ($fastestDay?->day_number ?? $deliveryMinDays);
            $maxDay = (int) ($deliveryPopupDays->sortByDesc('day_number')->first()?->day_number ?? $deliveryMaxDays);
        @endphp
        <div id="elora-delivery-modal-backdrop"
             onclick="if(event.target===this)eloraCloseDeliveryModal()"
             class="hidden fixed inset-0 bg-[rgba(0,0,0,.45)] z-[100] items-center justify-center p-4"
             >

            <div id="elora-delivery-modal"
                 class="bg-[#FDFDFD] rounded-t-xl sm:rounded-b-xl w-full sm:max-w-[375px] lg:max-w-[475px] pt-8 pb-[50px] sm:pb-8 px-4 flex flex-col items-start gap-8 max-h-[90vh] fixed bottom-0 right-0 left-0 sm:relative"
                 >

                {{-- Close button --}}
                <button type="button" onclick="eloraCloseDeliveryModal()"
                        class="absolute -top-5 right-3 w-11 h-11 rounded-full bg-white flex items-center justify-center cursor-pointer z-10"
                       >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M1 1l12 12M13 1L1 13" stroke="#8F8F8F" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>

                {{-- Title --}}
                <h2 style="font-family:'Outfit',sans-serif;font-weight:500;font-size:24px;line-height:30px;color:#121212;margin:0;flex:none;order:0;flex-grow:0;z-index:0;">{{ __('Delivery') }}</h2>

                {{-- Content frame --}}
                <div
                    style="display:flex;flex-direction:column;align-items:flex-start;padding:0;gap:24px;width:100%;flex:none;order:1;align-self:stretch;flex-grow:0;z-index:1;">

                    {{-- Summary table --}}
                    <div
                        style="display:flex;flex-direction:column;align-items:flex-start;padding:0;gap:12px;width:100%;flex:none;order:0;align-self:stretch;flex-grow:0;">

                        {{-- Table row --}}
                        <div
                            style="display:flex;flex-direction:row;align-items:center;padding:0;width:100%;flex:none;order:0;align-self:stretch;flex-grow:0;">
                            {{-- Left: labels --}}
                            <div
                                style="display:flex;flex-direction:column;align-items:flex-start;padding:4px 8px;gap:8px;width:129px;background:#EDEDED;flex:none;order:0;flex-grow:0;">
                                <span
                                    style="font-family:'Outfit',sans-serif;font-weight:500;font-size:14px;line-height:18px;letter-spacing:.5px;color:#121212;flex:none;order:0;flex-grow:0;">{{ __('Delivery Time') }}</span>
                                <span
                                    style="font-family:'Outfit',sans-serif;font-weight:500;font-size:14px;line-height:18px;letter-spacing:.5px;color:#121212;flex:none;order:1;flex-grow:0;">{{ __('Costs') }}</span>
                            </div>
                            {{-- Right: values --}}
                            <div
                                style="box-sizing:border-box;display:flex;flex-direction:column;align-items:flex-start;padding:4px 12px;gap:8px;flex:1;border-left:1px solid #D4D4D4;flex:none;order:1;flex-grow:1;">
                                <span
                                    style="font-family:'Outfit',sans-serif;font-weight:500;font-size:14px;line-height:18px;letter-spacing:.5px;color:#121212;flex:none;order:0;flex-grow:0;">{{ $minDay }}-{{ $maxDay }} {{ __('days') }}</span>
                                <span
                                    style="font-family:'Outfit',sans-serif;font-weight:500;font-size:14px;line-height:18px;letter-spacing:.5px;color:#121212;flex:none;order:1;flex-grow:0;">{{ __('Free') }}</span>
                            </div>
                        </div>

                        {{-- Info note --}}
                        <div
                            style="display:flex;flex-direction:row;align-items:flex-start;padding:0;gap:9px;width:100%;flex:none;order:1;align-self:stretch;flex-grow:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                 style="flex:none;order:0;flex-grow:0;">
                                <path d="M12 16v-4M12 8h.01" stroke="#121212" stroke-width="1.8" stroke-linecap="round"
                                      stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="10" stroke="#121212" stroke-width="1.5"/>
                            </svg>
                            <p style="font-family:'Outfit',sans-serif;font-weight:400;line-height:13px;letter-spacing:.5px;color:#121212;margin:0;order:1;flex-grow:1;"
                            class="text-[10px] lg:text-sm"
                            >
                                {{ __('The above applies only to products not shipped from local warehouses.If delivery is made after April 20, you will receive a ') }}
                                <span class="text-main">
                                {{ __('15$ credit') }}
                                </span>
                                {{ __('... within 48 hour.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Distribution --}}
                    <div
                        style="display:flex;flex-direction:column;align-items:flex-start;padding:0;gap:16px;width:100%;flex:none;order:1;align-self:stretch;flex-grow:0;">

                        <p style="font-family:'Outfit',sans-serif;font-weight:500;font-size:14px;line-height:18px;letter-spacing:.5px;color:#FF4D00;margin:0;width:100%;flex:none;order:0;align-self:stretch;flex-grow:0;">
                            {{ __('Fastest delivery in :n Days', ['n' => $minDay]) }}
                        </p>

                        <div
                            style="display:flex;flex-direction:column;align-items:flex-start;padding:0;gap:8px;width:100%;flex:none;order:1;align-self:stretch;flex-grow:0;">
                            @foreach ($deliveryPopupDays as $dpDay)
                                @php
                                    $pct = (float) $dpDay->percentage;
                                    $barWidth = $maxPct > 0 ? round(($pct / $maxPct) * 100) : 0;
                                @endphp
                                <div
                                    style="display:flex;flex-direction:row;align-items:center;padding:0;gap:8px;width:100%;align-self:stretch;">
                                    <span
                                        style="font-family:'Outfit',sans-serif;font-weight:400;line-height:13px;letter-spacing:.5px;color:#121212;min-width:46px;flex:none;order:0;flex-grow:0;"
                                        class="text-[10px] lg:text-xs"
                                        >{{ $dpDay->day_number }} {{ $dpDay->day_number === 1 ? __('day') : __('days') }}</span>
                                    <div style="position:relative;flex:1;height:8px;order:1;flex-grow:1;">
                                        <div
                                            style="position:absolute;width:100%;height:8px;left:0;top:0;background:#D4D4D4;border-radius:26px;"></div>
                                        <div
                                            style="position:absolute;height:8px;left:0;top:0;width:{{ $barWidth }}%;background:#121212;border-radius:26px;{{ $pct > 0 ? 'min-width:4px;' : '' }}"></div>
                                    </div>
                                    <span
                                        style="font-family:'Outfit',sans-serif;font-weight:400;line-height:13px;letter-spacing:.5px;color:#121212;min-width:32px;text-align:right;flex:none;order:2;flex-grow:0;"
                                       class="text-[10px] lg:text-xs"
                                        >{{ number_format($pct, 1) }} %</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endif
    {{-- ─────────────────────────── privacy&secure POPUP MODAL ─────────────────────────── --}}
        <div id="elora-privacy-modal-backdrop"
             onclick="if(event.target===this)eloraClosePrivacyModal()"
             class="hidden fixed inset-0 bg-[rgba(0,0,0,.45)] z-[100] items-center justify-center p-4"
             >

            <div id="elora-privacy-modal"
                 class="bg-[#FDFDFD] rounded-t-xl sm:rounded-b-xl w-full sm:max-w-[375px] lg:max-w-[475px] pt-8 pb-[50px] sm:pb-8 px-4 max-h-[90vh] fixed bottom-0 right-0 left-0 sm:relative"
                 >

                {{-- Close button --}}
                <button type="button" onclick="eloraClosePrivacyModal()"
                        class="absolute -top-5 right-3 w-11 h-11 rounded-full bg-white flex items-center justify-center cursor-pointer z-10"
                       >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M1 1l12 12M13 1L1 13" stroke="#8F8F8F" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
                <!-- title -->
                <h2 class="flex items-center justify-center text-2xl gap-2 font-semibold"><span><svg width="24" height="28" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M0.75 4.73242V13.9199C0.75 23.1074 12 27.0449 12 27.0449C12 27.0449 23.25 23.1074 23.25 13.9199V4.73242L12 0.794922L0.75 4.73242Z" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="square"/>
<path d="M7.21875 12.8839L10.7525 16.4189L17.8237 9.34766" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="square"/>
</svg>
</span><span>{{ __('Why choose Elora') }}</span></h2>

<!-- taps -->
 <div class="border border-[#2AAF2F] rounded-sm p-0.5 my-8 flex justify-center gap-2 h-9">
    <button class="h-full flex items-center justify-center flex-1 rounded-sm text-[#2AAF2F]" >{{ __('Order guarantee')}}</button>
    <button class="h-full flex items-center justify-center flex-1 rounded-sm text-white bg-[#2AAF2F]">{{ __('Order guarantee')}}</button>
 </div>
 <!-- body -->
  <h4 class="text-center mb-[18px]">{{ __('Protection and privacy')}}</h4>
   <div class="flex items-center gap-2 text-sm mb-[9px]"><span class="w-5 h-5 rounded-full flex items-center justify-center bg-[#2AAF2F]">1</span><p>{{ __(' Pay with confidence using our secure methods
')}}</p></div>
<!-- privacy list -->
 <ul class="flex flex-col gap-2">
    <li class="flex items-center gap-2"><span><svg width="11" height="8" viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
</span><p class="text-[10px]">{{ __('Card information is secure and not vulnerable to disclosure')}}</p></li>
    <li class="flex items-center gap-2"><span><svg width="11" height="8" viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
</span><p class="text-[10px]">{{ __("Elora follows the Payment Card Industry (PCI DSS) data security standard and other security standards when handling card data")}}</p></li>
    <li class="flex items-center gap-2"><span><svg width="11" height="8" viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
</span><p class="text-[10px]">{{ __('All data is encrypted')}}</p></li>
    <li class="flex items-center gap-2"><span><svg width="11" height="8" viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.774 0.75L3.987 6.536L0.75 3.304" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
</span><p class="text-[10px]">{{ __('Elora does not sell your card information')}}</p></li>
 </ul>

 <!-- payment ways -->
  <div class="flex flex-col gap-2 my-2">
    <span class="flex items-center gap-2">
        <!-- visa payment -->
        <svg width="36" height="24" viewBox="0 0 36 24" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="35.2903" height="23.5268" fill="url(#pattern0_1_23063)"/>
<defs>
<pattern id="pattern0_1_23063" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_1_23063" transform="scale(0.00833333 0.0125)"/>
</pattern>
<image id="image0_1_23063" width="120" height="80" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>
<!-- mastercard payment -->
 <svg width="36" height="24" viewBox="0 0 36 24" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="35.2903" height="23.5268" fill="url(#pattern0_1_23064)"/>
<defs>
<pattern id="pattern0_1_23064" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_1_23064" transform="scale(0.00833333 0.0125)"/>
</pattern>
<image id="image0_1_23064" width="120" height="80" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>
    </span>
    <p class="text-[10px]">{{ __('We also provide you with other payment methods')}}</p>
     <span class="flex items-center gap-2">
        <!-- visa payment -->
        <svg width="36" height="24" viewBox="0 0 36 24" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="35.2903" height="23.5268" fill="url(#pattern0_1_23063)"/>
<defs>
<pattern id="pattern0_1_23063" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_1_23063" transform="scale(0.00833333 0.0125)"/>
</pattern>
<image id="image0_1_23063" width="120" height="80" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>
<!-- mastercard payment -->
 <svg width="36" height="24" viewBox="0 0 36 24" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="35.2903" height="23.5268" fill="url(#pattern0_1_23064)"/>
<defs>
<pattern id="pattern0_1_23064" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_1_23064" transform="scale(0.00833333 0.0125)"/>
</pattern>
<image id="image0_1_23064" width="120" height="80" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>
    </span>
  </div>
  <!-- ////////////// -->
      <div class="flex items-center gap-2 text-sm mb-[9px]"><span class="w-5 h-5 rounded-full flex items-center justify-center bg-[#2AAF2F]">2</span><p>{{ __('We keep your personal data private and secure')}}</p></div>
<p class="text-[10px]"><span class="text-[#2AAF2F]">{{ __('The security of your personal information is important to us.')}}</span><span>{{ __('we
We are committed to maintaining transparency and reducing permission requests within Application. You can view our ')}}</span><span class="text-main">{{ __('privacy policy')}}</span><span>{{ __('and')}}</span>
<span class="text-main">{{ __('similar kisses')}}</span><span>{{ __('to obtain the details.')}}</span></p>
<p class="text-[10px]"><span>{{ __('In March 2026, we officially partnered with the security agency Cyber ​​HackerOne to create a bug bounty program that Offers rewards to the cybersecurity community for discovering vulnerabilities Possible security and successfully completed it. We are committed to preserving Seamless shopping experience on Temu. To learn more, visit')}}</span>
<span class="text-main mt-2">{{ __('https://hackerone.com/Elora')}}</span>
</p>
            </div>
        </div>
    {{-- ── Share Modal ─────────────────────────────────────────────────────────── --}}
    <div id="elora-share-modal"
         class="hidden fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
         onclick="if(event.target===this) eloraCloseShareModal()">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        {{-- Sheet --}}
        <div id="elora-share-sheet"
             class="relative w-full sm:max-w-sm bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl px-6 pt-6 pb-8 z-10 transition-all duration-200"
             style="opacity:0;transform:translateY(16px)">

            {{-- Handle (mobile) --}}
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>

            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900">{{ __('Share this product') }}</h3>
                <button onclick="eloraCloseShareModal()"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Social icons --}}
            <div class="grid grid-cols-4 gap-3 mb-6">
                {{-- WhatsApp --}}
                <a id="elora-share-whatsapp" href="#" target="_blank" rel="noopener noreferrer"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-green-50 transition-colors group">
                <span class="w-11 h-11 flex items-center justify-center rounded-full bg-[#25D366] text-white shadow-sm group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                    </svg>
                </span>
                    <span class="text-[11px] text-gray-500 font-medium">WhatsApp</span>
                </a>

                {{-- Facebook --}}
                <a id="elora-share-facebook" href="#" target="_blank" rel="noopener noreferrer"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-blue-50 transition-colors group">
                <span class="w-11 h-11 flex items-center justify-center rounded-full bg-[#1877F2] text-white shadow-sm group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </span>
                    <span class="text-[11px] text-gray-500 font-medium">Facebook</span>
                </a>

                {{-- X / Twitter --}}
                <a id="elora-share-twitter" href="#" target="_blank" rel="noopener noreferrer"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                <span class="w-11 h-11 flex items-center justify-center rounded-full bg-black text-white shadow-sm group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </span>
                    <span class="text-[11px] text-gray-500 font-medium">X / Twitter</span>
                </a>

                {{-- Telegram --}}
                <a id="elora-share-telegram" href="#" target="_blank" rel="noopener noreferrer"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-sky-50 transition-colors group">
                <span class="w-11 h-11 flex items-center justify-center rounded-full bg-[#229ED9] text-white shadow-sm group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-.979-.64-.346-1.005.236-1.487.146-.13 2.705-2.48 2.755-2.692.006-.026.011-.13-.05-.183-.064-.054-.156-.032-.226-.018l-.075.018c-.129.03-2.175 1.382-6.143 4.058-.58.398-1.106.591-1.578.58-.52-.012-1.517-.294-2.26-.537-.91-.299-1.635-.457-1.572-.964.033-.267.458-.54 1.274-.822 4.994-2.175 8.324-3.609 9.99-4.301 4.757-1.98 5.745-2.323 6.392-2.335z"/>
                    </svg>
                </span>
                    <span class="text-[11px] text-gray-500 font-medium">Telegram</span>
                </a>
            </div>

            {{-- Copy URL bar --}}
            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <span id="elora-share-url-text" class="flex-1 text-sm text-gray-600 truncate select-all"></span>
                <button id="elora-copy-btn" onclick="eloraCopyShareUrl()"
                        class="flex-shrink-0 text-xs font-semibold text-white bg-gray-900 hover:bg-black px-3 py-1.5 rounded-lg transition-colors">
                    {{ __('Copy') }}
                </button>
            </div>
        </div>
    </div>

</main>


@push('head')
@php
    $jsonLdImages = $mediaItems->pluck('src')->filter()->values()->toArray();
    $jsonLdRating = $avgRating > 0 ? $avgRating : null;
    $jsonLdReviewCount = $reviewCount ?? 0;
    $jsonLdDesc = $seoDesc ?? strip_tags($product->translationValue('description') ?? $product->centralProduct?->translationValue('description') ?? '');
    $jsonLdName = $product->translationValue('name') ?? $product->slug;
    $jsonLdSku = $product->sku ?? ($product->centralProduct?->sku ?? null);
    $jsonLdBrand = $storeName ?? null;
    $jsonLdUrl = route('tenant.storefront.product', $product->slug);
    $jsonLdPrice = number_format($sellPrice * $rate, 2, '.', '');
    $jsonLdCurrency = data_get($currentCurrency ?? null, 'code', 'USD');
    $jsonLdAvailability = $isInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
    $jsonLd = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $jsonLdName,
        'description' => mb_substr($jsonLdDesc, 0, 500),
        'url' => $jsonLdUrl,
        'image' => count($jsonLdImages) === 1 ? $jsonLdImages[0] : $jsonLdImages,
        'offers' => [
            '@type' => 'Offer',
            'url' => $jsonLdUrl,
            'priceCurrency' => $jsonLdCurrency,
            'price' => $jsonLdPrice,
            'availability' => $jsonLdAvailability,
            'priceValidUntil' => now()->addYear()->toDateString(),
        ],
    ];
    if ($jsonLdSku) $jsonLd['sku'] = $jsonLdSku;
    if ($jsonLdBrand) $jsonLd['brand'] = ['@type' => 'Brand', 'name' => $jsonLdBrand];
    if ($jsonLdRating && $jsonLdReviewCount > 0) {
        $jsonLd['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $jsonLdRating,
            'reviewCount' => $jsonLdReviewCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@push('scripts')
@vite('resources/js/elora/product.js')
    <script>
        window.__eloraProductConfig = {
            textItem: @json(__('Item')),
            textCopied: @json(__('Copied!')),
            textCopy: @json(__('Copy')),
            textLoginFavorites: @json(__('Please log in to add products to your favorites.')),
            textRemovedFav: @json(__('Removed from favorites.')),
            textAddedFav: @json(__('Added to favorites!')),
            shareTitle: @json($product->translationValue('name') ?? $product->slug),
            shareDescription: @json(mb_substr($seoDesc ?? '', 0, 200)),
            shareImage: @json($mediaItems->first()['src'] ?? ''),
            shareUrl: @json(route('tenant.storefront.product', $product->slug)),
        };
    </script>
    <script>
        // ── Variant selection – no Livewire re-render ─────────────────────────────
        const ELORA_VARIANTS = @json($variantData ?? []);
        const ELORA_CART_ADD_URL = @json($cartAddUrl);
        const ELORA_PRODUCT_SLUG = @json($product->slug);
        let eloraSelectedVariantId = @json($activeVariant?->id);
        let eloraQty = @json($qty);

        function eloraFormatWeight(grams) {
            if (!grams) return '';
            return grams >= 1000
                ? (grams / 1000).toFixed(2) + @json(__('kg'))
                : grams + @json(__('g'));
        }

        window.storefrontUpdateShippingProgress = function (data) {
            const threshold = data.shippingThreshold ?? 0;
            if (!threshold) return;
            const pct = Math.max(0, Math.min(100, data.shippingPct ?? 0));
            const remaining = data.remainingForFreeShipping ?? 0;
            const message = remaining <= 0
                ? @json(__("You've reached free shipping!"))
                : @json(__('Add :weight more to qualify for free shipping', ['weight' => '__WEIGHT__'])).replace('__WEIGHT__', eloraFormatWeight(remaining));

            ['', '-mobile'].forEach(function (suffix) {
                const bar = document.getElementById('elora-shipping-bar' + suffix);
                if (bar) bar.style.width = pct + '%';
                const msg = document.getElementById('elora-shipping-message' + suffix);
                if (msg) msg.textContent = message;
            });
        };

        function eloraSetQty(value) {
            eloraQty = Math.max(1, parseInt(value, 10) || 1);
            eloraSyncQtyInputs();
        }

        function eloraIncrementQty() {
            eloraQty++;
            eloraSyncQtyInputs();
        }

        function eloraDecrementQty() {
            if (eloraQty > 1) eloraQty--;
            eloraSyncQtyInputs();
        }

        function eloraSyncQtyInputs() {
            ['elora-qty-input', 'elora-qty-input-mobile'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.value = eloraQty;
            });
        }

        function eloraAddToCart() {
            const buttons = ['elora-add-to-cart-btn', 'elora-mobile-add-to-cart-btn']
                .map((id) => document.getElementById(id))
                .filter(Boolean);
            const labels = ['elora-add-to-cart-label', 'elora-mobile-add-to-cart-label']
                .map((id) => document.getElementById(id))
                .filter(Boolean);

            buttons.forEach((btn) => { btn.disabled = true; btn.classList.add('opacity-60', 'cursor-not-allowed'); });
            labels.forEach((el) => { el.textContent = @json(__('Adding…')); });

            window.storefrontCartAdd({
                url: ELORA_CART_ADD_URL,
                slug: ELORA_PRODUCT_SLUG,
                variantId: eloraSelectedVariantId,
                qty: eloraQty,
            }).finally(function () {
                buttons.forEach((btn) => { btn.disabled = false; btn.classList.remove('opacity-60', 'cursor-not-allowed'); });
                labels.forEach((el) => { el.textContent = @json(__('Add to Cart')); });
            });
        }

        function eloraSelectVariant(id) {
            const data = ELORA_VARIANTS[id];
            if (!data) return;

            eloraSelectedVariantId = id;

            // Update button active states
            document.querySelectorAll('[data-variant-id]').forEach(function (btn) {
                const ring = btn.querySelector('[data-variant-ring]');
                if (!ring) return;
                const isActive = parseInt(btn.dataset.variantId) === id;
                if (isActive) {
                    ring.classList.remove('border-transparent', 'ring-1', 'ring-[#e5e5e5]');
                    ring.classList.add('border-[#222]');
                } else {
                    ring.classList.remove('border-[#222]');
                    ring.classList.add('border-transparent', 'ring-1', 'ring-[#e5e5e5]');
                }
            });

            // Update variant title
            const titleEl = document.getElementById('elora-variant-title');
            if (titleEl) titleEl.textContent = data.title;

            // Update sell price
            const sellEl = document.getElementById('elora-sell-price');
            if (sellEl) sellEl.textContent = data.displaySell;

            // Update discount area
            const realEl = document.getElementById('elora-real-price');
            const realVal = document.getElementById('elora-real-price-val');
            const badgeEl = document.getElementById('elora-discount-badge');
            const pctEl = document.getElementById('elora-discount-pct');
            if (data.hasDiscount && data.displayReal) {
                if (realEl) realEl.classList.remove('hidden');
                if (realVal) realVal.textContent = data.displayReal;
                if (badgeEl) badgeEl.classList.remove('hidden');
                if (pctEl) pctEl.textContent = data.discountPct;
            } else {
                if (realEl) realEl.classList.add('hidden');
                if (badgeEl) badgeEl.classList.add('hidden');
            }

            // Update stock badge
            const stockBadge = document.getElementById('elora-stock-badge');
            const stockDot = document.getElementById('elora-stock-dot');
            const stockText = document.getElementById('elora-stock-text');
            if (stockBadge) {
                if (data.isInStock) {
                    stockBadge.classList.remove('bg-red-100', 'text-red-700');
                    stockBadge.classList.add('bg-[#e8fbe8]', 'text-[#166534]');
                    if (stockDot) { stockDot.classList.remove('bg-red-500'); stockDot.classList.add('bg-[#22c55e]'); }
                    if (stockText) stockText.textContent = @json(__('In Stock'));
                } else {
                    stockBadge.classList.remove('bg-[#e8fbe8]', 'text-[#166534]');
                    stockBadge.classList.add('bg-red-100', 'text-red-700');
                    if (stockDot) { stockDot.classList.remove('bg-[#22c55e]'); stockDot.classList.add('bg-red-500'); }
                    if (stockText) stockText.textContent = @json(__('Out of Stock'));
                }
            }

            // Update add-to-cart button disabled state
            ['elora-add-to-cart-btn', 'elora-mobile-add-to-cart-btn'].forEach(function (btnId) {
                const btn = document.getElementById(btnId);
                if (btn) btn.disabled = !data.isInStock;
            });

            // Update weight
            const weightEl = document.getElementById('elora-weight-display');
            if (weightEl) weightEl.textContent = data.weightDisplay ?? '';

            // Jump the main gallery to this variant's image, if it has one
            if (data.mediaIndex !== null && data.mediaIndex !== undefined && window.eloraGoToMediaIndex) {
                window.eloraGoToMediaIndex(data.mediaIndex);
            }
        }
    </script>
    <script>
        // ── Image zoom on hover ────────────────────────────────────────────
        (function () {
            if (window.matchMedia('(hover: none)').matches) return;
            function initZoom() {
                document.querySelectorAll('.product-preview-swiper .swiper-slide').forEach(function (slide) {
                    const img = slide.querySelector('img');
                    if (!img || slide._zoomInit) return;
                    slide._zoomInit = true;
                    slide.style.overflow = 'hidden';
                    slide.style.cursor = 'crosshair';
                    slide.addEventListener('mousemove', function (e) {
                        const rect = slide.getBoundingClientRect();
                        const x = ((e.clientX - rect.left) / rect.width * 100).toFixed(2);
                        const y = ((e.clientY - rect.top) / rect.height * 100).toFixed(2);
                        img.style.transformOrigin = x + '% ' + y + '%';
                        img.style.transform = 'scale(2)';
                        img.style.transition = 'transform 0.1s ease';
                        img.style.pointerEvents = 'none';
                    });
                    slide.addEventListener('mouseleave', function () {
                        img.style.transform = 'scale(1)';
                        img.style.transformOrigin = 'center center';
                    });
                });
            }
            document.addEventListener('DOMContentLoaded', initZoom);
            setTimeout(initZoom, 800);
        })();
    </script>
@endpush
