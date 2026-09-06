    @php
      $heroLead = ['Explore', 'Flash', 'Best'];
      $heroRest = [' New Products', ' Sale Up To 50%', ' Sellers Are Here'];
      $heroCta = [__('Shop Now'), __('Shop Deals'), __('Discover More')];
    @endphp
    <!-- ============ HERO (carousel) ============ -->
    <section class="relative">
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          @forelse ($banners as $banner)
            @php
              $img = $banner->image_path ?? null;
              $imgUrl = $img
                  ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
                  : asset('elora-5/assets/images/hero-desktop.png');
            @endphp
            <div class="swiper-slide">
              <a
                href="{{ $banner->url ?? '#' }}"
                class="relative flex items-center h-[254px] lg:h-[537px] overflow-hidden px-[18px] lg:px-[56px] py-[22px]"
              >
                <img
                  src="{{ $imgUrl }}"
                  alt="{{ $banner->title ?? $storeName }}"
                  class="absolute inset-0 h-full w-full object-cover"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      180deg,
                      rgba(19, 32, 146, 0) 0%,
                      rgba(19, 32, 146, 0.57) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[12px] lg:gap-[19px] items-start justify-center w-[150px] lg:w-[412px]"
                >
                  <h1
                    class="font-bold text-[24px] lg:text-[64px] leading-[1.05] text-white tracking-[0.5px]"
                  >
                    @if ($banner->title)
                      {{ $banner->title }}
                    @else
                      <span style="color: var(--color-primary)">{{ $heroLead[$loop->index % 3] }}</span>{{ $heroRest[$loop->index % 3] }}
                    @endif
                  </h1>
                  <button
                    type="button"
                    class="bg-white flex h-[38px] lg:h-[68px] items-center justify-center rounded-full px-[24px] lg:px-[44px] cursor-pointer w-full lg:w-auto"
                  >
                    <span
                      class="font-medium text-[14px] lg:text-[24px] tracking-[0.5px]"
                      style="color: var(--color-primary)"
                      >{{ $banner->button_text ?? $heroCta[$loop->index % 3] }}</span
                    >
                  </button>
                </div>
              </a>
            </div>
          @empty
            <div class="swiper-slide">
              <div
                class="relative flex items-center h-[254px] lg:h-[537px] overflow-hidden px-[18px] lg:px-[56px] py-[22px]"
              >
                <img
                  src="{{ asset('elora-5/assets/images/hero-desktop.png') }}"
                  alt=""
                  class="absolute inset-0 h-full w-full object-cover"
                />
                <div
                  class="absolute inset-0"
                  style="
                    background: linear-gradient(
                      180deg,
                      rgba(19, 32, 146, 0) 0%,
                      rgba(19, 32, 146, 0.57) 100%
                    );
                  "
                ></div>
                <div
                  class="relative flex flex-col gap-[12px] lg:gap-[19px] items-start justify-center w-[150px] lg:w-[412px]"
                >
                  <h1
                    class="font-bold text-[24px] lg:text-[64px] leading-[1.05] text-white tracking-[0.5px]"
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
