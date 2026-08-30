    <!-- ============ FLASH SALE ============ -->
    <section
      class="relative overflow-hidden px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[20px]"
      style="
        background: linear-gradient(
          120deg,
          var(--color-flash-rainbow-pink) 0%,
          var(--color-flash-rainbow-orange) 25%,
          var(--color-flash-rainbow-yellow) 50%,
          var(--color-flash-rainbow-teal) 75%,
          var(--color-flash-rainbow-purple) 100%
        );
      "
    >
      <div class="relative flex items-center gap-[10px]">
        <img
          src="assets/icons/icon-flash.svg"
          alt=""
          class="size-[28px] lg:size-[40px]"
        />
        <h2
          class="font-semibold text-[26px] lg:text-[42px] text-white tracking-[0.5px]"
        >
          Flash Sale
        </h2>
        <img
          src="assets/icons/icon-flash.svg"
          alt=""
          class="size-[28px] lg:size-[40px]"
        />
      </div>
      <div class="relative w-full">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="flashSaleWrapper"></div>
        </div>
        <button
          id="flashSalePrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev"
        >
          <img
            src="assets/icons/arrow-down.svg"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="flashSaleNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next"
        >
          <img
            src="assets/icons/arrow-down.svg"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
      <button
        type="button"
        class="relative border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]"
          >Shop now</span
        >
      </button>
    </section>
