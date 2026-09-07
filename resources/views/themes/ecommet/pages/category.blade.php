<main class="grow bg-white pt-6 pb-14 w-full">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="flex items-center gap-2 text-[12px] text-[#808080] mb-6 flex-wrap">
            <a href="{{ route('tenant.home') }}" class="hover:text-[#242424] transition">{{ __('Home') }}</a>
            <span>/</span>
            @if ($slug)
                <a href="{{ route('tenant.storefront.category') }}"
                    class="hover:text-[#242424] transition">{{ __('Categories') }}</a>
                <span>/</span>
                @if ($parentCategory)
                    <a href="{{ route('tenant.storefront.category', $parentCategory->slug) }}"
                        class="hover:text-[#242424] transition">{{ $parentCategory->name }}</a>
                    <span>/</span>
                @endif
                <span class="text-[#242424] font-medium">{{ $category->name }}</span>
            @else
                <span class="text-[#242424] font-medium">{{ __('All Products') }}</span>
            @endif
        </div>

        @if ($relatedCategories->isNotEmpty())
            <div
                class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-x-3 gap-y-6 mb-8 mt-2 max-w-[1180px] mx-auto justify-items-center">

                {{-- Back: to parent category or to home (all categories) --}}
                @php
                    $backUrl = $parentCategory
                        ? route('tenant.storefront.category', $parentCategory->slug)
                        : route('tenant.storefront.category');
                    $backLabel = $parentCategory ? $parentCategory->name : __('All categories');
                @endphp
                <a href="{{ $backUrl }}"
                    class="flex flex-col items-center gap-2 w-full group hover:-translate-y-1 transition duration-300">
                    <div
                        class="w-[68px] h-[68px] rounded-full bg-[#f3f5f7] border border-transparent flex items-center justify-center overflow-hidden transition shadow-sm group-hover:border-[#242424]">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-[#242424] transition" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-center leading-[1.1] text-[#444] line-clamp-2">
                        {{ $backLabel }}
                    </span>
                </a>

                @foreach ($relatedCategories as $relatedCategory)
                    @php
                        $isActiveCategory = $category && $relatedCategory->id === $category->id;
                    @endphp
                    <a href="{{ route('tenant.storefront.category', $relatedCategory->slug) }}"
                        class="flex flex-col items-center gap-2 w-full group hover:-translate-y-1 transition duration-300">
                        <div
                            class="w-[68px] h-[68px] rounded-full flex items-center justify-center overflow-hidden border transition shadow-sm {{ $isActiveCategory ? 'bg-[#e5f0ff] border-[#2272e2]' : 'bg-[#f5f5f5] border-transparent group-hover:border-[#242424]' }}">
                            @if ($relatedCategory->thumb_url)
                                <img loading="lazy" src="{{ $relatedCategory->thumb_url }}" alt="{{ $relatedCategory->name }}"
                                    class="object-contain w-full h-full rounded-full">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-[#888]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 11H5m14 0a2 2 0 100-4H5a2 2 0 100 4m14 0v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6" />
                                </svg>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold text-center leading-[1.1] text-[#444] line-clamp-2">
                            {{ $relatedCategory->name }}
                        </span>
                    </a>
                @endforeach
            </div>

            <hr class="border-[#ebebeb] mb-6 mt-6">
        @endif

        <div class="mb-6 rounded-[24px] border border-[#ececec] bg-[#fafafa] p-4 sm:p-5">
            <form
                action="{{ $category ? route('tenant.storefront.category', $category->slug) : route('tenant.storefront.category') }}"
                method="GET" class="flex flex-col gap-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-[#222] text-[18px] font-bold">
                            {{ $category ? $category->name : __('All Products') }}</h1>
                        <p class="text-[#808080] text-[12px] mt-1">
                            @if ($products->total() > 0)
                                {{ __('Showing :from-:to of :total products', ['from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()]) }}
                            @else
                                {{ __('No products found for the current filters') }}
                            @endif
                        </p>
                    </div>

                    <div class="w-full lg:max-w-[320px]">
                        <label for="keyword" class="sr-only">{{ __('Search products') }}</label>
                        <div class="relative group">
                            <input type="text" name="keyword" id="keyword" value="{{ $keyword }}"
                                placeholder="{{ __('Search products, SKU or title') }}"
                                class="w-full ps-10 pe-4 py-3 rounded-full border border-[#e5e5e5] text-[13px] font-medium text-[#242424] bg-white focus:outline-none focus:border-[#ff4e00] transition-all placeholder-[#999]">
                            <svg class="w-4 h-4 text-[#aaa] absolute left-3.5 top-1/2 -translate-y-1/2 group-focus-within:text-[#ff4e00] transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3">
                    <label class="block">
                        <span class="sr-only">{{ __('Sort') }}</span>
                        <select name="sort"
                            class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                            <option value="latest" @selected($sort === 'latest')>{{ __('Newest') }}</option>
                            <option value="old" @selected($sort === 'old')>{{ __('Oldest') }}</option>
                            <option value="ascending" @selected($sort === 'ascending')>{{ __('Price: low to high') }}
                            </option>
                            <option value="descending" @selected($sort === 'descending')>{{ __('Price: high to low') }}
                            </option>
                            <option value="rating" @selected($sort === 'rating')>{{ __('Top rated') }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="sr-only">{{ __('Availability') }}</span>
                        <select name="availability"
                            class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                            <option value="">{{ __('All availability') }}</option>
                            <option value="in_stock" @selected($availability === 'in_stock')>{{ __('In Stock') }}</option>
                            <option value="out_of_stock" @selected($availability === 'out_of_stock')>
                                {{ __('Out of Stock') }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="sr-only">{{ __('Flag') }}</span>
                        <select name="product_flag"
                            class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                            <option value="">{{ __('All products') }}</option>
                            <option value="featured" @selected($productFlag === 'featured')>{{ __('Featured only') }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="sr-only">{{ __('Sale') }}</span>
                        <select name="on_sale"
                            class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                            <option value="">{{ __('All offers') }}</option>
                            <option value="on_sale" @selected($sale === 'on_sale')>{{ __('On sale') }}</option>
                            <option value="flash_sale" @selected($sale === 'flash_sale')>{{ __('Flash Sale') }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="sr-only">{{ __('Rating') }}</span>
                        <select name="ratings"
                            class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                            <option value="">{{ __('Any rating') }}</option>
                            <option value="4" @selected($ratings === '4')>{{ __('4 stars and up') }}</option>
                            <option value="3" @selected($ratings === '3')>{{ __('3 stars and up') }}</option>
                            <option value="2" @selected($ratings === '2')>{{ __('2 stars and up') }}</option>
                            <option value="1" @selected($ratings === '1')>{{ __('1 star and up') }}</option>
                        </select>
                    </label>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="sr-only">{{ __('Minimum price') }}</span>
                            <input type="number" name="min" min="0" step="0.01" value="{{ $min }}"
                                placeholder="{{ __('Min') }}"
                                class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ __('Maximum price') }}</span>
                            <input type="number" name="max" min="0" step="0.01" value="{{ $max }}"
                                placeholder="{{ __('Max') }}"
                                class="w-full h-11 px-4 rounded-full border border-[#d8d8d8] bg-white text-[13px] font-medium text-[#242424] focus:outline-none focus:border-[#242424]">
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <button type="submit"
                        class="h-10 px-5 rounded-full bg-[#242424] text-white text-[13px] font-semibold hover:bg-[#ff4e00] transition-colors whitespace-nowrap">
                        {{ __('Apply filters') }}
                    </button>
                    <a href="{{ $category ? route('tenant.storefront.category', $category->slug) : route('tenant.storefront.category') }}"
                        class="h-10 px-5 rounded-full border border-[#d8d8d8] bg-white text-[#242424] text-[13px] font-semibold hover:border-[#242424] transition-colors inline-flex items-center">
                        {{ __('Reset') }}
                    </a>
                </div>
            </form>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-[28px] border border-dashed border-[#d9d9d9] bg-[#fafafa] px-6 py-14 text-center">
                <h2 class="text-[20px] font-semibold text-[#222] mb-2">{{ __('No products matched your filters.') }}</h2>
                <p class="text-[13px] text-[#777] max-w-[460px] mx-auto mb-5">
                    {{ __('Try widening the price range, clearing a filter, or browsing another category.') }}
                </p>
                <a href="{{ $category ? route('tenant.storefront.category', $category->slug) : route('tenant.storefront.category') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-full bg-[#242424] text-white text-[13px] font-semibold hover:bg-[#ff4e00] transition-colors">
                    {{ __('Clear filters') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">
                @foreach ($products as $product)
                    @include('themes.ecommet.pages._product-card', ['product' => $product])
                @endforeach
            </div>

            @include('themes.ecommet.pages._pagination', ['paginator' => $products])
        @endif
    </div>
</main>
