@php
    // Figma cycles three discount-chip palettes across the receding cards.
    $__chipPalettes = [
        ['bg' => '#FFB00A', 'color' => '#121212'],
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
    ];

    $__bestSellerCards = collect($bestSelling ?? [])->take(6)->values()->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
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

    // Figma "Group 57" (1354.21 x 472.89). Unlike Flash Sale these cards are not
    // rotated - they recede in size left to right. Each entry is left/top/width/height
    // as a percentage of the group, plus the stacking order (the biggest card is on
    // top and each following card sits behind it).
    //   Deal 18  295.45x472.89  left 56      top 98
    //   Deal 19  283.96x454.2   left 329     top 107
    //   Deal 4   255.24x409     left 580.18  top 129
    //   Deal 17  229.29x367     left 811     top 155
    //   Deal 16  210.06x336     left 1021    top 173
    //   Deal 20  192.21x308     left 1218    top 193
    $__fan = [
        ['left' => 4.135,  'top' => 20.723, 'w' => 21.816, 'h' => 100.000],
        ['left' => 24.294, 'top' => 22.626, 'w' => 20.968, 'h' => 96.048],
        ['left' => 42.843, 'top' => 27.279, 'w' => 18.847, 'h' => 86.489],
        ['left' => 59.887, 'top' => 32.777, 'w' => 16.931, 'h' => 77.607],
        ['left' => 75.395, 'top' => 36.583, 'w' => 15.511, 'h' => 71.053],
        ['left' => 89.942, 'top' => 40.813, 'w' => 14.193, 'h' => 65.132],
    ];
@endphp

@include('themes.souqify.pages.home-v4.sections.partials.deal_card_styles')

{{-- Figma: Frame 1984080240 (1440x626.89) - 24px 56px padding, 24px gap,
     background url(tras.png) blended plus-lighter over #5A9B00. --}}
<style>
    .sqv4-best {
        --sqv4-flash-pad: max(16px, calc((100% - 1440px) / 2 + 16px));
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 32px var(--sqv4-flash-pad);
        gap: 16px;
        background: #5A9B00;
        overflow: hidden;
    }
    /* The comp blends a texture over the green with plus-lighter. */
    .sqv4-best__overlay {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        mix-blend-mode: plus-lighter;
        opacity: 0.5;
        pointer-events: none;
    }
    /* White speckle over the green: a few fat dots with finer ones between
       them, spread on wide tiles so they stay sparse, and blurred so the edges
       glow instead of reading as hard circles. */
    .sqv4-best::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.6;
        filter: blur(1.6px);
        background-image:
            radial-gradient(circle at 18% 26%, rgba(255,255,255,0.95) 0 2.8px, transparent 3.2px),
            radial-gradient(circle at 72% 12%, rgba(255,255,255,0.9)  0 2.2px, transparent 2.6px),
            radial-gradient(circle at 40% 78%, rgba(255,255,255,0.9)  0 3px,   transparent 3.4px),
            radial-gradient(circle at 86% 62%, rgba(255,255,255,0.8)  0 1.4px, transparent 1.8px),
            radial-gradient(circle at 8% 84%,  rgba(255,255,255,0.8)  0 1.2px, transparent 1.6px);
        background-size:
            150px 130px, 118px 168px, 196px 152px, 96px 108px, 84px 122px;
    }

    .sqv4-best > *:not(.sqv4-best__overlay) { position: relative; z-index: 1; }
    .sqv4-best::after { z-index: 0; }

    .sqv4-best__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv4-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv4-best__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
        text-decoration: none;
    }

    /* ---------- Receding card group (Group 57, 1354.21 x 472.89) ---------- */
    .sqv4-best__group {
        /* Best Seller accent: the theme green. */
        --sqv4-deal-accent: #5A9B00;
        position: relative;
        width: 100%;
        aspect-ratio: 1354.21 / 472.89;
        /* The tallest card (top 98 + height 472.89 = 570.89) overhangs the group by
           98px; reserve that below so it clears the pagination.
           Percentage margins resolve against the width, so 98/1354.21. */
        margin-bottom: 7.24%;
    }

    /* ---------- Pagination (Frame 1984080197) ---------- */
    .sqv4-best__dots {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 0;
        gap: 3px;
        width: 100%;
        height: 8px;
    }
    .sqv4-best__dots .best-seller-dot {
        width: 8px;
        height: 8px;
        background: #F3F3F3;
        border-radius: 29px;
        opacity: 1;
        transition: width 0.2s ease, background-color 0.2s ease;
        cursor: pointer;
    }
    .sqv4-best__dots .best-seller-dot.is-active {
        width: 24px;
        background: #FFE100;
    }
    .sqv4-best__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    /* ---------- Mobile comp ----------
       The phone layout is not the desktop fan scaled down: the cards are close to
       square (~53% wide, ~54% tall against the screen) and overlap by roughly two
       thirds, so only a slice of each trailing card shows. These per-card values
       override the inline percentages the desktop fan writes. */
    @media (max-width: 1023.98px) {
        .sqv4-best__group {
            width: 100%;
            /* An explicit height beats aspect-ratio here: the base rule already
               sets the desktop 1354.21/472.89 ratio, and that squashed group is
               what was flattening the cards. */
            aspect-ratio: auto;
            height: 59.5vw;
            margin-bottom: 0;
            /* Three cards sit inside the gutters; the rest of the fan is reached by
               swiping. Absolutely positioned cards still contribute to the scroll
               width, so no extra wrapper is needed. */
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
            overscroll-behavior-x: contain;
        }
        .sqv4-best__group::-webkit-scrollbar { display: none; }
        /* Snap per page of three, not per card: card 4 starts at ~98% of the group,
           so one viewport of scroll lands it flush at the left edge. */
        .sqv4-best__group .sqv4-deal:nth-child(3n + 1) { scroll-snap-align: start; }
        /* Three cards make a page - big, medium, small - and the pattern restarts
           one full page (100%) to the right, so every swipe brings the next three
           in at exactly the same widths as the first three. */
        .sqv4-best__group .sqv4-deal:nth-child(1) { left: 0% !important;     top: 0% !important;  width: 41.2% !important; height: 100% !important; }
        .sqv4-best__group .sqv4-deal:nth-child(2) { left: 32.2% !important;  top: 6% !important;  width: 37.6% !important; height: 91% !important; }
        .sqv4-best__group .sqv4-deal:nth-child(3) { left: 66.2% !important;  top: 12% !important; width: 34% !important;   height: 82% !important; }
        .sqv4-best__group .sqv4-deal:nth-child(4) { left: 100% !important;   top: 0% !important;  width: 41.2% !important; height: 100% !important; }
        .sqv4-best__group .sqv4-deal:nth-child(5) { left: 132.2% !important; top: 6% !important;  width: 37.6% !important; height: 91% !important; }
        .sqv4-best__group .sqv4-deal:nth-child(6) { left: 166.2% !important; top: 12% !important; width: 34% !important;   height: 82% !important; }
    }

    @media (min-width: 1024px) {
        .sqv4-best {
            /* 24px 56px padding, 24px gap */
            --sqv4-flash-pad: max(
                clamp(24px, 3.889vw, 56px),
                calc((100% - 1440px) / 2 + 56px)
            );
            padding: 24px var(--sqv4-flash-pad);
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv4-best__title {
            /* 40px / 50px line-height */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv4-best__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv4-best">
  <img src="{{ asset('souqify-3/assets/images/best-seller-swirl-bg.png') }}" alt="" class="sqv4-best__overlay" />

  <div class="sqv4-best__head">
    <h2 class="sqv4-best__title">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv4-best__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__bestSellerCards->isNotEmpty())
    <div id="bestSellerWrapper" class="sqv4-best__group">
      @foreach ($__bestSellerCards as $index => $p)
        @php
          $__pos = $__fan[$index % count($__fan)];
          // Biggest card sits on top; each subsequent card falls behind it.
          $__z = count($__fan) - $index;
          $__style = "left:{$__pos['left']}%; top:{$__pos['top']}%; width:{$__pos['w']}%; height:{$__pos['h']}%; z-index:{$__z};";
        @endphp
        @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => $__style, 'cartIcon' => 'icon-cart-green.svg'])
      @endforeach
    </div>

    <div class="sqv4-best__dots">
      @foreach ($__bestSellerCards as $index => $p)
        <span class="best-seller-dot @if ($index === 0) is-active @endif"></span>
      @endforeach
    </div>
  @else
    <p class="sqv4-best__empty">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
