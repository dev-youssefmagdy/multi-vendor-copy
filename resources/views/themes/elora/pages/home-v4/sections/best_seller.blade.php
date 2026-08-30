    <!-- ============ BEST SELLER ============ -->
    <section
      class="texture-bg px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
    >
      <img
        src="assets/images/best-seller-texture.png"
        alt=""
        class="texture-overlay"
      />
      <h2 class="relative font-bold text-[24px] lg:text-[36px] text-white">
        Best Seller
      </h2>
      <div class="relative w-full">
        <div class="swiper card-swiper">
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
      <button
        type="button"
        class="relative border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]"
          >Explore all</span
        >
      </button>
    </section>
