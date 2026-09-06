@php
    // Figma shows 9 tiles; take as many real categories as exist.
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(9)->values();
    $__tileImages = [
        asset('souqify-3/assets/images/shop-accessories.png'),
        asset('souqify-3/assets/images/shop-electronics.jpg'),
        asset('souqify-3/assets/images/shop-mens.png'),
    ];
@endphp

{{-- Figma: Frame 1984080218 (1440x457.08) - column, centred, 24px 56px padding,
     24px gap, #FFFFFF. Tiles are 175.53x245.08 on a conic gradient with the
     category cut-out on top, 19.87px apart. --}}
<style>
    .sqv4-shopcat {
        --sqv4-flash-pad: max(16px, calc((100% - 1440px) / 2 + 16px));
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 24px var(--sqv4-flash-pad);
        gap: 16px;
        background: #FFFFFF;
    }
    /* Mobile comp: title on the left, a green "Explore all" link on the right,
       and no button underneath. Desktop centres the title and moves the action
       into the pill CTA at the bottom. */
    .sqv4-shopcat__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv4-shopcat__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 20px;
        line-height: 1.5;               /* 60 / 40 */
        letter-spacing: 0.5px;
        color: #121212;
        margin: 0;
        text-align: left;
    }
    .sqv4-shopcat__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 13px;
        line-height: 1.25;
        letter-spacing: 0.5px;
        color: #5A9B00;
        white-space: nowrap;
        text-decoration: none;
    }
    /* The pill CTA belongs to the desktop comp only. */
    .sqv4-shopcat__cta { display: none; }

    .sqv4-shopcat__row {
        width: 100%;
        overflow: hidden;
    }
    .sqv4-shopcat__row .swiper-wrapper { align-items: center; }

    /* Tile: 175.53 x 245.08, conic gradient behind the cut-out image. */
    .sqv4-shopcat__tile {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        padding: 0 0 4.72cqw;           /* 8.27988 / 175.53 */
        gap: 7.55cqw;                   /* 13.25 / 175.53 */
        width: 100%;
        aspect-ratio: 175.53 / 245.08;
        container-type: inline-size;
        background: conic-gradient(from 180deg at 50% 50%, #5A9B00 0deg, #76C00E 360deg);
        border-radius: 49.06cqw 49.06cqw 0px 4.72cqw;
        overflow: hidden;
        text-decoration: none;
    }
    .sqv4-shopcat__img {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        /* The label occupies the bottom 41.4px (245.08 - 203.685). */
        bottom: 16.9%;
        width: 100%;
        height: auto;
        max-height: 100%;
        object-fit: contain;
        object-position: center;
        margin: auto;
    }
    .sqv4-shopcat__label {
        position: relative;
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 12.5cqw;             /* 21.9384 / 175.53 */
        line-height: 1.5;               /* 33 / 21.9384 */
        letter-spacing: 0.685574px;
        color: #FFFFFF;
        white-space: nowrap;
    }

    /* Explore all (Frame 1984080196): 304x56, 1px #121212, radius 34. */
    .sqv4-shopcat__cta {
        box-sizing: border-box;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 8px;
        gap: 8px;
        width: 240px;
        height: 48px;
        border: 1px solid #121212;
        border-radius: 34px;
        background: transparent;
        cursor: pointer;
        text-decoration: none;
    }
    .sqv4-shopcat__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #121212;
        white-space: nowrap;
    }
    .sqv4-shopcat__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-footer-text-muted);
    }

    @media (min-width: 1024px) {
        .sqv4-shopcat__head { justify-content: center; }
        .sqv4-shopcat__seeall { display: none; }
        .sqv4-shopcat__title { text-align: center; }
        .sqv4-shopcat__cta { display: flex; }
        .sqv4-shopcat {
            /* 24px 56px padding, 24px gap */
            --sqv4-flash-pad: max(
                clamp(24px, 3.889vw, 56px),
                calc((100% - 1440px) / 2 + 56px)
            );
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv4-shopcat__title {
            /* 40px / 60px line-height */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv4-shopcat__cta {
            /* 304 x 56 */
            width: clamp(240px, 21.111vw, 304px);
            height: clamp(48px, 3.889vw, 56px);
        }
        .sqv4-shopcat__cta-label {
            font-size: clamp(16px, 1.389vw, 20px);
        }
    }
</style>

<!-- ============ SHOP BY CATEGORY ============ -->
<section class="sqv4-shopcat">
  <div class="sqv4-shopcat__head">
    <h2 class="sqv4-shopcat__title">{{ __('Shop by Category') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv4-shopcat__seeall">{{ __('Explore all') }}</a>
  </div>

  @if ($__tiles->isNotEmpty())
    <div class="swiper sqv4-shopcat__row">
      <div class="swiper-wrapper" id="shopCategoryWrapper">
        @foreach ($__tiles as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
          @endphp
          <div class="swiper-slide" wire:key="shop-cat-v4-{{ $category->id }}">
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="sqv4-shopcat__tile">
              <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv4-shopcat__img" />
              <span class="sqv4-shopcat__label">{{ $__name }}</span>
            </a>
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv4-shopcat__empty">{{ __('No categories yet.') }}</p>
  @endif

  <a href="{{ route('tenant.storefront.category') }}" class="sqv4-shopcat__cta">
    <span class="sqv4-shopcat__cta-label">{{ __('Explore all') }}</span>
  </a>
</section>
