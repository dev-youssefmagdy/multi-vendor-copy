@php
    $__bestSellerCards = collect($bestSelling ?? [])->take(6)->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the fanned cards.
        $chip = [
            ['bg' => '#FFB00A', 'color' => '#121212'],
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ][$index % 3];

        $__img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
        $__name = \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30);
        $__url = route('tenant.storefront.product', $product->slug);
        $__sellPrice = (float) $pricing['current_price'];
        $__displayReal = $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null;

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => $__url,
            'image' => $__img,
            'tag' => '🔥 ' . __('Trending Now'),
            'cartIcon' => 'trending-cart-purple.svg',
            'stock' => __('Only 5 left - Hurry up'),
            'name' => $__name,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format($__sellPrice * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'outOfStock' => $product->stockStatus() === 'out_of_stock',
            'favData' => json_encode([
                'slug' => $product->slug,
                'name' => $__name,
                'price' => round($__sellPrice * $rate, 2),
                'old_price' => $__displayReal,
                'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
                'rating' => $rating,
                'image' => $__img,
                'url' => $__url,
                'added' => time(),
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
        ];
    });

    $__bestSellerMobile = $__bestSellerCards->take(3);
@endphp

{{-- Figma: Frame 1984080385 (1440x681) - a full-bleed #EC910A panel whose top and
     bottom edges are scalloped (fifteen 101.01-wide rects with a 0/86 radius),
     a 50px header, then Group 58: six 228.76x366.14 Deal Cards fanned and
     overlapped, rotations cycling -11.98deg / 0 / 8.97deg.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv5-best {
        /* The comp keeps the card's price and tag purple inside this panel. */
        --sqv5-deal-accent: #8B03BD;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px 16px;
        gap: 16px;
        /* The panel has no fill of its own: the scallop IS the union of the
           orange columns below. */
        background: transparent;
        isolation: isolate;
        /* Without this, the fan cascade's absolutely-positioned cards (see
           .sqv5-best__row below) render past this section's own box instead
           of stopping at it. */
        overflow: hidden;
    }
    /* Rectangles 5967-5981: fifteen 101.01-wide full-height columns, each with a
       0/86 radius (rounded top-right and bottom-left only), stepped 95.64 apart
       so they overlap. Their union is the scalloped panel - the sharp point of
       one tooth meets the rounded shoulder of the next, which a symmetric
       semicircle mask cannot reproduce. */
    .sqv5-best__bg {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }
    .sqv5-best__bg span {
        position: absolute;
        top: 0;
        bottom: 0;
        /* 101.01 / 1440 */
        width: 7.015%;
        background: var(--color-primary);
        /* 86px on the 1440 canvas */
        border-radius: 0 clamp(34px, 5.972vw, 86px);
    }

    .sqv5-best__head {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv5-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv5-best__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
    }

    .sqv5-best__group {
        position: relative;
        width: 100%;
    }

    /* ---------- Mobile: card-effect carousel ----------
       Same technique as Green Edition's Flash Sale mobile carousel (see
       mountCardFanMobile() in carousels-v5.js): every slide's CSS base
       position is centered directly (left: calc(50% - 77.25px), 77.25 =
       half the 154.5px card width), so with translateX(0) the active card
       sits exactly centered; JS then offsets each neighbor from that
       centered base by a fixed pixel amount, rotated. */
    .sqv5-best__mobile-row {
        position: relative;
        z-index: 1;
        overflow: visible;
        padding: 0 0 24px;
        width: 100%;
    }
    .sqv5-best__mobile-row .swiper-wrapper {
        position: relative;
        height: 223.1px;
    }
    .sqv5-best__mobile-deal-base {
        position: absolute;
        top: 0;
        left: calc(50% - 77.25px);
        width: 154.5px;
        height: 223.1px;
        transition-property: transform;
    }
    .sqv5-best__mobile-deal-base .sqv5-deal {
        position: static;
        width: 100%;
        height: 100%;
        box-shadow: none;
        filter: drop-shadow(0px 0px 30px rgba(0, 0, 0, 0.18));
    }

    /* ---------- Desktop: draggable rotated fan cascade ----------
       Same technique as Green Edition's Flash Sale desktop card fan (see
       mountFanCascadeCarousel() in carousels-v5.js): every slide is
       absolutely positioned from a fixed geometry table indexed by distance
       from the active slide, so dragging shifts which product sits in the
       front slot while the rest cascade around it - every card stays the
       same size, only rotated and offset. */
    .sqv5-best__row {
        position: relative;
        z-index: 1;
        /* overflow:visible lets the fan's cards bleed past the swiper's own
           (much smaller) auto-width box. */
        overflow: visible;
        padding: 0 0 24px;
        cursor: grab;
        width: 100% !important;
    }
    .sqv5-best__deal-base {
        position: absolute;
        top: 0;
        left: 0;
        width: 295.45px;
        height: 472.89px;
    }
    .sqv5-best__deal-base .sqv5-deal {
        position: static;
        width: 100%;
        height: 100%;
        box-shadow: none;
        filter: drop-shadow(0px 0px 30px rgba(0, 0, 0, 0.18));
    }

    /* ---------- Pagination ---------- */
    .sqv5-best__dots {
        display: block;
        text-align: center;
        width: 100%;
        padding: 0;
        margin: 0;
        height: 8px;
        font-size: 0;
        line-height: 0;
    }
    .sqv5-best__dots .best-seller-dot {
        display: inline-block;
        vertical-align: top;
        margin: 0 1.5px;
        width: 8px;
        height: 8px;
        background: rgba(255, 255, 255, 0.4);
        border: 0;
        border-radius: 29px;
        padding: 0;
        opacity: 1;
        transition: width 0.2s ease, background-color 0.2s ease;
        cursor: pointer;
    }
    .sqv5-best__dots .best-seller-dot.is-active {
        width: 24px;
        background: #FFFFFF;
    }

    @media (min-width: 1024px) {
        .sqv5-best {
            /* 24px 56px padding on the 1440 canvas, plus the room the scallop and
               the rotated cards need. */
            padding: clamp(40px, 4.583vw, 66px) max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv5-best__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv5-best__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv5-best mx-[8px] lg:mx-[16px]" wire:ignore>
  <span class="sqv5-best__bg" aria-hidden="true">
    {{-- 95.64 / 1440 = 6.642% step; 16 columns cover the full 1440 width. --}}
    @for ($__i = 0; $__i < 16; $__i++)
      <span style="left: {{ round($__i * 6.642, 3) }}%"></span>
    @endfor
  </span>

  <div class="sqv5-best__head">
    <h2 class="sqv5-best__title">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv5-best__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__bestSellerCards->isNotEmpty())
    {{-- Mobile: card-effect carousel, same technique as Green Edition's
         Flash Sale mobile carousel (see mountCardFanMobile() in
         carousels-v5.js). --}}
    <div class="sqv5-best__group lg:!hidden">
      <div class="swiper sqv5-best__mobile-row w-full">
        <div class="swiper-wrapper" id="bestSellerMobileWrapper">
          @foreach ($__bestSellerMobile as $p)
            <div class="swiper-slide sqv5-best__mobile-deal-base" wire:key="best-seller-mobile-v5-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v5.sections.partials.deal_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="sqv5-best__dots lg:!hidden" id="bestSellerMobileDots"></div>

    {{-- Desktop: draggable rotated fan cascade, matching Green Edition's
         Flash Sale desktop card fan (see mountFanCascadeCarousel() in
         carousels-v5.js). --}}
    <div class="swiper bestseller-swiper sqv5-best__row !hidden lg:!block w-full">
      <div class="swiper-wrapper sqv5-best__group" id="bestSellerDesktopWrapper">
        @foreach ($__bestSellerCards as $p)
          <div class="swiper-slide sqv5-best__deal-base" style="position:absolute; top:0; left:0;" wire:key="best-seller-desktop-v5-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v5.sections.partials.deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
    <div class="sqv5-best__dots !hidden lg:!block" id="bestSellerDesktopDots"></div>
  @else
    <p class="relative text-white/80 text-sm py-6">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
