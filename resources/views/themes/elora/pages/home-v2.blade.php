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

<div class="sm:hidden mb-14" style="background:#121212;">
    <div class="px-4 pt-3 pb-0 flex flex-col gap-3">
        <!-- User info row -->
        <div class="flex items-center justify-between">
            <!-- Left: avatar + greeting -->
            @auth('storefront')
            @php $authCustomer = auth('storefront')->user(); @endphp
            <a href="{{ route('tenant.storefront.profile') }}" class="flex items-center gap-1.5 min-w-0">
                <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden border border-[#8A38F5]/40"
                     style="background:rgba(138,56,245,0.2);">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none">
                        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="flex flex-col gap-[3px] min-w-0">
                    <span class="text-white text-lg font-medium leading-[23px] truncate" style="font-family:'Outfit',sans-serif;">
                        {{ __('Hi') }}, {{ $authCustomer->full_name ?? $authCustomer->name }} 👋
                    </span>
                    <button type="button"
                        onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                        class="flex items-center gap-1 text-left group">
                        <span class="text-white/70 text-xs font-medium tracking-wide leading-[15px] truncate" style="font-family:'Outfit',sans-serif;">
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
                        <svg class="w-4 h-4 flex-shrink-0 text-white/70" viewBox="0 0 24 24" fill="none">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </a>
            @else
            <div class="flex items-center gap-1.5 min-w-0">
                <a href="{{ route('tenant.storefront.login') }}" class="flex-shrink-0">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center border border-[#8A38F5]/40"
                         style="background:rgba(138,56,245,0.2);">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none">
                            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20.5901 22C20.5901 18.13 16.7402 15 12.0002 15C7.26015 15 3.41016 18.13 3.41016 22"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </a>
                <div class="flex flex-col gap-[3px] min-w-0">
                    <a href="{{ route('tenant.storefront.login') }}">
                        <span class="text-white text-lg font-medium leading-[23px] truncate" style="font-family:'Outfit',sans-serif;">{{ __('Guest') }}</span>
                    </a>
                    <button type="button"
                        onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                        class="flex items-center gap-1 text-left group">
                        <span class="text-white/70 text-xs font-medium tracking-wide leading-[15px] truncate" style="font-family:'Outfit',sans-serif;">
                            @php
                                $currencyLabel = $currentCurrency?->code ?? __('Default');
                                $langLabel = $currentLanguage?->name ?? __('Default');
                                $countryLabel = $selectedCountry->name ?? __('Default');
                            @endphp
                            {{ $currencyLabel }} &bull; {{ $langLabel }} &bull; {{ $countryLabel }}
                        </span>
                        <svg class="w-4 h-4 flex-shrink-0 text-white/70" viewBox="0 0 24 24" fill="none">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endauth

            <!-- Right: favorites bell -->
            <a href="{{ route('tenant.storefront.favorites') }}"
               class="w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                <svg class="w-[18px] h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.7453 9C18.7453 5.25196 15.7479 2.21484 12.0453 2.21484C8.34277 2.21484 5.34534 5.25196 5.34534 9C5.34534 14.3284 3.59534 16.0784 3.59534 16.0784H20.4953C20.4953 16.0784 18.7453 14.3284 18.7453 9Z"
                        stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13.7299 19.5781C13.5561 19.877 13.307 20.1244 13.0072 20.2962C12.7075 20.468 12.3674 20.5584 12.0212 20.5584C11.675 20.5584 11.3349 20.468 11.0351 20.2962C10.7354 20.1244 10.4862 19.877 10.3124 19.5781"
                        stroke="#8A38F5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        <!-- Search bar -->
        <div class="translate-y-1/2 relative z-50 drop-shadow-2xl">
            <form action="{{ route('tenant.storefront.search') }}" method="GET"
                data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                <div class="elora-search-inner flex items-center bg-[#F6F5F5] rounded-[32px] px-3 py-2 gap-2 h-14 border border-[#D4D4D4]"
                    style="position:relative">
                    <svg class="w-5 h-5 flex-shrink-0 text-[#8F8F8F]" style="transform:scaleX(-1);"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="{{ __('Search...') }}" autocomplete="off"
                        class="bg-transparent flex-1 text-base text-[#8F8F8F] placeholder-[#8F8F8F] min-w-0 outline-none font-normal"
                        style="font-family:'Outfit',sans-serif;"/>
                    <div class="flex items-center justify-end pl-1.5 border-l border-[#8F8F8F]"></div>
                </div>
            </form>
        </div>
    </div>
</div>

<main class="w-full elora-v2">

    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('elora', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.elora.pages.home-v2.sections.{$__section}")
    @endforeach

</main>

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
