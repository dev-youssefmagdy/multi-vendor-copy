@php
    // Figma shows 8 tiles (the 4 designed categories repeated); take 8 real ones.
    $__categories = ($categories ?? $rootCategories ?? collect())->take(8)->values();
    $__catFallbackImages = [
        asset('souqify-4/assets/images/cat-women-bags.png'),
        asset('souqify-4/assets/images/cat-accessories.png'),
        asset('souqify-4/assets/images/cat-gaming.png'),
        asset('souqify-4/assets/images/cat-electronics.png'),
    ];
@endphp

{{-- Figma: Frame 1984080440 (1347x202.36 at left:43 on the 1440 canvas).
     40px header + 24px gap + 138.36px tile row. The blob behind each product is
     the existing category-blob-{1..4}.svg asset, cycled per tile.
     Values are expressed as clamp(min, <value>/1440*100vw, max). --}}
<style>
    .sqv5-cats {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 20px 16px;
        gap: 16px;
        background: #FFFFFF;
    }
    .sqv5-cats__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
        height: 40px;
    }
    .sqv5-cats__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 22px;
        line-height: 1.25;          /* 40 / 32 */
        color: var(--color-text-primary);
        margin: 0;
    }
    .sqv5-cats__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;          /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-primary);
        white-space: nowrap;
    }

    .sqv5-cats__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv5-cats__row .swiper-wrapper {
        align-items: flex-end;
    }

    .sqv5-cats__tile {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 0;
        /* 14.79px gap between art and label */
        gap: 10px;
        height: auto;
        min-width: 0;
        text-decoration: none;
    }
    /* Art block: the blob plus the product image overhanging its top. */
    .sqv5-cats__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        /* The blob is 83.18 square; the groups run 83.18-90.58 tall because the
           image overhangs it. */
        width: 100%;
        aspect-ratio: 83.18 / 90.58;
    }
    .sqv5-cats__blob {
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 100%;
        aspect-ratio: 1;
        transform: translateX(-50%);
        object-fit: contain;
    }
    .sqv5-cats__img {
        position: relative;
        z-index: 1;
        /* Figma image widths against the 83.18 blob run 65% (Group 51, 53.69) to
           102% (Group 50, 85.25). Sizing the box at the widest and letting
           object-fit:contain letterbox inside it reproduces all four without
           per-category values. */
        width: 102%;
        height: 100%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv5-cats__label {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 11px;
        line-height: 1.5;           /* 33 / 22.1818 */
        letter-spacing: 0.5px;
        text-align: center;
        white-space: nowrap;
        color: var(--color-text-primary);
        margin: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv5-cats__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 16px 0;
        color: var(--color-text-muted);
    }

    @media (min-width: 1024px) {
        .sqv5-cats {
            /* The frame itself has padding:0; the horizontal 43px is its left
               offset on the 1440 canvas. */
            padding: clamp(20px, 2.361vw, 34px) clamp(24px, 2.986vw, 43px);
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv5-cats__title {
            /* 32px / 40px line-height, weight 500 */
            font-size: clamp(24px, 2.222vw, 32px);
        }
        .sqv5-cats__seeall {
            /* 20px / 25px line-height */
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv5-cats__tile {
            gap: clamp(10px, 1.027vw, 14.79px);
        }
        .sqv5-cats__art {
            /* 83.18 blob, 90.58 tall group */
            width: clamp(64px, 5.776vw, 83.18px);
            height: clamp(70px, 6.29vw, 90.58px);
            aspect-ratio: auto;
        }
        .sqv5-cats__label {
            /* 22.1818px / 33px line-height / 0.924242px tracking */
            font-size: clamp(16px, 1.5404vw, 22.1818px);
            letter-spacing: clamp(0.5px, 0.0642vw, 0.924242px);
        }
    }
</style>

<!-- ============ CATEGORIES ============ -->
<section class="sqv5-cats">
  <div class="sqv5-cats__head">
    <h2 class="sqv5-cats__title">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv5-cats__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__categories->isNotEmpty())
    <div class="swiper categories-swiper sqv5-cats__row">
      <div id="categoriesWrapper" class="swiper-wrapper">
        @foreach ($__categories as $index => $category)
          @php $__catName = $category->translationValue('name') ?? $category->slug; @endphp
          <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="swiper-slide sqv5-cats__tile" wire:key="cat-v5-{{ $category->id }}">
            <div class="sqv5-cats__art">
              <img src="{{ asset('souqify-4/assets/icons/category-blob-' . (($index % 4) + 1) . '.svg') }}" alt="" class="sqv5-cats__blob" />
              <img
                src="{{ $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)] }}"
                alt="{{ $__catName }}"
                class="sqv5-cats__img" />
            </div>
            <p class="sqv5-cats__label">{{ \Illuminate\Support\Str::limit($__catName, 15) }}</p>
          </a>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv5-cats__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>
