    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[42px]"
      style="
        background: linear-gradient(
          180deg,
          var(--color-accent-purple) 0%,
          var(--color-accent-purple-light) 54%,
          var(--color-accent-purple-dark) 100%
        );
      "
    >
      <div
        class="flex flex-col lg:flex-row items-center gap-[24px] lg:gap-[12px]"
      >
        <div
          class="flex flex-col items-center lg:items-start gap-[16px] lg:gap-[24px] lg:w-fit lg:shrink-0"
        >
          <div
            class="flex flex-col items-center lg:items-start tracking-[1px] lg:tracking-[1.5px]"
          >
            <p
              class="font-semibold text-white text-[40px] lg:text-[100px] leading-[1.1]"
            >
              Flash Sale
            </p>
            <p
              class="font-medium text-[20px] lg:text-[50px]"
              style="color: var(--color-accent-yellow)"
            >
              up to 50%
            </p>
          </div>
          <div
            class="bg-white flex items-center justify-center gap-[10px] lg:gap-[25px] rounded-full h-[48px] lg:h-[78px] w-full max-w-[217px] lg:max-w-[446px]"
          >
            <img
              src="assets/icons/alarm-clock.svg"
              class="size-[32px] lg:size-[66px]"
              alt=""
            />
            <span
              id="flashTimer"
              class="font-semibold text-[24px] lg:text-[66px] tracking-[1px]"
              style="color: var(--color-accent-purple)"
              >03:06:25</span
            >
          </div>
          <button
            type="button"
            class="border-2 border-white rounded-full h-[48px] lg:h-[78px] px-[24px] lg:px-[40px] flex items-center justify-center cursor-pointer"
          >
            <span
              class="font-medium text-white text-[16px] lg:text-[29px] tracking-[1px]"
              >Explore all</span
            >
          </button>
        </div>

        <div
          class="swiper card-swiper flash-swiper w-full lg:flex-1 max-w-[700px]! pt-[20px]!"
        >
          <div class="swiper-wrapper" id="flashStackWrapper"></div>
        </div>
      </div>
    </section>
