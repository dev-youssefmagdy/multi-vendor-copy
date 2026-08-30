    <header>
      <!-- Mobile -->
      <div
        class="lg:hidden flex flex-col gap-[16px] px-[16px] pt-[14px] pb-[12px]"
        style="background: var(--color-bg-main)"
      >
        <div class="flex items-center justify-between gap-[8px]">
          <div class="flex items-center gap-[12px]">
            <button
              type="button"
              id="mobileMenuBtn"
              aria-label="Open menu"
              aria-expanded="false"
              aria-controls="mobileDrawer"
              class="flex flex-col items-center justify-center cursor-pointer"
            >
              <svg
                viewBox="0 0 24 24"
                class="size-[22px]"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                style="color: var(--color-black)"
              >
                <path d="M3 6h18M3 12h18M3 18h18" />
              </svg>
              <span
                class="text-[8px] tracking-[0.3px]"
                style="color: var(--color-black)"
                >menu</span
              >
            </button>
            <img
              src="{{ asset('elora-5/assets/icons/logo-elora.svg') }}"
              alt="ELORA"
              class="h-[26px] w-auto"
            />
          </div>
          <button
            type="button"
            aria-label="Notifications"
            class="relative flex items-center justify-center rounded-full size-[36px]"
            style="background: var(--color-page-bg)"
          >
            <img src="{{ asset('elora-5/assets/icons/icon-bell.svg') }}" alt="" class="size-[20px]" />
            <span
              class="absolute -top-[2px] -right-[2px] flex items-center justify-center rounded-full size-[15px] text-[9px] font-medium text-white"
              style="background: var(--color-secondary)"
              >0</span
            >
          </button>
        </div>
        <div class="flex items-center gap-[8px]">
          <div
            class="flex items-center gap-[8px] flex-1 h-[44px] rounded-[32px] px-[18px] border"
            style="
              background: var(--color-page-bg);
              border-color: var(--color-stroke);
            "
          >
            <img
              src="{{ asset('elora-5/assets/icons/search.svg') }}"
              alt=""
              class="size-[18px] opacity-70"
            />
            <input
              type="search"
              placeholder="Search..."
              class="bg-transparent outline-none text-[15px] w-full"
              style="color: var(--color-gray)"
            />
          </div>
          <button
            type="button"
            aria-label="Search by camera"
            class="shrink-0 flex items-center justify-center size-[40px] rounded-full"
            style="background: var(--color-page-bg)"
          >
            <img
              src="{{ asset('elora-5/assets/icons/icon-camera.svg') }}"
              alt=""
              class="size-[18px]"
            />
          </button>
        </div>
      </div>

      <!-- Desktop -->
      <div
        class="hidden lg:flex items-center gap-[18px] px-[48px] py-[18px] bg-white"
      >
        <button
          type="button"
          id="mobileMenuBtnDesktop"
          aria-label="Open menu"
          class="flex flex-col items-center justify-center cursor-pointer"
        >
          <img src="{{ asset('elora-5/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
          <span class="text-[10px] tracking-[0.5px] text-black">menu</span>
        </button>
        <img
          src="{{ asset('elora-5/assets/icons/logo-elora.svg') }}"
          alt="ELORA"
          class="h-[38px] w-auto"
        />
        <div
          class="flex flex-1 items-center gap-[8px] h-[54px] rounded-[32px] px-[24px]"
          style="background: var(--color-page-bg)"
        >
          <img
            src="{{ asset('elora-5/assets/icons/search.svg') }}"
            alt=""
            class="size-[22px] opacity-70"
          />
          <input
            type="search"
            placeholder="Search..."
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-gray)"
          />
        </div>
        <div class="flex items-center gap-[38px] shrink-0">
          <div class="flex items-center gap-[6px]">
            <img
              src="{{ asset('elora-5/assets/icons/flag-en.png') }}"
              alt="EN"
              class="h-[25px] w-[40px] object-cover rounded-[2px]"
            />
            <div class="flex flex-col">
              <span
                class="font-light text-[12px] tracking-[0.5px]"
                style="color: var(--color-black-alt)"
                >En/</span
              >
              <span
                class="font-medium text-[12px] tracking-[0.5px] flex items-center gap-[2px]"
                style="color: var(--color-black-alt)"
                >SAR
                <img
                  src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
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
            <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" class="size-[24px]" alt="" />
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
            <img src="{{ asset('elora-5/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
            <span class="flex flex-col items-center">
              <span
                class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center"
                style="background: var(--color-primary)"
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
            <img src="{{ asset('elora-5/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
            <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
              <span class="block" style="color: var(--color-gray)"
                >Welcome</span
              >
              <span class="block" style="color: var(--color-black-alt)"
                >Sign in / Register</span
              >
            </span>
          </button>
        </div>
      </div>
    </header>

    <!-- ============ MOBILE DRAWER MENU ============ -->
    <div
      id="mobileDrawerOverlay"
      class="mobile-drawer-overlay fixed inset-0 bg-black/50 z-40"
    ></div>
    <aside
      id="mobileDrawer"
      class="mobile-drawer fixed top-0 left-0 z-50 h-full w-[280px] max-w-[80vw] flex flex-col bg-white"
      style="box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15)"
    >
      <div
        class="flex items-center justify-between px-[20px] py-[16px] border-b"
        style="border-color: var(--color-page-bg)"
      >
        <img
          src="{{ asset('elora-5/assets/icons/logo-elora.svg') }}"
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
        style="background: var(--color-page-bg)"
      >
        <img
          src="{{ asset('elora-5/assets/icons/search.svg') }}"
          alt=""
          class="size-[18px] opacity-70"
        />
        <input
          type="search"
          placeholder="Search..."
          class="bg-transparent outline-none text-[14px] w-full"
          style="color: var(--color-gray)"
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
            color: var(--color-primary);
          "
          >Women's Bags
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-60"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="border-color: var(--color-page-bg); color: var(--color-black)"
          >Accessories
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="border-color: var(--color-page-bg); color: var(--color-black)"
          >Gaming
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="border-color: var(--color-page-bg); color: var(--color-black)"
          >Electronics
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="border-color: var(--color-page-bg); color: var(--color-black)"
          >Fashion
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]"
          style="color: var(--color-black)"
          >Watches
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
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
          <img src="{{ asset('elora-5/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Sign in / Register</span
          >
        </a>
        <a href="#" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
          >
        </a>
        <a href="#" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-5/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
          >
          <span
            class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center"
            style="background: var(--color-primary)"
            >0</span
          >
        </a>
      </div>
    </aside>
