@push('body-attrs')data-page="index" data-variant="v4"@endpush

@php
$currency = $currentCurrency ?? null;
$symbol = data_get($currency, 'symbol', '$');
$rate = (float) data_get($currency, 'conversion_rate', 1.0);
$selectedCountryId = session('storefront_country_id');
$selectedCountry = $selectedCountryId ? \App\Models\Country::find($selectedCountryId) : null;
@endphp


{{-- ============================================================
     SINGLE ROOT WRAPPER (Livewire requires one root element)
     ============================================================ --}}
<div class="elora-v4-root">
    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('elora', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.elora.pages.home-v4.sections.{$__section}")
    @endforeach

</div>

@push('scripts')
@endpush
