    <!--========= mobile header ==========  -->
    <div class="lg:hidden text-white bg-gradient-to-r from-[#002562] via-[#144AA6] to-[#003387] {{ ($tight ?? false) ? 'mb-8' : 'pb-3' }} pt-6 px-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <!-- Mobile menu toggle -->
                <button onclick="openMobileMenu()" class="lg:hidden p-2 -ml-2" aria-label="{{ __('Open menu') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <!-- Logo -->
                <a href="{{ route('tenant.home') }}" class="flex items-center gap-2 shrink-0">
                    <x-storefront-logo :storeName="$storeName" class="h-8 sm:h-10 lg:h-12 w-auto" />
                </a>
            </div>
            <div class="flex items-center gap-2">
                <!-- Currency / Language -->
                <button type="button"
                    onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                    class="flex items-center justify-center text-main transition-colors bg-white text-black rounded-full w-10 h-10">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3c2.5 2.6 3.75 5.7 3.75 9s-1.25 6.4-3.75 9c-2.5-2.6-3.75-5.7-3.75-9S9.5 5.6 12 3z" />
                    </svg>
                </button>
                <!-- cart -->
                <a href="{{ route('tenant.storefront.cart') }}"
                    class="relative flex items-center justify-center text-main transition-colors bg-white text-black rounded-full w-10 h-10">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span id="souqify-cart-badge-mobile" class="souqify-cart-badge absolute -top-1 -end-1 bg-[var(--sq-primary)] text-white text-[10px] rounded-full min-w-[16px] h-[16px] px-1 flex items-center justify-center font-semibold leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                </a>
            </div>
        </div>
        <!-- Mobile search -->
        <div class="{{ ($tight ?? false) ? 'translate-y-1/2' : 'mt-3' }} relative z-50 drop-shadow-2xl px-4 sm:px-6 lg:hidden">
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
    </div>
