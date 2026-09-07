    @php
      $__heroFallbackTitles = ['Explore New Products', 'Flash Sale Up To 50%', 'New Season Arrivals', 'Free Shipping On Every Order'];
      $__heroFallbackCtas = ['Shop Now', 'Shop Deals', 'Discover More', 'Start Shopping'];
    @endphp
    <!-- ============ HERO ============ -->
    <section class="relative">
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          @forelse ($banners as $banner)
            @php
              $__heroImg = $banner->image_path ?? null;
              $__heroImgUrl = $__heroImg
                  ? (filter_var($__heroImg, FILTER_VALIDATE_URL) ? $__heroImg : asset('storage/' . ltrim($__heroImg, '/')))
                  : asset('elora-2/assets/images/hero-desktop.png');
            @endphp
            <div class="swiper-slide">
              <a href="{{ $banner->url ?? '#' }}"
                class="relative flex flex-col items-center lg:items-start justify-end h-[254px] lg:h-[529px] px-[18px] lg:px-[56px] py-[22px] overflow-hidden"
              >
                <img
                  src="{{ $__heroImgUrl }}"
                  alt="{{ $banner->title ?? $storeName }}"
                  class="lg:hidden absolute inset-0 h-full w-full object-cover"
                />
                <img
                  src="{{ $__heroImgUrl }}"
                  alt="{{ $banner->title ?? $storeName }}"
                  class="hidden lg:block absolute inset-0 h-full w-full object-cover"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      270deg,
                      rgba(1, 152, 66, 0) 8%,
                      rgba(1, 152, 66, 0.56) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[19px] items-start justify-center w-[150px] lg:w-[412px]"
                >
                  <h1
                    class="font-semibold lg:font-bold text-[24px] lg:text-[64px] text-white tracking-[0.69px] leading-[1.05] lg:leading-[80px]"
                  >
                    {{ $banner->title ?? $storeName }}
                  </h1>
                  <button
                    type="button"
                    class="flex h-[38px] lg:h-[68px] items-center justify-center rounded-[34px] w-full px-[8px] cursor-pointer bg-white"
                  >
                    <span
                      class="font-medium text-[14px] lg:text-[24px] lg:leading-[25px] tracking-[0.5px]"
                      style="color: var(--color-accent-green)"
                      >{{ $banner->button_text ?? 'Shop Now' }}</span
                    >
                  </button>
                </div>
              </a>
            </div>
          @empty
            @foreach ($__heroFallbackTitles as $__i => $__title)
              <div class="swiper-slide">
                <div
                  class="relative flex flex-col items-center lg:items-start justify-end h-[254px] lg:h-[529px] px-[18px] lg:px-[56px] py-[22px] overflow-hidden"
                >
                  <img
                    src="{{ asset('elora-2/assets/images/hero-mobile.png') }}"
                    alt=""
                    class="lg:hidden absolute inset-0 h-full w-full object-cover"
                  />
                  <img
                    src="{{ asset('elora-2/assets/images/hero-desktop.png') }}"
                    alt=""
                    class="hidden lg:block absolute inset-0 h-full w-full object-cover"
                  />
                  <div
                    class="absolute inset-0"
                    style="
                      background: linear-gradient(
                        270deg,
                        rgba(1, 152, 66, 0) 8%,
                        rgba(1, 152, 66, 0.56) 100%
                      );
                    "
                  ></div>
                  <div
                    class="relative flex flex-col gap-[19px] items-start justify-center w-[150px] lg:w-[412px]"
                  >
                    <h1
                      class="font-semibold lg:font-bold text-[24px] lg:text-[64px] text-white tracking-[0.69px] leading-[1.05]"
                    >
                      {{ $__title }}
                    </h1>
                    <button
                      type="button"
                      class="flex h-[38px] lg:h-[68px] items-center justify-center rounded-[34px] w-full px-[8px] cursor-pointer bg-white"
                    >
                      <span
                        class="font-medium text-[14px] lg:text-[24px] tracking-[0.5px]"
                        style="color: var(--color-accent-green)"
                        >{{ $__heroFallbackCtas[$__i] }}</span
                      >
                    </button>
                  </div>
                </div>
              </div>
            @endforeach
          @endforelse
        </div>
        <div class="hero-pagination"></div>
      </div>
    </section>
