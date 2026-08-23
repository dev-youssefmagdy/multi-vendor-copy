@push('body-attrs')data-page="index" data-variant="v2"@endpush

@php
$currency = $currentCurrency ?? null;
$symbol = data_get($currency, 'symbol', '$');
$rate = (float) data_get($currency, 'conversion_rate', 1.0);
$selectedCountryId = session('storefront_country_id');
$selectedCountry = $selectedCountryId ? \App\Models\Country::find($selectedCountryId) : null;
@endphp

@push('head')
    @vite(['resources/css/elora/home.css', 'resources/css/elora/home-v2.css'])
@endpush

{{-- ============================================================
     SINGLE ROOT WRAPPER (Livewire requires one root element)
     ============================================================ --}}
<div class="elora-v2-root">

    {{-- ============================================================
         MOBILE HEADER  (dark #121212 bg, purple accent)
         Hidden on sm+ — desktop uses the standard navbar partial
         Matches Figma node 1-299
         ============================================================ --}}
    <div class="sm:hidden elora-v2-mobile-header" style="position:relative;background:#121212;padding:12px 16px 0;">
        <!-- Top row: menu + logo + bell -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <!-- Left: hamburger menu + logo -->
            <div style="display:flex;align-items:center;gap:12px;">
                <button id="v2MenuBtn"
                    onclick="openSideMenu(); setTimeout(function(){ var t=window.__eloraCatTree; window.eloraActivateCat && t && t.length && window.eloraActivateCat(t[0].id); }, 30);"
                    style="display:flex;flex-direction:column;gap:4px;padding:4px;background:none;border:none;cursor:pointer;">
                    <span style="display:block;width:22px;height:2px;background:#FDFDFD;border-radius:2px;"></span>
                    <span style="display:block;width:22px;height:2px;background:#FDFDFD;border-radius:2px;"></span>
                    <span style="display:block;width:22px;height:2px;background:#FDFDFD;border-radius:2px;"></span>
                </button>
                <a href="{{ route('tenant.home') }}" style="flex-shrink:0;">
                    <x-storefront-logo :storeName="$storeName" class="h-9 w-auto" />
                </a>
            </div>
            <!-- Right: notification bell -->
            <a href="{{ route('tenant.storefront.favorites') }}"
               style="width:40px;height:40px;background:#FDFDFD;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,.12);">
                <svg width="18" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M18.7453 9C18.7453 5.25196 15.7479 2.21484 12.0453 2.21484C8.34277 2.21484 5.34534 5.25196 5.34534 9C5.34534 14.3284 3.59534 16.0784 3.59534 16.0784H20.4953C20.4953 16.0784 18.7453 14.3284 18.7453 9Z"
                        stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13.7299 19.5781C13.5561 19.877 13.307 20.1244 13.0072 20.2962C12.7075 20.468 12.3674 20.5584 12.0212 20.5584C11.675 20.5584 11.3349 20.468 11.0351 20.2962C10.7354 20.1244 10.4862 19.877 10.3124 19.5781"
                        stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        <!-- Search bar floating (translates down to overlap hero) -->
        <div style="transform:translateY(50%);position:relative;z-index:50;filter:drop-shadow(0 4px 12px rgba(0,0,0,.18));">
            <form action="{{ route('tenant.storefront.search') }}" method="GET"
                data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                <div class="elora-search-inner"
                     style="display:flex;align-items:center;background:#F6F5F5;border-radius:32px;padding:0 12px;gap:8px;height:56px;border:1px solid #D4D4D4;position:relative;">
                    <svg style="width:20px;height:20px;flex-shrink:0;color:#8F8F8F;transform:scaleX(-1);"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="{{ __('Search...') }}" autocomplete="off"
                        style="background:transparent;flex:1;font-size:16px;color:#8F8F8F;outline:none;font-family:'Outfit',sans-serif;min-width:0;border:none;"/>
                    <!-- Camera icon with left border -->
                    <div style="display:flex;align-items:center;padding-left:6px;border-left:1px solid #8F8F8F;">
                        <svg style="width:20px;height:18px;color:#171717;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 8.5c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5zm0 7c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zM20 4h-3.17L15 2H9L7.17 4H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H4V6h4.05l1.83-2h4.24l1.83 2H20v14z"/>
                        </svg>
                    </div>
                </div>
            </form>
        </div>
    </div>
    {{-- spacer so hero doesn't get eaten by search overlap on mobile --}}
    <div class="sm:hidden" style="height:28px;background:#121212;"></div>

    {{-- ============================================================
         PAGE SECTIONS
         ============================================================ --}}
    <main class="w-full" style="padding-bottom:80px;" class="sm:pb-0">

        @php
            $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('elora', 'home');
        @endphp
        @foreach ($__homeSections as $__section)
            @includeIf("themes.elora.pages.home-v2.sections.{$__section}")
        @endforeach

    </main>

    {{-- ============================================================
         MOBILE BOTTOM NAV  (floating pill, purple cart)
         Matches Figma node 1-626
         Only visible on mobile (sm:hidden)
         ============================================================ --}}
    <div class="sm:hidden" style="position:fixed;bottom:16px;left:0;right:0;z-index:100;padding:0 16px;">
        <div style="background:#FDFDFD;border-radius:129px;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;box-shadow:0 0 10px rgba(0,0,0,.08);position:relative;">

            <!-- Home -->
            <a href="{{ route('tenant.home') }}"
               style="display:flex;flex-direction:column;align-items:center;gap:4px;width:34px;text-decoration:none;">
                <!-- active indicator bar -->
                <div style="height:2px;width:22px;background:#9D00FF;border-radius:0 0 8px 8px;"></div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M9.02 2.84L3.63 7.04C2.73 7.74 2 9.23 2 10.36v7.41C2 19.92 3.53 22 5.83 22h12.34C20.47 22 22 19.92 22 17.77V10.5c0-1.21-.81-2.76-1.8-3.45l-6.18-4.33C12.68 1.77 10.43 1.84 9.02 2.84Z" stroke="#121212" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 18v-3" stroke="#121212" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="font-size:12px;color:#121212;font-family:'Outfit',sans-serif;letter-spacing:.5px;white-space:nowrap;">{{ __('Home') }}</span>
            </a>

            <!-- Favorites -->
            <a href="{{ route('tenant.storefront.favorites') }}"
               style="display:flex;flex-direction:column;align-items:center;gap:4px;width:54px;text-decoration:none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12.62 20.81c-.34.12-.9.12-1.24 0C8.48 19.82 2 15.69 2 8.69 2 5.6 4.49 3.1 7.56 3.1c1.82 0 3.43.88 4.44 2.24a5.53 5.53 0 0 1 4.44-2.24C19.51 3.1 22 5.6 22 8.69c0 7-6.48 11.13-9.38 12.12Z" stroke="#8F8F8F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="font-size:12px;color:#8F8F8F;font-family:'Outfit',sans-serif;letter-spacing:.5px;white-space:nowrap;">{{ __('Favorites') }}</span>
            </a>

            <!-- Cart (center, elevated purple circle) -->
            <a href="{{ route('tenant.storefront.cart') }}"
               style="position:absolute;left:50%;transform:translateX(-50%);top:-28px;width:81px;height:81px;background:#9D00FF;border-radius:50%;border:4px solid #FDFDFD;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;text-decoration:none;box-shadow:0 2px 8px rgba(157,0,255,.3);">
                <div style="position:relative;">
                    <svg width="33" height="33" viewBox="0 0 24 24" fill="none">
                        <path d="M2 2h1.74c.77 0 1.42.56 1.53 1.32L6.5 13.68c.11.76.76 1.32 1.53 1.32H18" stroke="#FDFDFD" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.64 16.12c0 .62.5 1.13 1.13 1.13.62 0 1.13-.5 1.13-1.13 0-.62-.5-1.13-1.13-1.13-.63 0-1.13.51-1.13 1.13ZM15.91 16.12c0 .62.5 1.13 1.13 1.13.62 0 1.13-.5 1.13-1.13 0-.62-.5-1.13-1.13-1.13-.63 0-1.13.51-1.13 1.13ZM8.25 8.25h13.5l-.99 6.6H9.23" stroke="#FDFDFD" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <!-- cart count badge -->
                    @php $mobileCartCount = array_sum(array_column(session('storefront_cart', []), 'qty')); @endphp
                    @if($mobileCartCount > 0)
                    <span style="position:absolute;top:-6px;right:-8px;background:#FDFDFD;color:#121212;font-size:10px;font-weight:700;border-radius:50%;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 2px;box-shadow:0 2px 2px rgba(0,0,0,.15);">{{ $mobileCartCount }}</span>
                    @endif
                </div>
                <span style="font-size:12px;color:#FDFDFD;font-family:'Outfit',sans-serif;letter-spacing:.5px;white-space:nowrap;">{{ __('My Cart') }}</span>
            </a>

            <!-- Orders -->
            <a href="{{ route('tenant.storefront.profile') }}"
               style="display:flex;flex-direction:column;align-items:center;gap:4px;width:40px;text-decoration:none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M3.17 7.43 12 12.55l8.77-5.11M12 21.61V12.54" stroke="#8F8F8F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.93 2.48 4.59 5.44c-1.21.67-2.2 2.35-2.2 3.73v5.65c0 1.38.99 3.06 2.2 3.73l5.34 2.97c1.14.63 3.01.63 4.15 0l5.34-2.97c1.21-.67 2.2-2.35 2.2-3.73V9.17c0-1.38-.99-3.06-2.2-3.73l-5.34-2.97c-1.15-.63-3.01-.63-4.15.01Z" stroke="#8F8F8F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="font-size:12px;color:#8F8F8F;font-family:'Outfit',sans-serif;letter-spacing:.5px;white-space:nowrap;">{{ __('Orders') }}</span>
            </a>

            <!-- Profile -->
            <a href="{{ route('tenant.storefront.profile') }}"
               style="display:flex;flex-direction:column;align-items:center;gap:4px;width:37px;text-decoration:none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="#8F8F8F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22" stroke="#8F8F8F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="font-size:12px;color:#8F8F8F;font-family:'Outfit',sans-serif;letter-spacing:.5px;white-space:nowrap;">{{ __('Profile') }}</span>
            </a>

        </div>
    </div>

</div>{{-- /elora-v2-root --}}

@push('scripts')
<script>
window.__eloraHomeConfig = {
    tabbedApiUrl:      @json(route('tenant.storefront.home.tabbed-products')),
    hasMoreFlash:      @json($hasMoreFlash ?? false),
    allProductsApiUrl: @json(route('tenant.storefront.products.json')),
    hasMoreProducts:   @json($paginatedProducts->hasMorePages()),
    noProductsText:    @json(__('No products found.')),
};
</script>
@vite('resources/js/elora/home.js')
@endpush
