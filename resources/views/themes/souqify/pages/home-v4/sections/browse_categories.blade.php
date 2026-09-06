@php
    // Figma shows 8 tiles (the 4 designed categories repeated); take 8 real ones.
    $__categories = ($categories ?? $rootCategories ?? collect())->take(8)->values();
    $__catFallbackImages = [
        asset('souqify-3/assets/images/cat-women-bags.png'),
        asset('souqify-3/assets/images/cat-accessories.png'),
        asset('souqify-3/assets/images/cat-gaming.png'),
        asset('souqify-3/assets/images/cat-electronics.png'),
    ];
@endphp

{{-- Figma: Frame 1984080440 (1347x204.33 at left:43 on the 1440 canvas).
     40px header + 24px gap + 140.33px tile row = 204.33px.
     Each tile: 89.42px art block + 14.9px gap + 36px label = 140.32px.
     Values are expressed as clamp(min, <value>/1440*100vw, max). --}}
<style>
    .sqv4-cats {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
    }
    .sqv4-cats__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
        height: 40px;
    }
    .sqv4-cats__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 22px;
        line-height: 1.25;          /* 40 / 32 */
        color: #000000;
        margin: 0;
    }
    .sqv4-cats__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;          /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #5A9B00;
        white-space: nowrap;
    }

    .sqv4-cats__row {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 12px;
        width: 100%;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .sqv4-cats__row::-webkit-scrollbar { display: none; }

    .sqv4-cats__tile {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 0;
        /* 14.9px gap between art and label */
        gap: 10px;
        /* Mobile comp fits four tiles across the row; the rest scroll in. */
        flex: 0 0 calc((100% - 36px) / 4);
        min-width: 0;
        scroll-snap-align: start;
        text-decoration: none;
    }
    /* Art block: the green shape plus the product image that overhangs it. */
    .sqv4-cats__art {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-end;
        /* Rectangle 5963 is 67.07x68.93; the group is 89.42 tall because the image
           overhangs the shape by ~20.5px at the bottom. */
        width: 100%;
        height: auto;
        aspect-ratio: 67.07 / 89.42;
    }
    .sqv4-cats__shape {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        /* 68.93 / 67.07 */
        height: 87.26%;
        background: #5A9B00;
        border-radius: 96.8746px 96.8746px 0px 9.31487px;
    }
    .sqv4-cats__img {
        position: relative;
        z-index: 1;
        /* Figma image widths against the 67.07 shape run 108% (Group 45, 72.66) to
           133% (Group 48, 89.42); Group 46 is taller than wide (51.9x83.83). Sizing
           the box at the widest and letting object-fit:contain letterbox inside it
           reproduces all four without per-category values. */
        width: 133.33%;
        height: 100%;
        object-fit: contain;
        object-position: bottom center;
    }
    .sqv4-cats__label {
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
    .sqv4-cats__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 16px 0;
        color: var(--color-footer-text-muted);
    }

    @media (min-width: 1024px) {
        .sqv4-cats {
            /* The frame itself has padding:0; the 48px vertical space is the canvas
               gap between the trust bar (ends 892.2) and this frame (starts 940.2).
               Horizontal 43px is the frame's left offset. */
            padding: clamp(24px, 3.333vw, 48px) clamp(24px, 2.986vw, 43px);
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv4-cats__title {
            /* 32px / 40px line-height */
            font-size: clamp(24px, 2.222vw, 32px);
        }
        .sqv4-cats__seeall {
            /* 20px / 25px line-height */
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv4-cats__row {
            /* 44.71px gap, distributed across the full 1347px width */
            gap: clamp(20px, 3.105vw, 44.71px);
            justify-content: space-between;
        }
        .sqv4-cats__tile {
            gap: clamp(8px, 1.0347vw, 14.9px);
            flex: none;
        }
        .sqv4-cats__art {
            /* 67.07 wide, 89.42 tall group */
            width: clamp(56px, 4.6576vw, 67.07px);
            height: clamp(74px, 6.2097vw, 89.42px);
            aspect-ratio: auto;
        }
        .sqv4-cats__label {
            /* 24px / 36px line-height / 0.931487px tracking */
            font-size: clamp(16px, 1.6667vw, 24px);
            letter-spacing: clamp(0.5px, 0.0647vw, 0.931487px);
        }
    }
</style>

<!-- ============ CATEGORIES ============ -->
<section class="sqv4-cats" style="background:var(--color-page-bg)">
  <div class="sqv4-cats__head">
    <h2 class="sqv4-cats__title">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="sqv4-cats__seeall">{{ __('see all') }}</a>
  </div>
  <div class="sqv4-cats__row">
    @forelse ($__categories as $index => $category)
      @php $__catName = $category->translationValue('name') ?? $category->slug; @endphp
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="sqv4-cats__tile">
        <div class="sqv4-cats__art">
          <span class="sqv4-cats__shape"></span>
          <img
            src="{{ $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)] }}"
            alt="{{ $__catName }}"
            class="sqv4-cats__img" />
        </div>
        <p class="sqv4-cats__label">{{ \Illuminate\Support\Str::limit($__catName, 15) }}</p>
      </a>
    @empty
      <p class="sqv4-cats__empty">{{ __('No categories yet.') }}</p>
    @endforelse
  </div>
</section>
