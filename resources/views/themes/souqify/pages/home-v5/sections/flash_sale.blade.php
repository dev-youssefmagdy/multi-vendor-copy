@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;
    $__flashCards = collect($flashProducts ?? [])->take(8)->values()->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'brand' => $product->brand?->translationValue('name') ?? $product->vendor?->name ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 40),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? '-' . (int) round((float) $pricing['discount_percentage']) . '%' : null,
            'sold' => $product->orders_count ?? null,
            'stock' => $variant?->quantity,
        ];
    });

    // Figma stacks the cards two per column (Frame 1984080157: 399.87 x 352.43,
    // 14.03px gap), and the columns are what the carousel scrolls.
    $__flashColumns = $__flashCards->chunk(2)->values();
@endphp

{{-- Figma: Frame 1984080361 (1440x566.66). Orange panel with a color-dodge
     pattern and a 0px 200px corner radius, two 42px lightning badges pinned
     outside the content (bottom-left and top at left:255), a 40px/800 title,
     the column carousel, then the Shop now + "Ends in" bar.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv5-flash {
        position: relative;
        isolation: isolate;
        /* The button and the badge hang below the panel, so the section reserves
           the strip they occupy instead of letting them overlap what follows. */
        padding-bottom: 22px;
    }
    /* Rectangle 5967: radius 0px 200px = 0 / 200 / 0 / 200. Kept as its own
       clipping box so the badge and button can hang outside the rounded corner
       instead of being cut off by overflow:hidden. */
    .sqv5-flash__panel {
        position: relative;
        border-radius: 0 170px 0 210px;
        background: var(--color-primary);
        /* Not clipped: cards may cross the rounded corner. The pattern and the
           wash carry the radius themselves so only they stay inside it. */
        overflow: visible;
    }
    .sqv5-flash__pattern {
        position: absolute;
        inset: 0;
        border-radius: inherit;
        width: 100%;
        height: 100%;
        object-fit: cover;
        mix-blend-mode: color-dodge;
        pointer-events: none;
        z-index: 0;
    }
    /* A flat dark wash over the pattern: color-dodge blows the photo out to near
       white, so the panel needs it knocked back to read as orange. */
    .sqv5-flash__shade {
        position: absolute;
        inset: 0;
        border-radius: inherit;
        /* Desktop only: on phones the panel reads dirty rather than deep. */
        background: transparent;
        pointer-events: none;
        z-index: 0;
    }
    /* Frame 1984080359 / 1984080360: the two lightning badges sit outside the
       padded content, on the panel itself. */
    .sqv5-flash__badge {
        position: absolute;
        z-index: 3;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 36px;
        height: 36px;
        background: var(--color-primary);
        border-radius: 50%;
        pointer-events: none;
    }
    .sqv5-flash__badge img { width: 68.9%; height: 68.9%; }   /* 29 / 42 */
    /* Sibling of the button inside the bar, not a child: a child of the button
       cannot paint behind it, because the button's own z-index makes it a
       stacking context. Here a lower z-index does put it under the button. */
    .sqv5-flash__badge--bottom {
        left: 10px;
        bottom: -16px;
        z-index: 1;
    }
    /* Mirrors the bottom-left badge across the panel. */
    /* Centred on the panel's top edge. */
    .sqv5-flash__badge--top { right: 10px; top: -4px; }

    .sqv5-flash__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        /* 24px 56px padding, 26px gap */
        padding: 24px 16px;
        gap: 20px;
    }
    .sqv5-flash__title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    /* Phones carry the glyph as the top-right badge instead. */
    .sqv5-flash__title img { display: none; width: 20px; height: 20px; }

    /* ---------- Column carousel ---------- */
    .sqv5-flash__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv5-flash__row .swiper-wrapper { align-items: stretch; }
    /* Column width comes from slidesPerView (3.35 on desktop, so the 4th column
       shows a sliver and reads as swipeable). */
    .sqv5-flash__col {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        /* 14.03px between the two stacked cards */
        gap: 12px;
        height: auto;
    }

    /* ---------- SideProductCard: 399.87 x 169.2 ---------- */
    .sqv5-flash__card {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 10px;
        gap: 12px;
        background: #FFFFFF;
        border-radius: 10px;
        min-width: 0;
    }
    .sqv5-flash__media {
        position: relative;
        flex: none;
        /* 137.97 square, radius 8.18445. Fluid below the desktop breakpoint so a
           360px phone still leaves room for the copy beside it. */
        width: clamp(78px, 26vw, 110px);
        height: clamp(78px, 26vw, 110px);
        border-radius: 8.18445px;
        background: var(--color-surface, #F3F3F3);
        overflow: hidden;
        isolation: isolate;
    }
    .sqv5-flash__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* "Text" ribbon: 44.94 x 23.37, top-left of the image. */
    .sqv5-flash__ribbon {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        padding: 2.33841px 7.01524px;
        background: linear-gradient(262.6deg, #AC0202 -2.08%, #E30000 44.54%, #9E0000 100%);
        border-radius: 4.67683px;
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 10px;
        line-height: 1.625;             /* 19 / 11.6921 */
        color: #FFFFFF;
    }
    /* BTN: 44.43 square at left/top 84.18 of the 137.97 image = 61%. */
    .sqv5-flash__cart {
        position: absolute;
        left: 61%;
        top: 61%;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 32.2%;                   /* 44.43 / 137.97 */
        height: 32.2%;
        background: #FFFFFF;
        box-shadow: 0px 0px 14.0305px rgba(0, 0, 0, 0.25);
        border-radius: 9.35366px;
    }
    .sqv5-flash__cart img { width: 63.2%; height: 63.2%; }   /* 28.06 / 44.43 */

    .sqv5-flash__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        /* 9.35px between the text block and the countdown */
        gap: 8px;
        flex: 1;
        min-width: 0;
    }
    .sqv5-flash__text {
        display: flex;
        flex-direction: column;
        gap: 4.68px;
        width: 100%;
        min-width: 0;
    }
    .sqv5-flash__brand {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 9.35366px;
        line-height: 1.283;
        letter-spacing: 0.584604px;
        color: #6A7282;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv5-flash__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 15px;
        line-height: 1.283;             /* 24 / 18.7073 */
        color: #1E2939;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv5-flash__prices {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 3.51px;
    }
    .sqv5-flash__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 14px;
        line-height: 1.283;             /* 21 / 16.3689 */
        /* Souqify/primary from the card component, kept literal: the card in the
           comp stays blue even though the panel around it is the orange token. */
        color: #0159ED;
    }
    .sqv5-flash__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 10px;
        line-height: 1.283;
        letter-spacing: 0.584604px;
        text-decoration-line: line-through;
        color: #99A1AF;
    }
    /* Stock progress: 4.68px track, red fill. */
    .sqv5-flash__meter {
        width: 100%;
        height: 4.68px;
        background: #E5E7EB;
        border-radius: 999px;
        overflow: hidden;
    }
    .sqv5-flash__meter-fill {
        height: 100%;
        background: #DE1709;
        border-radius: 999px;
    }
    .sqv5-flash__stockrow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 2.34px;
        width: 100%;
    }
    .sqv5-flash__sold,
    .sqv5-flash__left {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 10px;
        line-height: 1.283;
        letter-spacing: 0.584604px;
        white-space: nowrap;
    }
    .sqv5-flash__sold { color: #6A7282; }
    .sqv5-flash__left { color: #DE1709; }

    /* Per-card countdown: 46.77 x 39.71 chips, #F2F2F2. */
    .sqv5-flash__timer {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 4.68px;
    }
    .sqv5-flash__chip {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 6px;
        min-width: 38px;
        height: 32px;
        background: #F2F2F2;
        border-radius: 9.35366px;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 14px;
        line-height: 1.283;             /* 21 / 16.3689 */
        color: #0159ED;
    }

    /* ---------- Bottom bar: Shop now + Ends in ---------- */
    .sqv5-flash__bar {
        position: relative;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        width: 100%;
        min-height: 44px;
    }
    .sqv5-flash__cta {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10.905px;
        /* 244 x 59.98 */
        width: 170px;
        height: 44px;
        background: #FFFFFF;
        box-shadow: 0px 0px 47.7095px rgba(0, 0, 0, 0.12);
        border-radius: 76.3352px;
        text-decoration: none;
        flex: none;
        /* In the bar's flex row, so align-items centres it against the digits.
           The negative margin is what makes it overhang the panel's corner - the
           panel no longer clips, so it can cross the edge. */
        position: relative;
        z-index: 4;
        /* Phones keep it inside the padding; only desktop overhangs the corner. */
        margin-left: 0;
    }
    .sqv5-flash__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.4167;            /* 34 / 24 */
        letter-spacing: 0.681564px;
        color: var(--color-primary);
        white-space: nowrap;
    }
    .sqv5-flash__ends {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        gap: 4.38px;
    }
    .sqv5-flash__ends-label {
        /* Figma rotates the label -90deg; writing-mode does it without leaving a
           horizontal layout box behind that would trample the digits. */
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 12px;
        line-height: 1.5;
        letter-spacing: 0.729943px;
        color: #FFFFFF;
    }
    .sqv5-flash__digit {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        /* 67.15 x 64.23 */
        width: 38px;
        height: 40px;
        background: var(--color-primary);
        border: 1.45989px solid #FFFFFF;
        border-radius: 10.2192px;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 17px;
        line-height: 1.5;
        letter-spacing: 0.729943px;
        color: #FFFFFF;
    }

    .sqv5-flash__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: #FFFFFF;
        padding: 16px 0;
    }

    @media (min-width: 1024px) {
        /* Desktop keeps the original comp: the deeper corners, the badge tucked
           into the panel's bottom-left, the lightning glyph in the title, and the
           button floating over the panel - the reworked layout above is the
           phone treatment only. */
        .sqv5-flash__panel {
            border-radius: 0 200px 0 200px;
            /* Not clipped: the button has to cross the curve, and the badge sits
               beside it on the white the corner cuts away. */
            overflow: visible;
        }
        .sqv5-flash__shade { background: rgba(120, 62, 0, 0.42); }
        .sqv5-flash__badge {
            width: clamp(36px, 2.917vw, 42px);
            height: clamp(36px, 2.917vw, 42px);
        }
        /* Below-left of the button, on the panel's cut corner. */
        .sqv5-flash__badge--bottom {
            left: clamp(8px, 0.83vw, 12px);
            bottom: clamp(4px, 0.56vw, 8px);
            z-index: 3;
        }
        .sqv5-flash__badge--top { display: none; }
        .sqv5-flash__title img { display: inline-block; }
        /* Static again so the badge and the button anchor to the section. */
        .sqv5-flash__bar {
            position: static;
            justify-content: flex-end;
            padding-left: clamp(200px, 20vw, 288px);
        }
        .sqv5-flash__body {
            padding: clamp(24px, 2.222vw, 32px) clamp(24px, 3.889vw, 56px);
            gap: clamp(20px, 1.806vw, 26px);
        }
        .sqv5-flash__title {
            /* 40px / 50px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv5-flash__title img {
            width: clamp(20px, 1.944vw, 28px);
            height: clamp(20px, 1.944vw, 28px);
        }
        .sqv5-flash__col { gap: clamp(12px, 0.974vw, 14.03px); }
        .sqv5-flash__card {
            padding: clamp(10px, 0.812vw, 11.6921px);
            gap: clamp(12px, 0.974vw, 14.03px);
            border-radius: 11.6921px;
        }
        .sqv5-flash__media {
            width: clamp(110px, 9.581vw, 137.97px);
            height: clamp(110px, 9.581vw, 137.97px);
        }
        .sqv5-flash__ribbon { font-size: clamp(10px, 0.812vw, 11.6921px); }
        .sqv5-flash__name { font-size: clamp(15px, 1.2991vw, 18.7073px); }
        .sqv5-flash__price { font-size: clamp(14px, 1.1367vw, 16.3689px); }
        .sqv5-flash__old,
        .sqv5-flash__sold,
        .sqv5-flash__left { font-size: clamp(10px, 0.812vw, 11.6921px); }
        .sqv5-flash__chip {
            min-width: clamp(38px, 3.248vw, 46.77px);
            height: clamp(32px, 2.758vw, 39.71px);
            font-size: clamp(14px, 1.1367vw, 16.3689px);
        }
        .sqv5-flash__cta {
            position: absolute;
            margin-left: 0;
            left: clamp(40px, 3.4vw, 49px);
            bottom: clamp(24px, 2.3vw, 33px);
            width: clamp(170px, 16.944vw, 244px);
            height: clamp(44px, 4.165vw, 59.98px);
        }
        .sqv5-flash__cta-label { font-size: clamp(16px, 1.667vw, 24px); }
        .sqv5-flash__ends-label { font-size: clamp(12px, 1.2166vw, 17.5186px); }
        .sqv5-flash__digit {
            width: clamp(46px, 4.663vw, 67.15px);
            height: clamp(44px, 4.46vw, 64.23px);
            font-size: clamp(20px, 2.4331vw, 35.0373px);
        }
    }
</style>

<!-- ============ FLASH SALE ============ -->
<section class="sqv5-flash mx-[8px] lg:mx-[16px]" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <span class="sqv5-flash__badge sqv5-flash__badge--top">
    <img src="{{ asset('souqify-4/assets/icons/flash-lightning.svg') }}" alt="" />
  </span>

  <div class="sqv5-flash__panel">
  <img src="{{ asset('souqify-4/assets/images/flash-bg-pattern.png') }}" alt="" class="sqv5-flash__pattern" />
  <span class="sqv5-flash__shade"></span>

  <div class="sqv5-flash__body">
    <h2 class="sqv5-flash__title">
      {{ __('Flash Sale') }}
      <img src="{{ asset('souqify-4/assets/icons/flash-lightning.svg') }}" alt="" />
    </h2>

    @if ($__flashColumns->isNotEmpty())
      <div class="swiper flash-swiper sqv5-flash__row">
        <div id="flashWrapper" class="swiper-wrapper">
          @foreach ($__flashColumns as $__colIndex => $__column)
            <div class="swiper-slide sqv5-flash__col" wire:key="flashcol-v5-{{ $__colIndex }}">
              @foreach ($__column as $p)
                @php
                  // Figma draws a stock meter at ~79% (171.87 of 217.47). With no
                  // sold/stock data the design ratio stands in.
                  $__sold = $p['sold'];
                  $__left = $p['stock'];
                  $__pct = ($__sold !== null && $__left !== null && ($__sold + $__left) > 0)
                      ? round($__sold / ($__sold + $__left) * 100)
                      : 79;
                @endphp
                <div class="sqv5-flash__card" wire:key="flash-v5-{{ $p['id'] }}">
                  <a href="{{ $p['url'] }}" class="sqv5-flash__media">
                    @if ($p['image'])
                      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv5-flash__img" onerror="this.style.display='none'" />
                    @endif
                    @if ($p['discount'])
                      <span class="sqv5-flash__ribbon">{{ $p['discount'] }}</span>
                    @endif
                    <span class="sqv5-flash__cart">
                      <img src="{{ asset('souqify-4/assets/icons/flash-cart.svg') }}" alt="" />
                    </span>
                  </a>

                  <div class="sqv5-flash__info">
                    <div class="sqv5-flash__text">
                      @if ($p['brand'])
                        <span class="sqv5-flash__brand">{{ $p['brand'] }}</span>
                      @endif
                      <a href="{{ $p['url'] }}" class="sqv5-flash__name">{{ $p['name'] }}</a>
                      <div class="sqv5-flash__prices">
                        <span class="sqv5-flash__price">{{ $p['price'] }}</span>
                        @if (!empty($p['oldPrice']))
                          <span class="sqv5-flash__old">{{ $p['oldPrice'] }}</span>
                        @endif
                      </div>
                      <div class="sqv5-flash__meter">
                        <span class="sqv5-flash__meter-fill" style="width: {{ $__pct }}%"></span>
                      </div>
                      <div class="sqv5-flash__stockrow">
                        <span class="sqv5-flash__sold">{{ __('Sold') }}: {{ $__sold ?? 125 }}</span>
                        @if ($__left !== null)
                          <span class="sqv5-flash__left">{{ $__left }} {{ __('left') }}</span>
                        @endif
                      </div>
                    </div>

                    <div class="sqv5-flash__timer">
                      <span class="sqv5-flash__chip" data-flash-hours>04</span>
                      <span class="sqv5-flash__chip" data-flash-minutes>22</span>
                      <span class="sqv5-flash__chip" data-flash-seconds>57</span>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      </div>
    @else
      <p class="sqv5-flash__empty">{{ __('No flash sale products yet.') }}</p>
    @endif

    <div class="sqv5-flash__bar">
      <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv5-flash__cta">
        <span class="sqv5-flash__cta-label">{{ __('Shop now') }}</span>
      </a>
      <span class="sqv5-flash__badge sqv5-flash__badge--bottom">
        <img src="{{ asset('souqify-4/assets/icons/flash-lightning.svg') }}" alt="" />
      </span>
      <div class="sqv5-flash__ends">
        <span class="sqv5-flash__ends-label">{{ __('Ends in') }}</span>
        <span class="sqv5-flash__digit" data-flash-hours>03</span>
        <span class="sqv5-flash__digit" data-flash-minutes>06</span>
        <span class="sqv5-flash__digit" data-flash-seconds>25</span>
      </div>
    </div>
  </div>
  </div>
</section>
