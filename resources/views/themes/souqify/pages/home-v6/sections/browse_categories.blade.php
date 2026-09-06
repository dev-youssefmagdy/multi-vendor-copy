@php
    // Figma lays eight tiles across the 1347px row.
    $__categories = ($categories ?? $rootCategories ?? collect())->take(8)->values();
    $__catFallbackImages = [
        asset('souqify-5/assets/images/category-bag.png'),
        asset('souqify-5/assets/images/category-gaming.png'),
        asset('souqify-5/assets/images/category-electronics.png'),
        asset('souqify-5/assets/images/category-accessories.png'),
    ];
@endphp

{{-- Figma: Frame 1984080440 (1347x216.73) - a 40px header over the 152.73px
     tile row, 24px apart. Each tile is Rectangle 5966 (70.33x84.4, an 8.03821px
     #FF1A90 arch) with the product photo overlapping it, then a 24.1146px label.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv6-cats {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-page-bg, #FFFFFF);
    }
    /* Frame 1984080017 */
    .sqv6-cats__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv6-cats__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 22px;
        line-height: 1.25;              /* 40 / 32 */
        color: #000000;
        margin: 0;
    }
    .sqv6-cats__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-brand-pink, #FF1A90);
        white-space: nowrap;
        text-decoration: none;
    }

    /* ---------- Tile row ---------- */
    .sqv6-cats__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv6-cats__row .swiper-wrapper { align-items: flex-end; }
    /* Frame 1984080350: the arch group over an 8.04px gap and the label. */
    .sqv6-cats__tile {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 6px;
        height: auto;
        min-width: 0;
        text-decoration: none;
    }
    .sqv6-cats__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        width: 100%;
        /* Group 53: 88.42 x 94.45 */
        aspect-ratio: 88.42 / 94.45;
    }
    /* Rectangle 5966: 70.33 x 84.4, an 8.03821px pink stroke. The comp draws it
       as an arch - stroked on three sides with the top corners rounded - so the
       bottom border is dropped rather than radiused. */
    .sqv6-cats__arch {
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        box-sizing: border-box;
        /* 70.33 / 88.42 */
        width: 79.54%;
        /* 84.4 / 94.45 */
        height: 89.36%;
        border: 4px solid var(--color-brand-pink, #FF1A90);
        border-bottom: 0;
        border-radius: 999px 999px 0 0;
    }
    .sqv6-cats__img {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 88%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv6-cats__label {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 12px;
        line-height: 1.5;               /* 36 / 24.1146 */
        letter-spacing: 0.5px;
        text-align: center;
        color: #000000;
        margin: 0;
        max-width: 100%;
    }
    .sqv6-cats__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 16px 0;
        color: var(--color-text-muted, #8F8F8F);
    }

    @media (min-width: 1024px) {
        .sqv6-cats {
            /* The comp's content column is 1347px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.229vw, 46.5px), calc((100% - 1347px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-cats__title { font-size: clamp(24px, 2.222vw, 32px); }
        .sqv6-cats__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        .sqv6-cats__tile { gap: clamp(6px, 0.558vw, 8.04px); }
        .sqv6-cats__arch { border-width: clamp(5px, 0.5582vw, 8.03821px); }
        .sqv6-cats__label {
            /* 24.1146px / 36px line-height / 1.00478px tracking */
            font-size: clamp(16px, 1.6746vw, 24.1146px);
            letter-spacing: clamp(0.5px, 0.0698vw, 1.00478px);
        }
    }
</style>

<!-- ============ CATEGORIES ============ -->
<section class="sqv6-cats">
  <div class="sqv6-cats__head">
    <h2 class="sqv6-cats__title">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv6-cats__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__categories->isNotEmpty())
    <div class="swiper categories-swiper sqv6-cats__row">
      <div id="categoriesWrapper" class="swiper-wrapper">
        @foreach ($__categories as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15);
          @endphp
          <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="swiper-slide sqv6-cats__tile" wire:key="cat-v6-{{ $category->id }}">
            <div class="sqv6-cats__art">
              <span class="sqv6-cats__arch" aria-hidden="true"></span>
              <img src="{{ $__img }}" alt="{{ $__name }}" class="sqv6-cats__img" />
            </div>
            <p class="sqv6-cats__label">{{ $__name }}</p>
          </a>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv6-cats__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>
