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
<div data-home-root class="elora-v4-root">
    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('elora', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.elora.pages.home-v4.sections.{$__section}")
    @endforeach

</div>

@push('scripts')
@once
<script>
    (function () {
        document.addEventListener('livewire:init', function () {
            Livewire.on('storefront-cart-added', function () {
                document.querySelectorAll('#elora-v4-cart-badge, #elora-v4-cart-badge-mobile').forEach(function (badge) {
                    badge.textContent = (parseInt(badge.textContent, 10) || 0) + 1;
                    badge.classList.remove('hidden');
                });
            });
        });
    })();
</script>
@endonce
@endpush
