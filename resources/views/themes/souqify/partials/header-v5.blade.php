@php
    $cartCount = $cartCount ?? 0;
    $rootCategories = $rootCategories ?? collect();
@endphp

<!-- ============ NAVBAR ============ -->
<header>
  <!-- Mobile -->
  <div class="lg:hidden flex flex-col bg-white pb-[12px]" style="box-shadow:0 4px 4px 0 rgba(180,180,180,0.15)">
    <div class="flex items-center justify-between px-[16px] pt-[12px]">
      <div class="flex items-center gap-[10px]">
        <button type="button" id="mobileMenuBtn" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="mobileDrawer" class="flex items-center justify-center cursor-pointer size-[32px] shrink-0">
          <svg viewBox="0 0 24 24" class="size-[24px]" fill="none" stroke="var(--color-text-primary)" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <a href="{{ route('tenant.home') }}" class="shrink-0">
          <x-storefront-logo :storeName="$storeName" class="size-[44px] rounded-full object-cover" />
        </a>
        @auth('storefront')
          <div class="flex flex-col gap-[3px]">
            <p class="font-medium text-[18px]" style="color:var(--color-text-primary)">{{ __('Hi, :name', ['name' => auth('storefront')->user()->name]) }} 👋</p>
          </div>
        @endauth
      </div>
      <div class="flex items-center gap-[16px] shrink-0">
        <a href="{{ route('tenant.storefront.search') }}" aria-label="{{ __('Search') }}" class="flex items-center justify-center"><img src="{{ asset('souqify-4/assets/icons/mobile-search.svg') }}" class="size-[22px]" alt="" /></a>
        <a href="{{ route('tenant.storefront.cart') }}" aria-label="{{ __('Cart') }}" class="flex items-center justify-center relative"><img src="{{ asset('souqify-4/assets/icons/mobile-cart.svg') }}" class="size-[22px]" alt="" />
          <span id="souqify-v5-mob-cart-badge" class="souqify-cart-badge absolute -top-[6px] -right-[8px] text-white text-[10px] rounded-full min-w-[16px] h-[16px] flex items-center justify-center px-[3px] {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-primary)">{{ $cartCount }}</span>
        </a>
      </div>
    </div>
    <nav class="flex items-center gap-[4px] overflow-x-auto no-scrollbar px-[16px] mt-[10px] border-b" style="border-color:var(--color-page-bg)">
      <a href="{{ route('tenant.home') }}" class="category-pill is-active shrink-0 px-[16px] py-[8px] text-[12px] tracking-[0.5px] relative" style="color:var(--color-primary)">{{ __('All') }}
        <span class="absolute left-[16px] right-[16px] -bottom-[1px] h-[2px] rounded-full" style="background:var(--color-primary)"></span>
      </a>
      @foreach ($rootCategories as $category)
        <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="category-pill shrink-0 px-[16px] py-[8px] text-[12px] tracking-[0.5px]" style="color:var(--color-gray)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15) }}</a>
      @endforeach
    </nav>
  </div>

  <!-- Desktop -->
  <div class="hidden lg:flex lg:flex-col">
    <div class="flex items-center justify-between px-[32px] py-[12px]" style="background:var(--color-navbar-dark)">
      <div class="flex items-center gap-[16px]">
        <span class="flex items-center gap-[4px]"><img src="{{ asset('souqify-4/assets/icons/nav-store.svg') }}" class="size-[11px]" alt="" /><span class="text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-muted)">{{ __('Find a Store') }}</span></span>
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[4px]"><img src="{{ asset('souqify-4/assets/icons/nav-tracking.svg') }}" class="size-[11px]" alt="" /><span class="text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-muted)">{{ __('Order Tracking') }}</span></a>
        @endauth
        <a href="{{ route('tenant.storefront.best-selling') }}" class="flex items-center gap-[4px]"><img src="{{ asset('souqify-4/assets/icons/nav-shop.svg') }}" class="size-[11px]" alt="" /><span class="text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-muted)">{{ __('Shop') }}</span></a>
      </div>
      <div class="flex items-center gap-[16px]">
        @if($hasFreeShipping ?? false)
          <div class="flex items-center gap-[4px]">
            <img src="{{ asset('souqify-4/assets/icons/nav-shipping-truck.svg') }}" class="size-[11px]" alt="" />
            <span class="text-[12px] tracking-[0.5px] text-white">{{ __('Free shipping worldwide.') }}</span>
            <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-muted)">{{ __('Orders over') }} <span style="color:var(--color-primary)">{{ $freeShippingThreshold ?? '$200' }}</span></span>
          </div>
        @endif
        <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] cursor-pointer" aria-label="{{ __('Currency & Language') }}"><span class="text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-muted)">{{ $currentCurrency?->code ?? 'USD' }}</span><img src="{{ asset('souqify-4/assets/icons/nav-dropdown.svg') }}" class="size-[10px]" alt="" /></button>
        <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] cursor-pointer" aria-label="{{ __('Currency & Language') }}"><img src="{{ asset('souqify-4/assets/icons/nav-globe.svg') }}" class="size-[11px]" alt="" /><span class="text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-muted)">{{ $currentLanguage?->name ?? 'English' }}</span><img src="{{ asset('souqify-4/assets/icons/nav-dropdown.svg') }}" class="size-[10px]" alt="" /></button>
      </div>
    </div>
    <div class="flex items-center justify-between px-[32px] py-[18px] bg-white gap-[24px]">
      <a href="{{ route('tenant.home') }}" class="shrink-0">
        <x-storefront-logo :storeName="$storeName" class="h-[38px] w-auto" />
      </a>
      <div class="flex items-center gap-[8px] flex-1 max-w-[962px]">
        <button type="button" id="desktopMenuBtn" onclick="event.stopPropagation(); document.getElementById('souqifyV5DeptMenu')?.classList.toggle('hidden')" class="flex items-center gap-[8px] h-[54px] px-[12px] rounded-[4px] shrink-0 cursor-pointer relative" style="background:var(--color-primary)">
          <img src="{{ asset('souqify-4/assets/icons/nav-categories-grid.svg') }}" class="size-[20px]" alt="" />
          <span class="text-[14px] text-white tracking-[0.5px] whitespace-nowrap">{{ __('Shop By Categories') }}</span>
          <img src="{{ asset('souqify-4/assets/icons/nav-dropdown-white.svg') }}" class="size-[18px]" alt="" />
          <div id="souqifyV5DeptMenu"
              class="hidden absolute {{ app()->getLocale() == 'ar' ? 'right' : 'left' }}-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-neutral-100 z-50 overflow-hidden text-left">
            <div class="max-h-96 overflow-y-auto py-2">
              @forelse ($rootCategories as $category)
                <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-900 hover:bg-orange-50 hover:text-[color:var(--color-primary)] transition">
                  <span class="w-1.5 h-1.5 rounded-full" style="background:var(--color-accent-yellow)"></span>
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
            class="flex items-center flex-1 h-[54px] px-[24px] rounded-[4px] gap-[8px]" style="background:var(--color-surface)">
          <div class="flex items-center gap-[8px] pr-[16px] border-r shrink-0" style="border-color:var(--color-gray)">
            <span class="text-[14px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-black-alt)">{{ __('All Category') }}</span>
            <img src="{{ asset('souqify-4/assets/icons/nav-dropdown-all.svg') }}" class="size-[18px]" alt="" />
          </div>
          <button type="submit"><img src="{{ asset('souqify-4/assets/icons/nav-search.svg') }}" class="size-[22px] opacity-70" alt="" /></button>
          <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search for premium tech, fashion, or home...') }}" class="bg-transparent outline-none text-[16px] w-full" style="color:var(--color-text-placeholder)" />
        </form>
      </div>
      <div class="flex items-center gap-[38px] shrink-0">
        <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Cart') }}">
          <img src="{{ asset('souqify-4/assets/icons/nav-cart.svg') }}" class="size-[24px]" alt="" />
          <span class="flex flex-col items-center">
            <span id="souqify-v5-cart-badge" class="souqify-cart-badge text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-primary)">{{ $cartCount }}</span>
            <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Cart') }}</span>
          </span>
        </a>
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
            <img src="{{ asset('souqify-4/assets/icons/nav-user.svg') }}" class="size-[24px]" alt="" />
            <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ auth('storefront')->user()->name }}</span>
          </a>
        @else
          <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
            <img src="{{ asset('souqify-4/assets/icons/nav-user.svg') }}" class="size-[24px]" alt="" />
            <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ __('ACCOUNT') }}</span>
          </a>
        @endauth
      </div>
    </div>
  </div>
</header>

<!-- ============ MOBILE DRAWER MENU ============ -->
<div id="mobileDrawerOverlay" class="mobile-drawer-overlay lg:hidden fixed inset-0 bg-black/50 z-40"></div>
<aside id="mobileDrawer" class="mobile-drawer lg:hidden fixed top-0 left-0 z-50 h-full w-[280px] max-w-[80vw] flex flex-col bg-white" style="box-shadow: 4px 0 24px rgba(0,0,0,0.15)">
  <div class="flex items-center justify-between px-[20px] py-[16px] border-b" style="border-color:var(--color-page-bg)">
    <x-storefront-logo :storeName="$storeName" class="h-[26px] w-auto" />
    <button type="button" id="drawerCloseBtn" aria-label="{{ __('Close menu') }}" class="flex items-center justify-center size-[32px] rounded-full cursor-pointer" style="background:var(--color-page-bg)">
      <svg viewBox="0 0 24 24" class="size-[16px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </div>
  <form action="{{ route('tenant.storefront.search') }}" method="GET"
      data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
      class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[24px] px-[16px]" style="background:var(--color-surface)">
    <button type="submit"><img src="{{ asset('souqify-4/assets/icons/mobile-search.svg') }}" alt="" class="size-[18px] opacity-70" /></button>
    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] w-full" style="color:var(--color-text-placeholder)" />
  </form>
  <nav class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar">
    <a href="{{ route('tenant.home') }}" class="flex items-center justify-between py-[12px] border-b text-[15px] font-medium tracking-[0.3px]" style="border-color:var(--color-page-bg); color:var(--color-primary)">{{ __('All') }}
      <img src="{{ asset('souqify-4/assets/icons/mobile-arrow-down.svg') }}" class="size-[14px] -rotate-90 opacity-60" alt="" />
    </a>
    @forelse ($rootCategories as $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]" style="border-color:var(--color-page-bg); color:var(--color-text-primary)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 30) }}
        <img src="{{ asset('souqify-4/assets/icons/mobile-arrow-down.svg') }}" class="size-[14px] -rotate-90 opacity-40" alt="" />
      </a>
    @empty
      <a href="{{ route('tenant.home') }}" class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]" style="color:var(--color-text-primary)">{{ __('All Products') }}</a>
    @endforelse
  </nav>
  <div class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t" style="border-color:var(--color-page-bg)">
    @auth('storefront')
      <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-4/assets/icons/nav-user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ auth('storefront')->user()->name }}</span>
      </a>
    @else
      <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-4/assets/icons/nav-user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Sign in / Register') }}</span>
      </a>
    @endauth
    <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('souqify-4/assets/icons/nav-cart.svg') }}" class="size-[20px]" alt="" />
      <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Cart') }}</span>
      <span id="souqify-v5-mobdrawer-cart-badge" class="souqify-cart-badge text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-primary)">{{ $cartCount }}</span>
    </a>
  </div>
</aside>

<livewire:tenant.storefront.layout.locale-switcher :hideTrigger="true" />

@vite(['resources/js/souqify/header-v2.js'])
