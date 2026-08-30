<header>
    <!-- Mobile -->
    <div
    class="lg:hidden flex items-center justify-between px-[18px] py-[12px]"
    style="background: var(--color-text-primary)"
    >
    <div class="flex items-center gap-[12px]">
        <button
        type="button"
        id="mobileMenuBtn"
        aria-label="Open menu"
        aria-expanded="false"
        aria-controls="mobileDrawer"
        class="flex flex-col items-center justify-center cursor-pointer"
        >
        <img
            src="{{ asset('elora-1/assets/icons/menu.svg') }}"
            alt=""
            class="size-[38px] -mb-1 invert"
        />
        <span class="text-white text-[10px] tracking-[0.5px]">menu</span>
        </button>
        <img
        src="{{ asset('elora-1/assets/icons/logo-elora.svg') }}"
        alt="ELORA"
        class="h-[28px] w-auto"
        />
    </div>
    <button
        type="button"
        aria-label="Notifications"
        class="bg-white rounded-full size-[40px] flex items-center justify-center"
    >
        <img src="{{ asset('elora-1/assets/icons/bell.svg') }}" alt="" class="h-[15px] w-[14px]" />
    </button>
    </div>

    <!-- Desktop -->
    <div
    class="hidden lg:flex items-center gap-[18px] px-[48px] py-[18px] bg-white"
    >
    <button
        type="button"
        aria-label="Open menu"
        class="flex flex-col items-center justify-center cursor-pointer"
    >
        <img src="{{ asset('elora-1/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
        <span class="text-[10px] tracking-[0.5px] text-black">menu</span>
    </button>
    <img
        src="{{ asset('elora-1/assets/icons/logo-elora.svg') }}"
        alt="ELORA"
        class="h-[38px] w-auto"
    />
    <div
        class="flex flex-1 items-center gap-[8px] h-[54px] rounded-[32px] px-[24px]"
        style="background: var(--color-surface)"
    >
        <img
        src="{{ asset('elora-1/assets/icons/search.svg') }}"
        alt=""
        class="size-[22px] opacity-70"
        />
        <input
        type="search"
        placeholder="Search..."
        class="bg-transparent outline-none text-[16px] text-[var(--color-text-placeholder)] w-full"
        />
    </div>
    <div class="flex items-center gap-[38px] shrink-0">
        <div class="flex items-center gap-[6px]">
        <img
            src="{{ asset('elora-1/assets/icons/flag-en.png') }}"
            alt="EN"
            class="h-[25px] w-[40px] object-cover rounded-[2px]"
        />
        <div class="flex flex-col">
            <span
            class="font-light text-[12px] tracking-[0.5px] text-[var(--color-black)]"
            >En/</span
            >
            <span
            class="font-medium text-[12px] tracking-[0.5px] text-[var(--color-black)] flex items-center gap-[2px]"
            >SAR
            <img
                src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
                class="size-[12px]"
                alt=""
            /></span>
        </div>
        </div>
        <button
        type="button"
        class="flex items-center gap-[8px] cursor-pointer"
        aria-label="Favorites"
        >
        <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" class="size-[24px]" alt="" />
        <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
        >
        </button>
        <button
        type="button"
        class="flex items-center gap-[8px] cursor-pointer"
        aria-label="Cart"
        >
        <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
        <span class="flex flex-col items-center">
            <span
            class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center"
            style="background: var(--color-badge-purple)"
            >0</span
            >
            <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
            >
        </span>
        </button>
        <button
        type="button"
        class="flex items-center gap-[8px] cursor-pointer"
        aria-label="Account"
        >
        <img src="{{ asset('elora-1/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
        <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
            <span class="block text-[var(--color-text-faint)]">Welcome</span>
            <span class="block" style="color: var(--color-black-alt)"
            >Sign in / Register</span
            >
        </span>
        </button>
    </div>
    </div>
</header>

    <div
      id="mobileDrawerOverlay"
      class="mobile-drawer-overlay lg:hidden fixed inset-0 bg-black/50 z-40"
    ></div>
    <aside
      id="mobileDrawer"
      class="mobile-drawer lg:hidden fixed top-0 left-0 z-50 h-full w-[280px] max-w-[80vw] flex flex-col bg-white"
      style="box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15)"
    >
      <div
        class="flex items-center justify-between px-[20px] py-[16px] border-b"
        style="border-color: var(--color-page-bg)"
      >
        <img
          src="{{ asset('elora-1/assets/icons/logo-elora.svg') }}"
          alt="ELORA"
          class="h-[26px] w-auto"
        />
        <button
          type="button"
          id="drawerCloseBtn"
          aria-label="Close menu"
          class="flex items-center justify-center size-[32px] rounded-full cursor-pointer"
          style="background: var(--color-page-bg)"
        >
          <svg
            viewBox="0 0 24 24"
            class="size-[16px]"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          >
            <path d="M6 6l12 12M18 6L6 18" />
          </svg>
        </button>
      </div>
      <div
        class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[24px] px-[16px]"
        style="background: var(--color-surface)"
      >
        <img
          src="{{ asset('elora-1/assets/icons/search.svg') }}"
          alt=""
          class="size-[18px] opacity-70"
        />
        <input
          type="search"
          placeholder="Search..."
          class="bg-transparent outline-none text-[14px] text-[var(--color-text-placeholder)] w-full"
        />
      </div>
      <nav
        class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar"
      >
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] font-medium tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-accent-purple);
          "
          >Men's Clothing
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-60"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-text-primary);
          "
          >Women's Clothing
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-text-primary);
          "
          >Kid's Fashion
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-text-primary);
          "
          >Electronics
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-text-primary);
          "
          >Gaming
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-text-primary);
          "
          >Home & Kitchen
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="
            border-color: var(--color-page-bg);
            color: var(--color-text-primary);
          "
          >Bags
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]"
          style="color: var(--color-text-primary)"
          >Food & Grocery
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
      </nav>
      <div
        class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t"
        style="border-color: var(--color-page-bg)"
      >
        <a href="#" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-1/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Sign in / Register</span
          >
        </a>
        <a href="#" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
          >
        </a>
        <a href="#" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
          >
          <span
            class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center"
            style="background: var(--color-badge-purple)"
            >0</span
          >
        </a>
      </div>
    </aside>
