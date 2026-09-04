    <!-- ============ SHOP BY CATEGORY ============ -->
    @if ($categories->isNotEmpty())
    @php
      $__sbcTiles = [
        ['color' => 'var(--color-tile-outerwear)', 'image' => 'tile-outerwear.png'],
        ['color' => 'var(--color-tile-footwear)', 'image' => 'tile-footwear.png'],
        ['color' => 'var(--color-tile-bags)', 'image' => 'tile-bags.png'],
        ['color' => 'var(--color-tile-watches)', 'image' => 'tile-watches.png'],
      ];
      $__sbcCategories = $categories->skip(4)->take(5)->values();
      if ($__sbcCategories->isEmpty()) {
          $__sbcCategories = $categories->take(5)->values();
      }
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      style="background: var(--color-bg-main)"
      wire:ignore
    >
      <h2
        class="font-semibold text-[22px] lg:text-[32px] text-black text-center tracking-[0.5px]"
      >
        {{ __('Shop by Category') }}
      </h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex gap-[12px] lg:gap-[24px] w-full">
        @foreach ($__sbcCategories as $i => $cat)
        @php $__tile = $__sbcTiles[$i % count($__sbcTiles)]; @endphp
        <a
          href="{{ route('tenant.storefront.category', $cat->slug) }}"
          class="relative rounded-[16px] lg:rounded-[20px] overflow-hidden flex flex-col justify-between p-[10px] lg:p-[12px] h-[100px] lg:h-[128px] lg:flex-1"
          style="background: {{ $__tile['color'] }}"
        >
          @if ($cat->thumb_url ?? null)
            <img
              src="{{ $cat->thumb_url }}"
              alt=""
              class="absolute inset-0 h-full w-full object-cover opacity-90"
            />
          @else
            <img
              src="{{ asset('elora-4/assets/images/' . $__tile['image']) }}"
              alt=""
              class="absolute inset-0 h-full w-full object-cover opacity-90"
            />
          @endif
          <div
            class="absolute inset-0"
            style="
              background: linear-gradient(
                105deg,
                {{ $__tile['color'] }} 41.55%,
                rgba(0, 0, 0, 0) 42.39%
              );
            "
          ></div>
          <span
            class="relative text-white text-[9px] lg:text-[10px] tracking-[1px] uppercase"
            style="opacity: 0.75"
            >{{ __('Explore') }}</span
          >
          <div class="relative flex flex-col gap-[6px]">
            <span class="text-white font-extrabold text-[14px] lg:text-[18px]"
              >{{ $cat->translationValue('name') ?? $cat->name }}</span
            >
            <span
              class="category-tile-chip flex items-center justify-center rounded-full size-[20px] lg:size-[24px]"
              ><img
                src="{{ asset('elora-4/assets/icons/icon-explore-arrow.svg') }}"
                class="size-[10px] lg:size-[12px]"
                alt=""
            /></span>
          </div>
        </a>
        @endforeach
      </div>
      <a
        href="{{ route('tenant.storefront.category') }}"
        class="border rounded-full px-[32px] py-[16px] text-[14px] lg:text-[16px] font-medium cursor-pointer"
        style="
          border-color: var(--color-text-primary);
          color: var(--color-text-primary);
        "
      >
        {{ __('Explore all') }}
      </a>
    </section>
    @endif
