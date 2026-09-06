@php
    $cartCount = $cartCount ?? 0;
    $rootCategories = $rootCategories ?? collect();
@endphp

<!-- ============ NAVBAR ============ -->
<header>
  <!-- Mobile: greeting header + search -->
  <div class="lg:hidden flex flex-col gap-[12px] px-[16px] pt-[14px] pb-[14px]" style="background:var(--color-bg-main)">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-[10px]">
        <button type="button" id="mobileMenuBtn" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="mobileDrawer" class="flex items-center justify-center size-[36px] rounded-full cursor-pointer shrink-0" style="background:var(--color-brand-green-tint)">
          <svg viewBox="0 0 24 24" class="size-[18px]" fill="none" stroke="var(--color-brand-green)" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a href="{{ route('tenant.home') }}" class="shrink-0">
          <x-storefront-logo :storeName="$storeName" class="h-[36px] w-auto rounded-full object-cover" />
        </a>
        @auth('storefront')
          <div class="flex flex-col gap-[2px]">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">{{ __('Hi, :name', ['name' => auth('storefront')->user()->name]) }} 👋</p>
          </div>
        @endauth
      </div>
      <a href="{{ route('tenant.storefront.cart') }}" aria-label="{{ __('Cart') }}" class="flex items-center justify-center rounded-full size-[36px] shrink-0" style="background:var(--color-brand-green-tint)">
        <img src="{{ asset('souqify-3/assets/icons/icon-cart-round.svg') }}" alt="" class="size-[18px]" />
      </a>
    </div>
    <form action="{{ route('tenant.storefront.search') }}" method="GET"
        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
        class="flex items-center gap-[8px] h-[48px] rounded-[28px] px-[14px] border" style="border-color:var(--color-stroke)">
      <button type="submit"><img src="{{ asset('souqify-3/assets/icons/icon-search-mobile.svg') }}" class="size-[18px] opacity-70" alt="" /></button>
      <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] w-full" style="color:var(--color-text-placeholder)" />
      <img src="{{ asset('souqify-3/assets/icons/icon-camera.svg') }}" class="size-[20px]" alt="" />
    </form>
  </div>

  <!-- Desktop: utility bar + main bar -->
  <div class="hidden lg:flex flex-col">
    <div class="flex items-center justify-between px-[32px] py-[10px]" style="background:var(--color-navbar-dark)">
      <div class="flex items-center gap-[18px]">
        <span class="flex items-center gap-[4px] text-[12px] tracking-[0.5px]" style="color:var(--color-navbar-muted)"><img src="{{ asset('souqify-3/assets/icons/icon-store-pin.svg') }}" class="size-[11px]" alt="" />{{ __('Find a Store') }}</span>
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px]" style="color:var(--color-navbar-muted)"><img src="{{ asset('souqify-3/assets/icons/icon-order-tracking.svg') }}" class="size-[11px]" alt="" />{{ __('Order Tracking') }}</a>
        @endauth
        <a href="{{ route('tenant.storefront.best-selling') }}" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px]" style="color:var(--color-navbar-muted)"><img src="{{ asset('souqify-3/assets/icons/icon-shop-bag.svg') }}" class="size-[11px]" alt="" />{{ __('Shop') }}</a>
      </div>
      <div class="flex items-center gap-[18px]">
        @if($hasFreeShipping ?? false)
          <span class="flex items-center gap-[4px] text-[12px] tracking-[0.5px] text-white whitespace-nowrap">
            <img src="{{ asset('souqify-3/assets/icons/icon-shipping-truck-small.svg') }}" class="size-[11px]" alt="" />{{ __('Free shipping worldwide.') }}
            <span style="color:var(--color-navbar-muted)">{{ __('Orders over') }} <span style="color:var(--color-brand-green)">{{ $freeShippingThreshold ?? '$200' }}</span></span>
          </span>
        @endif
        <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px] cursor-pointer" style="color:var(--color-navbar-muted)" aria-label="{{ __('Currency & Language') }}">{{ $currentCurrency?->code ?? 'USD' }}<img src="{{ asset('souqify-3/assets/icons/icon-chevron-down.svg') }}" class="size-[10px]" alt="" /></button>
        <button type="button" onclick="Livewire.dispatch('open-locale-modal')" class="flex items-center gap-[4px] text-[12px] tracking-[0.5px] cursor-pointer" style="color:var(--color-navbar-muted)" aria-label="{{ __('Currency & Language') }}"><img src="{{ asset('souqify-3/assets/icons/icon-globe.svg') }}" class="size-[11px]" alt="" />{{ $currentLanguage?->name ?? 'English' }}<img src="{{ asset('souqify-3/assets/icons/icon-chevron-down.svg') }}" class="size-[10px]" alt="" /></button>
      </div>
    </div>
    <div class="flex items-center justify-between gap-[24px] px-[32px] py-[16px]" style="background:var(--color-bg-main)">
      <a href="{{ route('tenant.home') }}" class="shrink-0">
        <x-storefront-logo :storeName="$storeName" class="h-[36px] w-auto" />
      </a>
      <div class="flex flex-1 items-center gap-[8px] max-w-[962px]">
        <button type="button" id="desktopMenuBtn" onclick="event.stopPropagation(); document.getElementById('souqifyV4DeptMenu')?.classList.toggle('hidden')" class="flex items-center gap-[8px] h-[54px] px-[16px] rounded-[4px] shrink-0 cursor-pointer relative" style="background:var(--color-brand-green)">
          <img src="{{ asset('souqify-3/assets/icons/icon-categories-bars.svg') }}" class="size-[18px]" alt="" />
          <span class="text-[14px] text-white tracking-[0.5px] whitespace-nowrap">{{ __('Shop By Categories') }}</span>
          <img src="{{ asset('souqify-3/assets/icons/icon-chevron-down-white.svg') }}" class="size-[16px]" alt="" />
          <div id="souqifyV4DeptMenu"
              class="hidden absolute {{ app()->getLocale() == 'ar' ? 'right' : 'left' }}-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-neutral-100 z-50 overflow-hidden text-left">
            <div class="max-h-96 overflow-y-auto py-2">
              @forelse ($rootCategories as $category)
                <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-900 hover:bg-green-50 hover:text-[color:var(--color-brand-green)] transition">
                  <span class="w-1.5 h-1.5 rounded-full" style="background:var(--color-badge-yellow)"></span>
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
            class="flex flex-1 items-center justify-between h-[54px] rounded-[4px] px-[20px]" style="background:var(--color-surface)">
          <div class="flex items-center gap-[8px] pr-[16px] border-r shrink-0" style="border-color:var(--color-gray)">
            <span class="text-[14px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-dim)">{{ __('All Category') }}</span>
            <img src="{{ asset('souqify-3/assets/icons/icon-chevron-down-gray.svg') }}" class="size-[16px]" alt="" />
          </div>
          <div class="flex flex-1 items-center gap-[8px] pl-[16px]">
            <button type="submit"><img src="{{ asset('souqify-3/assets/icons/icon-search.svg') }}" class="size-[20px] opacity-70" alt="" /></button>
            <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search for premium tech, fashion, or home...') }}" class="bg-transparent outline-none text-[15px] w-full" style="color:var(--color-text-placeholder)" />
          </div>
        </form>
      </div>
      <div class="flex items-center gap-[32px] shrink-0">
        <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Cart') }}">
          <img src="{{ asset('souqify-3/assets/icons/icon-cart.svg') }}" class="size-[24px]" alt="" />
          <span class="flex flex-col items-center">
            <span id="souqify-v4-cart-badge" class="souqify-cart-badge text-white text-[12px] rounded-full w-[26px] h-[15px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-brand-green)">{{ $cartCount }}</span>
            <span class="text-[13px]" style="color:var(--color-text-primary)">{{ __('Cart') }}</span>
          </span>
        </a>
        @auth('storefront')
          <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
            <img src="{{ asset('souqify-3/assets/icons/icon-user.svg') }}" class="size-[24px]" alt="" />
            <span class="text-[13px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ auth('storefront')->user()->name }}</span>
          </a>
        @else
          <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[8px] cursor-pointer" aria-label="{{ __('Account') }}">
            <img src="{{ asset('souqify-3/assets/icons/icon-user.svg') }}" class="size-[24px]" alt="" />
            <span class="text-[13px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ __('ACCOUNT') }}</span>
          </a>
        @endauth
      </div>
    </div>
  </div>
</header>

<!-- ============ MOBILE DRAWER MENU ============ -->
<div id="mobileDrawerOverlay" class="mobile-drawer-overlay lg:hidden fixed inset-0 bg-black/50 z-40"></div>
<aside id="mobileDrawer" class="mobile-drawer lg:hidden fixed top-0 left-0 z-50 h-full w-[280px] max-w-[80vw] flex flex-col" style="background:var(--color-white); box-shadow: 4px 0 24px rgba(0,0,0,0.15)">
  <div class="flex items-center justify-between px-[20px] py-[16px] border-b" style="border-color:var(--color-page-bg)">
    <x-storefront-logo :storeName="$storeName" class="h-[26px] w-auto" />
    <button type="button" id="drawerCloseBtn" aria-label="{{ __('Close menu') }}" class="flex items-center justify-center size-[32px] rounded-full cursor-pointer" style="background:var(--color-page-bg)">
      <svg viewBox="0 0 24 24" class="size-[16px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </div>
  <form action="{{ route('tenant.storefront.search') }}" method="GET"
      data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
      class="flex items-center gap-[8px] mx-[16px] mt-[16px] h-[44px] rounded-[24px] px-[16px]" style="background:var(--color-surface)">
    <button type="submit"><img src="{{ asset('souqify-3/assets/icons/icon-search.svg') }}" alt="" class="size-[18px] opacity-70" /></button>
    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off" placeholder="{{ __('Search...') }}" class="bg-transparent outline-none text-[14px] w-full" style="color:var(--color-text-placeholder)" />
  </form>
  <nav class="flex flex-col px-[16px] py-[12px] overflow-y-auto no-scrollbar">
    @forelse ($rootCategories as $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}"
          @if ($loop->first)
          class="flex items-center justify-between py-[12px] border-b text-[15px] font-medium tracking-[0.3px]"
          style="border-color:var(--color-page-bg); color:var(--color-brand-green)"
          @else
          class="flex items-center justify-between py-[12px] border-b text-[15px] tracking-[0.3px]"
          style="border-color:var(--color-page-bg); color:var(--color-text-primary)"
          @endif
          >{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 30) }}
        <img src="{{ asset('souqify-3/assets/icons/icon-chevron-down.svg') }}" class="size-[14px] -rotate-90 {{ $loop->first ? 'opacity-60' : 'opacity-40' }}" alt="" />
      </a>
    @empty
      <a href="{{ route('tenant.home') }}" class="flex items-center justify-between py-[12px] text-[15px] tracking-[0.3px]" style="color:var(--color-text-primary)">{{ __('All Products') }}</a>
    @endforelse
  </nav>
  <div class="mt-auto flex flex-col gap-[16px] px-[20px] py-[20px] border-t" style="border-color:var(--color-page-bg)">
    @auth('storefront')
      <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-3/assets/icons/icon-user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ auth('storefront')->user()->name }}</span>
      </a>
    @else
      <a href="{{ route('tenant.storefront.login') }}" class="flex items-center gap-[10px]">
        <img src="{{ asset('souqify-3/assets/icons/icon-user.svg') }}" class="size-[20px]" alt="" />
        <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ __('Sign in / Register') }}</span>
      </a>
    @endauth
    <a href="{{ route('tenant.storefront.cart') }}" class="flex items-center gap-[10px]">
      <img src="{{ asset('souqify-3/assets/icons/icon-cart.svg') }}" class="size-[20px]" alt="" />
      <span class="text-[14px] tracking-[0.5px]" style="color:var(--color-text-faint)">{{ __('Cart') }}</span>
      <span id="souqify-v4-mob-cart-badge" class="souqify-cart-badge text-white text-[12px] rounded-full w-[22px] h-[16px] flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-brand-green)">{{ $cartCount }}</span>
    </a>
  </div>
</aside>

<livewire:tenant.storefront.layout.locale-switcher :hideTrigger="true" />

@vite(['resources/js/souqify/header-v2.js'])
