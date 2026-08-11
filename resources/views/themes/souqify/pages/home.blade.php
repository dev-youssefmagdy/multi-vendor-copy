@php
$banners = $banners ?? collect();
$heroBanner = $banners->first();
$sideBanners = $banners->skip(1)->take(2);
$promoBanners = $banners->skip(3)->take(2);
$rootCats = ($categories ?? collect())->take(10);
$allCats = $categories ?? collect();
$tabKey = $activeProductTab ?? 'cat-flash';

// Flash sale layout
$flash = $flashProducts ?? collect();
$flashHero = $flash->first();
$flashLeft = $flash->skip(1)->take(3);
$flashRight = $flash->skip(4)->take(3);
$flashSaleEnd = optional(($flashSales ?? collect())->first())->end_date;

// Section collections mapped from available component data
$trendingProds = $bestSelling ?? collect();
$topProds = ($bestSelling ?? collect())->take(5);
$newArrivals = $newInProducts ?? collect();
$_flashAll = $flashProducts ?? collect();
$exploreProds = $_flashAll->isNotEmpty() ? $_flashAll : ($newInProducts ?? collect());
$featuredProds = $bestSelling ?? collect();
$hotDealsProds = $flashProducts ?? collect();
$specialProds = $topRatedProducts ?? collect();
$recommendedProds = ($bestSelling ?? collect())->slice(5)->values();

$promoCardBanner = $banners->skip(5)->first() ?? $promoBanners->first() ?? $heroBanner;
$promoCardImage = $promoCardBanner?->image_url ??
'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80';

// Promo card discount: highest active flash-sale % among featured products, else static 30
$promoFlashPct = $featuredProds
    ->map(fn($p) => $p->activeStorefrontFlashSale()?->discount_percentage)
    ->filter()
    ->max();
$promoDiscountPct = $promoFlashPct ? (int) round((float) $promoFlashPct) : 30;

// Currency helpers
$currency = $currentCurrency ?? null;
$symbol = $currency->symbol ?? '$';
$rate = (float) ($currency->conversion_rate ?? 1);
@endphp

<div>
    <!--========= mobile header ==========  -->
    <div class="lg:hidden text-white bg-gradient-to-r from-[#002562] via-[#144AA6] to-[#003387] mb-14 pt-6 px-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <!-- Mobile menu toggle -->
                <button onclick="openMobileMenu()" class="lg:hidden p-2 -ml-2" aria-label="{{ __('Open menu') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <!-- Logo -->
                <a href="{{ route('tenant.home') }}" class="flex items-center gap-2 shrink-0">
                    <x-storefront-logo :storeName="$storeName" class="h-8 sm:h-10 lg:h-12 w-auto" />
                </a>
            </div>
            <div class="flex items-center gap-2">
                <!-- Currency / Language -->
                <button type="button"
                    onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                    class="flex items-center justify-center text-main transition-colors bg-white text-black rounded-full w-10 h-10">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3c2.5 2.6 3.75 5.7 3.75 9s-1.25 6.4-3.75 9c-2.5-2.6-3.75-5.7-3.75-9S9.5 5.6 12 3z" />
                    </svg>
                </button>
                <!-- cart -->
                <a href="{{ route('tenant.storefront.cart') }}"
                    class="flex items-center justify-center text-main transition-colors bg-white text-black rounded-full w-10 h-10">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
        </div>
        <!-- Mobile search -->
        <div class="translate-y-1/2 relative z-50 drop-shadow-2xl px-4 sm:px-6 lg:hidden">
            <form action="{{ route('tenant.storefront.search') }}" method="GET"
                data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                <div class="souqify-search-inner flex items-center bg-white rounded-full px-3 sm:px-5 py-2 gap-2 h-14">
                    <svg class="w-5 h-5 text-neutral-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                        placeholder="{{ __('Search products...') }}"
                        class="flex-1 bg-transparent outline-none text-sm text-black" />
                </div>
            </form>
        </div>
    </div>

    <!-- hero section -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 lg:pt-10">
        <!-- large screen hero -->
        <div class="hidden md:grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6">
            <!-- Main hero -->
            <div class="lg:col-span-8 relative rounded-2xl overflow-hidden h-[400px] sm:h-[500px] lg:h-[600px]">
                @if ($heroBanner && $heroBanner->image_path)
                <img loading="lazy" src="{{ $heroBanner->image_path }}" alt="{{ $heroBanner->title ?? $storeName }}"
                    class="absolute inset-0 w-full h-full object-cover" />
                @else
                <img loading="lazy" src="https://images.unsplash.com/photo-1593344484962-796055d4a3a4?auto=format&fit=crop&w=1400&q=80"
                    alt="" class="absolute inset-0 w-full h-full object-cover" />
                @endif
                <div class="absolute inset-0 hero-overlay"></div>
                <div class="relative h-full flex flex-col justify-center px-6 sm:px-12 max-w-xl">
                    <span
                        class="inline-flex w-fit px-4 py-2 bg-violet-700 rounded-full text-white text-xs font-medium tracking-wide mb-6">
                        {{ $heroBanner?->translationValue('subtitle') ?? __('Limited Edition') }}
                    </span>
                    <h1 class="text-white text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.05] mb-4">
                        {!! $heroBanner?->title ? nl2br(e($heroBanner?->title)) : __('Unleash the<br />Next Gen.') !!}
                    </h1>
                    @if ($heroBanner->description ?? null)
                    <p class="text-white/90 text-base sm:text-lg leading-7 mb-8 max-w-md">
                        {{ $heroBanner->description }}
                    </p>
                    @else
                    <p class="text-white/90 text-base sm:text-lg leading-7 mb-8 max-w-md">
                        {{ __('Experience the future of performance with our exclusive curated collection.') }}
                    </p>
                    @endif
                    <a href="{{ $heroBanner->link ?? route('tenant.storefront.best-selling') }}"
                        class="inline-flex w-fit h-14 px-7 bg-white hover:bg-blue-50 transition rounded-lg items-center text-blue-700 font-medium">
                        {{ __('Explore Collection') }}
                    </a>
                </div>
            </div>
            <!-- Side panels -->
            <div class="lg:col-span-4 grid grid-cols-2 lg:grid-cols-1 gap-4 lg:gap-6">
                @php
                $sideB1 = $sideBanners->get(1);
                $sideB2 = $sideBanners->get(2);
                @endphp
                <div class="relative rounded-xl overflow-hidden bg-violet-700 h-[200px] sm:h-[290px] lg:h-auto group">
                    @if ($sideB1?->image_path)
                    <img loading="lazy" src="{{ $sideB1->image_path }}" alt="{{ $sideB1->title ?? '' }}"
                        class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                    @else
                    <img loading="lazy" src="https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=600&q=80"
                        alt=""
                        class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                    @endif
                    <div class="absolute inset-0 bg-violet-900/40"></div>
                    <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                        <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                            {!! $sideB1?->title ? nl2br(e($sideB1->title)) : __('Best 2024<br class="hidden sm:block" />LED
                            Watch') !!}
                        </h3>
                        <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                            {{ $sideB1?->translationValue('subtitle') ?? __('Starting at') . ' ' . $symbol . '199' }}
                        </p>
                    </div>
                </div>
                <div class="relative rounded-xl overflow-hidden bg-zinc-800 h-[200px] sm:h-[290px] lg:h-auto group">
                    @if ($sideB2?->image_path)
                    <img loading="lazy" src="{{ $sideB2->image_path }}" alt="{{ $sideB2->title ?? '' }}"
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                    @else
                    <img loading="lazy" src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=600&q=80"
                        alt=""
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                    @endif
                    <div class="absolute inset-0 bg-black/40"></div>
                    <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                        <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                            {!! $sideB2?->title ? nl2br(e($sideB2->title)) : __('Find the Best<br
                                class="hidden sm:block" />iPhone Deals') !!}
                        </h3>
                        <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                            {{ $sideB2?->translationValue('subtitle') ?? __('Trade-in & Save') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- small screen hero -->
        <div class="md:hidden swiper hero-slider max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="swiper-wrapper h-64 rounded-xl overflow-hidden">
                <!-- slide one -->
                <div class="swiper-slide">
                    <div class="relative rounded-2xl overflow-hidden p-3 h-full">
                        @if ($heroBanner && $heroBanner->image_path)
                        <img loading="lazy" src="{{ $heroBanner->image_path }}" alt="{{ $heroBanner->title ?? $storeName }}"
                            class="absolute inset-0 w-full h-full object-cover" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1593344484962-796055d4a3a4?auto=format&fit=crop&w=1400&q=80"
                            alt="" class="absolute inset-0 w-full h-full object-cover" />
                        @endif
                        <div class="absolute inset-0 hero-overlay"></div>
                        <div class="relative h-full flex flex-col justify-center px-6 sm:px-12 max-w-xl">
                            <span
                                class="inline-flex w-fit px-4 py-2 bg-violet-700 rounded-full text-white text-xs font-medium tracking-wide mb-6">
                                {{ $heroBanner?->translationValue('subtitle') ?? __('Limited Edition') }}
                            </span>
                            <h1 class="text-white text-2xl font-extrabold leading-[1.05] mb-2">
                                {!! $heroBanner?->title ? nl2br(e($heroBanner?->title)) : __('Unleash the Next
                                Gen.') !!}
                            </h1>
                            @if ($heroBanner->description ?? null)
                            <p class="text-white/90 text-sm leading-7 mb-4">
                                {{ $heroBanner->description }}
                            </p>
                            @else
                            <p class="text-white/90 text-sm leading-7 mb-4">
                                {{ __('Experience the future of performance with our exclusive curated collection.') }}
                            </p>
                            @endif
                            <a href="{{ $heroBanner->link ?? route('tenant.storefront.best-selling') }}"
                                class="inline-flex w-fit h-14 px-7 bg-white hover:bg-blue-50 transition rounded-lg items-center text-blue-700 font-medium">
                                {{ __('Explore Collection') }}
                            </a>
                        </div>
                    </div>
                </div>
                <!-- slide tow -->
                <div class="swiper-slide h-full">
                    <div class="relative rounded-xl overflow-hidden bg-violet-700 h-full">
                        @if ($sideB1?->image_path)
                        <img loading="lazy" src="{{ $sideB1->image_path }}" alt="{{ $sideB1->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=600&q=80"
                            alt=""
                            class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                        @endif
                        <div class="absolute inset-0 bg-violet-900/40"></div>
                        <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                            <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                                {!! $sideB1?->title ? nl2br(e($sideB1->title)) : __('Best 2024<br
                                    class="hidden sm:block" />LED
                                Watch') !!}
                            </h3>
                            <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                                {{ $sideB1?->translationValue('subtitle') ?? __('Starting at') . ' ' . $symbol . '199' }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- slide three -->
                <div class="swiper-slide">
                    <div class="relative rounded-xl overflow-hidden bg-zinc-800 h-full">
                        @if ($sideB2?->image_path)
                        <img loading="lazy" src="{{ $sideB2->image_path }}" alt="{{ $sideB2->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=600&q=80"
                            alt=""
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                        @endif
                        <div class="absolute inset-0 bg-black/40"></div>
                        <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                            <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                                {!! $sideB2?->title ? nl2br(e($sideB2->title)) : __('Find the Best<br
                                    class="hidden sm:block" />iPhone Deals') !!}
                            </h3>
                            <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                                {{ $sideB2?->translationValue('subtitle') ?? __('Trade-in & Save') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========== TRUST BAR =========== -->
    <section
        class="bg-gradient-to-r from-[#002562] via-[#144AA6] to-[#003387] md:from-transparent md:via-transparent md:to-transparent md:bg-[#FFB00A] mt-8 lg:mt-12">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 lg:gap-8 text-white md:text-black">
            <div class="flex items-center md:justify-between gap-4 lg:gap-6 overflow-x-auto py-4 sm:py-5">
                <div class="flex items-center gap-3">
                    <div>
                        <svg class="w-6" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M5.82899 7.33164C5.82899 6.7669 5.37117 6.30908 4.80643 6.30908C4.24169 6.30908 3.78387 6.7669 3.78387 7.33164C3.78303 7.57107 3.86829 7.80282 4.02411 7.9846C4.18111 8.14821 4.23554 8.38484 4.16579 8.6006L4.03643 8.98252C3.9864 9.15427 4.01947 9.33953 4.12583 9.48336C4.2322 9.62719 4.39964 9.71308 4.57851 9.71556H5.03435C5.21914 9.71551 5.39256 9.62631 5.50009 9.47602C5.60762 9.32573 5.63604 9.1328 5.57643 8.95788L5.42243 8.57596C5.35268 8.3602 5.40711 8.12357 5.56411 7.95996C5.72344 7.78812 5.81721 7.56569 5.82899 7.33164Z"
                                fill="currentColor" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M7.73243 3.77116V1.84924C7.73277 1.34715 7.5288 0.866541 7.16743 0.517958C6.80607 0.169376 6.31842 -0.0171659 5.81667 0.0012442H3.79619C3.29444 -0.0171659 2.80679 0.169376 2.44543 0.517958C2.08407 0.866541 1.88009 1.34715 1.88043 1.84924V3.77116C0.604397 4.22044 -0.171639 5.51383 0.0324309 6.85116L0.617631 10.4055C0.906265 11.8213 2.16679 12.8277 3.61139 12.7956H6.00147C7.4564 12.8321 8.72593 11.8149 9.00755 10.387L9.59275 6.83268C9.78575 5.49837 9.00564 4.21454 7.73243 3.77116ZM2.80443 1.84924C2.82775 1.32419 3.27103 0.916702 3.79619 0.937564H5.81667C6.34183 0.916702 6.78511 1.32419 6.80843 1.84924V3.55556H2.80443V1.84924ZM7.88643 10.2022L8.50243 6.63556C8.56356 6.17415 8.41691 5.70938 8.10203 5.3666C7.71911 4.93236 7.16558 4.6871 6.58667 4.69516H3.02619C2.44924 4.68398 1.89602 4.92461 1.51083 5.35428C1.20346 5.70529 1.06803 6.17478 1.14123 6.63556L1.72643 10.2022C1.91742 11.0808 2.71308 11.6945 3.61139 11.656H6.00147C6.89979 11.6945 7.69544 11.0808 7.88643 10.2022Z"
                                fill="currentColor" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-base lg:text-2xl font-medium line-clamp-1 whitespace-nowrap">{{ __('Secure privacy') }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div>
                        <svg class="w-6" viewBox="0 0 13 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M9.856 0H2.464C1.10317 0 0 1.10317 0 2.464V6.16C0 7.52083 1.10317 8.624 2.464 8.624H9.856C11.2168 8.624 12.32 7.52083 12.32 6.16V2.464C12.32 1.10317 11.2168 0 9.856 0ZM2.464 0.924H9.856C10.2644 0.924 10.6561 1.08625 10.9449 1.37506C11.2338 1.66386 11.396 2.05557 11.396 2.464V5.082H0.924V2.464C0.924 1.61348 1.61348 0.924 2.464 0.924ZM2.464 7.7H9.856C10.2644 7.7 10.6561 7.53775 10.9449 7.24894C11.2338 6.96014 11.396 6.56843 11.396 6.16V6.006H0.924V6.16C0.924 7.01052 1.61348 7.7 2.464 7.7Z"
                                fill="currentColor" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-base lg:text-2xl font-medium line-clamp-1 whitespace-nowrap">{{ __('Safe payments') }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div>
                        <svg class="w-6" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M6.16 11.704C9.22183 11.704 11.704 9.22183 11.704 6.16C11.704 3.09817 9.22183 0.616 6.16 0.616C3.09817 0.616 0.616 3.09817 0.616 6.16C0.616 9.22183 3.09817 11.704 6.16 11.704ZM6.16 12.32C9.56217 12.32 12.32 9.56217 12.32 6.16C12.32 2.75783 9.56217 0 6.16 0C2.75783 0 0 2.75783 0 6.16C0 9.56217 2.75783 12.32 6.16 12.32Z"
                                fill="currentColor" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M9.44798 3.77674C9.50858 3.83148 9.54495 3.90804 9.54911 3.98959C9.55327 4.07114 9.52487 4.15101 9.47016 4.21163L5.35651 8.75802L2.86911 6.38365C2.81325 6.32657 2.78175 6.25002 2.78126 6.17016C2.78076 6.0903 2.8113 6.01337 2.86644 5.9556C2.92157 5.89782 2.99699 5.86372 3.07679 5.86049C3.15659 5.85726 3.23452 5.88515 3.29415 5.93828L5.32387 7.8756L9.0134 3.7986C9.04055 3.7686 9.07335 3.74424 9.10992 3.72692C9.14649 3.70959 9.18612 3.69965 9.22653 3.69764C9.26695 3.69564 9.30736 3.70162 9.34547 3.71525C9.38357 3.72887 9.418 3.74956 9.44798 3.77674Z"
                                fill="currentColor" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-base lg:text-2xl font-medium line-clamp-1 whitespace-nowrap">{{ __('Easy recovery') }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div>
                        <svg class="w-6" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M9.23847 1.23242V7.39242C9.23847 8.07002 8.68407 8.62442 8.00647 8.62442H1.23047V4.69434C1.68015 5.23026 2.37009 5.5629 3.13393 5.54442C3.75609 5.5321 4.31663 5.29186 4.73551 4.89146C4.92647 4.7313 5.08664 4.52802 5.20984 4.30626C5.4316 3.9305 5.55479 3.48697 5.54247 3.02497C5.52399 2.30425 5.20368 1.66978 4.70472 1.23242H9.23847Z"
                                stroke="currentColor" stroke-width="0.4928" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M13.5505 8.62408V10.4721C13.5505 11.4946 12.725 12.3201 11.7025 12.3201H11.0865C11.0865 11.6425 10.5321 11.0881 9.85447 11.0881C9.17687 11.0881 8.62247 11.6425 8.62247 12.3201H6.15847C6.15847 11.6425 5.60407 11.0881 4.92647 11.0881C4.24887 11.0881 3.69447 11.6425 3.69447 12.3201H3.07847C2.05591 12.3201 1.23047 11.4946 1.23047 10.4721V8.62408H8.00647C8.68407 8.62408 9.23847 8.06968 9.23847 7.39208V3.08008H10.3719C10.8154 3.08008 11.222 3.32032 11.4438 3.70224L12.4971 5.54408H11.7025C11.3637 5.54408 11.0865 5.82128 11.0865 6.16008V8.00808C11.0865 8.34688 11.3637 8.62408 11.7025 8.62408H13.5505Z"
                                stroke="currentColor" stroke-width="0.4928" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M4.92731 13.5519C5.60773 13.5519 6.15931 13.0003 6.15931 12.3199C6.15931 11.6395 5.60773 11.0879 4.92731 11.0879C4.2469 11.0879 3.69531 11.6395 3.69531 12.3199C3.69531 13.0003 4.2469 13.5519 4.92731 13.5519Z"
                                stroke="currentColor" stroke-width="0.4928" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M9.857 13.5519C10.5374 13.5519 11.089 13.0003 11.089 12.3199C11.089 11.6395 10.5374 11.0879 9.857 11.0879C9.17659 11.0879 8.625 11.6395 8.625 12.3199C8.625 13.0003 9.17659 13.5519 9.857 13.5519Z"
                                stroke="currentColor" stroke-width="0.4928" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M13.5538 7.39195V8.62394H11.7058C11.367 8.62394 11.0898 8.34675 11.0898 8.00795V6.15995C11.0898 5.82115 11.367 5.54395 11.7058 5.54395H12.5005L13.5538 7.39195Z"
                                stroke="currentColor" stroke-width="0.4928" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M5.54589 3.02424C5.55821 3.48624 5.43503 3.92977 5.21327 4.30553C5.09007 4.52729 4.9299 4.73058 4.73894 4.89074C4.32006 5.29114 3.75951 5.53138 3.13735 5.5437C2.37351 5.56218 1.68357 5.22954 1.23389 4.69361C1.14765 4.60121 1.07374 4.4965 1.00598 4.39178C0.765738 4.02834 0.630215 3.59715 0.617895 3.13515C0.599415 2.35899 0.94437 1.65057 1.49877 1.18857C1.91765 0.843613 2.4474 0.628018 3.02644 0.615698C3.67324 0.603378 4.26462 0.837458 4.70814 1.2317C5.2071 1.66906 5.52741 2.30352 5.54589 3.02424Z"
                                stroke="currentColor" stroke-width="0.4928" stroke-miterlimit="10"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2.12109 3.09831L2.74326 3.68964L4.03068 2.44531" stroke="currentColor"
                                stroke-width="0.4928" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                    </div>
                    <span class="text-xs sm:text-base lg:text-2xl font-medium line-clamp-1 whitespace-nowrap">{{ __('Free shipping') }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- =========== TRENDING THIS WEEK =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-16">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg md:text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">
                {{ __('Trending This Week') }}
            </h2>
            <a href="{{ route('tenant.storefront.category') }}?section=trending"
                class="flex items-center gap-1 text-xs md:text-base text-slate-900 hover:text-blue-700 transition">{{ __('view all') }}
                <span class="hidden md:inline">{{ __('products') }}</span>
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
        <div class="swiper product-slide-swiper" wire:ignore>
            <div class="swiper-wrapper">
                @forelse ($trendingProds->take(5) as $product)
                <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                    @include('themes.souqify.pages._product-card', ['badge' => __('Trending')])
                </div>
                @empty
                <p class="col-span-full text-center text-neutral-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- =========== BROWSE BY CATEGORIES =========== -->
    <section class="bg-white mb-6 lg:mb-16" wire:ignore>
        <div class="swiper categories-slide max-w-[1440px] mx-auto p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">{{ __('Browse by Categories') }}</h3>
                <div class="flex items-center gap-2">
                    <button
                        class="swiper-navigation-prev w-8 h-8 rounded-full border border-gray-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        class="swiper-navigation-next w-8 h-8 rounded-full border border-gray-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="swiper-wrapper">
                @forelse ($allCats->take(12) as $cat)
                @php
                $catImg = $cat->thumb_url ?? null;
                $catName = $cat?->translationValue('name') ?? $cat->name ?? $cat->slug;
                $catUrl = route('tenant.storefront.category', $cat->slug);
                @endphp
                <div class="swiper-slide w-fit">
                    <a href="{{ $catUrl }}" class="flex flex-col items-center gap-2 shrink-0 group">
                        <div
                            class="w-20 h-20 lg:w-32 lg:h-32 rounded-lg overflow-hidden border-transparent flex items-center justify-center">
                            @if ($catImg)
                            <img loading="lazy" src="{{ $catImg }}" alt="{{ $catName }}" class="w-full h-full object-cover" />
                            @else
                            <span class="text-2xl">🏷️</span>
                            @endif
                        </div>
                        <span
                            class="text-xs font-medium text-gray-700 group-hover:text-blue-700 transition text-center whitespace-nowrap max-w-[80px] truncate">{{ $catName }}</span>
                    </a>
                </div>
                @empty
                <p class="text-sm text-neutral-400 py-4">{{ __('No categories yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- =========== TODAY'S FLASH SALE =========== -->
    <section class="bg-amber-500" wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <!-- Header -->
            <div class="flex items-center justify-between gap-1 md:gap-6 mb-9 flex-wrap">
                <h2 class="text-sm sm:text-base lg:text-3xl xl:text-5xl font-semibold text-neutral-800">
                    {{ __('Today\'s Flash Sale') }}
                </h2>
                <div class="flex items-center gap-2 me-auto">
                    <span class="hidden xl:inline text-2xl sm:text-3xl lg:text-4xl text-neutral-800">{{ __('Ends in') }}</span>
                    <div class="flex items-center gap-1">
                        <div class="bg-white rounded-xl p-1 xl:p-3 text-center min-w-[30px]">
                            <div class="text-amber-500 text-xs md:text-lg lg:text-2xl lg:text-4xl font-bold leading-none"
                                id="sqFsHours">04</div>
                            <div class="text-neutral-500 text-[10px] md:text-xs lg:text-sm hidden lg:block">{{ __('Hours') }}</div>
                            <div class="text-neutral-500 text-[10px] md:text-xs lg:text-sm lg:hidden">{{ __('hrs') }}</div>
                        </div>
                        <div class="bg-white rounded-xl p-1 xl:p-3 text-center min-w-[30px]">
                            <div class="text-amber-500 text-xs md:text-lg lg:text-2xl lg:text-4xl font-bold leading-none"
                                id="sqFsMins">22</div>
                            <div class="text-neutral-500 text-[10px] md:text-xs lg:text-sm hidden lg:block">{{ __('Minutes') }}</div>
                            <div class="text-neutral-500 text-[10px] md:text-xs lg:text-sm lg:hidden">{{ __('min') }}</div>
                        </div>
                        <div class="bg-white rounded-xl p-1 xl:p-3 text-center min-w-[30px]">
                            <div class="text-amber-500 text-xs md:text-lg lg:text-2xl lg:text-4xl font-bold leading-none"
                                id="sqFsSecs">57</div>
                            <div class="text-neutral-500 text-[10px] md:text-xs lg:text-sm hidden lg:block">{{ __('Seconds') }}</div>
                            <div class="text-neutral-500 text-[10px] md:text-xs lg:text-sm lg:hidden">{{ __('sec') }}</div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('tenant.storefront.category') }}?section=flash_sale"
                    class="flex items-center gap-0.5 md:gap-2 text-neutral-900 text-[10px] lg:text-xl"> <span class="hidden md:inline">{{ __('View')}}</span>{{ __('all products') }}
                    <svg class="w-2 sm:w-4 lg:w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <!-- Flash sale grid: 3 columns small cards | 1 hero card | 3 columns small cards -->
            <div class="grid grid-cols-1 lg:grid-cols-11 gap-5">
                <!-- Left small cards -->
                <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-5 content-start">
                    @forelse ($flashLeft as $prod)
                    @php
                    $fV = $prod->variants->firstWhere('active', true) ?? $prod->variants->first();
                    $fPr = $prod->storefrontPricing($fV);
                    $fSell = number_format((float) $fPr['current_price'] * $rate, 2);
                    $fReal = $fPr['has_discount'] ? number_format((float) $fPr['original_price'] * $rate, 2) : null;
                    $fDisc = $fPr['has_discount'] ? (int) round((float) $fPr['discount_percentage']) : 0;
                    $fImg = $prod->centralProduct?->primary_image_url ?? $prod->primary_image_url ?? null;
                    $fName = $prod?->translationValue('name') ?? $prod->slug;
                    $fBrand = $prod->centralProduct?->brand?->name ?? '';
                    $fRating = (float) ($prod->average_rating ?? 0);
                    $fStars = (int) round($fRating);
                    $fSoldCount = (int) ($prod->centralProduct?->sold_count ?? 0);
                    $fStock = (int) ($prod->centralProduct?->stock ?? $prod->stock ?? 0);
                    $fTotal = $fSoldCount + $fStock;
                    $fSoldPct = $fTotal > 0 ? (int) round($fSoldCount / $fTotal * 100) : 0;
                    @endphp
                    <div class="bg-white rounded-xl p-3 flex gap-3 items-start hover:shadow-md transition cursor-pointer"
                        onclick="sqNavigateToProduct(event, '{{ route('tenant.storefront.product', $prod->slug) }}')">
                        <div class="relative w-36 sm:w-44 product-img-bg rounded-md overflow-hidden shrink-0">
                            @if ($fImg)
                            <a href="{{ route('tenant.storefront.product', $prod->slug) }}">
                                <img loading="lazy" src="{{ $fImg }}" alt="{{ $fName }}" class="w-full h-full object-cover" />
                            </a>
                            @else
                            <div class="w-full h-full flex items-center justify-center text-3xl text-neutral-300">🛍️
                            </div>
                            @endif
                            @if ($fDisc > 0)
                            <span
                                class="absolute top-1 left-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">-{{ $fDisc }}%</span>
                            @endif
                            <button wire:click="addToCart({{ $prod->id }})"
                                class="absolute bottom-1 right-1 w-8 h-8 bg-white rounded-md flex items-center justify-center transition text-lg font-bold"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8.25 7.7502H12.25M10.25 5.7502V9.7502M1.46 3.1502H16.674C18.052 3.1502 19.047 4.4202 18.669 5.6982L17.015 11.2982C16.76 12.1582 15.946 12.7502 15.02 12.7502H5.862C4.935 12.7502 4.12 12.1572 3.866 11.2982L1.46 3.1502ZM1.46 3.1502L0.75 0.750198M14.25 18.7502C14.6478 18.7502 15.0294 18.5922 15.3107 18.3109C15.592 18.0296 15.75 17.648 15.75 17.2502C15.75 16.8524 15.592 16.4708 15.3107 16.1895C15.0294 15.9082 14.6478 15.7502 14.25 15.7502C13.8522 15.7502 13.4706 15.9082 13.1893 16.1895C12.908 16.4708 12.75 16.8524 12.75 17.2502C12.75 17.648 12.908 18.0296 13.1893 18.3109C13.4706 18.5922 13.8522 18.7502 14.25 18.7502ZM6.25 18.7502C6.64782 18.7502 7.02936 18.5922 7.31066 18.3109C7.59196 18.0296 7.75 17.648 7.75 17.2502C7.75 16.8524 7.59196 16.4708 7.31066 16.1895C7.02936 15.9082 6.64782 15.7502 6.25 15.7502C5.85218 15.7502 5.47064 15.9082 5.18934 16.1895C4.90804 16.4708 4.75 16.8524 4.75 17.2502C4.75 17.648 4.90804 18.0296 5.18934 18.3109C5.47064 18.5922 5.85218 18.7502 6.25 18.7502Z"
                                        stroke="#FF570F" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 min-w-0">
                            @if ($fBrand)
                            <p class="text-gray-500 text-xs mb-0.5">{{ $fBrand }}</p>
                            @endif
                            <a href="{{ route('tenant.storefront.product', $prod->slug) }}">
                                <h4
                                    class="text-slate-800 text-sm font-semibold line-clamp-2 leading-tight mb-2 hover:text-blue-700 transition">
                                    {{ $fName }}
                                </h4>
                            </a>
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="text-red-500 font-bold text-base">{{ $symbol }}{{ $fSell }}</span>
                                @if ($fReal)
                                <span class="text-gray-400 text-xs line-through">{{ $symbol }}{{ $fReal }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                @for ($i = 1; $i <= 5; $i++) <svg
                                    class="w-3 h-3 {{ $i <= $fStars ? 'text-yellow-400' : 'text-gray-300' }}"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                    </svg>
                                    @endfor
                            </div>
                            <!-- sold progress -->
                            <div class="flex items-center justify-between flex-wrap my-3">
                                <div class="h-1 bg-[#E5E7EB] rounded-sm w-full overflow-hidden">
                                    <span class="h-full rounded-sm bg-[#DE1709] block"
                                        style="width: {{ $fSoldPct }}%"></span>
                                </div>
                                <p class="text-[#6A7282] text-xs">{{ __('Sold') }}: <span
                                        class="text-[#FF570F]">{{ $fSoldCount }}</span></p>
                                <p class="text-xs text-[#DE1709]">{{ $fStock }} {{ __('left') }}</p>
                            </div>
                            <div class="flex items-center justify-between text-xs mt-1">
                                <span class="text-neutral-800 font-medium">{{ __('Deal Ends') }}</span>
                                <div class="flex items-center gap-px">
                                    <div class="bg-orange-600 text-white px-1.5 py-1 rounded text-xs font-bold sq-fsh2">
                                        04</div>
                                    <div class="bg-orange-600 text-white px-1.5 py-1 rounded text-xs font-bold sq-fsm2">
                                        22</div>
                                    <div class="bg-orange-600 text-white px-1.5 py-1 rounded text-xs font-bold sq-fss2">
                                        57</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white/30 rounded-xl h-40 flex items-center justify-center text-neutral-700 text-sm">
                        {{ __('Flash deals coming soon') }}
                    </div>
                    @endforelse
                </div>
                <!-- Center hero card -->
                @if ($flashHero)
                @php
                $hV = $flashHero->variants->firstWhere('active', true) ?? $flashHero->variants->first();
                $hPr = $flashHero->storefrontPricing($hV);
                $hSell = number_format((float) $hPr['current_price'] * $rate, 2);
                $hReal = $hPr['has_discount'] ? number_format((float) $hPr['original_price'] * $rate, 2) : null;
                $hDisc = $hPr['has_discount'] ? (int) round((float) $hPr['discount_percentage']) : 0;
                $hImg = $flashHero->centralProduct?->primary_image_url ?? $flashHero->primary_image_url ?? null;
                $hName = $flashHero?->translationValue('name') ?? $flashHero->slug;
                $hBrand = $flashHero->centralProduct?->brand?->name ?? $flashHero->centralProduct?->category?->name ??
                '';
                $hRating = (float) ($flashHero->average_rating ?? 0);
                $hStars = (int) round($hRating);
                $hRatingCount = $flashHero->relationLoaded('rates') ? $flashHero->rates->count() : 0;
                $hManageStock = (bool) ($flashHero->centralProduct?->manage_stock ?? false);
                $hStock = (int) ($flashHero->centralProduct?->stock ?? 0);
                $hSoldCount = (int) ($flashHero->centralProduct?->sold_count ?? 0);
                $hTotal = $hSoldCount + $hStock;
                $hSoldPct = $hTotal > 0 ? (int) round($hSoldCount / $hTotal * 100) : 0;
                @endphp
                <div class="lg:col-span-3 bg-white rounded-2xl p-5 flex flex-col cursor-pointer"
                    onclick="sqNavigateToProduct(event, '{{ route('tenant.storefront.product', $flashHero->slug) }}')">
                    <div
                        class="relative bg-gradient-to-br from-violet-50 to-purple-100 rounded-2xl h-56 sm:h-72 mb-4 overflow-hidden">
                        <span
                            class="absolute top-3 left-3 z-10 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded">{{ __('HOT') }}</span>
                        @if ($hImg)
                        <a href="{{ route('tenant.storefront.product', $flashHero->slug) }}">
                            <img loading="lazy" src="{{ $hImg }}" alt="{{ $hName }}" class="w-full h-full object-cover" />
                        </a>
                        @else
                        <div class="w-full h-full flex items-center justify-center text-6xl text-neutral-300">🛍️</div>
                        @endif
                    </div>
                    @if ($hBrand)
                    <p class="text-gray-500 text-xs">{{ $hBrand }}</p>
                    @endif
                    <a href="{{ route('tenant.storefront.product', $flashHero->slug) }}">
                        <h3
                            class="text-slate-800 text-xl sm:text-2xl font-semibold leading-tight mt-1 mb-3 hover:text-blue-700 transition line-clamp-2">
                            {{ $hName }}
                        </h3>
                    </a>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-red-500 text-2xl font-bold">{{ $symbol }}{{ $hSell }}</span>
                        @if ($hReal)
                        <span class="text-gray-400 text-sm line-through">{{ $symbol }}{{ $hReal }}</span>
                        @endif
                        @if ($hDisc > 0)
                        <span
                            class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded">-{{ $hDisc }}%</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex items-center gap-0.5">
                            @for ($i = 1; $i <= 5; $i++) <svg
                                class="w-4 h-4 {{ $i <= $hStars ? 'text-yellow-400' : 'text-gray-300' }}"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                </svg>
                                @endfor
                        </div>
                        <span class="text-gray-500 text-sm">({{ $hRatingCount }})</span>
                    </div>
                    @if ($hManageStock)
                    <div class="mb-4">
                        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-red-700 to-red-500 rounded-full"
                                style="width: {{ $hSoldPct }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="text-gray-500">{{ __('Sold') }}: <span
                                    class="text-orange-600 font-medium">{{ $hSoldCount }}</span></span>
                            <span class="text-red-600">{{ $hStock }} {{ __('left') }}</span>
                        </div>
                    </div>
                    @endif
                    <div class="flex items-center justify-between mt-auto">
                        <span class="text-neutral-800 font-medium">{{ __('Deal Ends in') }}</span>
                        <div class="flex items-center gap-px">
                            <div class="bg-orange-600 text-white p-2 rounded-lg text-lg font-bold sq-fsh2">04</div>
                            <div class="bg-orange-600 text-white p-2 rounded-lg text-lg font-bold sq-fsm2">22</div>
                            <div class="bg-orange-600 text-white p-2 rounded-lg text-lg font-bold sq-fss2">57</div>
                        </div>
                    </div>
                </div>
                @else
                <div class="lg:col-span-3 bg-white rounded-2xl p-5 flex flex-col">
                    <div
                        class="relative bg-gradient-to-br from-violet-50 to-purple-100 rounded-2xl h-56 sm:h-72 mb-4 overflow-hidden">
                        <span
                            class="absolute top-3 left-3 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded">{{ __('HOT') }}</span>
                        <img loading="lazy" src="https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?auto=format&fit=crop&w=600&q=80"
                            alt="" class="w-full h-full object-cover" />
                    </div>
                    <p class="text-gray-500 text-xs">{{ __('Flash Sale') }}</p>
                    <h3 class="text-slate-800 text-xl sm:text-2xl font-semibold leading-tight mt-1 mb-3">
                        {{ __('Flash Sale Deals Coming Soon') }}
                    </h3>
                    <div class="flex items-center justify-between mt-auto">
                        <span class="text-neutral-800 font-medium">{{ __('Deal Ends in') }}</span>
                        <div class="flex items-center gap-px">
                            <div class="bg-orange-600 text-white p-2 rounded-lg text-lg font-bold sq-fsh2">04</div>
                            <div class="bg-orange-600 text-white p-2 rounded-lg text-lg font-bold sq-fsm2">22</div>
                            <div class="bg-orange-600 text-white p-2 rounded-lg text-lg font-bold sq-fss2">57</div>
                        </div>
                    </div>
                </div>
                @endif
                <!-- Right small cards -->
                <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-5 content-start">
                    @forelse ($flashRight as $prod)
                    @php
                    $fV = $prod->variants->firstWhere('active', true) ?? $prod->variants->first();
                    $fPr = $prod->storefrontPricing($fV);
                    $fSell = number_format((float) $fPr['current_price'] * $rate, 2);
                    $fReal = $fPr['has_discount'] ? number_format((float) $fPr['original_price'] * $rate, 2) : null;
                    $fDisc = $fPr['has_discount'] ? (int) round((float) $fPr['discount_percentage']) : 0;
                    $fImg = $prod->centralProduct?->primary_image_url ?? $prod->primary_image_url ?? null;
                    $fName = $prod?->translationValue('name') ?? $prod->slug;
                    $fBrand = $prod->centralProduct?->brand?->name ?? '';
                    $fRating = (float) ($prod->average_rating ?? 0);
                    $fStars = (int) round($fRating);
                    $fSoldCount = (int) ($prod->centralProduct?->sold_count ?? 0);
                    $fStock = (int) ($prod->centralProduct?->stock ?? $prod->stock ?? 0);
                    $fTotal = $fSoldCount + $fStock;
                    $fSoldPct = $fTotal > 0 ? (int) round($fSoldCount / $fTotal * 100) : 0;
                    @endphp
                    <div class="bg-white rounded-xl p-3 flex gap-3 items-start hover:shadow-md transition cursor-pointer"
                        onclick="sqNavigateToProduct(event, '{{ route('tenant.storefront.product', $prod->slug) }}')">
                        <div class="relative w-36 sm:w-44 product-img-bg rounded-md overflow-hidden shrink-0">
                            @if ($fImg)
                            <a href="{{ route('tenant.storefront.product', $prod->slug) }}">
                                <img loading="lazy" src="{{ $fImg }}" alt="{{ $fName }}" class="w-full h-full object-cover" />
                            </a>
                            @else
                            <div class="w-full h-full flex items-center justify-center text-3xl text-neutral-300">🛍️
                            </div>
                            @endif
                            @if ($fDisc > 0)
                            <span
                                class="absolute top-1 left-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">-{{ $fDisc }}%</span>
                            @endif
                            <button wire:click="addToCart({{ $prod->id }})"
                                class="absolute bottom-1 right-1 w-8 h-8 bg-white rounded-md flex items-center justify-center transition text-lg font-bold"
                                aria-label="{{ __('Add to cart') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8.25 7.7502H12.25M10.25 5.7502V9.7502M1.46 3.1502H16.674C18.052 3.1502 19.047 4.4202 18.669 5.6982L17.015 11.2982C16.76 12.1582 15.946 12.7502 15.02 12.7502H5.862C4.935 12.7502 4.12 12.1572 3.866 11.2982L1.46 3.1502ZM1.46 3.1502L0.75 0.750198M14.25 18.7502C14.6478 18.7502 15.0294 18.5922 15.3107 18.3109C15.592 18.0296 15.75 17.648 15.75 17.2502C15.75 16.8524 15.592 16.4708 15.3107 16.1895C15.0294 15.9082 14.6478 15.7502 14.25 15.7502C13.8522 15.7502 13.4706 15.9082 13.1893 16.1895C12.908 16.4708 12.75 16.8524 12.75 17.2502C12.75 17.648 12.908 18.0296 13.1893 18.3109C13.4706 18.5922 13.8522 18.7502 14.25 18.7502ZM6.25 18.7502C6.64782 18.7502 7.02936 18.5922 7.31066 18.3109C7.59196 18.0296 7.75 17.648 7.75 17.2502C7.75 16.8524 7.59196 16.4708 7.31066 16.1895C7.02936 15.9082 6.64782 15.7502 6.25 15.7502C5.85218 15.7502 5.47064 15.9082 5.18934 16.1895C4.90804 16.4708 4.75 16.8524 4.75 17.2502C4.75 17.648 4.90804 18.0296 5.18934 18.3109C5.47064 18.5922 5.85218 18.7502 6.25 18.7502Z"
                                        stroke="#FF570F" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 min-w-0">
                            @if ($fBrand)
                            <p class="text-gray-500 text-xs mb-0.5">{{ $fBrand }}</p>
                            @endif
                            <a href="{{ route('tenant.storefront.product', $prod->slug) }}">
                                <h4
                                    class="text-slate-800 text-sm font-semibold line-clamp-2 leading-tight mb-2 hover:text-blue-700 transition">
                                    {{ $fName }}
                                </h4>
                            </a>
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="text-red-500 font-bold text-base">{{ $symbol }}{{ $fSell }}</span>
                                @if ($fReal)
                                <span class="text-gray-400 text-xs line-through">{{ $symbol }}{{ $fReal }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                @for ($i = 1; $i <= 5; $i++) <svg
                                    class="w-3 h-3 {{ $i <= $fStars ? 'text-yellow-400' : 'text-gray-300' }}"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                    </svg>
                                    @endfor
                            </div>
                            <!-- sold progress -->
                            <div class="flex items-center justify-between flex-wrap my-3">
                                <div class="h-1 bg-[#E5E7EB] rounded-sm w-full overflow-hidden">
                                    <span class="h-full rounded-sm bg-[#DE1709] block"
                                        style="width: {{ $fSoldPct }}%"></span>
                                </div>
                                <p class="text-[#6A7282] text-xs">{{ __('Sold') }}: <span
                                        class="text-[#FF570F]">{{ $fSoldCount }}</span></p>
                                <p class="text-xs text-[#DE1709]">{{ $fStock }} {{ __('left') }}</p>
                            </div>
                            <div class="flex items-center justify-between text-xs mt-1">
                                <span class="text-neutral-800 font-medium">{{ __('Deal Ends') }}</span>
                                <div class="flex items-center gap-px">
                                    <div class="bg-orange-600 text-white px-1.5 py-1 rounded text-xs font-bold sq-fsh2">
                                        04</div>
                                    <div class="bg-orange-600 text-white px-1.5 py-1 rounded text-xs font-bold sq-fsm2">
                                        22</div>
                                    <div class="bg-orange-600 text-white px-1.5 py-1 rounded text-xs font-bold sq-fss2">
                                        57</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white/30 rounded-xl h-40 flex items-center justify-center text-neutral-700 text-sm">
                        {{ __('Flash deals coming soon') }}
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- =========== TOP PRODUCTS =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16" wire:ignore>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Top Products') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}?section=top_products"
                class="flex items-center gap-1 text-slate-900 hover:text-blue-700 transition text-sm sm:text-base">{{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
        <div class="swiper product-slide-swiper">
            <div class="swiper-wrapper">
                @forelse ($topProds->take(5) as $product)
                <div class="swiper-slide w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                    @include('themes.souqify.pages._product-card', ['badge' => __('Top Pick')])
                </div>
                @empty
                <p class="col-span-full text-center text-neutral-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- =========== HERO BANNERS =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12" wire:ignore>
        <div class="swiper banners-slide-swiper">
            @php
            $pb1 = $promoBanners->first();
            $pb2 = $promoBanners->last();
            @endphp
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="relative bg-blue-950 rounded-3xl overflow-hidden p-4 sm:p-8 lg:p-12">
                        @if ($pb1?->image_path)
                        <img loading="lazy" src="{{ $pb1->image_path }}" alt="{{ $pb1->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?auto=format&fit=crop&w=1000&q=80"
                            alt="" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @endif
                        <div class="relative max-w-xs">
                            <p class="text-white text-xs sm:text-sm lg:text-lg xl:text-xl mb-3">
                                {{ $pb1?->translationValue('subtitle') ?? __('New Technology') }}
                            </p>
                            <h3 class="text-white text-lg sm:text-xl xl:text-4xl font-semibold leading-tight mb-6">
                                {!! $pb1?->title ? nl2br(e($pb1->title)) : __('Immerse Yourself in 8K<br
                                    class="xl:hidden" />Reality')
                                !!}
                            </h3>
                            <a href="{{ $pb1?->link ?? route('tenant.storefront.category') }}"
                                class="inline-flex h-9 sm:h-14 px-6 bg-white text-blue-700 hover:bg-blue-50 transition rounded-lg items-center font-medium">
                                {{ __('Shop Now') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="relative bg-indigo-950/90 rounded-3xl overflow-hidden p-4 sm:p-8 lg:p-12">
                        @if ($pb2?->image_path)
                        <img loading="lazy" src="{{ $pb2->image_path }}" alt="{{ $pb2->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1000&q=80"
                            alt="" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @endif
                        <div class="relative max-w-xs">
                            <p class="text-white text-xs sm:text-sm lg:text-lg xl:text-xl mb-3">
                                {{ $pb2?->translationValue('subtitle') ?? __('Seasonal Sale') }}
                            </p>
                            <h3 class="text-white text-lg sm:text-xl xl:text-4xl font-semibold leading-tight mb-6">
                                {!! $pb2?->title ? nl2br(e($pb2->title)) : __('Step Into the Summer<br
                                    class="xl:hidden" />Refresh') !!}
                            </h3>
                            <a href="{{ $pb2?->link ?? route('tenant.storefront.category') }}"
                                class="inline-flex h-9 sm:h-14 px-6 bg-white text-purple-600 hover:bg-purple-50 transition rounded-lg items-center font-medium">
                                {{ __('Shop Now') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- =========== NEW ARRIVALS (orange band) =========== -->
    <section class="bg-gradient-to-r from-orange-700 via-orange-500 to-orange-700" wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-5 lg:py-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">{{ __('New Arrivals') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}?section=new_arrivals"
                    class="flex items-center gap-1 text-white hover:text-amber-100 transition text-sm sm:text-base">{{ __('View all products') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <div class="swiper product-slide-swiper">
                <div class="swiper-wrapper">
                    @forelse ($newArrivals->take(5) as $product)
                    <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                        @include('themes.souqify.pages._product-card', ['badge' => __('New In')])
                    </div>
                    @empty
                    <p class="col-span-full text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- =========== EXPLORE PRODUCTS =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16" wire:ignore>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Explore Products') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}?section=explore"
                class="flex items-center gap-1 text-slate-900 hover:text-blue-700 transition text-sm sm:text-base">{{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
        <div class="swiper product-slide-swiper lg:mb-6">
            <div class="swiper-wrapper">
                @forelse ($exploreProds->take(4) as $product)
                <div class="swiper-slide  w-fit">
                    @include('themes.souqify.pages._explore-card')
                </div>
                @empty
                <p class="col-span-full text-center text-neutral-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="swiper product-slide-swiper hidden lg:block">
            <div class="swiper-wrapper">
                @forelse ($exploreProds->skip(4)->take(4) as $product)
                <div class="swiper-slide w-fit">
                    @include('themes.souqify.pages._explore-card')
                </div>
                @empty
                <p></p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- small screen Promo card -->
    <section class="mb-6 lg:hidden" wire:ignore>
        <div class="p-4 overflow-hidden bg-cover bg-center"
            style="background-image: linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)), url('{{ $promoCardImage }}');">
            <div class="text-blue-700 text-4xl font-bold leading-none">{{ $promoDiscountPct }}%</div>
            <div class="w-12 h-0.5 bg-white my-1"></div>
            <div class="flex justify-between">
                <div>
                    <p class="text-gray-300 text-lg sm:text-xl mb-1">{{ __('PREMIUM TECH') }}</p>
                    <p class="text-[#FF4D00] text-base font-semibold">{{ __('Best Deals') }}</p>
                </div>
                <a href="{{ route('tenant.storefront.category') }}?section=explore"
                    class="h-12 px-6 bg-blue-700 hover:bg-blue-800 transition rounded-full text-white inline-flex items-center font-medium">{{ __('Shop now') }}</a>
            </div>
        </div>
    </section>

    <!-- =========== FEATURED PRODUCTS (dark) =========== -->
    <section class="bg bg-cover bg-center bg-no-repeat"
        style="background-image: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('/souqify/assets/images/featured.jpg');"
        wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">{{ __('Featured Products') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}?section=featured"
                    class="flex items-center gap-1 text-white hover:text-blue-200 transition text-sm sm:text-base">{{ __('View all products') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <div class="flex gap-6">
                <div class="flex-1 max-w-full lg:max-w-[88%]">
                    <div class="swiper featured-product-slide mb-7">
                        <div class="swiper-wrapper">
                            @forelse ($featuredProds->take(3) as $product)
                            <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                                @include('themes.souqify.pages._product-card', ['badge' => __('Featured')])
                            </div>
                            @empty
                            <p class="col-span-full text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="swiper featured-product-slide">
                        <div class="swiper-wrapper">
                            @forelse ($featuredProds->skip(3)->take(3) as $product)
                            <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                                @include('themes.souqify.pages._product-card', ['badge' => __('Featured')])
                            </div>
                            @empty
                            <p class="col-span-full text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <!-- large screen Promo card -->
                <div class="hidden lg:flex flex-col items-center justify-center text-center rounded-2xl p-8 overflow-hidden bg-cover bg-center w-36"
                    style="background-image: linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)), url('{{ $promoCardImage }}');">
                    <div class="text-white text-7xl sm:text-8xl font-bold leading-none">{{ $promoDiscountPct }}<br />%</div>
                    <div class="w-12 h-0.5 bg-white my-6"></div>
                    <p class="text-gray-300 text-lg sm:text-xl mb-1">{{ __('PREMIUM TECH') }}</p>
                    <p class="text-white text-base font-semibold mb-8">{{ __('Best Deals') }}</p>
                    <a href="{{ route('tenant.storefront.category') }}?section=featured"
                        class="h-12 px-6 bg-blue-700 hover:bg-blue-800 transition rounded-full text-white inline-flex items-center font-medium">{{ __('Shop now') }}</a>
                </div>
            </div>
        </div>
    </section>

    <!-- =========== HOT DEALS =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16" wire:ignore>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Hot Deals') }}</h2>
            <a href="{{ route('tenant.storefront.category') }}?section=hot_deals"
                class="flex items-center gap-1 text-slate-900 hover:text-blue-700 transition text-sm sm:text-base">{{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
        <div class="swiper product-slide-swiper">
            <div class="swiper-wrapper">
                @forelse ($hotDealsProds->take(5) as $product)
                <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                    @include('themes.souqify.pages._product-card', ['badge' => __('Hot Deals')])
                </div>
                @empty
                <p class="col-span-full text-center text-neutral-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- =========== SPECIAL OFFERS (dark) =========== -->
    <section class="bg-zinc-900" style="background-image: linear-gradient(#00236046, #003b9a51, #002d7b5a, #0049c654), url('/souqify/assets/images/special.jpeg');" wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">{{ __('Special Offers') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}?section=special_offers"
                    class="flex items-center gap-1 text-white hover:text-blue-200 transition text-sm sm:text-base">{{ __('View all products') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <div class="swiper product-slide-swiper">
                <div class="swiper-wrapper">
                    @forelse (($specialProds->isEmpty() ? $paginatedProducts : $specialProds)->take(5) as $product)
                    <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                        @include('themes.souqify.pages._product-card', ['badge' => __('Special')])
                    </div>
                    @empty
                    <p class="col-span-full text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- =========== TOP RATED PRODUCTS =========== -->
    <section wire:ignore class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Top Rated Products') }}
            </h2>
            <a href="{{ route('tenant.storefront.category') }}?section=top_rated"
                class="flex items-center gap-1 text-slate-900 hover:text-blue-700 transition text-sm sm:text-base">{{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
        <div class="swiper product-slide-swiper">
            <div class="swiper-wrapper">
                @forelse (($topRatedProducts->isEmpty() ? $paginatedProducts : $topRatedProducts)->take(5) as $product)
                <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                    @include('themes.souqify.pages._product-card', ['badge' => __('Top Rated')])
                </div>
                @empty
                <p class="col-span-full text-center text-neutral-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- =========== RECOMMENDED PRODUCTS (infinite scroll) =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12 lg:pb-16" id="sqRecommendedSection"
        data-api-url="{{ route('tenant.storefront.products.json') }}">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Recommended Products') }}
            </h2>
            <a href="{{ route('tenant.storefront.category') }}?section=recommended"
                class="flex items-center gap-1 text-slate-900 hover:text-blue-700 transition text-sm sm:text-base">
                {{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

        <!-- Product grid populated by JS -->
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-6"
            id="sqRecommendedGrid"></div>

        <!-- Scroll sentinel — hidden once all pages are loaded -->
        <div id="sqRecommendedSentinel" class="w-full h-4 mt-4"></div>

        <!-- Spinner -->
        <div id="sqRecommendedLoader" class="hidden w-full justify-center py-8">
            <div class="w-8 h-8 border-4 border-blue-700 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </section>

    <!-- =========== NEWSLETTER =========== -->
    {{--<section class="bg-blue-700">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
            <div class="max-w-2xl mx-auto text-center">
                <h2 class="text-white text-3xl sm:text-4xl lg:text-5xl font-semibold mb-3">
                    {{ __('Stay in the Loop') }}
    </h2>
    <p class="text-blue-100 text-base sm:text-lg mb-8">
        {{ __('Subscribe for exclusive deals, new arrivals, and flash sales — straight to your inbox.') }}
    </p>
    <form onsubmit="sqNewsletterSubmit(event)" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
        <input type="email" name="email" required placeholder="{{ __('Enter your email') }}"
            class="flex-1 h-14 px-5 rounded-lg text-slate-900 placeholder-neutral-400 outline-none focus:ring-2 focus:ring-white/50" />
        <button type="submit"
            class="h-14 px-7 bg-amber-500 hover:bg-amber-400 text-neutral-900 font-semibold rounded-lg transition whitespace-nowrap">
            {{ __('Subscribe') }}
        </button>
    </form>
</div>
</div>
</section>--}}
</div>

@push('scripts')
<script>
    (function() {
        var sqProductSliderConfig = {
            spaceBetween: 12,
            slidesPerView: "auto",
            freeMode: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            breakpoints: {
                1024: {
                    spaceBetween: 24
                },
            }
        };

        function sqDestroySwiper(selector) {
            document.querySelectorAll(selector).forEach(function(element) {
                if (element.swiper) {
                    element.swiper.destroy(true, true);
                }
            });
        }

        function sqInitSwipers() {
            sqDestroySwiper('.hero-slider');
            sqDestroySwiper('.product-slide-swiper');
            sqDestroySwiper('.categories-slide');
            sqDestroySwiper('.featured-product-slide');
            sqDestroySwiper('.banners-slide-swiper');

            var heroElement = document.querySelector('.hero-slider');
            if (heroElement) {
                new Swiper(heroElement, {
                    loop: true,
                    effect: 'fade',
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                    },
                });
            }

            document.querySelectorAll('.product-slide-swiper').forEach(function(element) {
                new Swiper(element, sqProductSliderConfig);
            });

            var categoriesElement = document.querySelector('.categories-slide');
            if (categoriesElement) {
                new Swiper(categoriesElement, {
                    loop: true,
                    spaceBetween: 16, // Updated to match the 8px gap from your CSS
                    slidesPerView: "auto",
                    freeMode: true,
                    navigation: {
                        nextEl: '.swiper-navigation-next',
                        prevEl: '.swiper-navigation-prev',
                    },
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                });
            }

            document.querySelectorAll('.featured-product-slide').forEach(function(element) {
                new Swiper(element, {
                    spaceBetween: 24,
                    slidesPerView: "auto",
                    freeMode: true,
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                });
            });

            var bannersElement = document.querySelector('.banners-slide-swiper');
            if (bannersElement) {
                new Swiper(bannersElement, {
                    spaceBetween: 24,
                    slidesPerView: 1.1,
                    freeMode: true,
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                    breakpoints: {
                        375: {
                            slidesPerView: 1.2,
                            spaceBetween: 10
                        },
                        768: {
                            slidesPerView: 1.5,
                            spaceBetween: 10
                        },
                        1024: {
                            slidesPerView: 1.7,
                            spaceBetween: 10
                        },
                        1440: {
                            slidesPerView: 2,
                            spaceBetween: 10
                        },
                    }
                });
            }
        }

        window.sqInitSwipers = sqInitSwipers;
        sqInitSwipers();

        if (!window.__sqSwiperNavigatedBound) {
            window.__sqSwiperNavigatedBound = true;
            document.addEventListener('livewire:navigated', sqInitSwipers);
        }
    })();
</script>
<script>
    function sqNewsletterSubmit(e) {
        e.preventDefault();
        var form = e.target;
        var email = form.querySelector('[name="email"]').value;
        if (!email) return;
        Swal.fire({
            icon: 'success',
            title: '{{ __("Thank you!") }}',
            text: '{{ __("You have subscribed successfully.") }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        form.reset();
    }

    function sqNavigateToProduct(event, url) {
        if (event.target.closest('a, button, input, textarea, select, label, [role="button"]')) {
            return;
        }

        if (window.Livewire && typeof window.Livewire.navigate === 'function') {
            window.Livewire.navigate(url);
            return;
        }

        window.location.href = url;
    }

    (function() {
        var fsEnd = {{ $flashSaleEnd ? $flashSaleEnd->timestamp * 1000 : 'Date.now() + 4 * 3600 * 1000' }};
        var fsTimer = null;

        function sqPad(n) {
            return String(n).padStart(2, '0');
        }

        function sqFsTick() {
            var diff = Math.max(0, Math.floor((fsEnd - Date.now()) / 1000));
            var h = Math.floor(diff / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;
            var ph = sqPad(h),
                pm = sqPad(m),
                ps = sqPad(s);

            var eh = document.getElementById('sqFsHours');
            var em = document.getElementById('sqFsMins');
            var es = document.getElementById('sqFsSecs');
            if (eh) eh.textContent = ph;
            if (em) em.textContent = pm;
            if (es) es.textContent = ps;

            document.querySelectorAll('.sq-fsh2').forEach(function(el) {
                el.textContent = ph;
            });
            document.querySelectorAll('.sq-fsm2').forEach(function(el) {
                el.textContent = pm;
            });
            document.querySelectorAll('.sq-fss2').forEach(function(el) {
                el.textContent = ps;
            });
        }

        function sqFsInit() {
            clearInterval(fsTimer);
            sqFsTick();
            fsTimer = setInterval(sqFsTick, 1000);
        }

        sqFsInit();
        document.addEventListener('livewire:navigated', sqFsInit);
        document.addEventListener('livewire:navigating', function() {
            clearInterval(fsTimer);
        });
    })();
</script>
<script>
    // ── Recommended Products — infinite scroll ─────────────────────────────
    (function() {
        var _grid = null;
        var _sentinel = null;
        var _loader = null;
        var _observer = null;
        var _loading = false;
        var _nextPage = 1;
        var _hasMore = true;
        var _apiUrl = null;

        function sqRecInit() {
            _grid = document.getElementById('sqRecommendedGrid');
            _sentinel = document.getElementById('sqRecommendedSentinel');
            _loader = document.getElementById('sqRecommendedLoader');
            var section = document.getElementById('sqRecommendedSection');

            if (!_grid || !section) return;

            _apiUrl = section.dataset.apiUrl;
            _nextPage = 1;
            _hasMore = true;
            _loading = false;
            _grid.innerHTML = '';
            if (_sentinel) _sentinel.style.display = '';

            if (_observer) {
                _observer.disconnect();
                _observer = null;
            }

            _observer = new IntersectionObserver(function(entries) {
                if (!entries[0].isIntersecting || _loading || !_hasMore) return;
                sqRecLoadPage();
            }, {
                rootMargin: '500px'
            });

            if (_sentinel) _observer.observe(_sentinel);
        }

        function sqRecLoadPage() {
            if (_loading || !_hasMore) return;
            _loading = true;

            if (_loader) {
                _loader.style.display = 'flex';
            }

            var url = _apiUrl + (_apiUrl.indexOf('?') === -1 ? '?' : '&') + 'page=' + _nextPage;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.cards && data.cards.length && _grid) {
                        data.cards.forEach(function(html) {
                            var wrap = document.createElement('div');
                            wrap.innerHTML = html.trim();
                            var card = wrap.firstElementChild;
                            if (card) _grid.appendChild(card);
                        });
                    }
                    _hasMore = !!data.has_more;
                    _nextPage = data.next_page || (_nextPage + 1);

                    if (!_hasMore && _sentinel) {
                        _sentinel.style.display = 'none';
                    }
                })
                .catch(function() {
                    /* silently fail */
                })
                .finally(function() {
                    _loading = false;
                    if (_loader) {
                        _loader.style.display = 'none';
                    }
                });
        }

        // Init on first load
        sqRecInit();

        // Re-init on Livewire SPA navigation
        document.addEventListener('livewire:navigated', sqRecInit);
    })();
</script>
@endpush
