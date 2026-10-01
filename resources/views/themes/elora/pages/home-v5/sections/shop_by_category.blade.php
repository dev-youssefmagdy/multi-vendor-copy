    @if ($categories->isNotEmpty())
    @php
        $shopByCatChunks = $categories->values()->chunk(3);
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

      <div class="swiper shop-by-cat-swiper w-full" id="shopByCatSwiper">
        <div class="swiper-wrapper">
          @foreach ($shopByCatChunks as $chunk)
            @php $tiles = $chunk->values(); @endphp
            <div class="swiper-slide !w-auto">
              <div class="flex gap-[10px] lg:gap-[16px]">

                {{-- Side column: tiles [1] + [2] --}}
                @if ($tiles->get(1) || $tiles->get(2))
                  <div class="flex flex-col gap-[10px] lg:gap-[16px]">
                    @foreach ([1, 2] as $sideIdx)
                      @php $tile = $tiles->get($sideIdx); @endphp
                      @if ($tile)
                        @php
                          $tileSlug = $tile->translationValue('slug') ?? $tile->centralCategory?->translationValue('slug') ?? $tile->slug;
                          $tileName = \Illuminate\Support\Str::limit($tile->translationValue('name') ?? $tile->name, 20);
                        @endphp
                        <a href="{{ route('tenant.storefront.category', $tileSlug) }}"
                           class="shop-cat-tile h-[95px] w-[120px] lg:h-[147px] lg:w-[253px]">
                          @if ($tile->thumb_url)
                            <img src="{{ $tile->thumb_url }}" alt="{{ $tileName }}" />
                          @else
                            <div style="position:absolute;inset:0;background:linear-gradient(135deg,#e8eaf6,#c5cae9);"></div>
                          @endif
                          <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(19, 32, 146, 0) 30%, rgba(19, 32, 146, 0.55) 100%);"></div>
                          <span class="shop-cat-label text-[12px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]">{{ $tileName }}</span>
                        </a>
                      @endif
                    @endforeach
                  </div>
                @endif

                {{-- Large tile [0] --}}
                @if ($tiles->get(0))
                  @php
                    $mainTile = $tiles->get(0);
                    $mainSlug = $mainTile->translationValue('slug') ?? $mainTile->centralCategory?->translationValue('slug') ?? $mainTile->slug;
                    $mainName = \Illuminate\Support\Str::limit($mainTile->translationValue('name') ?? $mainTile->name, 20);
                  @endphp
                  <a href="{{ route('tenant.storefront.category', $mainSlug) }}"
                     class="shop-cat-tile h-[200px] w-[160px] lg:h-[310px] lg:w-[253px]">
                    @if ($mainTile->thumb_url)
                      <img src="{{ $mainTile->thumb_url }}" alt="{{ $mainName }}" />
                    @else
                      <div style="position:absolute;inset:0;background:linear-gradient(135deg,#e8eaf6,#c5cae9);"></div>
                    @endif
                    <div
                      class="absolute inset-0"
                      style="background: linear-gradient(180deg, rgba(19, 32, 146, 0) 40%, rgba(19, 32, 146, 0.55) 100%);"
                    ></div>
                    <span class="shop-cat-label text-[16px] lg:text-[24px] lg:leading-[150%] lg:tracking-[0.66px]">{{ $mainName }}</span>
                  </a>
                @endif

              </div>
            </div>
          @endforeach
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

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        new Swiper('#shopByCatSwiper', {
          slidesPerView: 'auto',
          spaceBetween: 10,
          freeMode: true,
          breakpoints: {
            1024: {
              spaceBetween: 16,
            },
          },
        });
      });
    </script>
    @endif
