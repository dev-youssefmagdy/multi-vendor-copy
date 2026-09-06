@push('body-attrs')data-page="index" data-variant="v6"@endpush

@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);
@endphp

{{-- ============================================================
     SINGLE ROOT WRAPPER (Livewire requires one root element)
     ============================================================ --}}
<div class="souqify-v6-root pb-[84px] lg:pb-0" style="background: var(--color-bg-main)">
    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('souqify', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.souqify.pages.home-v6.sections.{$__section}")
    @endforeach
</div>

@push('scripts')
@endpush
