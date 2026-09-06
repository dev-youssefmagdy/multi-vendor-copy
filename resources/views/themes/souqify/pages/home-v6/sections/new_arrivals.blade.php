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

{{-- Figma: Frame 1984080239 (1440 x 547.02) - a full-bleed #FF1A90 panel,
     24px 56px padding, 24px gap: a 50px header then the 425.02px card row
     (19.96px between cards).
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv6-newin {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        position: relative;
        background: var(--color-brand-pink, #FF1A90);
        overflow: hidden;
        isolation: isolate;
    }
    /* Voronoi backdrop (phones): a crystallised mosaic of irregular 5-7 sided
       cells, each a different shade of hot pink/magenta with a faint white
       stroke, generated as a seamlessly tiling SVG. ::after carries the cells;
       ::before is the pink-to-magenta radial shading behind them, brighter at
       the centre than at the edges. Desktop replaces both with the bokeh. */
    .sqv6-newin::before,
    .sqv6-newin::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background-repeat: repeat;
    }
    .sqv6-newin::before {
        background-image: radial-gradient(
            120% 90% at 50% 34%,
            #FF4FA8 0%,
            #FF1A90 42%,
            #D6007A 74%,
            #A8004C 100%
        );
        background-repeat: no-repeat;
        background-size: 100% 100%;
    }
    .sqv6-newin::after {
        background-image: url('{{ asset('souqify-5/assets/images/voronoi-pink.svg') }}');
        background-size: 420px 420px;
        /* Near full strength so the facets and their strokes read clearly; the
           radial shading below still keeps the centre brighter. */
        opacity: 0.75;
        mix-blend-mode: normal;
    }
    /* A dark wash over the particles: it knocks the pink back so the cards read
       as the brightest thing on the panel. Above the bokeh, below the content. */
    .sqv6-newin__shade {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background: rgba(120, 0, 60, 0.28);
    }
    .sqv6-newin > *:not(.sqv6-newin__shade) { position: relative; z-index: 1; }
    /* Frame 1984080018 */
    .sqv6-newin__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv6-newin__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv6-newin__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
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
        color: rgba(255, 255, 255, 0.8);
    }

    /* Narrow phones: a smaller tile keeps the cells from reading as a few big
       flat shapes behind the cards. */
    @media (max-width: 480px) {
        .sqv6-newin::after { background-size: 340px 340px; }
    }

    @media (min-width: 1024px) {
        /* Desktop keeps the original bokeh: soft light blooms over the pink, on
           two tiled layers at different sizes so they never line up. */
        .sqv6-newin::before {
            filter: blur(20px);
            background-repeat: repeat;
            background-image:
                radial-gradient(circle 74px at 10% 16%, rgba(255, 255, 255, 0.54), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 44px at 38% 6%, rgba(255, 252, 244, 0.48), rgba(255, 252, 244, 0) 72%),
                radial-gradient(circle 86px at 74% 30%, rgba(255, 255, 255, 0.46), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 56px at 20% 54%, rgba(255, 255, 255, 0.50), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 66px at 50% 76%, rgba(255, 252, 244, 0.44), rgba(255, 252, 244, 0) 72%),
                radial-gradient(circle 40px at 88% 62%, rgba(255, 255, 255, 0.48), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 52px at 66% 96%, rgba(255, 255, 255, 0.46), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 36px at 4% 88%, rgba(255, 252, 244, 0.50), rgba(255, 252, 244, 0) 72%);
            background-size: 330px 330px;
            background-position: 0 0;
        }
        .sqv6-newin::after {
            opacity: 1;
            mix-blend-mode: normal;
            filter: blur(14px);
            background-image:
                radial-gradient(circle 36px at 18% 26%, rgba(255, 255, 255, 0.44), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 48px at 64% 14%, rgba(255, 255, 255, 0.40), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 28px at 44% 56%, rgba(255, 252, 244, 0.42), rgba(255, 252, 244, 0) 72%),
                radial-gradient(circle 42px at 86% 70%, rgba(255, 255, 255, 0.38), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 24px at 8% 82%, rgba(255, 255, 255, 0.42), rgba(255, 255, 255, 0) 72%),
                radial-gradient(circle 32px at 58% 92%, rgba(255, 255, 255, 0.40), rgba(255, 255, 255, 0) 72%);
            background-size: 200px 200px;
            background-position: 55px 80px;
        }
        .sqv6-newin {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-newin__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv6-newin__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ NEW IN ============ -->
<section class="sqv6-newin">
  <span class="sqv6-newin__shade" aria-hidden="true"></span>

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
