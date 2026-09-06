@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;

    // Figma cycles three discount-chip palettes across the fanned cards.
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

    // Figma "Group 57" (1176.38 x 456.33): each card's left/top as a percentage of
    // the group, plus its rotation.
    // Verbatim Figma coordinates, as percentages of Group 57 (1176.38 x 456.33):
    //   Deal 4  left 0       top 129.49  rotate -11.98
    //   Deal 17 left 233     top 96      rotate 0
    //   Deal 16 left 388.33  top 130.49  rotate 8.97
    //   Deal 20 left 535.33  top 111.49  rotate -11.98
    //   Deal 18 left 759.33  top 104.49  rotate 0
    //   Deal 19 left 893.33  top 155     rotate 8.97
    $__fan = [
        ['left' => 0.000,  'top' => 28.38, 'rot' => -11.98],
        ['left' => 19.807, 'top' => 21.04, 'rot' => 0],
        ['left' => 33.010, 'top' => 28.60, 'rot' => 8.97],
        ['left' => 45.506, 'top' => 24.43, 'rot' => -11.98],
        ['left' => 64.548, 'top' => 22.90, 'rot' => 0],
        ['left' => 75.938, 'top' => 33.97, 'rot' => 8.97],
    ];
@endphp

{{-- Figma: Frame 1984080356 (1440x700.33) - row, 24px 56px padding, 32px gap,
     background #5A9B00, border-radius 200px 200px 0 0. Left: the rotated countdown.
     Right: Flash Sale title, the fanned card group, and the Shop now button. --}}
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

    /* ---------- Fanned card group (Group 57, 1176.38 x 456.33) ---------- */
    .sqv4-flash__group {
        /* Flash Sale accent: the comp's purple. */
        --sqv4-deal-accent: #8B03BD;
        position: relative;
        width: 100%;
        /* Group 57: 1176.38 x 456.33 */
        aspect-ratio: 1176.38 / 456.33;
        /* The lowest card (top 155 + height 366.14 = 521) overhangs the group by
           64.67px; reserve that below so it never lands on the Shop now button.
           Percentage margins resolve against the width, so 64.67/1176.38. */
        margin-bottom: 5.5%;
    }
    /* Card footprint inside this section's group. */
    .sqv4-flash__group .sqv4-deal {
        /* 228.76 / 1176.38 wide, 366.14 / 456.33 tall */
        width: 19.446%;
        height: 80.24%;
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
        /* Three overlapping cards make a page and the pattern restarts 100% to the
           right, so a swipe brings the next three in at the same size and tilt. */
        .sqv4-flash__group {
            width: 100%;
            aspect-ratio: auto;
            height: 70vw;
            margin-bottom: 6%;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
            overscroll-behavior-x: contain;
        }
        .sqv4-flash__group::-webkit-scrollbar { display: none; }
        /* Pin the scroll width to a whole number of pages. Without this the width
           is decided by the last card's tilted corner, the scroll can never reach
           100%, and page two lands short - leaving a slice of the previous card. */
        .sqv4-flash__group::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: 1px;
            pointer-events: none;
        }
        .sqv4-flash__group .sqv4-deal:nth-child(3n + 1) { scroll-snap-align: start; }
        /* Three cards face on, overlapping like the comp: the middle one is the
           biggest and sits on top, the outer two tuck behind it. Only position,
           size and stacking are overridden - the inline transform keeps each
           card's Figma tilt. The next three start one row width to the right. */
        .sqv4-flash__group .sqv4-deal:nth-child(1) { left: 7% !important;   top: 18% !important; width: 37% !important; height: 78% !important; z-index: 1 !important; }
        .sqv4-flash__group .sqv4-deal:nth-child(2) { left: 32% !important;  top: 8% !important;  width: 40% !important; height: 88% !important; z-index: 3 !important; }
        .sqv4-flash__group .sqv4-deal:nth-child(3) { left: 57% !important;  top: 18% !important; width: 37% !important; height: 78% !important; z-index: 2 !important; }
        .sqv4-flash__group .sqv4-deal:nth-child(4) { left: 107% !important; top: 18% !important; width: 37% !important; height: 78% !important; z-index: 1 !important; }
        .sqv4-flash__group .sqv4-deal:nth-child(5) { left: 132% !important; top: 8% !important;  width: 40% !important; height: 88% !important; z-index: 3 !important; }
        .sqv4-flash__group .sqv4-deal:nth-child(6) { left: 157% !important; top: 18% !important; width: 37% !important; height: 78% !important; z-index: 2 !important; }
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
<section class="sqv4-flash" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
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
      <div id="flashStack" class="sqv4-flash__group">
        @foreach ($__flashCards as $index => $p)
          @php
            $__pos = $__fan[$index % count($__fan)];
            $__z = $index + 1;
            $__style = "left:{$__pos['left']}%; top:{$__pos['top']}%; z-index:{$__z}; transform:rotate({$__pos['rot']}deg);";
          @endphp
          @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => $__style])
        @endforeach
      </div>
    @else
      <p class="sqv4-flash__empty">{{ __('No flash deals right now.') }}</p>
    @endif

    <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv4-flash__cta">
      <span class="sqv4-flash__cta-label">{{ __('Shop now') }}</span>
    </a>
  </div>
</section>
