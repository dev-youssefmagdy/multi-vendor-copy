@php
    // The comp shows six tiles (its three categories repeated); take six real ones.
    $__tiles = ($categories ?? $rootCategories ?? collect())->take(6)->values();
    $__tileImages = [
        asset('souqify-2/assets/images/shop-cat-accessories.jpg'),
        asset('souqify-2/assets/images/shop-cat-fashion.jpg'),
        asset('souqify-2/assets/images/shop-cat-electronics.jpg'),
    ];
@endphp

{{-- Figma: Frame 1984080218 (1440x495.94) - white, 24px 56px padding, 24px gap.
     60px title + 283.94px tile row + the Explore all button.
     Tile: a photo with a teal wedge ("Vector 2", 174.51 of the 283.94 height)
     across the bottom, the label over it, and the white petal cluster
     ("Repeat group 1", 61.84 square, rotated 23.64deg) at the lower right.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv3-shop {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv3-shop__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.5;               /* 60 / 40 */
        letter-spacing: 0.5px;
        text-align: center;
        color: #121212;
        margin: 0;
    }

    /* ---------- Carousel ---------- */
    .sqv3-shop__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv3-shop__row .swiper-wrapper {
        align-items: stretch;
    }
    /* Tile width comes from Swiper's slidesPerView (5.5 on desktop). */
    .sqv3-shop__slide {
        height: auto;
    }
    .sqv3-shop__tile {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        padding: 8.4007px 0;
        /* 199.94 x 283.94 */
        aspect-ratio: 199.94 / 283.94;
        width: 100%;
        border-radius: 13.4411px;
        overflow: hidden;
        isolation: isolate;
        text-decoration: none;
    }
    .sqv3-shop__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
    }
    /* Vector 2: the teal wedge. 174.51 / 283.94 = 61.46% of the tile height, with a
       straight diagonal top edge rising from left to right. */
    .sqv3-shop__wedge {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 61.46%;
        background: var(--color-souqify-teal);
        clip-path: polygon(0 55%, 100% 0, 100% 100%, 0 100%);
        z-index: 1;
    }
    /* Repeat group 1: 61.84 square at left 116.75 / top 144.07 of the tile,
       rotated 23.64deg. */
    .sqv3-shop__petals {
        position: absolute;
        left: 58.39%;                   /* 116.75 / 199.94 */
        top: 50.74%;                    /* 144.07 / 283.94 */
        width: 30.93%;                  /* 61.84 / 199.94 */
        aspect-ratio: 1;
        transform: rotate(23.64deg);
        background: url('{{ asset('souqify-3/assets/icons/cat-leaves-white.svg') }}') center / contain no-repeat;
        z-index: 2;
        pointer-events: none;
    }
    .sqv3-shop__label {
        position: relative;
        z-index: 3;
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.5;               /* 40 / 26.8822 */
        letter-spacing: 0.84007px;
        color: #FFFFFF;
        text-align: center;
        max-width: 100%;
        padding: 0 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv3-shop__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted);
    }

    /* ---------- Explore all ---------- */
    .sqv3-shop__cta {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px;
        width: 100%;
        max-width: 304px;
        height: 44px;
        border: 1px solid #121212;
        border-radius: 34px;
        text-decoration: none;
    }
    .sqv3-shop__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
    }

    @media (min-width: 1024px) {
        .sqv3-shop {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps slidesPerView from inflating the tiles on wider displays;
               max() still leaves the Figma 56px gutter at exactly 1440. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-shop__title {
            /* 40px / 60px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv3-shop__label {
            /* 26.8822px */
            font-size: clamp(18px, 1.8668vw, 26.8822px);
        }
        .sqv3-shop__cta {
            /* 304 x 56 */
            max-width: clamp(240px, 21.111vw, 304px);
            height: clamp(44px, 3.889vw, 56px);
        }
        .sqv3-shop__cta-label {
            font-size: clamp(16px, 1.389vw, 20px);
        }
    }
</style>

<!-- ============ SHOP BY CATEGORY ============ -->
<section class="sqv3-shop">
  <h2 class="sqv3-shop__title">{{ __('Shop by Category') }}</h2>

  @if ($__tiles->isNotEmpty())
    <div class="swiper shopcat-swiper sqv3-shop__row">
      <div id="shopByCategoryWrapper" class="swiper-wrapper">
        @foreach ($__tiles as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
          @endphp
          <div class="swiper-slide sqv3-shop__slide" wire:key="shopcat-v3-{{ $category->id }}">
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="sqv3-shop__tile">
              <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv3-shop__img" />
              <span class="sqv3-shop__wedge"></span>
              <span class="sqv3-shop__petals"></span>
              <span class="sqv3-shop__label">{{ $__name }}</span>
            </a>
          </div>
        @endforeach
      </div>
    </div>

    <a href="{{ route('tenant.storefront.category') }}" class="sqv3-shop__cta">
      <span class="sqv3-shop__cta-label">{{ __('Explore all') }}</span>
    </a>
  @else
    <p class="sqv3-shop__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>
