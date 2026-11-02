@extends('tenant.layouts.app')

@section('title', 'Products')

@php
    $page = $products->currentPage();
    $lastPage = max(1, $products->lastPage());
    $pages = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
    $hasFilters = filled(request('search')) || !empty($imageSearchIds);
@endphp

@section('content')
    <div class="pm-page">
        @include('tenant.pages.catalog._module-nav', ['activeTab' => 'products'])

        {{-- PANELS-UPDATES-NEW-PRODUCTS-SYNC: products the platform added after registration are not pushed automatically;
             the tenant pulls them in from here (or the dashboard "New in products" section). --}}
        <div class="pm-newbar fu d1" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 16px;padding:12px 16px;border:1px solid var(--ds-border, #e5e7eb);border-radius:12px;background:var(--ds-surface, #fff)">
            <p style="margin:0;font-size:14px">
                @if(($pendingNewCount ?? 0) > 0)
                    <strong>{{ $pendingNewCount }} new {{ \Illuminate\Support\Str::plural('product', $pendingNewCount) }}</strong> from the platform {{ $pendingNewCount === 1 ? 'is' : 'are' }} not in your store yet.
                @else
                    Your store is up to date with the platform catalog.
                @endif
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                {{-- PANELS-UPDATES-PRODUCTS-SALES: sort the product cards by sales count or price --}}
                <form method="GET" action="{{ route('tenant.products.index') }}" style="margin:0">
                    @foreach(request()->except(['sort', 'page']) as $k => $v)
                        @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <select name="sort" onchange="this.form.submit()" aria-label="Sort products" style="height:44px;border-radius:12px;padding:0 12px;border:1px solid var(--ds-border, #e5e7eb);background:var(--ds-surface, #fff);color:inherit">
                        @foreach(['' => 'Default order', 'sales_desc' => 'Most sold', 'sales_asc' => 'Least sold', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('sort', '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('tenant.products.new') }}" class="btn btn-lg">New products</a>
                @if(($pendingNewCount ?? 0) > 0)
                    <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg">Sync new products ({{ $pendingNewCount }})</button>
                    </form>
                @endif
            </div>
        </div>

        @if(!empty($imageSearchIds))
            <p class="pm-note fu d1">Showing products matching your image search.</p>
        @endif

        @if($products->isEmpty())
            <div class="pm-empty fu d2">
                <h3>{{ $hasFilters ? 'No products match your search' : 'No products yet' }}</h3>
                <p>{{ $hasFilters ? 'Try a different search.' : 'Add your first product to see it here.' }}</p>
            </div>
        @else
            <div class="pm-grid fu d2">
                @foreach($products as $product)
                    @include('tenant.pages.catalog.products._card', ['product' => $product, 'central' => $centralSnapshots[$product->central_product_id] ?? null])
                @endforeach
            </div>
        @endif

        @if($products->total() > 0)
            <nav class="tc-pagination" aria-label="Products pages">
                <p>Show <strong>{{ $products->count() }}</strong> of {{ $products->total() }} result</p>
                <div class="tc-pages">
                    @if($products->onFirstPage())
                        <span class="tc-page-btn is-text is-disabled" aria-disabled="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="tc-page-btn is-text" rel="prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</a>
                    @endif

                    @foreach($pages as $i => $n)
                        @if($i > 0 && $n - $pages[$i - 1] > 1)<span class="tc-page-gap" aria-hidden="true">…</span>@endif
                        @if($n === $page)
                            <span class="tc-page-btn is-current" aria-current="page">{{ $n }}</span>
                        @else
                            <a href="{{ $products->url($n) }}" class="tc-page-btn">{{ $n }}</a>
                        @endif
                    @endforeach

                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="tc-page-btn is-next" rel="next">Next <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a>
                    @else
                        <span class="tc-page-btn is-next is-disabled" aria-disabled="true">Next <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></span>
                    @endif
                </div>
            </nav>
        @endif
    </div>

    @include('tenant.pages.catalog.products._modals.social')
    @include('tenant.pages.catalog.products._modals.video-ad')
    @include('tenant.pages.catalog.products._modals.price-finder')
    @include('tenant.pages.catalog.products._modals.share')
    @include('tenant.pages.catalog.products._modals.price-list')

    <x-tenant::image-search-modal :action="route('tenant.products.image-search')" />
@endsection

@push('tenant-vite')
    @vite(['resources/js/tenant/pages/catalog/products-index.js', 'resources/js/tenant/pages/catalog/products-grid.js'])
@endpush
