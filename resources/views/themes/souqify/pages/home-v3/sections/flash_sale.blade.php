@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;

    $__flashProducts = collect($flashProducts ?? [])->values()->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'desc' => \Illuminate\Support\Str::limit($product->translationValue('short_description') ?? '', 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            // Design-only fields with no backing data yet.
            'weight' => null,
            'sold' => '70% ' . __('Sold'),
            'badge' => '🔥 ' . __('Trending Now'),
            'delivery' => __('Delivered by 24 March'),
            'stock' => __('Only 5 left'),
            'ribbonStock' => __('Only 5 left - Hurry up'),
        ];
    });

    // Frame 1984079776 alternates one tall "Deal Card" with a column of three
    // "Mobile Card"s (Frame 1984080244). One product feeds the deal card, the next
    // three feed the column, and so on.
    $__flashSlides = [];
    $__queue = $__flashProducts->all();
    while ($__queue) {
        $__flashSlides[] = ['type' => 'deal', 'items' => [array_shift($__queue)]];
        if ($__queue) {
            $__flashSlides[] = ['type' => 'stack', 'items' => array_splice($__queue, 0, 3)];
        }
    }
@endphp

{{-- Figma: Frame 1984080446 (1440x679) - 24px 56px padding, 16px gap, #199387 with
     GREEN BACKGROUND.jpg blended soft-light. Left: the rotated "Flash Sale" title
     and countdown. Right: the carousel (Frame 1984079776, 21.48px gap) alternating
     a 319.97x511.14 Deal Card with a column of three 510.57x161.05 Mobile Cards.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv3-flash {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-souqify-teal);
        overflow: hidden;
    }
    .sqv3-flash__texture {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        mix-blend-mode: soft-light;
        pointer-events: none;
    }
    .sqv3-flash__inner {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        width: 100%;
    }
    /* Frame 1984080447: the rotated title/timer column beside the carousel. */
    .sqv3-flash__row {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        width: 100%;
        min-width: 0;
    }

    /* ---------- Title + countdown ---------- */
    .sqv3-flash__timer {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        flex: none;
    }
    .sqv3-flash__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 32px;
        line-height: 1.2589;            /* 132 / 104.851 */
        color: #FFFFFF;
        margin: 0;
        white-space: nowrap;
    }
    .sqv3-flash__clock {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
    }
    .sqv3-flash__endsin {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 12px;
        line-height: 1.5;
        letter-spacing: 1.3758px;
        color: var(--color-accent-yellow, #FFD428);
        white-space: nowrap;
    }
    .sqv3-flash__box {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 46px;
        height: 44px;
        background: var(--color-souqify-teal);
        border: 2.7516px solid #FFFFFF;
        border-radius: 19.2612px;
    }
    .sqv3-flash__digit {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 24px;
        line-height: 1.5;
        letter-spacing: 1.3758px;
        color: #FFFFFF;
    }

    /* ---------- Carousel ---------- */
    .sqv3-flash__carousel {
        /* Must match mountFlash()'s spaceBetween for the slide-width maths below. */
        --sqv3-flash-gap: 12px;
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv3-flash__carousel .swiper-wrapper {
        align-items: stretch;
    }
    /* Deal Card slide: 319.97 x 511.14. On mobile the comp puts the deal card at
       just over half the row so the stack starts mid-screen and runs off the
       right edge; the stack keeps the 510.57 : 319.97 ratio either way. */
    .sqv3-flash__slide--deal {
        width: 56%;
        height: auto;
    }
    /* Frame 1984080244: three stacked Mobile Cards, 14.32px gap. */
    .sqv3-flash__slide--stack {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        width: calc(56% * 1.5957);
        height: auto;
    }
    .sqv3-flash__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
        text-align: center;
        width: 100%;
    }

    /* ---------- Shop now ---------- */
    .sqv3-flash__cta {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px;
        width: 100%;
        max-width: 370px;
        height: 48px;
        background: #FFFFFF;
        border-radius: 34px;
        text-decoration: none;
    }
    .sqv3-flash__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.0417;            /* 25 / 24 */
        letter-spacing: 0.5px;
        color: var(--color-souqify-teal);
    }

    @media (min-width: 1024px) {
        .sqv3-flash {
            /* 24px 56px padding, 16px gap */
            padding: 24px clamp(24px, 3.889vw, 56px);
            gap: 16px;
        }
        /* Frame 1984080447: title/timer column to the left of the carousel. */
        .sqv3-flash__row {
            flex-direction: row;
            align-items: center;
            gap: 16px;
        }
        /* Frame 1984080352 is a row rotated -90deg, so on screen it reads as a
           column: the title on the left, then the digits bottom-to-top with the
           "Ends in" label under them. writing-mode gives the sideways text a
           correctly-sized box, which a rotate() transform would not. */
        .sqv3-flash__timer {
            flex-direction: row;
            align-items: center;
            gap: clamp(12px, 1.529vw, 22.01px);
            flex: none;
        }
        .sqv3-flash__title {
            /* 104.851px / 132px line-height, reading bottom-to-top */
            font-size: clamp(48px, 7.281vw, 104.851px);
            writing-mode: vertical-rl;
            transform: rotate(180deg);
        }
        .sqv3-flash__clock {
            /* DOM order is label, hours, minutes, seconds; reversing it puts the
               seconds at the top and the label at the bottom, as in the comp. */
            flex-direction: column-reverse;
            gap: clamp(8px, 0.573vw, 8.25px);
        }
        .sqv3-flash__endsin {
            /* 33.0192px */
            font-size: clamp(16px, 2.293vw, 33.0192px);
            writing-mode: vertical-rl;
        }
        .sqv3-flash__box {
            /* 126.57 x 121.07 */
            width: clamp(64px, 8.79vw, 126.57px);
            height: clamp(60px, 8.408vw, 121.07px);
        }
        .sqv3-flash__digit {
            /* 66.0385px */
            font-size: clamp(30px, 4.586vw, 66.0385px);
        }
        .sqv3-flash__carousel {
            /* Cards keep their Figma sizes; the carousel is capped instead, to
               exactly one Deal Card, the stack of three, and half of the next Deal
               Card - so the cut lands mid-card and reads as swipeable:
                   D + g + S + g + D/2
               margin-left:auto parks that block against the section's right edge,
               with the title/countdown staying at the far left. */
            --sqv3-flash-gap: 21.48px;
            --sqv3-flash-deal-w: clamp(240px, 22.22vw, 319.97px);
            --sqv3-flash-stack-w: clamp(300px, 35.456vw, 510.57px);
            max-width: calc(var(--sqv3-flash-deal-w) * 1.5
                          + var(--sqv3-flash-stack-w)
                          + var(--sqv3-flash-gap) * 2);
            margin-left: auto;
        }
        .sqv3-flash__slide--deal {
            /* 319.97 wide */
            width: var(--sqv3-flash-deal-w);
        }
        .sqv3-flash__slide--stack {
            /* 510.57 wide, 14.32px inner gap */
            width: var(--sqv3-flash-stack-w);
            gap: clamp(10px, 0.9944vw, 14.32px);
        }
        .sqv3-flash__cta {
            /* 370 x 68 */
            max-width: clamp(280px, 25.694vw, 370px);
            height: clamp(52px, 4.722vw, 68px);
        }
        .sqv3-flash__cta-label {
            font-size: clamp(18px, 1.667vw, 24px);
        }
    }
</style>

@include('themes.souqify.pages.home-v3.sections.partials.deal_card_styles')
@include('themes.souqify.pages.home-v3.sections.partials.trending_card_styles')

<!-- ============ FLASH SALE ============ -->
<section class="sqv3-flash" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <img src="{{ asset('souqify-2/assets/images/flash-sale-texture-mobile.png') }}" alt="" class="sqv3-flash__texture lg:hidden" />
  <img src="{{ asset('souqify-2/assets/images/flash-sale-texture-desktop.png') }}" alt="" class="sqv3-flash__texture hidden lg:block" />

  <div class="sqv3-flash__inner">
    <div class="sqv3-flash__row">
      <div class="sqv3-flash__timer">
        <h2 class="sqv3-flash__title">{{ __('Flash Sale') }}</h2>
        <div class="sqv3-flash__clock">
          <span class="sqv3-flash__endsin">{{ __('Ends in') }}</span>
          <span class="sqv3-flash__box"><span data-flash-hours class="sqv3-flash__digit">03</span></span>
          <span class="sqv3-flash__box"><span data-flash-minutes class="sqv3-flash__digit">06</span></span>
          <span class="sqv3-flash__box"><span data-flash-seconds class="sqv3-flash__digit">25</span></span>
        </div>
      </div>

      @if (!empty($__flashSlides))
        <div class="swiper flash-swiper sqv3-flash__carousel">
          <div id="flashSaleWrapper" class="swiper-wrapper">
            @foreach ($__flashSlides as $__slideIndex => $__slide)
              @if ($__slide['type'] === 'deal')
                <div class="swiper-slide sqv3-flash__slide--deal" wire:key="flash-v3-slide-{{ $__slideIndex }}">
                  @include('themes.souqify.pages.home-v3.sections.partials.deal_card', ['p' => array_merge($__slide['items'][0], ['stock' => $__slide['items'][0]['ribbonStock']])])
                </div>
              @else
                <div class="swiper-slide sqv3-flash__slide--stack" wire:key="flash-v3-slide-{{ $__slideIndex }}">
                  @foreach ($__slide['items'] as $p)
                    @include('themes.souqify.pages.home-v3.sections.partials.trending_card', ['p' => $p])
                  @endforeach
                </div>
              @endif
            @endforeach
          </div>
        </div>
      @else
        <p class="sqv3-flash__empty">{{ __('Flash deals coming soon') }}</p>
      @endif
    </div>

    @if (!empty($__flashSlides))
      <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv3-flash__cta">
        <span class="sqv3-flash__cta-label">{{ __('Shop now') }}</span>
      </a>
    @endif
  </div>
</section>
