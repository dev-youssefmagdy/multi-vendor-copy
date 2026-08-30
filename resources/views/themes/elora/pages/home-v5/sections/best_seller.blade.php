    <!-- ============ BEST SELLER ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      style="background: var(--color-yellow)"
    >
      <div class="flex items-center justify-between w-full">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          Best Seller
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >see all</a
        >
      </div>
      <!-- Mobile -->
      <div class="swiper bestseller-swiper lg:!hidden w-full">
        <div class="swiper-wrapper" id="bestSellerMobileWrapper"></div>
      </div>
      <!-- Desktop: 6 per view -->
      <div class="swiper bestseller-swiper !hidden lg:!block w-full max-w-[1183px] mx-auto">
        <div class="swiper-wrapper" id="bestSellerDesktopWrapper"></div>
      </div>
    </section>
