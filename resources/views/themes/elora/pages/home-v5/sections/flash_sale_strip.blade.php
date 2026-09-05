    @if ($flashProducts->isNotEmpty())
    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $flashSaleProducts = $flashProducts->take(6)->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-5/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          $centralProd = $product->centralProduct;
          $weightGrams = $centralProd?->weight_grams ?? $product->weight_grams ?? null;
          $weightLabel = $weightGrams
              ? ($weightGrams >= 1000 ? number_format($weightGrams / 1000, 1) . __('kg') : $weightGrams . __('g'))
              : ($centralProd?->category?->name ?: '200g');

          $manageStock = (bool) ($centralProd?->manage_stock ?? false);
          $stockQty = (int) ($centralProd?->stock ?? 0);
          $showLowStock = $manageStock && $stockQty > 0 && $stockQty <= 5;

          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $weightLabel,
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
              'stock' => '',
              'left' => $showLowStock ? __('Only :count left', ['count' => $stockQty]) : '',
          ];
      })->values();
    @endphp
    <!-- ============ FLASH SALE ============ -->
    <section
      class="relative overflow-hidden py-[24px] lg:py-[12px] flex flex-col items-center gap-[20px] lg:gap-[24px]"
      style="
        background:
          linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)),
          radial-gradient(circle at 18% 15%, #e0748a 0%, transparent 45%),
          radial-gradient(circle at 42% 8%, #e8a34f 0%, transparent 50%),
          radial-gradient(circle at 64% 28%, #e0c25a 0%, transparent 50%),
          radial-gradient(circle at 86% 18%, #5aa88c 0%, transparent 55%),
          radial-gradient(circle at 8% 75%, #8a6ac0 0%, transparent 50%),
          radial-gradient(circle at 92% 82%, #4a9aa0 0%, transparent 55%),
          linear-gradient(90deg, #d97a7a 0%, #e0a05a 30%, #d9c26a 55%, #6fae6a 75%, #4a9a94 100%);
      "
    >
      <div class="relative flex items-center gap-[10px] px-[16px] lg:px-[56px]">
        <img
          src="{{ asset('elora-5/assets/icons/icon-flash.svg') }}"
          alt=""
          class="size-[28px] lg:size-[66.14px]"
        />
        <h2
          class="font-semibold text-[26px] lg:text-[50.39px] text-white tracking-[0.5px] lg:tracking-[0.79px] lg:leading-[150%]"
        >
          Flash Sale
        </h2>
        <img
          src="{{ asset('elora-5/assets/icons/icon-flash.svg') }}"
          alt=""
          class="size-[28px] lg:size-[66.14px]"
        />
      </div>
      <div class="relative w-full">
        <div class="swiper card-swiper !ps-[16px] lg:!ps-[56px] pe-0!">
          <div class="swiper-wrapper" id="flashSaleWrapper">
            @if ($flashSaleProducts->get(0))
              <div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]" wire:key="flash-v5-{{ $flashSaleProducts[0]['id'] }}">
                @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $flashSaleProducts[0]])
              </div>
            @endif
            @if ($flashSaleProducts->get(1) || $flashSaleProducts->get(2))
              <div class="swiper-slide !w-[362px] lg:!w-[536px] !h-[302px] lg:!h-[450px]">
                <div class="flex flex-col gap-[10px] lg:gap-[18.78px] items-start h-full">
                  <div class="flex items-center gap-[6px] lg:gap-[5.4px]" @if($flashBanner?->end_date ?? null) data-countdown="{{ $flashBanner->end_date->timestamp }}" @endif>
                    <span class="font-semibold text-[12px] lg:text-[21.61px] tracking-[0.6px] lg:tracking-[0.9px] lg:leading-[150%] -rotate-90 w-0 lg:w-[32px] shrink-0 whitespace-nowrap" style="color:var(--color-yellow)">Ends in</span>
                    <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[79.22px] lg:w-[82.82px] rounded-[8px] lg:rounded-[12.6px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
                      <span class="font-semibold text-[18px] lg:text-[43.21px] lg:tracking-[0.9px] lg:leading-[150%]" style="color:var(--color-price-blue)" data-flash-timer>03</span>
                    </div>
                    <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[79.22px] lg:w-[82.82px] rounded-[8px] lg:rounded-[12.6px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
                      <span class="font-semibold text-[18px] lg:text-[43.21px] lg:tracking-[0.9px] lg:leading-[150%]" style="color:var(--color-price-blue)" data-flash-timer>06</span>
                    </div>
                    <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[79.22px] lg:w-[82.82px] rounded-[8px] lg:rounded-[12.6px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
                      <span class="font-semibold text-[18px] lg:text-[43.21px] lg:tracking-[0.9px] lg:leading-[150%]" style="color:var(--color-price-blue)" data-flash-timer>25</span>
                    </div>
                  </div>
                  @if ($flashSaleProducts->get(1))
                    @include('themes.elora.pages.home-v5.sections.partials.flash_mini_card', ['p' => $flashSaleProducts[1]])
                  @endif
                  @if ($flashSaleProducts->get(2))
                    @include('themes.elora.pages.home-v5.sections.partials.flash_mini_card', ['p' => $flashSaleProducts[2]])
                  @endif
                </div>
              </div>
            @endif
            @foreach ($flashSaleProducts->slice(3, 3) as $p)
              <div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]" wire:key="flash-v5-{{ $p['id'] }}">
                @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
      </div>
      <a
        href="{{ route('tenant.storefront.best-selling') }}"
        class="relative border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]"
          >{{ __('Shop now') }}</span
        >
      </a>
    </section>
    @endif
