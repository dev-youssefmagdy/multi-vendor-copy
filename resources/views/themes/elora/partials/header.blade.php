{{-- Header/Footer partial for elora theme --}}
{{-- Edit this file directly to change header/footer markup --}}
{{-- Tenant-specific data via tenant() helper only --}}
<!-- ===== NAVBAR ===== -->
@php
    $buildCatTree = function ($cats) use (&$buildCatTree) {
        return $cats->map(function ($cat) use (&$buildCatTree) {
            return [
                'id' => $cat->id,
                'name' => $cat->translationValue('name') ?? $cat->slug,
                'url' => route('tenant.storefront.category', $cat->slug),
                'children' => $cat->relationLoaded('children') ? $buildCatTree($cat->children)->values()->all() : [],
            ];
        })->values();
    };
@endphp
<script>
    window.__eloraCatTree = @json($buildCatTree($rootCategories));
</script>
<header class="navbar">
    {{-- <!-- Announcement -->--}}
    {{-- <div class="bg-charcoal text-white text-xs py-1.5 overflow-hidden">--}}
        {{-- <div class="marquee-inner">--}}
            {{-- <span class="px-6">🚚 {{ __('Free shipping on all orders') }} &nbsp;|&nbsp; 🔥--}}
                {{-- {{ __('Flash deals every hour') }} &nbsp;|&nbsp; ✅ {{ __('Secure Payments') }} &nbsp;|&nbsp; 📦--}}
                {{-- {{ __('Easy Returns') }} &nbsp;|&nbsp;</span>--}}
            {{-- </div>--}}
        {{-- </div>--}}
    <!-- Main Row -->
    <div class="bg-white px-3 sm:px-6 lg:px-10 py-2.5 sm:py-3 flex items-center gap-2 sm:gap-3">
        <button id="menuBtn"
            onclick="openSideMenu(); setTimeout(function(){ var t=window.__eloraCatTree; window.eloraActivateCat && t && t.length && window.eloraActivateCat(t[0].id); }, 30);"
            class="flex flex-col gap-1 p-1.5 flex-shrink-0 items-center group">
            <span
                class="w-5 h-0.5 bg-charcoal block transition-all group-[.opened]:rotate-[40deg] origin-top-left"></span>
            <span class="w-5 h-0.5 bg-charcoal block transition-all group-[.opened]:opacity-0"></span>
            <span
                class="w-5 h-0.5 bg-charcoal block transition-all group-[.opened]:-rotate-[40deg] origin-bottom-left"></span>
            <span class="text-[10px] group-[.opened]:hidden">{{ __('Menu') }}</span>
            <span class="text-[10px] hidden group-[.opened]:block">{{ __('close') }}</span>
        </button>
        <a href="{{ route('tenant.home') }}" class="flex-shrink-0">
            <x-storefront-logo :storeName="$storeName" class="h-7 sm:h-9 lg:h-11 w-auto" />
        </a>
        <!-- Search -->
        <div class="flex-1 min-w-0 mx-1 sm:mx-3 lg:mx-4">
            <form action=" {{ route('tenant.storefront.search') }}" method="GET"
                data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                <div class="elora-search-inner flex items-center bg-bg rounded-full px-3 sm:px-5 py-2 gap-2 h-14"
                    style="position:relative">
                    <svg class="w-6 h-4  flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="{{ __('Search for products, brands...') }}" autocomplete="off"
                        class="bg-transparent flex-1 text-xs sm:text-sm text-gray-600 placeholder-gray-400 min-w-0 outline-none" />
                    {{-- Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.). --}}
                    <button type="button" data-image-search-trigger="storefront-image-search-modal"
                        class="flex-shrink-0 text-charcoal hover:text-main transition-colors" aria-label="{{ __('Search by Image') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path d="M4 8a2 2 0 0 1 2-2h1l1.2-1.6A2 2 0 0 1 9.8 3.6h4.4a2 2 0 0 1 1.6.8L17 6h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                            <circle cx="12" cy="13" r="3.2" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        <x-image-search-modal id="storefront-image-search-modal" :action="route('tenant.storefront.search.image')" />
        <!-- Actions -->
        <nav class="flex items-center gap-3 sm:gap-4 lg:gap-5 flex-shrink-0">

            <!-- Lang / Currency (locale-switcher Livewire component handles trigger + modal) -->
            <livewire:tenant.storefront.layout.locale-switcher />

            <!-- Favorite -->
            <a href="{{ route('tenant.storefront.favorites') }}"
                class="hidden sm:flex items-center gap-1 text-charcoal hover:text-main transition-colors">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12.62 20.8101C12.28 20.9301 11.72 20.9301 11.38 20.8101C8.48 19.8201 2 15.6901 2 8.6901C2 5.6001 4.49 3.1001 7.56 3.1001C9.38 3.1001 10.99 3.9801 12 5.3401C13.01 3.9801 14.63 3.1001 16.44 3.1001C19.51 3.1001 22 5.6001 22 8.6901C22 15.6901 15.52 19.8201 12.62 20.8101Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="text-xs hidden lg:block">{{ __('Favorite') }}</span>
            </a>

            <!-- Cart -->
            <a href="{{ route('tenant.storefront.cart') }}"
                class="flex items-center gap-1 text-charcoal hover:text-main transition-colors relative">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="">
                    <span id="elora-cart-badge"
                        class="bg-main text-white text-[10px] rounded-full w-full h-[18px] min-w-5 flex items-center justify-center font-semibold leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                    <span class="text-xs hidden lg:block">{{ __('Cart') }}</span>
                </div>
            </a>

            <!-- Account -->
            @auth('storefront')
                <a href="{{ route('tenant.storefront.profile') }}"
                    class="hidden md:flex items-center gap-1.5 text-charcoal hover:text-main transition-colors">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span
                        class="text-xs text-gray-500 hidden lg:block leading-tight">{{ auth('storefront')->user()->name }}</span>
                </a>
            @else
                <a href="{{ route('tenant.storefront.login') }}"
                    class="hidden md:flex items-center gap-1.5 text-charcoal hover:text-main transition-colors">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span class="text-xs text-gray-500 hidden lg:block leading-tight">{{ __('Welcome') }}<br><span
                            class="font-medium text-charcoal">{{ __('Sign in / Register') }}</span></span>
                </a>
            @endauth
        </nav>
    </div>
    </div>
    <!-- ===== SIDE MENU ===== -->
    <div id="sideMenu" class="hidden relative" style="z-index:400">
        <div class="_menu-overlay fixed inset-0" onclick="closeSideMenu()"></div>
        <div class="menu-panel !absolute !h-[calc(100vh-108px)] shadow-2xl" id="eloraSideMenuPanel">
            <div class="flex flex-row" style="height:100%;">

                {{-- ── Left column: parent categories ── --}}
                <div class="flex flex-col overflow-y-auto bg-white" id="eloraCatLeft"
                    style="width:50%; padding:16px 24px; gap:4px; border-inline-end:1px solid #f0f0f0; box-sizing:border-box;">
                    @forelse ($rootCategories as $category)
                        <button type="button"
                            class="cat-menu-btn w-full text-left flex flex-row justify-between items-center"
                            style="padding:8px; gap:8px; min-height:34px; border-radius:4px; border:none; background:transparent; cursor:pointer;"
                            data-cat-id="{{ $category->id }}"
                            data-href="{{ route('tenant.storefront.category', $category->slug) }}"
                            data-has-children="{{ $category->children->count() > 0 ? '1' : '0' }}"
                            onmouseenter="eloraActivateCat({{ $category->id }})" onclick="eloraHandleCatClick(this)">
                            <span class="cat-menu-label"
                                style="font-family:'Outfit',sans-serif; font-weight:400; font-size:14px; line-height:18px; letter-spacing:0.5px; color:#242424; flex:1; text-align:start; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                {{ $category->name }}
                            </span>
                            @if($category->children->count() > 0)
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="cat-menu-arrow"
                                    style="flex-shrink:0; color:#242424; transition:color 0.15s;">
                                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                                </svg>
                            @endif
                        </button>
                    @empty
                        <a href="{{ route('tenant.home') }}" onclick="closeSideMenu()"
                            class="block py-2 px-2 text-sm text-charcoal hover:text-main transition-colors">
                            {{ __('All Products') }}
                        </a>
                    @endforelse

                    {{-- Bottom auth link --}}
                    <div style="margin-top:auto; padding-top:12px; border-top:1px solid #f0f0f0;">
                        @auth('storefront')
                            <a href="{{ route('tenant.storefront.profile') }}" onclick="closeSideMenu()"
                                class="flex items-center gap-2 py-2 px-2 rounded-lg hover:bg-gray-50 text-sm text-charcoal transition-colors">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                {{ __('My Profile') }}
                            </a>
                        @else
                            <a href="{{ route('tenant.storefront.login') }}" onclick="closeSideMenu()"
                                class="flex items-center gap-2 py-2 px-2 rounded-lg hover:bg-gray-50 text-sm text-charcoal transition-colors">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                {{ __('Sign In') }}
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- ── Right column: sub-categories (JS-managed, supports unlimited depth) ── --}}
                <div class="flex flex-col overflow-y-auto" id="eloraCatRight"
                    style="width:50%; background:#F4F4F4; padding:0; gap:0; box-sizing:border-box;">
                    <div id="eloraCatRightContent"
                        style="display:flex; flex-direction:column; padding:16px 24px; gap:4px; height:100%; box-sizing:border-box;">
                    </div>
                </div>

            </div>
        </div>

</header>



<!-- ===== MOBILE BOTTOM NAV ===== -->
<nav class="mobile-bottom-nav items-center bg-transparent" id="mobileBottomNav">
    <div class="shadow-2xl flex-1 flex items-center justify-around gap-1 rounded-full py-2.5 px-4 bg-white">
        <a href="{{ route('tenant.home') }}"
            class="mob-nav-item {{ request()->routeIs('tenant.home') ? 'active' : '' }}">
            <svg width="24" height="24" stroke="currentColor" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M2.36465 12.958C1.98465 10.321 1.79465 9.002 2.33565 7.875C2.87665 6.748 4.02665 6.062 6.32765 4.692L7.71265 3.867C9.80065 2.622 10.8466 2 12.0006 2C13.1546 2 14.1996 2.622 16.2886 3.867L17.6736 4.692C19.9736 6.062 21.1246 6.748 21.6656 7.875C22.2066 9.002 22.0156 10.321 21.6356 12.958L21.3576 14.895C20.8706 18.283 20.6266 19.976 19.4516 20.988C18.2766 22 16.5536 22 13.1066 22H10.8946C7.44765 22 5.72465 22 4.54965 20.988C3.37465 19.976 3.13065 18.283 2.64365 14.895L2.36465 12.958Z"
                    stroke-width="1.5" />
                <path d="M15 18H9" stroke-width="1.5" stroke-linecap="round" />
            </svg>

            <span>{{ __('Home') }}</span>
        </a>
        <a href="{{ route('tenant.storefront.best-selling') }}"
            class="mob-nav-item {{ request()->routeIs('tenant.storefront.best-selling') ? 'active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path
                    d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
            </svg>
            <span>{{ __('Best Sellers') }}</span>
        </a>
        <a href="{{ route('tenant.storefront.new-in') }}" class="mob-nav-item">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                <rect x="9" y="3" width="6" height="4" rx="1" />
            </svg>
            <span>{{ __('New In') }}</span>
        </a>
        @auth('storefront')
            <a href="{{ route('tenant.storefront.profile') }}" class="mob-nav-item">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span>{{ __('Profile') }}</span>
            </a>
        @else
            <a href="{{ route('tenant.storefront.login') }}" class="mob-nav-item">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span>{{ __('Sign In') }}</span>
            </a>
        @endauth
    </div>
    <a href="{{ route('tenant.storefront.cart') }}" class="mob-nav-item cart-nav">
        <div class="cart-nav-inner w-16 h-16 rounded-full shadow-2xl">
            <div class="relative inline-flex">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                        stroke="#FDFDFD" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span id="elora-mob-cart-badge"
                    class="absolute -top-1 -right-1 bg-white text-main text-[10px] font-bold rounded-full min-w-4.5 h-4.5 flex items-center justify-center px-1 leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
            </div>
            <span>{{ __('My Cart') }}</span>
        </div>
    </a>
</nav>

@vite(['resources/css/elora/header.css', 'resources/js/elora/header.js'])
