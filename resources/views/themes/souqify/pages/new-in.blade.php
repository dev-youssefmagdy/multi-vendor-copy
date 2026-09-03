<main class="bg-zinc-100 pt-8 pb-16 w-full">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <nav class="text-xs text-neutral-500 flex items-center gap-2 mb-2">
                <a href="{{ route('tenant.home') }}" class="hover:text-blue-700">{{ __('Home') }}</a>
                <span>/</span>
                <span class="text-slate-900">{{ __('New In') }}</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ __('New In') }}</h1>
            <p class="text-sm text-neutral-500 mt-1">{{ __('Fresh arrivals across the latest categories') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-5 mb-6">
            <form action="{{ route('tenant.storefront.new-in') }}" method="GET"
                class="flex items-center gap-2 flex-wrap">
                @if ($currentCategoryId)
                    <input type="hidden" name="category_id" value="{{ $currentCategoryId }}">
                @endif
                <input type="text" name="keyword" value="{{ $search }}" placeholder="{{ __('Search items') }}"
                    class="px-4 py-2 rounded-full border border-neutral-200 text-sm bg-white focus:outline-none focus:border-blue-700 min-w-[180px] flex-1 max-w-md">
                <button type="submit"
                    class="px-5 py-2 rounded-full bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold transition">
                    {{ __('Apply') }}
                </button>
            </form>

            <div
                class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar pb-1 mt-4 pt-4 border-t border-neutral-100">
                <a href="{{ route('tenant.storefront.new-in', array_filter(['keyword' => $search !== '' ? $search : null])) }}"
                    class="px-4 py-1.5 rounded-full border text-xs transition shrink-0
                        {{ !$currentCategoryId ? 'border-blue-700 bg-blue-50 text-blue-700 font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700' }}">
                    {{ __('All categories') }}
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('tenant.storefront.new-in', array_filter(['keyword' => $search !== '' ? $search : null, 'category_id' => $category->id])) }}"
                        class="px-4 py-1.5 rounded-full border text-xs transition shrink-0
                                        {{ $currentCategoryId === $category->id ? 'border-blue-700 bg-blue-50 text-blue-700 font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-blue-700' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        @if ($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                @foreach ($products as $product)
                    @include('themes.souqify.pages._product-card', ['product' => $product, 'badge' => 'New In'])
                @endforeach
            </div>
            <div class="mt-10">
                @include('themes.souqify.pages._pagination', ['paginator' => $products])
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-2xl py-16 text-center text-neutral-500">
                <svg class="w-14 h-14 mx-auto mb-4 text-neutral-300" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                </svg>
                <p class="text-base">{{ __('No new products matched your current filters.') }}</p>
            </div>
        @endif
    </div>
</main>