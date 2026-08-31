    <!-- ============ HERO ("NEW USER" promo) ============ -->
    <section class="relative">
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          @forelse ($banners as $banner)
          @php
            $img = $banner->image_path ?? null;
            $img = $img
              ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
              : asset('elora-4/assets/images/hero-woman.png');
            $bTitle = $banner->translationValue('title') ?? $storeName;
            $bSubtitle = $banner->translationValue('subtitle');
            $bButtonText = $banner->translationValue('button_text') ?? __('Shop Now');
            $bUrl = $banner->url ?? route('tenant.home');
          @endphp
          <div class="swiper-slide">
            <div
              class="relative flex items-end lg:items-center h-[321px] lg:h-[527px] overflow-hidden"
              style="background: var(--color-brand-orange-bright)"
            >
              <div
                class="flex flex-col gap-[18px] lg:gap-[24px] items-start justify-center px-[20px] lg:px-[74px] py-[24px] lg:py-0 w-[70%] lg:w-[548px] shrink-0"
              >
                <h1
                  class="font-black text-[28px] lg:text-[57px] leading-[1.05] text-white"
                >
                  {{ $bTitle }}
                </h1>
                @if ($bSubtitle)
                <p
                  class="font-normal text-[14px] lg:text-[24px]"
                  style="color: var(--color-hero-subtitle)"
                >
                  {{ $bSubtitle }}
                </p>
                @endif
                <a
                  href="{{ $bUrl }}"
                  class="bg-white flex h-[38px] lg:h-[70px] items-center justify-center rounded-full px-[24px] lg:px-[44px] cursor-pointer"
                >
                  <span
                    class="font-bold text-[14px] lg:text-[26px]"
                    style="color: var(--color-brand-orange)"
                    >{{ $bButtonText }}</span
                  >
                </a>
              </div>
              <div
                class="absolute right-0 top-0 h-full w-[45%] lg:w-[892px] lg:static lg:flex-1"
              >
                <img
                  src="{{ $img }}"
                  alt="{{ $bTitle }}"
                  class="h-full w-full object-cover"
                />
              </div>
            </div>
          </div>
          @empty
          <div class="swiper-slide">
            <div
              class="relative flex items-end lg:items-center h-[321px] lg:h-[527px] overflow-hidden"
              style="background: var(--color-brand-orange-bright)"
            >
              <div
                class="flex flex-col gap-[18px] lg:gap-[24px] items-start justify-center px-[20px] lg:px-[74px] py-[24px] lg:py-0 w-[70%] lg:w-[548px] shrink-0"
              >
                <div
                  class="bg-white rounded-[8px] px-[14px] lg:px-[18px] py-[3px] lg:py-[4px]"
                >
                  <p
                    class="font-black text-[16px] lg:text-[26px] tracking-[1.3px] lg:tracking-[2.2px]"
                    style="color: var(--color-brand-orange)"
                  >
                    {{ __('NEW USER') }}
                  </p>
                </div>
                <h1
                  class="font-black text-[28px] lg:text-[57px] leading-[1.05] text-white"
                >
                  {{ __('Explore New') }}<br />{{ __('Products') }}
                </h1>
                <p
                  class="font-normal text-[14px] lg:text-[24px]"
                  style="color: var(--color-hero-subtitle)"
                >
                  {{ __('New user exclusive deal') }}
                </p>
                <a
                  href="{{ route('tenant.home') }}"
                  class="bg-white flex h-[38px] lg:h-[70px] items-center justify-center rounded-full px-[24px] lg:px-[44px] cursor-pointer"
                >
                  <span
                    class="font-bold text-[14px] lg:text-[26px]"
                    style="color: var(--color-brand-orange)"
                    >{{ __('Shop Now') }}</span
                  >
                </a>
              </div>
              <div
                class="absolute right-0 top-0 h-full w-[45%] lg:w-[892px] lg:static lg:flex-1"
              >
                <img
                  src="{{ asset('elora-4/assets/images/hero-woman.png') }}"
                  alt="{{ __('New user exclusive deal') }}"
                  class="h-full w-full object-cover"
                />
              </div>
            </div>
          </div>
          @endforelse
        </div>
        <div class="hero-pagination"></div>
      </div>
    </section>
