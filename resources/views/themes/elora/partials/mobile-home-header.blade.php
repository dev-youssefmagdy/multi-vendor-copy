@php
    $selectedCountryId = session('storefront_country_id');
    $selectedCountry = $selectedCountryId ? app(\App\Services\Tenant\HomeViewData::class)->country($selectedCountryId) : null;
    $currency = $currentCurrency ?? null;
@endphp
    <!--========= mobile header ==========  -->
    <div class="sm:hidden {{ ($tight ?? false) ? 'mb-8' : 'mb-8' }}" style="background: linear-gradient(89.62deg, #C94F1A 0.07%, #F56323 57.6%, #BA3800 100%);">
        <div class="px-4 pt-3 pb-0 flex flex-col gap-3">
            <!-- User info row -->
            <div class="flex items-center justify-between">
                <!-- Left: avatar + greeting -->
                @auth('storefront')
                @php $authCustomer = auth('storefront')->user(); @endphp
                <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-1.5 min-w-0">
                    <!-- Default avatar circle -->
                    <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 overflow-hidden border border-white/30">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none">
                            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="flex flex-col gap-[3px] min-w-0">
                        <span class="text-white text-lg font-medium leading-[23px] truncate" style="font-family:'Outfit',sans-serif;">
                            {{ __('Hi') }}, {{ $authCustomer->full_name ?? $authCustomer->name }} 👋
                        </span>
                        <!-- Subtitle: opens currency & country selector -->
                        <button type="button"
                            onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                            class="flex items-center gap-1 text-left group">
                            <span class="text-white text-xs font-medium tracking-wide leading-[15px] truncate" style="font-family:'Outfit',sans-serif;">
                                @php
                                    $currencyLabel = $currentCurrency?->code ?? __('Default');
                                    $langLabel = $currentLanguage?->name ?? __('Default');
                                    if ($selectedCountry) {
                                        $countryLabel = $selectedCountry->name;
                                    } elseif ($authCustomer->country_id) {
                                        $countryLabel = app(\App\Services\Tenant\HomeViewData::class)->country($authCustomer->country_id)?->name ?? __('Default');
                                    } else {
                                        $countryLabel = __('Default');
                                    }
                                @endphp
                                {{ $currencyLabel }} &bull; {{ $langLabel }} &bull; {{ $countryLabel }}
                            </span>
                            <svg class="w-4 h-4 flex-shrink-0 text-white" viewBox="0 0 24 24" fill="none">
                                <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </a>
                @else
                <div class="flex items-center gap-1.5 min-w-0">
                    <a href="{{ route('tenant.storefront.login') }}" class="flex-shrink-0">
                        <!-- Default avatar circle for guest -->
                        <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center border border-white/30">
                            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none">
                                <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </a>
                    <div class="flex flex-col gap-[3px] min-w-0">
                        <a href="{{ route('tenant.storefront.login') }}">
                            <span class="text-white text-lg font-medium leading-[23px] truncate" style="font-family:'Outfit',sans-serif;">{{ __('Login / Register') }}</span>
                        </a>
                        <!-- Subtitle: opens currency & country selector -->
                        <button type="button"
                            onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                            class="flex items-center gap-1 text-left group">
                            <span class="text-white text-xs font-medium tracking-wide leading-[15px] truncate" style="font-family:'Outfit',sans-serif;">
                                @php
                                    $currencyLabel = $currentCurrency?->code ?? __('Default');
                                    $langLabel = $currentLanguage?->name ?? __('Default');
                                    $countryLabel = $selectedCountry->name ?? __('Default');
                                @endphp
                                {{ $currencyLabel }} &bull; {{ $langLabel }} &bull; {{ $countryLabel }}
                            </span>
                            <svg class="w-4 h-4 flex-shrink-0 text-white" viewBox="0 0 24 24" fill="none">
                                <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endauth

                <!-- Right: cart + notification/bell buttons -->
                <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('tenant.storefront.cart') }}" aria-label="{{ __('My Cart') }}"
                    class="relative w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <path d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                            stroke="#F56323" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="elora-cart-badge absolute -top-1 -right-1 bg-[#F56323] text-white text-[10px] font-bold rounded-full min-w-4 h-4 flex items-center justify-center px-1 leading-none {{ ($cartCount ?? 0) > 0 ? '' : 'hidden' }}">{{ $cartCount ?? 0 }}</span>
                </a>
                <a href="{{ route('tenant.storefront.favorites') }}"
                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                    <!-- Bell icon -->
                    <svg class="w-[18px] h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.7453 9C18.7453 5.25196 15.7479 2.21484 12.0453 2.21484C8.34277 2.21484 5.34534 5.25196 5.34534 9C5.34534 14.3284 3.59534 16.0784 3.59534 16.0784H20.4953C20.4953 16.0784 18.7453 14.3284 18.7453 9Z"
                            stroke="#F56323" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.7299 19.5781C13.5561 19.877 13.307 20.1244 13.0072 20.2962C12.7075 20.468 12.3674 20.5584 12.0212 20.5584C11.675 20.5584 11.3349 20.468 11.0351 20.2962C10.7354 20.1244 10.4862 19.877 10.3124 19.5781"
                            stroke="#F56323" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                </div>
            </div>

            <!-- Search bar — translates down to overlap the section below -->
            <div class="translate-y-1/2 relative z-50 drop-shadow-2xl">
                <form action="{{ route('tenant.storefront.search') }}" method="GET"
                    data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                    <div class="elora-search-inner flex items-center bg-[#F6F5F5] rounded-[32px] px-3 py-2 gap-2 h-14 border border-[#D4D4D4]"
                        style="position:relative">
                        <!-- Search icon (mirrored per Figma design) -->
                        <svg class="w-5 h-5 flex-shrink-0 text-[#8F8F8F]" style="transform:scaleX(-1);"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="{{ __('Search...') }}" autocomplete="off"
                            class="bg-transparent flex-1 text-base text-[#8F8F8F] placeholder-[#8F8F8F] min-w-0 outline-none font-normal"
                            style="font-family:'Outfit',sans-serif;" />
                        <!-- Camera / image search -->
                        <button type="button" aria-label="{{ __('Search by Image') }}"
                            class="flex items-center justify-end ps-1.5 border-s border-[#8F8F8F] flex-shrink-0"
                            data-image-search-trigger="storefront-image-search-modal-mobile-home">
                            <svg class="w-5 h-5 text-[#171717]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path d="M4 8a2 2 0 0 1 2-2h1l1.2-1.6A2 2 0 0 1 9.8 3.6h4.4a2 2 0 0 1 1.6.8L17 6h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                                <circle cx="12" cy="13" r="3.2" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-image-search-modal id="storefront-image-search-modal-mobile-home" :action="route('tenant.storefront.search.image')" />
