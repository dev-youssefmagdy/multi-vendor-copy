@php
    $__trendingCards = collect($trendingNowProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        // Figma cycles three discount-chip palettes across the cards.
        $chip = [
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
            ['bg' => '#FFB00A', 'color' => '#121212'],
        ][$index % 3];
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

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
<!-- ============ TRENDING NOW ============ -->
<style>
    .sqv5-trend {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv5-trend__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv5-trend__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 22px;
        line-height: 1.25;              /* 50 / 40 */
        color: #000000;
        margin: 0;
    }
    .sqv5-trend__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-primary);
        white-space: nowrap;
    }
    /* The shared .card-swiper rule sets overflow: visible, so the slides ran
       past the section and gave the page a horizontal scroll. Clip this one. */
    .sqv5-trend .swiper {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    @media (min-width: 1024px) {
        .sqv5-trend {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps slidesPerView from inflating the cards on wider displays;
               max() still leaves the Figma 56px gutter at exactly 1440. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv5-trend__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv5-trend__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<section class="sqv5-trend">
    <div class="sqv5-trend__head">
        <h2 class="sqv5-trend__title">{{ __('Trending Now') }}</h2>
        <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv5-trend__seeall">{{ __('see all') }}</a>
    </div>
    @if ($__trendingCards->isNotEmpty())
        <div class="relative w-full min-w-0">
            <div class="swiper card-swiper">
                <div class="swiper-wrapper" id="trendingWrapper">
                    @foreach ($__trendingCards as $p)
                        <div class="swiper-slide h-auto" wire:key="trending-v5-{{ $p['id'] }}">
                            @include('themes.souqify.pages.home-v5.sections.partials.deal_card', ['p' => $p])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <p class="text-sm py-6" style="color:var(--color-text-muted)">{{ __('No trending products yet.') }}</p>
    @endif
</section>
