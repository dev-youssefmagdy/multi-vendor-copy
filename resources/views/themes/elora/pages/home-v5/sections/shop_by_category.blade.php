    @if ($categories->isNotEmpty())
    @php
      $shopByCatFallbackImgs = ['shop-cat-accessories.png', 'shop-cat-fashion.png', 'shop-cat-electronics.png'];
      $shopByCatTiles = $categories->take(3)->map(function ($cat, $index) use ($shopByCatFallbackImgs) {
          return [
              'name' => \Illuminate\Support\Str::limit($cat->translationValue('name') ?? $cat->name, 20),
              'image' => $cat->thumb_url ?? asset('elora-5/assets/images/' . $shopByCatFallbackImgs[$index % 3]),
              'url' => route('tenant.storefront.category', $cat->slug),
          ];
      })->values();
    @endphp
    <!-- ============ SHOP BY CATEGORY ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      style="background: #FFFFFF"
    >
      <h2
        class="font-semibold text-[22px] lg:text-[32px] lg:leading-[150%] text-center tracking-[0.5px]"
        style="color: var(--color-black)"
      >
        {{ __('Shop by Category') }}
      </h2>
      <div
        class="grid grid-cols-2 lg:flex gap-[10px] lg:gap-[16px] w-full lg:w-auto lg:max-w-[790px]"
      >
        @if ($shopByCatTiles->get(0))
          <a href="{{ $shopByCatTiles[0]['url'] }}" class="shop-cat-tile h-[200px] lg:h-[310px] lg:w-[253px]">
            <img src="{{ $shopByCatTiles[0]['image'] }}" alt="{{ $shopByCatTiles[0]['name'] }}" />
            <div
              class="absolute inset-0"
              style="
                background: linear-gradient(
                  180deg,
                  rgba(19, 32, 146, 0) 40%,
                  rgba(19, 32, 146, 0.55) 100%
                );
              "
            ></div>
            <span class="shop-cat-label text-[16px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]"
              >{{ $shopByCatTiles[0]['name'] }}</span
            >
          </a>
        @endif
        <div class="flex flex-col gap-[10px] lg:gap-[16px]">
          @if ($shopByCatTiles->get(1))
            <a href="{{ $shopByCatTiles[1]['url'] }}" class="shop-cat-tile h-[95px] lg:h-[147px] lg:w-[253px]">
              <img src="{{ $shopByCatTiles[1]['image'] }}" alt="{{ $shopByCatTiles[1]['name'] }}" />
              <div
                class="absolute inset-0"
                style="
                  background: linear-gradient(
                    180deg,
                    rgba(19, 32, 146, 0) 30%,
                    rgba(19, 32, 146, 0.55) 100%
                  );
                "
              ></div>
              <span class="shop-cat-label text-[14px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]"
                >{{ $shopByCatTiles[1]['name'] }}</span
              >
            </a>
          @endif
          @if ($shopByCatTiles->get(2))
            <a href="{{ $shopByCatTiles[2]['url'] }}" class="shop-cat-tile h-[95px] lg:h-[147px] lg:w-[253px]">
              <img
                src="{{ $shopByCatTiles[2]['image'] }}"
                alt="{{ $shopByCatTiles[2]['name'] }}"
              />
              <div
                class="absolute inset-0"
                style="
                  background: linear-gradient(
                    180deg,
                    rgba(19, 32, 146, 0) 30%,
                    rgba(19, 32, 146, 0.55) 100%
                  );
                "
              ></div>
              <span class="shop-cat-label text-[14px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]"
                >{{ $shopByCatTiles[2]['name'] }}</span
              >
            </a>
          @endif
        </div>
      </div>
      <a
        href="{{ route('tenant.storefront.category') }}"
        class="border rounded-full px-[32px] py-[14px] lg:px-0 lg:py-0 lg:w-[208px] lg:h-[64px] flex items-center justify-center text-[14px] lg:text-[20px] lg:leading-[25px] tracking-[0.5px] font-medium cursor-pointer"
        style="border-color: var(--color-primary); color: var(--color-primary)"
      >
        {{ __('Explore all') }}
      </a>
    </section>
    @endif
