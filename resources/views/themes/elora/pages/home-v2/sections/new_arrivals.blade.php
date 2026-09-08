    @php
      $newInCards = $newInProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-1/assets/images/product-hoodie.png');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
          $weightGrams = $product->centralProduct?->weight_grams ?? $product->weight_grams ?? null;
          $weightLabel = $weightGrams
              ? ($weightGrams >= 1000 ? number_format($weightGrams / 1000, 1) . __('kg') : $weightGrams . __('g'))
              : '';
          $centralProd = $product->centralProduct;
          $manageStock = (bool) ($centralProd?->manage_stock ?? false);
          $stockQty = (int) ($centralProd?->stock ?? 0);
          $showLowStock = $manageStock && $stockQty > 0 && $stockQty <= 5;
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
              'badgeLabel' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : __('New'),
              'badgeBg' => $hasDiscount ? 'var(--color-primary)' : 'var(--color-accent-purple)',
              'badgeText' => 'var(--color-white)',
              'name' => \Illuminate\Support\Str::limit($productName, 30),
              'fullName' => $productName,
              'weight' => $weightLabel,
              'desc' => $product->centralProduct?->category?->name ?? __('Premium cotton blend'),
              'rating' => $rating,
              'ratingLabel' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format($sellPrice * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
              'delivered' => $hasDiscount ? null : __('Delivered by :date', ['date' => $deliveryDate]),
              'stockLeft' => $showLowStock ? __('Only :count left', ['count' => $stockQty]) : null,
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
    <section
    wire:ignore
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] mt-12 flex flex-col gap-[16px] lg:gap-[34px]"
      style="
        background: linear-gradient(
          90deg,
          var(--color-yellow-bright) 0%,
          transparent 100%
        );
      "
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('New In') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper new-in-swiper">
          <div class="swiper-wrapper" id="newInWrapper">
            @forelse ($newInCards as $product)
              @include('themes.elora.pages.home-v2.sections.partials.new_in_card', ['p' => $product])
            @empty
              <p class="text-sm text-gray-500 py-6 w-full">{{ __('No new arrivals yet.') }}</p>
            @endforelse
          </div>
        </div>
        <button
          id="newInPrev"
          type="button"
          aria-label="{{ __('Previous') }}"
          class="swiper-nav-btn swiper-nav-prev"
        >
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90 rtl:-rotate-90"
            alt=""
          />
        </button>
        <button
          id="newInNext"
          type="button"
          aria-label="{{ __('Next') }}"
          class="swiper-nav-btn swiper-nav-next"
        >
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 rtl:rotate-90"
            alt=""
          />
        </button>
      </div>
    </section>
