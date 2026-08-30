    <!-- ============ BEST SELLER ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      style="background: var(--color-accent-yellow)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Best Seller
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-text-secondary-dark)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper w-full">
          <div class="swiper-wrapper" id="bestSellerWrapper"></div>
        </div>
        <button
          id="bestSellerPrev"
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
          id="bestSellerNext"
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
    </section>
