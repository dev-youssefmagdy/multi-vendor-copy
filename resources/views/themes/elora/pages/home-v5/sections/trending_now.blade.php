    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $trendingProducts = $trendingNowProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-5/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          // Favorite / cart wiring (mirrors _product-card.blade.php)
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
          $isOutOfStock = $product->stockStatus() === 'out_of_stock';
          $sellPrice = (float) $pricing['current_price'];
          $displayReal = $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null;
          $discountPct = $hasDiscount ? (int) round((float) $pricing['discount_percentage']) : 0;
          $favData = json_encode([
              'slug' => $product->slug,
              'name' => $product->translationValue('name') ?? $product->slug,
              'price' => round($sellPrice * $rate, 2),
              'old_price' => $displayReal,
              'discount' => $hasDiscount ? $discountPct . '% Off' : null,
              'rating' => $rating,
              'image' => $img,
              'url' => route('tenant.storefront.product', $product->slug),
              'badge' => null,
              'added' => time(),
          ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
          $deliveryDate = \Carbon\Carbon::now()->addDays(3)->translatedFormat('d F');

          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'nameJs' => $product->translationValue('name') ?? $product->slug,
              'description' => $product->centralProduct?->category?->name ?? '',
              'weight' => $product->centralProduct?->category?->name ?? '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
              'stock' => '',
              'delivery' => __('Delivered by') . ' ' . $deliveryDate,
              'favData' => $favData,
              'isOutOfStock' => $isOutOfStock,
              'hasMultipleVariants' => $hasMultipleVariants,
              'variantModalData' => $variantModalData,
          ];
      });
    @endphp
    <!-- ============ TRENDING NOW ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[28px]"
      style="background: var(--color-page-bg)"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          {{ __('Trending Now') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper trending-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @foreach ($trendingProducts as $p)
              <div class="swiper-slide" wire:key="trending-v5-{{ $p['id'] }}">
                @include('themes.elora.pages.home-v5.sections.partials.trending_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
