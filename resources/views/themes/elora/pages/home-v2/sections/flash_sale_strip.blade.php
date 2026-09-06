    @php
      $flashSaleProducts = $flashProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-1/assets/images/flash-sneaker.png');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => '',
              'desc' => $product->centralProduct?->category?->name ?? '',
              'rating' => $rating,
              'ratingLabel' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% OFF' : __('Flash Sale'),
              'badgeBg' => 'var(--color-accent-yellow)',
              'badgeText' => 'var(--color-text-primary)',
              'delivered' => 'Delivered by 24 March',
          ];
      })->values();

      $firstSale = $flashSales->first();
      $countdownRemaining = $firstSale && $firstSale->end_date ? max(0, now()->diffInSeconds($firstSale->end_date, false)) : 0;
      $countdownH = str_pad((string) intdiv($countdownRemaining, 3600), 2, '0', STR_PAD_LEFT);
      $countdownM = str_pad((string) intdiv($countdownRemaining % 3600, 60), 2, '0', STR_PAD_LEFT);
      $countdownS = str_pad((string) ($countdownRemaining % 60), 2, '0', STR_PAD_LEFT);
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[42px]"
      style="
        background: linear-gradient(
          180deg,
          var(--color-accent-purple) 0%,
          var(--color-accent-purple-light) 54%,
          var(--color-accent-purple-dark) 100%
        );
      "
    >
      <div
        class="flex flex-col lg:flex-row items-center lg:justify-between gap-[24px] lg:gap-[12px]"
      >
        <div
          class="flex flex-col items-center lg:items-start gap-[16px] lg:gap-[24px] lg:w-fit lg:shrink-0"
        >
          <div
            class="flex flex-col items-center lg:items-start tracking-[1px] lg:tracking-[1.5px]"
          >
            <p
              class="font-semibold text-white text-[40px] lg:text-[100px] leading-[1.1]"
            >
              Flash Sale
            </p>
            <p
              class="font-medium text-[20px] lg:text-[50px]"
              style="color: var(--color-accent-yellow)"
            >
              up to 50%
            </p>
          </div>
          <div
            class="bg-white flex items-center justify-center gap-[10px] lg:gap-[25px] rounded-full h-[48px] lg:h-[78px] w-full max-w-[217px] lg:max-w-[446px]"
          >
            <img
              src="{{ asset('elora-1/assets/icons/alarm-clock.svg') }}"
              class="size-[32px] lg:size-[66px]"
              alt=""
            />
            <span
              id="flashTimer"
              class="font-semibold text-[24px] lg:text-[66px] tracking-[1px]"
              style="color: var(--color-accent-purple)"
              @if($firstSale && $firstSale->end_date) data-countdown="{{ $firstSale->end_date->timestamp }}" @endif
              >{{ $countdownH }}:{{ $countdownM }}:{{ $countdownS }}</span
            >
          </div>
          <a
            href="{{ route('tenant.storefront.best-selling') }}"
            class="border-2 border-white rounded-full h-[48px] lg:h-[78px] px-[24px] lg:px-[40px] flex items-center justify-center cursor-pointer"
          >
            <span
              class="font-medium text-white text-[16px] lg:text-[29px] tracking-[1px]"
              >Explore all</span
            >
          </a>
        </div>

        <div class="swiper flash-sale-swiper w-full lg:w-auto lg:shrink-0">
          <div class="swiper-wrapper" id="flashSaleWrapper">
            @forelse ($flashSaleProducts as $p)
              <div class="swiper-slide flash-sale-slide">
                @include('themes.elora.pages.home-v2.sections.partials.flash_sale_card', ['p' => $p])
              </div>
            @empty
              <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
            @endforelse
          </div>
        </div>
      </div>
    </section>
