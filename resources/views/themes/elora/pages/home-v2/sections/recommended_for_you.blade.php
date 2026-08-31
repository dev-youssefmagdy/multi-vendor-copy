    @php
      $recommendedCards = $recommendedProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-1/assets/images/product-sneaker.png');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : __('New In'),
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
      class="px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[34px]"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('Recommended For You') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >{{ __('see all') }}</a
        >
      </div>
      <div
        id="recommendedWrapper"
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]"
      >
        @forelse ($recommendedCards as $product)
          <div class="h-[380px] lg:h-[440px]" wire:key="recommended-v2-{{ $product['id'] }}">
            @include('themes.elora.pages.home-v2.sections.partials.product_card', ['p' => $product])
          </div>
        @empty
          <p class="text-sm text-gray-500 py-6 col-span-full">{{ __('No recommended products yet.') }}</p>
        @endforelse
      </div>
      @if ($hasMoreRecommended ?? false)
        <div wire:intersect="loadMoreRecommended" class="flex items-center justify-center py-[8px]">
          <div wire:loading wire:target="loadMoreRecommended" class="flex items-center gap-2 text-[14px]" style="color: var(--color-text-subtitle)">
            <svg class="animate-spin size-[18px]" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            {{ __('Loading more...') }}
          </div>
        </div>
      @endif
    </section>
