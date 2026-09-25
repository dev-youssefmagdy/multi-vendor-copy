{{--
    Real store product as a design card (Products tab). Keeps every product
    action from the old table row: active/featured toggles, edit, and the
    social / video ad / AI price / share / price list menu.
    "dummy" = market comparison data the backend doesn't provide yet.
    Pass $added = false to hide the "Already added" badge (Own products).
--}}

@php
    $added = $added ?? true;
    $label = $product->translationValue('name') ?? $product->slug ?? ('Product #'.$product->id);
    $description = \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->translationValue('description'))), 140);
    $imageUrl = $central['image_url'] ?? $product->primary_image_url;
    $price = (float) ($product->default_price ?? 0);
    $hasSocial = !empty($product->social_posts);
    $hasPriceData = !empty($product->ai_price_data);
    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
@endphp

<article class="db-opp pm-card {{ $added ? 'is-added' : '' }}">
    <div class="db-opp-media" @if($imageUrl) style="background-image:url('{{ $imageUrl }}')" @endif>
        @unless($imageUrl)
            <span class="pm-card-initial" aria-hidden="true">{{ strtoupper(mb_substr($label, 0, 1)) }}</span>
        @endunless
        @if($added)<span class="db-opp-added">Already added</span>@endif
        <span class="db-opp-cheaper">
            <small>Cheaper than market by</small>
            <strong>dummy</strong>
        </span>
    </div>

    <div class="db-opp-body">
        <div>
            <h3 class="db-opp-title pm-card-title" title="{{ $label }}">{{ $label }}</h3>
            <p class="db-opp-desc pm-card-desc">{{ $description !== '' ? $description : 'No description yet.' }}</p>
        </div>

        <div class="db-opp-stats pm-card-stats">
            <div class="db-opp-stat is-dark is-half">
                <span class="db-opp-stat-label">Cost to your customer's door</span>
                <strong>${{ number_format($price, 2) }}</strong>
                <small>Product + International Shipping</small>
            </div>
            <div class="db-opp-stat">
                <span class="db-opp-stat-label">Average Cheaper than market by</span>
                <strong>dummy</strong>
                <small>Through global stores</small>
            </div>
        </div>

        {{-- Product tools (active/featured toggles, edit, ⋯ menu) — hidden for now; uncomment to restore.
        <div class="pm-card-tools">
            <div class="pm-card-toggles">
                <x-tenant::status-toggle
                    :checked="$product->active"
                    :action-url="route('tenant.products.toggle-active', $product)"
                    on-label="Active"
                    off-label="Inactive"
                    title="Toggle active"
                    data-success="reload-page"
                />
                <x-tenant::status-toggle
                    :checked="$product->featured"
                    :action-url="route('tenant.products.toggle-featured', $product)"
                    on-label="Featured"
                    off-label="Not featured"
                    title="Toggle featured"
                    data-success="reload-page"
                />
            </div>
            <div class="pm-card-actions">
                <a href="{{ route('tenant.products.edit', $product) }}" class="t-icon-btn" title="Edit product" aria-label="Edit {{ $label }}">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </a>
                <x-tenant::dropdown align="end">
                    <x-tenant::dropdown-item data-modal-open="product-social-modal" data-product-id="{{ $product->id }}">
                        {{ $hasSocial ? 'View / regenerate social posts' : 'Generate social media posts' }}
                    </x-tenant::dropdown-item>
                    <x-tenant::dropdown-item data-modal-open="product-price-finder-modal" data-product-id="{{ $product->id }}">
                        {{ $hasPriceData ? 'View AI price data' : 'Fetch AI price data' }}
                    </x-tenant::dropdown-item>
                    <x-tenant::dropdown-item data-modal-open="product-share-modal" data-product-id="{{ $product->id }}">Share</x-tenant::dropdown-item>
                    <x-tenant::dropdown-item data-modal-open="product-price-list-modal" data-product-id="{{ $product->id }}">Price list</x-tenant::dropdown-item>
                </x-tenant::dropdown>
            </div>
        </div>
        --}}

        <button type="button" class="btn btn-lg db-opp-cta is-added" data-modal-open="product-video-ad-modal" data-product-id="{{ $product->id }}">
            {!! $idea !!}
            Create your Ad
        </button>
    </div>
</article>
