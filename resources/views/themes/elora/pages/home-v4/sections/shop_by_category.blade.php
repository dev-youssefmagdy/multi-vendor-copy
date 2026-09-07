    <!-- ============ SHOP BY CATEGORY ============ -->
    @if ($categories->isNotEmpty())
    @php
      $__sbcTiles = [
        ['color' => 'var(--color-tile-outerwear)', 'image' => 'tile-outerwear.png'],
        ['color' => 'var(--color-tile-footwear)', 'image' => 'tile-footwear.png'],
        ['color' => 'var(--color-tile-bags)', 'image' => 'tile-bags.png'],
        ['color' => 'var(--color-tile-watches)', 'image' => 'tile-watches.png'],
      ];
      $__sbcCategories = $categories->skip(4)->take(24)->values();
      if ($__sbcCategories->isEmpty()) {
          $__sbcCategories = $categories->take(24)->values();
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

      {{-- Carousel: mobile pages 2x2 (Swiper grid module, rows:2), desktop
           shows 6 slides per view in a single row. --}}
      <div class="relative w-full">
        <div class="swiper shop-by-category-swiper">
          <div class="swiper-wrapper" id="shopByCategoryWrapper">
            @foreach ($__sbcCategories as $i => $cat)
              @php $__tile = $__sbcTiles[$i % count($__sbcTiles)]; @endphp
              <div class="swiper-slide">
                <a
                  href="{{ route('tenant.storefront.category', $cat->slug) }}"
                  class="relative flex flex-col justify-between p-[12px] w-full h-[128px] rounded-[20px] overflow-hidden"
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
                    class="relative text-white text-[10px] tracking-[1px] uppercase"
                    style="opacity: 0.75"
                    >{{ __('Explore') }}</span
                  >
                  <div class="relative flex flex-col gap-[6px]">
                    <span class="text-white font-extrabold text-[18px] leading-[20px] line-clamp-2"
                      >{{ $cat->translationValue('name') ?? $cat->name }}</span
                    >
                    <span
                      class="category-tile-chip flex items-center justify-center rounded-full size-[24px]"
                      ><img
                        src="{{ asset('elora-4/assets/icons/icon-explore-arrow.svg') }}"
                        class="size-[12px]"
                        alt=""
                    /></span>
                  </div>
                </a>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <a
        href="{{ route('tenant.storefront.category') }}"
        class="flex items-center justify-center w-[121px] h-[38px] p-[8px] gap-[8px] rounded-[34px] border text-[14px] font-medium tracking-[0.5px] leading-[25px] cursor-pointer border-[var(--color-brand-orange)] text-[var(--color-brand-orange)] lg:w-auto lg:h-auto lg:px-[32px] lg:py-[16px] lg:rounded-full lg:text-[16px] lg:border-[var(--color-text-primary)] lg:text-[var(--color-text-primary)]"
      >
        {{ __('Explore all') }}
      </a>
    </section>
    @endif
