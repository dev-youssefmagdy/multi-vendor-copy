{{-- Header/Footer partial for souqify theme --}}
{{-- Edit this file directly to change header/footer markup --}}
{{-- Tenant-specific data via tenant() helper only --}}
@php
    $cartCount = $cartCount ?? 0;
    $rootCategories = $rootCategories ?? collect();
@endphp

<!-- =========== TOP UTILITY BAR =========== -->
<div class="hidden lg:block bg-stone-950 text-gray-300 text-xs">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="hidden md:flex items-center gap-5">
            <a href="{{ route('tenant.home') }}" class="flex items-center gap-1.5 hover:text-white transition">
                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_181_12092)">
                        <path
                            d="M9.16536 4.58341C9.16536 6.87187 6.62666 9.25521 5.77416 9.99129C5.69474 10.051 5.59806 10.0833 5.4987 10.0833C5.39933 10.0833 5.30266 10.051 5.22324 9.99129C4.37074 9.25521 1.83203 6.87187 1.83203 4.58341C1.83203 3.61095 2.21834 2.67832 2.90597 1.99069C3.59361 1.30306 4.52624 0.916748 5.4987 0.916748C6.47116 0.916748 7.40379 1.30306 8.09142 1.99069C8.77906 2.67832 9.16536 3.61095 9.16536 4.58341Z"
                            stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                        <path
                            d="M5.5 5.95825C6.25939 5.95825 6.875 5.34264 6.875 4.58325C6.875 3.82386 6.25939 3.20825 5.5 3.20825C4.74061 3.20825 4.125 3.82386 4.125 4.58325C4.125 5.34264 4.74061 5.95825 5.5 5.95825Z"
                            stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                    </g>
                    <defs>
                        <clipPath id="clip0_181_12092">
                            <rect width="11" height="11" fill="white" />
                        </clipPath>
                    </defs>
                </svg>

                <span>{{ __('Find a Store') }}</span>
            </a>
            @auth('storefront')
                <a href="{{ route('tenant.storefront.profile') }}"
                    class="flex items-center gap-1.5 hover:text-white transition">
                    <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_181_12097)">
                            <path
                                d="M5.04167 9.9597C5.18102 10.0402 5.33909 10.0825 5.5 10.0825C5.66091 10.0825 5.81898 10.0402 5.95833 9.9597L9.16667 8.12637C9.30588 8.04599 9.42151 7.93042 9.50196 7.79125C9.5824 7.65208 9.62484 7.4942 9.625 7.33345V3.66678C9.62484 3.50604 9.5824 3.34816 9.50196 3.20898C9.42151 3.06981 9.30588 2.95424 9.16667 2.87387L5.95833 1.04053C5.81898 0.96008 5.66091 0.917725 5.5 0.917725C5.33909 0.917725 5.18102 0.96008 5.04167 1.04053L1.83333 2.87387C1.69412 2.95424 1.57849 3.06981 1.49804 3.20898C1.4176 3.34816 1.37516 3.50604 1.375 3.66678V7.33345C1.37516 7.4942 1.4176 7.65208 1.49804 7.79125C1.57849 7.93042 1.69412 8.04599 1.83333 8.12637L5.04167 9.9597Z"
                                stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M5.5 10.0833V5.5" stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M1.50781 3.20825L5.4999 5.49992L9.49198 3.20825" stroke="var(--color-primary)"
                                stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M3.4375 1.95703L7.5625 4.31745" stroke="var(--color-primary)" stroke-width="0.916667"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_181_12097">
                                <rect width="11" height="11" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>


                    <span>{{ __('Order Tracking') }}</span>
                </a>
            @endauth
            <a href="{{ route('tenant.storefront.best-selling') }}"
                class="flex items-center gap-1.5 hover:text-white transition">
                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_181_12104)">
                        <path
                            d="M2.75 0.916748L1.375 2.75008V9.16675C1.375 9.40986 1.47158 9.64302 1.64349 9.81493C1.81539 9.98684 2.04855 10.0834 2.29167 10.0834H8.70833C8.95145 10.0834 9.18461 9.98684 9.35651 9.81493C9.52842 9.64302 9.625 9.40986 9.625 9.16675V2.75008L8.25 0.916748H2.75Z"
                            stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M1.375 2.75H9.625" stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M7.33464 4.58325C7.33464 5.06948 7.14148 5.5358 6.79766 5.87961C6.45385 6.22343 5.98753 6.41659 5.5013 6.41659C5.01507 6.41659 4.54876 6.22343 4.20494 5.87961C3.86112 5.5358 3.66797 5.06948 3.66797 4.58325"
                            stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                    </g>
                    <defs>
                        <clipPath id="clip0_181_12104">
                            <rect width="11" height="11" fill="white" />
                        </clipPath>
                    </defs>
                </svg>

                <span>{{ __('Shop') }}</span>
            </a>
        </div>
        <div class="flex items-center gap-4 {{ app()->getLocale() == 'ar' ? 'mr-auto' : 'ml-auto' }}">
            @if($hasFreeShipping ?? false)
            <p class="hidden sm:flex items-center gap-1">
                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M6.41797 8.24992V2.74992C6.41797 2.5068 6.32139 2.27365 6.14948 2.10174C5.97757 1.92983 5.74442 1.83325 5.5013 1.83325H1.83464C1.59152 1.83325 1.35836 1.92983 1.18645 2.10174C1.01455 2.27365 0.917969 2.5068 0.917969 2.74992V7.79159C0.917969 7.91314 0.966257 8.02972 1.05221 8.11568C1.13817 8.20163 1.25474 8.24992 1.3763 8.24992H2.29297"
                        stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M6.875 8.25H4.125" stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path
                        d="M8.70964 8.25008H9.6263C9.74786 8.25008 9.86444 8.20179 9.95039 8.11584C10.0363 8.02988 10.0846 7.91331 10.0846 7.79175V6.11883C10.0845 6.01482 10.0489 5.91396 9.9838 5.83283L8.3888 3.83908C8.34594 3.7854 8.29155 3.74204 8.22967 3.71221C8.16779 3.68238 8.1 3.66685 8.0313 3.66675H6.41797"
                        stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M7.79167 9.16659C8.29793 9.16659 8.70833 8.75618 8.70833 8.24992C8.70833 7.74366 8.29793 7.33325 7.79167 7.33325C7.28541 7.33325 6.875 7.74366 6.875 8.24992C6.875 8.75618 7.28541 9.16659 7.79167 9.16659Z"
                        stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M3.20964 9.16659C3.7159 9.16659 4.1263 8.75618 4.1263 8.24992C4.1263 7.74366 3.7159 7.33325 3.20964 7.33325C2.70337 7.33325 2.29297 7.74366 2.29297 8.24992C2.29297 8.75618 2.70337 9.16659 3.20964 9.16659Z"
                        stroke="var(--color-primary)" stroke-width="0.916667" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span class="text-white">{{ __('Free shipping worldwide.') }}</span>
                <span class="text-gray-300">{{ __('Orders over') }} <span class="text-blue-500">{{ $freeShippingThreshold ?? '$200' }}</span></span>
            </p>
            @endif
            {{-- Locale / Currency switcher Livewire component handles trigger + modal --}}
            @livewire('tenant.storefront.layout.locale-switcher')
        </div>
    </div>
</div>

<!-- =========== MAIN HEADER =========== -->
<header class="hidden lg:block bg-white sticky top-0 z-50 shadow-sm">
    <div
        class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-4 lg:py-4 flex items-center gap-4 lg:gap-6 justify-between">

        <!-- Logo -->
        <a href="{{ route('tenant.home') }}" class="flex items-center gap-2 shrink-0">
            <x-storefront-logo :storeName="$storeName" class="h-8 sm:h-10 lg:h-12 w-auto" />
        </a>

        <!-- Search bar -->
        <div class="hidden lg:flex flex-1 items-center gap-2">
            <button type="button" onclick="event.stopPropagation(); document.getElementById('souqifyDeptMenu')?.classList.toggle('hidden')"
                class="h-12 px-4 bg-blue-700 hover:bg-blue-800 transition rounded text-white flex items-center gap-2 text-sm shrink-0 relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                {{ __('Shop By Department') }}
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
                <div id="souqifyDeptMenu"
                    class="hidden absolute {{ app()->getLocale() == 'ar' ? 'right' : 'left' }}-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-neutral-100 z-50 overflow-hidden text-left">
                    <div class="max-h-96 overflow-y-auto py-2">
                        @forelse ($categories as $category)
                            <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-900 hover:bg-blue-50 hover:text-blue-700 transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <span class="flex-1 truncate">{{ $category->translationValue('name') }}</span>
                            </a>
                        @empty
                            <div class="px-4 py-3 text-sm text-neutral-500">{{ __('No categories yet') }}</div>
                        @endforelse
                    </div>
                </div>
            </button>
            <form action="{{ route('tenant.storefront.search') }}" method="GET"
                data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}" class="flex-1">
                <div class="souqify-search-inner relative flex-1 h-12 bg-neutral-100 rounded flex items-center pe-2">
                    <div>
                        {{-- <button type="button"
                            onclick="document.getElementById('searchByMenu')?.classList.toggle('hidden')"
                            class="h-9 px-4 transition flex items-center gap-2 text-sm shrink-0 relative border-e border-neutral-300">
                            {{ __('All Categories') }}
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button> --}}
                        <div id="searchByMenu"
                            class="hidden absolute {{ app()->getLocale() == 'ar' ? 'right' : 'left'}}-0 top-full mt-2 w-72 bg-white rounded-xl shadow-2xl border border-neutral-100 z-50 overflow-hidden text-left">
                            <div class="max-h-96 overflow-y-auto py-2">
                                @forelse ($categories as $category)
                                    <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-900 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        <span class="flex-1 truncate">{{ $category->translationValue('name') }}</span>
                                    </a>
                                @empty
                                    <div class="px-4 py-3 text-sm text-neutral-500">{{ __('No categories yet') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="p-2 text-neutral-700 hover:text-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                        placeholder="{{ __('Search for premium tech, fashion, or home...') }}"
                        class="flex-1 bg-transparent outline-none text-sm text-neutral-700 placeholder:text-neutral-500" />
                    {{-- Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.). --}}
                    <button type="button" data-image-search-trigger="storefront-image-search-modal"
                        class="p-2 text-neutral-700 hover:text-blue-700 transition" aria-label="{{ __('Search by Image') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path d="M4 8a2 2 0 0 1 2-2h1l1.2-1.6A2 2 0 0 1 9.8 3.6h4.4a2 2 0 0 1 1.6.8L17 6h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                            <circle cx="12" cy="13" r="3.2" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        <x-image-search-modal id="storefront-image-search-modal" :action="route('tenant.storefront.search.image')" />

        <!-- Right icons -->
        <div class="flex items-center gap-4 sm:gap-6 lg:gap-8 ml-auto lg:ml-4">
            <!-- <a href="{{ route('tenant.storefront.favorites') }}"
                class="hidden sm:flex items-center gap-2 hover:text-blue-700 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="text-sm hidden xl:inline">{{ __('Wishlist') }}</span>
            </a> -->
            <!-- cart icon -->
            <a href="{{ route('tenant.storefront.cart') }}"
                class="flex items-center justify-center gap-2 hover:text-blue-700 transition relative w-[38px] h-[38px] lg:w-auto lg:h-auto rounded-full bg-white text-black">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                    <path
                        d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="{{ $cartCount > 0 ? '' : 'hidden lg:block' }}">
                    <span id="souqify-cart-badge"
                        class="souqify-cart-badge bg-blue-700 text-white text-[10px] rounded-full w-full h-[18px] flex items-center justify-center font-semibold leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                    <span class="text-xs hidden lg:block">{{ __('Cart') }}</span>
                </div>
            </a>
            <!-- user profile icon -->
            @auth('storefront')
                <a href="{{ route('tenant.storefront.profile') }}"
                    class="hidden md:flex items-center gap-1 hover:text-blue-700  text-charcoal transition-colors relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span
                        class="text-sm uppercase tracking-wide truncate max-w-[90px]">{{ auth('storefront')->user()->name }}</span>
                </a>
            @else
                <a href="{{ route('tenant.storefront.login') }}"
                    class="hidden md:flex items-center gap-2 hover:text-blue-700 transition">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span class="text-sm uppercase tracking-wide">{{ __('Account') }}</span>
                </a>
            @endauth
        </div>
    </div>

    <!-- Mobile search -->
    <div class="translate-y-1/2 relative z-50 drop-shadow-2xl px-4 sm:px-6 lg:hidden">
        <form action="{{ route('tenant.storefront.search') }}" method="GET"
            data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
            <div class="souqify-search-inner flex items-center bg-white rounded-full px-3 sm:px-5 py-2 gap-2 h-14">
                <svg class="w-5 h-5 text-neutral-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                    placeholder="{{ __('Search products...') }}"
                    class="flex-1 bg-transparent outline-none text-sm text-black" />
            </div>
        </form>
    </div>
</header>

<!-- Mobile menu drawer -->
<div id="mobileMenu" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeMobileMenu()"></div>
    <aside class="absolute start-0 top-0 h-full w-[80%] max-w-xs bg-white shadow-2xl p-6 overflow-y-auto">
        <div class="flex items-center justify-between mb-8">
            <x-storefront-logo :storeName="$storeName" class="h-8 w-auto" />
            <button onclick="closeMobileMenu()" class="p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <nav class="flex flex-col gap-1 text-sm font-medium">
            <a href="{{ route('tenant.home') }}" class="py-3 border-b border-neutral-200">{{ __('Home') }}</a>
            <a href="{{ route('tenant.storefront.best-selling') }}"
                class="py-3 border-b border-neutral-200">{{ __('Best Sellers') }}</a>
            <a href="{{ route('tenant.storefront.new-in') }}"
                class="py-3 border-b border-neutral-200">{{ __('New In') }}</a>
            @forelse ($categories as $category)
                <a href="{{ route('tenant.storefront.category', $category->slug) }}"
                    class="py-3 border-b border-neutral-200">{{ $category->name }}</a>
            @empty
            @endforelse
            @auth('storefront')
                <a href="{{ route('tenant.storefront.profile') }}"
                    class="py-3 border-b border-neutral-200">{{ __('Account') }}</a>
                <a href="{{ route('tenant.storefront.favorites') }}"
                    class="py-3 border-b border-neutral-200">{{ __('Wishlist') }}</a>
            @else
                <a href="{{ route('tenant.storefront.login') }}"
                    class="py-3 border-b border-neutral-200">{{ __('Sign In') }}</a>
            @endauth
        </nav>
    </aside>
</div>


<!-- ===== MOBILE BOTTOM NAV ===== -->
<nav class="lg:hidden fixed bottom-0 z-40 items-center shadow-2xl flex-1 flex justify-around gap-1 py-2.5 px-3 bg-white w-screen"
    id="mobileBottomNav">
    <a href="{{ route('tenant.home') }}"
        class="flex flex-col items-center justify-center w-16 h-[60px] rounded-xl text-[#8F8F8F] text-xs {{ request()->routeIs('tenant.home') ? 'bg-blue-700 text-white' : '' }}">
        <svg width="24" height="24" stroke="currentColor" viewBox="0 0 24 24" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M2.36465 12.958C1.98465 10.321 1.79465 9.002 2.33565 7.875C2.87665 6.748 4.02665 6.062 6.32765 4.692L7.71265 3.867C9.80065 2.622 10.8466 2 12.0006 2C13.1546 2 14.1996 2.622 16.2886 3.867L17.6736 4.692C19.9736 6.062 21.1246 6.748 21.6656 7.875C22.2066 9.002 22.0156 10.321 21.6356 12.958L21.3576 14.895C20.8706 18.283 20.6266 19.976 19.4516 20.988C18.2766 22 16.5536 22 13.1066 22H10.8946C7.44765 22 5.72465 22 4.54965 20.988C3.37465 19.976 3.13065 18.283 2.64365 14.895L2.36465 12.958Z"
                stroke-width="1.5" />
            <path d="M15 18H9" stroke-width="1.5" stroke-linecap="round" />
        </svg>

        <span>{{ __('Home') }}</span>
    </a>
    <a href="{{ route('tenant.storefront.favorites') }}"
        class="flex flex-col items-center justify-center w-16 h-[60px] rounded-xl text-[#8F8F8F] text-xs {{ request()->routeIs('tenant.favorites') ? 'bg-blue-700 text-white' : '' }}">
        <svg width="22" height="20" viewBox="0 0 22 20" fill="none" stroke="currentColor"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M11.37 18.46C11.03 18.58 10.47 18.58 10.13 18.46C7.23 17.47 0.75 13.34 0.75 6.34C0.75 3.25 3.24 0.75 6.31 0.75C8.13 0.75 9.74 1.63 10.75 2.99C11.76 1.63 13.38 0.75 15.19 0.75C18.26 0.75 20.75 3.25 20.75 6.34C20.75 13.34 14.27 17.47 11.37 18.46Z"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <span>{{ __('Favorites') }}</span>
    </a>
    <a href="{{ route('tenant.storefront.offers') }}"
        class="flex flex-col items-center justify-center w-16 h-[60px] rounded-xl text-[#8F8F8F] text-xs {{ request()->routeIs('tenant.storefront.offers') ? 'bg-blue-700 text-white' : '' }}">
        <svg width="20" height="24" viewBox="0 0 20 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M7.05006 0.290359C7.15511 0.171185 7.29121 0.0835505 7.44317 0.0372357C7.59513 -0.00907918 7.75697 -0.0122514 7.91063 0.0280731C9.81178 0.523502 12.7329 1.81093 15.1946 4.10293C17.6752 6.4155 19.7049 9.77036 19.7049 14.3458C19.7049 16.1578 19.2986 18.3744 17.8912 20.2104C16.4615 22.0789 14.0906 23.4332 10.4118 23.6612H10.4015C5.49863 23.9081 2.05635 21.3606 0.672918 17.8515C-0.698511 14.3732 -0.0110824 10.0464 3.07977 6.7635C3.16154 6.6764 3.2606 6.60735 3.37062 6.56079C3.48063 6.51423 3.59916 6.49117 3.71861 6.49311C3.83806 6.49505 3.95578 6.52193 4.06423 6.57204C4.17268 6.62214 4.26945 6.69437 4.34835 6.78407L6.51692 9.24579C7.31063 7.82979 7.69635 6.5955 7.75463 5.3595C7.81463 4.10293 7.53692 2.77607 6.89749 1.17664C6.83855 1.02913 6.82177 0.868135 6.84901 0.711634C6.87626 0.555133 6.94646 0.409282 7.05177 0.290359M6.96606 13.3275C6.96606 12.9865 7.10152 12.6595 7.34264 12.4184C7.58376 12.1772 7.91078 12.0418 8.25177 12.0418C8.59277 12.0418 8.91979 12.1772 9.16091 12.4184C9.40203 12.6595 9.53749 12.9865 9.53749 13.3275C9.53749 13.6685 9.40203 13.9955 9.16091 14.2366C8.91979 14.4778 8.59277 14.6132 8.25177 14.6132C7.91078 14.6132 7.58376 14.4778 7.34264 14.2366C7.10152 13.9955 6.96606 13.6685 6.96606 13.3275ZM13.8061 13.5435C13.9668 13.3826 14.057 13.1644 14.0568 12.9369C14.0567 12.7094 13.9662 12.4914 13.8052 12.3306C13.6443 12.1699 13.4261 12.0797 13.1986 12.0799C12.9711 12.08 12.7531 12.1706 12.5923 12.3315L6.96263 17.9595C6.88077 18.0386 6.81547 18.1332 6.77054 18.2377C6.72562 18.3423 6.70198 18.4548 6.70099 18.5686C6.7 18.6824 6.72169 18.7953 6.76478 18.9006C6.80788 19.0059 6.87153 19.1016 6.95201 19.1821C7.03249 19.2626 7.12819 19.3263 7.23353 19.3694C7.33887 19.4124 7.45174 19.4341 7.56555 19.4331C7.67936 19.4322 7.79183 19.4085 7.89641 19.3636C8.00098 19.3187 8.09556 19.2534 8.17463 19.1715L13.8061 13.5435ZM11.5261 18.1772C11.5261 17.8362 11.6615 17.5092 11.9026 17.2681C12.1438 17.027 12.4708 16.8915 12.8118 16.8915C13.1528 16.8915 13.4798 17.027 13.7209 17.2681C13.962 17.5092 14.0975 17.8362 14.0975 18.1772C14.0975 18.5182 13.962 18.8452 13.7209 19.0864C13.4798 19.3275 13.1528 19.4629 12.8118 19.4629C12.4708 19.4629 12.1438 19.3275 11.9026 19.0864C11.6615 18.8452 11.5261 18.5182 11.5261 18.1772Z"
                fill="url(#paint0_linear_181_26077)" />
            <defs>
                <linearGradient id="paint0_linear_181_26077" x1="-4.25881e-09" y1="21.2909" x2="19.8224" y2="21.1809"
                    gradientUnits="userSpaceOnUse">
                    <stop stop-color="#C94F1A" />
                    <stop offset="0.575759" stop-color="#F56323" />
                    <stop offset="1" stop-color="#BA3800" />
                </linearGradient>
            </defs>
        </svg>

        <span>{{ __('Offers') }}</span>
    </a>
    <a href="{{ route('tenant.storefront.profile') }}"
        class="flex flex-col items-center justify-center w-16 h-[60px] rounded-xl text-[#8F8F8F] text-xs {{ request()->routeIs('tenant.orders') ? 'bg-blue-700 text-white' : '' }}">
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M19.75 6.25L15.75 8.25M15.75 8.25L15.25 8.5L10.75 10.75M15.75 8.25V11.75M15.75 8.25L6.25 3.25M10.75 10.75L1.75 6.25M10.75 10.75V20.25M14.328 2.132L16.328 3.182C18.479 4.311 19.555 4.875 20.153 5.89C20.75 6.904 20.75 8.167 20.75 10.692V10.809C20.75 13.333 20.75 14.596 20.153 15.61C19.555 16.625 18.479 17.19 16.328 18.319L14.328 19.368C12.572 20.289 11.694 20.75 10.75 20.75C9.806 20.75 8.928 20.29 7.172 19.368L5.172 18.318C3.021 17.189 1.945 16.625 1.347 15.61C0.75 14.596 0.75 13.333 0.75 10.81V10.693C0.75 8.168 0.75 6.905 1.347 5.891C1.945 4.876 3.021 4.311 5.172 3.183L7.172 2.133C8.928 1.211 9.806 0.75 10.75 0.75C11.694 0.75 12.572 1.21 14.328 2.132Z"
                stroke-width="1.5" stroke-linecap="round" />
        </svg>


        <span>{{ __('Orders') }}</span>
    </a>
    @auth('storefront')
        <a href="{{ route('tenant.storefront.profile') }}"
            class="flex flex-col items-center justify-center w-16 h-[60px] rounded-xl text-[#8F8F8F] text-xs {{ request()->routeIs('tenant.profile') ? 'bg-blue-700 text-white' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            <span>{{ __('Profile') }}</span>
        </a>
    @else
        <a href="{{ route('tenant.storefront.login') }}"
            class="flex flex-col items-center justify-center w-16 h-[60px] rounded-xl text-[#8F8F8F] text-xs {{ request()->routeIs('tenant.profile') ? 'bg-blue-700 text-white' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            <span>{{ __('Sign In') }}</span>
        </a>
    @endauth
</nav>

<script>
    document.addEventListener('click', function () {
        document.getElementById('souqifyDeptMenu')?.classList.add('hidden');
    });
</script>
