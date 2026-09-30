{{--
    Dashboard "Selected winning opportunities for you today".

    BACKEND TODO: $opportunities is sample data so the design can be built.
    Replace it with real data from the controller (same keys). Every value
    marked "dummy" and the sample titles/images must come from the backend.
    Buttons and the country select are UI only for now.
--}}

@php
    $opportunities = $opportunities ?? [];
@endphp

<section class="db-section fu d2">
    <div class="db-section-head">
        <div class="db-welcome-copy db-opp-intro">
            <h2 class="db-welcome-title"><span class="db-title-full">Selected winning opportunities for you today</span><span class="db-title-short">winning opportunities</span></h2>
            <p class="db-welcome-sub">Out of the thousands of products in your store, we have selected these opportunities based on demand, trends, and competition in your chosen market.</p>
        </div>
        <a href="{{ route('tenant.products.index') }}" class="db-see-all">
            See all
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </a>
    </div>

    {{-- Market selector — flag dropdown; UI only until the backend provides markets. --}}
    <x-tenant::country-picker trigger-class="db-country-select">
        <span data-country-label>Select your country</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
    </x-tenant::country-picker>

    <div class="db-opp-slider is-opps swiper" data-opp-slider data-mobile-view="1.5" aria-label="Opportunities">
        <div class="swiper-wrapper">
        @foreach($opportunities as $opp)
            @include('tenant.pages.dashboard._opportunity-card', ['opp' => $opp, 'cardClass' => 'swiper-slide'])
        @endforeach
        </div>
    </div>
</section>
