@php
    $hasSocial = !empty($product->social_posts);
    $hasPriceData = !empty($product->ai_price_data);
@endphp
<div class="products-actions">
    <div class="products-actions-toggles">
        <x-tenant::status-toggle
            :checked="$product->active"
            :action-url="route('tenant.products.toggle-active', $product)"
            :on-label="__('Active')"
            :off-label="__('Inactive')"
            :title="__('Toggle active')"
            data-success="reload-table:#products-table"
        />

        <x-tenant::status-toggle
            :checked="$product->featured"
            :action-url="route('tenant.products.toggle-featured', $product)"
            :on-label="__('Featured')"
            :off-label="__('Off')"
            :title="__('Toggle featured')"
            data-success="reload-table:#products-table"
        />
    </div>

    <div class="products-actions-buttons">
        <a href="{{ route($product->is_own_product ? 'tenant.own-products.edit' : 'tenant.products.edit', $product) }}" class="t-icon-btn" title="{{ __('Edit product') }}" aria-label="{{ __('Edit product') }}">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
            </svg>
        </a>

        <x-tenant::dropdown align="end">
            <x-tenant::dropdown-item data-modal-open="product-social-modal" data-product-id="{{ $product->id }}">
                {{ $hasSocial ? __('View / regenerate social posts') : __('Generate social media posts') }}
            </x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-video-ad-modal" data-product-id="{{ $product->id }}">
                {{ __('Video ad') }}
            </x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-price-finder-modal" data-product-id="{{ $product->id }}">
                {{ $hasPriceData ? __('View AI price data') : __('Fetch AI price data') }}
            </x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-share-modal" data-product-id="{{ $product->id }}">
                {{ __('Share') }}
            </x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-price-list-modal" data-product-id="{{ $product->id }}">
                {{ __('Price list') }}
            </x-tenant::dropdown-item>
        </x-tenant::dropdown>
    </div>
</div>
