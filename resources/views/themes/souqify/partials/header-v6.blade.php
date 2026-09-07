@php
    $cartCount = $cartCount ?? 0;
    $rootCategories = $rootCategories ?? collect();
@endphp

<!-- ============ NAVBAR ============ -->
<header>
  <!-- Mobile -->
  <div class="lg:hidden flex flex-col bg-white">
    <div class="flex items-center justify-between px-[16px] py-[14px]">
      <a href="{{ route('tenant.home') }}" class="shrink-0">
        <x-storefront-logo :storeName="$storeName" class="font-semibold text-[26px] tracking-[0.5px]" />
      </a>
      <button type="button" id="mobileMenuBtn" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="mobileDrawer" class="flex items-center justify-center cursor-pointer">
        <img src="{{ asset('souqify-5/assets/icons/menu.svg') }}" alt="" class="size-[24px]" />
      </button>
    </div>
    <form action="{{ route('tenant.storefront.search') }}" method="GET"
        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
        class="flex items-center gap-[8px] mx-[16px] mb-[16px] h-[44px] rounded-[24px] px-[16px]" style="background:var(--color-surface)">
      <button type="submit"><img src="{{ asset('souqify-5/assets/icons/search.svg') }}" alt="" class="size-[18px] opacity-70" /></button>
      <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] text-[var(--color-text-placeholder)] w-full" />
      <div class="w-px h-[24px]" style="background:var(--color-stroke)"></div>
      <img src="{{ asset('souqify-5/assets/icons/camera.svg') }}" alt="{{ __('Visual search') }}" class="size-[20px]" />
    </form>
  </div>

  <!-- Desktop -->
  <div class="hidden lg:flex flex-col bg-white">
    <div class="flex items-center justify-between px-[32px] py-[12px]" style="background:var(--color-topbar)">
      <div class="flex items-center gap-[16px]">
        <span class="flex items-center gap-[4px] text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-text)">{{ __('Find a Store') }}</span>
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-text)">{{ __('Order Tracking') }}</a>
        @endauth
        <a href="{{ route('tenant.storefront.best-selling') }}" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px]" style="color:var(--color-topbar-text)">{{ __('Shop') }}</a>
      </div>
      <div class="flex items-center gap-[16px]">
        @if($hasFreeShipping ?? false)
          <span class="text-[12px] tracking-[0.5px] text-white">{{ __('Free shipping worldwide.') }} <span style="color:var(--color-topbar-text)">{{ __('Orders over') }} <span style="color:var(--color-brand-pink)">{{ $freeShippingThreshold ?? '$200' }}</span></span></span>
        @endif
        <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px] cursor-pointer" style="color:var(--color-topbar-text)" aria-label="{{ __('Currency & Language') }}">{{ $currentCurrency?->code ?? 'USD' }} <img src="{{ asset('souqify-5/assets/icons/arrow-down.svg') }}" class="size-[10px] invert opacity-70" alt="" /></button>
        <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px] cursor-pointer" style="color:var(--color-topbar-text)" aria-label="{{ __('Currency & Language') }}">{{ $currentLanguage?->name ?? 'English' }} <img src="{{ asset('souqify-5/assets/icons/arrow-down.svg') }}" class="size-[10px] invert opacity-70" alt="" /></button>
      </div>
    </div>
    <div class="flex items-center gap-[24px] px-[32px] py-[16px]">
      <a href="{{ route('tenant.home') }}" class="shrink-0">
        <x-storefront-logo :storeName="$storeName" class="h-[46px] w-auto" />
      </a>
      <div class="flex flex-1 items-center gap-[8px]">
        <button type="button" id="desktopMenuBtn" onclick="event.stopPropagation(); document.getElementById('souqifyV6DeptMenu')?.classList.toggle('hidden')" class="flex items-center gap-[8px] h-[54px] px-[12px] rounded-[4px] shrink-0 cursor-pointer relative" style="background:var(--color-brand-pink)">
          <img src="{{ asset('souqify-5/assets/icons/menu.svg') }}" alt="" class="size-[20px] invert" />
          <span class="text-[14px] text-white tracking-[0.5px] whitespace-nowrap">{{ __('Shop By Categories') }}</span>
          <img src="{{ asset('souqify-5/assets/icons/arrow-down.svg') }}" alt="" class="size-[12px] invert" />
          <div id="souqifyV6DeptMenu"
              class="hidden absolute {{ app()->getLocale() == 'ar' ? 'right' : 'left' }}-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-neutral-100 z-50 overflow-hidden text-left">
            <div class="max-h-96 overflow-y-auto py-2">
              @forelse ($rootCategories as $category)
                <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-900 hover:bg-pink-50 hover:text-[color:var(--color-brand-pink)] transition">
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
            class="flex flex-1 items-center h-[54px] px-[24px] rounded-[4px] gap-[8px]" style="background:var(--color-surface)">
          <span class="flex items-center gap-[8px] pr-[16px] mr-[16px] border-r shrink-0" style="border-color:var(--color-gray)">
            <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-slate)">{{ __('All Category') }}</span>
            <img src="{{ asset('souqify-5/assets/icons/arrow-down.svg') }}" alt="" class="size-[12px] opacity-60" />
          </span>
          <button type="submit"><img src="{{ asset('souqify-5/assets/icons/search.svg') }}" alt="" class="size-[18px] opacity-70 mr-[8px]" /></button>
          <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search for premium tech, fashion, or home...') }}" class="bg-transparent outline-none text-[16px] w-full" style="color:var(--color-text-placeholder)" />
        </form>
      </div>
      <div class="flex items-center gap-[38px] shrink-0">
        <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Cart') }}">
          <img src="{{ asset('souqify-5/assets/icons/cart.svg') }}" class="size-[24px]" alt="" />
          <span class="flex flex-col items-center">
            <span id="souqify-v6-cart-badge" class="souqify-cart-badge text-white text-[14px] rounded-full w-[30px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-brand-pink)">{{ $cartCount }}</span>
            <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Cart') }}</span>
          </span>
        </a>
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
            <img src="{{ asset('souqify-5/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
            <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ auth('storefront')->user()->name }}</span>
          </a>
        @else
          <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
            <img src="{{ asset('souqify-5/assets/icons/user.svg') }}" class="size-[24px]" alt="" />
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
    <x-storefront-logo :storeName="$storeName" class="font-semibold text-[22px]" />
    <button type="button" id="drawerCloseBtn" aria-label="{{ __('Close menu') }}" class="flex items-center justify-center size-[32px] rounded-full cursor-pointer" style="background:var(--color-page-bg)">
      <svg viewBox="0 0 24 24" class="size-[16px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </div>
  <form action="{{ route('tenant.storefront.search') }}" method="GET"
      data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
      class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[24px] px-[16px]" style="background:var(--color-surface)">
    <button type="submit"><img src="{{ asset('souqify-5/assets/icons/search.svg') }}" alt="" class="size-[18px] opacity-70" /></button>
    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] text-[var(--color-text-placeholder)] w-full" />
  </form>
  <nav class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar">
    <a href="{{ route('tenant.home') }}" class="flex items-center justify-between py-[12px] border-b text-[15px] font-medium tracking-[0.3px] relative" style="border-color:var(--color-page-bg); color:var(--color-brand-pink)">{{ __('All') }}
      <img src="{{ asset('souqify-5/assets/icons/arrow-down.svg') }}" class="size-[14px] -rotate-90 opacity-60" alt="" />
    </a>
    @forelse ($rootCategories as $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]" style="border-color:var(--color-page-bg); color:var(--color-text-primary)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 30) }}
        <img src="{{ asset('souqify-5/assets/icons/arrow-down.svg') }}" class="size-[14px] -rotate-90 opacity-40" alt="" />
      </a>
    @empty
      <a href="{{ route('tenant.home') }}" class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]" style="color:var(--color-text-primary)">{{ __('All Products') }}</a>
    @endforelse
  </nav>
  <div class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t" style="border-color:var(--color-page-bg)">
    @auth('storefront')
      <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-5/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ auth('storefront')->user()->name }}</span>
      </a>
    @else
      <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-5/assets/icons/user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Sign in / Register') }}</span>
      </a>
    @endauth
    <a href="{{ route('tenant.storefront.favorites') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[20px]" alt="" />
      <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Favorite') }}</span>
    </a>
    <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('souqify-5/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
      <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-black-alt)">{{ __('Cart') }}</span>
      <span id="souqify-v6-mobdrawer-cart-badge" class="souqify-cart-badge text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-brand-pink)">{{ $cartCount }}</span>
    </a>
  </div>
</aside>

<livewire:tenant.storefront.layout.locale-switcher :hideTrigger="true" />

@vite(['resources/js/souqify/header-v2.js'])
