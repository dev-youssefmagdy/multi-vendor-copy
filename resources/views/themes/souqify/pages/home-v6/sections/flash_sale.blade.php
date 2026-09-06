@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;
    $__flashCards = collect($flashProducts ?? [])->take(8)->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the fanned cards.
        $chip = [
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
            ['bg' => '#FFB00A', 'color' => '#121212'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
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

{{-- Figma: Frame 1984080396 (1440x730). A 704x108 pink header pill (radius
     0/67.4118/67.4118/0) carrying the title and the "Ends in" counter, then the
     white 622px panel (radius 0/67.4118/0/0) holding the fanned card row and the
     outlined Shop now button.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv6-flash {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        background: var(--color-page-bg, #FFFFFF);
    }
    /* Frame 1984080395: the pink pill, 704 wide and open on its left edge. */
    .sqv6-flash__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        width: 100%;
        max-width: 704px;
        padding: 16px;
        background: var(--color-brand-pink, #FF1A90);
        border-radius: 0px 34px 34px 0px;
    }
    .sqv6-flash__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 60 / 48 */
        text-align: right;
        color: #FFFFFF;
        margin: 0;
    }
    /* Frame 1984080248: the rotated label plus the three digit pills. */
    .sqv6-flash__ends {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        gap: 4.07px;
    }
    .sqv6-flash__ends-label {
        /* Figma rotates the label -90deg; writing-mode does it without leaving a
           horizontal layout box behind that would trample the digits. */
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 11px;
        line-height: 1.5;
        letter-spacing: 0.677862px;
        color: #FFFFFF;
    }
    /* Frame 1984080245: 62.36 x 59.65, a 1.35572px pink stroke on white. */
    .sqv6-flash__digit {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 40px;
        height: 38px;
        background: #FFFFFF;
        border: 1.35572px solid var(--color-brand-pink, #FF1A90);
        border-radius: 168.529px;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 18px;
        line-height: 1.5;
        letter-spacing: 0.677862px;
        color: var(--color-brand-pink, #FF1A90);
    }

    /* Frame 1984080362: the white panel under the pill. */
    .sqv6-flash__panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 16px;
        width: 100%;
        padding: 24px 16px;
        background: #FFFFFF;
        border-radius: 0px 34px 0px 0px;
        /* Nothing leaves the panel: the swiper clips its own track, and this
           catches anything the track's rounding pushes past it. */
        overflow: hidden;
    }
    .sqv6-flash__row {
        width: 100%;
        min-width: 0;
        /* Vertical room for the card shadow only: side padding would push the
           track past the swiper's own width and let a seventh card peek in. */
        padding: 12px 0;
        overflow: hidden;
    }
    .sqv6-flash__row .swiper-wrapper { align-items: center; }
    .sqv6-flash__slide { height: auto; }
    /* Group 58 steps the cards down in width from left to right - 289.73,
       278.46, 250.3, 224.85, 205.99, 188.49 of the 1328px row - and overlaps
       them. Real widths (not a scale) so no empty margin is left inside a
       shrunken slide, which is what pushed the row out of the comp's rhythm.
       The cycle repeats per six slides, so it survives any product count. */
    /* Phones fan three cards across the row, stepping down in width just like
       the desktop six. They add up to 100.2% so the fourth starts past the
       right edge and the panel's overflow clips it. */
    .sqv6-flash__slide:nth-child(3n + 1) { width: 42.4%; }
    .sqv6-flash__slide:nth-child(3n + 2) { width: 33.6%; }
    .sqv6-flash__slide:nth-child(3n + 3) { width: 24.2%; }
    .sqv6-flash__slide { z-index: 1; }
    .sqv6-flash__slide:nth-child(3n + 1) { z-index: 3; }
    .sqv6-flash__slide:nth-child(3n + 2) { z-index: 2; }
    .sqv6-flash__slide:nth-child(3n + 3) { z-index: 1; }
    @media (min-width: 1024px) {
    .sqv6-flash__slide:nth-child(6n + 1) { z-index: 6; }
    .sqv6-flash__slide:nth-child(6n + 2) { z-index: 5; }
    .sqv6-flash__slide:nth-child(6n + 3) { z-index: 4; }
    .sqv6-flash__slide:nth-child(6n + 4) { z-index: 3; }
    .sqv6-flash__slide:nth-child(6n + 5) { z-index: 2; }
    .sqv6-flash__slide:nth-child(6n + 6) { z-index: 1; }
    }
    /* Frame 1984080196: 320.21 x 67.41, a 1.53209px pink stroke, radius 85.7968. */
    .sqv6-flash__cta {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 12.2567px;
        width: 100%;
        max-width: 220px;
        height: 46px;
        background: #FFFFFF;
        border: 1.53209px solid var(--color-brand-pink, #FF1A90);
        border-radius: 85.7968px;
        text-decoration: none;
    }
    .sqv6-flash__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.5833;            /* 38 / 24 */
        letter-spacing: 0.766043px;
        color: var(--color-brand-pink, #FF1A90);
        white-space: nowrap;
    }
    .sqv6-flash__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    @media (min-width: 1024px) {
        .sqv6-flash__head {
            /* 24px 56px padding, 68.94px gap, radius 67.4118 */
            padding: clamp(16px, 1.667vw, 24px) clamp(24px, 3.889vw, 56px);
            gap: clamp(24px, 4.788vw, 68.94px);
            border-radius: 0px clamp(34px, 4.681vw, 67.4118px) clamp(34px, 4.681vw, 67.4118px) 0px;
        }
        .sqv6-flash__title { font-size: clamp(32px, 3.333vw, 48px); }
        .sqv6-flash__ends-label { font-size: clamp(12px, 1.1298vw, 16.2687px); }
        .sqv6-flash__digit {
            /* 62.36 x 59.65 */
            width: clamp(48px, 4.331vw, 62.36px);
            height: clamp(46px, 4.142vw, 59.65px);
            font-size: clamp(22px, 2.2596vw, 32.5374px);
        }
        .sqv6-flash__panel {
            /* The comp's content column is 1328px inside the 1440 canvas -
               without the cap the cards inflate on wider displays. */
            padding: clamp(24px, 2.222vw, 32px) max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
            border-radius: 0px clamp(34px, 4.681vw, 67.4118px) 0px 0px;
        }
        /* The six Figma widths normalised to fill the row exactly (they total
           108% at their raw sizes). All six fit, none is cut, and the step-down
           order is preserved - so a swipe swaps a whole set of six for the next
           six in the same order. */
        /* The six add up to 99.4%, not 100 - the leftover keeps sub-pixel
           rounding from letting the seventh card peek in at the right edge. */
        /* The six add up to 100.2% - slightly over the row, so the seventh card
           starts past the right edge and the panel's overflow clips it. Coming
           in under 100% is what left it peeking through. */
        .sqv6-flash__slide:nth-child(6n + 1) { width: 20.4%; }   /* 289.73 */
        .sqv6-flash__slide:nth-child(6n + 2) { width: 19.6%; }   /* 278.46 */
        .sqv6-flash__slide:nth-child(6n + 3) { width: 17.6%; }   /* 250.3  */
        .sqv6-flash__slide:nth-child(6n + 4) { width: 15.8%; }   /* 224.85 */
        .sqv6-flash__slide:nth-child(6n + 5) { width: 14.4%; }   /* 205.99 */
        .sqv6-flash__slide:nth-child(6n + 6) { width: 12.4%; }   /* 188.49 */
        .sqv6-flash__cta {
            /* 320.21 x 67.41 */
            max-width: clamp(240px, 22.237vw, 320.21px);
            height: clamp(52px, 4.681vw, 67.41px);
        }
        .sqv6-flash__cta-label { font-size: clamp(18px, 1.667vw, 24px); }
    }
</style>

<!-- ============ FLASH SALE ============ -->
<section class="sqv6-flash" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <div class="sqv6-flash__head">
    <h2 class="sqv6-flash__title">{{ __('Flash Sale') }}</h2>
    <div class="sqv6-flash__ends">
      <span class="sqv6-flash__ends-label">{{ __('Ends in') }}</span>
      <span class="sqv6-flash__digit" data-flash-hours data-countdown="hours">03</span>
      <span class="sqv6-flash__digit" data-flash-minutes data-countdown="minutes">06</span>
      <span class="sqv6-flash__digit" data-flash-seconds data-countdown="seconds">25</span>
    </div>
  </div>

  <div class="sqv6-flash__panel">
    @if ($__flashCards->isNotEmpty())
      <div class="swiper flash-swiper sqv6-flash__row">
        <div id="flashWrapper" class="swiper-wrapper">
          @foreach ($__flashCards as $p)
            <div class="swiper-slide sqv6-flash__slide" wire:key="flash-v6-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v6.sections.partials.pink_deal_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
    @else
      <p class="sqv6-flash__empty">{{ __('No flash sale products yet.') }}</p>
    @endif

    <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv6-flash__cta">
      <span class="sqv6-flash__cta-label">{{ __('Shop now') }}</span>
    </a>
  </div>
</section>
