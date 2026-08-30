    <!-- ============ NAVBAR ============ -->
    <header>
      <!-- Mobile: greeting bar -->
      <div
        class="lg:hidden flex flex-col gap-[16px] px-[16px] pt-[12px] pb-[12px]"
        style="background: var(--color-page-bg)"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-[7px]">
            <img
              src="assets/icons/avatar.png"
              alt=""
              class="size-[44px] rounded-full object-cover"
            />
            <div class="flex flex-col gap-[3px]">
              <p
                class="font-medium text-[18px]"
                style="color: var(--color-text-primary)"
              >
                Hi, Abdullah 👋
              </p>
              <div class="flex items-center gap-[4px]">
                <span
                  class="font-medium text-[12px] tracking-[0.5px]"
                  style="color: var(--color-text-location-muted)"
                  >15 st. cairo, Egypt</span
                >
                <img
                  src="assets/icons/chevron-down-small.svg"
                  alt=""
                  class="size-[16px]"
                />
              </div>
            </div>
          </div>
          <button
            type="button"
            id="mobileMenuBtn"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="mobileDrawer"
            class="flex flex-col items-center justify-center cursor-pointer"
          >
            <img src="assets/icons/menu.svg" alt="" class="size-[38px] -mb-1" />
            <span
              class="text-[10px] tracking-[0.5px]"
              style="color: var(--color-text-primary)"
              >menu</span
            >
          </button>
        </div>
        <div
          class="flex items-center justify-center gap-[8px] h-[40px] rounded-[32px] px-[12px] border"
          style="
            background: var(--color-surface);
            border-color: var(--color-stroke);
          "
        >
          <img
            src="assets/icons/search.svg"
            alt=""
            class="size-[20px] opacity-70"
          />
          <input
            type="search"
            placeholder="Search..."
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-gray)"
          />
          <img src="assets/icons/camera.svg" alt="" class="h-[24px] w-[33px]" />
        </div>
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
          <img src="assets/icons/menu.svg" alt="" class="size-[38px] -mb-1" />
          <span class="text-[10px] tracking-[0.5px] text-black">menu</span>
        </button>
        <img
          src="assets/icons/logo-elora.svg"
          alt="ELORA"
          class="h-[38px] w-auto"
        />
        <div
          class="flex flex-1 items-center gap-[8px] h-[54px] rounded-[32px] px-[24px]"
          style="background: var(--color-surface)"
        >
          <img
            src="assets/icons/search.svg"
            alt=""
            class="size-[22px] opacity-70"
          />
          <input
            type="search"
            placeholder="Search..."
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-text-placeholder)"
          />
        </div>
        <div class="flex items-center gap-[38px] shrink-0">
          <div class="flex items-center gap-[6px]">
            <img
              src="assets/icons/flag-en.png"
              alt="EN"
              class="h-[25px] w-[40px] object-cover rounded-[2px]"
            />
            <div class="flex flex-col">
              <span
                class="font-light text-[12px] tracking-[0.5px]"
                style="color: var(--color-black)"
                >En/</span
              >
              <span
                class="font-medium text-[12px] tracking-[0.5px] flex items-center gap-[2px]"
                style="color: var(--color-black)"
                >SAR
                <img
                  src="assets/icons/arrow-down.svg"
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
            <img src="assets/icons/heart.svg" class="size-[24px]" alt="" />
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
            <img src="assets/icons/cart.svg" class="size-[24px]" alt="" />
            <span class="flex flex-col items-center">
              <span
                class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center"
                style="background: var(--color-accent-green)"
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
            <img src="assets/icons/user.svg" class="size-[24px]" alt="" />
            <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
              <span class="block" style="color: var(--color-text-faint)"
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
          src="assets/icons/logo-elora.svg"
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
          src="assets/icons/search.svg"
          alt=""
          class="size-[18px] opacity-70"
        />
        <input
          type="search"
          placeholder="Search..."
          class="bg-transparent outline-none text-[14px] w-full"
          style="color: var(--color-text-placeholder)"
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
            color: var(--color-accent-green);
          "
          >Men's Clothing
          <img
            src="assets/icons/arrow-down-bold.svg"
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
          >Food & Grocery
          <img
            src="assets/icons/arrow-down-bold.svg"
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
          >Featured
          <img
            src="assets/icons/arrow-down-bold.svg"
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
            src="assets/icons/arrow-down-bold.svg"
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
          >Women's Clothing
          <img
            src="assets/icons/arrow-down-bold.svg"
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
            src="assets/icons/arrow-down-bold.svg"
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
          >Cameras
          <img
            src="assets/icons/arrow-down-bold.svg"
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
          >Home Decor
          <img
            src="assets/icons/arrow-down-bold.svg"
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
            src="assets/icons/arrow-down-bold.svg"
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
            src="assets/icons/arrow-down-bold.svg"
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
            src="assets/icons/arrow-down-bold.svg"
            class="size-[14px] -rotate-90 opacity-40"
            alt=""
          />
        </a>
        <a
          href="#"
          class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]"
          style="color: var(--color-text-primary)"
          >Sports
          <img
            src="assets/icons/arrow-down-bold.svg"
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
          <img src="assets/icons/user.svg" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Sign in / Register</span
          >
        </a>
        <a href="#" class="flex items-center gap-[10px]">
          <img src="assets/icons/heart.svg" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
          >
        </a>
        <a href="#" class="flex items-center gap-[10px]">
          <img src="assets/icons/cart.svg" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
          >
          <span
            class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center"
            style="background: var(--color-accent-green)"
            >0</span
          >
        </a>
      </div>
    </aside>


        <!-- ============ MOBILE BOTTOM NAV ============ -->
    <nav
      class="lg:hidden fixed bottom-0 inset-x-0 z-30 flex items-center justify-between px-[24px] h-[64px] bg-[var(--color-bg-main)]"
      style="box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.08)"
    >
      <a
        href="#"
        class="flex items-center gap-[4px] px-[16px] py-[8px] rounded-[33px]"
        style="background: var(--color-accent-green)"
      >
        <img src="assets/icons/nav-home.svg" alt="" class="size-[20px]" />
        <span class="font-medium text-[14px] tracking-[0.5px] text-white"
          >Home</span
        >
      </a>
      <a
        href="#"
        aria-label="Favorites"
        class="flex items-center justify-center w-[40px]"
      >
        <img
          src="assets/icons/heart.svg"
          alt=""
          class="size-[22px] opacity-70"
        />
      </a>
      <a
        href="#"
        aria-label="Cart"
        class="relative flex items-center justify-center w-[40px]"
      >
        <img src="assets/icons/nav-cart.svg" alt="" class="size-[24px]" />
        <span
          class="absolute -top-[4px] right-[2px] flex items-center justify-center h-[16px] w-[18px] rounded-full text-white text-[11px]"
          style="background: var(--color-accent-green)"
          >0</span
        >
      </a>
      <a
        href="#"
        aria-label="Orders"
        class="flex items-center justify-center w-[40px]"
      >
        <img src="assets/icons/nav-box.svg" alt="" class="size-[22px]" />
      </a>
      <a
        href="#"
        aria-label="Account"
        class="flex items-center justify-center w-[40px]"
      >
        <img
          src="assets/icons/user.svg"
          alt=""
          class="size-[22px] opacity-70"
        />
      </a>
    </nav>
