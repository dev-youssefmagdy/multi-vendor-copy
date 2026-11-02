@php
    // Figma cycles three discount-chip palettes across the cards.
    $__chipPalettes = [
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ['bg' => '#FFB00A', 'color' => '#121212'],
    ];

    $__newInCards = collect($newInProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        $chip = $__chipPalettes[$index % count($__chipPalettes)];

        return [
            'id' => $product->id,
            'outOfStock' => $product->stockStatus() === 'out_of_stock',
            'slug' => $product->slug,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'ratingValue' => $rating,
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'rawPrice' => round((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            // Design-only fields with no backing data yet.
            'weight' => null,
            'desc' => \Illuminate\Support\Str::limit($product->translationValue('short_description') ?? '', 30),
            'sold' => (function () use ($product) { $sc = (int) ($product->centralProduct?->sold_count ?? 0); $st = $sc + (int) ($product->stock ?? 0); return $st > 0 && $sc > 0 ? (int) round($sc / $st * 100) . '% ' . __('Sold') : null; })(),
            'badge' => '🔥 ' . __('Trending Now'),
            'labels' => $product->cardLabels(),
        ];
    });
@endphp

{{-- Figma: Frame 1984080239 (1440x523.25) - 24px 56px padding, 24px gap.
     50px header + 401.25px card row. Card = the same Deal Card as Flash Sale and
     Best Seller, at 250.48x401.25 with an 18.79px gap (Frame 1984079779).
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv3-newin {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding-block: 24px;
        padding-inline-start: 16px;
        padding-inline-end: 0;
        gap: 16px;
        background: var(--color-page-bg);
    }
    .sqv3-newin__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv3-newin__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #1C1C1C;
        margin: 0;
    }
    .sqv3-newin__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #1C1C1C;
        white-space: nowrap;
    }

    /* ---------- Carousel ---------- */
    .sqv3-newin__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv3-newin__row .swiper-wrapper {
        align-items: stretch;
    }
    /* Deal Card slide: 250.48 x 401.25. Width comes from Swiper's slidesPerView. */
    .sqv3-newin__slide {
        height: auto;
    }
    /* Phones: New In uses the same card as Trending Now (full row, square fitted photo);
       from 640px the original deal card is kept. */
    .sqv3-newin__wide { display: none; }
    @media (max-width: 639px) {
        .sqv3-newin__row { padding-inline-end: 16px; box-sizing: border-box; }
        /* Taller New In cards: bigger square photo (52% of the card) and a 214px minimum height. */
        .sqv3-newin .sqv3-trend-card { min-height: 214px; align-items: center; }
        .sqv3-newin .sqv3-trend-card__media { width: 52%; align-self: center; }
    }
    @media (min-width: 640px) {
        .sqv3-newin__phone { display: none; }
        .sqv3-newin__wide { display: block; }
    }
    .sqv3-newin__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted);
    }

    @media (min-width: 1024px) {
        .sqv3-newin {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps slidesPerView from inflating the cards on wider displays;
               max() still leaves the Figma 56px gutter at exactly 1440. */
            padding-block: 24px;
            padding-inline-start: max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            padding-inline-end: 0;
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-newin__title {
            /* 40px / 50px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv3-newin__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
    }
</style>

@include('themes.souqify.pages.home-v3.sections.partials.deal_card_styles')
@include('themes.souqify.pages.home-v3.sections.partials.trending_card_styles')

<!-- ============ NEW IN ============ -->
<section class="sqv3-newin" wire:ignore>
  <div class="sqv3-newin__head">
    <h2 class="sqv3-newin__title">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="sqv3-newin__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__newInCards->isNotEmpty())
    <div class="swiper newin-swiper sqv3-newin__row">
      <div id="newInWrapper" class="swiper-wrapper">
        @foreach ($__newInCards as $p)
          <div class="swiper-slide sqv3-newin__slide" wire:key="new-in-v3-{{ $p['id'] }}">
            <div class="sqv3-newin__phone">@include('themes.souqify.pages.home-v3.sections.partials.trending_card', ['p' => $p])</div>
            <div class="sqv3-newin__wide">@include('themes.souqify.pages.home-v3.sections.partials.deal_card', ['p' => $p])</div>
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv3-newin__empty">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>

{{-- Phones show one card per row; carousels-v3.js mounts this row at 2.15 slides in view. --}}
<script>
(function () {
    function fit() {
        var el = document.querySelector('.newin-swiper.sqv3-newin__row');
        if (!el || !el.swiper) return false;
        if (window.innerWidth < 640) {
            el.swiper.params.slidesPerView = 1;
            el.swiper.update();
        }
        return true;
    }
    var tries = 0, t = setInterval(function () { if (fit() || ++tries > 40) clearInterval(t); }, 150);
})();
</script>
