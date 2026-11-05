{{--
    PANELS-UPDATES-NEW-PRODUCTS-SYNC: Dashboard "New in products" slideshow.
    Shows ONLY products the platform added after the tenant registered that are not in the tenant's store yet
    ($newProducts = pending central products; "id" is the CENTRAL product id). The catalog is no longer pushed
    automatically: the tenant adds them with "Add to my store" or "Sync new products". Existing products keep
    receiving updates automatically. Reuses the opportunity-card styles and Swiper setup of opportunities.js.
--}}

@php
    $newProducts = $newProducts ?? [];
    $newProductsPending = $newProductsPending ?? count($newProducts);
@endphp

<section class="db-section fu d2">
    <div class="db-section-head">
        <div class="db-welcome-copy db-opp-intro">
            <h2 class="db-welcome-title">New in products</h2>
            <p class="db-welcome-sub">
                @if($newProductsPending > 0)
                    {{ $newProductsPending }} new {{ \Illuminate\Support\Str::plural('product', $newProductsPending) }} added by the platform {{ $newProductsPending === 1 ? 'is' : 'are' }} not in your store yet. Add the ones you like &mdash; products you already have keep updating automatically.
                @else
                    You are up to date. New products added by the platform will show up here for you to add.
                @endif
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            @if($newProductsPending > 0)
                <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Sync new products ({{ $newProductsPending }})</button>
                </form>
            @endif
            <a href="{{ route('tenant.products.new') }}" class="db-see-all">
                See all
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        </div>
    </div>

    @if(count($newProducts) > 0)
    <div class="db-opp-slider is-newin swiper" data-opp-slider data-mobile-view="1" aria-label="New in products">
        <div class="swiper-wrapper">
            @foreach($newProducts as $product)
                <article class="db-opp swiper-slide">
                    <div class="db-opp-media" @if(!empty($product['image'])) style="background-image:url('{{ $product['image'] }}')" @endif></div>

                    <div class="db-opp-body">
                        <div>
                            <h3 class="db-opp-title">@if(!empty($product['url']))<a href="{{ $product['url'] }}" style="color:inherit;text-decoration:none">{{ $product['title'] }}</a>@else{{ $product['title'] }}@endif</h3>
                            <p class="db-opp-desc">{{ $product['description'] }}</p>
                        </div>

                        <div class="db-opp-stats">
                            <div class="db-opp-stat is-dark is-half">
                                <span class="db-opp-stat-label">Platform price</span>
                                <strong>{{ $product['cost'] ?? '—' }}</strong>
                                <small>Before your profit margin</small>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                            @csrf
                            <input type="hidden" name="product_ids[]" value="{{ $product['id'] }}">
                            <button type="submit" class="btn btn-primary btn-lg db-opp-cta" style="width:100%">Add to my store</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    @endif
</section>
