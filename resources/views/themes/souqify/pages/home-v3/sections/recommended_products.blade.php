@php
    // Figma cycles three discount-chip palettes across the cards.
    $__chipPalettes = [
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ['bg' => '#FFB00A', 'color' => '#121212'],
    ];

    // Frame 1984080444 stacks four rows of five cards, so cap at 20.
    $__recommendedCards = collect($recommendedProducts ?? [])->take(20)->values()
        ->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
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

{{-- Figma: Frame 1984080445 (1328x2176.88 at left:56 on the 1440 canvas).
     50px header + Frame 1984080444, which stacks rows of five 250.57x401.38
     Deal Cards - 18.79px between cards, 24px between rows. Unlike the other
     product sections this one is a static grid, not a carousel.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv3-reco {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
    }
    .sqv3-reco__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv3-reco__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #121212;
        margin: 0;
    }
    .sqv3-reco__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
    }

    /* ---------- Grid ---------- */
    .sqv3-reco__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 12px;
        width: 100%;
    }
    .sqv3-reco__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted);
    }

    @media (min-width: 640px) {
        .sqv3-reco__grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .sqv3-reco {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps the cards at their design size on wider displays; max() still
               leaves the Figma 56px gutter at exactly 1440. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-reco__title {
            /* 40px / 50px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv3-reco__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv3-reco__grid {
            /* Five cards per row; 18.79px between cards, 24px between rows. */
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: clamp(16px, 1.667vw, 24px) clamp(12px, 1.3049vw, 18.79px);
        }
    }
</style>

@include('themes.souqify.pages.home-v3.sections.partials.deal_card_styles')

<!-- ============ RECOMMENDED FOR YOU ============ -->
<section class="sqv3-reco">
  <div class="sqv3-reco__head">
    <h2 class="sqv3-reco__title">{{ __('Recommended For You') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv3-reco__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__recommendedCards->isNotEmpty())
    <div class="sqv3-reco__grid">
      @foreach ($__recommendedCards as $p)
        @include('themes.souqify.pages.home-v3.sections.partials.deal_card', ['p' => $p])
      @endforeach
    </div>
  @else
    <p class="sqv3-reco__empty">{{ __('No recommendations yet.') }}</p>
  @endif
</section>
