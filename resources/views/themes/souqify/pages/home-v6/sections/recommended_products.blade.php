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
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
        ];
    });
@endphp

{{-- Figma: Frame 1984080445 (1328 wide) - a 50px header over rows of five
     250.57px Deal Cards, 18.79px apart with 24px between rows. A static grid in
     the comp, so no carousel here; Livewire keeps paginating on scroll.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv6-reco {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-page-bg, #FFFFFF);
    }
    /* Frame 1984080017 */
    .sqv6-reco__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv6-reco__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #121212;
        margin: 0;
    }
    .sqv6-reco__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984080444: rows of five cards, 18.79px apart, 24px between rows. */
    .sqv6-reco__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 12px;
        width: 100%;
    }
    /* Deal Card: radius 7.63145, no shadow in this section. */
    .sqv6-reco__grid .sqv6-deal {
        border-radius: 7.63145px;
        box-shadow: none;
    }
    .sqv6-reco__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    @media (min-width: 640px) {
        .sqv6-reco__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .sqv6-reco {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-reco__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv6-reco__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        .sqv6-reco__grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: clamp(16px, 1.667vw, 24px) clamp(12px, 1.3049vw, 18.79px);
        }
    }
</style>

<!-- ============ RECOMMENDED FOR YOU ============ -->
<section class="sqv6-reco">
  <div class="sqv6-reco__head">
    <h2 class="sqv6-reco__title">{{ __('Recommended For You') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv6-reco__seeall">{{ __('see all') }}</a>
  </div>

  <div id="recommendedGrid" class="sqv6-reco__grid">
    @forelse ($__recommendedCards as $p)
      <div wire:key="recommended-v6-{{ $p['id'] }}">
        @include('themes.souqify.pages.home-v6.sections.partials.pink_deal_card', ['p' => $p])
      </div>
    @empty
      <p class="sqv6-reco__empty" style="grid-column: 1 / -1">{{ __('No recommended products yet.') }}</p>
    @endforelse
  </div>

  @if ($hasMoreRecommended ?? false)
    <div wire:intersect="loadMoreRecommended" class="flex items-center justify-center py-[8px] w-full">
      <div wire:loading wire:target="loadMoreRecommended" class="flex items-center gap-2 text-[14px]" style="color:var(--color-text-subtitle)">
        <svg class="animate-spin size-[18px]" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        {{ __('Loading more...') }}
      </div>
    </div>
  @endif
</section>
