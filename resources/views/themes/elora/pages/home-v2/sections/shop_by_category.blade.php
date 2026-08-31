    @if ($categories->isNotEmpty())
    @php
      $shopByCatFallbackImgs = ['shop-accessories.png', 'shop-fashion.png', 'shop-electronics.png', 'shop-furniture.png', 'shop-presents.png', 'shop-decor.png'];
      $shopByCatRowSpan = [true, false, false, true, false, false];
      $shopByCatData = $categories->take(6)->values()->map(function ($cat, $i) use ($shopByCatFallbackImgs) {
          $catName = $cat->translationValue('name') ?? $cat->name;
          return [
              'url' => route('tenant.storefront.category', $cat->slug),
              'name' => \Illuminate\Support\Str::limit($catName, 20),
              'image' => $cat->thumb_url ?? asset('elora-1/assets/images/' . $shopByCatFallbackImgs[$i % count($shopByCatFallbackImgs)]),
          ];
      });
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[56px] flex flex-col items-center gap-[16px] lg:gap-[32px]"
    >
      <h2 class="font-medium text-[22px] lg:text-[32px] text-black text-center">
        Shop by Category
      </h2>
      <div
        class="grid grid-cols-2 lg:grid-cols-4 lg:grid-rows-2 gap-[10px] lg:gap-[12px] w-full lg:h-[330px]"
      >
        @foreach ($shopByCatData as $i => $cat)
          <a
            href="{{ $cat['url'] }}"
            class="relative rounded-[8px] overflow-hidden flex {{ $shopByCatRowSpan[$i % count($shopByCatRowSpan)] ? 'items-end' : 'items-center' }} justify-center p-[16px] h-[150px] lg:h-auto {{ $shopByCatRowSpan[$i % count($shopByCatRowSpan)] ? 'lg:row-span-2' : '' }}"
          >
            <img
              src="{{ $cat['image'] }}"
              alt="{{ $cat['name'] }}"
              class="absolute inset-0 h-full w-full object-cover"
            />
            <div
              class="absolute inset-0"
              style="
                background: linear-gradient(
                  0deg,
                  rgba(0, 0, 0, 0.79) 0%,
                  rgba(138, 56, 245, 0.09) 100%
                );
              "
            ></div>
            <span
              class="relative text-white font-medium text-[16px] lg:text-[24px] tracking-[0.5px] truncate max-w-full px-2"
              >{{ $cat['name'] }}</span
            >
          </a>
        @endforeach
      </div>
      <a
        href="{{ route('tenant.storefront.category') }}"
        class="border rounded-full px-[32px] py-[16px] text-[14px] lg:text-[16px] font-medium cursor-pointer"
        style="
          border-color: var(--color-stroke);
          color: var(--color-text-primary);
        "
      >
        Explore all
      </a>
    </section>
    @endif
