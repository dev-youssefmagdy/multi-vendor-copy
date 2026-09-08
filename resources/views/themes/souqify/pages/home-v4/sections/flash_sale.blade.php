@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;

    // Figma cycles three discount-chip palettes across the flash sale cards.
    $__chipPalettes = [
        ['bg' => '#FFB00A', 'color' => '#121212'],
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
    ];

    $__flashCards = collect($flashProducts ?? [])->take(6)->values()->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        $chip = $__chipPalettes[$index % count($__chipPalettes)];
        $__name = \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30);
        $__img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
        $__url = route('tenant.storefront.product', $product->slug);
        $__outOfStock = $product->stockStatus() === 'out_of_stock';

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => $__url,
            'image' => $__img,
            'name' => $__name,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'outOfStock' => $__outOfStock,
            'favData' => json_encode([
                'slug' => $product->slug,
                'name' => $__name,
                'price' => round((float) $pricing['current_price'] * $rate, 2),
                'old_price' => $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null,
                'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
                'rating' => $rating,
                'image' => $__img,
                'url' => $__url,
                'added' => time(),
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
            // Design-only fields with no backing data yet.
            'weight' => null,
            'badge' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
        ];
    });

@endphp

{{-- Figma: Frame 1984080356 (1440x700.33) - row, 24px 56px padding, 32px gap,
     background #5A9B00, border-radius 200px 200px 0 0. Left: the rotated countdown.
     Right: Flash Sale title, the card carousel, and the Shop now button. --}}
<style>
    .sqv4-flash {
        --sqv4-flash-pad: max(16px, calc((100% - 1440px) / 2 + 16px));
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 32px var(--sqv4-flash-pad);
        gap: 24px;
        background: #5A9B00;
        /* Mobile comp arches much harder than the desktop 200px: the flat top only
           starts about a quarter of the way in. */
        border-radius: 48vw 48vw 0px 0px;
        overflow: hidden;
    }
    /* Concentric rings from the comp, centred on the card group. */
    .sqv4-flash::before {
        content: "";
        position: absolute;
        inset: 0;
        background: repeating-radial-gradient(
            circle at 50% 46%,
            #64AA02 0 22px,
            #5A9B00 22px 46px
        );
        /* Follow the arch so the rings stop at the rounded top corners. */
        border-radius: inherit;
        pointer-events: none;
    }
    .sqv4-flash > * { position: relative; z-index: 1; }
    /* ---------- Countdown (Frame 1984080248, rotated -90deg) ---------- */
    .sqv4-flash__timer {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 6.18px;
        flex: none;
    }
    .sqv4-flash__endsin {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 14px;
        line-height: 1.5;
        letter-spacing: 1.0303px;
        color: #FFD428;
        white-space: nowrap;
    }
    .sqv4-flash__box {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 52px;
        height: 50px;
        background: #5A9B00;
        border: 2.06061px solid #FFFFFF;
        border-radius: 14.4242px;
        flex: none;
    }
    .sqv4-flash__digit {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 26px;
        line-height: 1.5;
        letter-spacing: 1.0303px;
        color: #FFFFFF;
    }

    /* ---------- Main column (Frame 1984080448) ---------- */
    .sqv4-flash__main {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0;
        gap: 24px;
        width: 100%;
    }
    .sqv4-flash__titlewrap {
        position: relative;
        display: flex;
        align-items: center;
        /* Bolt sits beside the word, not over it. */
        gap: 0.12em;
        font-size: 32px;
    }
    .sqv4-flash__bolt {
        /* Taken out of the flow and hung off the title's left edge, so the word
           itself lands on the section's centre line instead of the bolt+word
           pair straddling it. */
        position: absolute;
        right: 100%;
        margin-right: 0.12em;
        /* oi:flash: an 81.94px square box beside a 51.1864px title = 1.6em on both
           axes. The path inside spans x 6-24 of the viewBox (the middle 50%) and
           the full height, matching the comp's left:25% / right:25% / top:0 /
           bottom:0 vector. */
        width: 1.6em;
        height: 1.6em;
        flex: none;
        fill: #FFD428;
        pointer-events: none;
    }
    .sqv4-flash__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 1em;                 /* set on .sqv4-flash__titlewrap */
        line-height: 1.25;              /* 64 / 51.1864 */
        color: #FFFFFF;
        margin: 0;
        white-space: nowrap;
    }

    /* ---------- Carousel ----------
       Mobile: card-effect carousel, same technique as ELORA Purple Edition's
       Flash Sale (see the .sqv4-flash__mobile-swiper rules + comment below).
       Desktop: a draggable rotated fan cascade matching Souqify's own
       original card fan (see the .sqv4-flash__desktop-swiper rules below). */
    .sqv4-flash__group {
        --sqv4-deal-accent: #8B03BD;
        position: relative;
        width: 100%;
        overflow: visible;
    }
    .sqv4-flash__group .sqv4-deal {
        position: static;
        width: 100%;
        height: 100%;
    }
    /* Mobile: virtualTranslate + self-computed positioning (see
       mountFlashSaleMobile() in carousels-v4.js) — every slide's CSS base
       position is centered directly (left: calc(50% - 80px), 80 = half the
       160px card width), so with translateX(0) the active card sits
       exactly centered; JS then offsets each neighbor from that centered
       base by a fixed pixel amount, independent of anything Swiper itself
       measures or translates. */
    .sqv4-flash__mobile-swiper {
        overflow: visible;
        padding: 0;
        width: 100%;
    }
    .sqv4-flash__mobile-swiper .swiper-wrapper {
        position: relative;
        height: 256.1px;
    }
    .sqv4-flash__mobile-deal-base {
        position: absolute;
        top: 0;
        left: calc(50% - 80px);
        width: 160px;
        height: 256.1px;
        transition-property: transform;
    }
    /* Desktop: draggable rotated fan cascade (Figma "Group 57", matching
       Souqify's own original card fan) - every card stays the SAME size
       (228.76 x 366.14) and only rotates/translates per fan slot (see
       mountFlashSaleDesktop() in carousels-v4.js). overflow:visible lets
       the fan's cards bleed past the swiper's own auto-width box. */
    .sqv4-flash__desktop-swiper {
        overflow: visible;
        padding-bottom: 24px;
        cursor: grab;
        width: 100% !important;
    }
    .sqv4-flash__desktop-swiper .swiper-wrapper {
        width: 100%;
    }
    .sqv4-flash__deal-base {
        position: absolute;
        top: 0;
        left: 0;
        width: 228.76px;
        height: 366.14px;
    }
    /* ---------- Shop now (Frame 1984080355) ---------- */
    .sqv4-flash__cta {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 8px;
        gap: 8px;
        width: 240px;
        height: 52px;
        background: #FFFFFF;
        border-radius: 34px;
        text-decoration: none;
    }
    .sqv4-flash__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.0417;            /* 25 / 24 */
        letter-spacing: 0.5px;
        color: #199387;
        white-space: nowrap;
    }
    .sqv4-flash__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: #FFFFFF;
        padding: 24px 0;
    }

    /* ---------- Mobile comp ----------
       Stacked: title, then the countdown row, then the fan, then a full-width
       Shop now pill. Unwrapping .sqv4-flash__main with display:contents lets the
       timer sit between the title and the cards without changing the markup. */
    @media (max-width: 1023.98px) {
        /* The bolt hangs over the arch, on the white outside the rounded corner,
           so the section cannot clip to its own border radius. clip-path keeps the
           sides and bottom clipped (the card fan still gets cropped) while leaving
           room above the top edge. */
        .sqv4-flash {
            overflow: visible;
            clip-path: inset(-120px 0px 0px 0px);
        }
        .sqv4-flash__main { display: contents; }
        .sqv4-flash__titlewrap {
            gap: 0.2em;
            align-items: flex-start;
        }
        .sqv4-flash__bolt {
            /* Comp bolt is taller than wide and rides up into the arch. */
            width: 1.2em;
            height: 1.75em;
            margin-top: -0.35em;
            margin-right: 0.2em;
        }
        .sqv4-flash__titlewrap,
        .sqv4-flash__group,
        .sqv4-flash__cta,
        .sqv4-flash__empty { position: relative; z-index: 1; }
        .sqv4-flash__titlewrap { order: 1; }
        .sqv4-flash__timer    { order: 2; }
        .sqv4-flash__group,
        .sqv4-flash__empty    { order: 3; }
        .sqv4-flash__cta      { order: 4; }
        .sqv4-flash__cta {
            width: 100%;
            max-width: 360px;
        }
    }

    @media (min-width: 1024px) {
        .sqv4-flash {
            /* 24px 56px padding, 32px gap, 200px top corners */
            --sqv4-flash-pad: max(
                clamp(24px, 3.889vw, 56px),
                calc((100% - 1440px) / 2 + 56px)
            );
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            padding: 24px var(--sqv4-flash-pad);
            gap: clamp(16px, 2.222vw, 32px);
            border-radius: clamp(80px, 13.889vw, 200px) clamp(80px, 13.889vw, 200px) 0px 0px;
        }
        /* The comp rotates the whole timer row -90deg, which stacks the boxes
           bottom-to-top and stands "Ends in" on its side. It is pinned to the
           frame's left padding edge so the card group can centre on the section
           rather than being pushed off-centre by the timer's width. */
        .sqv4-flash__timer {
            position: absolute;
            left: var(--sqv4-flash-pad);
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            flex-direction: column-reverse;
            align-items: center;
            gap: clamp(4px, 0.429vw, 6.18px);
        }
        /* Reserve the rotated label's on-screen height (its own line-height) so the
           column does not reserve its full horizontal width. */
        .sqv4-flash__endsin-slot {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 1.5em;
            height: 5em;
        }
        .sqv4-flash__endsin {
            /* 24.7273px. The comp nets +90deg on this label (-90 from the rotated
               timer row, +180 on the text itself), so rotate the flat text rather
               than using writing-mode, which would flip each glyph. */
            font-size: clamp(16px, 1.717vw, 24.7273px);
            transform: rotate(-90deg);
            transform-origin: center;
            white-space: nowrap;
        }
        .sqv4-flash__box {
            /* 94.79 x 90.67 */
            width: clamp(60px, 6.583vw, 94.79px);
            height: clamp(58px, 6.296vw, 90.67px);
        }
        .sqv4-flash__digit {
            /* 49.4545px */
            font-size: clamp(30px, 3.434vw, 49.4545px);
        }
        .sqv4-flash__main {
            /* 1176.38 wide, 32px gap - centred in the frame */
            width: 100%;
            max-width: 1176.38px;
            margin-left: auto;
            margin-right: 0;
            gap: clamp(20px, 2.222vw, 32px);
            flex: 0 1 1176.38px;
        }
        .sqv4-flash__titlewrap {
            /* 51.1864px / 64px line-height */
            font-size: clamp(34px, 3.555vw, 51.1864px);
        }
        .sqv4-flash__cta {
            /* 370 x 68 */
            width: clamp(260px, 25.694vw, 370px);
            height: clamp(52px, 4.722vw, 68px);
        }
        .sqv4-flash__cta-label {
            font-size: clamp(18px, 1.667vw, 24px);
        }
    }
</style>

@include('themes.souqify.pages.home-v4.sections.partials.deal_card_styles')

<!-- ============ FLASH SALE ============ -->
<section class="sqv4-flash" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif wire:ignore>
  <div class="sqv4-flash__timer">
    <span class="sqv4-flash__endsin-slot"><span class="sqv4-flash__endsin">{{ __('Ends in') }}</span></span>
    <span class="sqv4-flash__box"><span data-flash-hours class="sqv4-flash__digit">03</span></span>
    <span class="sqv4-flash__box"><span data-flash-minutes class="sqv4-flash__digit">06</span></span>
    <span class="sqv4-flash__box"><span data-flash-seconds class="sqv4-flash__digit">25</span></span>
  </div>

  <div class="sqv4-flash__main">
    <div class="sqv4-flash__titlewrap">
      {{-- oi:flash - an 81.94px square box whose vector spans the middle 50%
           horizontally (left 25% / right 25%) and the full height. --}}
      <svg viewBox="0 0 24 24" class="sqv4-flash__bolt" aria-hidden="true">
        <path d="M15.6 0 L6 15 H10.8 L8.4 24 L18 9 H13.2 Z"/>
      </svg>
      <h2 class="sqv4-flash__title">{{ __('Flash Sale') }}</h2>
    </div>

    @if ($__flashCards->isNotEmpty())
      {{-- Mobile: card-effect carousel, same technique as ELORA Purple
           Edition's Flash Sale (see mountFlashSaleMobile() in
           carousels-v4.js). --}}
      <div class="sqv4-flash__group lg:!hidden">
        <div class="swiper sqv4-flash__mobile-swiper">
          <div class="swiper-wrapper" id="flashMobileWrapper">
            @foreach ($__flashCards as $p)
              <div class="swiper-slide sqv4-flash__mobile-deal-base">
                @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => ''])
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Desktop: draggable rotated fan cascade, matching Souqify's own
           original Flash Sale card fan (see mountFlashSaleDesktop() in
           carousels-v4.js). --}}
      <div class="swiper sqv4-flash__desktop-swiper !hidden lg:!block">
        <div class="sqv4-flash__group swiper-wrapper" id="flashDesktopWrapper">
          @foreach ($__flashCards as $p)
            <div class="swiper-slide sqv4-flash__deal-base" style="position:absolute; top:0; left:0;">
              @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => ''])
            </div>
          @endforeach
        </div>
      </div>
    @else
      <p class="sqv4-flash__empty">{{ __('No flash deals right now.') }}</p>
    @endif

    <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv4-flash__cta">
      <span class="sqv4-flash__cta-label">{{ __('Shop now') }}</span>
    </a>
  </div>
</section>
