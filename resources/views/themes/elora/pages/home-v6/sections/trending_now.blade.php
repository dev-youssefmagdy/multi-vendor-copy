    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      // Real 30-day sales volume per product, used to size the "ordered" progress bar.
      $__trendingProductIds = $trendingNowProducts->pluck('id')->all();
      $__trendingSoldQty = empty($__trendingProductIds) ? collect() : \App\Models\Tenant\OrderItem::query()
          ->selectRaw('COALESCE(order_items.product_id, product_variants.product_id) as product_id, SUM(order_items.qty) as total_qty')
          ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
          ->join('orders', 'orders.id', '=', 'order_items.order_id')
          ->where('orders.created_at', '>=', now()->subDays(30))
          ->where(function ($q) use ($__trendingProductIds): void {
              $q->whereIn('order_items.product_id', $__trendingProductIds)
                  ->orWhereIn('product_variants.product_id', $__trendingProductIds);
          })
          ->groupBy(\Illuminate\Support\Facades\DB::raw('COALESCE(order_items.product_id, product_variants.product_id)'))
          ->pluck('total_qty', 'product_id');
      $__trendingMaxSold = max(1, (int) ($__trendingSoldQty->max() ?? 1));

      $trendingCards = $trendingNowProducts->map(function ($product) use ($symbol, $rate, $__trendingSoldQty, $__trendingMaxSold) {
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

          $soldQty = (int) ($__trendingSoldQty->get($product->id) ?? 0);
          $progress = $soldQty > 0 ? max(8, (int) round(($soldQty / $__trendingMaxSold) * 100)) : 8;

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightGrams > 0 ? $weightGrams . 'g' : '',
              'desc' => \Illuminate\Support\Str::limit(strip_tags((string) ($product->translationValue('description') ?? '')), 40) ?: null,
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% OFF' : __('Trending'),
              'badgeBg' => $hasDiscount ? 'var(--color-primary)' : 'var(--color-accent-yellow)',
              'badgeColor' => $hasDiscount ? '#fff' : 'var(--color-black)',
              'progress' => $progress,
              'ordered' => $soldQty > 0 ? __(':count sold this month', ['count' => $soldQty]) : '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : '',
          ];
      });
    @endphp
    <!-- ============ TRENDING NOW ============ -->
    <section wire:ignore
      class="py-[24px]! lg:py-[48px]! flex flex-col gap-[16px] lg:gap-[24px] bg-[#f0f0f0]"
    >
      <div class="flex items-center justify-between px-[16px] lg:px-[56px]">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          {{ __('Trending Now') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-green)"
          >{{ __('see all') }}</a
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
