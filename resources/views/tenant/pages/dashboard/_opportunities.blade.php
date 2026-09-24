{{--
    Dashboard "Selected winning opportunities for you today".

    BACKEND TODO: $opportunities is sample data so the design can be built.
    Replace it with real data from the controller (same keys). Every value
    marked "dummy" and the sample titles/images must come from the backend.
    Buttons and the country select are UI only for now.
--}}

@php
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

    $zap = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 2.25L4.75 13.5h7l-1.25 8.25L19.25 10.5h-7l1.25-8.25z"/></svg>';
    $trendUp = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 7.25l-7 7-4-4-6 6"/><path d="M16 7.25h4.5v4.5"/></svg>';
    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
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
            <article class="db-opp swiper-slide {{ $opp['added'] ? 'is-added' : '' }}">
                <div class="db-opp-media" style="background-image:url('{{ $opp['image'] }}')">
                    @if($opp['added'])
                        <span class="db-opp-added">Already added</span>
                    @endif
                    <span class="db-opp-cheaper">
                        <small>Cheaper than market by</small>
                        <strong>{{ $opp['below_market'] }}</strong>
                    </span>
                </div>

                <div class="db-opp-body">
                    <div class="db-opp-tags">
                        <span class="db-tag is-blue">{{ $opp['category'] }}</span>
                        <span class="db-tag is-orange"><i></i>{{ $opp['trend'] }}</span>
                        <span class="db-tag is-green"><i></i>{{ $opp['competition'] }}</span>
                    </div>

                    <div>
                        <h3 class="db-opp-title">{{ $opp['title'] }}</h3>
                        <p class="db-opp-desc">{{ $opp['description'] }}</p>
                    </div>

                    <div class="db-opp-markets">
                        <h4>The best markets for this opportunity</h4>
                        {{-- Auto-scrolling ticker; the list is rendered twice so the loop never runs short. --}}
                        <div class="db-opp-flags swiper" data-flag-ticker>
                            <div class="swiper-wrapper">
                                @foreach([1, 2] as $pass)
                                    @foreach($opp['markets'] as $code => $label)
                                        <div class="swiper-slide db-flag-slide" @if($pass === 2) aria-hidden="true" @endif>
                                            <x-tenant::flag :code="$code" :label="$label" />
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="db-opp-gauges">
                        @foreach([
                            ['Opp. Score', $opp['score'], $opp['score_pct'], '#FF522C'],
                            ['Est. Profit', $opp['profit'], $opp['profit_pct'], '#03AA00'],
                            ['competition', $opp['competition_level'], $opp['competition_pct'], '#F0C800'],
                            ['Product status', $opp['status'], $opp['status_pct'], '#F85223'],
                        ] as [$gLabel, $gValue, $gPct, $gColor])
                            <div class="db-gauge">
                                <span class="db-gauge-label">{{ $gLabel }}</span>
                                <span class="db-gauge-ring" style="--p: {{ $gPct }}; --c: {{ $gColor }}">
                                    <span class="db-gauge-value {{ is_numeric(str_replace(['+', '%'], '', $gValue)) ? '' : 'is-text' }}">
                                        {{ $gValue }}@if($gLabel === 'Product status' && $gValue === 'Hot') <span aria-hidden="true">🔥</span>@endif
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="db-opp-stats">
                        <div class="db-opp-stat">
                            <span class="db-opp-stat-label">YOUR COST
                                <x-tenant::tooltip label="What is your cost?" text="The total you pay for one unit: the product price plus delivery to your customer." />
                            </span>
                            <strong>{{ $opp['cost'] }}</strong>
                            <small>Product + Delivery</small>
                        </div>
                        <div class="db-opp-stat">
                            <span class="db-opp-stat-label">BELOW MARKET</span>
                            <strong>{{ $opp['below_market'] }}</strong>
                            <small>Compared with major stores</small>
                        </div>
                        <div class="db-opp-stat is-dark">
                            <span class="db-opp-stat-label">RECOMMENDED MARKUP</span>
                            <strong>{{ $opp['markup'] }}</strong>
                            <small>Suggested Price {{ $opp['suggested_price'] }} · Potential</small>
                        </div>
                    </div>

                    <div class="db-opp-actions">
                        <button type="button" class="btn-tile">{!! $zap !!} Add to Flash Sale</button>
                        <button type="button" class="btn-tile">{!! $trendUp !!} Add to trending</button>
                    </div>

                    <button type="button" class="btn btn-lg db-opp-cta {{ $opp['added'] ? 'is-added' : 'btn-primary' }}">
                        {!! $idea !!}
                        {{ $opp['added'] ? 'Create your Ad' : 'Add to store & create your Ad' }}
                    </button>
                </div>
            </article>
        @endforeach
        </div>

        <button type="button" class="db-slider-nav is-prev" data-opp-prev aria-label="Previous opportunities">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
        </button>
        <button type="button" class="db-slider-nav is-next" data-opp-next aria-label="Next opportunities">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </button>
    </div>
</section>
