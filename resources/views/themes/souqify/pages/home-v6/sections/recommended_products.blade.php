@php
    $__recommendedCards = collect($recommendedProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : __('New'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
        ];
    });
@endphp
<!-- ============ RECOMMENDED FOR YOU ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]">
    <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">{{ __('Recommended For You') }}</h2>
        <a href="{{ route('tenant.storefront.best-selling') }}" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-brand-pink)">{{ __('see all') }}</a>
    </div>
    <div id="recommendedGrid" class="grid grid-cols-2 lg:grid-cols-5 gap-[12px] lg:gap-[20px]">
        @forelse ($__recommendedCards as $p)
            <div class="h-[300px] lg:h-[340px]" wire:key="recommended-v6-{{ $p['id'] }}">
                @include('themes.souqify.pages.home-v6.sections.partials.deal_card', ['p' => $p])
            </div>
        @empty
            <p class="text-sm py-6 col-span-full" style="color:var(--color-text-muted)">{{ __('No recommended products yet.') }}</p>
        @endforelse
    </div>
    @if ($hasMoreRecommended ?? false)
        <div wire:intersect="loadMoreRecommended" class="flex items-center justify-center py-[8px]">
            <div wire:loading wire:target="loadMoreRecommended" class="flex items-center gap-2 text-[14px]" style="color:var(--color-text-subtitle)">
                <svg class="animate-spin size-[18px]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                {{ __('Loading more...') }}
            </div>
        </div>
    @endif
</section>
