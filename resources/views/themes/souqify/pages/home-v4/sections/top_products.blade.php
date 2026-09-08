@php
    // Figma cycles three discount-chip palettes across the receding cards.
    $__chipPalettes = [
        ['bg' => '#FFB00A', 'color' => '#121212'],
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
    ];

    $__bestSellerCards = collect($bestSelling ?? [])->take(6)->values()->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        $chip = $__chipPalettes[$index % count($__chipPalettes)];
        $__name = \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30);
        $__img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
        $__url = route('tenant.storefront.product', $product->slug);
        $__outOfStock = $product->stockStatus() === 'out_of_stock';

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => $__url,
            'image' => $__img,
            'name' => $__name,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'outOfStock' => $__outOfStock,
            'favData' => json_encode([
                'slug' => $product->slug,
                'name' => $__name,
                'price' => round((float) $pricing['current_price'] * $rate, 2),
                'old_price' => $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null,
                'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
                'rating' => $rating,
                'image' => $__img,
                'url' => $__url,
                'added' => time(),
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
            // Design-only fields with no backing data yet.
            'weight' => null,
            'badge' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
        ];
    });

    $__bestSellerMobile = $__bestSellerCards->take(3);
@endphp

@include('themes.souqify.pages.home-v4.sections.partials.deal_card_styles')

{{-- Figma: Frame 1984080240 (1440x626.89) - 24px 56px padding, 24px gap,
     background url(tras.png) blended plus-lighter over #5A9B00. --}}
<style>
    .sqv4-best {
        --sqv4-flash-pad: max(16px, calc((100% - 1440px) / 2 + 16px));
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 32px var(--sqv4-flash-pad);
        gap: 16px;
        background: #5A9B00;
        overflow: hidden;
    }
    /* The comp blends a texture over the green with plus-lighter. */
    .sqv4-best__overlay {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        mix-blend-mode: plus-lighter;
        opacity: 0.5;
        pointer-events: none;
    }
    /* White speckle over the green: a few fat dots with finer ones between
       them, spread on wide tiles so they stay sparse, and blurred so the edges
       glow instead of reading as hard circles. */
    .sqv4-best::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.6;
        filter: blur(1.6px);
        background-image:
            radial-gradient(circle at 18% 26%, rgba(255,255,255,0.95) 0 2.8px, transparent 3.2px),
            radial-gradient(circle at 72% 12%, rgba(255,255,255,0.9)  0 2.2px, transparent 2.6px),
            radial-gradient(circle at 40% 78%, rgba(255,255,255,0.9)  0 3px,   transparent 3.4px),
            radial-gradient(circle at 86% 62%, rgba(255,255,255,0.8)  0 1.4px, transparent 1.8px),
            radial-gradient(circle at 8% 84%,  rgba(255,255,255,0.8)  0 1.2px, transparent 1.6px);
        background-size:
            150px 130px, 118px 168px, 196px 152px, 96px 108px, 84px 122px;
    }

    .sqv4-best > *:not(.sqv4-best__overlay) { position: relative; z-index: 1; }
    .sqv4-best::after { z-index: 0; }

    .sqv4-best__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv4-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv4-best__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
        text-decoration: none;
    }

    /* ---------- Draggable fan cascade ----------
       Same technique as ELORA Minimal Edition's Best Seller carousel (see
       mountBestSellerCarousel() in carousels-v4.js): every slide is
       absolutely positioned from a fixed geometry table indexed by distance
       from the active slide, so dragging shifts which product sits in the
       biggest/leftmost slot while the rest cascade down in size behind it. */
    .sqv4-best__swiper {
        /* overflow:visible lets the fan's cards bleed past the swiper's own
           (much smaller) auto-width box. */
        overflow: visible;
        padding: 0 0 24px;
        cursor: grab;
        width: 100% !important;
    }
    .sqv4-best__group {
        /* Best Seller accent: the theme green. */
        --sqv4-deal-accent: #5A9B00;
        position: relative;
        width: 100%;
    }
    /* Every slide is this ONE fixed base size (matching the biggest/active
       Figma card exactly); mountBestSellerCarousel applies
       `transform: translate(left, top) scale(ratio)` per slide to produce
       every other cascade depth from it, so content never gets crammed into
       a shrunk box - the whole card scales as one unit. */
    .sqv4-best__deal-base {
        position: absolute;
        top: 0;
        left: 0;
        width: 154.5px;
        height: 223.1px;
        transform-origin: top left;
    }
    @media (min-width: 1024px) {
        .sqv4-best__deal-base {
            width: 295.45px;
            height: 472.89px;
        }
    }
    .sqv4-best__deal-base .sqv4-deal {
        position: static;
        width: 100%;
        height: 100%;
    }

    /* ---------- Pagination (Frame 1984080197) ---------- */
    .sqv4-best__dots {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 0;
        gap: 3px;
        width: 100%;
        height: 8px;
    }
    .sqv4-best__dots .best-seller-dot {
        width: 8px;
        height: 8px;
        background: #F3F3F3;
        border-radius: 29px;
        opacity: 1;
        transition: width 0.2s ease, background-color 0.2s ease;
        cursor: pointer;
    }
    .sqv4-best__dots .best-seller-dot.is-active {
        width: 24px;
        background: #FFE100;
    }
    .sqv4-best__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    @media (min-width: 1024px) {
        .sqv4-best {
            /* 24px 56px padding, 24px gap */
            --sqv4-flash-pad: max(
                clamp(24px, 3.889vw, 56px),
                calc((100% - 1440px) / 2 + 56px)
            );
            padding: 24px var(--sqv4-flash-pad);
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv4-best__title {
            /* 40px / 50px line-height */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv4-best__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv4-best" wire:ignore>
  <img src="{{ asset('souqify-3/assets/images/best-seller-swirl-bg.png') }}" alt="" class="sqv4-best__overlay" />

  <div class="sqv4-best__head">
    <h2 class="sqv4-best__title">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv4-best__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__bestSellerCards->isNotEmpty())
    {{-- Same draggable fan-cascade carousel as ELORA Minimal Edition's Best
         Seller (see mountBestSellerCarousel() in carousels-v4.js). --}}
    <div class="swiper sqv4-best__swiper lg:!hidden w-full">
      <div class="swiper-wrapper sqv4-best__group" id="bestSellerMobileWrapper">
        @foreach ($__bestSellerMobile as $p)
          <div class="swiper-slide sqv4-best__deal-base" style="position:absolute; top:0; left:0;" wire:key="bestseller-mobile-v4-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => '', 'cartIcon' => 'icon-cart-green.svg'])
          </div>
        @endforeach
      </div>
    </div>
    <div class="sqv4-best__dots lg:!hidden" id="bestSellerMobileDots"></div>

    <div class="swiper sqv4-best__swiper !hidden lg:!block w-full">
      <div class="swiper-wrapper sqv4-best__group" id="bestSellerDesktopWrapper">
        @foreach ($__bestSellerCards as $p)
          <div class="swiper-slide sqv4-best__deal-base" style="position:absolute; top:0; left:0;" wire:key="bestseller-desktop-v4-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v4.sections.partials.deal_card', ['p' => $p, 'style' => '', 'cartIcon' => 'icon-cart-green.svg'])
          </div>
        @endforeach
      </div>
    </div>
    <div class="sqv4-best__dots !hidden lg:!block" id="bestSellerDesktopDots"></div>
  @else
    <p class="sqv4-best__empty">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
