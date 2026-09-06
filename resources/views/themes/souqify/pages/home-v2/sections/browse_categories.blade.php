@php
    $__categories = collect($categories ?? $rootCategories ?? [])->values();
    $__tileImages = [
        asset('souqify-1/assets/images/cat-women-bags.png'),
        asset('souqify-1/assets/images/cat-accessories-lamp.png'),
        asset('souqify-1/assets/images/cat-gaming-controller.png'),
        asset('souqify-1/assets/images/cat-electronics-laptop.png'),
    ];
@endphp

{{-- Figma: Frame 1984080440 (1347 wide) - a 40px header over the 146.23px tile
     row, 24px apart. Each tile is 160.63 wide: the product cut-out over an
     83.53 x 74.96 purple arch (radius 0/55.6844/0/0), then a 24.6296px label.
     Seven tiles fill the 1330px row at a 34.27px gap, so the row runs as a
     carousel at 7 per view.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-cats {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 16px;
        gap: 16px;
        background: var(--color-page-bg, #FFFFFF);
    }
    /* Frame 1984080017 */
    .sqv2-cats__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv2-cats__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 20px;
        line-height: 1.25;              /* 40 / 32 */
        color: #000000;
        margin: 0;
    }
    .sqv2-cats__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-accent-purple, #8A38F5);
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984080340: the tile row. */
    .sqv2-cats__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    /* Tops aligned: the labels wrap to different line counts, so an end-aligned
       row would step the artwork up and down. */
    .sqv2-cats__row .swiper-wrapper { align-items: flex-start; }
    .sqv2-cats__tile {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        gap: 4px;
        height: auto;
        min-width: 0;
        text-decoration: none;
    }
    /* Group 39: the purple arch with the cut-out overhanging it. */
    .sqv2-cats__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        width: 100%;
        /* 134.93 wide x 100.66 tall - the widest tile artwork in the comp. */
        aspect-ratio: 134.93 / 100.66;
    }
    /* Rectangle 5963: 83.53 x 74.96, rounded at the top-right only. */
    .sqv2-cats__arch {
        position: absolute;
        left: 52%;
        bottom: 0;
        transform: translateX(-50%);
        /* 83.53 / 134.93 */
        width: 61.91%;
        /* 74.96 / 100.66 */
        height: 74.47%;
        background: var(--color-brand-purple, #8B03BD);
        border-radius: 0px 999px 0px 0px;
    }
    .sqv2-cats__img {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv2-cats__label {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 10px;
        line-height: 1.4;
        letter-spacing: 0.2px;
        text-align: center;
        color: var(--color-brand-purple, #8B03BD);
        margin: 0;
        max-width: 100%;
    }
    .sqv2-cats__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 16px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    /* Widths are set in CSS, not left to Swiper: the v2 bundle may mount a
       wrapper with slidesPerView:"auto" before the mount below runs, and an
       auto slide with no width blows the tile up to the full row. */
    /* Phones fit four whole tiles across the row. */
    .sqv2-cats__tile { width: calc((100% - 3 * 12px) / 4); }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-cats__tile { width: calc((100% - 4 * 24px) / 5); }
    }

    @media (min-width: 1024px) {
        .sqv2-cats {
            /* The comp's row is 1347px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.229vw, 46.5px), calc((100% - 1347px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv2-cats__title { font-size: clamp(26px, 2.222vw, 32px); }
        .sqv2-cats__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        .sqv2-cats__tile {
            /* Seven tiles across the 1330px row at a 34.27px gap. */
            width: calc((100% - 6 * 34.27px) / 7);
            gap: clamp(6px, 0.595vw, 8.57px);
        }
        .sqv2-cats__label {
            /* 24.6296px / 37px line-height / 1.07085px tracking */
            font-size: clamp(16px, 1.7104vw, 24.6296px);
            letter-spacing: clamp(0.5px, 0.0744vw, 1.07085px);
        }
    }
</style>

<!-- ============ CATEGORIES ============ -->
<section class="sqv2-cats">
  <div class="sqv2-cats__head">
    <h2 class="sqv2-cats__title">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv2-cats__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__categories->isNotEmpty())
    <div class="swiper categories-swiper sqv2-cats__row">
      <div class="swiper-wrapper" id="categoriesWrapper">
        @foreach ($__categories as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15);
          @endphp
          <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="swiper-slide sqv2-cats__tile" wire:key="category-v2-{{ $category->id }}">
            <div class="sqv2-cats__art">
              <span class="sqv2-cats__arch" aria-hidden="true"></span>
              <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv2-cats__img" />
            </div>
            <p class="sqv2-cats__label">{{ $__name }}</p>
          </a>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv2-cats__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountCategoriesV2() {
    var el = document.querySelector('.categories-swiper');
    if (!el || typeof Swiper === 'undefined') return;
    if (el.swiper) {
      if (el.swiper.__sqv2Categories) return;
      el.swiper.destroy(true, true);
    }

    var sw = new Swiper(el, {
      // Phones fit four whole tiles; a swipe brings the next four.
      slidesPerView: 4,
      slidesPerGroup: 4,
      spaceBetween: 12,
      breakpoints: {
        640: { slidesPerView: 5, slidesPerGroup: 5, spaceBetween: 24 },
        // Seven tiles fill the comp's row; a swipe brings the next seven.
        1024: { slidesPerView: 7, slidesPerGroup: 7, spaceBetween: 34.27 },
      },
    });
    sw.__sqv2Categories = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountCategoriesV2);
  } else {
    mountCategoriesV2();
  }
  window.addEventListener('load', mountCategoriesV2);
})();
</script>
