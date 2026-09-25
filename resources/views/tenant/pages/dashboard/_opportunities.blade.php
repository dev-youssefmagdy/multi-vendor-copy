{{--
    Dashboard "Selected winning opportunities for you today".

    BACKEND TODO: $opportunities is sample data so the design can be built.
    Replace it with real data from the controller (same keys). Every value
    marked "dummy" and the sample titles/images must come from the backend.
    Buttons and the country select are UI only for now.
--}}

@php
    // FOR DESIGN PURPOSE
    $opportunities = $opportunities ?? [
        [
            'image' => 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=900&q=70&auto=format&fit=crop',
            'added' => true,
            'category' => 'electronics',
            'trend' => 'rising',
            'competition' => 'Low competition',
            'title' => 'Sports smart watch',
            'description' => 'Rapid demand, easy advertising content, and a margin that leaves you with a strong competitive advantage.',
            'markets' => ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE', 'fr' => 'FRA', 'ma' => 'Mor', 'iq' => 'IRQ', 'qa' => 'QTR'],
            'below_market' => 'dummy',
            'score' => 'dummy', 'score_pct' => 60,
            'profit' => 'dummy', 'profit_pct' => 70,
            'competition_level' => 'dummy', 'competition_pct' => 30,
            'status' => 'Hot', 'status_pct' => 75,
            'cost' => 'dummy',
            'markup' => 'dummy',
            'suggested_price' => 'dummy',
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=900&q=70&auto=format&fit=crop',
            'added' => false,
            'category' => 'electronics',
            'trend' => 'rising',
            'competition' => 'Low competition',
            'title' => 'Sports smart watch',
            'description' => 'Rapid demand, easy advertising content, and a margin that leaves you with a strong competitive advantage.',
            'markets' => ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE', 'fr' => 'FRA', 'ma' => 'Mor', 'iq' => 'IRQ', 'qa' => 'QTR'],
            'below_market' => 'dummy',
            'score' => 'dummy', 'score_pct' => 60,
            'profit' => 'dummy', 'profit_pct' => 70,
            'competition_level' => 'dummy', 'competition_pct' => 30,
            'status' => 'Hot', 'status_pct' => 75,
            'cost' => 'dummy',
            'markup' => 'dummy',
            'suggested_price' => 'dummy',
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=900&q=70&auto=format&fit=crop',
            'added' => false,
            'category' => 'electronics',
            'trend' => 'rising',
            'competition' => 'Low competition',
            'title' => 'Sports smart watch',
            'description' => 'Rapid demand, easy advertising content, and a margin that leaves you with a strong competitive advantage.',
            'markets' => ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE', 'fr' => 'FRA', 'ma' => 'Mor', 'iq' => 'IRQ', 'qa' => 'QTR'],
            'below_market' => 'dummy',
            'score' => 'dummy', 'score_pct' => 60,
            'profit' => 'dummy', 'profit_pct' => 70,
            'competition_level' => 'dummy', 'competition_pct' => 30,
            'status' => 'Hot', 'status_pct' => 75,
            'cost' => 'dummy',
            'markup' => 'dummy',
            'suggested_price' => 'dummy',
        ],
    ];

@endphp

<section class="db-section fu d2">
    <div class="db-section-head">
        <div class="db-welcome-copy db-opp-intro">
            <h2 class="db-welcome-title">Selected winning opportunities for you today</h2>
            <p class="db-welcome-sub">Out of the thousands of products in your store, we have selected these opportunities based on demand, trends, and competition in your chosen market.</p>
        </div>
        <a href="{{ route('tenant.products.index') }}" class="db-see-all">
            See all
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </a>
    </div>

    {{-- Market selector — UI only until the backend provides markets. --}}
    <button type="button" class="db-country-select">
        Select your country
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
    </button>

    <div class="db-opp-slider swiper" data-opp-slider aria-label="Opportunities">
        <div class="swiper-wrapper">
        @foreach($opportunities as $opp)
            @include('tenant.pages.dashboard._opportunity-card', ['opp' => $opp, 'cardClass' => 'swiper-slide'])
        @endforeach
        </div>
    </div>
</section>
