@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);
    $catName = $category
        ? ($category->translationValue('name') ?? $category->name ?? $category->slug)
        : __('All Products');
    $catDesc = $category ? ($category->translationValue('description') ?? $category->description ?? '') : '';
    $totalItems = $products->total();
    $parentName = $parentCategory ? ($parentCategory->translationValue('name') ?? $parentCategory->name) : null;
@endphp

<div>
    <!-- =========== BREADCRUMB =========== -->
    <div class="bg-white border-b border-neutral-200">
        <div
            class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center gap-2 text-sm text-neutral-600 overflow-x-auto no-scrollbar">
            <a href="{{ route('tenant.home') }}" class="hover:text-blue-700 transition shrink-0">{{ __('Home') }}</a>
            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            @if ($slug)
                <a href="{{ route('tenant.storefront.category') }}"
                    class="hover:text-blue-700 transition shrink-0">{{ __('Categories') }}</a>
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                @if ($parentCategory)
                    <a href="{{ route('tenant.storefront.category', $parentCategory->slug) }}"
                        class="hover:text-blue-700 transition shrink-0">{{ $parentName }}</a>
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                @endif
                <span class="text-blue-700 font-medium shrink-0">{{ $catName }}</span>
            @else
                <span class="text-blue-700 font-medium shrink-0">{{ __('All Products') }}</span>
            @endif
        </div>
    </div>

    <!-- =========== MAIN LAYOUT =========== -->
    <div class="max-w-[1440px] mx-auto">
        <div class="flex flex-col lg:flex-row">

            <!-- ========= SIDEBAR FILTERS ========= -->
            <aside id="filtersSidebar"
                class="hidden lg:flex bg-white w-full lg:w-80 shrink-0 px-6 lg:px-10 py-8 flex-col gap-10">

                <!-- Categories -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-zinc-900 text-lg font-medium">{{ __('Categories') }}</h3>
                        @if ($slug)
                            <a href="{{ route('tenant.storefront.category') }}"
                                class="text-xs text-blue-700 hover:underline transition">{{ __('All') }}</a>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1">
                        @foreach ($relatedCategories as $cat)
                                            @php
                                                $catLabel = $cat->translationValue('name') ?? $cat->name ?? $cat->slug;
                                                $isCurrentCat = $category && $category->id === $cat->id;
                                            @endphp
                                            <a href="{{ route('tenant.storefront.category', $cat->slug) }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                                                                                                                                            {{ $isCurrentCat
                            ? 'bg-blue-50 text-blue-700 font-medium'
                            : 'text-gray-700 hover:bg-neutral-50 hover:text-blue-700' }}">
                                                <span>{{ $catLabel }}</span>
                                                @if ($isCurrentCat)
                                                    <svg class="w-4 h-4 text-blue-700 shrink-0" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 13l4 4L19 7" />
                                                    </svg>
                                                @endif
                                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Price -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Price') }}</h3>
                    <div class="relative pt-2 pb-1">
                        <div class="h-1 bg-slate-200 rounded-full relative">
                            <div id="priceFill" class="absolute h-1 bg-blue-700 rounded-full"
                                style="left: {{ $min ? round(($min / 500) * 100) : 0 }}%; right: {{ $max ? round((1 - ($max / 500)) * 100) : 0 }}%;">
                            </div>
                        </div>
                        <input type="range" min="0" max="500" value="{{ $min ?: 0 }}" id="priceMin" class="range-thumb"
                            style="top: 7px;">
                        <input type="range" min="0" max="500" value="{{ $max ?: 500 }}" id="priceMax"
                            class="range-thumb" style="top: 7px;">
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-700">
                        <span id="priceMinLabel">{{ $symbol }}{{ $min ?: 0 }}</span>
                        <span id="priceMaxLabel">{{ $symbol }}{{ $max ?: 500 }}</span>
                    </div>
                </div>

                <!-- Availability -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Availability') }}</h3>
                    <div class="flex flex-col gap-2">
                        @php
                            $availOpts = [
                                ['value' => 'in_stock', 'label' => __('In Stock')],
                                ['value' => 'out_of_stock', 'label' => __('Out of Stock')],
                            ];
                        @endphp
                        @foreach ($availOpts as $opt)
                            @php $isActive = $availability === $opt['value']; @endphp
                            <button wire:click="$set('availability', '{{ $isActive ? '' : $opt['value'] }}')"
                                class="flex items-center gap-3 text-left hover:opacity-80 transition w-full">
                                <span
                                    class="w-4 h-4 border-2 rounded shrink-0 flex items-center justify-center
                                                            {{ $isActive ? 'border-blue-700 bg-blue-700' : 'border-gray-400' }}">
                                    @if ($isActive)
                                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </span>
                                <span
                                    class="text-sm {{ $isActive ? 'text-blue-700 font-medium' : 'text-gray-700' }}">{{ $opt['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Rating -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Product rating') }}</h3>
                    <div class="flex flex-col gap-2">
                        @foreach ([4, 3, 2, 1] as $stars)
                            @php $isActive = (string) $ratings === (string) $stars; @endphp
                            <button wire:click="$set('ratings', '{{ $isActive ? '' : $stars }}')"
                                class="flex items-center gap-2 hover:opacity-80 transition {{ $isActive ? 'opacity-100' : 'opacity-70' }}">
                                <div class="flex">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $stars ? 'text-amber-400' : 'text-gray-300' }}"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-sm {{ $isActive ? 'text-blue-700 font-bold' : 'text-gray-700' }}">
                                    &amp; {{ __('up') }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Clear All -->
                <div class="pt-2">
                    <button wire:click="clearFilters"
                        class="w-full h-11 border border-neutral-300 hover:bg-neutral-50 text-neutral-700 text-sm font-medium rounded-lg transition">
                        {{ __('Clear all') }}
                    </button>
                </div>

            </aside>

            <!-- ========= MAIN CONTENT ========= -->
            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-10">

                <!-- Page header -->
                <div class="mb-6 lg:mb-8 flex items-center md:block justify-between">
                    <h1 class="text-2xl sm:text-3xl text-neutral-800 font-medium mb-2">{{ $catName }}</h1>
                    @if ($catDesc)
                        <p class="text-neutral-500 text-base mb-1">{{ $catDesc }}</p>
                    @endif
                    <p class="text-blue-700 text-base">{{ number_format($totalItems) }} {{ __('items') }}</p>
                </div>

                <!-- Toolbar: mobile filter trigger + sort tabs -->
                <div class="flex flex-col gap-4 mb-6">
                    <!-- Mobile filter button -->
                    <div class="lg:hidden flex items-center gap-3">
                        <button id="openFiltersBtn"
                            class="flex items-center gap-2 px-4 py-2 border border-neutral-300 rounded-lg text-sm font-medium hover:bg-neutral-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            {{ __('Filters') }}
                        </button>
                        <span class="text-sm text-neutral-500">{{ number_format($totalItems) }}
                            {{ __('results') }}</span>
                    </div>

                    <!-- Sort -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        <span class="text-gray-700 text-base shrink-0">{{ __('Sort by:') }}</span>
                        <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                            @php
                                $sortTabs = [
                                    'latest' => __('Most Popular'),
                                    'rating' => __('Top Rated'),
                                    'old' => __('New Arrivals'),
                                    'ascending' => __('Price: Low to High'),
                                    'descending' => __('Price: High to Low'),
                                ];
                            @endphp
                            @foreach ($sortTabs as $sortKey => $sortLabel)
                                                    <button wire:click="$set('sort', '{{ $sortKey }}')"
                                                        class="px-4 py-2 text-sm rounded-full whitespace-nowrap transition
                                                                                                                                                                                                                {{ $sort === $sortKey
                                ? 'bg-blue-700 text-white hover:bg-blue-800'
                                : 'bg-white text-gray-700 border border-neutral-200 hover:border-blue-300 hover:text-blue-700' }}">{{ $sortLabel }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Product grid -->
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5" id="productsGrid">

                    @forelse ($products as $product)
                        @php
                            $variant = $product->variants->where('active', true)->sortBy(
                                fn($v) => $v->sellPriceForCountry((int) session('storefront_country_id') ?: null)
                            )->first() ?? $product->variants->first();
                            $pricing = $product->storefrontPricing($variant);
                            $sellPrice = (float) $pricing['current_price'];
                            $realPrice = $pricing['original_price'];
                            $hasDiscount = (bool) $pricing['has_discount'];
                            $discountPct = $hasDiscount ? (int) round((float) $pricing['discount_percentage']) : 0;
                            $displaySell = number_format($sellPrice * $rate, 2);
                            $displayReal = $hasDiscount && $realPrice !== null ? number_format((float) $realPrice * $rate, 2) : null;
                            $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
                            $rating = (float) ($product->average_rating ?? 0);
                            $reviewCount = (int) ($product->reviews_count ?? 0);
                            $productName = $product->translationValue('name') ?? $product->slug;
                            $productUrl = route('tenant.storefront.product', $product->slug);
                            $weight = $product->centralProduct?->weight ?? null;
                            $favData = json_encode([
                                'id' => $product->id,
                                'name' => $productName,
                                'price' => $displaySell,
                                'image' => $img,
                            ]);
                        @endphp

                        <div class="bg-white rounded-xl p-4 hover:shadow-md transition group hidden lg:block">
                            <!-- Image -->
                            <a href="{{ $productUrl }}"
                                class="relative product-img-bg rounded-lg aspect-square overflow-hidden mb-4 block">
                                @if ($img)
                                    <img loading="lazy" src="{{ $img }}" alt="{{ $productName }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                @else
                                    <div class="w-full h-full bg-zinc-100 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-zinc-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif

                                <!-- Top-left: rating badge -->
                                @if ($rating > 0)
                                    <span
                                        class="absolute top-0 left-0 bg-gradient-to-br from-red-700 to-red-600 text-white text-xs font-bold px-2 py-1 rounded-br-lg flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                        </svg>
                                        {{ number_format($rating, 1) }}
                                    </span>
                                @endif

                                <!-- Top-right: weight badge -->
                                @if ($weight)
                                    <span
                                        class="absolute top-0 right-0 bg-blue-700 text-white text-xs font-medium px-2 py-1 rounded-bl-lg flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                                        </svg>
                                        {{ $weight }}g
                                    </span>
                                @endif

                                <!-- Bottom-left: trending tag -->
                                <div
                                    class="absolute bottom-0 left-0 bg-gradient-to-br from-red-700 to-red-600 text-white text-xs font-medium px-3 py-1.5 rounded-tr-2xl">
                                    🔥 {{ __('Trending Now') }}
                                </div>
                            </a>

                            <!-- Name -->
                            <h3 class="text-zinc-900 text-base sm:text-lg font-medium mb-2 line-clamp-2 min-h-[3rem]">
                                {{ $productName }}
                            </h3>

                            <!-- Price -->
                            <div class="flex items-center gap-2 mb-3 flex-wrap">
                                <span class="text-blue-700 text-lg sm:text-xl font-medium">
                                    {{ $symbol }}{{ $displaySell }}
                                </span>
                                @if ($displayReal)
                                    <span class="text-neutral-400 text-sm line-through">
                                        {{ $symbol }}{{ $displayReal }}
                                    </span>
                                    <span class="text-orange-600 text-xs">
                                        {{ $discountPct }}% {{ __('off') }}
                                    </span>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button wire:click="addToCart({{ $product->id }})"
                                    class="flex-1 h-12 px-4 bg-blue-700 hover:bg-blue-800 text-white rounded-lg flex items-center justify-center gap-2 transition text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ __('Add to cart') }}
                                </button>
                                <button
                                    class="souqify-fav-btn w-12 h-12 border border-blue-700 hover:bg-blue-50 rounded-lg flex items-center justify-center text-blue-700 transition"
                                    onclick="souqifyToggleFavorite(this)" data-slug="{{ $product->slug }}"
                                    data-fav='{{ $favData }}'>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="lg:hidden">
                            @include('themes.souqify.pages._product-card', ['badge' => __('Hot Deals')])
                        </div>

                    @empty
                        <div class="col-span-3 py-20 flex flex-col items-center justify-center text-center gap-4">
                            <svg class="w-16 h-16 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17" />
                            </svg>
                            <p class="text-neutral-500 text-lg">{{ __('No products found') }}</p>
                        </div>
                    @endforelse

                </div>

                <!-- Infinite scroll sentinel + loader -->
                @if ($hasMore)
                    <div id="loadMoreSentinel" class="w-full h-4 mt-4"></div>
                @endif
                <div wire:loading.flex class="w-full justify-center py-8">
                    <div class="w-8 h-8 border-4 border-blue-700 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile filters drawer -->
    <div id="filtersDrawer" class="fixed inset-0 z-[60] hidden lg:hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeFilters()"></div>
        <aside class="absolute right-0 top-0 h-full w-[88%] max-w-sm bg-white shadow-2xl overflow-y-auto">
            <div class="sticky top-0 bg-white px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <span class="text-lg font-semibold">{{ __('Filters') }}</span>
                <button onclick="closeFilters()" class="p-1"><svg class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <div id="drawerContent" class="px-6 py-6 flex flex-col gap-8">

                <!-- Categories -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Categories') }}</h3>
                    <div class="flex flex-col gap-1">
                        @foreach ($relatedCategories as $cat)
                            @php
                                $catLabel = $cat->translationValue('name') ?? $cat->name ?? $cat->slug;
                                $isCurrentCat = $category && $category->id === $cat->id;
                            @endphp
                            <a href="{{ route('tenant.storefront.category', $cat->slug) }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                {{ $isCurrentCat
                                    ? 'bg-blue-50 text-blue-700 font-medium'
                                    : 'text-gray-700 hover:bg-neutral-50 hover:text-blue-700' }}">
                                <span>{{ $catLabel }}</span>
                                @if ($isCurrentCat)
                                    <svg class="w-4 h-4 text-blue-700 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Price -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Price') }}</h3>
                    <div class="relative pt-2 pb-1">
                        <div class="h-1 bg-slate-200 rounded-full relative">
                            <div id="mobilePriceFill" class="absolute h-1 bg-blue-700 rounded-full"
                                style="left: {{ $min ? round(($min / 500) * 100) : 0 }}%; right: {{ $max ? round((1 - ($max / 500)) * 100) : 0 }}%;">
                            </div>
                        </div>
                        <input type="range" min="0" max="500" value="{{ $min ?: 0 }}" id="mobilePriceMin"
                            class="range-thumb" style="top: 7px;">
                        <input type="range" min="0" max="500" value="{{ $max ?: 500 }}" id="mobilePriceMax"
                            class="range-thumb" style="top: 7px;">
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-700">
                        <span id="mobilePriceMinLabel">{{ $symbol }}{{ $min ?: 0 }}</span>
                        <span id="mobilePriceMaxLabel">{{ $symbol }}{{ $max ?: 500 }}</span>
                    </div>
                </div>

                <!-- Availability -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Availability') }}</h3>
                    <div class="flex flex-col gap-2">
                        @foreach ($availOpts as $opt)
                            @php $isActive = $availability === $opt['value']; @endphp
                            <button type="button" data-mobile-filter="availability"
                                data-value="{{ $opt['value'] }}"
                                class="mobile-filter-btn flex items-center gap-3 text-left hover:opacity-80 transition w-full"
                                data-active="{{ $isActive ? '1' : '0' }}">
                                <span
                                    class="mobile-filter-check w-4 h-4 border-2 rounded shrink-0 flex items-center justify-center
                                        {{ $isActive ? 'border-blue-700 bg-blue-700' : 'border-gray-400' }}">
                                    <svg class="mobile-filter-tick w-2.5 h-2.5 text-white {{ $isActive ? '' : 'hidden' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span
                                    class="mobile-filter-label text-sm {{ $isActive ? 'text-blue-700 font-medium' : 'text-gray-700' }}">{{ $opt['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Rating -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-zinc-900 text-lg font-medium">{{ __('Product rating') }}</h3>
                    <div class="flex flex-col gap-2">
                        @foreach ([4, 3, 2, 1] as $stars)
                            @php $isActive = (string) $ratings === (string) $stars; @endphp
                            <button type="button" data-mobile-filter="ratings" data-value="{{ $stars }}"
                                data-active="{{ $isActive ? '1' : '0' }}"
                                class="mobile-filter-btn flex items-center gap-2 hover:opacity-80 transition {{ $isActive ? 'opacity-100' : 'opacity-70' }}">
                                <div class="flex">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $stars ? 'text-amber-400' : 'text-gray-300' }}"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z" />
                                        </svg>
                                    @endfor
                                </div>
                                <span
                                    class="mobile-filter-label text-sm {{ $isActive ? 'text-blue-700 font-bold' : 'text-gray-700' }}">
                                    &amp; {{ __('up') }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

            </div>
            <div class="sticky bottom-0 bg-white border-t border-neutral-200 px-6 py-4 flex gap-3">
                <button wire:click="clearFilters" onclick="closeFilters()"
                    class="flex-1 h-11 border border-neutral-300 hover:bg-neutral-50 text-neutral-700 text-sm font-medium rounded-lg transition">{{ __('Clear') }}</button>
                <button id="applyFiltersBtn"
                    class="flex-1 h-11 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg transition">{{ __('Apply') }}</button>
            </div>
        </aside>
    </div>
</div>

@push('styles')
    <style>
        /* Dual-thumb price range slider */
        input[type="range"].range-thumb {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            pointer-events: none;
            position: absolute;
            width: 100%;
            height: 4px;
            outline: none;
        }

        input[type="range"].range-thumb::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            pointer-events: auto;
            width: 16px;
            height: 16px;
            border-radius: 9999px;
            background: #fff;
            border: 2px solid #1d4ed8;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .1);
            cursor: pointer;
        }

        input[type="range"].range-thumb::-moz-range-thumb {
            pointer-events: auto;
            width: 16px;
            height: 16px;
            border-radius: 9999px;
            background: #fff;
            border: 2px solid #1d4ed8;
            cursor: pointer;
        }
    </style>
@endpush

@script
<script>
    (function () {
        // ── Price range dual-thumb slider ──────────────────────────────────────
        const priceMin = document.getElementById('priceMin');
        const priceMax = document.getElementById('priceMax');
        const priceFill = document.getElementById('priceFill');
        const priceMinLabel = document.getElementById('priceMinLabel');
        const priceMaxLabel = document.getElementById('priceMaxLabel');
        const currencySymbol = @json($symbol);

        function updatePriceRange(source) {
            if (!priceMin || !priceMax) return;
            let minVal = parseInt(priceMin.value);
            let maxVal = parseInt(priceMax.value);
            if (minVal > maxVal - 20) {
                if (source === 'min') minVal = maxVal - 20;
                else maxVal = minVal + 20;
                priceMin.value = minVal;
                priceMax.value = maxVal;
            }
            const minPct = (minVal / 500) * 100;
            const maxPct = 100 - (maxVal / 500) * 100;
            if (priceFill) {
                priceFill.style.left = minPct + '%';
                priceFill.style.right = maxPct + '%';
            }
            if (priceMinLabel) priceMinLabel.textContent = currencySymbol + minVal;
            if (priceMaxLabel) priceMaxLabel.textContent = currencySymbol + maxVal;
        }

        // Update visual fill on every drag move
        priceMin?.addEventListener('input', () => updatePriceRange('min'));
        priceMax?.addEventListener('input', () => updatePriceRange('max'));

        // Fire Livewire only on release (mouseup/touchend) to avoid excessive requests
        priceMin?.addEventListener('change', () => {
            $wire.set('min', priceMin.value);
        });
        priceMax?.addEventListener('change', () => {
            $wire.set('max', priceMax.value);
        });

        // Sync fill position on initial load
        updatePriceRange('max');

        // ── Mobile filters drawer ───────────────────────────────────────────────
        // Selections inside the drawer are staged locally (deferred, no request)
        // and only committed to the server when the "Apply" button is pressed.
        const filtersDrawer = document.getElementById('filtersDrawer');
        const openFiltersBtn = document.getElementById('openFiltersBtn');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');

        openFiltersBtn?.addEventListener('click', () => {
            filtersDrawer.classList.remove('hidden');
        });

        document.querySelectorAll('.mobile-filter-btn[data-mobile-filter]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const prop = btn.dataset.mobileFilter;
                const value = btn.dataset.value;
                const wasActive = btn.dataset.active === '1';
                const nextValue = wasActive ? '' : value;

                // Stage the change client-side only — no network request yet.
                $wire.set(prop, nextValue, false);

                document.querySelectorAll(`.mobile-filter-btn[data-mobile-filter="${prop}"]`).forEach((sibling) => {
                    const isActive = !wasActive && sibling === btn;
                    sibling.dataset.active = isActive ? '1' : '0';

                    const check = sibling.querySelector('.mobile-filter-check');
                    const tick = sibling.querySelector('.mobile-filter-tick');
                    const label = sibling.querySelector('.mobile-filter-label');

                    if (check) {
                        check.classList.toggle('border-blue-700', isActive);
                        check.classList.toggle('bg-blue-700', isActive);
                        check.classList.toggle('border-gray-400', !isActive);
                    }
                    if (tick) tick.classList.toggle('hidden', !isActive);
                    if (label && prop === 'availability') {
                        label.classList.toggle('text-blue-700', isActive);
                        label.classList.toggle('font-medium', isActive);
                        label.classList.toggle('text-gray-700', !isActive);
                    }
                    if (prop === 'ratings') {
                        sibling.classList.toggle('opacity-100', isActive);
                        sibling.classList.toggle('opacity-70', !isActive);
                        if (label) {
                            label.classList.toggle('text-blue-700', isActive);
                            label.classList.toggle('font-bold', isActive);
                            label.classList.toggle('text-gray-700', !isActive);
                        }
                    }
                });
            });
        });

        // Mobile price slider — stage min/max locally, no request until Apply.
        const mobilePriceMin = document.getElementById('mobilePriceMin');
        const mobilePriceMax = document.getElementById('mobilePriceMax');
        const mobilePriceFill = document.getElementById('mobilePriceFill');
        const mobilePriceMinLabel = document.getElementById('mobilePriceMinLabel');
        const mobilePriceMaxLabel = document.getElementById('mobilePriceMaxLabel');

        function updateMobilePriceRange(source) {
            if (!mobilePriceMin || !mobilePriceMax) return;
            let minVal = parseInt(mobilePriceMin.value);
            let maxVal = parseInt(mobilePriceMax.value);
            if (minVal > maxVal - 20) {
                if (source === 'min') minVal = maxVal - 20;
                else maxVal = minVal + 20;
                mobilePriceMin.value = minVal;
                mobilePriceMax.value = maxVal;
            }
            const minPct = (minVal / 500) * 100;
            const maxPct = 100 - (maxVal / 500) * 100;
            if (mobilePriceFill) {
                mobilePriceFill.style.left = minPct + '%';
                mobilePriceFill.style.right = maxPct + '%';
            }
            if (mobilePriceMinLabel) mobilePriceMinLabel.textContent = currencySymbol + minVal;
            if (mobilePriceMaxLabel) mobilePriceMaxLabel.textContent = currencySymbol + maxVal;
        }

        mobilePriceMin?.addEventListener('input', () => updateMobilePriceRange('min'));
        mobilePriceMax?.addEventListener('input', () => updateMobilePriceRange('max'));
        mobilePriceMin?.addEventListener('change', () => $wire.set('min', mobilePriceMin.value, false));
        mobilePriceMax?.addEventListener('change', () => $wire.set('max', mobilePriceMax.value, false));

        // Apply button commits all staged changes to the server in one request.
        applyFiltersBtn?.addEventListener('click', () => {
            $wire.$refresh().then(() => closeFilters());
        });
    })();

    // ── Infinite scroll ────────────────────────────────────────────────────────
    (function () {
        let _observer = null;
        let _loading = false;

        function attachObserver() {
            if (_observer) {
                _observer.disconnect();
                _observer = null;
            }
            const sentinel = document.getElementById('loadMoreSentinel');
            if (!sentinel) return;

            _observer = new IntersectionObserver((entries) => {
                if (!entries[0].isIntersecting || _loading) return;
                _loading = true;
                $wire.loadMore().then(() => { _loading = false; });
            }, {
                rootMargin: '400px'
            });

            _observer.observe(sentinel);
        }

        attachObserver();

        Livewire.hook('morph.updated', () => attachObserver());
    })();

    window.closeFilters = function () {
        document.getElementById('filtersDrawer')?.classList.add('hidden');
    }
</script>
@endscript