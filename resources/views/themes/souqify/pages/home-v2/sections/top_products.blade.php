@php
    $__bestSellerCards = collect($bestSelling ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the row.
        $chip = [
            ['bg' => '#FFB00A', 'color' => '#121212'],
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ][$index % 3];

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'tag' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
            'sold' => __('70% Sold'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'subtitle' => $product->centralProduct?->category?->name ?? '',
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'delivery' => __('Delivered by 24 March'),
        ];
    });

    // Figma alternates one Deal Card with a column of three Mobile Cards, so the
    // products are grouped 1, 3, 1, 3 ... across the row.
    $__bestGroups = [];
    $__buffer = $__bestSellerCards->all();
    $__i = 0;
    while (!empty($__buffer)) {
        $__take = $__i % 2 === 0 ? 1 : 3;
        $__bestGroups[] = ['type' => $__i % 2 === 0 ? 'deal' : 'mobile', 'items' => array_splice($__buffer, 0, $__take)];
        $__i++;
    }
@endphp

{{-- Figma: Frame 1984080241 (1440 x 556.96) - the purple mesh panel, 24px 56px
     padding, 24px gap: a 50px header, the 402.96px row (16.9px apart) and the
     dot row. The row alternates a 251.7px Deal Card with a 401.64px column of
     three Mobile Cards, so it runs as a carousel over those groups.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-best {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        /* One flat pastel sweep, tilted so pink/magenta holds the top-left and
           the light blue lands as an even band along the whole bottom edge
           rather than only the bottom-right corner. */
        background: linear-gradient(155deg,
            #f07ad4 0%,
            #f28ed9 12%,
            #f2a2de 24%,
            #eab2e6 38%,
            #d9b8ec 52%,
            #c4bcf0 64%,
            #b0bef1 76%,
            #a3c0f0 88%,
            #9ec4f2 100%);
    }
    /* Frame 1984080017 */
    .sqv2-best__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv2-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #121212;
        margin: 0;
    }
    .sqv2-best__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984079776: the card row. */
    .sqv2-best__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv2-best__row .swiper-wrapper { align-items: stretch; }
    /* Widths are set in CSS, not left to Swiper: the v2 bundle may mount this
       wrapper with slidesPerView:"auto" before the mount below runs, and an
       auto slide with no width blows the card up to the full row. */
    .sqv2-best__slide { height: auto; }
    /* Phones show the Deal Card whole and about half of the Mobile column. */
    .sqv2-best__slide--deal { width: 50%; }
    .sqv2-best__slide--mobile { width: 88%; }
    /* Frame 1984080244: three Mobile Cards, 11.27px apart. */
    .sqv2-best__col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 11.27px;
        height: 100%;
    }
    .sqv2-best__col > * { width: 100%; }

    /* Frame 1984080197: the dot row. */
    .sqv2-best__dots {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: flex-end;
        gap: 3px;
        width: 100%;
        height: 8px;
    }
    .sqv2-best__dot {
        cursor: pointer;
        width: 8px;
        height: 8px;
        background: #F0F0F0;
        border-radius: 29px;
    }
    .sqv2-best__dot.is-active {
        width: 24px;
        background: #FFDB4C;
    }
    .sqv2-best__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.85);
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-best__slide--deal { width: 32%; }
        .sqv2-best__slide--mobile { width: 52%; }
    }

    @media (min-width: 1024px) {
        .sqv2-best {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv2-best__title { font-size: clamp(32px, 2.778vw, 40px); }
        .sqv2-best__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        /* Two Deal Cards and two Mobile columns fill the row exactly - the
           251.7 : 401.64 ratio, scaled so nothing of a fifth group shows. */
        .sqv2-best__slide--deal { width: calc((100% - 3 * 16.9px) * 0.19262); }
        .sqv2-best__slide--mobile { width: calc((100% - 3 * 16.9px) * 0.30738); }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv2-best">
  <div class="sqv2-best__head">
    <h2 class="sqv2-best__title">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv2-best__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__bestSellerCards->isNotEmpty())
    <div class="swiper card-swiper sqv2-best__row">
      <div class="swiper-wrapper" id="bestSellerWrapper">
        @foreach ($__bestGroups as $__groupIndex => $__group)
          <div class="swiper-slide sqv2-best__slide sqv2-best__slide--{{ $__group['type'] }}" wire:key="best-seller-v2-group-{{ $__groupIndex }}">
            @if ($__group['type'] === 'deal')
              @foreach ($__group['items'] as $p)
                @include('themes.souqify.pages.home-v2.sections.partials.purple_deal_card', ['p' => $p])
              @endforeach
            @else
              <div class="sqv2-best__col">
                @foreach ($__group['items'] as $p)
                  <div wire:key="best-seller-v2-{{ $p['id'] }}">
                    @include('themes.souqify.pages.home-v2.sections.partials.purple_mobile_card', ['p' => $p])
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    {{-- Swiper fills this with one bullet per page and keeps the active one
         in step with the row. --}}
    <div class="sqv2-best__dots" id="bestSellerDots"></div>
  @else
    <p class="sqv2-best__empty">{{ __('No best sellers yet.') }}</p>
  @endif
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountBestSellerV2() {
    var el = document.querySelector('.sqv2-best__row');
    if (!el || typeof Swiper === 'undefined') return;
    if (el.swiper) {
      if (el.swiper.__sqv2Best) return;
      el.swiper.destroy(true, true);
    }

    // Slide widths differ per group, so the row runs on auto width against the
    // CSS widths above.
    var sw = new Swiper(el, {
      slidesPerView: 'auto',
      spaceBetween: 12,
      pagination: {
        el: '#bestSellerDots',
        clickable: true,
        bulletClass: 'sqv2-best__dot',
        bulletActiveClass: 'is-active',
      },
      breakpoints: {
        640: { spaceBetween: 16 },
        // Four groups fill the row; a swipe brings the next four.
        1024: { spaceBetween: 16.9, slidesPerGroup: 4 },
      },
    });
    sw.__sqv2Best = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountBestSellerV2);
  } else {
    mountBestSellerV2();
  }
  window.addEventListener('load', mountBestSellerV2);
})();
</script>
