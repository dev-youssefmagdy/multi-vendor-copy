@push('body-attrs')data-page="index" data-variant="v4"@endpush

@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);
@endphp

{{-- ============================================================
     SINGLE ROOT WRAPPER (Livewire requires one root element)
     ============================================================ --}}
<div class="souqify-v4-root has-mobile-tabbar" style="background: var(--color-bg-main)">
    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('souqify', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.souqify.pages.home-v4.sections.{$__section}")
    @endforeach
</div>

@push('scripts')
@endpush
