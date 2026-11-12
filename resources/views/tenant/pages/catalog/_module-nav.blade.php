{{--
    Products module header + scrollable tab strip, shared by every page of the
    Products module (products, own products, categories, edit requests and
    the product badge lists). Pass $activeTab (a key of $tabs below).
    The header shows the active tab's title + description beside the search box.
    The add button is shown only when a page passes $addUrl (Own products, Categories); platform
    products are added from the dashboard / "New products" page, so the other tabs have none.
    $addLabel overrides its label (default "Add product").
    The strip behaviour (drag, wheel, fades) lives in resources/js/tenant/pages/catalog/module-nav.js.
--}}

@php
    $tabs = [
        'products' => [__('Products'), route('tenant.products.index'), __('Browse, search, and manage every product in your store catalog.')],
        'own-products' => [__('Own products'), route('tenant.own-products.index'), __('Manage tenant-scoped product details, pricing, availability, and featured status.'), __('Own Products')],
        'categories' => [__('Categories'), route('tenant.categories.index'), __('Manage tenant category ordering, featured state, and translated storefront copy.')],
        'edit-requests' => [__('Edit requests'), route('tenant.products.edit-requests'), __('Product name and description edits go through admin review before going live. Track them here.')],
        'new-in' => [__('New in'), route('tenant.badges.show', ['badge' => 'new-in']), __("These are your store's products all in one place."), __('New in products')],
        'best-selling' => [__('Best selling'), route('tenant.badges.show', ['badge' => 'best-selling']), __('Choose the products shown under the Best selling badge.')],
        'featured' => [__('Featured'), route('tenant.badges.show', ['badge' => 'featured']), __('Choose the products shown under the Featured badge.')],
        'recommended' => [__('Recommended'), route('tenant.badges.show', ['badge' => 'recommended']), __('Choose the products shown under the Recommended badge.')],
        'trending-now' => [__('Trending now'), route('tenant.badges.show', ['badge' => 'trending-now']), __('Choose the products shown under the Trending now badge.')],
    ];
    $current = $tabs[$activeTab ?? 'products'] ?? $tabs['products'];
    // Optional 4th item: a page heading that differs from the short tab label.
    $heading = $current[3] ?? $current[0];
@endphp

<div class="pm-nav fu d0" data-products-module>
    <div class="db-welcome">
        <div class="db-welcome-copy">
            <h1 class="db-welcome-title">{{ __($heading) }}</h1>
            <p class="db-welcome-sub">{{ __($current[2]) }}</p>
        </div>

        <form class="pm-search" method="GET" action="{{ route('tenant.products.index') }}" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search') }}" aria-label="{{ __('Search products') }}">
            {{-- PANELS-UPDATES-REMOVE-IMAGE-SEARCH: image search was removed from the merchant side --}}
        </form>
    </div>

    <div class="pm-bar">
        <nav class="pm-tabs" data-pm-tabs aria-label="{{ __('Products module') }}">
            @foreach($tabs as $key => [$label, $url])
                <a href="{{ $url }}" class="pm-tab {{ ($activeTab ?? '') === $key ? 'is-active' : '' }}" draggable="false" @if(($activeTab ?? '') === $key) aria-current="page" @endif>{{ __($label) }}</a>
            @endforeach
        </nav>

        @isset($addUrl)
        <a href="{{ $addUrl }}" class="btn btn-primary btn-lg pm-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v8M16 12H8"/><path d="M2.5 12c0-4.48 0-6.72 1.39-8.11C5.28 2.5 7.52 2.5 12 2.5s6.72 0 8.11 1.39C21.5 5.28 21.5 7.52 21.5 12s0 6.72-1.39 8.11C18.72 21.5 16.48 21.5 12 21.5s-6.72 0-8.11-1.39C2.5 18.72 2.5 16.48 2.5 12z"/></svg>
            {{ __($addLabel ?? __('Add product')) }}
        </a>
        @endisset
    </div>
</div>

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/module-nav.js')
@endpush
