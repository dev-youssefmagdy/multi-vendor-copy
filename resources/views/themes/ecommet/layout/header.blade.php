    <header class="w-full relative z-50">

        {{-- ── Announcement bar ────────────────────────────────────────────────── --}}
        <div class="w-full bg-[#1a1a1a] text-white text-[12px] py-2 px-4 hidden lg:block">
            <div class="container flex items-center justify-between">

                {{-- Left: Shipping --}}
                @if($hasFreeShipping ?? false)
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M1 3h11v13H1V3zm13 2h3l3 4v5h-6V5z" />
                    </svg>
                    <div class="flex flex-col leading-tight">
                        <span class="underline underline-offset-2">{{ __('Free shipping on all orders') }}</span>
                        <span class="underline underline-offset-2 text-[11px] text-gray-300">{{ __('Limited-time offer') }}</span>
                    </div>
                </div>
                @endif

                {{-- Center: Limited-time offers --}}
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                    </svg>
                    <div class="flex flex-col leading-tight text-center">
                        <span>{{ __('Limited-time Offers') }}</span>
                        <span class="text-[11px] text-gray-300">{{ __('Do not miss out') }}</span>
                    </div>
                </div>

                {{-- Right: Secure Checkout --}}
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ __('Secure Checkout') }}</span>
                </div>

            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────────────── --}}
        {{-- Desktop header                                                          --}}
        {{-- ────────────────────────────────────────────────────────────────────── --}}
        <div class="bg-primary text-white hidden lg:block">
            <div class="container py-4 flex items-center justify-between gap-6">

                {{-- Logo --}}
                <a href="{{ route('tenant.home') }}" class="flex items-center shrink-0">
                    <x-storefront-logo :storeName="$storeName" class="h-[32px] max-w-[160px] object-contain" />
                </a>

                {{-- Primary nav --}}
                <nav class="hidden lg:flex items-center gap-4 xl:gap-5 text-sm font-semibold shrink-0">

                    <a href="{{ route('tenant.storefront.best-selling') }}"
                        class="flex items-center gap-1 hover:text-yellow-light transition">
                        <svg class="w-[20px] h-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                        <span class="hidden xl:inline">{{ __('Best-Selling Items') }}</span>
                        <span class="xl:hidden">{{ __('Best-Selling') }}</span>
                    </a>

                    <a href="{{ route('tenant.storefront.full-star') }}"
                        class="flex items-center gap-1 hover:text-yellow-light transition">
                        <svg class="w-[20px] h-[20px] text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        {{ __('5-Star Rated') }}
                    </a>

                    <a href="{{ route('tenant.storefront.new-in') }}"
                        class="hover:text-yellow-light transition">{{ __('New In') }}</a>

                    {{-- Categories mega menu --}}
                    @if ($rootCategories->isNotEmpty())
                        <div class="relative group">
                            <button type="button"
                                class="flex items-center gap-1 hover:text-yellow-light transition focus:outline-none py-2 cursor-pointer">
                                {{ __('Categories') }}
                                <svg class="w-[16px] h-[16px] transition-transform duration-200 group-hover:rotate-180"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div class="absolute ltr:-left-32 rtl:-right-32 xl:ltr:left-0 xl:rtl:right-auto xl:ltr:right-auto top-full pt-4 w-[700px] xl:w-[850px] max-w-[90vw] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-[100]">
                                <div class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden flex min-h-[300px]">

                                    {{-- Left: parent category tabs --}}
                                    <div class="w-1/3 bg-[#f8f8f8] border-e border-gray-200 flex flex-col py-3 h-[420px] overflow-y-auto no-scrollbar">
                                        @foreach ($rootCategories as $i => $parent)
                                            <div class="mega-menu-tab w-full text-start px-4 xl:px-5 py-2.5 text-[13px] font-medium transition-colors flex justify-between items-center cursor-pointer {{ $i === 0 ? 'bg-white text-[#ff4e00]' : 'text-[#333] hover:text-[#ff4e00]' }}"
                                                data-target="mega-content-{{ $parent->id }}">
                                                {{ $parent->translationValue('name') ?? $parent->slug }}
                                                <svg class="w-3.5 h-3.5 text-gray-300 rtl:rotate-180 shrink-0"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Right: children grids --}}
                                    <div class="w-2/3 bg-white p-4 xl:p-6 relative h-[420px] overflow-y-auto no-scrollbar text-gray-darkest">
                                        @foreach ($rootCategories as $i => $parent)
                                            <div class="mega-menu-content fade-in {{ $i === 0 ? 'block' : 'hidden' }}"
                                                id="mega-content-{{ $parent->id }}">
                                                @if ($parent->children->isNotEmpty())
                                                    <div class="grid grid-cols-3 xl:grid-cols-4 gap-x-2 xl:gap-x-4 gap-y-6">
                                                        @foreach ($parent->children as $child)
                                                            <a href="{{ route('tenant.storefront.category', $child->slug) }}"
                                                                class="flex flex-col items-center group/item text-center">
                                                                <div class="relative w-[65px] h-[65px] xl:w-[72px] xl:h-[72px] rounded-full overflow-hidden mb-2 bg-gray-100 group-hover/item:shadow-md transition duration-200">
                                                                    @php($thumbUrl = $child->thumb_url ?? $child->centralCategory->thumb_url) {{-- Fallback to parent thumb if child doesn't have one --}}
                                                                    @if ($thumbUrl)
                                                                        <img loading="lazy" src="{{ $thumbUrl }}"
                                                                            alt="{{ $child->name ?? $child->slug }}"
                                                                            class="w-full h-full object-cover">
                                                                    @else
                                                                        <div class="w-full h-full flex items-center justify-center bg-gray-50">
                                                                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                            </svg>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <span class="text-[11px] font-medium text-[#333] group-hover/item:text-[#ff4e00] transition-colors leading-snug line-clamp-2 px-1">
                                                                    {{ $child->translationValue('name') ?? $child->slug }}
                                                                </span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    {{-- Parent has no children – link directly --}}
                                                    <div class="grid grid-cols-3 xl:grid-cols-4 gap-x-2 xl:gap-x-4 gap-y-6">
                                                        <a href="{{ route('tenant.storefront.category', $parent->slug) }}"
                                                            class="flex flex-col items-center group/item text-center">
                                                            <div class="relative w-[65px] h-[65px] xl:w-[72px] xl:h-[72px] rounded-full overflow-hidden mb-2 bg-gray-100 group-hover/item:shadow-md transition duration-200">
                                                                @php($thumbUrl = $parent->thumb_url ?? $parent->centralCategory->thumb_url)
                                                                @if ($thumbUrl)
                                                                    <img loading="lazy" src="{{ $thumbUrl }}"
                                                                        alt="{{ $parent->translationValue('name') ?? $parent->slug }}"
                                                                        class="w-full h-full object-cover">
                                                                @else
                                                                    <div class="w-full h-full flex items-center justify-center bg-gray-50">
                                                                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <span class="text-[11px] font-medium text-[#333] group-hover/item:text-[#ff4e00] transition-colors leading-snug line-clamp-2 px-1">
                                                                {{ $parent->translationValue('name') ?? $parent->slug }}
                                                            </span>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endif

                </nav>

                {{-- Search bar --}}
                <div class="flex-1 max-w-xl mx-4 relative hidden md:block">
                    <form action="{{ route('tenant.storefront.search') }}" method="GET" class="relative"
                        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
                        style="position: relative;">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search products...') }}"
                            class="w-full bg-white text-black rounded-full py-2.5 pl-5 pr-11 outline-none focus:ring-2 focus:ring-yellow-light placeholder-gray-medium text-sm shadow-sm">
                        <button type="submit"
                            class="absolute right-1 top-1 bottom-1 w-8 h-8 rounded-full bg-gray-darkest text-white flex items-center justify-center hover:bg-black transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                            </svg>
                        </button>
                    </form>
                </div>

                {{-- Right controls: locale, auth, cart --}}
                <div class="flex items-center gap-3 xl:gap-5 shrink-0">

                    {{-- Locale switcher --}}
                    @livewire('tenant.storefront.layout.locale-switcher')

                    {{-- Auth --}}
                    @auth('storefront')
                        <div class="relative group">
                            <button type="button"
                                class="flex items-center gap-2 text-white hover:text-yellow-light transition focus:outline-none">
                                <svg class="w-[24px] h-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex flex-col text-xs leading-tight hidden xl:flex">
                                    <span class="text-[#C5C5C5]">{{ __('Hello,') }}</span>
                                    <span>{{ auth('storefront')->user()->full_name }}</span>
                                </div>
                            </button>
                            <div class="absolute right-0 top-full mt-3 w-[180px] bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[90]">
                                <a href="{{ route('tenant.storefront.profile') }}"
                                    class="block px-4 py-3 text-[13px] text-[#333] font-medium hover:bg-gray-50 transition">
                                    {{ __('My Profile') }}
                                </a>
                                <form method="POST" action="{{ route('tenant.storefront.logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left px-4 py-3 text-[13px] text-red-600 font-medium hover:bg-red-50 transition border-t border-gray-100">
                                        {{ __('Sign out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('tenant.storefront.login') }}"
                            class="flex items-center gap-2 text-white hover:text-yellow-light transition">
                            <svg class="w-[24px] h-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex flex-col text-xs leading-tight hidden xl:flex">
                                <span class="text-[#C5C5C5]">{{ __('Welcome') }}</span>
                                <span>{{ __('Sign in / Register') }}</span>
                            </div>
                        </a>
                    @endauth

                    {{-- Cart dropdown (Livewire) --}}
                    @livewire('tenant.storefront.layout.cart-dropdown')

                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────────────────────────────── --}}
        {{-- Mobile sticky header                                                    --}}
        {{-- ────────────────────────────────────────────────────────────────────── --}}
        <div class="lg:hidden flex flex-col bg-white w-full z-40 sticky top-0 shadow-sm">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[#eaeaea]">

                {{-- Mobile logo --}}
                <a href="{{ route('tenant.home') }}" class="flex items-center shrink-0">
                    <x-storefront-logo :storeName="$storeName" class="h-[28px] max-w-[120px] object-contain" />
                </a>

                {{-- Mobile search --}}
                <div class="flex-1 mx-3 relative">
                    <form action="{{ route('tenant.storefront.search') }}" method="GET"
                        data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}"
                        style="position: relative;">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search...') }}"
                            class="w-full bg-[#f6f6f6] text-black rounded-full py-[7px] px-4 outline-none text-[13px] placeholder-gray-500">
                        <button type="submit"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="flex items-center gap-[14px] shrink-0">

                    {{-- Mobile locale switcher --}}
                    <livewire:tenant.storefront.layout.locale-switcher :key="'mobile-locale'" />

                    {{-- Mobile cart --}}
                    <a href="{{ route('tenant.storefront.cart') }}" class="relative text-gray-darkest">
                        <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span id="ecommet-mob-cart-badge"
                            class="absolute -top-2 -right-1 bg-primary text-white text-[9px] w-[16px] h-[16px] flex items-center justify-center rounded-full font-bold leading-none {{ ($cartCount ?? 0) > 0 ? '' : 'hidden' }}">{{ $cartCount ?? 0 }}</span>
                    </a>

                    {{-- Hamburger --}}
                    <button type="button" data-mobile-menu-open=""
                        class="flex flex-col gap-[3.5px] py-1 cursor-pointer">
                        <div class="w-[18px] h-[2px] bg-gray-darkest rounded-full"></div>
                        <div class="w-[18px] h-[2px] bg-gray-darkest rounded-full"></div>
                        <div class="w-[18px] h-[2px] bg-gray-darkest rounded-full"></div>
                    </button>

                </div>
            </div>

        </div>

    </header>

    {{-- ── Mobile Menu Drawer ─────────────────────────────────────────────────── --}}
    <div id="mobileMenuOverlay"
         class="lg:hidden fixed inset-0 bg-black/50 z-[200] hidden transition-opacity duration-300"></div>

    <div id="mobileMenuDrawer"
         class="lg:hidden fixed inset-0 z-[210] flex flex-col bg-white translate-x-full transition-transform duration-300">

        {{-- Drawer header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-[#eaeaea] shrink-0">
            <a href="{{ route('tenant.home') }}" class="flex items-center">
                <x-storefront-logo :storeName="$storeName" class="h-[28px] max-w-[120px] object-contain" />
            </a>
            <button type="button" id="closeMobileMenu" class="p-1 text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        @if ($rootCategories->isNotEmpty())
            <div class="flex flex-1 overflow-hidden">

                <div class="w-[130px] shrink-0 bg-[#f8f8f8] border-e border-[#eaeaea] overflow-y-auto">
                    @foreach ($rootCategories as $i => $parent)
                        <button type="button"
                            class="mobile-cat-tab w-full text-left px-4 py-3 text-[13px] border-b border-[#f0f0f0] transition-colors border-l-[3px]
                                {{ $i === 0
                                    ? 'bg-white text-primary font-semibold border-l-primary'
                                    : 'text-[#444] font-medium border-l-transparent' }}"
                            data-target="mobile-cat-{{ $parent->id }}">
                            {{ $parent->name ?? $parent->slug }}
                        </button>
                    @endforeach
                </div>

                <div class="flex-1 overflow-y-auto p-4 pb-20">
                    @foreach ($rootCategories as $i => $parent)
                        <div class="mobile-cat-content {{ $i === 0 ? 'block' : 'hidden' }}" id="mobile-cat-{{ $parent->id }}">
                            @php($items = $parent->children->isNotEmpty() ? $parent->children : collect([$parent]))
                            <div class="grid grid-cols-3 gap-x-3 gap-y-5">
                                @foreach ($items as $item)
                                    <a href="{{ route('tenant.storefront.category', $item->slug) }}"
                                       class="flex flex-col items-center text-center">
                                        <div class="w-[68px] h-[68px] rounded-full overflow-hidden bg-gray-100 mb-1.5 mx-auto">
                                            @php($thumb = $item->thumb_url ?? $item->centralCategory?->thumb_url ?? null)
                                            @if ($thumb)
                                                <img loading="lazy" src="{{ $thumb }}"
                                                     alt="{{ $item->name ?? $item->slug }}"
                                                     class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-gray-50">
                                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-[#333] leading-snug line-clamp-2">
                                            {{ $item->name ?? $item->slug }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        @else
            <div class="flex flex-col divide-y divide-[#f0f0f0] p-2">
                <a href="{{ route('tenant.storefront.new-in') }}" class="px-4 py-3.5 text-[15px] font-medium text-[#333]">{{ __('New In') }}</a>
                <a href="{{ route('tenant.storefront.best-selling') }}" class="px-4 py-3.5 text-[15px] font-medium text-[#333]">{{ __('Best-Selling') }}</a>
                <a href="{{ route('tenant.storefront.full-star') }}" class="px-4 py-3.5 text-[15px] font-medium text-[#333]">{{ __('5-Star Rated') }}</a>
            </div>
        @endif

    </div>

    {{-- ── Mobile Bottom Navigation ─────────────────────────────────────────────── --}}
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-[190] bg-[#1a1a1a] flex items-center justify-around py-2.5 px-2">

        <a href="{{ route('tenant.home') }}"
           class="flex flex-col items-center text-white opacity-80 hover:opacity-100 transition">
            <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
        </a>

        <a href="{{ route('tenant.storefront.cart') }}"
           class="flex items-center gap-1.5 bg-white text-[#1a1a1a] rounded-full px-5 py-1.5 font-bold text-[13px] relative">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            {{ __('Cart') }}
            <span id="ecommet-cart-badge"
                class="absolute -top-1.5 -right-1 bg-primary text-white text-[9px] w-[16px] h-[16px] flex items-center justify-center rounded-full font-bold leading-none {{ ($cartCount ?? 0) > 0 ? '' : 'hidden' }}">{{ $cartCount ?? 0 }}</span>
        </a>

        <a href="{{ route('tenant.storefront.new-in') }}"
           class="flex flex-col items-center text-white opacity-80 hover:opacity-100 transition">
            <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </a>

        <a href="{{ route('tenant.storefront.profile') }}"
           class="flex flex-col items-center text-white opacity-80 hover:opacity-100 transition">
            <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </a>

    </nav>

    <script>
    (function () {
        function openMobileMenu() {
            document.getElementById('mobileMenuOverlay')?.classList.remove('hidden');
            requestAnimationFrame(function () {
                document.getElementById('mobileMenuDrawer')?.classList.remove('translate-x-full');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileMenuDrawer')?.classList.add('translate-x-full');
            document.getElementById('mobileMenuOverlay')?.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-mobile-menu-open]').forEach(function (btn) {
                btn.addEventListener('click', openMobileMenu);
            });

            document.getElementById('closeMobileMenu')?.addEventListener('click', closeMobileMenu);
            document.getElementById('mobileMenuOverlay')?.addEventListener('click', closeMobileMenu);

            document.querySelectorAll('.mobile-cat-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('.mobile-cat-tab').forEach(function (t) {
                        t.classList.remove('bg-white', 'text-primary', 'font-semibold', 'border-l-primary');
                        t.classList.add('text-[#444]', 'border-l-transparent');
                    });
                    this.classList.add('bg-white', 'text-primary', 'font-semibold', 'border-l-primary');
                    this.classList.remove('text-[#444]', 'border-l-transparent');

                    document.querySelectorAll('.mobile-cat-content').forEach(function (c) {
                        c.classList.add('hidden');
                    });
                    var target = document.getElementById(this.dataset.target);
                    if (target) target.classList.remove('hidden');
                });
            });

            // Desktop mega menu tab switching (click & hover)
            document.querySelectorAll('.mega-menu-tab').forEach(function (tab) {
                function activateTab(el) {
                    document.querySelectorAll('.mega-menu-tab').forEach(function (t) {
                        t.classList.remove('bg-white', 'text-[#ff4e00]');
                        t.classList.add('text-[#333]', 'hover:text-[#ff4e00]');
                    });
                    el.classList.add('bg-white', 'text-[#ff4e00]');
                    el.classList.remove('text-[#333]', 'hover:text-[#ff4e00]');

                    document.querySelectorAll('.mega-menu-content').forEach(function (c) {
                        c.classList.add('hidden');
                    });
                    var target = document.getElementById(el.dataset.target);
                    if (target) target.classList.remove('hidden');
                }

                tab.addEventListener('click', function (e) {
                    if (e && e.preventDefault) e.preventDefault();
                    activateTab(this);
                });
                tab.addEventListener('mouseenter', function () {
                    activateTab(this);
                });
            });
        });
    })();
    </script>
