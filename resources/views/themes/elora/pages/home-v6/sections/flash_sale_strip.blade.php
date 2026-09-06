    @if ($flashSales->isNotEmpty())
    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);
      $firstFlashSale = $flashSales->first();

      $flashCards = $flashProducts->map(function ($product) use ($symbol, $rate) {
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

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% OFF' : '70% Sold',
              'badgeBg' => $hasDiscount ? 'var(--color-primary)' : 'var(--color-accent-yellow)',
              'badgeColor' => $hasDiscount ? '#fff' : 'var(--color-black)',
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightGrams > 0 ? $weightGrams . 'g' : '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : '',
          ];
      });

      $flashRemaining = $firstFlashSale->end_date ? max(0, now()->diffInSeconds($firstFlashSale->end_date, false)) : 0;
      $flashH = str_pad((string) intdiv($flashRemaining, 3600), 2, '0', STR_PAD_LEFT);
      $flashM = str_pad((string) intdiv($flashRemaining % 3600, 60), 2, '0', STR_PAD_LEFT);
      $flashS = str_pad((string) ($flashRemaining % 60), 2, '0', STR_PAD_LEFT);
    @endphp
    <!-- ============ FLASH SALE ============ -->
    <section
      class="flash-sale-stripes py-[24px] lg:py-[32px] flex flex-col gap-[24px]"
    >
      <div class="flex items-center justify-between px-[16px] lg:px-[56px]">
        <div class="flex items-center gap-2 lg:gap-3.25">
          <h2
            class="font-medium lg:font-semibold text-[18px] lg:text-[40px] max-lg:leading-5.75 text-white whitespace-nowrap"
          >
            Flash Sale
          </h2>
          <img
            src="{{ asset('elora-2/assets/icons/oi-flash.svg') }}"
            alt=""
            class="size-6.25 lg:size-[41px]"
          />
        </div>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="font-normal text-[12px] lg:text-[24px] max-lg:leading-3.75 tracking-[0.5px] lg:tracking-[0.8px] text-white whitespace-nowrap"
          >see all</a
        >
      </div>

      <div class="swiper flash-swiper w-full ps-[16px]! lg:ps-[56px]!">
        <div class="swiper-wrapper" id="flashGrid">
          @foreach ($flashCards as $p)
            <div class="swiper-slide">
              @include('themes.elora.pages.home-v6.sections.partials.flash_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>

      <div
        class="flex items-center justify-end gap-3 lg:gap-[20px] w-full px-[16px] lg:px-[56px]"
      >
        <div class="flex items-center gap-[3px] lg:gap-[5px]">
          <span
            class="font-semibold text-[12px] lg:text-[20px] max-lg:leading-[18px] tracking-[0.5px] lg:tracking-[0.8px] whitespace-nowrap"
            style="
              color: var(--color-accent-yellow);
              transform: rotate(-90deg) translateY(20px);
            "
            >Ends in</span
          >
          <div class="flex items-center gap-[3px] lg:gap-[5px] ml-1 lg:ml-2">
            <div
              class="border lg:border-2 border-white flex flex-col h-[44px] w-[46px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[7px] lg:rounded-[12px] max-lg:p-2"
              style="background: var(--color-accent-green)"
            >
              <span
                id="flashTimerH"
                class="font-semibold text-[24px] lg:text-[40px] max-lg:leading-9 text-white tracking-[0.5px] lg:tracking-[0.8px]"
                >{{ $flashH }}</span
              >
            </div>
            <div
              class="border lg:border-2 border-white flex flex-col h-[44px] w-[46px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[7px] lg:rounded-[12px] max-lg:p-2"
              style="background: var(--color-accent-green)"
            >
              <span
                id="flashTimerM"
                class="font-semibold text-[24px] lg:text-[40px] max-lg:leading-9 text-white tracking-[0.5px] lg:tracking-[0.8px]"
                >{{ $flashM }}</span
              >
            </div>
            <div
              class="border lg:border-2 border-white flex flex-col h-[44px] w-[46px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[7px] lg:rounded-[12px] max-lg:p-2"
              style="background: var(--color-accent-green)"
            >
              <span
                id="flashTimerS"
                class="font-semibold text-[24px] lg:text-[40px] max-lg:leading-9 text-white tracking-[0.5px] lg:tracking-[0.8px]"
                >{{ $flashS }}</span
              >
            </div>
          </div>
        </div>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="flex-1 bg-white h-[38px] lg:h-[63px] rounded-full flex items-center justify-center max-lg:p-2 lg:px-[16px]"
        >
          <span
            class="font-medium text-[14px] lg:text-[23px] max-lg:leading-[25px] tracking-[0.5px] lg:tracking-[0.8px]"
            style="color: var(--color-accent-green)"
            >Shop now</span
          >
        </a>
      </div>
    </section>
    @endif
