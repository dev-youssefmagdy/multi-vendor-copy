@push('body-attrs')data-page="categories"@endpush

@php
    $pageTitle    = $isImageSearch ?? false ? __('Products similar to your image') : ($isSearchRoute ? __('Search Results') : __('Best-Selling Items'));
    $searchAction = $isSearchRoute ? route('tenant.storefront.search') : route('tenant.storefront.best-selling');
    $searchParam  = $isSearchRoute ? 'q' : 'keyword';
    $dayOptions   = [30, 14, 7];
@endphp

<main class="flex-grow bg-white pt-8 w-full">
    <div class="max-w-[1280px] mx-auto px-5 sm:px-8 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center flex-wrap gap-4">
                <h1 class="text-[#333] font-medium text-[15px] mr-2">{{ $pageTitle }}</h1>

                @if (!$isSearchRoute)
                    <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap no-scrollbar pb-1 -mb-1 max-w-full">
                        @foreach ($dayOptions as $option)
                            <a href="{{ route('tenant.storefront.best-selling', array_filter([
                                'days'        => $option,
                                'keyword'     => $search !== '' ? $search : null,
                                'category_id' => $currentCategoryId,
                            ])) }}"
                                class="px-5 py-1.5 rounded-full border {{ $days === $option ? 'border-[#171717] text-[#171717] font-semibold' : 'border-[#e5e5e5] text-[#888] font-medium' }} text-[13px] bg-white hover:bg-gray-50 transition shrink-0">
                                {{ __('Within last :days days', ['days' => $option]) }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <form action="{{ $searchAction }}" method="GET" class="flex items-center gap-3 flex-wrap">
                @if (!$isSearchRoute)
                    <input type="hidden" name="days" value="{{ $days }}">
                @endif
                @if ($currentCategoryId)
                    <input type="hidden" name="category_id" value="{{ $currentCategoryId }}">
                @endif
                <input type="text" name="{{ $searchParam }}" value="{{ $search }}" placeholder="{{ __('Search items') }}"
                    class="px-4 py-1.5 rounded-full border border-[#e5e5e5] text-[#444] font-medium text-[13px] bg-white focus:outline-none min-w-[180px]">
                <button type="submit"
                    class="px-4 py-1.5 rounded-full border border-[#171717] text-[#171717] font-semibold text-[13px] bg-white hover:bg-gray-50 transition">
                    {{ __('Search') }}
                </button>
            </form>
        </div>

        {{-- Category pills --}}
        <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap no-scrollbar pb-5">
            <a href="{{ $isSearchRoute
                ? route('tenant.storefront.search', array_filter(['q' => $search !== '' ? $search : null]))
                : route('tenant.storefront.best-selling', array_filter(['days' => $days, 'keyword' => $search !== '' ? $search : null])) }}"
                class="cat-pill {{ !$currentCategoryId ? 'active' : '' }} px-5 py-1.5 rounded-full border text-[13px] bg-white hover:bg-gray-50 transition shrink-0">
                {{ __('All categories') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ $isSearchRoute
                    ? route('tenant.storefront.search', array_filter(['q' => $search !== '' ? $search : null, 'category_id' => $category->id]))
                    : route('tenant.storefront.best-selling', array_filter(['days' => $days, 'keyword' => $search !== '' ? $search : null, 'category_id' => $category->id])) }}"
                    class="cat-pill {{ $currentCategoryId === $category->id ? 'active' : '' }} px-5 py-1.5 rounded-full border text-[13px] bg-white hover:bg-gray-50 transition shrink-0">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        @if ($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">
                @foreach ($products as $product)
                    @include('themes.elora.pages._showcase-card', ['product' => $product, 'badgeText' => __('Best-Selling')])
                @endforeach
            </div>
            <div class="mt-8">
                @include('themes.elora.pages._pagination', ['paginator' => $products])
            </div>
        @else
            <div class="col-span-full bg-[#fafafa] border border-[#e5e5e5] rounded-[20px] py-16 text-center text-[#808080] text-[14px]">
                {{ __('No products matched your current filters.') }}
            </div>
        @endif
    </div>
</main>
