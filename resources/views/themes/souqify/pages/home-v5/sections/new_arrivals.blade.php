@php
    $__newInCards = collect($newInProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 22),
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'subtitle' => \Illuminate\Support\Str::limit(strip_tags($product->translationValue('short_description') ?? $product->translationValue('description') ?? ''), 34) ?: null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'sold' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Sold') : null,
            'delivery' => __('Delivered by') . ' ' . now()->addDays(5)->translatedFormat('j F'),
            'stock' => __('Only 5 left'),
        ];
    });

    // Frame 1984080450: the cards are stacked three to a column, and the columns
    // are what the carousel scrolls.
    $__newInColumns = $__newInCards->chunk(3)->values();
@endphp

{{-- Figma: Frame 1984080239 (1440 x 583.78) - the patterned panel, 24px 56px
     padding, 24px gap: a 50px header then the column row (12px between columns,
     12.92px between the cards inside one).
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv5-newin {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        /* Breathing room from the flash panel above and Trending below. */
        margin: 24px 0;
        /* Figma: the wave photo over a yellow fill, blended with difference.
           The blend runs on its own layer at reduced opacity - at full strength
           the bright green highlights inverted to a saturated orange instead of
           the muted brick red of the comp. */
        background-color: #EFD35B;
        overflow: hidden;
        isolation: isolate;
    }
    .sqv5-newin::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 0;
        background-image: url('{{ asset('souqify-4/assets/images/newin-bg-pattern.jpg') }}');
        /* The whole 736x920 photo fits the frame rather than cropping: the black
           top band is what leaves the flat yellow strip under the header. */
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
        mix-blend-mode: difference;
        opacity: 0.55;
        pointer-events: none;
    }
    .sqv5-newin__head {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv5-newin__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv5-newin__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
        text-decoration: none;
    }

    .sqv5-newin__row {
        position: relative;
        z-index: 1;
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    /* Frame 1984080245: one slide is a column of three cards. */
    .sqv5-newin__col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        height: auto;
        min-width: 0;
    }
    .sqv5-newin__empty {
        position: relative;
        z-index: 1;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    @media (min-width: 1024px) {
        .sqv5-newin {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
            margin: clamp(24px, 3.333vw, 48px) 0;
        }
        .sqv5-newin__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv5-newin__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        .sqv5-newin__col { gap: clamp(8px, 0.897vw, 12.92px); }
    }
</style>

<!-- ============ NEW IN ============ -->
<section class="sqv5-newin">
  <div class="sqv5-newin__head">
    <h2 class="sqv5-newin__title">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="sqv5-newin__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__newInColumns->isNotEmpty())
    <div class="swiper newin-swiper sqv5-newin__row">
      <div class="swiper-wrapper" id="newInWrapper">
        @foreach ($__newInColumns as $__colIndex => $__column)
          <div class="swiper-slide sqv5-newin__col" wire:key="new-in-v5-col-{{ $__colIndex }}">
            @foreach ($__column as $p)
              @include('themes.souqify.pages.home-v5.sections.partials.new_in_card', ['p' => $p])
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv5-newin__empty">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>
