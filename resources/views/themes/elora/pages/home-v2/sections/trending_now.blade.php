    @php
      $trendingProducts = $trendingNowProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-1/assets/images/product-sneaker.png');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
          $weightGrams = $product->centralProduct?->weight_grams ?? $product->weight_grams ?? null;
          $weightLabel = $weightGrams
              ? ($weightGrams >= 1000 ? number_format($weightGrams / 1000, 1) . __('kg') : $weightGrams . __('g'))
              : '';
          $deliveryDate = \Carbon\Carbon::now()->addDays(3)->translatedFormat('d F');
          $sellPrice = (float) $pricing['current_price'];
          $activeVariants = $product->variants->where('active', true)->values();
          $hasMultipleVariants = $activeVariants->count() > 1;
          $variantModalData = $hasMultipleVariants
              ? $activeVariants->map(fn($v) => [
                  'id' => $v->id,
                  'label' => $v->centralVariant?->title ?? __('Variant #:id', ['id' => $v->id]),
                  'price' => $symbol . number_format((float) $product->storefrontPricing($v)['current_price'] * $rate, 2),
                  'inStock' => (int) $v->stock > 0,
              ])->values()->all()
              : null;
          $productName = $product->translationValue('name') ?? $product->slug;
          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($productName, 30),
              'fullName' => $productName,
              'weight' => $weightLabel,
              'desc' => $product->centralProduct?->category?->name ?? __('Premium cotton blend'),
              'badge' => __('Trending'),
              'badgeBg' => 'var(--color-accent-yellow)',
              'badgeText' => 'var(--color-black)',
              'progress' => min(100, (int) round($rating / 5 * 100)),
              'ordered' => __('Trending this week'),
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format($sellPrice * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
              'delivered' => __('Delivered by :date', ['date' => $deliveryDate]),
              'isOutOfStock' => $product->stockStatus() === 'out_of_stock',
              'hasMultipleVariants' => $hasMultipleVariants,
              'variantModalData' => $variantModalData,
              'favData' => json_encode([
                  'slug' => $product->slug,
                  'name' => $productName,
                  'price' => round($sellPrice * $rate, 2),
                  'old_price' => $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null,
                  'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
                  'rating' => $rating,
                  'image' => $img,
                  'url' => route('tenant.storefront.product', $product->slug),
                  'badge' => null,
                  'added' => time(),
              ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
          ];
      });
    @endphp
    <section wire:ignore
      class=" py-12 flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between px-[16px] lg:px-[56px]">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('Trending Now') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="relative ps-[16px] lg:ps-[56px]">
        <div class="swiper card-swiper trending-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @forelse ($trendingProducts as $product)
              @include('themes.elora.pages.home-v2.sections.partials.trending_card', ['p' => $product])
            @empty
              <p class="text-sm text-gray-500 py-6 w-full">{{ __('No trending products yet.') }}</p>
            @endforelse
          </div>
        </div>

      </div>
    </section>
