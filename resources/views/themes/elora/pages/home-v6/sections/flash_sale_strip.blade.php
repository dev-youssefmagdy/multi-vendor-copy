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

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
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
        <div class="flex items-center gap-[13px]">
          <h2
            class="font-semibold text-[24px] lg:text-[40px] text-white whitespace-nowrap"
          >
            Flash Sale
          </h2>
          <img
            src="{{ asset('elora-2/assets/icons/oi-flash.svg') }}"
            alt=""
            class="size-[26px] lg:size-[41px]"
          />
        </div>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="font-normal text-[14px] lg:text-[24px] tracking-[0.5px] lg:tracking-[0.8px] text-white whitespace-nowrap"
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
        class="flex items-center justify-end gap-[20px] w-full px-[16px] lg:px-[56px]"
      >
        <div class="flex items-center gap-[5px]">
          <span
            class="font-semibold text-[16px] lg:text-[20px] tracking-[0.5px] lg:tracking-[0.8px] whitespace-nowrap"
            style="
              color: var(--color-accent-yellow);
              transform: rotate(-90deg) translateY(20px);
            "
            >Ends in</span
          >
          <div
            class="border-2 border-white flex flex-col h-[52px] w-[54px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[8px] lg:rounded-[12px]"
            style="background: var(--color-accent-green)"
          >
            <span
              id="flashTimerH"
              class="font-semibold text-[24px] lg:text-[40px] text-white tracking-[0.5px] lg:tracking-[0.8px]"
              >{{ $flashH }}</span
            >
          </div>
          <div
            class="border-2 border-white flex flex-col h-[52px] w-[54px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[8px] lg:rounded-[12px]"
            style="background: var(--color-accent-green)"
          >
            <span
              id="flashTimerM"
              class="font-semibold text-[24px] lg:text-[40px] text-white tracking-[0.5px] lg:tracking-[0.8px]"
              >{{ $flashM }}</span
            >
          </div>
          <div
            class="border-2 border-white flex flex-col h-[52px] w-[54px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[8px] lg:rounded-[12px]"
            style="background: var(--color-accent-green)"
          >
            <span
              id="flashTimerS"
              class="font-semibold text-[24px] lg:text-[40px] text-white tracking-[0.5px] lg:tracking-[0.8px]"
              >{{ $flashS }}</span
            >
          </div>
        </div>
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="flex-1 bg-white h-[46px] lg:h-[63px] rounded-full flex items-center justify-center px-[16px]"
        >
          <span
            class="font-medium text-[16px] lg:text-[23px] tracking-[0.5px] lg:tracking-[0.8px]"
            style="color: var(--color-accent-green)"
            >Shop now</span
          >
        </a>
      </div>
    </section>
    @endif
