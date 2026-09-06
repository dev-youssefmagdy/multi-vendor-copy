@php
    $__recommendedCards = collect($recommendedProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the cards.
        $chip = [
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
            ['bg' => '#FFB00A', 'color' => '#121212'],
        ][$index % 3];

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'tag' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
        ];
    });
@endphp
<!-- ============ RECOMMENDED FOR YOU ============ -->
<style>
    .sqv5-reco {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv5-reco__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv5-reco__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 22px;
        line-height: 1.25;              /* 50 / 40 */
        color: #121212;
        margin: 0;
    }
    .sqv5-reco__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
    }
    /* Frame 1984080444: rows of five 250.57 cards, 18.79px apart, 24px between
       rows. A static grid in the comp - no carousel. */
    .sqv5-reco__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 12px;
        width: 100%;
    }
    @media (min-width: 640px) {
        .sqv5-reco__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .sqv5-reco {
            /* The comp's content column is 1328px inside a 1440 canvas; capping it
               keeps the cards at their design size on wider displays. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv5-reco__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv5-reco__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        .sqv5-reco__grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: clamp(16px, 1.667vw, 24px) clamp(12px, 1.3049vw, 18.79px);
        }
    }
</style>

<section class="sqv5-reco">
    <div class="sqv5-reco__head">
        <h2 class="sqv5-reco__title">{{ __('Recommended For You') }}</h2>
        <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv5-reco__seeall">{{ __('see all') }}</a>
    </div>
    <div id="recommendedWrapper" class="sqv5-reco__grid">
        @forelse ($__recommendedCards as $p)
            <div wire:key="recommended-v5-{{ $p['id'] }}">
                @include('themes.souqify.pages.home-v5.sections.partials.deal_card', ['p' => $p])
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
