    <section
      class="px-[16px] lg:px-[56px] py-[48px] flex flex-col gap-[16px] lg:gap-[24px] bg-[#F0F0F0]"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Trending Now
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-success)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="trendingWrapper"></div>
        </div>
        <button
          id="trendingPrev"
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
          id="trendingNext"
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
