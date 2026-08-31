    @php
      $bestSellerProducts = $bestSelling->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-1/assets/images/product-sneaker.png');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'badge' => __('Best Seller'),
              'badgeBg' => 'var(--color-primary)',
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => '',
              'desc' => $product->centralProduct?->category?->name ?? '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
          ];
      });
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px] overflow-hidden"
      style="background: var(--color-yellow-bright)"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Best Seller
        </h2>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div class="swiper best-seller-swiper">
        <div class="swiper-wrapper" id="bestSellerWrapper">
          @forelse ($bestSellerProducts as $product)
            <div class="swiper-slide best-seller-slide">
              @include('themes.elora.pages.home-v2.sections.partials.product_card', ['p' => $product])
            </div>
          @empty
            <p class="text-sm text-black/60 py-6 w-full">{{ __('No best sellers yet.') }}</p>
          @endforelse
        </div>
      </div>
    </section>
