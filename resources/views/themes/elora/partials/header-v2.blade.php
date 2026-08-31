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
        <x-storefront-logo :storeName="$storeName" class="h-[28px] w-auto" />
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
        id="desktopMenuBtn"
        aria-label="Open menu"
        aria-controls="mobileDrawer"
        class="flex flex-col items-center justify-center cursor-pointer"
    >
        <img src="{{ asset('elora-1/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
        <span class="text-[10px] tracking-[0.5px] text-black">menu</span>
    </button>
    <a href="{{ route('tenant.home') }}">
        <x-storefront-logo :storeName="$storeName" class="h-[38px] w-auto" />
    </a>
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
        <button
        type="button"
        onclick="Livewire.dispatch('open-locale-modal')"
        class="flex items-center gap-[6px] cursor-pointer"
        aria-label="{{ __('Currency & Language') }}"
        >
        @if ($currentLanguage?->image_url)
            <img
                src="{{ $currentLanguage->image_url }}"
                alt="{{ strtoupper($currentLanguage?->code ?? 'EN') }}"
                class="h-[25px] w-[40px] object-cover rounded-[2px]"
            />
        @endif
        <div class="flex flex-col">
            <span
            class="font-light text-[12px] tracking-[0.5px] text-[var(--color-black)]"
            >{{ strtoupper($currentLanguage?->code ?? 'EN') }}/</span
            >
            <span
            class="font-medium text-[12px] tracking-[0.5px] text-[var(--color-black)] flex items-center gap-[2px]"
            >{{ $currentCurrency?->code ?? 'SAR' }}
            <img
                src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
                class="size-[12px]"
                alt=""
            /></span>
        </div>
        </button>
        <a
        href="{{ route('tenant.storefront.favorites') }}"
        class="flex items-center gap-[8px] cursor-pointer"
        aria-label="Favorites"
        >
        <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" class="size-[24px]" alt="" />
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
        <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
        <span class="flex flex-col items-center">
            <span
            id="elora-v2-cart-badge"
            class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}"
            style="background: var(--color-badge-purple)"
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
        <img src="{{ asset('elora-1/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
        <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
            <span class="block text-[var(--color-text-faint)]">Welcome</span>
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
        <img src="{{ asset('elora-1/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
        <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
            <span class="block text-[var(--color-text-faint)]">Welcome</span>
            <span class="block" style="color: var(--color-black-alt)"
            >Sign in / Register</span
            >
        </span>
        </a>
        @endauth
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
        <x-storefront-logo :storeName="$storeName" class="h-[26px] w-auto" />
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
        @forelse ($rootCategories as $category)
          <a
            href="{{ route('tenant.storefront.category', $category->slug) }}"
            @if ($loop->first)
            class="flex items-center justify-between py-[12px] border-b text-[15px] font-medium tracking-[0.3px]"
            style="
              border-color: var(--color-page-bg);
              color: var(--color-accent-purple);
            "
            @else
            class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
            style="
              border-color: var(--color-page-bg);
              color: var(--color-text-primary);
            "
            @endif
            >{{ $category->translationValue('name') ?? $category->slug }}
            <img
              src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
              class="size-[14px] -rotate-90 {{ $loop->first ? 'opacity-60' : 'opacity-40' }}"
              alt=""
            />
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
          <img src="{{ asset('elora-1/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >{{ auth('storefront')->user()->name }}</span
          >
        </a>
        @else
        <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-1/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Sign in / Register</span
          >
        </a>
        @endauth
        <a href="{{ route('tenant.storefront.favorites') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
          >
        </a>
        <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
          >
          <span
            id="elora-v2-mob-cart-badge"
            class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}"
            style="background: var(--color-badge-purple)"
            >{{ $cartCount }}</span
          >
        </a>
      </div>
    </aside>

    <livewire:tenant.storefront.layout.locale-switcher :hideTrigger="true" />

@vite(['resources/css/elora/header-v2.css', 'resources/js/elora/header-v2.js'])
