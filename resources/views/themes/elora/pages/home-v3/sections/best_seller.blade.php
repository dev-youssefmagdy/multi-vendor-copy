    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $bestSellerProducts = $bestSelling->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-3/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'desc' => $product->centralProduct?->category?->name,
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
          ];
      });
    @endphp
    <section
      class="pattern-bestseller px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      wire:ignore
    >
      <h2 class="font-medium text-[22px] lg:text-[32px] text-white">
        {{ __('Best Seller') }}
      </h2>
      @if ($bestSellerProducts->isEmpty())
        <p class="text-sm text-white/70 py-6 w-full">{{ __('No best sellers yet.') }}</p>
      @else
        <div class="swiper bestseller-swiper w-full" id="bestSellerSwiper">
          <div class="swiper-wrapper" id="bestSellerWrapper">
            @foreach ($bestSellerProducts as $p)
              @include('themes.elora.pages.home-v3.sections.partials.best_seller_card', ['p' => $p])
            @endforeach
          </div>
        </div>
      @endif
      <a
        href="{{ route('tenant.storefront.best-selling') }}"
        class="border border-white rounded-full h-[44px] lg:h-[64px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px]"
          >{{ __('Explore all') }}</span
        >
      </a>
    </section>
