    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $recommendedCards = ($recommendedProducts->isEmpty() ? $bestSelling : $recommendedProducts)->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-3/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : __('New In'),
              'badgeBg' => $hasDiscount ? 'var(--color-primary)' : 'var(--color-accent-purple)',
              'badgeText' => 'var(--color-white)',
              'name' => $product->translationValue('name') ?? $product->slug,
              'weight' => '',
              'desc' => $product->centralProduct?->category?->name ?? '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
          ];
      });
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          {{ __('Recommended For You') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-text-primary)"
          >{{ __('see all') }}</a
        >
      </div>
      <div
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]"
      >
        @forelse ($recommendedCards as $p)
          <div>
            @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p])
          </div>
        @empty
          <p class="text-sm text-gray-500 py-6 col-span-full">{{ __('No recommended products yet.') }}</p>
        @endforelse
      </div>
    </section>
