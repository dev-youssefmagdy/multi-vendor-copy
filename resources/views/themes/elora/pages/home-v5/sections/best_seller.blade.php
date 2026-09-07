    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $bestSellerCards = $bestSelling->map(function ($product, $index) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-5/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
          $weightGrams = $product->centralProduct?->weight_grams ?? $product->weight_grams ?? null;
          $weightLabel = $weightGrams
              ? ($weightGrams >= 1000 ? number_format($weightGrams / 1000, 1) . __('kg') : $weightGrams . __('g'))
              : '';

          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightLabel,
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
              'alt' => $index % 3 === 0,
              'stock' => '',
          ];
      });
      $bestSellerMobile = $bestSellerCards->take(3);
    @endphp
    <!-- ============ BEST SELLER ============ -->
    <section
      class="bestseller-section-bg px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px] overflow-hidden"
    >
      <div class="flex items-center justify-between w-full">
        <h2
          class="font-medium text-[18px] lg:text-[32px] text-white lg:!text-[var(--color-black)]"
        >
          {{ __('Best Seller') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="font-normal text-[12px] lg:text-[20px] tracking-[0.5px] text-[#F6C97F] lg:!text-[var(--color-primary)]"
          >{{ __('see all') }}</a
        >
      </div>
      <!-- Mobile -->
      <div class="swiper bestseller-swiper lg:!hidden w-full">
        <div class="swiper-wrapper" id="bestSellerMobileWrapper">
          @foreach ($bestSellerMobile as $p)
            <div class="swiper-slide bestseller-slide fan-slide-base" style="position:absolute; top:0; left:0;" wire:key="bestseller-mobile-v5-{{ $p['id'] }}">
              @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <div class="flex items-center justify-center gap-[3px] w-full lg:hidden" id="bestSellerMobileDots"></div>
      <!-- Desktop: 6 per view -->
      <div class="swiper bestseller-swiper !hidden lg:!block w-full max-w-[1183px] mx-auto">
        <div class="swiper-wrapper" id="bestSellerDesktopWrapper">
          @foreach ($bestSellerCards as $p)
            <div class="swiper-slide bestseller-slide fan-slide-base" style="position:absolute; top:0; left:0;" wire:key="bestseller-desktop-v5-{{ $p['id'] }}">
              @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
    </section>
