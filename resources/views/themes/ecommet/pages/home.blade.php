@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);

    // 'hero_banner' renders full-bleed (outside the padded container); every
    // other section renders inside the container, exactly as before the
    // sections were made reorderable. Container divs open/close around
    // contiguous runs of non-full-bleed sections so any drag order is safe.
    $__fullBleedSections = ['hero_banner'];
    $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('ecommet', 'home');
    $__inContainer = false;
@endphp

<main class="w-full">

    @foreach ($__homeSections as $__section)
        @php $__isFullBleed = in_array($__section, $__fullBleedSections, true); @endphp
        @if ($__isFullBleed && $__inContainer)
            </div>
            @php $__inContainer = false; @endphp
        @elseif (!$__isFullBleed && !$__inContainer)
            <div class="container py-3 md:py-8 space-y-4 md:space-y-10 px-0 md:px-4">
            @php $__inContainer = true; @endphp
        @endif

        @includeIf("themes.ecommet.pages.home.sections.{$__section}")
    @endforeach

    @if ($__inContainer)
        </div>
    @endif

</main>

{{-- ── Countdown script ─────────────────────────────────────────────────────── --}}
@push('scripts')
    @vite('resources/js/ecommet/home.js')
@endpush
