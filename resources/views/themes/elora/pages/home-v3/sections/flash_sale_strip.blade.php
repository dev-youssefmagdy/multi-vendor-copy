    @if ($flashSales->isNotEmpty())
    @php
      $firstSale = $flashSales->first();
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $flashSaleProducts = $flashProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-3/assets/images/product-placeholder.svg');

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? '-' . (int) round((float) $pricing['discount_percentage']) . '%' : '',
          ];
      });

      $countdownRemaining = $firstSale->end_date ? max(0, now()->diffInSeconds($firstSale->end_date, false)) : 0;
      $countdownH = str_pad((string) intdiv($countdownRemaining, 3600), 2, '0', STR_PAD_LEFT);
      $countdownM = str_pad((string) intdiv($countdownRemaining % 3600, 60), 2, '0', STR_PAD_LEFT);
      $countdownS = str_pad((string) ($countdownRemaining % 60), 2, '0', STR_PAD_LEFT);
    @endphp
    <section
      class="pattern-flash-sale px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-[8px] lg:gap-[8px]">
          <img
            src="{{ asset('elora-3/assets/icons/icon-flash.svg') }}"
            alt=""
            class="size-[20px] lg:size-[40px]"
          />
          <h2 class="font-semibold text-[20px] lg:text-[32px] text-white">
            {{ __('Flash Sale') }}
          </h2>
        </div>
        <div class="flex items-center gap-[8px] lg:gap-[14px]" @if($firstSale->end_date) data-countdown="{{ $firstSale->end_date->timestamp }}" @endif>
          <span
            class="font-semibold text-[13px] lg:text-[20px] tracking-[0.5px] lg:tracking-[0.83px]"
            style="color: var(--color-accent-yellow)"
            >{{ __('Ends in') }}</span
          >
          <div class="flex items-center gap-[6px] lg:gap-[8px]">
            <span
              id="flashHours"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >{{ $countdownH }}</span
            >
            <span
              id="flashMinutes"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >{{ $countdownM }}</span
            >
            <span
              id="flashSeconds"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >{{ $countdownS }}</span
            >
          </div>
          <img
            src="{{ asset('elora-3/assets/icons/arrow-outlined.svg') }}"
            alt=""
            class="hidden lg:block h-[57px] w-auto"
          />
        </div>
      </div>
      <div class="relative">
        <div class="swiper card-swiper" id="flashSaleSwiper">
          <div class="swiper-wrapper">
            @forelse ($flashSaleProducts as $p)
              @include('themes.elora.pages.home-v3.sections.partials.flash_card', ['p' => $p])
            @empty
              <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
            @endforelse
          </div>
        </div>
        <button
          id="flashSalePrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev swiper-nav-btn-light"
        >
          <img
            src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="flashSaleNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next swiper-nav-btn-light"
        >
          <img
            src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
      <div class="flex items-center justify-center">
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="border border-white rounded-full h-[44px] lg:h-[64px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer"
        >
          <span
            class="font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px]"
            >{{ __('Explore all') }}</span
          >
        </a>
      </div>
    </section>
    @endif
