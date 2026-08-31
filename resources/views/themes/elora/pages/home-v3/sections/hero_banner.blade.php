    <!-- ============ HERO ============ -->
    <section class="relative" wire:ignore>
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          @php $heroBadges = ['NEW USER', 'FLASH SALE', 'NEW SEASON']; @endphp
          @forelse ($banners as $banner)
            @php
              $img = $banner->image_path ?? null;
              $imgUrl = $img
                  ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
                  : asset('elora-3/assets/images/hero-desktop.jpg');
            @endphp
            <div class="swiper-slide">
              <a href="{{ $banner->url ?? '#' }}"
                class="relative flex flex-col items-center lg:items-start justify-end h-[200px] lg:h-[524px] px-[20px] lg:px-[56px] py-[18px] lg:py-[32px] overflow-hidden"
              >
                <img
                  src="{{ $imgUrl }}"
                  alt="{{ $banner->title ?? $storeName }}"
                  class="lg:hidden absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <img
                  src="{{ $imgUrl }}"
                  alt="{{ $banner->title ?? $storeName }}"
                  class="hidden lg:block absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      90deg,
                      rgba(0, 0, 0, 0.65) 0%,
                      rgba(0, 0, 0, 0.1) 60%,
                      rgba(0, 0, 0, 0) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[8px] lg:gap-[21px] items-start justify-center w-[210px] lg:w-[530px]"
                >
                  <span
                    class="text-white font-black text-[12px] lg:text-[31px] tracking-[1px] lg:tracking-[2.6px] rounded-[4px] lg:rounded-[10px] px-[8px] lg:px-[21px] py-[2px] lg:py-[5px]"
                    style="background: var(--color-brand-pink)"
                    >{{ $heroBadges[$loop->index % count($heroBadges)] }}</span
                  >
                  <h1
                    class="font-black text-[26px] lg:text-[68px] text-white leading-[1.1]"
                  >
                    {{ $banner->title ?? $storeName }}
                  </h1>
                  @if ($banner->subtitle)
                    <p
                      class="text-[11px] lg:text-[29px] leading-tight"
                      style="color: var(--color-hero-subtitle)"
                    >
                      {{ $banner->subtitle }}
                    </p>
                  @endif
                  <button
                    type="button"
                    class="flex h-[32px] lg:h-[84px] items-center justify-center rounded-full px-[20px] lg:px-[52px] cursor-pointer mt-[8px] lg:mt-[10px]"
                    style="background: var(--color-brand-pink)"
                  >
                    <span class="font-bold text-[12px] lg:text-[31px] text-white"
                      >{{ $banner->button_text ?? __('Shop Now') }}</span
                    >
                  </button>
                </div>
              </a>
            </div>
          @empty
            <div class="swiper-slide">
              <div
                class="relative flex flex-col items-center lg:items-start justify-end h-[200px] lg:h-[524px] px-[20px] lg:px-[56px] py-[18px] lg:py-[32px] overflow-hidden"
              >
                <img
                  src="{{ asset('elora-3/assets/images/hero-mobile.jpg') }}"
                  alt=""
                  class="lg:hidden absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <img
                  src="{{ asset('elora-3/assets/images/hero-desktop.jpg') }}"
                  alt=""
                  class="hidden lg:block absolute inset-0 h-full w-full object-cover"
                  style="background: var(--color-hero-placeholder)"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      90deg,
                      rgba(0, 0, 0, 0.65) 0%,
                      rgba(0, 0, 0, 0.1) 60%,
                      rgba(0, 0, 0, 0) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[8px] lg:gap-[21px] items-start justify-center w-[210px] lg:w-[530px]"
                >
                  <h1
                    class="font-black text-[26px] lg:text-[68px] text-white leading-[1.1]"
                  >
                    {{ $storeName }}
                  </h1>
                </div>
              </div>
            </div>
          @endforelse
        </div>
        <div class="hero-pagination"></div>
      </div>
    </section>
