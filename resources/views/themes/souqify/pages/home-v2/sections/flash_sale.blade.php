@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;
    $__flashCards = collect($flashProducts ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the row.
        $chip = [
            ['bg' => '#FFB00A', 'color' => '#121212'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
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

{{-- Figma: Frame 1984080342 (1440 x 616.86) - a full-bleed #8B03BD panel,
     32px 56px padding, 24px gap: a 50px header, the 386.86px card row
     (18.12px between cards) and the 68px countdown + Shop now bar.
     The row measures 1540.12 against the 1328 the padding leaves, so it runs as
     a carousel: five whole cards and a sliver of the sixth.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-flash {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        overflow: hidden;
        /* Broad bands on a 45deg diagonal - lying open across the panel rather
           than the shared near-vertical hairlines. */
        background-color: var(--color-flash-sale-bg);
        background-image: repeating-linear-gradient(
            135deg,
            rgba(139, 3, 189, 0) 0px,
            rgba(139, 3, 189, 0) 46px,
            rgba(174, 1, 237, 0.55) 47px,
            rgba(174, 1, 237, 0.55) 74px
        );
    }
    /* The stripes come from the shared .stripe-bg-dark rule, untouched. This
       layer only paints the plain background back over the top-right and
       bottom-left corners so they read empty, per the comp. */
    .sqv2-flash__clear {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background-image:
            radial-gradient(120% 150% at 100% 0%, var(--color-flash-sale-bg) 0%, var(--color-flash-sale-bg) 26%, transparent 46%),
            radial-gradient(120% 150% at 0% 100%, var(--color-flash-sale-bg) 0%, var(--color-flash-sale-bg) 26%, transparent 46%);
    }
    .sqv2-flash > *:not(.sqv2-flash__clear) { position: relative; z-index: 1; }

    /* Frame 1984080017 */
    .sqv2-flash__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv2-flash__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv2-flash__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984079776: the card row. */
    .sqv2-flash__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv2-flash__row .swiper-wrapper { align-items: stretch; }
    /* Widths are set in CSS, not left to Swiper: the v2 bundle may mount this
       wrapper with slidesPerView:"auto" before the mount below runs, and an
       auto slide with no width blows the card up to the full row. */
    .sqv2-flash__slide {
        height: auto;
        /* Phones show two whole cards and a sliver of the third. */
        width: calc((100% - 2 * 12px) / 2.25);
    }

    /* Frame 1984080258: the countdown next to the Shop now pill. */
    .sqv2-flash__bar {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        width: 100%;
    }
    .sqv2-flash__ends {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 4.64px;
    }
    .sqv2-flash__ends-label {
        /* Figma rotates the label -90deg; writing-mode does it without leaving a
           horizontal layout box behind that would trample the digits. */
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 13px;
        line-height: 1.5;
        letter-spacing: 0.772727px;
        color: var(--color-accent-yellow, #FFD428);
    }
    /* Frame 1984080245: 71.09 x 68, a 1.54545px white stroke, radius 10.8182. */
    .sqv2-flash__digit {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 46px;
        height: 44px;
        background: var(--color-brand-purple, #8B03BD);
        border: 1.54545px solid #FFFFFF;
        border-radius: 10.8182px;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 24px;
        line-height: 1.5;
        letter-spacing: 0.772727px;
        color: #FFFFFF;
    }
    /* Frame 1984080196: the white pill fills the rest of the bar. */
    .sqv2-flash__cta {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px;
        gap: 8px;
        flex: 1 1 auto;
        min-width: 0;
        height: 44px;
        background: #FFFFFF;
        border-radius: 34px;
        text-decoration: none;
    }
    .sqv2-flash__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.0417;            /* 25 / 24 */
        letter-spacing: 0.5px;
        color: var(--color-brand-purple, #8B03BD);
        white-space: nowrap;
    }
    .sqv2-flash__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-flash__slide { width: calc((100% - 2 * 16px) / 2.5); }
    }

    @media (min-width: 1024px) {
        .sqv2-flash {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: clamp(24px, 2.222vw, 32px) max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv2-flash__title { font-size: clamp(32px, 2.778vw, 40px); }
        .sqv2-flash__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        /* Five whole cards plus a sliver of the sixth, 18.12px apart. */
        .sqv2-flash__slide { width: calc((100% - 5 * 18.12px) / 5.35); }
        .sqv2-flash__ends { gap: clamp(4px, 0.322vw, 4.64px); }
        .sqv2-flash__ends-label { font-size: clamp(14px, 1.288vw, 18.5455px); }
        .sqv2-flash__digit {
            /* 71.09 x 68 */
            width: clamp(56px, 4.937vw, 71.09px);
            height: clamp(54px, 4.722vw, 68px);
            font-size: clamp(28px, 2.576vw, 37.0909px);
        }
        .sqv2-flash__cta { height: clamp(54px, 4.722vw, 68px); }
        .sqv2-flash__cta-label { font-size: clamp(18px, 1.667vw, 24px); }
    }
</style>

<!-- ============ FLASH SALE ============ -->
<section class="sqv2-flash" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <span class="sqv2-flash__clear" aria-hidden="true"></span>

  <div class="sqv2-flash__head">
    <h2 class="sqv2-flash__title">{{ __('Flash Sale') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv2-flash__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__flashCards->isNotEmpty())
    <div class="swiper flash-swiper sqv2-flash__row">
      <div class="swiper-wrapper" id="flashSaleWrapper">
        @foreach ($__flashCards as $p)
          <div class="swiper-slide sqv2-flash__slide" wire:key="flash-v2-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v2.sections.partials.purple_deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>

    <div class="sqv2-flash__bar">
      <div class="sqv2-flash__ends">
        <span class="sqv2-flash__ends-label">{{ __('Ends in') }}</span>
        <span class="sqv2-flash__digit" data-flash-timer="h">03</span>
        <span class="sqv2-flash__digit" data-flash-timer="m">06</span>
        <span class="sqv2-flash__digit" data-flash-timer="s">25</span>
      </div>
      <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="sqv2-flash__cta">
        <span class="sqv2-flash__cta-label">{{ __('Shop now') }}</span>
      </a>
    </div>
  @else
    <p class="sqv2-flash__empty">{{ __('Flash deals coming soon') }}</p>
  @endif
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountFlashV2() {
    var el = document.querySelector('.flash-swiper');
    if (!el || typeof Swiper === 'undefined') return;
    if (el.swiper) {
      if (el.swiper.__sqv2Flash) return;
      el.swiper.destroy(true, true);
    }

    var sw = new Swiper(el, {
      // Two whole cards plus a sliver of the third.
      slidesPerView: 2.25,
      spaceBetween: 12,
      breakpoints: {
        640: { slidesPerView: 2.5, spaceBetween: 16 },
        // Five whole cards and a sliver of the sixth, 18.12px apart.
        1024: { slidesPerView: 5.35, spaceBetween: 18.12 },
      },
    });
    sw.__sqv2Flash = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountFlashV2);
  } else {
    mountFlashV2();
  }
  window.addEventListener('load', mountFlashV2);
})();
</script>
