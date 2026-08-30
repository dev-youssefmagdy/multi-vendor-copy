    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Categories
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div
        class="grid grid-cols-3 gap-[10px] lg:gap-[32px] lg:flex lg:items-start"
      >
        <div
          class="bg-white rounded-[12px] lg:rounded-[18px] flex flex-col items-center py-[14px] lg:py-[24px] gap-[10px] lg:gap-[16px] relative overflow-hidden lg:flex-1 lg:h-[227px]"
        >
          <p
            class="font-semibold text-[13px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px] text-center px-1"
            style="color: var(--color-accent-purple)"
          >
            Women bags
          </p>
          <img
            src="{{ asset('elora-1/assets/images/cat-bag.png') }}"
            alt="Women bags"
            class="w-[55px] lg:w-[140px] h-auto"
          />
        </div>
        <div class="flex flex-col gap-[8px] lg:gap-[24px] lg:w-[261px]">
          <div
            class="bg-white rounded-[12px] lg:rounded-[18px] flex items-center gap-[8px] lg:gap-[16px] px-[8px] lg:px-[16px] py-[10px] lg:py-[18px] relative overflow-hidden h-[62px] lg:h-[114px]"
          >
            <p
              class="font-semibold text-[11px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px] w-[65%] lg:w-[162px]"
              style="color: var(--color-accent-purple)"
            >
              Home Accessories
            </p>
            <img
              src="{{ asset('elora-1/assets/images/cat-lamp.png') }}"
              alt="Home Accessories"
              class="absolute right-[-10px] lg:right-[-4px] top-[-8px] lg:top-[-22px] w-[46px] lg:w-[79px] h-auto"
            />
          </div>
          <div
            class="bg-white rounded-[12px] lg:rounded-[18px] flex items-center gap-[8px] lg:gap-[16px] px-[8px] lg:px-[16px] py-[10px] lg:py-[18px] relative overflow-hidden h-[46px] lg:h-[87px]"
          >
            <p
              class="font-semibold text-[11px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px]"
              style="color: var(--color-accent-purple)"
            >
              Electronics
            </p>
            <img
              src="{{ asset('elora-1/assets/images/cat-laptop.png') }}"
              alt="Electronics"
              class="absolute right-[0px] lg:right-[-4px] top-[-2px] lg:top-[-6px] w-[56px] lg:w-[105px] h-auto"
            />
          </div>
        </div>
        <div
          class="bg-white rounded-[12px] lg:rounded-[18px] flex flex-col items-center py-[14px] lg:py-[24px] gap-[10px] lg:gap-[16px] relative overflow-hidden lg:flex-1 lg:h-[227px]"
        >
          <p
            class="font-semibold text-[13px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px]"
            style="color: var(--color-accent-purple)"
          >
            Gaming
          </p>
          <img
            src="{{ asset('elora-1/assets/images/cat-controller.png') }}"
            alt="Gaming"
            class="w-[64px] lg:w-[179px] h-auto -rotate-[10deg]"
          />
        </div>
      </div>
    </section>
