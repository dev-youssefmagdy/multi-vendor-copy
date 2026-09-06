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
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            // Design-only fields with no backing data yet.
            'weight' => null,
            'badge' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
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
        padding: 24px 16px;
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
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
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

<!-- ============ NEW IN ============ -->
<section class="sqv3-newin">
  <div class="sqv3-newin__head">
    <h2 class="sqv3-newin__title">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="sqv3-newin__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__newInCards->isNotEmpty())
    <div class="swiper newin-swiper sqv3-newin__row">
      <div id="newInWrapper" class="swiper-wrapper">
        @foreach ($__newInCards as $p)
          <div class="swiper-slide sqv3-newin__slide" wire:key="new-in-v3-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v3.sections.partials.deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv3-newin__empty">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>
