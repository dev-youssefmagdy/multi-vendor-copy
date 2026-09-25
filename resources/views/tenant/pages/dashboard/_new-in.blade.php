{{--
    Dashboard "New in products" slideshow (reuses the opportunity-card styles
    and the Swiper setup in resources/js/tenant/pages/dashboard/opportunities.js).

    BACKEND TODO: $newProducts is sample data — replace it with real data from
    the controller (same keys). "dummy" marks every value the backend provides.
    The CTA button is UI only for now.
--}}

@php
    // FOR DESIGN PURPOSE
    $newProducts = $newProducts ?? collect([
        'photo-1590874103328-eac38a683ce7',
        'photo-1505740420928-5e560c06d30e',
        'photo-1572635196237-14b3f281503f',
    ])->map(fn ($photo) => [
        'image' => "https://images.unsplash.com/{$photo}?w=900&q=70&auto=format&fit=crop",
        'title' => 'Sports smart watch',
        'description' => 'Rapid demand, easy advertising content, and a margin that leaves you with a strong competitive advantage.',
        'below_market' => 'dummy',
        'cost' => 'dummy',
    ])->all();

    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
@endphp

<section class="db-section fu d2">
    <div class="db-section-head">
        <div class="db-welcome-copy db-opp-intro">
            <h2 class="db-welcome-title">New in products</h2>
            <p class="db-welcome-sub">Out of the thousands of products in your store, we have selected these opportunities based on demand, trends, and competition in your chosen market.</p>
        </div>
        <a href="{{ route('tenant.products.index') }}" class="db-see-all">
            See all
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </a>
    </div>

    <div class="db-opp-slider swiper" data-opp-slider aria-label="New in products">
        <div class="swiper-wrapper">
            @foreach($newProducts as $product)
                <article class="db-opp swiper-slide">
                    <div class="db-opp-media" style="background-image:url('{{ $product['image'] }}')">
                        <span class="db-opp-cheaper">
                            <small>Cheaper than market by</small>
                            <strong>{{ $product['below_market'] }}</strong>
                        </span>
                    </div>

                    <div class="db-opp-body">
                        <div>
                            <h3 class="db-opp-title">{{ $product['title'] }}</h3>
                            <p class="db-opp-desc">{{ $product['description'] }}</p>
                        </div>

                        <div class="db-opp-stats">
                            <div class="db-opp-stat is-dark is-half">
                                <span class="db-opp-stat-label">Cost to your customer's door</span>
                                <strong>{{ $product['cost'] }}</strong>
                                <small>Product + International Shipping</small>
                            </div>
                            <div class="db-opp-stat">
                                <span class="db-opp-stat-label">Average Cheaper than market by</span>
                                <strong>{{ $product['below_market'] }}</strong>
                                <small>Through global stores</small>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg db-opp-cta">
                            {!! $idea !!}
                            Add to store &amp; create your Ad
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
