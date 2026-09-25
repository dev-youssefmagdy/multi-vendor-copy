{{--
    Products module header + tab carousel, shared by every page of the
    Products module (products, own products, categories, edit requests and
    the product badge lists). Pass $activeTab (a key of $tabs below).
    The header shows the active tab's title + description beside the search box.
    Optional $addUrl / $addLabel override the add button (e.g. Own products, Categories).
    The carousel is mounted by resources/js/tenant/pages/catalog/module-nav.js.
--}}

@php
    $tabs = [
        'products' => ['Products', route('tenant.products.index'), 'Browse, search, and manage every product in your store catalog.'],
        'own-products' => ['Own products', route('tenant.own-products.index'), 'Manage tenant-scoped product details, pricing, availability, and featured status.', 'Own Products'],
        'categories' => ['Categories', route('tenant.categories.index'), 'Manage tenant category ordering, featured state, and translated storefront copy.'],
        'edit-requests' => ['Edit requests', route('tenant.products.edit-requests'), 'Product name and description edits go through admin review before going live. Track them here.'],
        'new-in' => ['New in', route('tenant.badges.show', ['badge' => 'new-in']), "These are your store's products all in one place.", 'New in products'],
        'best-selling' => ['Best selling', route('tenant.badges.show', ['badge' => 'best-selling']), 'Choose the products shown under the Best selling badge.'],
        'featured' => ['Featured', route('tenant.badges.show', ['badge' => 'featured']), 'Choose the products shown under the Featured badge.'],
        'recommended' => ['Recommended', route('tenant.badges.show', ['badge' => 'recommended']), 'Choose the products shown under the Recommended badge.'],
        'trending-now' => ['Trending now', route('tenant.badges.show', ['badge' => 'trending-now']), 'Choose the products shown under the Trending now badge.'],
    ];
    $current = $tabs[$activeTab ?? 'products'] ?? $tabs['products'];
    // Optional 4th item: a page heading that differs from the short tab label.
    $heading = $current[3] ?? $current[0];
@endphp

<div class="pm-nav fu d0" data-products-module>
    <div class="db-welcome">
        <div class="db-welcome-copy">
            <h1 class="db-welcome-title">{{ $heading }}</h1>
            <p class="db-welcome-sub">{{ $current[2] }}</p>
        </div>

        <form class="pm-search" method="GET" action="{{ route('tenant.products.index') }}" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search" aria-label="Search products">
            @if(($activeTab ?? '') === 'products')
                <button type="button" class="pm-search-camera" data-image-search-open="tenant-image-search-modal" title="Search by image" aria-label="Search by image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8a2 2 0 0 1 2-2h1l1.2-1.6A2 2 0 0 1 9.8 3.6h4.4a2 2 0 0 1 1.6.8L17 6h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z"/><circle cx="12" cy="13" r="3.2"/></svg>
                </button>
            @endif
        </form>
    </div>

    <div class="pm-bar">
        <nav class="pm-tabs swiper" data-pm-tabs aria-label="Products module">
            <div class="swiper-wrapper">
                @foreach($tabs as $key => [$label, $url])
                    <a href="{{ $url }}" class="pm-tab swiper-slide {{ ($activeTab ?? '') === $key ? 'is-active' : '' }}" @if(($activeTab ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </div>
        </nav>

        <a href="{{ $addUrl ?? route('tenant.products.create') }}" class="btn btn-primary btn-lg pm-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v8M16 12H8"/><path d="M2.5 12c0-4.48 0-6.72 1.39-8.11C5.28 2.5 7.52 2.5 12 2.5s6.72 0 8.11 1.39C21.5 5.28 21.5 7.52 21.5 12s0 6.72-1.39 8.11C18.72 21.5 16.48 21.5 12 21.5s-6.72 0-8.11-1.39C2.5 18.72 2.5 16.48 2.5 12z"/></svg>
            {{ $addLabel ?? 'Add product' }}
        </a>
    </div>
</div>

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/module-nav.js')
@endpush
