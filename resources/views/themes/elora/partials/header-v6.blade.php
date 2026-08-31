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
              src="{{ asset('elora-2/assets/icons/avatar.png') }}"
              alt=""
              class="size-[44px] rounded-full object-cover"
            />
            <div class="flex flex-col gap-[3px]">
              <p
                class="font-medium text-[18px]"
                style="color: var(--color-text-primary)"
              >
                @auth('storefront')
                  {{ __('Hi, :name 👋', ['name' => auth('storefront')->user()->name]) }}
                @else
                  {{ __('Welcome 👋') }}
                @endauth
              </p>
              <div class="flex items-center gap-[4px]">
                <span
                  class="font-medium text-[12px] tracking-[0.5px]"
                  style="color: var(--color-text-location-muted)"
                  >{{ $storeName }}</span
                >
                <img
                  src="{{ asset('elora-2/assets/icons/chevron-down-small.svg') }}"
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
            class="elora-v6-menu-btn flex flex-col items-center justify-center cursor-pointer"
          >
            <img src="{{ asset('elora-2/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
            <span
              class="text-[10px] tracking-[0.5px]"
              style="color: var(--color-text-primary)"
              >menu</span
            >
          </button>
        </div>
        <form action="{{ route('tenant.storefront.search') }}" method="GET"
          class="flex items-center justify-center gap-[8px] h-[40px] rounded-[32px] px-[12px] border"
          style="
            background: var(--color-surface);
            border-color: var(--color-stroke);
          "
        >
          <img
            src="{{ asset('elora-2/assets/icons/search.svg') }}"
            alt=""
            class="size-[20px] opacity-70"
          />
          <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search..."
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-gray)"
          />
          <img src="{{ asset('elora-2/assets/icons/camera.svg') }}" alt="" class="h-[24px] w-[33px]" />
        </form>
      </div>

      <!-- Desktop -->
      <div
        class="hidden lg:flex items-center gap-[18px] px-[48px] py-[18px] bg-white"
      >
        <button
          type="button"
          id="menuBtnV6"
          aria-label="Open menu"
          aria-expanded="false"
          aria-controls="mobileDrawer"
          class="elora-v6-menu-btn flex flex-col items-center justify-center cursor-pointer"
        >
          <img src="{{ asset('elora-2/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
          <span class="text-[10px] tracking-[0.5px] text-black">menu</span>
        </button>
        <a href="{{ route('tenant.home') }}">
          <x-storefront-logo :storeName="$storeName" class="h-[38px] w-auto" />
        </a>
        <form action="{{ route('tenant.storefront.search') }}" method="GET"
          class="flex flex-1 items-center gap-[8px] h-[54px] rounded-[32px] px-[24px]"
          style="background: var(--color-surface)"
        >
          <img
            src="{{ asset('elora-2/assets/icons/search.svg') }}"
            alt=""
            class="size-[22px] opacity-70"
          />
          <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search..."
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-text-placeholder)"
          />
        </form>
        <div class="flex items-center gap-[38px] shrink-0">
          <div class="flex items-center gap-[6px]">
            <img
              src="{{ asset('elora-2/assets/icons/flag-en.png') }}"
              alt="{{ strtoupper($currentLanguage->code ?? 'EN') }}"
              class="h-[25px] w-[40px] object-cover rounded-[2px]"
            />
            <div class="flex flex-col">
              <span
                class="font-light text-[12px] tracking-[0.5px]"
                style="color: var(--color-black)"
                >{{ strtoupper($currentLanguage->code ?? 'EN') }}/</span
              >
              <span
                class="font-medium text-[12px] tracking-[0.5px] flex items-center gap-[2px]"
                style="color: var(--color-black)"
                >{{ $currentCurrency->code ?? 'SAR' }}
                <img
                  src="{{ asset('elora-2/assets/icons/arrow-down.svg') }}"
                  class="size-[12px]"
                  alt=""
              /></span>
            </div>
          </div>
          <a
            href="{{ route('tenant.storefront.favorites') }}"
            class="flex items-center gap-[8px] cursor-pointer"
            aria-label="Favorites"
          >
            <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[24px]" alt="" />
            <span
              class="text-[14px] tracking-[0.5px]"
              style="color: var(--color-black-alt)"
              >Favorite</span
            >
          </a>
          <a
            href="{{ route('tenant.storefront.cart') }}"
            class="flex items-center gap-[8px] cursor-pointer"
            aria-label="Cart"
          >
            <img src="{{ asset('elora-2/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
            <span class="flex flex-col items-center">
              <span
                class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center"
                style="background: var(--color-accent-green)"
                >{{ $cartCount }}</span
              >
              <span
                class="text-[14px] tracking-[0.5px]"
                style="color: var(--color-black-alt)"
                >Cart</span
              >
            </span>
          </a>
          @auth('storefront')
            <a
              href="{{ route('tenant.storefront.profile') }}"
              class="flex items-center gap-[8px] cursor-pointer"
              aria-label="Account"
            >
              <img src="{{ asset('elora-2/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
              <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
                <span class="block" style="color: var(--color-text-faint)"
                  >Welcome</span
                >
                <span class="block" style="color: var(--color-black-alt)"
                  >{{ auth('storefront')->user()->name }}</span
                >
              </span>
            </a>
          @else
            <a
              href="{{ route('tenant.storefront.login') }}"
              class="flex items-center gap-[8px] cursor-pointer"
              aria-label="Account"
            >
              <img src="{{ asset('elora-2/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
              <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
                <span class="block" style="color: var(--color-text-faint)"
                  >Welcome</span
                >
                <span class="block" style="color: var(--color-black-alt)"
                  >Sign in / Register</span
                >
              </span>
            </a>
          @endauth
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
        <a href="{{ route('tenant.home') }}">
          <x-storefront-logo :storeName="$storeName" class="h-[26px] w-auto" />
        </a>
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
      <form action="{{ route('tenant.storefront.search') }}" method="GET"
        class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[24px] px-[16px]"
        style="background: var(--color-surface)"
      >
        <img
          src="{{ asset('elora-2/assets/icons/search.svg') }}"
          alt=""
          class="size-[18px] opacity-70"
        />
        <input
          type="search"
          name="q"
          value="{{ request('q') }}"
          placeholder="Search..."
          class="bg-transparent outline-none text-[14px] w-full"
          style="color: var(--color-text-placeholder)"
        />
      </form>
      <nav
        class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar"
      >
        @forelse ($rootCategories as $category)
          <a
            href="{{ route('tenant.storefront.category', $category->slug) }}"
            class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px] {{ $loop->first ? 'font-medium' : '' }}"
            style="
              border-color: var(--color-page-bg);
              color: {{ $loop->first ? 'var(--color-accent-green)' : 'var(--color-text-primary)' }};
            "
            >{{ $category->name }}
            @if ($category->children->count() > 0)
              <img
                src="{{ asset('elora-2/assets/icons/arrow-down-bold.svg') }}"
                class="size-[14px] -rotate-90 {{ $loop->first ? 'opacity-60' : 'opacity-40' }}"
                alt=""
              />
            @endif
          </a>
        @empty
          <a
            href="{{ route('tenant.home') }}"
            class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]"
            style="color: var(--color-text-primary)"
            >{{ __('All Products') }}
          </a>
        @endforelse
      </nav>
      <div
        class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t"
        style="border-color: var(--color-page-bg)"
      >
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[10px]">
            <img src="{{ asset('elora-2/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
            <span
              class="text-[14px] tracking-[0.5px]"
              style="color: var(--color-black-alt)"
              >{{ auth('storefront')->user()->name }}</span
            >
          </a>
        @else
          <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
            <img src="{{ asset('elora-2/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
            <span
              class="text-[14px] tracking-[0.5px]"
              style="color: var(--color-black-alt)"
              >Sign in / Register</span
            >
          </a>
        @endauth
        <a href="{{ route('tenant.storefront.favorites') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
          >
        </a>
        <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-2/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
          >
          <span
            class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center"
            style="background: var(--color-accent-green)"
            >{{ $cartCount }}</span
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
        href="{{ route('tenant.home') }}"
        class="flex items-center gap-[4px] px-[16px] py-[8px] rounded-[33px]"
        style="background: var(--color-accent-green)"
      >
        <img src="{{ asset('elora-2/assets/icons/nav-home.svg') }}" alt="" class="size-[20px]" />
        <span class="font-medium text-[14px] tracking-[0.5px] text-white"
          >Home</span
        >
      </a>
      <a
        href="{{ route('tenant.storefront.favorites') }}"
        aria-label="Favorites"
        class="flex items-center justify-center w-[40px]"
      >
        <img
          src="{{ asset('elora-2/assets/icons/heart.svg') }}"
          alt=""
          class="size-[22px] opacity-70"
        />
      </a>
      <a
        href="{{ route('tenant.storefront.cart') }}"
        aria-label="Cart"
        class="relative flex items-center justify-center w-[40px]"
      >
        <img src="{{ asset('elora-2/assets/icons/nav-cart.svg') }}" alt="" class="size-[24px]" />
        <span
          class="absolute -top-[4px] right-[2px] flex items-center justify-center h-[16px] w-[18px] rounded-full text-white text-[11px]"
          style="background: var(--color-accent-green)"
          >{{ $cartCount }}</span
        >
      </a>
      <a
        href="{{ route('tenant.storefront.profile') }}"
        aria-label="Orders"
        class="flex items-center justify-center w-[40px]"
      >
        <img src="{{ asset('elora-2/assets/icons/nav-box.svg') }}" alt="" class="size-[22px]" />
      </a>
      @auth('storefront')
        <a
          href="{{ route('tenant.storefront.profile') }}"
          aria-label="Account"
          class="flex items-center justify-center w-[40px]"
        >
          <img
            src="{{ asset('elora-2/assets/icons/user.svg') }}"
            alt=""
            class="size-[22px] opacity-70"
          />
        </a>
      @else
        <a
          href="{{ route('tenant.storefront.login') }}"
          aria-label="Account"
          class="flex items-center justify-center w-[40px]"
        >
          <img
            src="{{ asset('elora-2/assets/icons/user.svg') }}"
            alt=""
            class="size-[22px] opacity-70"
          />
        </a>
      @endauth
    </nav>

@vite(['resources/css/elora/header-v6.css', 'resources/js/elora/header-v6.js'])
