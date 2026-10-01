    @if ($categories->isNotEmpty())
    @php
      $fallbacks = collect(glob(public_path('elora-5/assets/images/shop-cat-*.png')))->map(fn($p) => basename($p))->values();
      $shopByCatTiles = $categories->take(5)->values()->map(function ($cat, $index) use ($fallbacks) {
          return (object) [
              'name'  => \Illuminate\Support\Str::limit($cat->translationValue('name') ?? $cat->name, 20),
              'image' => $cat->thumb_url ?? ($fallbacks->isNotEmpty() ? asset('elora-5/assets/images/' . $fallbacks->get($index % $fallbacks->count())) : null),
              'url'   => route('tenant.storefront.category', $cat->slug),
          ];
      });
    @endphp
    <!-- ============ SHOP BY CATEGORY ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      style="background: #FFFFFF" wire:ignore
    >
      <h2
        class="font-semibold text-[22px] lg:text-[32px] lg:leading-[150%] text-center tracking-[0.5px]"
        style="color: var(--color-black)"
      >
        {{ __('Shop by Category') }}
      </h2>
      <div
        class="grid grid-cols-2 lg:flex gap-[10px] lg:gap-[16px] w-full lg:w-auto"
      >
        {{-- Left column: tiles [3] + [4] (desktop only) --}}
        @if ($shopByCatTiles->get(3) || $shopByCatTiles->get(4))
          <div class="hidden lg:flex flex-col gap-[16px]">
            @if ($shopByCatTiles->get(3))
              <a href="{{ $shopByCatTiles->get(3)->url }}" class="shop-cat-tile h-[147px] lg:w-[253px]">
                <img src="{{ $shopByCatTiles->get(3)->image }}" alt="{{ $shopByCatTiles->get(3)->name }}" />
                <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(19, 32, 146, 0) 30%, rgba(19, 32, 146, 0.55) 100%);"></div>
                <span class="shop-cat-label text-[14px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]">{{ $shopByCatTiles->get(3)->name }}</span>
              </a>
            @endif
            @if ($shopByCatTiles->get(4))
              <a href="{{ $shopByCatTiles->get(4)->url }}" class="shop-cat-tile h-[147px] lg:w-[253px]">
                <img src="{{ $shopByCatTiles->get(4)->image }}" alt="{{ $shopByCatTiles->get(4)->name }}" />
                <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(19, 32, 146, 0) 30%, rgba(19, 32, 146, 0.55) 100%);"></div>
                <span class="shop-cat-label text-[14px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]">{{ $shopByCatTiles->get(4)->name }}</span>
              </a>
            @endif
          </div>
        @endif

        {{-- Center: large tile [0] --}}
        @if ($shopByCatTiles->get(0))
          <a href="{{ $shopByCatTiles->get(0)->url }}" class="shop-cat-tile col-span-2 lg:col-span-1 h-[200px] lg:h-[310px] lg:w-[253px]">
            <img src="{{ $shopByCatTiles->get(0)->image }}" alt="{{ $shopByCatTiles->get(0)->name }}" />
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
              >{{ $shopByCatTiles->get(0)->name }}</span
            >
          </a>
        @endif

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
