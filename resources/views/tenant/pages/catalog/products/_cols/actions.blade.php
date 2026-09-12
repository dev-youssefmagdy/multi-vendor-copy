@php
    $hasSocial = !empty($product->social_posts);
    $hasPriceData = !empty($product->ai_price_data);
@endphp
<div class="flex gap-2 flex-wrap items-center products-actions">
    <a href="{{ route('tenant.products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>

    <x-tenant::switch
        :checked="$product->active"
        :action-url="route('tenant.products.toggle-active', $product)"
        payload-key="active"
        wrapper-class="t-switch-inline"
        label="Active"
        data-success="reload-table:#products-table"
    />

    <x-tenant::switch
        :checked="$product->featured"
        :action-url="route('tenant.products.toggle-featured', $product)"
        payload-key="featured"
        wrapper-class="t-switch-inline"
        label="Featured"
        data-success="reload-table:#products-table"
    />

    <x-tenant::dropdown label="More" align="end">
        <x-tenant::dropdown-item data-modal-open="product-social-modal" data-product-id="{{ $product->id }}">
            {{ $hasSocial ? 'View / regenerate social posts' : 'Generate social media posts' }}
        </x-tenant::dropdown-item>
        <x-tenant::dropdown-item data-modal-open="product-video-ad-modal" data-product-id="{{ $product->id }}">
            Video ad
        </x-tenant::dropdown-item>
        <x-tenant::dropdown-item data-modal-open="product-price-finder-modal" data-product-id="{{ $product->id }}">
            {{ $hasPriceData ? 'View AI price data' : 'Fetch AI price data' }}
        </x-tenant::dropdown-item>
        <x-tenant::dropdown-item data-modal-open="product-share-modal" data-product-id="{{ $product->id }}">
            Share
        </x-tenant::dropdown-item>
        <x-tenant::dropdown-item data-modal-open="product-price-list-modal" data-product-id="{{ $product->id }}">
            Price list
        </x-tenant::dropdown-item>
    </x-tenant::dropdown>
</div>
