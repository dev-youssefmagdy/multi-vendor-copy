    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $newInCards = $newInProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-5/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 25),
              'weight' => $product->centralProduct?->category?->name ?? '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
              'stock' => '',
          ];
      });
    @endphp
    <!-- ============ NEW IN ============ -->
    <section
      class="newin-bg ps-[16px] lg:ps-[56px] py-[24px] lg:py-[38px] flex flex-col gap-[16px] lg:gap-[24px] mt-12"
    >
      <img
        src="{{ asset('elora-5/assets/images/new-in-texture.png') }}"
        alt=""
        class="newin-texture"
      />
      <div class="relative flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          {{ __('New In') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper newin-swiper">
          <div class="swiper-wrapper" id="newInWrapper">
            @foreach ($newInCards as $p)
              <div class="swiper-slide h-auto !w-[280px] lg:!w-[430px]" wire:key="newin-v5-{{ $p['id'] }}">
                @include('themes.elora.pages.home-v5.sections.partials.wide_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
