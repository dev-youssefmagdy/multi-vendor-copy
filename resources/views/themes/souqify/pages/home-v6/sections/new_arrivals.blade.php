@php
    $__newInCards = collect($newInProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
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

        $__url = route('tenant.storefront.product', $product->slug);
        $__img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
        $__name = \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30);
        $__fav = json_encode([
            'slug' => $product->slug,
            'name' => $__name,
            'price' => round((float) $pricing['current_price'] * $rate, 2),
            'old_price' => $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
            'rating' => $rating,
            'image' => $__img,
            'url' => $__url,
            'added' => time(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => $__url,
            'image' => $__img,
            'tag' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
            'name' => $__name,
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'outOfStock' => $product->stockStatus() === 'out_of_stock',
            'fav' => $__fav,
        ];
    });
@endphp

{{-- Figma: Frame 1984080239 (1440 x 547.02), updated to a plain gray panel -
     padding-top only is lg:48px/16px, horizontal padding is start-only so the
     carousel row runs flush to the trailing edge (RTL/LTR via logical props).
     24px gap: a 50px header then the 425.02px card row (19.96px between
     cards). Values are clamp(min, <value>/1440*100vw, max) off the 1440
     canvas. --}}
<style>
    .sqv6-newin {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 16px;
        padding-bottom: 24px;
        padding-inline-start: 16px;
        padding-inline-end: 0;
        gap: 16px;
        position: relative;
        background: var(--color-page-bg, #F4F4F4);
        overflow: hidden;
    }
    /* Frame 1984080018 */
    .sqv6-newin__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding-inline-end: 16px;
    }
    .sqv6-newin__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: var(--color-text-primary, #121212);
        margin: 0;
    }
    .sqv6-newin__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-text-primary, #121212);
        white-space: nowrap;
        text-decoration: none;
    }

    .sqv6-newin__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv6-newin__row .swiper-wrapper { align-items: stretch; }
    /* Deal Card: 266.09 x 425.02, radius 8.10431. Width comes from
       slidesPerView, so only the corner is set here. */
    .sqv6-newin__slide { height: auto; }
    .sqv6-newin__slide .sqv6-deal {
        border-radius: 8.10431px;
        box-shadow: none;
    }
    .sqv6-newin__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-gray, #8F8F8F);
    }

    @media (min-width: 1024px) {
        .sqv6-newin {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding-top: 48px;
            padding-bottom: 24px;
            padding-inline-start: max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            padding-inline-end: 0;
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-newin__head {
            padding-inline-end: max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
        }
        .sqv6-newin__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv6-newin__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ NEW IN ============ -->
<section class="sqv6-newin" wire:ignore>
  <div class="sqv6-newin__head">
    <h2 class="sqv6-newin__title">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="sqv6-newin__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__newInCards->isNotEmpty())
    <div class="swiper newin-swiper sqv6-newin__row">
      <div class="swiper-wrapper" id="newInWrapper">
        @foreach ($__newInCards as $p)
          <div class="swiper-slide sqv6-newin__slide" wire:key="new-in-v6-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v6.sections.partials.pink_deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv6-newin__empty">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>
