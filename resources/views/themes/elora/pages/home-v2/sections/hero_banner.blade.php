    <section class="relative">
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          @forelse ($banners as $banner)
            @php
              $img = $banner->image_path ?? null;
              $imgUrl = $img
                  ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
                  : asset('elora-1/assets/images/hero-desktop.png');
            @endphp
            <div class="swiper-slide">
              <a
                href="{{ $banner->url ?? '#' }}"
                class="relative flex flex-col items-center lg:items-start justify-end h-[238px] lg:h-[543px] px-[18px] lg:px-[56px] py-[22px] overflow-hidden"
              >
                <img
                  src="{{ $imgUrl }}"
                  alt="{{ $banner->title ?? $storeName ?? '' }}"
                  class="lg:hidden absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <img
                  src="{{ $imgUrl }}"
                  alt="{{ $banner->title ?? $storeName ?? '' }}"
                  class="hidden lg:block absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      180deg,
                      rgba(0, 0, 0, 0) 0%,
                      rgba(0, 0, 0, 0.36) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[19px] items-start justify-center w-[150px] lg:w-[412px]"
                >
                  <h1
                    class="font-semibold lg:font-bold text-[24px] lg:text-[64px] text-white tracking-[0.69px] leading-[1.05]"
                  >
                    {{ $banner->title ?? $storeName ?? 'Explore New Products' }}
                  </h1>
                  <button
                    type="button"
                    class="flex h-[38px] lg:h-[68px] items-center justify-center rounded-[34px] w-full px-[8px] cursor-pointer"
                    style="background: var(--color-accent-yellow)"
                  >
                    <span
                      class="font-medium text-[14px] lg:text-[24px] text-black tracking-[0.5px]"
                      >{{ $banner->button_text ?? 'Shop Now' }}</span
                    >
                  </button>
                </div>
              </a>
            </div>
          @empty
            <div class="swiper-slide">
              <div
                class="relative flex flex-col items-center lg:items-start justify-end h-[238px] lg:h-[543px] px-[18px] lg:px-[56px] py-[22px] overflow-hidden"
              >
                <img
                  src="{{ asset('elora-1/assets/images/hero-mobile.png') }}"
                  alt=""
                  class="lg:hidden absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <img
                  src="{{ asset('elora-1/assets/images/hero-desktop.png') }}"
                  alt=""
                  class="hidden lg:block absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      180deg,
                      rgba(0, 0, 0, 0) 0%,
                      rgba(0, 0, 0, 0.36) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[19px] items-start justify-center w-[150px] lg:w-[412px]"
                >
                  <h1
                    class="font-semibold lg:font-bold text-[24px] lg:text-[64px] text-white tracking-[0.69px] leading-[1.05]"
                  >
                    {{ $storeName ?? 'Explore New Products' }}
                  </h1>
                  <button
                    type="button"
                    class="flex h-[38px] lg:h-[68px] items-center justify-center rounded-[34px] w-full px-[8px] cursor-pointer"
                    style="background: var(--color-accent-yellow)"
                  >
                    <span
                      class="font-medium text-[14px] lg:text-[24px] text-black tracking-[0.5px]"
                      >Shop Now</span
                    >
                  </button>
                </div>
              </div>
            </div>
          @endforelse
        </div>
        <div class="hero-pagination"></div>
      </div>
    </section>
