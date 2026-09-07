    @php
      $bestSellerProducts = $bestSelling->map(function ($product) use ($symbol, $rate) {
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

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('OFF') : __('70% Sold'),
              'badgeBg' => $hasDiscount ? 'var(--color-secondary)' : 'var(--color-accent-yellow)',
              'badgeText' => $hasDiscount ? 'var(--color-white)' : 'var(--color-black)',
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightLabel,
              'desc' => $product->centralProduct?->category?->name ?? 'Premium cotton blend',
              'ratingLabel' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
              'delivered' => $hasDiscount ? null : 'Delivered by 24 March',
              'stockLeft' => $hasDiscount ? 'Only 5 left' : null,
          ];
      });

      // Swiper's own `loop` feature computes its duplicate-slide buffer from
      // slidesPerView — which is "auto" here — and with exactly this many
      // real slides that estimate lands right on the boundary where Swiper
      // silently decides it doesn't have enough slides to loop safely, so
      // it never creates the duplicates needed to show neighbors before the
      // very first slide. Padding the display list ourselves with a small,
      // real (not synthetic) buffer sidesteps that heuristic entirely: the
      // "first" active slide always has genuine neighbor cards already
      // sitting next to it in the DOM, no loop math involved.
      $bestSellerBuffer = min(3, $bestSellerProducts->count());
      $bestSellerDisplay = $bestSellerBuffer > 0
          ? $bestSellerProducts->slice(-$bestSellerBuffer)->values()
              ->concat($bestSellerProducts)
              ->concat($bestSellerProducts->slice(0, $bestSellerBuffer))
          : $bestSellerProducts;
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
        <div class="swiper-wrapper" id="bestSellerWrapper" data-initial-slide="{{ $bestSellerBuffer }}">
          @forelse ($bestSellerDisplay as $product)
            <div class="swiper-slide best-seller-slide">
              @include('themes.elora.pages.home-v2.sections.partials.flash_sale_card', ['p' => $product])
            </div>
          @empty
            <p class="text-sm text-black/60 py-6 w-full">{{ __('No best sellers yet.') }}</p>
          @endforelse
        </div>
      </div>
    </section>
