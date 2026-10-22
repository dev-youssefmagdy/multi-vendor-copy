    <!-- ============ FEATURED STRIP ============ -->
    <section style="background: var(--color-accent-green)" wire:ignore>
      <!-- Mobile: 2.25-slide carousel -->
      <div class="lg:hidden py-[12px]">
        <div class="swiper features-swiper">
          <div class="swiper-wrapper" id="featuresWrapper">
            <div class="swiper-slide flex flex-col items-center gap-[12px]">
              <img
                src="{{ asset('elora-2/assets/icons/feature-truck.svg') }}"
                alt=""
                class="size-[28px]"
              />
              <p
                class="font-normal text-[13px] tracking-[0.5px] whitespace-nowrap text-white"
              >
                {{ __('Free Shipping') }}
              </p>
            </div>
            <div class="swiper-slide flex flex-col items-center gap-[12px]">
              <img
                src="{{ asset('elora-2/assets/icons/feature-headphones.svg') }}"
                alt=""
                class="size-[28px]"
              />
              <p
                class="font-normal text-[13px] tracking-[0.5px] whitespace-nowrap text-white"
              >
                {{ __('Customer Support 24/7') }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <!-- Desktop: centered strip -->
      <div
        class="hidden lg:flex items-center justify-center gap-[52px] px-[56px] py-[13px]"
      >
        <div class="flex flex-col items-center gap-[12px]">
          <img
            src="{{ asset('elora-2/assets/icons/feature-truck.svg') }}"
            alt=""
            class="size-[33px]"
          />
          <p
            class="font-normal text-[16.6px] leading-[21px] tracking-[0.7px] whitespace-nowrap text-white"
          >
            {{ __('Free Shipping') }}
          </p>
        </div>
        <div class="flex flex-col items-center gap-[12px]">
          <img
            src="{{ asset('elora-2/assets/icons/feature-headphones.svg') }}"
            alt=""
            class="size-[33px]"
          />
          <p
            class="font-normal text-[16.6px] leading-[21px] tracking-[0.7px] whitespace-nowrap text-white"
          >
            {{ __('Customer Support 24/7') }}
          </p>
        </div>
      </div>
    </section>
