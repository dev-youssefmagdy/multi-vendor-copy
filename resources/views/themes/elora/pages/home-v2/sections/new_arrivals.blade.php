    @php
      $newInCards = $newInProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-1/assets/images/product-hoodie.png');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : __('New'),
              'badgeBg' => $hasDiscount ? 'var(--color-primary)' : 'var(--color-accent-purple)',
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
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] mt-12 flex flex-col gap-[16px] lg:gap-[34px]"
      style="
        background: linear-gradient(
          180deg,
          var(--color-yellow-bright) 0%,
          transparent 100%
        );
      "
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          New In
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper new-in-swiper">
          <div class="swiper-wrapper" id="newInWrapper">
            @forelse ($newInCards as $product)
              <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
                @include('themes.elora.pages.home-v2.sections.partials.product_card', ['p' => $product])
              </div>
            @empty
              <p class="text-sm text-gray-500 py-6 w-full">{{ __('No new arrivals yet.') }}</p>
            @endforelse
          </div>
        </div>
        <button
          id="newInPrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev"
        >
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="newInNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next"
        >
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
    </section>
