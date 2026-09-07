@php
    $__recommendedCards = collect($recommendedProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the grid.
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
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
        ];
    });
@endphp

{{-- Figma: Frame 1984080445 (1328 wide, 24px gap) - a 50px header over rows of
     Deal Cards (Frame 1984079790): five 250.57px cards per row, 18.79px apart,
     rows 24px apart. Not a carousel: the rows stack and the section keeps
     loading more on scroll, so it renders as a five-column grid.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-rec {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    /* Frame 1984080017 */
    .sqv2-rec__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv2-rec__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #121212;
        margin: 0;
    }
    .sqv2-rec__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984079790 repeated: 18.79px between cards, 24px between rows. */
    .sqv2-rec__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 12px;
        width: 100%;
    }
    .sqv2-rec__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted, #8F8F8F);
        grid-column: 1 / -1;
    }
    .sqv2-rec__more {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 8px 0;
    }
    .sqv2-rec__more-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: var(--color-text-subtitle, #8F8F8F);
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-rec__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px 16px; }
    }

    @media (min-width: 1024px) {
        .sqv2-rec {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv2-rec__title { font-size: clamp(32px, 2.778vw, 40px); }
        .sqv2-rec__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        .sqv2-rec__grid {
            /* Five cards per row: 250.57px each at an 18.79px gap. */
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 24px 18.79px;
        }
    }
</style>

<!-- ============ RECOMMENDED FOR YOU ============ -->
<section class="sqv2-rec">
  <div class="sqv2-rec__head">
    <h2 class="sqv2-rec__title">{{ __('Recommended For You') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv2-rec__seeall">{{ __('see all') }}</a>
  </div>

  <div id="recommendedWrapper" class="sqv2-rec__grid">
    @forelse ($__recommendedCards as $p)
      <div wire:key="recommended-v2-{{ $p['id'] }}">
        @include('themes.souqify.pages.home-v2.sections.partials.purple_deal_card', ['p' => $p])
      </div>
    @empty
      <p class="sqv2-rec__empty">{{ __('No recommended products yet.') }}</p>
    @endforelse
  </div>

  @if ($hasMoreRecommended ?? false)
    <div wire:intersect="loadMoreRecommended" class="sqv2-rec__more">
      <span wire:loading wire:target="loadMoreRecommended" class="sqv2-rec__more-label">
        <svg class="animate-spin size-[18px]" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        {{ __('Loading more...') }}
      </span>
    </div>
  @endif
</section>
