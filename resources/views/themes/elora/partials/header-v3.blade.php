    <!-- ============ NAVBAR ============ -->
    <header>
      <div class="hidden">
        <livewire:tenant.storefront.layout.locale-switcher />
      </div>
      <!-- Mobile -->
      <div class="lg:hidden bg-white">
        <div class="flex items-center justify-between px-[18px] py-[12px]">
          <a href="{{ route('tenant.home') }}">
            <x-storefront-logo :storeName="$storeName" class="h-[28px] w-auto" />
          </a>
          <button
            type="button"
            id="mobileMenuBtn"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="mobileDrawer"
            class="flex flex-col items-center justify-center cursor-pointer"
          >
            <img src="{{ asset('elora-3/assets/icons/menu.svg') }}" alt="" class="size-[32px] -mb-1" />
            <span
              class="text-[10px] tracking-[0.5px]"
              style="color: var(--color-text-primary)"
              >menu</span
            >
          </button>
        </div>
        <div class="flex items-center gap-[8px] px-[16px] pb-[16px]">
          <form action="{{ route('tenant.storefront.search') }}" method="GET"
              data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
              class="flex flex-1 items-center gap-[8px] h-[44px] rounded-[32px] px-[18px]"
              style="
                background: var(--color-surface);
                border: 1px solid var(--color-stroke);
              "
            >
            <img
              src="{{ asset('elora-3/assets/icons/search.svg') }}"
              alt=""
              class="size-[20px] opacity-70"
            />
            <input
              type="text"
              name="q"
              value="{{ request('q') }}"
              placeholder="Search..."
              autocomplete="off"
              class="bg-transparent outline-none text-[16px] w-full"
              style="color: var(--color-gray)"
            />
          </form>
          <button type="button" aria-label="Search by image" class="shrink-0"
              data-image-search-trigger="storefront-image-search-modal-v3">
            <img src="{{ asset('elora-3/assets/icons/camera.svg') }}" alt="" class="size-[44px]" />
          </button>
        </div>
      </div>
      <x-image-search-modal id="storefront-image-search-modal-v3" :action="route('tenant.storefront.search.image')" />

      <!-- Desktop -->
      <div
        class="hidden lg:flex items-center gap-[18px] px-[48px] py-[18px] bg-white"
      >
        <button
          type="button"
          aria-label="Open menu"
          class="flex flex-col items-center justify-center cursor-pointer"
        >
          <img src="{{ asset('elora-3/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
          <span
            class="text-[10px] tracking-[0.5px]"
            style="color: var(--color-text-primary)"
            >menu</span
          >
        </button>
        <a href="{{ route('tenant.home') }}">
          <x-storefront-logo :storeName="$storeName" class="h-[38px] w-auto" />
        </a>
        <form action="{{ route('tenant.storefront.search') }}" method="GET"
          data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
          class="flex flex-1 items-center gap-[8px] h-[54px] rounded-[32px] px-[24px]"
          style="background: var(--color-surface)"
        >
          <img
            src="{{ asset('elora-3/assets/icons/search.svg') }}"
            alt=""
            class="size-[22px] opacity-70"
          />
          <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search..."
            autocomplete="off"
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-text-placeholder)"
          />
        </form>
        <div class="flex items-center gap-[38px] shrink-0">
          <button type="button" class="flex items-center gap-[6px]" onclick="Livewire.dispatch('open-locale-modal')">
            @if ($currentLanguage?->image_url)
              <img
                src="{{ $currentLanguage->image_url }}"
                alt="{{ $currentLanguage->name }}"
                class="h-[25px] w-[40px] object-cover rounded-[2px]"
              />
            @endif
            <div class="flex flex-col">
              <span
                class="font-light text-[12px] tracking-[0.5px]"
                style="color: var(--color-black)"
                >{{ strtoupper($currentLanguage?->code ?? 'EN') }}/</span
              >
              <span
                class="font-medium text-[12px] tracking-[0.5px] flex items-center gap-[2px]"
                style="color: var(--color-black)"
                >{{ $currentCurrency?->code ?? 'SAR' }}
                <img
                  src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
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
            <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[24px]" alt="" />
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
            <img src="{{ asset('elora-3/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
            <span class="flex flex-col items-center">
              <span
                class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center"
                style="background: var(--color-badge-green)"
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
              <img src="{{ asset('elora-3/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
              <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
                <span class="block" style="color: var(--color-text-faint)"
                  >{{ __('Welcome') }}</span
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
              <img src="{{ asset('elora-3/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
              <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
                <span class="block" style="color: var(--color-text-faint)"
                  >{{ __('Welcome') }}</span
                >
                <span class="block" style="color: var(--color-black-alt)"
                  >{{ __('Sign in / Register') }}</span
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
        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
        class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[24px] px-[16px]"
        style="background: var(--color-surface)"
      >
        <img
          src="{{ asset('elora-3/assets/icons/search.svg') }}"
          alt=""
          class="size-[18px] opacity-70"
        />
        <input
          type="text"
          name="q"
          value="{{ request('q') }}"
          placeholder="Search..."
          autocomplete="off"
          class="bg-transparent outline-none text-[14px] w-full"
          style="color: var(--color-text-placeholder)"
        />
      </form>
      <nav
        class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar"
      >
        @forelse ($rootCategories as $index => $category)
          <a
            href="{{ route('tenant.storefront.category', $category->slug) }}"
            class="flex items-center justify-between py-[12px] {{ $loop->last ? '' : 'border-b' }} text-[15px] {{ $index === 0 ? 'font-medium' : '' }} tracking-[0.3px]"
            style="
              border-color: var(--color-page-bg);
              color: {{ $index === 0 ? 'var(--color-badge-green)' : 'var(--color-text-primary)' }};
            "
            >{{ $category->translationValue('name') ?? $category->slug }}
            <img
              src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
              class="size-[14px] -rotate-90 {{ $index === 0 ? 'opacity-60' : 'opacity-40' }}"
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
            <img src="{{ asset('elora-3/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
            <span
              class="text-[14px] tracking-[0.5px]"
              style="color: var(--color-black-alt)"
              >{{ auth('storefront')->user()->name }}</span
            >
          </a>
        @else
          <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
            <img src="{{ asset('elora-3/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
            <span
              class="text-[14px] tracking-[0.5px]"
              style="color: var(--color-black-alt)"
              >{{ __('Sign in / Register') }}</span
            >
          </a>
        @endauth
        <a href="{{ route('tenant.storefront.favorites') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Favorite</span
          >
        </a>
        <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-3/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >Cart</span
          >
          <span
            class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center"
            style="background: var(--color-badge-green)"
            >{{ $cartCount }}</span
          >
        </a>
      </div>
    </aside>

@vite(['resources/css/elora/header-v3.css', 'resources/js/elora/header-v3.js'])
