@php
    // Figma shows 8 tiles (the 4 designed categories repeated); take 8 real ones.
    $__categories = ($categories ?? $rootCategories ?? collect())->take(8)->values();
    $__catFallbackImages = [
        asset('souqify-3/assets/images/cat-women-bags.png'),
        asset('souqify-3/assets/images/cat-gaming.png'),
        asset('souqify-3/assets/images/cat-accessories.png'),
        asset('souqify-3/assets/images/cat-electronics.png'),
    ];
@endphp

{{-- Figma: Frame 1984080440 (1347x219.31 at left:43 on the 1440 canvas).
     40px header + 24px gap + 155.31px tile row.
     Tile art = a 90.62px "Repeat group" of teal petals (Rectangle 5963, 34x30,
     radius 0 104px 0 176px) with the product image overhanging it.
     Values are expressed as clamp(min, <value>/1440*100vw, max). --}}
<style>
    .sqv3-cats {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
    }
    .sqv3-cats__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
        height: 40px;
    }
    .sqv3-cats__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 22px;
        line-height: 1.25;          /* 40 / 32 */
        color: #000000;
        margin: 0;
    }
    .sqv3-cats__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;          /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-souqify-teal);
        white-space: nowrap;
    }

    .sqv3-cats__row {
        width: 100%;
        overflow: hidden;
    }
    .sqv3-cats__row .swiper-wrapper {
        align-items: flex-end;
    }

    .sqv3-cats__tile {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 0;
        /* 16px gap between art and label */
        gap: 10px;
        height: auto;
        min-width: 0;
        text-decoration: none;
    }
    /* Art block: the teal petal cluster plus the product image overhanging it. */
    .sqv3-cats__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        /* "Repeat group 1" is 90.62 square; the group boxes run 91.31-103.31 tall
           because the image overhangs the petals. */
        width: 100%;
        aspect-ratio: 90.62 / 103.31;
    }
    /* The petal cluster ("Repeat group 1", 90.62 square). One petal is
       Rectangle 5963 - 34x30, radius 0/104/0/176 (the 104 and 176 clamp to
       min(w,h)/2 = 15) - repeated five times at 72deg. Shipped as one SVG so the
       cluster stays a single element behind the product image. */
    .sqv3-cats__shape {
        position: absolute;
        left: 50%;
        /* Figma puts "Repeat group 1" at top:0.69 inside the 91.31-tall group
           (Group 40 / Group 41), so the cluster sits at the top of the art box and
           the product image overhangs it downward. */
        top: 0.69px;
        width: 100%;
        aspect-ratio: 1;
        transform: translateX(-50%);
        background: url('{{ asset('souqify-3/assets/icons/cat-leaves-teal.svg') }}') center / contain no-repeat;
    }

    .sqv3-cats__img {
        position: relative;
        z-index: 1;
        /* Figma image widths against the 90.62 group run 62% (Group 42, 55.86) to
           117% (Group 43, 106). Sizing the box at the widest and letting
           object-fit:contain letterbox inside it reproduces all four without
           per-category values. */
        width: 117%;
        height: 100%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv3-cats__label {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 13px;
        line-height: 1.5;           /* 36 / 24 */
        letter-spacing: 0.5px;
        text-align: center;
        white-space: nowrap;
        color: #000000;
        margin: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv3-cats__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 16px 0;
        color: var(--color-text-muted);
    }

    @media (min-width: 1024px) {
        .sqv3-cats {
            /* The frame itself has padding:0; the horizontal 43px is the frame's
               left offset on the 1440 canvas. */
            padding: clamp(24px, 3.333vw, 48px) clamp(24px, 2.986vw, 43px);
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-cats__title {
            /* 32px / 40px line-height, weight 500 */
            font-size: clamp(24px, 2.222vw, 32px);
        }
        .sqv3-cats__seeall {
            /* 20px / 25px line-height */
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv3-cats__tile {
            gap: clamp(8px, 1.111vw, 16px);
        }
        .sqv3-cats__art {
            /* 90.62 wide, 103.31 tall group */
            width: clamp(70px, 6.2931vw, 90.62px);
            height: clamp(80px, 7.1743vw, 103.31px);
            aspect-ratio: auto;
        }
        .sqv3-cats__label {
            /* 24px / 36px line-height / 1px tracking */
            font-size: clamp(16px, 1.6667vw, 24px);
            letter-spacing: clamp(0.5px, 0.0694vw, 1px);
        }
    }
</style>

<!-- ============ CATEGORIES ============ -->
<section class="sqv3-cats" style="background:var(--color-page-bg)">
  <div class="sqv3-cats__head">
    <h2 class="sqv3-cats__title">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv3-cats__seeall">{{ __('see all') }}</a>
  </div>
  @if ($__categories->isNotEmpty())
    <div class="swiper categories-swiper sqv3-cats__row">
      <div id="categoriesWrapper" class="swiper-wrapper">
        @foreach ($__categories as $index => $category)
          @php $__catName = $category->translationValue('name') ?? $category->slug; @endphp
          <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="swiper-slide sqv3-cats__tile" wire:key="cat-v3-{{ $category->id }}">
            <div class="sqv3-cats__art">
              <span class="sqv3-cats__shape"></span>
              <img
                src="{{ $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)] }}"
                alt="{{ $__catName }}"
                class="sqv3-cats__img" />
            </div>
            <p class="sqv3-cats__label">{{ \Illuminate\Support\Str::limit($__catName, 15) }}</p>
          </a>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv3-cats__empty">{{ __('No categories yet.') }}</p>
  @endif
</section>
