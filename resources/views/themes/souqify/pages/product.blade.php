@push('head')
@php
    $seoProductName = $product->translationValue('name') ?? $product->slug;
    $seoImages = $mediaItems->where('type', 'image')->pluck('src')->values()->toArray();
    $seoCurrencyCode = data_get($currentCurrency ?? null, 'code', 'USD');
    $seoAvailability = $isInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
    $seoProductUrl = route('tenant.storefront.product', $product->slug);
    $weightLabel = fn (int $g) => $g >= 1000 ? number_format($g / 1000, 2) . __('kg') : $g . __('g');

    // BreadcrumbList items
    $breadcrumbItems = [];
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => route('tenant.home')];
    $pos = 2;
    foreach ($categoryAncestors as $ancestor) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $ancestor->translationValue('name') ?? $ancestor->slug,
            'item' => route('tenant.storefront.category', $ancestor->slug),
        ];
    }
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $seoProductName, 'item' => $seoProductUrl];

    $productSchema = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $seoProductName,
        'description' => $seoDesc,
        'url' => $seoProductUrl,
        'image' => $seoImages,
        'offers' => [
            '@type' => 'Offer',
            'url' => $seoProductUrl,
            'priceCurrency' => $seoCurrencyCode,
            'price' => number_format($sellPrice * $rate, 2, '.', ''),
            'availability' => $seoAvailability,
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ];

    if ($central?->sku) {
        $productSchema['sku'] = $central->sku;
    }

    if ($storeName ?? null) {
        $productSchema['brand'] = ['@type' => 'Brand', 'name' => $storeName];
    }

    if ($reviewCount > 0) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $avgRating,
            'reviewCount' => $reviewCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
    ];
@endphp
<script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

<div>
    <!-- =========== BREADCRUMB =========== -->
    <div class="bg-white border-b border-neutral-200 hidden lg:block">
        <div
            class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center gap-2 text-sm text-neutral-600 overflow-x-auto no-scrollbar">
            <a href="{{route('tenant.home')}}" class="hover:text-blue-700 transition shrink-0">{{ __('Home') }}</a>
            @foreach ($categoryAncestors as $ancestor)
            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('tenant.storefront.category', $ancestor->slug) }}"
                class="hover:text-blue-700 transition shrink-0">
                {{ $ancestor->translationValue('name') ?? $ancestor->slug }}
            </a>
            @endforeach
            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span
                class="text-blue-700 font-medium shrink-0">{{ $product->translationValue('name') ?? $product->slug }}</span>
        </div>
    </div>

    <!-- =========== PRODUCT HERO (gallery + info) =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10">

        <!-- Mobile Header -->
        <div class="lg:hidden mb-4 flex items-center gap-3">
            <!-- Back Button -->
            <button class="w-12 h-12 flex justify-center items-center rounded-full border hover:bg-gray-200"
                aria-label="{{ __('Back') }}" onclick="window.location.href='{{ url('/') }}'">
                <svg width=" 8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.66797 0.666992L0.667968 6.66699L6.66797 12.667" stroke="#0A0A0A" stroke-width="1.33333"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <!-- ---- search field ---------- -->
            <div class="flex-1">
            </div>
            {{--<div class="flex-1 overflow-hidden">
                <form action=" {{ route('tenant.storefront.search') }}" method="GET"
                    data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                    <div class="bg-[#F6F5F5] border flex items-center bg-bg rounded-full px-3 sm:px-5 py-2 gap-2 h-14"
                        style="position:relative">
                        <svg class="w-6 h-4  flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="{{ __('Search for products, brands...') }}" autocomplete="off"
                            class="bg-transparent flex-1 text-xs sm:text-sm text-gray-600 placeholder-gray-400 min-w-0 outline-none" />
                        <!-- <button type="submit"
                            class="bg-main text-white text-xs font-medium px-1 sm:px-3 py-0.5 rounded-full flex-shrink-0">{{ __('Search') }}</button> -->
                    </div>
                </form>
            </div> --}}
            <!-- cart icon -->
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
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
            <button type="button" onclick="souqifyOpenShareModal()"
                class="flex items-center justify-center transition-colors rounded-full w-12 h-12 border hover:bg-gray-200"
                aria-label="{{ __('Share') }}">
                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8 2V10.5M11 4L8 1L5 4M1 9V14C1 14.5304 1.21071 15.0391 1.58579 15.4142C1.96086 15.7893 2.46957 16 3 16H13C13.5304 16 14.0391 15.7893 14.4142 15.4142C14.7893 15.0391 15 14.5304 15 14V9"
                        stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-3 lg:gap-6 xl:gap-8">

            <!-- Gallery -->
            <div class="flex flex-col-reverse sm:flex-row gap-1 lg:gap-1.5 xl:gap-2 lg:w-fit min-[1440px]:w-[699px] max-h-[733px] min-w-0" wire:ignore>
                <!-- thumbs strip -->
                <div class="product-preview-thumbs select-none w-full sm:w-[95px]">
                    <div class="no-scrollbar flex sm:flex-col gap-4 overflow-x-auto sm:overflow-x-visible sm:overflow-y-auto sm:max-h-[500px] pb-1 sm:pb-0">
                        @foreach($mediaItems as $idx => $item)
                        @if($item['type'] === 'video')
                        <button type="button" data-thumb-idx="{{ $idx }}"
                            class="sq-thumb shrink-0 p-1 !h-20 !w-[88px] rounded-lg overflow-hidden cursor-pointer relative bg-[#E6E6E6] flex items-center justify-center border-2 {{ $idx === 0 ? 'border-[#004AC6]' : 'border-transparent' }}">
                            @if(!empty($item['poster']))
                            <img loading="lazy" class="w-full h-full object-cover opacity-60" src="{{ $item['poster'] }}" alt="">
                            @else
                            <div class="absolute inset-0 bg-[#E6E6E6] opacity-60"></div>
                            @endif
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-1">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 3l14 9-14 9V3z" fill="#0159ED" />
                                </svg>
                                <span
                                    class="text-[11px] font-medium tracking-wide text-[#1210159ED212] leading-tight">{{ __('Play Video') }}</span>
                            </div>
                        </button>
                        @else
                        <button type="button" data-thumb-idx="{{ $idx }}"
                            class="sq-thumb shrink-0 !w-[88px] !h-20 rounded-lg overflow-hidden cursor-pointer border-2 {{ $idx === 0 ? 'border-[#004AC6]' : 'border-transparent' }}">
                            <img loading="lazy" class="w-full h-full object-cover opacity-60" src="{{ $item['src'] }}"
                                alt="{{ $item['alt'] ?? '' }}">
                        </button>
                        @endif
                        @endforeach
                    </div>
                </div>

                <!-- Main stage -->
                <div class="relative w-full aspect-square sm:w-[600px] sm:h-[600px] lg:w-[380px] lg:h-[380px] min-[1440px]:w-[600px] min-[1440px]:h-[600px] sm:aspect-auto max-w-full rounded-2xl overflow-hidden flex-none">{{--mx-auto--}}
                    <div class="product-preview-swiper relative w-full h-full rounded-xl bg-[#E6E6E6] overflow-hidden">
                        @foreach($mediaItems as $idx => $item)
                        @if($item['type'] === 'video')
                        <div class="sq-main-slide absolute inset-0 flex items-center justify-center bg-[#E6E6E6] transition-opacity duration-300 {{ $idx === 0 ? 'opacity-100 z-[1]' : 'opacity-0 pointer-events-none' }}"
                            data-slide-idx="{{ $idx }}">
                            <video autoplay controls class="sq-gallery-video w-full h-full object-contain"
                                src="{{ $item['src'] }}" @if(!empty($item['poster'])) poster="{{ $item['poster'] }}"
                                @endif muted loop playsinline preload="none"></video>
                        </div>
                        @else
                        <div class="sq-main-slide absolute inset-0 flex items-center justify-center bg-[#E6E6E6] transition-opacity duration-300 {{ $idx === 0 ? 'opacity-100 z-[1]' : 'opacity-0 pointer-events-none' }}"
                            data-slide-idx="{{ $idx }}">
                            <img @if($idx === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                                class="w-full h-full object-contain" src="{{ $item['src'] }}" alt="{{ $item['alt'] ?? $seoProductName ?? '' }}">
                        </div>
                        @endif
                        @endforeach
                        <!-- Pagination -->
                        <div
                            class="sq-main-dots absolute end-0 bottom-0 bg-black text-white w-fit rounded-ss-xl px-2 py-1.5 flex items-center gap-1.5 z-10 sm:hidden">
                            @foreach($mediaItems as $idx => $item)
                            <button type="button" data-dot-idx="{{ $idx }}"
                                class="sq-dot w-1.5 h-1.5 rounded-full transition-colors {{ $idx === 0 ? 'bg-white' : 'bg-white/40' }}"
                                aria-label="{{ __('Image :n', ['n' => $idx + 1]) }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <!--bottom left badges -->
                    <div
                        class="flex items-center gap-2 pl-12 lg:pl-20 pr-1 absolute bottom-0 left-0 z-10 bg-[#0159ED] py-2 rounded-se-xl max-w-[90%] overflow-hidden text-[#FDFDFD]">
                        <span class="text-xs lg:text-base flex items-center gap-1">
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7.652 0.153329C7.14102 -0.0511098 6.57098 -0.0511098 6.06 0.153329L0.987429 2.1819C0.695928 2.29859 0.446069 2.49993 0.27007 2.75996C0.0940715 3.01998 4.72448e-06 3.32677 9.56756e-08 3.64076V9.80761C-0.00010948 10.1217 0.093904 10.4286 0.26991 10.6888C0.445916 10.9489 0.695838 11.1503 0.987429 11.267L6.06057 13.2956C6.50011 13.4717 6.98557 13.4972 7.44114 13.3682C7.2197 13.1322 7.0257 12.8719 6.86286 12.5922C6.69717 12.5923 6.53295 12.5611 6.37886 12.5002L3.80171 11.4693L3.78571 11.463L1.30571 10.471C1.1733 10.418 1.05981 10.3265 0.979867 10.2083C0.89992 10.0902 0.857176 9.95083 0.857143 9.80819V3.64076C0.857143 3.59504 0.861524 3.5499 0.870286 3.50533L6.42743 5.72876V9.33219C6.58938 8.7272 6.88196 8.16506 7.28457 7.68533V5.72876L12.8423 3.5059C12.8507 3.54971 12.8549 3.59466 12.8549 3.64076V6.81219C13.1714 7.01219 13.4594 7.25219 13.712 7.52533V3.64133C13.712 3.32734 13.6179 3.02055 13.4419 2.76053C13.2659 2.5005 13.0161 2.29916 12.7246 2.18247L7.652 0.153329ZM6.37829 0.948758C6.68494 0.826033 7.02706 0.826033 7.33371 0.948758L12.1291 2.86761L10.0691 3.69162L4.796 1.5819L6.37829 0.948758ZM3.64229 2.04361L8.91657 4.15333L6.856 4.9779L1.58229 2.86761L3.64229 2.04361ZM14.2834 10.439C14.2834 11.4241 13.8921 12.3689 13.1955 13.0654C12.499 13.762 11.5542 14.1533 10.5691 14.1533C9.58405 14.1533 8.63931 13.762 7.94275 13.0654C7.24618 12.3689 6.85486 11.4241 6.85486 10.439C6.85486 9.45395 7.24618 8.50921 7.94275 7.81265C8.63931 7.11608 9.58405 6.72476 10.5691 6.72476C11.5542 6.72476 12.499 7.11608 13.1955 7.81265C13.8921 8.50921 14.2834 9.45395 14.2834 10.439ZM9.91429 12.0699L12.7714 9.21276C12.8251 9.15911 12.8552 9.08634 12.8552 9.01047C12.8552 8.9346 12.8251 8.86184 12.7714 8.80819C12.7178 8.75454 12.645 8.7244 12.5691 8.7244C12.4933 8.7244 12.4205 8.75454 12.3669 8.80819L9.712 11.4636L8.77143 10.5225C8.74486 10.4959 8.71333 10.4748 8.67862 10.4605C8.64391 10.4461 8.60671 10.4387 8.56914 10.4387C8.53158 10.4387 8.49438 10.4461 8.45967 10.4605C8.42496 10.4748 8.39342 10.4959 8.36686 10.5225C8.34029 10.549 8.31922 10.5806 8.30484 10.6153C8.29047 10.65 8.28307 10.6872 8.28307 10.7248C8.28307 10.7623 8.29047 10.7995 8.30484 10.8342C8.31922 10.8689 8.34029 10.9005 8.36686 10.927L9.50972 12.0699C9.53625 12.0965 9.56778 12.1176 9.6025 12.132C9.63721 12.1464 9.67442 12.1538 9.712 12.1538C9.74958 12.1538 9.78679 12.1464 9.82151 12.132C9.85622 12.1176 9.88775 12.0965 9.91429 12.0699Z"
                                    fill="#FDFDFD" />
                            </svg>
                            <span class="whitespace-nowrap">{{ __('Free shipping') }}</span>
                        </span>
                        <!-- <span class="text-xs lg:text-base flex items-center gap-1">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.6843 4.93158C12.5231 4.74533 13.6156 3.91283 13.7818 3.50283C13.8793 3.26283 13.3931 2.88783 13.2631 2.73658C12.9656 2.39158 13.0856 2.22158 13.2006 1.81033C13.3318 1.34283 13.0206 0.826585 12.5793 0.624085C12.1381 0.421585 11.6106 0.485335 11.1668 0.682835C10.7231 0.880335 10.3443 1.19783 9.97305 1.51033C9.7018 1.33908 9.2693 0.582835 8.5693 1.07408C8.08555 1.41283 8.0418 2.15033 8.1043 2.73908C8.25055 4.09783 8.58555 4.80283 9.2168 4.99283C10.0143 5.23283 10.8968 5.10659 11.6843 4.93158Z"
                                    fill="#FDFDFD" />
                                <path
                                    d="M13.0469 1.02344C12.9406 2.85469 11.1544 4.06969 10.3069 4.60469L10.8569 5.07719C10.8569 5.07719 11.2056 5.08469 11.6831 4.93219C12.5019 4.67219 13.6981 3.93719 13.7806 3.50344C13.9019 2.87219 13.2544 3.00969 13.1081 2.51094C13.0319 2.24719 13.4794 1.74844 13.0469 1.02344ZM9.97438 1.51219C9.97438 1.51219 9.65563 1.18594 9.41938 1.03719C9.30188 1.25844 9.21813 1.49844 9.17688 1.74594C9.10313 2.18094 9.17688 2.79844 9.35063 3.25844C9.37813 3.32969 9.48063 3.31844 9.49188 3.24344C9.64188 2.25469 9.97438 1.51219 9.97438 1.51219Z"
                                    fill="#0159ED" />
                                <path
                                    d="M7.74307 4.77056C7.74307 4.77056 3.84432 5.19181 2.08557 8.57681C0.326818 11.9618 1.82182 14.0143 3.40432 14.7618C4.98682 15.5093 8.97432 15.7731 11.5681 15.1581C14.1618 14.5431 14.8106 13.2681 14.6893 12.0368C14.5131 10.2393 12.8431 9.13431 12.8431 9.13431C12.8431 9.13431 12.9093 6.91431 11.1868 5.47306C9.65807 4.19306 7.74307 4.77056 7.74307 4.77056Z"
                                    fill="#FDFDFD" />
                                <path
                                    d="M9.34405 10.4664C8.5078 9.41137 7.5203 9.27012 7.20405 8.89137C7.03155 8.68512 6.9303 8.47637 6.96905 8.21762C7.0103 7.94262 7.32905 7.75262 7.57655 7.70637C7.8653 7.65137 8.55405 7.68012 9.1328 8.23137C9.2703 8.36137 9.2203 8.56387 9.21655 8.74512C9.20655 9.13387 9.7628 9.51137 10.2128 9.18637C10.6641 8.86012 10.3178 8.13387 10.0266 7.78137C9.80655 7.51512 9.00905 6.92887 8.0178 6.83637C7.73905 6.81012 6.6178 6.64387 5.9703 7.87512C5.78405 8.22887 5.7153 9.08387 6.6903 9.80637C6.89405 9.95762 7.95155 10.4951 8.2453 10.8439C8.75405 11.4476 8.4053 11.9789 8.0078 12.0489C6.92405 12.2389 6.3103 11.6526 6.19655 11.3314C6.1153 11.1026 6.2003 10.8551 6.0953 10.6401C5.9878 10.4189 5.7678 10.3314 5.5353 10.4051C4.7728 10.6476 5.0153 11.4814 5.3528 11.9401C5.71405 12.4314 6.1578 12.7276 6.65655 12.8901C8.5178 13.4964 9.4128 12.5389 9.56905 11.8376C9.68405 11.3239 9.67155 10.8789 9.34405 10.4664Z"
                                    fill="#0159ED" />
                                <path d="M8.89531 6.12305C7.31156 9.50555 7.03906 13.7768 7.03906 13.7768"
                                    stroke="#0159ED" stroke-width="0.625" stroke-miterlimit="10" />
                                <path
                                    d="M10.209 3.99507C11.264 4.33882 11.4977 5.29257 11.4215 5.55257C11.3302 5.85757 10.1615 4.67007 8.42398 4.74132C7.81648 4.76632 7.99273 4.39382 8.27398 4.17882C8.64523 3.89507 9.26898 3.69007 10.209 3.99507Z"
                                    fill="#0159ED" />
                                <path
                                    d="M10.209 3.99507C11.264 4.33882 11.4977 5.29257 11.4215 5.55257C11.3302 5.85757 10.1615 4.67007 8.42398 4.74132C7.81648 4.76632 7.99273 4.39382 8.27398 4.17882C8.64523 3.89507 9.26898 3.69007 10.209 3.99507Z"
                                    fill="#0159ED" />
                                <path
                                    d="M12.0622 7.35678C12.1947 7.26553 12.6397 7.42303 12.7647 8.29428C12.8259 8.72053 12.8447 9.13303 12.8447 9.13303C12.8447 9.13303 12.3197 8.66178 12.1459 8.33053C11.9272 7.91178 11.8422 7.50678 12.0622 7.35678Z"
                                    fill="#0159ED" />
                            </svg> -->

                            <!-- <span class="whitespace-nowrap">{{ __('$20 credit in case of delay') }}</span> -->
                        </span>
                    </div>
                    <div dir="ltr"
                        class="font-bold absolute bottom-0 left-0 z-20 bg-[#FF4D00] p-1 rounded-[0%100%0%100%/100%49%51%0%] text-white text-xs lg:text-xl">
                        <span class="text-[#37160E] block whitespace-nowrap">{{ __('Day') }}</span>
                        <span class="text-white block whitespace-nowrap">{{ __('Salary') }}</span>
                        <span class="absolute top-0 right-0"><svg class="w-5 lg:w-7" viewBox="0 0 19 21" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7.94192 0.000382314C7.76424 -0.000216644 7.58605 -0.000111828 7.40735 0.000696766C6.54723 0.00469481 5.67336 0.0272905 4.79204 0.0599936L5.04702 1.17823C5.96482 1.07446 6.97102 0.997199 7.97714 0.958566C8.52222 0.937632 9.06398 0.926581 9.58947 0.929367C11.166 0.937587 12.5964 1.06997 13.5323 1.43258L13.9324 1.58774L13.7538 1.97789C12.4483 4.82975 12.1464 7.58108 12.351 10.2806C12.4519 7.07688 13.7836 4.2274 15.5319 0.574439C13.153 0.179126 10.6069 0.00923178 7.94192 0.000337246V0.000382314ZM9.20126 1.7429C8.80864 1.74322 8.40839 1.75117 8.00818 1.76653C6.13619 1.83841 4.27072 2.05807 3.05491 2.30991C4.41303 4.82908 5.51169 7.35405 6.22114 9.81554C6.30939 9.8126 6.39772 9.81328 6.48591 9.81756C6.66641 9.82641 6.84492 9.8509 7.01936 9.88899C6.29724 7.58014 5.47472 5.45071 4.45225 3.32451L4.22122 2.84412L4.74617 2.75122C6.0649 2.51771 7.42603 2.42733 8.72149 2.42693C8.80789 2.42693 8.89396 2.42729 8.9797 2.42801C9.80285 2.43519 10.5962 2.47778 11.3312 2.54188L11.8278 2.58523L11.6829 3.06217C10.8429 5.82769 10.6783 8.7977 11.1533 11.6235C11.1613 11.6307 11.1691 11.6381 11.1769 11.6455C11.3482 11.5855 11.5084 11.5243 11.6578 11.4589C11.287 8.43141 11.4513 5.30804 12.8361 2.06117C12.0633 1.86428 10.8853 1.76136 9.59095 1.74519C9.46106 1.74359 9.33116 1.74279 9.20126 1.7429ZM15.7125 2.07465C14.3219 5.01955 13.3342 7.37947 13.1723 9.92713C14.0724 6.92626 15.8123 4.2362 17.7949 2.50792C17.1067 2.33624 16.412 2.19171 15.7125 2.07465ZM2.46226 2.90701C1.61952 3.08077 0.796285 3.30133 0 3.60608C0.387496 3.9421 1.08801 4.56718 1.93793 5.39797C2.32911 4.99534 2.72806 4.59755 3.14417 4.23584C2.92378 3.78939 2.69644 3.34639 2.46226 2.90701ZM8.8422 3.24311C8.23036 3.24015 7.60406 3.25767 6.97551 3.3012C6.90957 3.91582 6.54606 4.49082 6.11189 5.05629C6.80508 6.69455 7.40375 8.36627 7.94273 10.1412C8.76817 10.3847 9.5848 10.6725 10.2463 11.0173C10.0006 9.11004 10.0342 7.15572 10.3532 5.24452C9.7744 4.64661 9.4729 3.98266 9.44873 3.25268C9.24659 3.2473 9.0444 3.24411 8.8422 3.24311ZM17.0188 4.45533C15.9079 5.77154 14.929 7.42004 14.2619 9.21309C15.4867 7.47093 16.9927 6.11205 18.5959 5.15216C18.0736 4.91224 17.5478 4.67994 17.0188 4.45533ZM3.50898 4.99255C2.85339 5.58498 2.20562 6.29605 1.53103 6.97347C2.98151 7.83557 4.21695 8.85651 5.22477 10.0129C5.28735 9.98925 5.35055 9.96801 5.41412 9.94865C4.94253 8.32715 4.29624 6.66787 3.50898 4.99255ZM13.9932 10.5966C13.2566 11.4856 12.5631 11.9549 11.6083 12.3293C11.7157 12.7435 11.5562 13.219 11.189 13.5826C10.973 13.7965 10.7016 14.0403 10.3047 14.1987C10.362 14.2326 10.4204 14.2645 10.4799 14.2943C11.0353 14.5715 12.0166 14.4821 12.9282 14.0675C13.8369 13.6542 14.6488 12.9403 14.9496 12.2171C15.1004 11.8106 14.9928 11.5985 14.7288 11.2876C14.5408 11.0663 14.2663 10.8433 13.9932 10.5967V10.5966ZM6.3529 10.623C5.87156 10.6186 5.38272 10.7575 4.97874 11.0614C4.40418 11.4937 3.96126 12.2646 3.9573 13.5741V13.5831L3.95685 13.592C3.82568 16.3494 4.02873 17.6215 4.431 18.3118C4.78607 18.921 5.37661 19.2126 6.24046 19.749C6.2816 19.6409 6.32446 19.508 6.36467 19.3403C6.46695 18.9136 6.55159 18.3275 6.60522 17.6793C6.71254 16.3829 6.69686 14.8263 6.5174 13.7062L6.42257 13.1137L7.00727 13.2482C8.42748 13.5749 9.23989 13.6135 9.71157 13.5303C10.1833 13.447 10.3432 13.2822 10.6199 13.0081C10.88 12.7506 10.8752 12.658 10.8201 12.5131C10.765 12.3681 10.5439 12.1292 10.1687 11.9006C9.41841 11.4435 8.14344 11.0197 6.9517 10.7036C6.75624 10.6519 6.55508 10.6248 6.3529 10.623ZM14.8476 13.7413C14.3978 14.1794 13.8443 14.5391 13.2629 14.8035C12.3203 15.2322 11.2835 15.4283 10.4234 15.1428C10.1197 15.2559 9.94328 15.3857 9.85429 15.4932C9.73219 15.6407 9.72455 15.7493 9.76592 15.9047C9.8073 16.0601 9.93056 16.2447 10.083 16.3775C10.2354 16.5103 10.4166 16.5792 10.4855 16.5825C12.5876 16.6823 13.4973 16.079 14.6307 15.3793C14.9613 15.1753 15.0878 14.762 15.0561 14.3492C15.0402 14.1429 14.9802 13.9489 14.9109 13.8268C14.8936 13.7956 14.8724 13.7669 14.8476 13.7413ZM7.38219 14.1499C7.50469 15.276 7.50671 16.5898 7.41103 17.746C7.35501 18.4231 7.2684 19.0388 7.15098 19.5287C7.0999 19.7419 7.0437 19.931 6.97578 20.0974L13.5657 19.807C13.4913 19.5308 13.4155 19.2534 13.3397 18.9758C13.0865 19.0601 12.8277 19.1265 12.5652 19.1746L12.5672 19.2074L12.1936 19.2306C11.9636 19.2582 11.7241 19.2734 11.4757 19.2752L7.7085 19.5092L7.82049 18.9878C8.17749 17.3254 8.21042 15.8618 7.99021 14.2605C7.78682 14.2276 7.5841 14.1907 7.38215 14.1499H7.38219ZM8.81843 14.3603C8.99578 15.7948 8.97445 17.1639 8.71035 18.6369L9.57573 18.5831C9.43998 18.3829 9.34672 18.157 9.3017 17.9193C9.24348 17.6102 9.27587 17.2662 9.47807 16.9817C9.48701 16.9691 9.49666 16.957 9.50623 16.9449C9.27057 16.7272 9.07372 16.4479 8.98451 16.1127C8.88954 15.7559 8.94884 15.319 9.23144 14.9776C9.32484 14.8648 9.43773 14.763 9.57029 14.6714C9.45304 14.5779 9.35026 14.4789 9.2615 14.3757C9.12386 14.3766 8.97647 14.3715 8.81843 14.3603ZM14.778 16.2374C13.7244 16.8776 12.5405 17.4896 10.4471 17.3902C10.3788 17.3869 10.3109 17.3776 10.2442 17.3626C10.1888 17.3874 10.1592 17.419 10.1372 17.4501C10.0939 17.5109 10.0683 17.6208 10.0963 17.7696C10.1524 18.0673 10.4344 18.3935 10.6969 18.4241C12.556 18.6403 13.8405 18.0321 14.6205 17.3245C14.9798 16.9985 14.9829 16.7899 14.93 16.563C14.9035 16.4497 14.8451 16.3398 14.778 16.2374ZM14.4778 18.4413C14.353 18.5195 14.2249 18.5924 14.0939 18.6596C14.168 18.9314 14.2423 19.2033 14.3157 19.4751L15.2105 19.3147C14.9796 19.1176 14.7989 18.9124 14.6539 18.7084C14.5891 18.6173 14.532 18.5287 14.4778 18.4413Z"
                                    fill="white" />
                            </svg></span>
                    </div>
                </div>
            </div>

            <!-- Product info -->
            <div class="flex flex-col gap-2 lg:gap-6 lg:flex-1 min-[1440px]:flex-none min-[1440px]:w-[639px] min-w-0">
                @if($badgeLabel)
                <span
                    class="self-start inline-block bg-orange-500 text-white text-xs font-semibold px-3 py-1 rounded-full">
                    {{ __($badgeLabel) }}
                </span>
                @endif

                <h1 class="text-2xl sm:text-4xl font-medium text-zinc-900 leading-tight">
                    {{ $product->translationValue('name') ?? $product->slug }}
                </h1>

                <div class="flex items-center gap-2">
                    <div class="flex">
                        @for ($s = 1; $s <= 5; $s++) <svg
                            class="w-4 h-4 {{ $s <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300' }}"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                            </svg>
                            @endfor
                    </div>
                    <span class="text-sm text-neutral-600">({{ round($avgRating) }} {{ __('Reviews') }})</span>
                </div>

                <!-- Price block -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-3xl sm:text-4xl text-blue-700 font-medium">{{ $symbol }}<span id="sq-sell-price">{{ $displaySell }}</span></span>
                    <span id="sq-real-price" class="text-base text-neutral-400 line-through {{ ($hasDiscount && $displayReal !== null) ? '' : 'hidden' }}">{{ $symbol }}<span id="sq-real-price-val">{{ $displayReal }}</span></span>
                    <span id="sq-save-badge" class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded {{ ($hasDiscount && $displayReal !== null) ? '' : 'hidden' }}">{{ __('SAVE') }}
                        {{ $symbol }}<span id="sq-save-amount">{{ $savedAmount }}</span></span>
                </div>
                @if($pricing['is_flash_sale'])
                <p class="flex items-center gap-2 text-orange-600 text-sm font-medium -mt-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z" />
                    </svg>
                    {{ __('Flash Sale price — limited time!') }}
                </p>
                @endif

                <!-- Stock + only X left + weight -->
                <div class="flex items-center text-sm">
                    <span id="sq-stock-in"
                        class="flex text-lg lg:text-xl items-center gap-1.5 text-emerald-600 font-medium pe-5 lg:pe-7 border-e-2 me-5 {{ $isInStock ? '' : 'hidden' }}">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        {{ __('In Stock') }}
                    </span>
                    <span id="sq-stock-out" class="flex text-lg lg:text-xl items-center gap-1.5 text-[#D8000A] font-medium {{ $isInStock ? 'hidden' : '' }}">
                        <span class="w-2 h-2 rounded-full bg-[#D8000A]"></span>
                        {{ __('Out of Stock') }}
                    </span>
                    @if($manageStock && $isInStock && $stockValue <= 10) <span
                        class="flex text-lg items-center gap-1.5 text-[#D8000A] font-medium pe-5 border-e-2 me-5 lg:me-7">
                        🔥
                        {{ __('Only :count left', ['count' => $stockValue]) }}
                        </span>
                        @endif
                        <span id="sq-weight-badge" class="flex lg:text-xl items-center gap-1.5 text-[#FF570F] font-medium {{ $weightDisplay ? '' : 'hidden' }}">
                            <svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12.3715 0C9.88209 0 7.84267 2.03943 7.84267 4.52887C7.84267 5.95446 8.51222 7.2316 9.55138 8.06359H5.30209L0 23.9698H24.7431L19.441 8.06359H15.1917C16.2308 7.2316 16.9004 5.95446 16.9004 4.52887C16.9004 2.03943 14.861 0 12.3715 0ZM12.3715 1.98828C13.7864 1.98828 14.9121 3.11398 14.9121 4.52887C14.9121 5.94375 13.7864 7.06945 12.3715 7.06945C10.9566 7.06945 9.83095 5.94375 9.83095 4.52887C9.83095 3.11398 10.9566 1.98828 12.3715 1.98828ZM7.83079 13.1972H9.1287V15.0343L10.9995 13.1972H12.5064L10.0827 15.5803L12.7558 18.23H11.1311L9.1287 16.2479V18.23H7.83079V13.1972ZM14.4548 14.3703C14.7177 14.3703 14.9491 14.4219 15.1492 14.5253C15.3491 14.6286 15.5323 14.7916 15.6987 15.0141V14.4546H16.9122V17.8491C16.9122 18.4559 16.7201 18.9188 16.3357 19.238C15.9537 19.5593 15.3986 19.72 14.6704 19.7199C14.4346 19.7199 14.2065 19.702 13.9861 19.666C13.7607 19.6288 13.5386 19.5736 13.322 19.5009V18.5603C13.5334 18.6817 13.7401 18.7716 13.9423 18.8301C14.1446 18.8908 14.3481 18.9211 14.5526 18.9211C14.9481 18.9211 15.238 18.8346 15.4222 18.6615C15.6065 18.4885 15.6987 18.2178 15.6987 17.8492V17.5896C15.5324 17.8098 15.3492 17.9716 15.1492 18.075C14.9492 18.1783 14.7177 18.23 14.4548 18.2301C13.994 18.2301 13.6131 18.0492 13.312 17.6874C13.0109 17.3233 12.8603 16.8604 12.8603 16.2986C12.8603 15.7344 13.0109 15.2726 13.312 14.9131C13.6131 14.5513 13.994 14.3703 14.4548 14.3703ZM14.9031 15.24C14.6536 15.24 14.4592 15.3321 14.3199 15.5164C14.1806 15.7007 14.1109 15.9615 14.1109 16.2986C14.1109 16.6446 14.1783 16.9075 14.3131 17.0872C14.448 17.2648 14.6447 17.3536 14.9031 17.3536C15.1548 17.3536 15.3503 17.2614 15.4897 17.0771C15.629 16.8929 15.6987 16.6333 15.6986 16.2985C15.6986 15.9614 15.629 15.7007 15.4896 15.5164C15.3503 15.3321 15.1548 15.24 14.9031 15.24H14.9031Z"
                                    fill="#FF570F" />
                            </svg>
                            <span id="sq-weight-badge-text">{{ $weightDisplay }}</span>
                        </span>
                </div>

                @if($product->translationValue('description'))
                <div>
                    <div id="sq-desc-wrap" class="sq-desc-readmore" data-lines="3">
                        <div id="sq-desc-text" class="text-base text-neutral-700 leading-7">
                            {!! $product->translationValue('description') !!}
                        </div>
                        <span class="sq-desc-fade" aria-hidden="true"></span>
                    </div>
                    <button id="sq-desc-toggle" type="button"
                        class="mt-1 flex items-center gap-1 text-[#0159ED] text-sm font-medium hover:underline focus:outline-none">
                        <span>{{ __('See more') }}</span>
                        <svg id="sq-desc-icon" class="w-4 h-4 transition-transform duration-300" fill="none"
                            stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
                <style>
                    .sq-desc-readmore {
                        position: relative;
                        overflow: hidden;
                        transition: max-height 0.35s ease;
                    }

                    .sq-desc-fade {
                        position: absolute;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        height: 48px;
                        background: linear-gradient(to bottom, rgba(255, 255, 255, 0), rgba(255, 255, 255, 1));
                        pointer-events: none;
                        opacity: 0;
                        transition: opacity 0.2s ease;
                    }

                    .sq-desc-readmore.is-collapsed .sq-desc-fade {
                        opacity: 1;
                    }
                </style>
                <script>
                    (function () {
                        function initSqDescReadMore() {
                            var wrap = document.getElementById('sq-desc-wrap');
                            var content = document.getElementById('sq-desc-text');
                            var btn = document.getElementById('sq-desc-toggle');
                            var icon = document.getElementById('sq-desc-icon');
                            if (!wrap || !content || !btn || wrap.dataset.readmoreInit) return;
                            wrap.dataset.readmoreInit = '1';

                            var lines = parseInt(wrap.getAttribute('data-lines') || '3', 10);
                            var lineHeight = parseFloat(getComputedStyle(content).lineHeight);
                            if (!lineHeight || isNaN(lineHeight)) lineHeight = 24;
                            var collapsedHeight = Math.ceil(lineHeight * lines);
                            var fullHeight = wrap.scrollHeight;

                            if (fullHeight <= collapsedHeight + 8) {
                                btn.style.display = 'none';
                                return;
                            }

                            wrap.style.maxHeight = collapsedHeight + 'px';
                            wrap.classList.add('is-collapsed');

                            btn.addEventListener('click', function () {
                                var isCollapsed = wrap.classList.contains('is-collapsed');
                                if (isCollapsed) {
                                    wrap.style.maxHeight = fullHeight + 'px';
                                    wrap.classList.remove('is-collapsed');
                                    btn.querySelector('span').textContent = '{{ __('See less') }}';
                                    icon.style.transform = 'rotate(180deg)';
                                } else {
                                    wrap.style.maxHeight = collapsedHeight + 'px';
                                    wrap.classList.add('is-collapsed');
                                    btn.querySelector('span').textContent = '{{ __('See more') }}';
                                    icon.style.transform = 'rotate(0deg)';
                                }
                            });
                        }
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initSqDescReadMore);
                        } else {
                            initSqDescReadMore();
                        }
                        document.addEventListener('livewire:navigated', initSqDescReadMore);
                    })();
                </script>
                @endif

                <!--Free shipping / weight info banner -->
                <x-storefront.free-shipping-progress
                    theme="souqify"
                    :threshold="$shippingThreshold"
                    :weight="$shippingProgressWeight"
                    :item-weight="$weightGrams" />
                @endif
                <!-- Shipping countries -->
                @if($shippingCountries->isNotEmpty())
                <div class="border border-neutral-200 rounded-xl p-3 lg:p-4 flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                        <svg class="w-4 h-4 text-blue-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        <span>{{ __('Ships to') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($shippingCountries as $country)
                        <span class="inline-flex items-center gap-1 bg-neutral-100 text-neutral-700 text-xs px-2 py-1 rounded-full">
                            @if($country->flag_emoji)
                            <span>{{ $country->flag_emoji }}</span>
                            @endif
                            {{ $country->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- features badges -->
                <!-- <div class="grid grid-cols-4 gap-3 items-center">
                    <div
                        class="flex flex-col items-center justify-center gap-1 w-full sm:w-20 md:w-36 lg:w-20 xl:w-36 h-20 bg-white rounded-2xl">
                        <p class="text-xl">🎵</p>
                        <h4 class="text-xs font-semibold line-clamp-1">{{ __('HD Audio') }}</h4>
                        <p class="text-[10px] text-[#747474] line-clamp-1">{{ __('360 Reality Audio') }}</p>
                    </div>
                    <div
                        class="flex flex-col items-center justify-center gap-1 w-full sm:w-20 md:w-36 lg:w-20 xl:w-36 h-20 bg-white rounded-2xl">
                        <p class="text-xl">🔇</p>
                        <h4 class="text-xs font-semibold line-clamp-1">{{ __('ANC') }}</h4>
                        <p class="text-[10px] text-[#747474] line-clamp-1">{{ __('Industry Leading') }}</p>
                    </div>
                    <div
                        class="flex flex-col items-center justify-center gap-1 w-full sm:w-20 md:w-36 lg:w-20 xl:w-36 h-20 bg-white rounded-2xl">
                        <p class="text-xl">🔋</p>
                        <h4 class="text-xs font-semibold line-clamp-1">{{ __('30 Hr') }}</h4>
                        <p class="text-[10px] text-[#747474] line-clamp-1">{{ __('Battery Life') }}</p>
                    </div>
                    <div
                        class="flex flex-col items-center justify-center gap-1 w-full sm:w-20 md:w-36 lg:w-20 xl:w-36 h-20 bg-white rounded-2xl">
                        <p class="text-xl">📱</p>
                        <h4 class="text-xs font-semibold line-clamp-1">{{ __('Multipoint') }}</h4>
                        <p class="text-[10px] text-[#747474] line-clamp-1">{{ __('2-Device Connect') }}</p>
                    </div>
                </div> -->

                <!-- Variant selector -->
                @if($variants->count() > 1)
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-neutral-700">{{ __('Choose option') }}</span>
                        @if($activeVariant)
                        <span id="sq-variant-title" class="text-sm text-neutral-500">{{ $activeVariant->display_label ?? '' }}</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($variants as $v)
                        @php
                        $vTitle = $v->display_label ?? '#' . $loop->iteration;
                        $vThumb = $v->thumbnail_url ?? null;
                        $vSwatch = $v->centralVariant?->options?->first()?->swatch ?? null;
                        $vIsActive = ($activeVariant?->id ?? $variants->first()?->id) === $v->id;
                        $vIsInStock = !$manageStock || (($v->stock ?? 9999) > 0);
                        $vMediaIndex = $variantData[$v->id]['mediaIndex'] ?? null;
                        @endphp
                        <button type="button" data-variant-id="{{ $v->id }}" onclick="sqSelectVariant({{ $v->id }})" title="{{ $vTitle }}"
                            @if($vMediaIndex !== null) onmouseenter="window.sqGoToMediaIndex && window.sqGoToMediaIndex({{ $vMediaIndex }})" @endif
                            @if(!$vIsInStock) aria-disabled="true" @endif
                            class="transition-all active:scale-95 {{ !$vIsInStock ? 'opacity-50 cursor-not-allowed' : '' }} {{ $vThumb || $vSwatch ? '' : 'px-4 py-2 rounded-lg border text-sm font-medium ' . ($vIsActive ? 'border-[#004AC6] bg-[#E1E2ED] text-[#004AC6]' : 'border-neutral-300 hover:border-[#004AC6]') . (!$vIsInStock ? ' line-through' : '') }}">
                            @if($vThumb)
                            {{-- Image swatch --}}
                            <div data-variant-ring class="relative w-[60px] h-[60px] rounded-xl border-2 p-0.5 overflow-hidden {{ $vIsActive ? 'border-[#004AC6] bg-[#E1E2ED]' : 'border-transparent ring-1 ring-neutral-200' }}">
                                <img loading="lazy" src="{{ $vThumb }}" alt="{{ $vTitle }}"
                                    class="w-full h-full object-cover rounded-lg" />
                                @if(!$vIsInStock)
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="w-[110%] h-px bg-neutral-400 rotate-45 origin-center"></div>
                                    </div>
                                @endif
                            </div>
                            @elseif($vSwatch)
                            {{-- Color swatch --}}
                            <div data-variant-ring class="relative w-9 h-9 rounded-full border-2 p-0.5 {{ $vIsActive ? 'border-[#004AC6]' : 'border-transparent ring-1 ring-neutral-300' }}">
                                <div class="w-full h-full rounded-full" style="background: {{ $vSwatch }};"></div>
                                @if(!$vIsInStock)
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="w-[110%] h-px bg-neutral-400 rotate-45 origin-center"></div>
                                    </div>
                                @endif
                            </div>
                            @else
                            {{-- Text pill — classes applied on button directly above --}}
                            {{ $vTitle }}
                            @endif
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Add to cart + qty -->
                <div
                    class="fixed lg:relative bottom-0 left-0 lg:bottom-auto lg:left-auto w-screen lg:w-auto z-50 lg:z-0 bg-white lg:bg-auto py-4 px-2 lg:p-0  flex items-center gap-3">
                    <button type="button" id="sq-add-to-cart-btn" onclick="sqAddToCart()" @if(!$isInStock) disabled @endif
                        class="flex-1 h-14 {{ $isInStock ? 'bg-blue-700 hover:bg-blue-800' : 'bg-neutral-400 cursor-not-allowed' }} text-white rounded-lg flex items-center justify-center gap-2 transition font-medium text-base">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span id="sq-add-to-cart-label">{{ __('Add to cart') }} —
                            {{ $symbol }}<span id="sq-add-to-cart-total">{{ number_format($sellPrice * $qty * $rate, 2) }}</span></span>
                    </button>
                    <div class="h-14 flex items-center border border-neutral-300 rounded-lg overflow-hidden">
                        <button type="button" onclick="sqDecrementQty()"
                            class="w-10 h-full flex items-center justify-center text-lg hover:bg-neutral-100 transition">−</button>
                        <span id="sq-qty-display" class="w-10 text-center text-base font-medium">{{ $qty }}</span>
                        <button type="button" onclick="sqIncrementQty()"
                            class="w-10 h-full flex items-center justify-center text-lg hover:bg-neutral-100 transition">+</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========== DESCRIPTION + WARRANTY =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-10">
        <!-- Tabs -->
        <div class="flex items-center gap-6 border-b border-neutral-300 mb-6">
            <button class="tab-btn pb-3 text-sm sm:text-base font-medium border-b-2 border-blue-700 text-blue-700"
                data-tab="desc">{{ __('Description') }}</button>
            <button
                class="tab-btn pb-3 text-sm sm:text-base font-medium border-b-2 border-transparent text-neutral-600 hover:text-blue-700 transition"
                data-tab="specs">{{ __('Technical Specs') }}</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
            <!-- Description card -->
            <div class="lg:col-span-3 bg-white rounded-[40px] p-6 sm:p-8">
                <div id="tab-desc" class="tab-content">
                    <h2 class="text-xl sm:text-2xl font-medium text-zinc-900 mb-3">
                        {{ $product->translationValue('name') ?? $product->slug }}
                    </h2>
                    @if($product->translationValue('description'))
                    <div class="text-neutral-700 leading-7">
                        {!! $product->translationValue('description') !!}
                    </div>
                    @else
                    <p class="text-neutral-500">{{ __('No description available.') }}</p>
                    @endif
                </div>
                <div id="tab-specs" class="tab-content hidden">
                    <h2 class="text-xl sm:text-2xl font-medium text-zinc-900 mb-4">{{ __('Technical Specifications') }}
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        @if($central?->sku)
                        <div class="flex justify-between border-b border-neutral-100 pb-2">
                            <dt class="text-neutral-500">{{ __('SKU') }}</dt>
                            <dd class="font-medium">{{ $central->sku }}</dd>
                        </div>
                        @endif
                        @if($weightDisplay)
                        <div class="flex justify-between border-b border-neutral-100 pb-2">
                            <dt class="text-neutral-500">{{ __('Weight') }}</dt>
                            <dd class="font-medium">{{ $weightDisplay }}</dd>
                        </div>
                        @endif
                        @foreach($variants as $v)
                        @if($v->display_label)
                        <div class="flex justify-between border-b border-neutral-100 pb-2">
                            <dt class="text-neutral-500">{{ __('Variant') }}</dt>
                            <dd class="font-medium">{{ $v->display_label }}</dd>
                        </div>
                        @endif
                        @endforeach
                        @if(
                        !$central?->sku && !$weightDisplay &&
                        $variants->every(fn($v) => blank($v->display_label))
                        )
                        <div class="col-span-2 text-neutral-500">{{ __('No specifications available.') }}</div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Warranty + delivery card -->
            <aside class="bg-amber-400 rounded-[40px] p-6 flex flex-col gap-5">
                <div class="bg-white rounded-xl p-4 flex flex-col gap-1">
                    <svg width="22" height="16" viewBox="0 0 22 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5 16C4.16667 16 3.45833 15.7083 2.875 15.125C2.29167 14.5417 2 13.8333 2 13H0V2C0 1.45 0.195833 0.979167 0.5875 0.5875C0.979167 0.195833 1.45 0 2 0H16V4H19L22 8V13H20C20 13.8333 19.7083 14.5417 19.125 15.125C18.5417 15.7083 17.8333 16 17 16C16.1667 16 15.4583 15.7083 14.875 15.125C14.2917 14.5417 14 13.8333 14 13H8C8 13.8333 7.70833 14.5417 7.125 15.125C6.54167 15.7083 5.83333 16 5 16ZM5 14C5.28333 14 5.52083 13.9042 5.7125 13.7125C5.90417 13.5208 6 13.2833 6 13C6 12.7167 5.90417 12.4792 5.7125 12.2875C5.52083 12.0958 5.28333 12 5 12C4.71667 12 4.47917 12.0958 4.2875 12.2875C4.09583 12.4792 4 12.7167 4 13C4 13.2833 4.09583 13.5208 4.2875 13.7125C4.47917 13.9042 4.71667 14 5 14ZM2 11H2.8C3.08333 10.7 3.40833 10.4583 3.775 10.275C4.14167 10.0917 4.55 10 5 10C5.45 10 5.85833 10.0917 6.225 10.275C6.59167 10.4583 6.91667 10.7 7.2 11H14V2H2V11ZM17 14C17.2833 14 17.5208 13.9042 17.7125 13.7125C17.9042 13.5208 18 13.2833 18 13C18 12.7167 17.9042 12.4792 17.7125 12.2875C17.5208 12.0958 17.2833 12 17 12C16.7167 12 16.4792 12.0958 16.2875 12.2875C16.0958 12.4792 16 12.7167 16 13C16 13.2833 16.0958 13.5208 16.2875 13.7125C16.4792 13.9042 16.7167 14 17 14ZM16 9H20.25L18 6H16V9Z"
                            fill="#FFB00A" />
                    </svg>
                    <h4 class="font-semibold text-[#191B23]">{{ __('Free Delivery') }}</h4>
                    <p class="text-xs text-[#434655]">{{ __('when you add more :weight to cart', ['weight' => $weightLabel($shippingThreshold)]) }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 flex flex-col gap-1">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 110-16 8 8 0 010 16zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"
                            fill="#FFB00A" />
                    </svg>
                    <h4 class="font-semibold text-[#191B23]">{{ __('Delivery Time') }}</h4>
                    <p class="text-xs text-[#434655]">{{ $deliveryMinDays }}–{{ $deliveryMaxDays }}
                        {{ __('business days') }}
                    </p>
                </div>
                {{-- <div class="bg-white rounded-xl p-4 flex flex-col gap-1">
                    <svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6.95 13.55L12.6 7.9L11.175 6.475L6.95 10.7L4.85 8.6L3.425 10.025L6.95 13.55ZM8 20C5.68333 19.4167 3.77083 18.0875 2.2625 16.0125C0.754167 13.9375 0 11.6333 0 9.1V3L8 0L16 3V9.1C16 11.6333 15.2458 13.9375 13.7375 16.0125C12.2292 18.0875 10.3167 19.4167 8 20ZM8 17.9C9.73333 17.35 11.1667 16.25 12.3 14.6C13.4333 12.95 14 11.1167 14 9.1V4.375L8 2.125L2 4.375V9.1C2 11.1167 2.56667 12.95 3.7 14.6C4.83333 16.25 6.26667 17.35 8 17.9Z"
                            fill="#FFB00A" />
                    </svg>
                    <h4 class="font-semibold text-[#191B23]">{{ __('2-Year Warranty') }}</h4>
                    <p class="text-xs text-[#434655]">{{ __('Full coverage protection') }}</p>
                </div> --}}
                <div class="flex flex-wrap items-center gap-2 mt-auto pt-2 justify-center">
                    <div class="px-2.5 py-1 bg-white rounded text-[11px] font-bold text-blue-700">{{ __('VISA') }}</div>
                    <div class="px-2.5 py-1 bg-white rounded text-[11px] font-bold text-emerald-600">{{ __('tabby') }}
                    </div>
                    <div class="px-2.5 py-1 bg-white rounded text-[11px] font-bold text-rose-500">
                        {{ __('tamara') }}
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <!-- =========== USER REVIEWS =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900 mb-6">{{ __('User Reviews') }}</h2>

        <!-- Summary -->
        <div class="grid grid-cols-12 gap-2 md:gap-6 mb-6">
            <div
                class="bg-white rounded-2xl col-span-5 flex flex-col items-center justify-center text-center md:border-r md:border-neutral-200 pb-4 md:pb-0">
                <div class="text-5xl font-medium text-zinc-900">{{ $avgRating > 0 ? $avgRating : '—' }}</div>
                <div class="flex my-2">
                    @for($s = 1; $s <= 5; $s++) <svg
                        class="w-5 h-5 {{ $s <= round($avgRating) ? 'text-amber-400' : 'text-gray-300' }}"
                        fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                        </svg>
                        @endfor
                </div>
                <p class="text-xs text-neutral-500">{{ __('Based on :count reviews', ['count' => $reviewCount]) }}</p>
            </div>
            <div class="col-span-7 flex flex-col gap-1 md:gap-2 justify-center">
                <!-- Bars -->
                @foreach([5, 4, 3, 2, 1] as $star)
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-3 text-neutral-600">{{ $star }}</span>
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                    </svg>
                    <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-400 rounded-full"
                            style="width:{{ $ratingDistribution[$star]['percentage'] }}%"></div>
                    </div>
                    <span class="w-8 text-right text-neutral-600">{{ $ratingDistribution[$star]['percentage'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Individual reviews -->
        <div class="bg-white rounded-2xl divide-y divide-neutral-200">
            @forelse($latestReviews as $review)
            @php
            $nameParts = explode(' ', trim($review->customer?->full_name ?? __('Anonymous')));
            $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1)) . strtoupper(substr($nameParts[1] ?? '', 0, 1));
            $avatarClasses = [
            'bg-blue-100 text-blue-700',
            'bg-emerald-100 text-emerald-700',
            'bg-purple-100
            text-purple-700',
            'bg-rose-100 text-rose-700'
            ];
            $avatarClass = $avatarClasses[$loop->index % count($avatarClasses)];
            @endphp
            <article class="p-6">
                <div class="flex items-start gap-3 mb-2">
                    <div
                        class="w-10 h-10 rounded-full {{ $avatarClass }} font-semibold flex items-center justify-center shrink-0">
                        {{ $initials ?: '?' }}
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="font-medium">{{ $review->customer?->full_name ?? __('Anonymous') }}</span>
                            @if($review->customer_id)
                            <span class="flex items-center gap-1 text-emerald-600 text-xs">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>{{ __('Verified') }}
                            </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex">
                                @for($s = 1; $s <= 5; $s++) <svg
                                    class="w-3.5 h-3.5 {{ $s <= $review->stars ? 'text-amber-400' : 'text-gray-300' }}"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                    </svg>
                                    @endfor
                            </div>
                            <span class="text-xs text-neutral-500">{{ $review->created_at->format('F d, Y') }}</span>
                        </div>
                    </div>
                </div>
                @if($review->comment)
                <p class="text-sm text-neutral-700 leading-6">{{ $review->comment }}</p>
                @endif
            </article>
            @empty
            <div class="p-6 text-center text-neutral-500">{{ __('No reviews yet.') }}</div>
            @endforelse
        </div>

        @if($reviewCount > 3)
        <div class="relative flex justify-center mt-6">
            <button type="button" wire:click="openReviewsModal"
                class="text-[#0F0345] font-medium hover:underline flex items-center justify-center gap-1 z-10 relative bg-white rounded-full w-[133px] md:w-[202px] h-[38px] md:h-[48px] border border-[#0F0345] text-xs md:text-base transition">
                {{ __('View all reviews') }}
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <span class="w-full h-[1px] absolute top-1/2 -translate-y-1/2 bg-[#0F0345] z-0"></span>
        </div>
        @endif
    </section>

    <!-- =========== COMPLETE THE COLLECTION =========== -->
    @if($related->isNotEmpty())
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Complete the Collection') }}</h2>
            <div class="flex items-center gap-2">
                <button onclick="scrollCarousel('collection', -1)"
                    class="w-10 h-10 rounded-full bg-white border border-neutral-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition"
                    aria-label="{{ __('Previous') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg></button>
                <button onclick="scrollCarousel('collection', 1)"
                    class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center hover:bg-blue-800 transition"
                    aria-label="{{ __('Next') }}"><svg class="w-4 h-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg></button>
            </div>
        </div>
        <div id="collection" class="flex overflow-x-auto gap-4 sm:gap-5 no-scrollbar scroll-snap-x scroll-smooth pb-2">
            @foreach($related as $rp)
            @php $rpPricing = $rp->storefrontPricing();@endphp
            <a href="{{ route('tenant.storefront.product', $rp->slug) }}"
                class="shrink-0 w-56 sm:w-64 group scroll-snap-start">
                <div class="aspect-square rounded-xl overflow-hidden bg-zinc-100 mb-3">
                    <img loading="lazy" src="{{ $rp->primary_image_url }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                        alt="{{ $rp->translationValue('name') ?? $rp->slug }}" />
                </div>
                <h3 class="font-medium text-zinc-900 mb-1 line-clamp-1">{{ $rp->translationValue('name') ?? $rp->slug }}</h3>
                <p class="text-blue-700 font-medium">
                    {{ $symbol }}{{ number_format($rpPricing['current_price'] * $rate, 2) }}
                </p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- =========== RECOMMENDED FOR YOU =========== -->
    @if($recommended->isNotEmpty())
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Recommended For You') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}"
                class="text-blue-700 font-medium text-sm hover:underline flex items-center gap-1">{{ __('View all products') }}
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" />
                </svg></a>
        </div>
        <div id="recommendedGrid" class="flex overflow-x-auto gap-4 no-scrollbar scroll-snap-x scroll-smooth pb-2">
            @foreach($recommended as $rp)
            @php $rpPricing = $rp->storefrontPricing();
            $rpSell = $rpPricing['current_price'];
            $rpReal = $rpPricing['base_price'];
            $rpDiscount = $rpPricing['has_discount'];
            $rpBadge = $rp->badges->first()?->label; @endphp
            <a href="{{ route('tenant.storefront.product', $rp->slug) }}"
                class="shrink-0 w-60 sm:w-64 bg-white rounded-2xl overflow-hidden border border-neutral-100 hover:shadow-lg transition group scroll-snap-start">
                <div class="relative aspect-square bg-zinc-100 overflow-hidden">
                    <img loading="lazy" src="{{ $rp->primary_image_url }}" alt="{{ $rp->translationValue('name') ?? $rp->slug }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                    @if($rpBadge)
                    <span
                        class="absolute top-3 left-3 bg-orange-500 text-white text-xs font-medium px-2.5 py-1 rounded-md">{{ $rpBadge }}</span>
                    @endif
                    <button type="button" onclick="event.preventDefault(); sqQuickAddToCart({{ $rp->id }})"
                        class="absolute bottom-3 right-3 w-9 h-9 rounded-full bg-white shadow flex items-center justify-center hover:bg-blue-50 transition"
                        aria-label="{{ __('Add to cart') }}"><svg class="w-4 h-4 text-blue-700" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg></button>
                </div>
                <div class="p-3">
                    <h3 class="text-sm font-medium mb-1 line-clamp-1">{{ $rp->translationValue('name') ?? $rp->slug }}
                    </h3>
                    <div class="flex items-center justify-between text-xs mb-2">
                        <div class="flex items-center gap-1">
                            <div class="flex">
                                @for($s = 1; $s <= 5; $s++) <svg
                                    class="w-3 h-3 {{ $s <= round($rp->average_rating) ? 'text-amber-400' : 'text-gray-300' }}"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                    </svg>
                                    @endfor
                            </div>
                            <span class="text-neutral-500">{{ $rp->average_rating }} ({{ $rp->rates->count() }})</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="text-blue-700 font-semibold">{{ $symbol }}{{ number_format($rpSell * $rate, 2) }}</span>
                        @if($rpDiscount && $rpReal)
                        <span
                            class="text-neutral-400 text-xs line-through">{{ $symbol }}{{ number_format($rpReal * $rate, 2) }}</span>
                        <span
                            class="text-orange-600 text-[10px] bg-orange-50 px-1.5 py-0.5 rounded">{{ $rpPricing['discount_percentage'] }}%
                            {{ __('off') }}</span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- =========== EXPLORE PRODUCTS =========== -->
    @if($alsoViewed->isNotEmpty())
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Explore Products') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}"
                class="text-blue-700 font-medium text-sm hover:underline flex items-center gap-1">{{ __('View all products') }}
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" />
                </svg></a>
        </div>
        <div id="exploreGrid" class="flex overflow-x-auto gap-4 no-scrollbar scroll-snap-x scroll-smooth pb-2">
            @foreach($alsoViewed as $ep)
            @php $epPricing = $ep->storefrontPricing();
            $epSell = $epPricing['current_price'];
            $epReal = $epPricing['base_price'];
            $epDiscount = $epPricing['has_discount'];
            $epBadge = $ep->badges->first()?->label; @endphp
            <a href="{{ route('tenant.storefront.product', $ep->slug) }}"
                class="shrink-0 w-60 sm:w-64 bg-white rounded-2xl overflow-hidden border border-neutral-100 hover:shadow-lg transition group scroll-snap-start">
                <div class="relative aspect-square bg-zinc-100 overflow-hidden">
                    <img loading="lazy" src="{{ $ep->primary_image_url }}" alt="{{ $ep->translationValue('name') ?? $ep->slug }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                    @if($epBadge)
                    <span
                        class="absolute top-3 left-3 bg-orange-500 text-white text-xs font-medium px-2.5 py-1 rounded-md">{{ $epBadge }}</span>
                    @endif
                    <button type="button" onclick="event.preventDefault(); sqQuickAddToCart({{ $ep->id }})"
                        class="absolute bottom-3 right-3 w-9 h-9 rounded-full bg-white shadow flex items-center justify-center hover:bg-blue-50 transition"
                        aria-label="{{ __('Add to cart') }}"><svg class="w-4 h-4 text-blue-700" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg></button>
                </div>
                <div class="p-3">
                    <h3 class="text-sm font-medium mb-1 line-clamp-1">{{ $ep->translationValue('name') ?? $ep->slug }}
                    </h3>
                    <div class="flex items-center justify-between text-xs mb-2">
                        <div class="flex items-center gap-1">
                            <div class="flex">
                                @for($s = 1; $s <= 5; $s++) <svg
                                    class="w-3 h-3 {{ $s <= round($ep->average_rating) ? 'text-amber-400' : 'text-gray-300' }}"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                    </svg>
                                    @endfor
                            </div>
                            <span class="text-neutral-500">{{ $ep->average_rating }} ({{ $ep->rates->count() }})</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="text-blue-700 font-semibold">{{ $symbol }}{{ number_format($epSell * $rate, 2) }}</span>
                        @if($epDiscount && $epReal)
                        <span
                            class="text-neutral-400 text-xs line-through">{{ $symbol }}{{ number_format($epReal * $rate, 2) }}</span>
                        <span
                            class="text-orange-600 text-[10px] bg-orange-50 px-1.5 py-0.5 rounded">{{ $epPricing['discount_percentage'] }}%
                            {{ __('off') }}</span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── All Reviews Modal ─────────────────────────────────────────────────── --}}
    @if($showReviewsModal)
    <div class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        wire:click.self="closeReviewsModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden"
            wire:click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900">{{ __('All Reviews') }}</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        {{ __(':count reviews · :avg average', ['count' => $allReviewsTotal, 'avg' => $avgRating > 0 ? $avgRating : '—']) }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Star summary --}}
                    <div class="flex items-center gap-1">
                        @for($s = 1; $s <= 5; $s++) <svg
                            class="w-4 h-4 {{ $s <= round($avgRating) ? 'text-amber-400' : 'text-gray-200' }}"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                            </svg>
                            @endfor
                    </div>
                    <button type="button" wire:click="closeReviewsModal"
                        class="w-8 h-8 flex items-center justify-center rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Reviews list --}}
            <div class="flex-1 overflow-y-auto divide-y divide-neutral-100">
                @forelse($allReviews as $rev)
                @php
                $rParts = explode(' ', trim($rev->customer?->full_name ?? __('Anonymous')));
                $rInitials = strtoupper(substr($rParts[0] ?? '', 0, 1)) . strtoupper(substr($rParts[1] ?? '', 0, 1));
                $rColors = ['bg-blue-100 text-blue-700','bg-emerald-100 text-emerald-700','bg-purple-100
                text-purple-700','bg-rose-100 text-rose-700','bg-amber-100 text-amber-700'];
                $rColor = $rColors[$loop->index % count($rColors)];
                @endphp
                <article class="px-6 py-4">
                    <div class="flex items-start gap-3 mb-2">
                        <div
                            class="w-9 h-9 rounded-full {{ $rColor }} font-semibold flex items-center justify-center shrink-0 text-sm">
                            {{ $rInitials ?: '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span
                                    class="font-medium text-sm text-zinc-900">{{ $rev->customer?->full_name ?? __('Anonymous') }}</span>
                                @if($rev->customer_id)
                                <span class="flex items-center gap-1 text-emerald-600 text-xs">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Verified') }}
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex">
                                    @for($s = 1; $s <= 5; $s++) <svg
                                        class="w-3.5 h-3.5 {{ $s <= $rev->stars ? 'text-amber-400' : 'text-gray-300' }}"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                        </svg>
                                        @endfor
                                </div>
                                <span class="text-xs text-neutral-400">{{ $rev->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                    @if($rev->comment)
                    <p class="text-sm text-neutral-700 leading-6 ml-12">{{ $rev->comment }}</p>
                    @endif
                </article>
                @empty
                <div class="px-6 py-12 text-center text-neutral-400 text-sm">{{ __('No reviews yet.') }}</div>
                @endforelse
            </div>

            {{-- Load more / footer --}}
            <div class="shrink-0 border-t border-neutral-100 px-6 py-4">
                @if($allReviews->count() < $allReviewsTotal) <button type="button" wire:click="loadMoreReviews"
                    wire:loading.attr="disabled" wire:target="loadMoreReviews"
                    class="w-full h-10 rounded-xl border border-neutral-300 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition disabled:opacity-60 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="loadMoreReviews">
                        {{ __('Load more') }}
                        <span class="text-neutral-400">({{ $allReviewsTotal - $allReviews->count() }}
                            {{ __('remaining') }})</span>
                    </span>
                    <span wire:loading wire:target="loadMoreReviews"
                        class="inline-block w-4 h-4 border-2 border-neutral-300 border-t-neutral-700 rounded-full animate-spin"></span>
                    </button>
                    @else
                    <p class="text-center text-xs text-neutral-400">
                        {{ __('All :count reviews loaded', ['count' => $allReviewsTotal]) }}
                    </p>
                    @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Share Modal ───────────────────────────────────────────────────────── --}}
    <div id="souqify-share-modal" class="hidden fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
        onclick="if(event.target===this) souqifyCloseShareModal()">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div id="souqify-share-sheet"
            class="relative w-full sm:max-w-sm bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl px-6 pt-6 pb-8 z-10 transition-all duration-200"
            style="opacity:0;transform:translateY(16px)">

            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>

            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-[#001537]">{{ __('Share this product') }}</h3>
                <button onclick="souqifyCloseShareModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-4 gap-3 mb-6">
                {{-- WhatsApp --}}
                <a id="souqify-share-whatsapp" href="#" target="_blank" rel="noopener noreferrer"
                    class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-green-50 transition-colors group">
                    <span
                        class="w-11 h-11 flex items-center justify-center rounded-full bg-[#25D366] text-white shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z" />
                        </svg>
                    </span>
                    <span class="text-[11px] text-gray-500 font-medium">WhatsApp</span>
                </a>

                {{-- Facebook --}}
                <a id="souqify-share-facebook" href="#" target="_blank" rel="noopener noreferrer"
                    class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-blue-50 transition-colors group">
                    <span
                        class="w-11 h-11 flex items-center justify-center rounded-full bg-[#1877F2] text-white shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </span>
                    <span class="text-[11px] text-gray-500 font-medium">Facebook</span>
                </a>

                {{-- X / Twitter --}}
                <a id="souqify-share-twitter" href="#" target="_blank" rel="noopener noreferrer"
                    class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-gray-50 transition-colors group">
                    <span
                        class="w-11 h-11 flex items-center justify-center rounded-full bg-black text-white shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </span>
                    <span class="text-[11px] text-gray-500 font-medium">X / Twitter</span>
                </a>

                {{-- Telegram --}}
                <a id="souqify-share-telegram" href="#" target="_blank" rel="noopener noreferrer"
                    class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-sky-50 transition-colors group">
                    <span
                        class="w-11 h-11 flex items-center justify-center rounded-full bg-[#229ED9] text-white shadow-sm group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-.979-.64-.346-1.005.236-1.487.146-.13 2.705-2.48 2.755-2.692.006-.026.011-.13-.05-.183-.064-.054-.156-.032-.226-.018l-.075.018c-.129.03-2.175 1.382-6.143 4.058-.58.398-1.106.591-1.578.58-.52-.012-1.517-.294-2.26-.537-.91-.299-1.635-.457-1.572-.964.033-.267.458-.54 1.274-.822 4.994-2.175 8.324-3.609 9.99-4.301 4.757-1.98 5.745-2.323 6.392-2.335z" />
                        </svg>
                    </span>
                    <span class="text-[11px] text-gray-500 font-medium">Telegram</span>
                </a>
            </div>

            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </svg>
                <span id="souqify-share-url-text" class="flex-1 text-sm text-gray-600 truncate select-all"></span>
                <button id="souqify-copy-btn" onclick="souqifyCopyShareUrl()"
                    class="flex-shrink-0 text-xs font-semibold text-white bg-[#001537] hover:bg-[#0159ED] px-3 py-1.5 rounded-lg transition-colors">
                    {{ __('Copy') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // ── Fixed corner video popup (picture-in-picture) ────────────────────────
    var sqPipModal = null;
    var sqPipVideo = null;
    var sqPipDismissed = false;

    function sqDestroyPip(userDismissed) {
        if (sqPipModal && sqPipModal.parentNode) {
            if (sqPipVideo) { sqPipVideo.pause(); sqPipVideo = null; }
            sqPipModal.style.opacity = '0';
            sqPipModal.style.transform = 'translateY(-16px)';
            var m = sqPipModal;
            setTimeout(function () { if (m.parentNode) m.parentNode.removeChild(m); }, 300);
            sqPipModal = null;
        }
        if (userDismissed) {
            sqPipDismissed = true;
        }
    }

    function sqShowPip(src, poster) {
        if (sqPipDismissed) return;
        if (sqPipModal) {
            // update source if different
            if (sqPipVideo && sqPipVideo.getAttribute('data-src') !== src) {
                sqPipVideo.setAttribute('data-src', src);
                sqPipVideo.src = src;
                if (poster) sqPipVideo.poster = poster;
                sqPipVideo.load();
                sqPipVideo.play().catch(function () {});
            }
            return;
        }

        var modal = document.createElement('div');
        modal.style.cssText = [
            'position:fixed',
            'top:80px',
            'right:12px',
            'width:200px',
            'height:260px',
            'border-radius:18px',
            'overflow:hidden',
            'background:#111',
            'box-shadow:0 8px 32px rgba(0,0,0,0.55)',
            'border:1.5px solid rgba(255,255,255,0.22)',
            'z-index:9999',
            'transform:translateY(-16px)',
            'opacity:0',
            'transition:opacity 0.3s ease,transform 0.3s ease',
        ].join(';');

        var closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', window.trans('Close video'));
        closeBtn.style.cssText = [
            'position:absolute',
            'top:6px',
            'right:8px',
            'z-index:10',
            'color:#fff',
            'font-size:18px',
            'font-weight:700',
            'line-height:1',
            'background:rgba(0,0,0,0.5)',
            'border:none',
            'border-radius:50%',
            'width:24px',
            'height:24px',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'cursor:pointer',
            'padding:0',
        ].join(';');
        closeBtn.onclick = function () { sqDestroyPip(true); };

        var vid = document.createElement('video');
        vid.src = src;
        vid.setAttribute('data-src', src);
        if (poster) vid.poster = poster;
        vid.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
        vid.autoplay = true;
        vid.muted = true;
        vid.loop = true;
        vid.playsInline = true;
        vid.setAttribute('playsinline', '');

        modal.appendChild(vid);
        modal.appendChild(closeBtn);
        document.body.appendChild(modal);
        sqPipModal = modal;
        sqPipVideo = vid;

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.style.opacity = '1';
                modal.style.transform = 'translateY(0)';
            });
        });
        vid.play().catch(function () {});
    }

    function sqCheckSlideForVideo(activeIndex) {
        var videoEl = document.querySelector('.sq-main-slide .sq-gallery-video');
        if (!videoEl) return;
        var videoSlide = videoEl.closest('.sq-main-slide');
        var isActiveVideo = Number(videoSlide.dataset.slideIdx) === activeIndex;
        if (!isActiveVideo) {
            var src = videoEl.src || videoEl.getAttribute('src') || '';
            var poster = videoEl.poster || videoEl.getAttribute('poster') || '';
            sqShowPip(src, poster);
        } else {
            sqPipDismissed = false;
            sqDestroyPip();
        }
    }
    // ─────────────────────────────────────────────────────────────────────────
    // ── Plain-JS product gallery (no Swiper) ──────────────────────────────────

    window.sqActiveMediaIndex = 0;
    window.sqGalleryReady = false;

    function sqSetActiveSlide(index) {
        var slides = document.querySelectorAll('.sq-main-slide');
        if (!slides.length) return;
        index = ((index % slides.length) + slides.length) % slides.length;

        slides.forEach(function (slide) {
            var isActive = Number(slide.dataset.slideIdx) === index;
            slide.classList.toggle('opacity-100', isActive);
            slide.classList.toggle('z-[1]', isActive);
            slide.classList.toggle('opacity-0', !isActive);
            slide.classList.toggle('pointer-events-none', !isActive);
            // Video only autoplays on the initial page load; later slide
            // switches (thumb/dot/variant clicks, Livewire actions) never
            // (re)trigger playback.
            if (window.sqGalleryReady) return;
            var vid = slide.querySelector('.sq-gallery-video');
            if (vid) {
                if (isActive) { vid.play().catch(function () {}); } else { vid.pause(); }
            }
        });

        document.querySelectorAll('.sq-thumb').forEach(function (thumb) {
            var isActive = Number(thumb.dataset.thumbIdx) === index;
            thumb.classList.toggle('border-[#004AC6]', isActive);
            thumb.classList.toggle('border-transparent', !isActive);
        });

        document.querySelectorAll('.sq-dot').forEach(function (dot) {
            var isActive = Number(dot.dataset.dotIdx) === index;
            dot.classList.toggle('bg-white', isActive);
            dot.classList.toggle('bg-white/40', !isActive);
        });

        window.sqActiveMediaIndex = index;
        sqCheckSlideForVideo(index);
    }

    // Jump the main gallery to a given media index (used on variant select/hover)
    window.sqGoToMediaIndex = function (index) {
        if (index === null || index === undefined) return;
        sqSetActiveSlide(index);
    };

    document.querySelectorAll('.sq-thumb').forEach(function (thumb) {
        var idx = Number(thumb.dataset.thumbIdx);
        thumb.addEventListener('click', function () { window.sqGoToMediaIndex(idx); });
        thumb.addEventListener('mouseenter', function () { window.sqGoToMediaIndex(idx); });
    });

    document.querySelectorAll('.sq-dot').forEach(function (dot) {
        dot.addEventListener('click', function () {
            window.sqGoToMediaIndex(Number(dot.dataset.dotIdx));
        });
    });

    // Touch swipe on the main stage (mobile)
    (function () {
        var stage = document.querySelector('.product-preview-swiper');
        if (!stage) return;
        var startX = 0, startY = 0, isSwiping = false;
        stage.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isSwiping = true;
        }, { passive: true });
        stage.addEventListener('touchend', function (e) {
            if (!isSwiping) return;
            isSwiping = false;
            var dx = e.changedTouches[0].clientX - startX;
            var dy = e.changedTouches[0].clientY - startY;
            if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
            var next = dx < 0 ? window.sqActiveMediaIndex + 1 : window.sqActiveMediaIndex - 1;
            window.sqGoToMediaIndex(next);
        }, { passive: true });
    })();

    sqCheckSlideForVideo(0);
    window.sqGalleryReady = true;
</script>

<script>
    // ── Souqify Product Gallery ──────────────────────────────────


    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('border-blue-700', 'text-blue-700');
                b.classList.add('border-transparent', 'text-neutral-600');
            });
            btn.classList.remove('border-transparent', 'text-neutral-600');
            btn.classList.add('border-blue-700', 'text-blue-700');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
        });
    });

    // Mobile menu
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    menuBtn?.addEventListener('click', () => mobileMenu.classList.remove('hidden'));

    function closeMobileMenu() {
        mobileMenu.classList.add('hidden');
    }
    // Carousel scroll
    function scrollCarousel(id, dir) {
        const el = document.getElementById(id);
        if (el) el.scrollBy({
            left: dir * 300,
            behavior: 'smooth'
        });
    }
</script>

<script>
    // ── Variant selection, qty, and add-to-cart – no Livewire re-render ────────
    const SQ_VARIANTS = @json($variantData ?? []);

    // ── Tracking: ViewContent ─────────────────────────────────────────────
    (function () {
        if (typeof window.trackViewContent !== 'function') return;
        window.trackViewContent({
            content_ids:  [@json($activeVariant?->id ?? $product->id)],
            content_type: 'product',
            content_name: @json($product->translationValue('name') ?? $product->slug),
            value:        parseFloat(@json(number_format($sellPrice * $rate, 2, '.', ''))),
            currency:     @json(data_get($currentCurrency ?? null, 'code', 'USD')),
        });
    })();
    const SQ_CART_ADD_URL = @json($cartAddUrl);
    const SQ_PRODUCT_SLUG = @json($product->slug);
    let SQ_UNIT_PRICE = @json($sellPrice * $rate);
    let sqSelectedVariantId = @json($activeVariant?->id);
    let sqQty = @json($qty);
    const SQ_SHIPPING_THRESHOLD = @json($shippingThreshold ?? 0);

    function sqFormatWeight(grams) {
        grams = grams || 0;
        return grams >= 1000
            ? (grams / 1000).toFixed(2) + @json(__('kg'))
            : grams + @json(__('g'));
    }

    window.storefrontUpdateShippingProgress = function (data) {
        const threshold = data.shippingThreshold ?? SQ_SHIPPING_THRESHOLD;
        if (!threshold) return;
        const pct = Math.max(0, Math.min(100, data.shippingPct ?? 0));
        const remaining = data.remainingForFreeShipping ?? 0;
        const cartWeight = data.cartWeightGrams ?? 0;

        const msgEl = document.getElementById('sq-shipping-message');
        if (msgEl) {
            msgEl.innerHTML = remaining <= 0
                ? '<span class="font-semibold">' + @json(__('You unlocked FREE shipping')) + '</span>'
                : @json(__('Add :weight more to unlock FREE shipping', ['weight' => '__WEIGHT__'])).replace('__WEIGHT__', sqFormatWeight(remaining));
        }

        const ratioEl = document.getElementById('sq-shipping-ratio');
        if (ratioEl) ratioEl.textContent = sqFormatWeight(cartWeight) + '/' + sqFormatWeight(threshold);

        const barEl = document.getElementById('sq-shipping-bar');
        if (barEl) barEl.style.width = pct + '%';
    };

    function sqUpdateAddToCartTotal() {
        const totalEl = document.getElementById('sq-add-to-cart-total');
        if (totalEl) totalEl.textContent = (SQ_UNIT_PRICE * sqQty).toFixed(2);
    }

    function sqSetQty(value) {
        sqQty = Math.max(1, parseInt(value, 10) || 1);
        const el = document.getElementById('sq-qty-display');
        if (el) el.textContent = sqQty;
        sqUpdateAddToCartTotal();
    }

    function sqIncrementQty() {
        sqSetQty(sqQty + 1);
    }

    function sqDecrementQty() {
        sqSetQty(sqQty > 1 ? sqQty - 1 : 1);
    }

    function sqAddToCart() {
        const btn = document.getElementById('sq-add-to-cart-btn');
        const label = document.getElementById('sq-add-to-cart-label');
        const prevLabel = label ? label.innerHTML : '';
        if (btn) { btn.disabled = true; btn.classList.add('opacity-60', 'cursor-not-allowed'); }
        if (label) label.textContent = @json(__('Adding…'));

        window.storefrontCartAdd({
            url: SQ_CART_ADD_URL,
            slug: SQ_PRODUCT_SLUG,
            variantId: sqSelectedVariantId,
            qty: sqQty,
        }).then(function () {
            if (typeof window.trackAddToCart === 'function') {
                var variant = sqSelectedVariantId ? SQ_VARIANTS[sqSelectedVariantId] : null;
                window.trackAddToCart({
                    content_ids:  [sqSelectedVariantId || @json($product->id)],
                    content_type: 'product',
                    content_name: @json($product->translationValue('name') ?? $product->slug),
                    value:        variant ? parseFloat(variant.price || 0) : parseFloat(@json(number_format($sellPrice * $rate, 2, '.', ''))),
                    currency:     @json(data_get($currentCurrency ?? null, 'code', 'USD')),
                    num_items:    sqQty,
                });
            }
        }).finally(function () {
            if (btn) { btn.disabled = false; btn.classList.remove('opacity-60', 'cursor-not-allowed'); }
            if (label) label.innerHTML = prevLabel;
        });
    }

    function sqQuickAddToCart(productId) {
        window.storefrontCartAdd({
            url: SQ_CART_ADD_URL,
            productId: productId,
            qty: 1,
        });
    }

    function sqSelectVariant(id) {
        const data = SQ_VARIANTS[id];
        if (!data) return;
        if (!data.isInStock) {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('storefront-toast', { message: @json(__('This option is out of stock')), type: 'error' });
            }
            return;
        }

        sqSelectedVariantId = id;

        document.querySelectorAll('[data-variant-id]').forEach(function (btn) {
            const isActive = parseInt(btn.dataset.variantId) === id;
            const ring = btn.querySelector('[data-variant-ring]');
            if (ring) {
                if (isActive) {
                    ring.classList.remove('border-transparent', 'ring-1', 'ring-neutral-200', 'ring-neutral-300');
                    ring.classList.add('border-[#004AC6]', 'bg-[#E1E2ED]');
                } else {
                    ring.classList.remove('border-[#004AC6]', 'bg-[#E1E2ED]');
                    ring.classList.add('border-transparent', 'ring-1', 'ring-neutral-200');
                }
            } else {
                // Text pill – classes live on the button itself
                if (isActive) {
                    btn.classList.remove('border-neutral-300');
                    btn.classList.add('border-[#004AC6]', 'bg-[#E1E2ED]', 'text-[#004AC6]');
                } else {
                    btn.classList.remove('border-[#004AC6]', 'bg-[#E1E2ED]', 'text-[#004AC6]');
                    btn.classList.add('border-neutral-300');
                }
            }
        });

        const titleEl = document.getElementById('sq-variant-title');
        if (titleEl) titleEl.textContent = data.title;

        const sellEl = document.getElementById('sq-sell-price');
        if (sellEl) sellEl.textContent = data.displaySell;

        const realEl = document.getElementById('sq-real-price');
        const realVal = document.getElementById('sq-real-price-val');
        const saveBadge = document.getElementById('sq-save-badge');
        const saveVal = document.getElementById('sq-save-amount');
        if (data.hasDiscount && data.displayReal) {
            if (realEl) realEl.classList.remove('hidden');
            if (realVal) realVal.textContent = data.displayReal;
            if (saveBadge) saveBadge.classList.remove('hidden');
            if (saveVal) saveVal.textContent = (parseFloat(data.displayReal) - parseFloat(data.displaySell)).toFixed(2);
        } else {
            if (realEl) realEl.classList.add('hidden');
            if (saveBadge) saveBadge.classList.add('hidden');
        }

        const stockIn = document.getElementById('sq-stock-in');
        const stockOut = document.getElementById('sq-stock-out');
        if (stockIn && stockOut) {
            stockIn.classList.toggle('hidden', !data.isInStock);
            stockOut.classList.toggle('hidden', data.isInStock);
        }

        const addBtn = document.getElementById('sq-add-to-cart-btn');
        if (addBtn) addBtn.disabled = !data.isInStock;

        SQ_UNIT_PRICE = parseFloat(data.displaySell);
        sqUpdateAddToCartTotal();

        const weightBadge = document.getElementById('sq-weight-badge');
        const weightBadgeText = document.getElementById('sq-weight-badge-text');
        if (weightBadge) weightBadge.classList.toggle('hidden', !data.weightDisplay);
        if (weightBadgeText) weightBadgeText.textContent = data.weightDisplay ?? '';

        const itemWeightEl = document.getElementById('sq-item-weight');
        if (itemWeightEl) itemWeightEl.textContent = sqFormatWeight(data.weightGrams) + '.';

        if (data.mediaIndex !== null && data.mediaIndex !== undefined && window.sqGoToMediaIndex) {
            window.sqGoToMediaIndex(data.mediaIndex);
        }
    }
</script>



<script>
    window.__souqifyShareConfig = {
        shareTitle: @json($product->translationValue('name') ?? $product->slug),
        shareDescription: @json(mb_substr($seoDesc ?? '', 0, 200)),
        shareImage: @json($mediaItems->first()['src'] ?? ''),
        shareUrl: @json(route('tenant.storefront.product', $product->slug)),
    };
</script>
<script>
    (function() {
        var shareUrl = '';

        window.souqifyOpenShareModal = function() {
            var cfg = window.__souqifyShareConfig || {};
            shareUrl = cfg.shareUrl || window.location.href;
            var modal = document.getElementById('souqify-share-modal');
            var sheet = document.getElementById('souqify-share-sheet');
            var urlText = document.getElementById('souqify-share-url-text');
            if (!modal) return;

            var encoded = encodeURIComponent(shareUrl);
            var productName = encodeURIComponent(cfg.shareTitle || document.title || '');
            var productDesc = encodeURIComponent(cfg.shareDescription || '');
            var shareText = productName + (productDesc ? '%20%E2%80%94%20' + productDesc : '');

            document.getElementById('souqify-share-whatsapp').href = 'https://wa.me/?text=' + shareText + '%20' + encoded;
            document.getElementById('souqify-share-facebook').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encoded;
            document.getElementById('souqify-share-twitter').href = 'https://twitter.com/intent/tweet?url=' + encoded + '&text=' + productName;
            document.getElementById('souqify-share-telegram').href = 'https://t.me/share/url?url=' + encoded + '&text=' + shareText;

            if (urlText) urlText.textContent = shareUrl;

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    sheet.style.opacity = '1';
                    sheet.style.transform = 'translateY(0)';
                });
            });
        };

        window.souqifyCloseShareModal = function() {
            var modal = document.getElementById('souqify-share-modal');
            var sheet = document.getElementById('souqify-share-sheet');
            if (!modal) return;
            sheet.style.opacity = '0';
            sheet.style.transform = 'translateY(16px)';
            setTimeout(function() {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 200);
        };

        window.souqifyCopyShareUrl = function() {
            if (!shareUrl) return;
            var btn = document.getElementById('souqify-copy-btn');
            navigator.clipboard.writeText(shareUrl).then(function() {
                if (btn) {
                    btn.textContent = '{{ __('Copied!') }}';
                    setTimeout(function() {
                        btn.textContent = '{{ __('Copy') }}';
                    }, 2000);
                }
            }).catch(function() {
                var ta = document.createElement('textarea');
                ta.value = shareUrl;
                ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                if (btn) {
                    btn.textContent = '{{ __('Copied!') }}';
                    setTimeout(function() {
                        btn.textContent = '{{ __('Copy') }}';
                    }, 2000);
                }
            });
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') window.souqifyCloseShareModal();
        });
    })();
</script>

<script>
    // ── Image zoom on hover ────────────────────────────────────────────
    (function () {
        if (window.matchMedia('(hover: none)').matches) return;
        function initZoom() {
            document.querySelectorAll('.product-preview-swiper .sq-main-slide').forEach(function (slide) {
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

@push('head')
@php
    $jsonLdImages = $mediaItems->pluck('src')->filter()->values()->toArray();
    $jsonLdRating = $avgRating > 0 ? $avgRating : null;
    $jsonLdReviewCount = $reviewCount ?? 0;
    $jsonLdDesc = $seoDesc ?? strip_tags($product->translationValue('description') ?? $product->centralProduct?->translationValue('description') ?? '');
    $jsonLdName = $product->translationValue('name') ?? $product->slug;
    $jsonLdSku = $product->sku ?? $product->centralProduct?->sku ?? null;
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
