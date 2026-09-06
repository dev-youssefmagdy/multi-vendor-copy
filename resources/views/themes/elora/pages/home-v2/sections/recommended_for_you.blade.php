    @php
      $recommendedCards = $recommendedProducts->map(function ($product) use ($symbol, $rate) {
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
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightLabel,
              'desc' => $product->centralProduct?->category?->name ?? '',
              'rating' => $rating,
              'ratingLabel' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
              'delivered' => $hasDiscount ? null : 'Delivered by 24 March',
              'stockLeft' => $hasDiscount ? 'Only 5 left' : null,
          ];
      });
    @endphp
    <section class="relative px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px] overflow-hidden">
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
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[24px]"
      >
        @forelse ($recommendedCards as $p)
          @php
            $__badgeCycle = $loop->index % 3;
            if ($__badgeCycle === 0) {
                $p['badgeLabel'] = __('New In');
                $p['badgeBg'] = 'var(--color-accent-purple)';
                $p['badgeText'] = 'var(--color-white)';
            } elseif ($__badgeCycle === 1) {
                $p['badgeLabel'] = !empty($p['discount']) ? $p['discount'] : __('30% Off');
                $p['badgeBg'] = 'var(--color-error)';
                $p['badgeText'] = 'var(--color-white)';
            } else {
                $p['badgeLabel'] = __('70% Sold');
                $p['badgeBg'] = 'var(--color-accent-yellow)';
                $p['badgeText'] = 'var(--color-black)';
            }
          @endphp
          <div class="h-full" wire:key="recommended-v2-{{ $p['id'] }}">
            @include('themes.elora.pages.home-v2.sections.partials.recommended_card', ['p' => $p])
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

      {{-- Right-edge fade hinting the grid continues --}}
      <div
        class="hidden lg:block absolute top-0 right-0 h-full w-[120px] pointer-events-none"
        style="background: linear-gradient(91.97deg, rgba(255, 255, 255, 0) 1.85%, #F3F3F3 95.21%);"
      ></div>
    </section>
