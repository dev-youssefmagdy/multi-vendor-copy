{{--
    Dashboard "New in products" slideshow (reuses the opportunity-card styles
    and the Swiper setup in resources/js/tenant/pages/dashboard/opportunities.js).

    BACKEND TODO: $newProducts is sample data — replace it with real data from
    the controller (same keys). "dummy" marks every value the backend provides.
    The CTA button is UI only for now.
--}}

@php
    $newProducts = $newProducts ?? [];

    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
    $zap = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 2.25L4.75 13.5h7l-1.25 8.25L19.25 10.5h-7l1.25-8.25z"/></svg>';
    $trendUp = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 7.25l-7 7-4-4-6 6"/><path d="M16 7.25h4.5v4.5"/></svg>';
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

    <div class="db-opp-slider is-newin swiper" data-opp-slider data-mobile-view="1.6" aria-label="New in products">
        <div class="swiper-wrapper">
            @foreach($newProducts as $product)
                <article class="db-opp swiper-slide"
                    @if(!empty($product['id']))
                    data-opp-card
                    data-product-id="{{ $product['id'] }}"
                    data-in-flash-sale="{{ ($product['in_flash_sale'] ?? false) ? '1' : '0' }}"
                    data-featured="{{ ($product['featured'] ?? false) ? '1' : '0' }}"
                    data-flash-sales-url="{{ route('tenant.store.flash-sales.available-for-product') }}"
                    data-attach-url="{{ route('tenant.store.flash-sales.attach-product', ['flashSale' => '__SALE__']) }}"
                    data-toggle-featured-url="{{ route('tenant.products.toggle-featured', ['product' => '__PROD__']) }}"
                    @endif>
                    <div class="db-opp-media" style="background-image:url('{{ $product['image'] }}')">
                        @if(!empty($product['below_market']))
                            <span class="db-opp-cheaper">
                                <small>Cheaper than market by</small>
                                <strong>{{ $product['below_market'] }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="db-opp-body">
                        <div>
                            <h3 class="db-opp-title">{{ $product['title'] }}</h3>
                            <p class="db-opp-desc">{{ $product['description'] }}</p>
                        </div>

                        <div class="db-opp-stats">
                            <div class="db-opp-stat is-dark is-half">
                                <span class="db-opp-stat-label">Cost to your customer's door</span>
                                <strong>{{ $product['cost'] ?? '—' }}</strong>
                                <small>Product + International Shipping</small>
                            </div>
                            <div class="db-opp-stat">
                                <span class="db-opp-stat-label">Average Cheaper than market by</span>
                                <strong>{{ $product['below_market'] ?? '—' }}</strong>
                                <small>Through global stores</small>
                            </div>
                        </div>

                        @if(!empty($product['id']))
                            <div class="db-opp-actions">
                                <button type="button" class="btn-tile {{ ($product['in_flash_sale'] ?? false) ? 'is-active' : '' }}" data-opp-flash-sale-btn>
                                    {!! $zap !!} <span>{{ ($product['in_flash_sale'] ?? false) ? 'In Flash Sale' : 'Add to Flash Sale' }}</span>
                                </button>
                                <button type="button" class="btn-tile {{ ($product['featured'] ?? false) ? 'is-active' : '' }}" data-opp-trending-btn>
                                    {!! $trendUp !!} <span>{{ ($product['featured'] ?? false) ? 'Trending ✓' : 'Add to trending' }}</span>
                                </button>
                            </div>

                            <button type="button" class="btn btn-primary btn-lg db-opp-cta"
                                data-modal-open="product-video-ad-modal"
                                data-product-id="{{ $product['id'] }}">
                                {!! $idea !!} Create your Ad
                            </button>
                        @else
                            <a href="{{ route('tenant.products.create') }}" class="btn btn-primary btn-lg db-opp-cta">
                                {!! $idea !!} Add to store &amp; create your Ad
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
