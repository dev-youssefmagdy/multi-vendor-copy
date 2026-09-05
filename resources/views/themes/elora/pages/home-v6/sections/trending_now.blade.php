    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $trendingCards = $trendingNowProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-2/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          $variantWeight = (int) ($variant?->centralVariant?->weight_grams ?? 0);
          $weightGrams = $variantWeight > 0
              ? $variantWeight
              : (int) ($product->centralProduct->weight_grams ?? $product->weight_grams ?? 0);

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightGrams > 0 ? $weightGrams . 'g' : '',
              'progress' => 60,
              'ordered' => '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : '',
          ];
      });
    @endphp
    <!-- ============ TRENDING NOW ============ -->
    <section
      class="py-[24px]! lg:py-[48px]! flex flex-col gap-[16px] lg:gap-[24px] bg-[#f0f0f0]"
    >
      <div class="flex items-center justify-between px-[16px] lg:px-[56px]">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Trending Now
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-green)"
          >see all</a
        >
      </div>
      <div class="relative ps-[16px] lg:ps-[56px]">
        <div class="swiper card-swiper trending-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @foreach ($trendingCards as $p)
              <div class="swiper-slide h-auto">
                @include('themes.elora.pages.home-v6.sections.partials.trending_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
