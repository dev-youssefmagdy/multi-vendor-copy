@php
    $sortOptions = [
        'best_selling' => __('Best Selling'),
        'discount' => __('Biggest discount'),
        'ascending' => __('Price: Low to High'),
        'descending' => __('Price: High to Low'),
        'newest' => __('New Arrivals'),
    ];
    $discountOptions = [0 => __('All'), 10 => '10%+', 20 => '20%+', 30 => '30%+', 50 => '50%+'];

    // Tag shown on the card: the product's own badge, otherwise its real discount.
    $tagFor = function ($product) {
        $badge = $product->cardBadgeText();
        if (filled($badge)) {
            return $badge;
        }
        $variant = $product->variants->where('active', true)->sortBy(
            fn($v) => $v->sellPriceForCountry((int) session('storefront_country_id') ?: null)
        )->first() ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $pct = $pricing['has_discount'] ? (int) round((float) $pricing['discount_percentage']) : 0;

        return $pct > 0 ? $pct . '% ' . __('OFF') : __('Flash Sale');
    };
    $hasFilters = $sort !== 'best_selling' || $currentCategoryId || $flashOnly || $minDiscount > 0;
@endphp

<main class="bg-zinc-100 pt-8 pb-16 w-full">
    @unless (in_array($storefrontThemeVariant?->key, ['v2', 'v3', 'v4', 'v5', 'v6'], true))
        @include('themes.souqify.partials.mobile-home-header', ['tight' => true])
    @endunless
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <nav class="text-xs text-neutral-500 flex items-center gap-2 mb-2">
                <a href="{{ route('tenant.home') }}" class="hover:text-[color:var(--sq-primary)]">{{ __('Home') }}</a>
                <span>/</span>
                <span class="text-slate-900">{{ __('Offers') }}</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ __('Offers') }}</h1>
            <p class="text-sm text-neutral-500 mt-1">{{ number_format($totalItems) }} {{ __('items') }}</p>
        </div>

        {{-- Filter bar --}}
        <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-5 mb-6">
            <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4">
                <span class="text-sm text-neutral-600 shrink-0">{{ __('Sort by:') }}</span>
                <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar pb-1">
                    @foreach ($sortOptions as $key => $label)
                        <button type="button" wire:click="$set('sort', '{{ $key }}')"
                            class="px-4 py-2 rounded-full border text-xs sm:text-sm transition shrink-0 {{ $sort === $key ? 'border-[color:var(--sq-primary)] bg-[color:var(--sq-primary)] text-white font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-[color:var(--sq-primary)] hover:text-[color:var(--sq-primary)]' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4 mt-4 pt-4 border-t border-neutral-100">
                <button type="button" wire:click="$toggle('flashOnly')"
                    class="px-4 py-2 rounded-full border text-xs sm:text-sm transition shrink-0 self-start {{ $flashOnly ? 'border-[color:var(--sq-primary)] bg-[color:var(--sq-primary-soft)] text-[color:var(--sq-primary)] font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-[color:var(--sq-primary)]' }}">
                    {{ __('Flash Sale') }}
                </button>
                <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar pb-1">
                    <span class="text-xs text-neutral-500 shrink-0">{{ __('Discount') }}</span>
                    @foreach ($discountOptions as $value => $label)
                        <button type="button" wire:click="$set('minDiscount', {{ $value }})"
                            class="px-4 py-1.5 rounded-full border text-xs transition shrink-0 {{ $minDiscount === $value ? 'border-[color:var(--sq-primary)] bg-[color:var(--sq-primary-soft)] text-[color:var(--sq-primary)] font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-[color:var(--sq-primary)]' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                @if ($hasFilters)
                    <button type="button" wire:click="clearFilters"
                        class="px-4 py-1.5 rounded-full border border-neutral-200 text-neutral-500 text-xs hover:border-red-400 hover:text-red-500 transition shrink-0 self-start md:ms-auto">
                        {{ __('Clear all') }}
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap no-scrollbar pb-1 mt-4 pt-4 border-t border-neutral-100">
                <button type="button" wire:click="setCategory('')"
                    class="px-4 py-1.5 rounded-full border text-xs transition shrink-0 {{ !$currentCategoryId ? 'border-[color:var(--sq-primary)] bg-[color:var(--sq-primary-soft)] text-[color:var(--sq-primary)] font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-[color:var(--sq-primary)]' }}">
                    {{ __('All categories') }}
                </button>
                @foreach ($categories as $category)
                    <button type="button" wire:click="setCategory('{{ $category->id }}')"
                        class="px-4 py-1.5 rounded-full border text-xs transition shrink-0 {{ $currentCategoryId === $category->id ? 'border-[color:var(--sq-primary)] bg-[color:var(--sq-primary-soft)] text-[color:var(--sq-primary)] font-semibold' : 'border-neutral-200 text-neutral-600 hover:border-[color:var(--sq-primary)]' }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                @foreach ($products as $product)
                    <div wire:key="offer-{{ $product->id }}">
                        @include('themes.souqify.pages._product-card', ['product' => $product, 'badge' => $tagFor($product)])
                    </div>
                @endforeach
            </div>

            @if ($hasMore)
                <div class="flex justify-center mt-8">
                    <button type="button" wire:click="loadMore" wire:loading.attr="disabled"
                        class="px-8 py-3 rounded-full border border-[color:var(--sq-primary)] text-[color:var(--sq-primary)] text-sm font-semibold hover:bg-[color:var(--sq-primary)] hover:text-white transition">
                        {{ __('Load more') }}
                    </button>
                </div>
            @endif

            <div wire:loading.flex class="w-full justify-center py-6">
                <div class="w-8 h-8 border-4 border-[color:var(--sq-primary)] border-t-transparent rounded-full animate-spin"></div>
            </div>
        @else
            <div class="rounded-3xl bg-white border border-neutral-200 py-16 px-6 text-center">
                <div class="text-5xl mb-3" aria-hidden="true">🏷️</div>
                <h2 class="text-xl font-semibold text-[#001537]">{{ __('No offers available right now.') }}</h2>
                @if ($hasFilters)
                    <button type="button" wire:click="clearFilters"
                        class="mt-5 inline-block rounded-full bg-[color:var(--sq-primary)] hover:bg-[color:var(--sq-primary-hover)] text-white text-sm font-semibold px-7 py-3 transition">
                        {{ __('Clear all') }}
                    </button>
                @else
                    <a href="{{ route('tenant.storefront.category') }}"
                        class="mt-5 inline-block rounded-full bg-[color:var(--sq-primary)] hover:bg-[color:var(--sq-primary-hover)] text-white text-sm font-semibold px-7 py-3 transition">
                        {{ __('Start Shopping') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</main>
