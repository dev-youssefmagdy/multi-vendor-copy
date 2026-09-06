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
