    @if ($categories->isNotEmpty())
    <section
      class="bg-[#FE3A6A] lg:bg-transparent mt-[16px] lg:mt-0 px-[16px] lg:px-[56px] py-[16px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      wire:ignore
    >
      <h2
        class="font-semibold text-[22px] lg:text-[32px] tracking-[0.5px] text-center text-white lg:text-(--color-text-primary)"
      >
        {{ __('Shop by Category') }}
      </h2>
      <div class="relative w-full">
        <div class="swiper card-swiper shopbycategory-swiper" id="shopByCategorySwiper">
          <div class="swiper-wrapper">
            @php $shopByCatFallbackImgs = ['shop-womens-fashion.jpg', 'shop-mens-fashion.jpg', 'shop-coastal-chic.jpg']; @endphp
            @foreach ($categories as $cat)
              @php
                $catName = $cat->translationValue('name') ?? $cat->name;
                $catNameDisplay = \Illuminate\Support\Str::limit($catName, 20);
              @endphp
              <div class="swiper-slide !w-[145px] lg:!w-[240.37px]">
                <a
                  href="{{ route('tenant.storefront.category', $cat->slug) }}"
                  class="relative block rounded-[16px] lg:rounded-[25.47px] overflow-hidden flex items-end justify-start p-[16px] lg:p-[19.1px] h-[220px] lg:h-[382.04px] w-[145px] lg:w-[240.37px]"
                >
                  <img
                    src="{{ $cat->thumb_url ?? asset('elora-3/assets/images/' . $shopByCatFallbackImgs[$loop->index % 3]) }}"
                    alt="{{ $catName }}"
                    class="absolute inset-0 h-full w-full object-cover"
                  />
                  <div
                    class="absolute inset-0"
                    style="
                      background: linear-gradient(
                        0deg,
                        rgba(0, 0, 0, 0.75) 0%,
                        rgba(0, 0, 0, 0) 55%
                      );
                    "
                  ></div>
                  <div class="relative flex flex-col items-start">
                    <span class="text-white font-bold text-[14px] leading-[20px] lg:text-[22.29px] lg:leading-[33px]"
                      >{{ $catNameDisplay }}</span
                    >
                    <span
                      class="mt-[2px] lg:mt-[3px] text-[11px] leading-[16px] lg:text-[15.92px] lg:leading-[24px] font-normal"
                      style="color: rgba(255, 255, 255, 0.75)"
                      >{{ number_format($cat->products_count ?? 0) }} {{ __('items') }}</span
                    >
                    <span
                      class="mt-[10px] lg:mt-[12.73px] rounded-full text-white font-bold text-[11px] lg:text-[19.1px] px-[14px] lg:px-[22px] py-[6px] lg:py-[9.55px]"
                      style="background: #FE3A6A"
                      >{{ __('Shop') }} →</span
                    >
                  </div>
                </a>
              </div>
            @endforeach
          </div>
        </div>

      </div>
      <a
        href="{{ route('tenant.storefront.category') }}"
        class="border rounded-full px-[32px] py-[16px] text-[14px] lg:text-[16px] font-medium cursor-pointer border-white text-white lg:border-(--color-text-primary) lg:text-(--color-text-primary)"
      >
        {{ __('Explore all') }}
      </a>
    </section>
    @endif
