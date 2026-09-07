@php
    $__categories = collect($categories ?? $rootCategories ?? [])->values();
    $__tileImages = [
        asset('souqify-1/assets/images/shopcat-accessories.png'),
        asset('souqify-1/assets/images/shopcat-fashion.png'),
        asset('souqify-1/assets/images/shopcat-electronics.png'),
    ];

    // Figma alternates a full-height tile with a column of two half-height
    // tiles, so the categories are grouped 1, 2, 1, 2 ... across the row.
    $__groups = [];
    $__buffer = $__categories->all();
    $__i = 0;
    while (!empty($__buffer)) {
        $__take = $__i % 2 === 0 ? 1 : 2;
        $__groups[] = array_splice($__buffer, 0, $__take);
        $__i++;
    }
@endphp

{{-- Figma: Frame 1984080218 (1440 x 539.52) - white, 24px 56px padding, 24px
     gap: a 60px centred title, the 327.52px tile row (16.58px apart) and the
     Explore all button. Every tile is a 11.0554px-radius photo with a purple
     label plate across its bottom-left (radius 0/35.93/0/11.0554).
     The row holds four slides, so it runs as a carousel at 4 per view.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-shop {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv2-shop__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 22px;
        line-height: 1.4;               /* 60 / 40 */
        letter-spacing: 0.5px;
        text-align: center;
        color: #121212;
        margin: 0;
    }

    /* Frame 1984080364: the tile row. */
    .sqv2-shop__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv2-shop__row .swiper-wrapper { align-items: stretch; }
    /* Widths are set in CSS, not left to Swiper: the v2 bundle may mount a
       wrapper with slidesPerView:"auto" first, and an auto slide with no width
       blows the tile up to the full row. */
    .sqv2-shop__slide {
        display: flex;
        flex-direction: column;
        gap: 12px;
        /* 327.52 tall against the 319.56 tile width. */
        aspect-ratio: 319.56 / 327.52;
        height: auto;
        /* Phones fit both groups: the tall tile beside the stacked pair. */
        width: calc((100% - 12px) / 2);
    }
    .sqv2-shop__tile {
        position: relative;
        display: block;
        flex: 1 1 0;
        min-height: 0;
        border-radius: 11.0554px;
        overflow: hidden;
        text-decoration: none;
    }
    .sqv2-shop__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Rectangle 5963: the purple plate along the bottom-left of the tile. */
    .sqv2-shop__label {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        max-width: 92%;
        padding: 4px 14px 4px 8px;
        background: var(--color-brand-purple, #8B03BD);
        border-radius: 0px 35.93px 0px 11.0554px;
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 1.5;               /* 33 / 22.1108 */
        letter-spacing: 0.35px;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv2-shop__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    /* Frame 1984080196: 304 x 56, a 1px #121212 stroke, radius 34. */
    .sqv2-shop__cta {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px;
        gap: 8px;
        width: 100%;
        max-width: 240px;
        height: 44px;
        background: transparent;
        border: 1px solid #121212;
        border-radius: 34px;
        text-decoration: none;
    }
    .sqv2-shop__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-shop__slide { width: calc((100% - 1 * 16px) / 2.4); }
    }

    @media (min-width: 1024px) {
        .sqv2-shop {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv2-shop__title { font-size: clamp(28px, 2.778vw, 40px); }
        /* Four slides fill the comp's row at a 16.58px gap. */
        .sqv2-shop__slide { width: calc((100% - 3 * 16.58px) / 4); }
        .sqv2-shop__label {
            /* 22.1108px / 33px line-height */
            font-size: clamp(18px, 1.5355vw, 22.1108px);
            padding: clamp(6px, 0.6vw, 8px) clamp(18px, 2.2vw, 32px) clamp(6px, 0.6vw, 8px) clamp(12px, 1.2vw, 17px);
        }
        .sqv2-shop__cta {
            max-width: clamp(240px, 21.111vw, 304px);
            height: clamp(44px, 3.889vw, 56px);
        }
        .sqv2-shop__cta-label { font-size: clamp(16px, 1.389vw, 20px); }
    }
</style>

<!-- ============ SHOP BY CATEGORY ============ -->
<section class="sqv2-shop">
  <h2 class="sqv2-shop__title">{{ __('Shop by Category') }}</h2>

  @if ($__categories->isNotEmpty())
    <div class="swiper shopcat-swiper sqv2-shop__row">
      <div class="swiper-wrapper" id="shopByCategoryWrapper">
        @foreach ($__groups as $__groupIndex => $__group)
          <div class="swiper-slide sqv2-shop__slide" wire:key="shopcat-v2-group-{{ $__groupIndex }}">
            @foreach ($__group as $__offset => $category)
              @php
                $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[($__groupIndex + $__offset) % count($__tileImages)];
                $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
              @endphp
              <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="sqv2-shop__tile" wire:key="shopcat-v2-{{ $category->id }}">
                <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv2-shop__img" />
                <span class="sqv2-shop__label">{{ $__name }}</span>
              </a>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>

    <a href="{{ route('tenant.storefront.category') }}" class="sqv2-shop__cta">
      <span class="sqv2-shop__cta-label">{{ __('Explore all') }}</span>
    </a>
  @else
    <p class="sqv2-shop__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountShopCatV2() {
    var el = document.querySelector('.shopcat-swiper');
    if (!el || typeof Swiper === 'undefined') return;
    if (el.swiper) {
      if (el.swiper.__sqv2ShopCat) return;
      el.swiper.destroy(true, true);
    }

    var sw = new Swiper(el, {
      // Both groups fill the phone row; a swipe brings the next two.
      slidesPerView: 2,
      slidesPerGroup: 2,
      spaceBetween: 12,
      breakpoints: {
        640: { slidesPerView: 2.4, spaceBetween: 16 },
        // Four slides fill the comp's 1328px row at a 16.58px gap.
        1024: { slidesPerView: 4, slidesPerGroup: 4, spaceBetween: 16.58 },
      },
    });
    sw.__sqv2ShopCat = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountShopCatV2);
  } else {
    mountShopCatV2();
  }
  window.addEventListener('load', mountShopCatV2);
})();
</script>
