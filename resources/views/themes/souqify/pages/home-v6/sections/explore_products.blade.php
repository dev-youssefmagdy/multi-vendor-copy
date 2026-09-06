@php
    // Figma repeats six tiles across the 1328px row.
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(6)->values();
    $__tileImages = [
        asset('souqify-5/assets/images/shop-mens.png'),
        asset('souqify-5/assets/images/shop-electronics.png'),
        asset('souqify-5/assets/images/shop-accessories.png'),
    ];
@endphp

{{-- Figma: Frame 1984080218 (1440 x 415.97) - white, 24px 56px padding, 24px
     gap: a 72px outlined title, the 191.97px tile row (16px gaps) and the
     Explore all button. Each tile is the product photo over a 96.16 x 104.74
     pink arch, then a 22.7483px label.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv6-shop {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-page-bg, #FFFFFF);
    }
    /* Outlined title: 48px / 150% line-height, weight 700, centred, with the
       comp's 1px #FF1A90 stroke and no fill. Stretches the full row width. */
    .sqv6-shop__title {
        width: 100%;
        font-family: 'Outfit', sans-serif;
        font-style: normal;
        font-weight: 700;
        font-size: 28px;
        line-height: 150%;
        text-align: center;
        color: transparent;
        -webkit-text-stroke: 1px var(--color-brand-pink, #FF1A90);
        margin: 0;
    }

    /* ---------- Tile carousel ---------- */
    .sqv6-shop__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    /* Tops aligned: the labels wrap to different line counts, so an end-aligned
       row would step the artwork up and down. */
    .sqv6-shop__row .swiper-wrapper { align-items: flex-start; }
    .sqv6-shop__tile {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        gap: 6px;
        height: auto;
        min-width: 0;
        text-decoration: none;
    }
    /* Group 64: the pink arch with the photo overhanging it. */
    .sqv6-shop__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        width: 100%;
        /* 168.28 wide x 157.97 tall */
        aspect-ratio: 168.28 / 157.97;
    }
    /* Rectangle 5979: 96.16 x 104.74, rounded at the top only. */
    .sqv6-shop__arch {
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        /* 96.16 / 168.28 */
        width: 57.14%;
        /* 104.74 / 157.97 */
        height: 66.3%;
        background: var(--color-brand-pink, #FF1A90);
        border-radius: 999px 999px 0 0;
    }
    .sqv6-shop__img {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv6-shop__label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 14px;
        line-height: 1.4945;            /* 34 / 22.7483 */
        letter-spacing: 0.5px;
        text-align: center;
        color: var(--color-brand-pink, #FF1A90);
        margin: 0;
        max-width: 100%;
    }
    .sqv6-shop__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    /* ---------- Explore all: 304 x 56, radius 34 ---------- */
    .sqv6-shop__cta {
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
    .sqv6-shop__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
    }

    @media (min-width: 1024px) {
        .sqv6-shop {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-shop__title {
            /* 48px on the 1440 canvas; the stroke stays 1px as in the comp. */
            font-size: clamp(32px, 3.333vw, 48px);
        }
        .sqv6-shop__tile { gap: clamp(6px, 0.954vw, 13.74px); }
        .sqv6-shop__label {
            /* 22.7483px / 34px line-height / 0.710886px tracking */
            font-size: clamp(16px, 1.5798vw, 22.7483px);
            letter-spacing: clamp(0.5px, 0.0494vw, 0.710886px);
        }
        .sqv6-shop__cta {
            max-width: clamp(240px, 21.111vw, 304px);
            height: clamp(44px, 3.889vw, 56px);
        }
        .sqv6-shop__cta-label { font-size: clamp(16px, 1.389vw, 20px); }
    }
</style>

<!-- ============ SHOP BY CATEGORY ============ -->
<section class="sqv6-shop">
  <h2 class="sqv6-shop__title">{{ __('Shop by Category') }}</h2>

  @if ($__tiles->isNotEmpty())
    <div class="swiper shopcat-swiper sqv6-shop__row">
      <div id="shopByCategoryWrapper" class="swiper-wrapper">
        @foreach ($__tiles as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
          @endphp
          <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="swiper-slide sqv6-shop__tile" wire:key="shopcat-v6-{{ $category->id }}">
            <div class="sqv6-shop__art">
              <span class="sqv6-shop__arch" aria-hidden="true"></span>
              <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv6-shop__img" />
            </div>
            <p class="sqv6-shop__label">{{ $__name }}</p>
          </a>
        @endforeach
      </div>
    </div>

    <a href="{{ route('tenant.storefront.category') }}" class="sqv6-shop__cta">
      <span class="sqv6-shop__cta-label">{{ __('Explore all') }}</span>
    </a>
  @else
    <p class="sqv6-shop__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>
