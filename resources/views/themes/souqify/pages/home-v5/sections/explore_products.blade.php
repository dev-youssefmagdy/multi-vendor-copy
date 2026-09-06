@php
    // Figma shows seven tiles in the 1328px row.
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(7)->values();
    $__tileImages = [
        asset('souqify-4/assets/images/shop-footwear.png'),
        asset('souqify-4/assets/images/shop-electronics.png'),
        asset('souqify-4/assets/images/shop-accessories.png'),
    ];
@endphp

{{-- Figma: Frame 1984080218 (1440x437.46) - white, 24px 56px padding, 24px gap.
     60px centred title + the 225.46px tile row + the Explore all button.
     Each tile is the product photo over an orange 133.07 disc (the existing
     shop-blob-{1..3}.svg assets), then a 21.4983px label.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv5-shop {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv5-shop__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 22px;
        line-height: 1.5;               /* 60 / 40 */
        letter-spacing: 0.5px;
        text-align: center;
        color: #121212;
        margin: 0;
    }

    /* ---------- Carousel ---------- */
    .sqv5-shop__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    /* The tiles differ in height (203.2 - 225.46 in the comp) and sit on a shared
       baseline, so the slides align to the end rather than stretching. */
    .sqv5-shop__row .swiper-wrapper { align-items: flex-end; }
    .sqv5-shop__tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        height: auto;
        min-width: 0;
        text-decoration: none;
    }
    /* Group 59: the disc plus the photo overhanging its top. */
    .sqv5-shop__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        width: 100%;
        /* 159.03 wide x 158.22 tall */
        aspect-ratio: 159.03 / 158.22;
    }
    .sqv5-shop__blob {
        position: absolute;
        left: 50%;
        bottom: 0;
        /* Subtract: 133.07 of the 159.03 group = 83.7%. */
        width: 83.7%;
        aspect-ratio: 1;
        transform: translateX(-50%);
        object-fit: contain;
    }
    .sqv5-shop__img {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv5-shop__label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 14px;
        line-height: 1.4885;            /* 32 / 21.4983 */
        letter-spacing: 0.5px;
        text-align: center;
        color: #121212;
        margin: 0;
        max-width: 100%;
    }
    .sqv5-shop__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted);
    }

    /* ---------- Explore all ---------- */
    .sqv5-shop__cta {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px;
        width: 100%;
        max-width: 240px;
        height: 44px;
        background: transparent;
        border: 1px solid #121212;
        border-radius: 34px;
        text-decoration: none;
        cursor: pointer;
    }
    .sqv5-shop__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
    }

    @media (min-width: 1024px) {
        .sqv5-shop {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps slidesPerView from inflating the tiles on wider displays;
               max() still leaves the Figma 56px gutter at exactly 1440. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv5-shop__title {
            /* 40px / 60px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv5-shop__tile { gap: clamp(8px, 0.901vw, 12.98px); }
        .sqv5-shop__label {
            /* 21.4983px / 32px line-height / 0.67182px tracking */
            font-size: clamp(16px, 1.4929vw, 21.4983px);
            letter-spacing: clamp(0.5px, 0.0467vw, 0.67182px);
        }
        .sqv5-shop__cta {
            /* 304 x 56 */
            max-width: clamp(240px, 21.111vw, 304px);
            height: clamp(44px, 3.889vw, 56px);
        }
        .sqv5-shop__cta-label { font-size: clamp(16px, 1.389vw, 20px); }
    }
</style>

<!-- ============ SHOP BY CATEGORY ============ -->
<section class="sqv5-shop">
  <h2 class="sqv5-shop__title">{{ __('Shop by Category') }}</h2>

  @if ($__tiles->isNotEmpty())
    <div class="swiper shopcat-swiper sqv5-shop__row">
      <div id="shopByCategoryWrapper" class="swiper-wrapper">
        @foreach ($__tiles as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
          @endphp
          <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="swiper-slide sqv5-shop__tile" wire:key="shopcat-v5-{{ $category->id }}">
            <div class="sqv5-shop__art">
              <img src="{{ asset('souqify-4/assets/icons/shop-blob-' . (($index % 3) + 1) . '.svg') }}" alt="" class="sqv5-shop__blob" />
              <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv5-shop__img" />
            </div>
            <p class="sqv5-shop__label">{{ $__name }}</p>
          </a>
        @endforeach
      </div>
    </div>

    <a href="{{ route('tenant.storefront.category') }}" class="sqv5-shop__cta">
      <span class="sqv5-shop__cta-label">{{ __('Explore all') }}</span>
    </a>
  @else
    <p class="sqv5-shop__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>
