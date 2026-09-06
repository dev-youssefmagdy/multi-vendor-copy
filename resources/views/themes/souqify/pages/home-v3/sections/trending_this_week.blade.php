@php
    $__trendingCards = collect($trendingNowProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'desc' => \Illuminate\Support\Str::limit($product->translationValue('short_description') ?? '', 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            // Design-only fields with no backing data yet; same placeholder convention
            // the v4 trending card already uses.
            'weight' => null,
            'sold' => '70% ' . __('Sold'),
            'delivery' => __('Delivered by 24 March'),
            'stock' => __('Only 5 left'),
        ];
    });

    // The comp stacks two cards per column (Frame 1984080249: 496.21x330.35,
    // 17.39px gap), and the columns are what the carousel slides through.
    $__trendingColumns = $__trendingCards->chunk(2)->values();
@endphp

{{-- Figma: Frame 1984080216 (1440x452.35) - 24px 56px padding, 24px gap,
     50px header + 330.35px card row. Card = "Mobile Card" 496.21x156.48.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv3-trend {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-page-bg);
    }
    .sqv3-trend__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv3-trend__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #000000;
        margin: 0;
    }
    .sqv3-trend__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-souqify-teal);
        white-space: nowrap;
    }
    .sqv3-trend__row {
        width: 100%;
        overflow: hidden;
    }
    .sqv3-trend__row .swiper-wrapper {
        align-items: stretch;
    }
    /* One slide = one column of two stacked cards (17.39px gap in the comp). */
    .sqv3-trend__col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        height: auto;
    }
    .sqv3-trend__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted);
    }

</style>

@include('themes.souqify.pages.home-v3.sections.partials.trending_card_styles')

<style>
    @media (min-width: 1024px) {

        .sqv3-trend {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps slidesPerView from inflating the cards on wider displays;
               max() still leaves the Figma 56px gutter at exactly 1440. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-trend__title {
            /* 40px / 50px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv3-trend__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv3-trend__col { gap: clamp(12px, 1.2076vw, 17.39px); }
    }
</style>

<!-- ============ TRENDING NOW ============ -->
<section class="sqv3-trend">
  <div class="sqv3-trend__head">
    <h2 class="sqv3-trend__title">{{ __('Trending Now') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv3-trend__seeall">{{ __('see all') }}</a>
  </div>
  @if ($__trendingColumns->isNotEmpty())
    <div class="swiper trending-swiper sqv3-trend__row">
      <div id="trendingWrapper" class="swiper-wrapper">
        @foreach ($__trendingColumns as $__colIndex => $__column)
          <div class="swiper-slide sqv3-trend__col" wire:key="trending-v3-col-{{ $__colIndex }}">
            @foreach ($__column as $p)
              @include('themes.souqify.pages.home-v3.sections.partials.trending_card', ['p' => $p])
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv3-trend__empty">{{ __('No trending products yet.') }}</p>
  @endif
</section>
