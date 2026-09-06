<header>
    <!-- Mobile: greeting bar (avatar / hi / location) + search + camera -->
    <div
      class="lg:hidden flex flex-col gap-[12px] px-[16px] pt-[14px] pb-[12px]"
      style="background: var(--color-bg-main)"
    >
      <div class="flex items-center justify-between gap-[8px]">
        <div class="flex items-center gap-[7px] flex-1 min-w-0">
          <button
            type="button"
            id="mobileMenuBtn"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="mobileDrawer"
            class="flex items-center justify-center shrink-0 size-[32px] rounded-full cursor-pointer"
            style="background: var(--color-surface)"
          >
            <img src="{{ asset('elora-4/assets/icons/menu.svg') }}" alt="" class="size-[16px]" />
          </button>
          <img
            src="{{ asset('elora-4/assets/images/avatar-user.png') }}"
            alt=""
            class="size-[44px] rounded-full object-cover shrink-0"
          />
          <div class="flex flex-col gap-[3px] min-w-0">
            <p
              class="font-medium text-[18px] truncate"
              style="color: var(--color-text-primary)"
            >
              Hi, {{ auth('storefront')->user()->name ?? __('there') }} &#128075;
            </p>
            <div class="flex items-center gap-[4px]">
              <span
                class="font-medium text-[12px] tracking-[0.5px] whitespace-nowrap"
                style="color: var(--color-greeting-muted)"
                >{{ $storeName ?? 'ELORA' }}</span
              >
              <img
                src="{{ asset('elora-4/assets/icons/icon-chevron-down.svg') }}"
                class="size-[16px]"
                alt=""
              />
            </div>
          </div>
        </div>
        <button
          type="button"
          aria-label="Notifications"
          class="flex items-center justify-center rounded-full size-[40px] shrink-0"
          style="background: var(--color-surface)"
        >
          <img
            src="{{ asset('elora-4/assets/icons/icon-bell-outline.svg') }}"
            alt=""
            class="size-[20px]"
          />
        </button>
      </div>
      <form action="{{ route('tenant.storefront.search') }}" method="GET"
        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
        class="flex items-center gap-[8px]">
        <div
          class="flex items-center gap-[8px] flex-1 h-[44px] rounded-[32px] px-[18px] border"
          style="
            background: var(--color-surface);
            border-color: var(--color-stroke);
          "
        >
          <img
            src="{{ asset('elora-4/assets/icons/search.svg') }}"
            alt=""
            class="size-[18px] opacity-70"
          />
          <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('Search...') }}"
            class="bg-transparent outline-none text-[16px] w-full"
            style="color: var(--color-gray)"
          />
        </div>
        <button type="button" data-image-search-trigger="storefront-image-search-modal-v4" aria-label="{{ __('Search by Image') }}" class="shrink-0">
          <img
            src="{{ asset('elora-4/assets/icons/icon-camera.svg') }}"
            alt=""
            class="size-[44px]"
          />
        </button>
      </form>
    </div>

    {{-- Mobile: horizontally-scrollable category quick-nav tabs, real
         categories from the storefront (StorefrontComposer shares
         $categories with every themed view, header included). --}}
    @if (($categories ?? collect())->isNotEmpty())
      @php $activeCategorySlug = request()->route('slug'); @endphp
      <div class="lg:hidden flex items-center overflow-x-auto no-scrollbar pb-[12px]" >
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="relative flex flex-col justify-center items-center px-[16px] py-[8px] shrink-0"
          style="text-decoration:none"
        >
          <span
            class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap"
            style="color: {{ !$activeCategorySlug ? 'var(--color-brand-orange)' : '#555555' }}"
          >{{ __('All') }}</span>
          @if (!$activeCategorySlug)
            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[19px] h-[2px] rounded-full" style="background: var(--color-brand-orange)"></span>
          @endif
        </a>
        @foreach ($categories as $cat)
          @php $catActive = $activeCategorySlug === $cat->slug; @endphp
          <a
            href="{{ route('tenant.storefront.category', $cat->slug) }}"
            class="relative flex flex-col justify-center items-center px-[16px] py-[8px] shrink-0"
            style="text-decoration:none"
          >
            <span
              class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap"
              style="color: {{ $catActive ? 'var(--color-brand-orange)' : '#555555' }}"
            >{{ $cat->translationValue('name') ?? $cat->name }}</span>
            @if ($catActive)
              <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[19px] h-[2px] rounded-full" style="background: var(--color-brand-orange)"></span>
            @endif
          </a>
        @endforeach
      </div>
    @endif

    <x-image-search-modal id="storefront-image-search-modal-v4" :action="route('tenant.storefront.search.image')" />

    <!-- Desktop -->
    <div
      class="hidden lg:flex items-center gap-[18px] px-[48px] py-[18px] bg-white"
    >
      <button
        type="button"
        id="desktopMenuBtn"
        aria-label="{{ __('Open menu') }}"
        aria-expanded="false"
        aria-controls="mobileDrawer"
        class="flex flex-col items-center justify-center cursor-pointer"
      >
        <img src="{{ asset('elora-4/assets/icons/menu.svg') }}" alt="" class="size-[38px] -mb-1" />
        <span class="text-[10px] tracking-[0.5px] text-black">{{ __('menu') }}</span>
      </button>
      <a href="{{ route('tenant.home') }}">
        <img
          src="{{ $logoPath ?? asset('elora-4/assets/icons/logo-elora.svg') }}"
          alt="{{ $storeName ?? 'ELORA' }}"
          class="h-[38px] w-auto"
        />
      </a>
      <form action="{{ route('tenant.storefront.search') }}" method="GET"
        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
        class="flex flex-1 items-center gap-[8px] h-[54px] rounded-[32px] px-[24px]"
        style="background: var(--color-surface)"
      >
        <img
          src="{{ asset('elora-4/assets/icons/search.svg') }}"
          alt=""
          class="size-[22px] opacity-70"
        />
        <input
          type="search"
          name="q"
          value="{{ request('q') }}"
          placeholder="{{ __('Search...') }}"
          class="bg-transparent outline-none text-[16px] text-[var(--color-text-placeholder)] w-full"
        />
        <button type="button" data-image-search-trigger="storefront-image-search-modal-v4" aria-label="{{ __('Search by Image') }}" class="shrink-0">
          <img
            src="{{ asset('elora-4/assets/icons/icon-camera.svg') }}"
            alt=""
            class="size-[22px]"
          />
        </button>
      </form>
      <div class="flex items-center gap-[38px] shrink-0">
        <button
          type="button"
          onclick="window.Livewire && window.Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
          class="flex items-center gap-[6px] cursor-pointer"
          aria-label="{{ __('Change language or currency') }}"
        >
          <img
            src="{{ data_get($currentLanguage ?? null, 'image_url') ?? asset('elora-4/assets/icons/flag-en.png') }}"
            alt="{{ strtoupper(data_get($currentLanguage ?? null, 'code', 'EN')) }}"
            class="h-[25px] w-[40px] object-cover rounded-[2px]"
          />
          <div class="flex flex-col">
            <span
              class="font-light text-[12px] tracking-[0.5px] text-[var(--color-black)]"
              >{{ strtoupper(data_get($currentLanguage ?? null, 'code', 'EN')) }}/</span
            >
            <span
              class="font-medium text-[12px] tracking-[0.5px] text-[var(--color-black)] flex items-center gap-[2px]"
              >{{ data_get($currentCurrency ?? null, 'code', 'SAR') }}
              <img
                src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}"
                class="size-[12px]"
                alt=""
            /></span>
          </div>
        </button>
        <div class="hidden">
          <livewire:tenant.storefront.layout.locale-switcher />
        </div>
        <a
          href="{{ route('tenant.storefront.favorites') }}"
          class="flex items-center gap-[8px] cursor-pointer"
          aria-label="{{ __('Favorites') }}"
        >
          <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" class="size-[24px]" alt="" />
          <span
            class="text-[14px] tracking-[0.5px]"
            style="color: var(--color-black-alt)"
            >{{ __('Favorite') }}</span
          >
        </a>
        <a
          href="{{ route('tenant.storefront.cart') }}"
          class="flex items-center gap-[8px] cursor-pointer"
          aria-label="{{ __('Cart') }}"
        >
          <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
          <span class="flex flex-col items-center">
            <span
              id="elora-v4-cart-badge"
              class="text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center {{ ($cartCount ?? 0) > 0 ? '' : 'hidden' }}"
              style="background: var(--color-brand-orange-bright)"
              >{{ $cartCount ?? 0 }}</span
            >
            <span
              class="text-[14px] tracking-[0.5px]"
              style="color: var(--color-black-alt)"
              >{{ __('Cart') }}</span
            >
          </span>
        </a>
        @auth('storefront')
        <a
          href="{{ route('tenant.storefront.profile') }}"
          class="flex items-center gap-[8px] cursor-pointer"
          aria-label="{{ __('Account') }}"
        >
          <img src="{{ asset('elora-4/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
          <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
            <span class="block text-[var(--color-text-faint)]">{{ __('Welcome') }}</span>
            <span class="block" style="color: var(--color-black-alt)"
              >{{ auth('storefront')->user()->name }}</span
            >
          </span>
        </a>
        @else
        <a
          href="{{ route('tenant.storefront.login') }}"
          class="flex items-center gap-[8px] cursor-pointer"
          aria-label="{{ __('Account') }}"
        >
          <img src="{{ asset('elora-4/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
          <span class="text-[14px] tracking-[0.5px] leading-tight text-left">
            <span class="block text-[var(--color-text-faint)]">{{ __('Welcome') }}</span>
            <span class="block" style="color: var(--color-black-alt)"
              >{{ __('Sign in / Register') }}</span
            >
          </span>
        </a>
        @endauth
      </div>
    </div>
</header>

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
      src="{{ asset('elora-4/assets/icons/logo-elora.svg') }}"
      alt="{{ $storeName ?? 'ELORA' }}"
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
      src="{{ asset('elora-4/assets/icons/search.svg') }}"
      alt=""
      class="size-[18px] opacity-70"
    />
    <input
      type="search"
      name="q"
      value="{{ request('q') }}"
      placeholder="{{ __('Search...') }}"
      class="bg-transparent outline-none text-[14px] text-[var(--color-text-placeholder)] w-full"
    />
  </div>
  <nav
    class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar"
  >
    @php
      $__navCategories = ($rootCategories ?? $categories ?? collect())->take(8);
    @endphp
    @forelse ($__navCategories as $i => $cat)
      <a
        href="{{ route('tenant.storefront.category', $cat->slug) }}"
        class="flex items-center justify-between py-[12px] {{ $loop->last ? '' : 'border-b' }} text-[15px] {{ $loop->first ? 'font-medium' : '' }} tracking-[0.3px]"
        style="
          border-color: var(--color-page-bg);
          color: {{ $loop->first ? 'var(--color-brand-orange-bright)' : 'var(--color-text-primary)' }};
        "
        >{{ $cat->translationValue('name') ?? $cat->slug }}
        <img
          src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}"
          class="size-[14px] -rotate-90 {{ $loop->first ? 'opacity-60' : 'opacity-40' }}"
          alt=""
        />
      </a>
    @empty
      <a
        href="#"
        class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]"
        style="color: var(--color-text-primary)"
        >All Categories
        <img
          src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}"
          class="size-[14px] -rotate-90 opacity-40"
          alt=""
        />
      </a>
    @endforelse
  </nav>
  <div
    class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t"
    style="border-color: var(--color-page-bg)"
  >
    @auth('storefront')
    <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('elora-4/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
      <span
        class="text-[14px] tracking-[0.5px]"
        style="color: var(--color-black-alt)"
        >{{ auth('storefront')->user()->name }}</span
      >
    </a>
    @else
    <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('elora-4/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
      <span
        class="text-[14px] tracking-[0.5px]"
        style="color: var(--color-black-alt)"
        >{{ __('Sign in / Register') }}</span
      >
    </a>
    @endauth
    <a href="{{ route('tenant.storefront.favorites') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
      <span
        class="text-[14px] tracking-[0.5px]"
        style="color: var(--color-black-alt)"
        >{{ __('Favorite') }}</span
      >
    </a>
    <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
      <span
        class="text-[14px] tracking-[0.5px]"
        style="color: var(--color-black-alt)"
        >{{ __('Cart') }}</span
      >
      <span
        id="elora-v4-cart-badge-mobile"
        class="text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center"
        style="background: var(--color-brand-orange-bright)"
        >{{ $cartCount ?? 0 }}</span
      >
    </a>
  </div>
</aside>

@vite(['resources/css/elora/header-v4.css', 'resources/js/elora/header-v4.js'])
