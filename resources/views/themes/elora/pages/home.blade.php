@push('body-attrs')data-page="index"@endpush

@php
$currency = $currentCurrency ?? null;
$symbol = data_get($currency, 'symbol', '$');
$rate = (float) data_get($currency, 'conversion_rate', 1.0);
$selectedCountryId = session('storefront_country_id');
$selectedCountry = $selectedCountryId ? app(\App\Services\Tenant\HomeViewData::class)->country($selectedCountryId) : null;
@endphp

@push('head')
    @vite('resources/css/elora/home.css')
@endpush

<main data-home-root class="w-full">

    @include('themes.elora.partials.mobile-home-header')

    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('elora', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.elora.pages.home.sections.{$__section}")
    @endforeach



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
