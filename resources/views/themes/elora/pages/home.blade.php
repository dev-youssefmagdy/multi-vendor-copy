@push('body-attrs')data-page="index"@endpush

@php
$currency = $currentCurrency ?? null;
$symbol = data_get($currency, 'symbol', '$');
$rate = (float) data_get($currency, 'conversion_rate', 1.0);
$selectedCountryId = session('storefront_country_id');
$selectedCountry = $selectedCountryId ? \App\Models\Country::find($selectedCountryId) : null;
@endphp

@push('head')
    @vite('resources/css/elora/home.css')
@endpush

<main class="w-full">

    <!--========= mobile header ==========  -->
    <div class="sm:hidden mb-14" style="background: linear-gradient(89.62deg, #C94F1A 0.07%, #F56323 57.6%, #BA3800 100%);">
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
                                        $countryLabel = \App\Models\Country::find($authCustomer->country_id)?->name ?? __('Default');
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
                            <span class="text-white text-lg font-medium leading-[23px] truncate" style="font-family:'Outfit',sans-serif;">{{ __('Guest') }}</span>
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

                <!-- Right: notification/bell button -->
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
                        <!-- Camera / scan icon with left border divider -->
                        <div class="flex items-center justify-end pl-1.5 border-l border-[#8F8F8F]">
{{--                            <svg class="w-5 h-[18px] text-[#171717]" viewBox="0 0 24 24" fill="currentColor">--}}
{{--                                <path d="M12 8.5c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5zm0 7c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zM20 4h-3.17L15 2H9L7.17 4H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H4V6h4.05l1.83-2h4.24l1.83 2H20v14z"/>--}}
{{--                            </svg>--}}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Hero Banner ────────────────────────────────────────────────────── --}}
    <section class="hero-wrapper" wire:ignore>
        <div class="swiper hero-slider max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="swiper-wrapper">
                @foreach ($banners as $idx => $banner)
                @php
                $img = $banner->image_path ?? null;
                $url = $img
                ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
                : null;
                @endphp
                @if ($url)
                <div class="swiper-slide">
                    <img loading="lazy" src="{{ $url }}" alt="{{ $banner->title ?? $storeName }}" class="hero-img" draggable="false" />
                </div>
                @endif
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
            <!-- <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div> -->
        </div>
        <!-- Navigation buttons -->
         @if(app()->getLocale() === 'ar')
                 <div class="swiper-button-prev hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-start: 1.5rem; inset-inline-end: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        <div class="swiper-button-next hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-end: 1.5rem; inset-inline-start: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        @else
        <div class="swiper-button-prev hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-start: 1.5rem; inset-inline-end: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        <div class="swiper-button-next hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-end: 1.5rem; inset-inline-start: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        @endif


        @if ($banners->isEmpty())
        <div class="hero-slide active" data-idx="0">
            <div class="hero-img flex items-center justify-center bg-gradient-to-br from-charcoal to-[#444]">
                <h1 class="text-white text-3xl sm:text-5xl font-black tracking-wide text-center px-6">{{ $storeName }}
                </h1>
            </div>
        </div>
        @endif
    </section>

    {{-- ── Trust Bar ────────────────────────────────────────────────────────── --}}
    <div
        class="bg-gradient-to-r from-[#f56323] to-[#c94f1a] md:from-transparent md:to-transparent  md:bg-charcoal py-3 sm:py-4">
        <div
            class="max-w-6xl mx-auto px-3 flex overflow-x-auto whitespace-nowrap items-center md:justify-center gap-3 sm:gap-8 lg:gap-[75px] no-scrollbar">
            <div class="flex items-center gap-2 text-white">
                <svg width="23" height="31" viewBox="0 0 23 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M13.73 17.2695C13.73 15.9392 12.6516 14.8609 11.3214 14.8609C9.99116 14.8609 8.91279 15.9392 8.91279 17.2695C8.9108 17.8334 9.11164 18.3793 9.47867 18.8075C9.84848 19.1929 9.97668 19.7502 9.81239 20.2585L9.50769 21.1581C9.38985 21.5626 9.46774 21.999 9.71828 22.3378C9.96881 22.6766 10.3632 22.8789 10.7845 22.8847H11.8583C12.2935 22.8846 12.702 22.6745 12.9553 22.3205C13.2086 21.9665 13.2755 21.512 13.1351 21.1L12.7724 20.2004C12.6081 19.6922 12.7363 19.1348 13.1061 18.7495C13.4814 18.3447 13.7023 17.8207 13.73 17.2695Z"
                        fill="white" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M18.2135 8.88286V4.35584C18.2143 3.17318 17.7338 2.04111 16.8827 1.22003C16.0315 0.398959 14.8829 -0.0404337 13.701 0.00293067H8.94181C7.75995 -0.0404337 6.61132 0.398959 5.76014 1.22003C4.90895 2.04111 4.4285 3.17318 4.4293 4.35584V8.88286C1.42364 9.94111 -0.40429 12.9877 0.0763901 16.1377L1.45481 24.5098C2.13468 27.8447 5.10382 30.2152 8.50652 30.1396H14.1363C17.5633 30.2256 20.5537 27.8296 21.217 24.4663L22.5954 16.0942C23.05 12.9512 21.2125 9.92723 18.2135 8.88286ZM6.60575 4.35584C6.66068 3.11908 7.7048 2.15926 8.94181 2.2084H13.701C14.938 2.15926 15.9821 3.11908 16.037 4.35584V8.37502H6.60575V4.35584ZM18.5762 24.031L20.0272 15.6299C20.1712 14.543 19.8258 13.4483 19.0841 12.6409C18.1821 11.618 16.8783 11.0403 15.5147 11.0593H7.1281C5.76911 11.033 4.46603 11.5998 3.55871 12.6119C2.83471 13.4386 2.51571 14.5445 2.68813 15.6299L4.06655 24.031C4.51643 26.1005 6.39057 27.546 8.50652 27.4553H14.1363C16.2522 27.546 18.1264 26.1005 18.5762 24.031Z"
                        fill="white" />
                </svg>

                <span class="text-xs sm:text-sm font-medium whitespace-nowrap">{{ __('Secure Privacy') }}</span>
            </div>
            <div class="flex items-center gap-2 text-white">
                <svg width="30" height="21" viewBox="0 0 30 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M23.2155 0H5.80388C2.59848 0 0 2.59848 0 5.80388V14.5097C0 17.7151 2.59848 20.3136 5.80388 20.3136H23.2155C26.4209 20.3136 29.0194 17.7151 29.0194 14.5097V5.80388C29.0194 2.59848 26.4209 0 23.2155 0ZM5.80388 2.17645H23.2155C24.1776 2.17645 25.1002 2.55863 25.7805 3.2389C26.4608 3.91917 26.8429 4.84182 26.8429 5.80388V11.9705H2.17645V5.80388C2.17645 3.80051 3.80051 2.17645 5.80388 2.17645ZM5.80388 18.1371H23.2155C24.1776 18.1371 25.1002 17.7549 25.7805 17.0747C26.4608 16.3944 26.8429 15.4717 26.8429 14.5097V14.1469H2.17645V14.5097C2.17645 16.5131 3.80051 18.1371 5.80388 18.1371Z"
                        fill="white" />
                </svg>

                <span class="text-xs sm:text-sm font-medium whitespace-nowrap">{{ __('Safe payments') }}</span>
            </div>
            <div class="flex items-center gap-2 text-white">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M14.5097 27.5684C21.7217 27.5684 27.5684 21.7217 27.5684 14.5097C27.5684 7.29765 21.7217 1.45097 14.5097 1.45097C7.29765 1.45097 1.45097 7.29765 1.45097 14.5097C1.45097 21.7217 7.29765 27.5684 14.5097 27.5684ZM14.5097 29.0194C22.5234 29.0194 29.0194 22.5234 29.0194 14.5097C29.0194 6.49599 22.5234 0 14.5097 0C6.49599 0 0 6.49599 0 14.5097C0 22.5234 6.49599 29.0194 14.5097 29.0194Z"
                        fill="#FDFDFD" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M22.2502 8.89373C22.3929 9.02267 22.4786 9.20302 22.4884 9.39511C22.4982 9.5872 22.4313 9.77532 22.3024 9.91811L12.6128 20.627L6.75382 15.0342C6.62226 14.8998 6.54806 14.7195 6.54689 14.5314C6.54572 14.3433 6.61765 14.1621 6.74753 14.026C6.8774 13.8899 7.05505 13.8096 7.24301 13.802C7.43097 13.7943 7.61453 13.86 7.75498 13.9852L12.5359 18.5485L21.2265 8.94524C21.2905 8.87457 21.3677 8.81719 21.4539 8.77638C21.54 8.73558 21.6333 8.71215 21.7285 8.70743C21.8237 8.70272 21.9189 8.7168 22.0087 8.7489C22.0984 8.78099 22.1795 8.82972 22.2502 8.89373Z"
                        fill="#FDFDFD" />
                </svg>

                <span class="text-xs sm:text-sm font-medium whitespace-nowrap">{{ __('Easy recovery') }}</span>
            </div>
            <div class="flex items-center gap-2 text-white">
                <svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M21.7649 2.90186V17.4115C21.7649 19.0076 20.4591 20.3135 18.863 20.3135H2.90234V11.0563C3.96155 12.3186 5.58668 13.1022 7.38588 13.0586C8.85136 13.0296 10.1717 12.4637 11.1584 11.5206C11.6082 11.1434 11.9854 10.6645 12.2756 10.1422C12.798 9.25709 13.0881 8.21236 13.0591 7.12414C13.0156 5.4265 12.2611 3.93204 11.0858 2.90186H21.7649Z"
                        stroke="white" stroke-width="1.16078" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M31.9217 20.3136V24.6665C31.9217 27.0751 29.9774 29.0194 27.5688 29.0194H26.1178C26.1178 27.4234 24.812 26.1175 23.2159 26.1175C21.6198 26.1175 20.314 27.4234 20.314 29.0194H14.5101C14.5101 27.4234 13.2042 26.1175 11.6082 26.1175C10.0121 26.1175 8.70622 27.4234 8.70622 29.0194H7.25525C4.84664 29.0194 2.90234 27.0751 2.90234 24.6665V20.3136H18.863C20.4591 20.3136 21.7649 19.0077 21.7649 17.4117V7.25488H24.4348C25.4795 7.25488 26.4371 7.82077 26.9594 8.72038L29.4405 13.0588H27.5688C26.7708 13.0588 26.1178 13.7117 26.1178 14.5097V18.8626C26.1178 19.6607 26.7708 20.3136 27.5688 20.3136H31.9217Z"
                        stroke="white" stroke-width="1.16078" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M11.609 31.9216C13.2117 31.9216 14.5109 30.6223 14.5109 29.0196C14.5109 27.4169 13.2117 26.1177 11.609 26.1177C10.0063 26.1177 8.70703 27.4169 8.70703 29.0196C8.70703 30.6223 10.0063 31.9216 11.609 31.9216Z"
                        stroke="white" stroke-width="1.16078" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M23.2144 31.9216C24.8171 31.9216 26.1164 30.6223 26.1164 29.0196C26.1164 27.4169 24.8171 26.1177 23.2144 26.1177C21.6117 26.1177 20.3125 27.4169 20.3125 29.0196C20.3125 30.6223 21.6117 31.9216 23.2144 31.9216Z"
                        stroke="white" stroke-width="1.16078" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M31.9211 17.4115V20.3134H27.5682C26.7701 20.3134 26.1172 19.6605 26.1172 18.8625V14.5096C26.1172 13.7115 26.7701 13.0586 27.5682 13.0586H29.4399L31.9211 17.4115Z"
                        stroke="white" stroke-width="1.16078" stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M13.0586 7.12405C13.0877 8.21228 12.7975 9.257 12.2751 10.1421C11.985 10.6644 11.6077 11.1433 11.1579 11.5205C10.1712 12.4637 8.85086 13.0295 7.38539 13.0586C5.58618 13.1021 3.96106 12.3185 2.90185 11.0562C2.69872 10.8386 2.52461 10.5919 2.365 10.3452C1.79912 9.48917 1.4799 8.47353 1.45088 7.3853C1.40736 5.55708 2.21989 3.88842 3.52576 2.80019C4.51242 1.98765 5.76022 1.47982 7.12413 1.4508C8.64765 1.42178 10.0406 1.97315 11.0853 2.90177C12.2606 3.93196 13.0151 5.42641 13.0586 7.12405Z"
                        stroke="white" stroke-width="1.16078" stroke-miterlimit="10" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M4.99219 7.29837L6.45768 8.69124L9.49016 5.76025" stroke="white" stroke-width="1.16078"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span class="text-xs sm:text-sm font-medium whitespace-nowrap">{{ __('Free shipping') }}</span>
            </div>
        </div>
    </div>

        {{-- ── Category Circles for small screen (2-row scrollable grid) ──────── --}}
    @if ($categories->isNotEmpty())
    <section class="py-4 bg-white border-t border-gray-100 sm:hidden" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-charcoal">{{ __('Categories') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}"
                    class="text-sm font-semibold text-main">{{ __('see all') }}</a>
            </div>
            {{-- 2-row CSS grid that scrolls horizontally --}}
            <div class="overflow-x-auto no-scrollbar -mx-3 px-3">
                <div style="display:grid;grid-template-rows:repeat(2,auto);grid-auto-flow:column;grid-auto-columns:max-content;gap:12px 10px;">
                    @foreach ($categories as $cat)
                    <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                        class="flex flex-col items-center gap-1.5 group" style="width:72px">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center group-hover:ring-2 group-hover:ring-main transition-all overflow-hidden m-1">
                            @if ($cat->thumb_url ?? null)
                                <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-2xl">🛍️</span>
                            @endif
                        </div>
                        <span class="text-[11px] text-center text-charcoal leading-tight line-clamp-2">{{ $cat->translationValue('name') ?? $cat->name }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ── Tabbed Products (Flash / Best-selling / New In / Top-rated) ──────────────────── --}}
    <section class="py-5 sm:py-8 bg-white" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">

            {{-- Tab pills --}}
            @php $pillBase = 'flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap flex-shrink-0'; @endphp
            <div class="flex items-center md:justify-center gap-2 mb-4 overflow-x-auto no-scrollbar pb-1">
                <button data-tab="flash" type="button"
                    class="cat-pill tabbed-pill active {{ $pillBase }} border border-main bg-main text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ __('Flash products') }}
                </button>

                <button data-tab="bestselling" type="button"
                    class="cat-pill tabbed-pill {{ $pillBase }} border border-main-light text-main">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    {{ __('Best-selling') }}
                </button>

                <button data-tab="newin" type="button"
                    class="cat-pill tabbed-pill {{ $pillBase }} border border-main-light text-main">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('New in') }}
                </button>

                <button data-tab="toprated" type="button"
                    class="cat-pill tabbed-pill {{ $pillBase }} border border-main-light text-main">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    {{ __('Top-rated') }}
                </button>
            </div>

            {{-- Single product swiper — populated server-side on first load, then via AJAX --}}
            <div id="tabbed-products-swiper-wrap">
                <div class="swiper product-slid-swiper" id="tabbed-products-swiper">
                    <div class="swiper-wrapper" id="tabbed-products-wrapper">
                        @forelse ($flashProducts as $product)
                        <div class="swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]">
                            @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('Flash Sale')])
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 py-6">{{ __('No flash sale products at the moment.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Show More --}}
            {{--<div class="flex justify-center mt-6" id="tabbed-products-more"
                @if(!($hasMoreFlash ?? false)) style="display:none" @endif>
                <button type="button" id="tabbed-products-more-btn"
                    class="flex items-center gap-2 px-8 py-3 rounded-full border-2 border-main text-main text-sm font-semibold hover:bg-main hover:text-white transition-colors">
                    {{ __('Show More') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>--}}
        </div>
    </section>


    {{-- ── Category Circles for large screen (2-row scrollable grid) ──────────────────── --}}
    @if ($categories->isNotEmpty())
    <section class="py-5 sm:py-8 bg-white border-t border-gray-100 hidden sm:block" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-bold text-charcoal">{{ __('Categories') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}"
                    class="text-sm font-semibold text-main">{{ __('See all') }}</a>
            </div>
            {{-- 2-row CSS grid that scrolls horizontally --}}
            <div class="overflow-x-auto no-scrollbar -mx-3 px-3">
                <div style="display:grid;grid-template-rows:repeat(2,auto);grid-auto-flow:column;grid-auto-columns:85px;gap:24px 32px;">
                    @foreach ($categories as $cat)
                    <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                        class="flex flex-col items-center gap-0 group" style="width:85px">
                        <div class="w-[81px] h-[81px] rounded-full bg-gray-100 flex items-center justify-center group-hover:ring-2 group-hover:ring-main transition-all overflow-hidden mb-[12px] m-1">
                            @if ($cat->thumb_url ?? null)
                            <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->name }}" class="w-full h-full object-cover">
                            @else
                            <span class="text-2xl">🛍️</span>
                            @endif
                        </div>
                        <span class="text-[14px] text-center text-charcoal leading-[18px] tracking-[0.5px] line-clamp-2"
                            style="width:85px">{{ $cat->translationValue('name') ?? $cat->name }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ── Flash Sale Strip ─────────────────────────────────────────────────────── --}}
    @if ($flashSales->isNotEmpty())
    @php $firstSale = $flashSales->first(); @endphp
    <section  wire:ignore>
        <div class="w-full overflow-hidden">
            @if ($flashBanner)
            <div>
                <img loading="lazy" src="{{ $flashBanner }}" alt="{{ __('Flash Sale') }}" class="w-full h-auto object-cover"
                    style="max-height:180px;object-position:center;" />
            </div>
            @endif
            <div class="py-5 sm:py-6"
                style="background:linear-gradient(89.62deg,#C94F1A,#2DCF9C 23%,#E0A340 58%,#F23F3F 72%,#BA3800 100%);">
                <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
                        {{-- Countdown timer --}}
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            @if ($firstSale->end_date)
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl elora-fs-h"
                                data-countdown="{{ $firstSale->end_date->timestamp }}">--h</div>
                            <span class="text-white font-bold text-lg sm:text-2xl">:</span>
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl elora-fs-m">
                                --m</div>
                            <span class="text-white font-bold text-lg sm:text-2xl">:</span>
                            <div class="countdown-box text-white font-bold text-2xl sm:text-3xl lg:text-4xl elora-fs-s">
                                --s</div>
                            @else
                            <span
                                class="text-white font-bold text-2xl sm:text-3xl">{{ __('Flash Sale On Now!') }}</span>
                            @endif
                        </div>
                        {{-- CTA --}}
                        <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                            <p class="text-white/90 text-xs sm:text-sm">
                                {{ __('Save on everything.') }}<br />
                                {{ __('Best seller + more') }}
                            </p>
                            <a href="{{ route('tenant.storefront.best-selling') }}"
                                class="bg-charcoal text-white text-xs sm:text-sm px-3 sm:px-5 py-2 rounded-full hover:bg-black transition-colors whitespace-nowrap">
                                {{ __('Shop flash deals') }}
                            </a>
                        </div>
                    </div>
                    {{-- products slide --}}
                    <div class="swiper product-slid-swiper">
                        <div class="swiper-wrapper">
                            @forelse ($flashProducts as $product)
                            <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                                @include('themes.elora.pages._product-card', ['product' => $product, 'badge' =>
                                __('Flash
                                Sale')])
                            </div>
                            @empty
                            <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif


    {{-- ── Trending Now ─────────────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="hidden sm:block"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="51" height="30" viewBox="0 0 51 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M31.3102 2.73516L31.5496 8.3245L39.1349 8.0053L26.8475 19.1788L16.6723 8.95057L0 23.8739L5.88802 30L17.1278 19.5878L27.2723 29.1001L43.7628 12.2196L43.7658 20.4386L51 20.1333L50.8473 0L31.3102 2.73516Z"
                            fill="url(#paint0_linear_3_1149)" />
                        <defs>
                            <linearGradient id="paint0_linear_3_1149" x1="-1.10226e-08" y1="26.9758" x2="51.2992"
                                y2="26.3943" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('Trending Now') }}</h2>
                </div>
                <a href="{{ route('tenant.storefront.new-in') }}"
                    class="flex items-center gap-1.5 text-xs sm:text-sm text-main hover:underline font-medium">
                    {{ __('See all') }}
                    <svg width="5" height="12" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.75 0.75L5.34317 5.68939C5.88561 6.27273 5.88561 7.22727 5.34317 7.81061L0.75 12.75"
                            stroke="url(#paint0_linear_13_11426)" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_13_11426" x1="0.75" y1="1.95968" x2="5.77994" y2="1.97365"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>
                </a>
            </div>
            {{-- products slide --}}
            <div class="swiper product-slid-swiper" wire:ignore>
                <div class="swiper-wrapper">
                    @forelse ($trendingNowProducts as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('New In')])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No trending products yet.') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    </section>

    {{-- ── Orange Category Strip ────────────────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
    <div class="py-4 sm:py-6" style="background:linear-gradient(89.62deg,#C94F1A 0%,#F56323 58%,#BA3800 100%);"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6 flex gap-4 sm:gap-6 overflow-x-auto no-scrollbar">
            @foreach ($categories as $cat)
            <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                class="flex flex-col items-center gap-1.5 sm:gap-2 flex-shrink-0" style="min-width:70px">
                <div class="w-14 h-14 sm:w-20 sm:h-20 rounded-full flex items-center justify-center overflow-hidden"
                    style="background:rgba(255,255,255,.2)">
                    @if ($cat->thumb_url ?? null)
                    <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->name }}" class="w-full h-full object-cover">
                    @else
                    <span class="text-2xl sm:text-3xl">🛍️</span>
                    @endif
                </div>
                <span class="text-xs text-white text-center">{{ \Str::words($cat->translationValue('name') ?? $cat->name, 2) }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Best Seller ──────────────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="hidden sm:block"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5.32422 13.7748V19.9873C5.32422 22.2623 5.32422 22.2623 7.47422 23.7123L13.3867 27.1248C14.2742 27.6373 15.7242 27.6373 16.6117 27.1248L22.5242 23.7123C24.6742 22.2623 24.6742 22.2623 24.6742 19.9873V13.7748C24.6742 11.4998 24.6742 11.4998 22.5242 10.0498L16.6117 6.6373C15.7242 6.1248 14.2742 6.1248 13.3867 6.6373L7.47422 10.0498C5.32422 11.4998 5.32422 11.4998 5.32422 13.7748Z"
                            stroke="url(#paint0_linear_23_638)" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M21.875 9.5375V6.25C21.875 3.75 20.625 2.5 18.125 2.5H11.875C9.375 2.5 8.125 3.75 8.125 6.25V9.45"
                            stroke="url(#paint1_linear_23_638)" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M15.7858 13.7377L16.4983 14.8502C16.6108 15.0252 16.8608 15.2002 17.0483 15.2502L18.3233 15.5752C19.1108 15.7752 19.3233 16.4502 18.8108 17.0752L17.9733 18.0877C17.8483 18.2502 17.7483 18.5377 17.7608 18.7377L17.8358 20.0502C17.8858 20.8627 17.3108 21.2752 16.5608 20.9752L15.3358 20.4877C15.1483 20.4127 14.8358 20.4127 14.6483 20.4877L13.4233 20.9752C12.6733 21.2752 12.0983 20.8502 12.1483 20.0502L12.2233 18.7377C12.2358 18.5377 12.1358 18.2377 12.0108 18.0877L11.1733 17.0752C10.6608 16.4502 10.8733 15.7752 11.6608 15.5752L12.9358 15.2502C13.1358 15.2002 13.3858 15.0127 13.4858 14.8502L14.1983 13.7377C14.6483 13.0627 15.3483 13.0627 15.7858 13.7377Z"
                            stroke="url(#paint2_linear_23_638)" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_23_638" x1="5.32422" y1="25.3664" x2="24.7895"
                                y2="25.2483" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                            <linearGradient id="paint1_linear_23_638" x1="8.125" y1="8.82807" x2="21.9551" y2="8.64791"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                            <linearGradient id="paint2_linear_23_638" x1="10.8945" y1="20.2826" x2="19.1382"
                                y2="20.2251" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('Best Seller') }}</h2>
                </div>
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="flex items-center gap-1.5 text-xs sm:text-sm text-main hover:underline font-medium">
                    {{ __('See all') }}
                    <svg width="5" height="12" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.75 0.75L5.34317 5.68939C5.88561 6.27273 5.88561 7.22727 5.34317 7.81061L0.75 12.75"
                            stroke="url(#paint0_linear_13_11426)" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_13_11426" x1="0.75" y1="1.95968" x2="5.77994" y2="1.97365"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>
                </a>
            </div>
            {{-- products slide --}}
            <div class="swiper product-slid-swiper">
                <div class="swiper-wrapper">
                    @forelse ($bestSelling as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.elora.pages._product-card', ['product' => $product, 'badge' =>
                        __('Best-Selling')])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No best sellers yet.') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    </section>

    {{-- ── New Arrivals ─────────────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="hidden sm:block"></div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.4016 1.60254L9.60156 2.39754L19.6016 12.4L20.4016 11.6L10.4016 1.60254ZM8.40156 7.60004L8.00156 8.00004L7.60156 8.40004L17.6016 18.4L18.4016 17.6L8.40156 7.60004ZM2.39656 9.60004L1.60156 10.4L11.6016 20.4L12.4016 19.6L2.39656 9.60004ZM22.0703 15.0125L20.2328 20.2688L14.8953 21.8938L19.3328 25.2688L19.2266 30.8438L23.8016 27.6688L29.0766 29.4875L27.4703 24.1563L30.8328 19.7063L25.2641 19.5813L22.0703 15.0125Z"
                            fill="url(#paint0_linear_3_1254)" />
                        <defs>
                            <linearGradient id="paint0_linear_3_1254" x1="1.60156" y1="27.8961" x2="31.0068"
                                y2="27.7001" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('New Arrivals') }}</h2>
                </div>
                <a href="{{ route('tenant.storefront.new-in') }}"
                    class="flex items-center gap-1.5 text-xs sm:text-sm text-main hover:underline font-medium">
                    {{ __('See all') }}
                    <svg width="5" height="12" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.75 0.75L5.34317 5.68939C5.88561 6.27273 5.88561 7.22727 5.34317 7.81061L0.75 12.75"
                            stroke="url(#paint0_linear_13_11426)" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_13_11426" x1="0.75" y1="1.95968" x2="5.77994" y2="1.97365"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>
                </a>


            </div>
            {{-- products slide --}}
            <div class="swiper product-slid-swiper">
                <div class="swiper-wrapper">
                    @forelse ($newInProducts as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => __('New In')])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No new arrivals yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>


    {{-- ── Shop by Category ────────────────────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
    <section wire:ignore class="elora-cats-section flex flex-col items-center justify-center"
        style="background:linear-gradient(89.62deg,#C94F1A 0.07%,#F56323 57.6%,#BA3800 100%);">

        {{-- Title row --}}
        <div class="flex flex-row items-center justify-center" style="gap:16px;">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M16 10.667H27.3333C27.8638 10.667 28.3725 10.8777 28.7475 11.2528C29.1226 11.6279 29.3333 12.1366 29.3333 12.667C29.3333 13.1974 29.1226 13.7061 28.7475 14.0812C28.3725 14.4563 27.8638 14.667 27.3333 14.667H17.3333M18 14.667H20.6667C21.1971 14.667 21.7058 14.8777 22.0809 15.2528C22.456 15.6279 22.6667 16.1366 22.6667 16.667C22.6667 17.1974 22.456 17.7061 22.0809 18.0812C21.7058 18.4563 21.1971 18.667 20.6667 18.667H17.3333M19.3333 18.667C19.8638 18.667 20.3725 18.8777 20.7475 19.2528C21.1226 19.6279 21.3333 20.1366 21.3333 20.667C21.3333 21.1974 21.1226 21.7061 20.7475 22.0812C20.3725 22.4563 19.8638 22.667 19.3333 22.667H17.3333"
                    stroke="#FDFDFD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path
                    d="M18 22.6669C18.5304 22.6669 19.0391 22.8776 19.4142 23.2527C19.7893 23.6278 20 24.1365 20 24.6669C20 25.1973 19.7893 25.706 19.4142 26.0811C19.0391 26.4562 18.5304 26.6669 18 26.6669H12C9.87827 26.6669 7.84344 25.8241 6.34315 24.3238C4.84286 22.8235 4 20.7886 4 18.6669V16.0002V16.2776C3.99978 14.9527 4.3286 13.6485 4.95695 12.4821C5.5853 11.3157 6.4935 10.3236 7.6 9.59491L8 9.33358C8.63822 8.91758 11.184 7.45713 15.6373 4.95224C16.0913 4.69684 16.627 4.62862 17.1305 4.76209C17.6339 4.89555 18.0655 5.22018 18.3333 5.66691C18.92 6.64558 18.7667 7.89891 17.96 8.70691L16 10.6669"
                    stroke="#FDFDFD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <h2 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:24px; line-height:30px; color:#FDFDFD; margin:0;">
                {{ __('Shop by Category') }}
            </h2>
        </div>

        {{-- Cards row --}}
        <div class="swiper categories-slide elora-cats-swiper w-full">
            <div class="swiper-wrapper" style="align-items:stretch;">
                @foreach ($categories as $cat)
                <div class="swiper-slide elora-cats-slide">
                    <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                         class="elora-cat-card">
                        {{-- Full-cover image --}}
                        @if ($cat->thumb_url ?? null)
                            <img loading="lazy" src="{{ $cat->thumb_url }}" alt="{{ $cat->translationValue('name') ?? $cat->name }}"
                                style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;" />
                        @else
                            <div style="position:absolute; inset:0; background:linear-gradient(135deg,#f0f0f0,#ddd); display:flex; align-items:center; justify-content:center;">
                                <span style="font-size:48px;">🛍️</span>
                            </div>
                        @endif
                        {{-- Label bar pinned to bottom --}}
                        <div class="elora-cat-label">
                            <span>{{ \Str::limit($cat->translationValue('name') ?? $cat->name, 22) }}</span>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>

    </section>
    @endif
    <!-- -------- buy together -------- -->
    @if (($buyTogetherProducts ?? collect())->count() === 2)
    @php
        $btProducts = $buyTogetherProducts;
    @endphp
    <section  wire:ignore class="py-5 md:hidden bg-gradient-to-r from-[#C94F1A] via-[#F56323] to-[#BA3800] mt-6 relative">
        <h2 class="text-white font-bold text-lg mb-2 text-center">{{ __('Buy together') }}</h2>
        <div class="flex items-center justify-center gap-1 px-7 relative z-20">
            @foreach ($btProducts as $btProduct)
            @php
                $btVariant = $btProduct->variants->firstWhere('active', true) ?? $btProduct->variants->first();
                $btPricing = $btProduct->storefrontPricing($btVariant);
                $btSellPrice = (float) $btPricing['current_price'];
                $btHasDiscount = (bool) $btPricing['has_discount'];
                $btDiscountPct = $btHasDiscount ? (int) round((float) $btPricing['discount_percentage']) : 0;
                $btDisplaySell = $symbol . number_format($btSellPrice * $rate, 2);
                $btImg = $btProduct->centralProduct?->primary_image_url ?? $btProduct->primary_image_url ?? null;
                $btName = $btProduct->translationValue('name') ?? $btProduct->slug;
                $btWeightGrams = $btProduct->centralProduct?->weight_grams ?? $btProduct?->weight_grams ?? null;
                $btWeightLabel = $btWeightGrams
                    ? ($btWeightGrams >= 1000 ? number_format($btWeightGrams / 1000, 1) . __('kg') : $btWeightGrams . __('g'))
                    : null;
                $btRating = round((float) ($btProduct->average_rating ?? 0), 1);
                $btRatingCount = $btProduct->relationLoaded('rates') ? $btProduct->rates->count() : 0;
                $btUrl = route('tenant.storefront.product', $btProduct->slug);
            @endphp
            <!-- product card -->
            <a href="{{ $btUrl }}"  class="flex bg-white rounded-lg overflow-hidden flex-1 h-[60px]" style="text-decoration:none">
                <!-- image -->
                <div class="relative w-2/5">
                    @if ($btImg)
                    <img loading="lazy" src="{{ $btImg }}" alt="{{ $btName }}" class="w-full h-full object-cover rounded">
                    @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-2xl rounded">🛍️</div>
                    @endif
                </div>
                <!-- info body -->
                <div class="flex flex-col justify-center gap-[2px] p-1 flex-1">
                    <h3 class="text-xs font-bold text-gray-800 line-clamp-1">{{ $btName }}</h3>
                    @if ($btWeightLabel)
                    <p class="text-main text-xs">{{ $btWeightLabel }}</p>
                    @endif
                    <p class="text-xs font-bold">{{ $btDisplaySell }}
                        @if ($btHasDiscount && $btDiscountPct > 0)
                        <span class="text-main text-[6px]">{{ $btDiscountPct }}{{ __('% off') }}</span>
                        @endif
                    </p>
                    @if ($btRating > 0)
                    <div class="flex gap-1 items-center">
                        <svg width="11" height="11" viewBox="0 0 6 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M2.75622 4.3367L1.29019 5.22197C1.24711 5.24324 1.20713 5.25185 1.17024 5.24781C1.13362 5.24351 1.09795 5.23085 1.06322 5.20985C1.02822 5.18831 1.00183 5.15789 0.98406 5.11858C0.96629 5.07927 0.964675 5.03632 0.979214 4.98975L1.36935 3.32986L0.0789997 2.21116C0.0426519 2.18154 0.0186893 2.14614 0.00711185 2.10494C-0.0044656 2.06375 -0.00190777 2.0243 0.0147853 1.98661C0.0314784 1.94892 0.0536909 1.91795 0.0814229 1.89372C0.109424 1.8703 0.147118 1.85441 0.194505 1.84606L1.8972 1.69744L2.56115 0.125602C2.57946 0.0811769 2.60585 0.0491368 2.64031 0.0294821C2.67477 0.00982735 2.71341 0 2.75622 0C2.79903 0 2.8378 0.00982735 2.87253 0.0294821C2.90726 0.0491368 2.93351 0.0811769 2.95128 0.125602L3.61524 1.69744L5.31753 1.84606C5.36518 1.85414 5.40301 1.87016 5.43101 1.89412C5.45901 1.91782 5.48136 1.94865 5.49805 1.98661C5.51448 2.0243 5.5169 2.06375 5.50532 2.10494C5.49375 2.14614 5.46978 2.18154 5.43344 2.21116L4.14309 3.32986L4.53322 4.98975C4.5483 5.03579 4.54682 5.0786 4.52878 5.11817C4.51074 5.15775 4.48422 5.18818 4.44922 5.20945C4.41475 5.23099 4.37908 5.24378 4.34219 5.24781C4.30558 5.25185 4.26573 5.24324 4.22265 5.22197L2.75622 4.3367Z"
                                fill="#FFE100" />
                        </svg>
                        <p class="text-gray-600 text-xs">{{ $btRating }}{{ $btRatingCount > 0 ? ' (+' . $btRatingCount . ')' : '' }}</p>
                    </div>
                    @endif
                </div>
            </a>
            @if (!$loop->last)
            <span>
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8.66667 5.33333H5.33333V8.66667C5.33333 8.84348 5.26309 9.01305 5.13807 9.13807C5.01305 9.2631 4.84348 9.33333 4.66667 9.33333C4.48986 9.33333 4.32029 9.2631 4.19526 9.13807C4.07024 9.01305 4 8.84348 4 8.66667V5.33333H0.666667C0.489856 5.33333 0.320287 5.2631 0.195262 5.13807C0.070238 5.01305 0 4.84348 0 4.66667C0 4.48986 0.070238 4.32029 0.195262 4.19526C0.320287 4.07024 0.489856 4 0.666667 4H4V0.666667C4 0.489856 4.07024 0.320286 4.19526 0.195262C4.32029 0.0702377 4.48986 0 4.66667 0C4.84348 0 5.01305 0.0702377 5.13807 0.195262C5.26309 0.320286 5.33333 0.489856 5.33333 0.666667V4H8.66667C8.84348 4 9.01305 4.07024 9.13807 4.19526C9.2631 4.32029 9.33333 4.48986 9.33333 4.66667C9.33333 4.84348 9.2631 5.01305 9.13807 5.13807C9.01305 5.2631 8.84348 5.33333 8.66667 5.33333Z"
                        fill="#FDFDFD" />
                </svg>
            </span>
            @endif
            @endforeach
        </div>
        <p
            class="text-[40px] font-extrabold tracking-[10px] absolute z-10 -rotate-90 -translate-y-1/2 top-1/2 -left-9 text-transparent [-webkit-text-stroke:1px_white]">
            20%</p>
        <p
            class="text-[40px] font-extrabold tracking-[10px] absolute z-10 -rotate-90 -translate-y-1/2 top-1/2 -right-9 text-transparent [-webkit-text-stroke:1px_white]">
            20%</p>
    </section>
    @endif


    {{-- ── Recommended For You ──────────────────────────────────────────────── --}}
    <section class="py-5 sm:py-8 bg-white"  wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-center mb-4 sm:mb-5">
                <div class="flex items-center gap-2 sm:gap-3">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M16 10.667H27.3333C27.8638 10.667 28.3725 10.8777 28.7475 11.2528C29.1226 11.6279 29.3333 12.1366 29.3333 12.667C29.3333 13.1974 29.1226 13.7061 28.7475 14.0812C28.3725 14.4563 27.8638 14.667 27.3333 14.667H17.3333M18 14.667H20.6667C21.1971 14.667 21.7058 14.8777 22.0809 15.2528C22.456 15.6279 22.6667 16.1366 22.6667 16.667C22.6667 17.1974 22.456 17.7061 22.0809 18.0812C21.7058 18.4563 21.1971 18.667 20.6667 18.667H17.3333M19.3333 18.667C19.8638 18.667 20.3725 18.8777 20.7475 19.2528C21.1226 19.6279 21.3333 20.1366 21.3333 20.667C21.3333 21.1974 21.1226 21.7061 20.7475 22.0812C20.3725 22.4563 19.8638 22.667 19.3333 22.667H17.3333"
                            stroke="url(#paint0_linear_3_1304)" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M18 22.6669C18.5304 22.6669 19.0391 22.8776 19.4142 23.2527C19.7893 23.6278 20 24.1365 20 24.6669C20 25.1973 19.7893 25.706 19.4142 26.0811C19.0391 26.4562 18.5304 26.6669 18 26.6669H12C9.87827 26.6669 7.84344 25.8241 6.34315 24.3238C4.84286 22.8235 4 20.7886 4 18.6669V16.0002V16.2776C3.99978 14.9527 4.3286 13.6485 4.95695 12.4821C5.5853 11.3157 6.4935 10.3236 7.6 9.59491L8 9.33358C8.63822 8.91758 11.184 7.45713 15.6373 4.95224C16.0913 4.69684 16.627 4.62862 17.1305 4.76209C17.6339 4.89555 18.0655 5.22018 18.3333 5.66691C18.92 6.64558 18.7667 7.89891 17.96 8.70691L16 10.6669"
                            stroke="url(#paint1_linear_3_1304)" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <defs>
                            <linearGradient id="paint0_linear_3_1304" x1="16" y1="21.4573" x2="29.4125" y2="21.3579"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                            <linearGradient id="paint1_linear_3_1304" x1="4" y1="24.452" x2="20.0955" y2="24.3739"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#C94F1A" />
                                <stop offset="0.575759" stop-color="#F56323" />
                                <stop offset="1" stop-color="#BA3800" />
                            </linearGradient>
                        </defs>
                    </svg>

                    <h2 class="text-lg sm:text-2xl font-bold text-gradient-main">{{ __('Recommended For You') }}</h2>
                </div>
            </div>
            {{-- products slide --}}
            <div class="swiper product-slid-swiper">
                <div class="swiper-wrapper">
                    @forelse (($recommendedProducts->isEmpty() ? $bestSelling : $recommendedProducts) as $product)
                    <div class="swiper-slide w-[132px] sm:w-[182px]  lg:w-[210px]">
                        @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-6 w-full">{{ __('No recommended products yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 sm:py-8 bg-white" id="all-products-section" wire:ignore>
        <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex items-center justify-between px-3 sm:px-5 py-2.5 rounded-lg mb-4 overflow-hidden"
                style="background:linear-gradient(89.62deg,#C94F1A 0.07%,#F56323 57.6%,#BA3800 100%);">
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="text-white text-xs sm:text-sm hover:underline">← {{ __('See all') }}</a>
                <span class="text-white text-xs sm:text-sm hidden sm:block">
                    {{ __('Free shipping when you purchase 1.2 kg') }}
                </span>
                <div></div>
            </div>
            {{-- infinite scroll grid --}}
            <div id="all-products-grid"
                 class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
                @forelse ($paginatedProducts as $product)
                    @include('themes.elora.pages._product-card', ['product' => $product, 'badge' => null])
                @empty
                    <p class="col-span-full text-sm text-gray-400 py-6">{{ __('No products yet.') }}</p>
                @endforelse
            </div>
            {{-- sentinel / loader --}}
            <div id="all-products-sentinel" class="flex justify-center py-8">
                <svg id="all-products-spinner" class="hidden animate-spin h-7 w-7 text-orange-500"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>
        </div>
    </section>


</main>

@push('scripts')
<script>
window.__eloraHomeConfig = {
    tabbedApiUrl:     @json(route('tenant.storefront.home.tabbed-products')),
    hasMoreFlash:     @json($hasMoreFlash ?? false),
    allProductsApiUrl: @json(route('tenant.storefront.products.json')),
    hasMoreProducts:  @json($paginatedProducts->hasMorePages()),
    noProductsText:   @json(__('No products found.')),
};
</script>
@vite('resources/js/elora/home.js')
@endpush
