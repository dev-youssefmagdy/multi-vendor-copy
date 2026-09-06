@php
    $cartCount = $cartCount ?? 0;
    $rootCategories = $rootCategories ?? collect();
@endphp

<!-- ============ NAVBAR ============ -->
<header>
  <!-- Mobile top bar -->
  <div class="lg:hidden flex flex-col" style="background: var(--color-bg-main)">
    <div class="flex items-center justify-between px-[16px] py-[14px]">
      <div class="flex items-center gap-[12px]">
        <button type="button" id="mobileMenuBtn" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="mobileDrawer"
            class="flex items-center justify-center size-[40px] rounded-full cursor-pointer" style="background: var(--color-page-bg)">
          <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="var(--color-black)" stroke-width="2" stroke-linecap="round">
            <path d="M3 6h18M3 12h18M3 18h18" />
          </svg>
        </button>
        <a href="{{ route('tenant.home') }}">
          <x-storefront-logo :storeName="$storeName" class="h-[26px] w-auto" />
        </a>
      </div>
      <button type="button" aria-label="{{ __('Notifications') }}" class="flex items-center justify-center size-[40px] rounded-full" style="background: var(--color-page-bg)">
        <img src="{{ asset('souqify-2/assets/icons/icon-bell.svg') }}" alt="" class="size-[18px]" />
      </button>
    </div>
    <form action="{{ route('tenant.storefront.search') }}" method="GET"
        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
        class="flex items-center gap-[8px] mx-[16px] mb-[16px] h-[44px] rounded-[8px] px-[16px]" style="background: var(--color-surface)">
      <button type="submit"><img src="{{ asset('souqify-2/assets/icons/icon-search.svg') }}" alt="" class="size-[18px] opacity-70" /></button>
      <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] w-full" style="color: var(--color-text-placeholder)" />
      <img src="{{ asset('souqify-2/assets/icons/icon-camera.svg') }}" alt="{{ __('Visual search') }}" class="size-[18px] opacity-70" />
    </form>
    <!-- Category filter pills -->
    <div class="flex items-center gap-[8px] px-[16px] pb-[12px] overflow-x-auto no-scrollbar">
      <a href="{{ route('tenant.home') }}" data-category-pill
          class="category-pill {{ request()->routeIs('tenant.home') ? 'is-active-pill' : '' }} shrink-0 h-[32px] px-[16px] rounded-full border text-[13px] font-medium whitespace-nowrap cursor-pointer">
        {{ __('All') }}
      </a>
      @foreach ($rootCategories as $category)
        <a href="{{ route('tenant.storefront.category', $category->slug) }}" data-category-pill
            class="category-pill shrink-0 h-[32px] px-[16px] rounded-full border text-[13px] font-medium whitespace-nowrap cursor-pointer">
          {{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20) }}
        </a>
      @endforeach
    </div>
  </div>

  <!-- Desktop utility bar -->
  <div class="hidden lg:flex items-center justify-between px-[32px] py-[12px]" style="background: var(--color-navbar-dark)">
    <div class="flex items-center gap-[16px]">
      <a href="{{ route('tenant.home') }}" class="flex items-center gap-[4px]">
        <img src="{{ asset('souqify-2/assets/icons/icon-store.svg') }}" alt="" class="size-[11px]" />
        <span class="text-[12px] tracking-[0.5px]" style="color: var(--color-navbar-muted)">{{ __('Find a Store') }}</span>
      </a>
      @auth('storefront')
        <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[4px]">
          <img src="{{ asset('souqify-2/assets/icons/icon-tracking.svg') }}" alt="" class="size-[11px]" />
          <span class="text-[12px] tracking-[0.5px]" style="color: var(--color-navbar-muted)">{{ __('Order Tracking') }}</span>
        </a>
      @endauth
      <a href="{{ route('tenant.storefront.best-selling') }}" class="flex items-center gap-[4px]">
        <img src="{{ asset('souqify-2/assets/icons/icon-shop-bag.svg') }}" alt="" class="size-[11px]" />
        <span class="text-[12px] tracking-[0.5px]" style="color: var(--color-navbar-muted)">{{ __('Shop') }}</span>
      </a>
    </div>
    <div class="flex items-center gap-[16px]">
      @if($hasFreeShipping ?? false)
        <div class="flex items-center gap-[4px]">
          <img src="{{ asset('souqify-2/assets/icons/icon-shipping-tag.svg') }}" alt="" class="size-[11px]" />
          <span class="text-[12px] tracking-[0.5px] text-white">{{ __('Free shipping worldwide.') }}
            <span style="color: var(--color-navbar-muted)">{{ __('Orders over') }}</span>
            <span style="color: var(--color-souqify-teal)">{{ $freeShippingThreshold ?? '$200' }}</span></span>
        </div>
      @endif
      <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] cursor-pointer" aria-label="{{ __('Currency & Language') }}">
        <span class="text-[12px] tracking-[0.5px]" style="color: var(--color-navbar-muted)">{{ $currentCurrency?->code ?? 'USD' }}</span>
        <img src="{{ asset('souqify-2/assets/icons/icon-chevron-down.svg') }}" alt="" class="size-[10px]" />
      </button>
      <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] cursor-pointer" aria-label="{{ __('Currency & Language') }}">
        <img src="{{ asset('souqify-2/assets/icons/icon-globe.svg') }}" alt="" class="size-[11px]" />
        <span class="text-[12px] tracking-[0.5px]" style="color: var(--color-navbar-muted)">{{ $currentLanguage?->name ?? 'English' }}</span>
        <img src="{{ asset('souqify-2/assets/icons/icon-chevron-down.svg') }}" alt="" class="size-[10px]" />
      </button>
    </div>
  </div>

  <!-- Desktop main bar -->
  <div class="hidden lg:flex items-center justify-between gap-[32px] px-[32px] py-[21px] bg-white">
    <a href="{{ route('tenant.home') }}" class="shrink-0">
      <x-storefront-logo :storeName="$storeName" class="h-[36px] w-auto" />
    </a>
    <div class="flex items-center gap-[8px] flex-1 max-w-[962px]">
      <button type="button" onclick="event.stopPropagation(); document.getElementById('souqifyV3DeptMenu')?.classList.toggle('hidden')"
          class="flex items-center gap-[8px] h-[54px] px-[12px] rounded-[4px] shrink-0 cursor-pointer relative" style="background: var(--color-souqify-teal)">
        <img src="{{ asset('souqify-2/assets/icons/icon-categories-grid.svg') }}" alt="" class="size-[20px]" />
        <span class="text-[14px] text-white tracking-[0.5px] whitespace-nowrap">{{ __('Shop By Categories') }}</span>
        <img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-white.svg') }}" alt="" class="size-[18px]" />
        <div id="souqifyV3DeptMenu"
            class="hidden absolute {{ app()->getLocale() == 'ar' ? 'right' : 'left' }}-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-neutral-100 z-50 overflow-hidden text-left">
          <div class="max-h-96 overflow-y-auto py-2">
            @forelse ($rootCategories as $category)
              <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                  class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-900 hover:bg-teal-50 hover:text-[color:var(--color-souqify-teal)] transition">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                <span class="flex-1 truncate">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 30) }}</span>
              </a>
            @empty
              <div class="px-4 py-3 text-sm text-neutral-500">{{ __('No categories yet') }}</div>
            @endforelse
          </div>
        </div>
      </button>
      <form action="{{ route('tenant.storefront.search') }}" method="GET"
          data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
          class="flex flex-1 items-center justify-between h-[54px] px-[24px] rounded-[4px]" style="background: var(--color-surface)">
        <div class="flex items-center gap-[8px] h-[38px] pr-[24px] border-r shrink-0" style="border-color: var(--color-gray)">
          <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-text-navbar-category)">{{ __('All Category') }}</span>
          <img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" alt="" class="size-[18px]" />
        </div>
        <div class="flex flex-1 items-center gap-[8px] pl-[24px]">
          <button type="submit"><img src="{{ asset('souqify-2/assets/icons/icon-search.svg') }}" alt="" class="size-[22px] opacity-70" /></button>
          <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search for premium tech, fashion, or home...') }}" class="bg-transparent outline-none text-[16px] w-full" style="color: var(--color-text-placeholder)" />
        </div>
      </form>
    </div>
    <div class="flex items-center gap-[38px] shrink-0">
      <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Cart') }}">
        <img src="{{ asset('souqify-2/assets/icons/icon-cart.svg') }}" class="size-[24px]" alt="" />
        <span class="flex flex-col items-center">
          <span id="souqify-v3-cart-badge" class="souqify-cart-badge text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background: var(--color-souqify-teal)">{{ $cartCount }}</span>
          <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-black-alt)">{{ __('Cart') }}</span>
        </span>
      </a>
      @auth('storefront')
        <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
          <img src="{{ asset('souqify-2/assets/icons/icon-user.svg') }}" class="size-[24px]" alt="" />
          <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-text-faint)">{{ auth('storefront')->user()->name }}</span>
        </a>
      @else
        <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
          <img src="{{ asset('souqify-2/assets/icons/icon-user.svg') }}" class="size-[24px]" alt="" />
          <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-text-faint)">{{ __('ACCOUNT') }}</span>
        </a>
      @endauth
    </div>
  </div>
</header>

<!-- ============ MOBILE DRAWER MENU ============ -->
<div id="mobileDrawerOverlay" class="mobile-drawer-overlay lg:hidden fixed inset-0 bg-black/50 z-40"></div>
<aside id="mobileDrawer" class="mobile-drawer lg:hidden fixed top-0 left-0 z-50 h-full w-[280px] max-w-[80vw] flex flex-col bg-white" style="box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15)">
  <div class="flex items-center justify-between px-[20px] py-[16px] border-b" style="border-color: var(--color-page-bg)">
    <x-storefront-logo :storeName="$storeName" class="h-[26px] w-auto" />
    <button type="button" id="drawerCloseBtn" aria-label="{{ __('Close menu') }}" class="flex items-center justify-center size-[32px] rounded-full cursor-pointer" style="background: var(--color-page-bg)">
      <svg viewBox="0 0 24 24" class="size-[16px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18" /></svg>
    </button>
  </div>
  <form action="{{ route('tenant.storefront.search') }}" method="GET"
      data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
      class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[8px] px-[16px]" style="background: var(--color-surface)">
    <button type="submit"><img src="{{ asset('souqify-2/assets/icons/icon-search.svg') }}" alt="" class="size-[18px] opacity-70" /></button>
    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] w-full" style="color: var(--color-text-placeholder)" />
  </form>
  <nav class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar">
    @forelse ($rootCategories as $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}"
          @if ($loop->first)
          class="flex items-center justify-between py-[12px] border-b text-[15px] font-medium tracking-[0.3px]"
          style="border-color: var(--color-page-bg); color: var(--color-souqify-teal);"
          @else
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="border-color: var(--color-page-bg); color: var(--color-text-primary);"
          @endif
          >{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 30) }}
        <img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90 {{ $loop->first ? 'opacity-60' : 'opacity-40' }}" alt="" />
      </a>
    @empty
      <a href="{{ route('tenant.home') }}" class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]" style="color: var(--color-text-primary)">{{ __('All Products') }}</a>
    @endforelse
  </nav>
  <div class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t" style="border-color: var(--color-page-bg)">
    @auth('storefront')
      <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-2/assets/icons/icon-user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-black-alt)">{{ auth('storefront')->user()->name }}</span>
      </a>
    @else
      <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-2/assets/icons/icon-user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-black-alt)">{{ __('Sign in / Register') }}</span>
      </a>
    @endauth
    <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('souqify-2/assets/icons/icon-cart.svg') }}" class="size-[20px]" alt="" />
      <span class="text-[14px] tracking-[0.5px]" style="color: var(--color-black-alt)">{{ __('Cart') }}</span>
      <span id="souqify-v3-mob-cart-badge" class="souqify-cart-badge text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background: var(--color-souqify-teal)">{{ $cartCount }}</span>
    </a>
  </div>
</aside>

<livewire:tenant.storefront.layout.locale-switcher :hideTrigger="true" />

@vite(['resources/js/souqify/header-v2.js'])
