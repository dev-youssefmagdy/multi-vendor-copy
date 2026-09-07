@php
    // Figma cycles three discount-chip palettes across the row.
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

@include('themes.souqify.pages.home-v4.sections.partials.deal_card_styles')

{{-- Figma: Frame 1984080238 (1440x472.07) - 24px 56px padding, 12px gap, no fill.
     Frame 1984079779 is a plain horizontal scroller: equal 226.67x362.07 cards,
     17px apart, no rotation and no size falloff. --}}
<style>
    .sqv4-newin {
        --sqv4-flash-pad: max(16px, calc((100% - 1440px) / 2 + 16px));
        /* New In accent: the theme green. */
        --sqv4-deal-accent: #5A9B00;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px var(--sqv4-flash-pad);
        gap: 12px;
    }
    .sqv4-newin__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv4-newin__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #1C1C1C;
        margin: 0;
    }
    .sqv4-newin__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #1C1C1C;
        white-space: nowrap;
        text-decoration: none;
    }

    .sqv4-newin__row {
        width: 100%;
        overflow: hidden;
    }
    .sqv4-newin__row .swiper-wrapper { align-items: stretch; }
    /* Deal Card 226.67 x 362.07, radius 6.90355. Swiper sets the slide width
       (5.5 per view), so the card just fills it and keeps the comp's ratio. */
    .sqv4-newin__row .sqv4-deal {
        position: relative;
        width: 100%;
        height: auto;
        aspect-ratio: 226.67 / 362.07;
        border-radius: 6.90355px;
        overflow: hidden;
    }
    .sqv4-newin__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-footer-text-muted);
    }

    @media (min-width: 1024px) {
        .sqv4-newin {
            /* 24px 56px padding, 12px gap */
            --sqv4-flash-pad: max(
                clamp(24px, 3.889vw, 56px),
                calc((100% - 1440px) / 2 + 56px)
            );
            gap: 12px;
        }
        .sqv4-newin__title {
            /* 40px / 50px line-height */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv4-newin__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
    }
</style>

<!-- ============ NEW IN ============ -->
<section class="sqv4-newin">
  <div class="sqv4-newin__head">
    <h2 class="sqv4-newin__title">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="sqv4-newin__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__newInCards->isNotEmpty())
    <div class="swiper card-swiper sqv4-newin__row">
      <div class="swiper-wrapper" id="newInWrapper">
        @foreach ($__newInCards as $p)
          <div class="swiper-slide" wire:key="new-in-v4-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => '', 'cartIcon' => 'icon-cart-green.svg'])
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv4-newin__empty">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>
