    <!-- ============ SHOP BY CATEGORY ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[36px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      style="background: var(--color-page-bg)"
    >
      <h2
        class="font-semibold text-[22px] lg:text-[32px] text-center tracking-[0.5px]"
        style="color: var(--color-black)"
      >
        Shop by Category
      </h2>
      <div
        class="grid grid-cols-2 lg:flex gap-[10px] lg:gap-[16px] w-full lg:w-auto lg:max-w-[790px]"
      >
        <a href="#" class="shop-cat-tile h-[200px] lg:h-[310px] lg:w-[253px]">
          <img src="assets/images/shop-cat-accessories.png" alt="Accessories" />
          <div
            class="absolute inset-0"
            style="
              background: linear-gradient(
                180deg,
                rgba(19, 32, 146, 0) 40%,
                rgba(19, 32, 146, 0.55) 100%
              );
            "
          ></div>
          <span class="shop-cat-label text-[16px] lg:text-[20px]"
            >Accessories</span
          >
        </a>
        <div class="flex flex-col gap-[10px] lg:gap-[16px]">
          <a href="#" class="shop-cat-tile h-[95px] lg:h-[147px] lg:w-[253px]">
            <img src="assets/images/shop-cat-fashion.png" alt="Fashion" />
            <div
              class="absolute inset-0"
              style="
                background: linear-gradient(
                  180deg,
                  rgba(19, 32, 146, 0) 30%,
                  rgba(19, 32, 146, 0.55) 100%
                );
              "
            ></div>
            <span class="shop-cat-label text-[14px] lg:text-[18px]"
              >Fashion</span
            >
          </a>
          <a href="#" class="shop-cat-tile h-[95px] lg:h-[147px] lg:w-[253px]">
            <img
              src="assets/images/shop-cat-electronics.png"
              alt="Electronics"
            />
            <div
              class="absolute inset-0"
              style="
                background: linear-gradient(
                  180deg,
                  rgba(19, 32, 146, 0) 30%,
                  rgba(19, 32, 146, 0.55) 100%
                );
              "
            ></div>
            <span class="shop-cat-label text-[14px] lg:text-[18px]"
              >Electronics</span
            >
          </a>
        </div>
      </div>
      <button
        type="button"
        class="border rounded-full px-[32px] py-[14px] lg:py-[16px] text-[14px] lg:text-[16px] font-medium cursor-pointer"
        style="border-color: var(--color-primary); color: var(--color-primary)"
      >
        Explore all
      </button>
    </section>
