@push('body-attrs')data-page="categories"@endpush

<main class="flex-grow bg-white pt-8 w-full">
    <div class="max-w-[1280px] mx-auto px-5 sm:px-8 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center flex-wrap gap-4">
                <h1 class="text-[#333] font-medium text-[15px] mr-2">{{ __('New In') }}</h1>
                <div class="flex items-center gap-2 text-[13px] text-[#888]">
                    {{ __('Fresh arrivals across the latest categories') }}</div>
            </div>
            <form action="{{ route('tenant.storefront.new-in') }}" method="GET"
                class="flex items-center gap-3 flex-wrap">
                @if ($currentCategoryId)
                <input type="hidden" name="category_id" value="{{ $currentCategoryId }}">
                @endif
                <input type="text" name="keyword" value="{{ $search }}" placeholder="{{ __('Search items') }}"
                    class="px-4 py-1.5 rounded-full border border-[#e5e5e5] text-[#444] font-medium text-[13px] bg-white focus:outline-none min-w-[180px]">
                <button type="submit"
                    class="px-4 py-1.5 rounded-full border border-[#171717] text-[#171717] font-semibold text-[13px] bg-white hover:bg-gray-50 transition">
                    {{ __('Apply') }}
                </button>
            </form>
        </div>

        {{-- Category pills --}}
        <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap no-scrollbar pb-5">
            <a href="{{ route('tenant.storefront.new-in', array_filter(['keyword' => $search !== '' ? $search : null])) }}"
                class="cat-pill {{ !$currentCategoryId ? 'active' : '' }} px-5 py-1.5 rounded-full border text-[13px] bg-white hover:bg-gray-50 transition shrink-0">
                {{ __('All categories') }}
            </a>
            @foreach ($categories as $category)
            <a href="{{ route('tenant.storefront.new-in', array_filter(['keyword' => $search !== '' ? $search : null, 'category_id' => $category->id])) }}"
                class="cat-pill {{ $currentCategoryId === $category->id ? 'active' : '' }} px-5 py-1.5 rounded-full border text-[13px] bg-white hover:bg-gray-50 transition shrink-0">
                {{ $category->name }}
            </a>
            @endforeach
        </div>

        @if ($products->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">
            @foreach ($products as $product)
            @include('themes.elora.pages._showcase-card', ['product' => $product, 'badgeText' => __('New In')])
            @endforeach
        </div>
        <div class="mt-8">
            @include('themes.elora.pages._pagination', ['paginator' => $products])
        </div>
        @else
        <div class="bg-[#fafafa] border border-[#e5e5e5] rounded-[20px] py-16 text-center text-[#808080] text-[14px]">
            {{ __('No new products matched your current filters.') }}
        </div>
        @endif
    </div>
</main>
