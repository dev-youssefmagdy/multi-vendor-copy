    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $bestSellerCards = $bestSelling->map(function ($product) use ($symbol, $rate) {
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
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% OFF' : 'Best-Selling',
              'badgeBg' => $hasDiscount ? 'var(--color-primary)' : 'var(--color-accent-yellow)',
              'badgeColor' => $hasDiscount ? 'var(--color-white)' : 'var(--color-black)',
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightGrams > 0 ? $weightGrams . 'g' : '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : '',
          ];
      });
      $bestSellerGroups = $bestSellerCards->values()->chunk(4)->filter(fn($g) => $g->count() === 4)->map(fn($g) => $g->values()->all())->all();
    @endphp
    <!-- ============ BEST SELLER ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      style="background: var(--color-accent-yellow)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Best Seller
        </h2>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-text-secondary-dark)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper best-seller-swiper w-full">
          <div class="swiper-wrapper" id="bestSellerWrapper">
            @foreach ($bestSellerGroups as $group)
              @php [$full, $small1, $small2, $wide] = $group; @endphp
              <div class="swiper-slide h-auto">
                <div class="flex gap-[12px] h-full">
                  <div class="flex-1">
                    @include('themes.elora.pages.home-v6.sections.partials.product_card', ['p' => $full + ['cardClass' => 'rounded-[8px] lg:rounded-[14px] shadow-[0px_4px_21.34px_rgba(0,0,0,0.25)] lg:shadow-[0px_6.84px_36.61px_rgba(0,0,0,0.25)]']])
                  </div>
                  <div class="flex-1 flex flex-col gap-[12px]">
                    <div class="flex gap-[12px] flex-1">
                      @include('themes.elora.pages.home-v6.sections.partials.best_seller_small_card', ['p' => $small1])
                      @include('themes.elora.pages.home-v6.sections.partials.best_seller_small_card', ['p' => $small2])
                    </div>
                    @include('themes.elora.pages.home-v6.sections.partials.best_seller_wide_card', ['p' => $wide])
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
