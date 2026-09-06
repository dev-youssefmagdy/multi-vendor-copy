@php
    // Figma cycles three discount-chip palettes across the grid.
    $__chipPalettes = [
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ['bg' => '#FFB00A', 'color' => '#121212'],
    ];

    $__recommendedCards = collect($recommendedProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
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

{{-- Figma: Frame 1984080445 (1328 wide at left 56) - column, 24px gap.
     Frame 1984080444 stacks rows of Frame 1984079779: five 250.57x401.38 Deal
     Cards, 18.79px apart. 5 x 250.57 + 4 x 18.79 = 1328, so it is a plain
     5-column grid, not a scroller. --}}
<style>
    .sqv4-reco {
        --sqv4-flash-pad: max(16px, calc((100% - 1440px) / 2 + 16px));
        /* Recommended accent: the theme green. */
        --sqv4-deal-accent: #5A9B00;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px var(--sqv4-flash-pad);
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv4-reco__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv4-reco__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #121212;
        margin: 0;
    }
    .sqv4-reco__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984080444: rows 24px apart, cards 18.79px apart. */
    .sqv4-reco__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 12px;
        row-gap: 16px;
        width: 100%;
    }
    /* Deal Card 250.57 x 401.38, radius 7.63145. The grid sets the width; the
       card keeps the comp's ratio. */
    .sqv4-reco__grid .sqv4-deal {
        position: relative;
        width: 100%;
        height: auto;
        aspect-ratio: 250.57 / 401.38;
        border-radius: 7.63145px;
        overflow: hidden;
    }
    .sqv4-reco__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-footer-text-muted);
    }
    .sqv4-reco__more {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 8px 0;
    }
    .sqv4-reco__more-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: #8F8F8F;
    }

    @media (min-width: 640px) {
        .sqv4-reco__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (min-width: 1024px) {
        .sqv4-reco {
            /* 1328 content inside a 1440 canvas = 56px side padding. */
            --sqv4-flash-pad: max(
                clamp(24px, 3.889vw, 56px),
                calc((100% - 1440px) / 2 + 56px)
            );
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv4-reco__title {
            /* 40px / 50px line-height */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv4-reco__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv4-reco__grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            column-gap: clamp(12px, 1.305vw, 18.79px);
            row-gap: clamp(16px, 1.667vw, 24px);
        }
    }
</style>

<!-- ============ RECOMMENDED FOR YOU ============ -->
<section class="sqv4-reco">
  <div class="sqv4-reco__head">
    <h2 class="sqv4-reco__title">{{ __('Recommended For You') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv4-reco__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__recommendedCards->isNotEmpty())
    <div id="recommendedGrid" class="sqv4-reco__grid">
      @foreach ($__recommendedCards as $p)
        <div wire:key="recommended-v4-{{ $p['id'] }}">
          @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => '', 'cartIcon' => 'icon-cart-green.svg'])
        </div>
      @endforeach
    </div>
  @else
    <p class="sqv4-reco__empty">{{ __('No recommended products yet.') }}</p>
  @endif

  @if ($hasMoreRecommended ?? false)
    <div wire:intersect="loadMoreRecommended" class="sqv4-reco__more">
      <div wire:loading wire:target="loadMoreRecommended" class="sqv4-reco__more-label">
        <svg class="animate-spin size-[18px]" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        {{ __('Loading more...') }}
      </div>
    </div>
  @endif
</section>
