@php
    $__trendingCards = collect($trendingNowProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the row.
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
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
        ];
    });
@endphp

{{-- Figma: Frame 1984080216 (1440 x 499.33) - white, 24px 56px padding, 24px
     gap: a 50px header then the 377.33px card row (17.71px between cards).
     The row measures 1505.71 against the 1328 the padding leaves, so it runs as
     a carousel at 5.5 cards per view.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-trend {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-page-bg, #FFFFFF);
    }
    /* Frame 1984080017 */
    .sqv2-trend__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv2-trend__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #000000;
        margin: 0;
    }
    .sqv2-trend__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-brand-purple, #8B03BD);
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984079779: the card row. Clipped locally because the shared
       .card-swiper rule sets overflow: visible. */
    .sqv2-trend__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv2-trend__row .swiper-wrapper { align-items: stretch; }
    /* Widths are set in CSS, not left to Swiper: the v2 bundle may mount this
       wrapper with slidesPerView:"auto" before the mount below runs, and an
       auto slide with no width blows the card up to the full row. */
    .sqv2-trend__slide {
        height: auto;
        /* Phones show two whole cards and a sliver of the third. */
        width: calc((100% - 2 * 12px) / 2.25);
    }

    .sqv2-trend__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-trend__slide { width: calc((100% - 2 * 16px) / 2.5); }
    }

    @media (min-width: 1024px) {
        .sqv2-trend {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        /* 5.5 cards across the 1328px row at a 17.71px gap. */
        .sqv2-trend__slide { width: calc((100% - 5 * 17.71px) / 5.5); }
        .sqv2-trend__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv2-trend__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ TRENDING NOW ============ -->
<section class="sqv2-trend">
  <div class="sqv2-trend__head">
    <h2 class="sqv2-trend__title">{{ __('Trending Now') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv2-trend__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__trendingCards->isNotEmpty())
    <div class="swiper trending-swiper sqv2-trend__row">
      <div class="swiper-wrapper" id="trendingWrapper">
        @foreach ($__trendingCards as $p)
          <div class="swiper-slide sqv2-trend__slide" wire:key="trending-v2-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v2.sections.partials.purple_deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv2-trend__empty">{{ __('No trending products yet.') }}</p>
  @endif
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountTrendingV2() {
    var el = document.querySelector('.trending-swiper');
    if (!el || typeof Swiper === 'undefined') return;
    // The Vite bundle mounts this wrapper with its own generic config; replace
    // it so the Figma per-view counts win regardless of which ran first.
    if (el.swiper) {
      if (el.swiper.__sqv2Trending) return;
      el.swiper.destroy(true, true);
    }

    var sw = new Swiper(el, {
      // Two whole cards plus a sliver of the third.
      slidesPerView: 2.25,
      spaceBetween: 12,
      breakpoints: {
        640: { slidesPerView: 2.5, spaceBetween: 16 },
        // 5.5 per view: the sixth card is half-cut, so the row reads as
        // swipeable. 17.71px is the Figma gap between cards.
        1024: { slidesPerView: 5.5, spaceBetween: 17.71 },
      },
    });
    sw.__sqv2Trending = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountTrendingV2);
  } else {
    mountTrendingV2();
  }
  window.addEventListener('load', mountTrendingV2);
})();
</script>
