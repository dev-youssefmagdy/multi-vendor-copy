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

        $__url = route('tenant.storefront.product', $product->slug);
        $__img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
        $__name = \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30);
        $__fav = json_encode([
            'slug' => $product->slug,
            'name' => $__name,
            'price' => round((float) $pricing['current_price'] * $rate, 2),
            'old_price' => $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
            'rating' => $rating,
            'image' => $__img,
            'url' => $__url,
            'added' => time(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => $__url,
            'image' => $__img,
            'tag' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
            'name' => $__name,
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'outOfStock' => $product->stockStatus() === 'out_of_stock',
            'fav' => $__fav,
        ];
    });

    $__flashMobile = $__flashCards->take(3);
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
    /* ---------- Draggable fan cascade ----------
       Same technique as Green Edition's Best Seller carousel (see
       mountFanCascadeCarousel() in carousels-v6.js): every slide is
       absolutely positioned from a fixed geometry table indexed by distance
       from the active slide, so dragging shifts which product sits in the
       biggest/frontmost slot while the rest cascade down in size behind it -
       the front card cycles to the back as you page through. */
    .sqv6-flash__group {
        /* Flash Sale accent: keep the card's own pink. */
        --sqv6-deal-accent: #FD1A8F;
        position: relative;
        width: 100%;
    }
    .sqv6-flash__row {
        /* overflow:visible lets the fan's cards bleed past the swiper's own
           (much smaller) auto-width box - the panel's own overflow:hidden
           still clips it at the edges. */
        overflow: visible;
        padding: 0 0 24px;
        cursor: grab;
        width: 100% !important;
    }
    @media (max-width: 1023.98px) {
        /* The mobile cascade table (SQV6_FLASH_POSITIONS_MOBILE) spans
           ~375.75px, but the panel's 16px side padding only leaves 343px
           inside .sqv6-flash__row - without this the last card's peek slice
           gets clipped mid-card (through its tag/cart badges) instead of at a
           clean edge. Bleeding the row out past the padding gives the
           cascade back the width it was sized for. */
        .sqv6-flash__row {
            width: calc(100% + 32px) !important;
            margin-left: -16px;
            margin-right: -16px;
        }
    }
    /* Every slide is this ONE fixed base size (matching the biggest/active
       Figma card exactly); mountFanCascadeCarousel applies
       `transform: translate(left, top) scale(ratio)` per slide to produce
       every other cascade depth from it, so content never gets crammed into
       a shrunk box - the whole card scales as one unit. */
    .sqv6-flash__deal-base {
        position: absolute;
        top: 0;
        left: 0;
        width: 154.5px;
        height: 223.1px;
        transform-origin: top left;
    }
    @media (min-width: 1024px) {
        .sqv6-flash__deal-base {
            width: 295.45px;
            height: 472.89px;
        }
    }
    .sqv6-flash__deal-base .sqv6-deal {
        position: static;
        width: 100%;
        height: 100%;
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
        .sqv6-flash__cta {
            /* 320.21 x 67.41 */
            max-width: clamp(240px, 22.237vw, 320.21px);
            height: clamp(52px, 4.681vw, 67.41px);
        }
        .sqv6-flash__cta-label { font-size: clamp(18px, 1.667vw, 24px); }
    }
</style>

<!-- ============ FLASH SALE ============ -->
<section class="sqv6-flash" wire:ignore @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
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
      {{-- Same draggable fan-cascade carousel as Green Edition's Best Seller
           (see mountFanCascadeCarousel() in carousels-v6.js). --}}
      <div class="swiper flash-swiper sqv6-flash__row lg:!hidden w-full">
        <div class="swiper-wrapper sqv6-flash__group" id="flashMobileWrapper">
          @foreach ($__flashMobile as $p)
            <div class="swiper-slide sqv6-flash__deal-base" style="position:absolute; top:0; left:0;" wire:key="flash-mobile-v6-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v6.sections.partials.pink_deal_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>

      <div class="swiper flash-swiper sqv6-flash__row !hidden lg:!block w-full">
        <div class="swiper-wrapper sqv6-flash__group" id="flashDesktopWrapper">
          @foreach ($__flashCards as $p)
            <div class="swiper-slide sqv6-flash__deal-base" style="position:absolute; top:0; left:0;" wire:key="flash-desktop-v6-{{ $p['id'] }}">
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
