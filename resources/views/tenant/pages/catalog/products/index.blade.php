@extends('tenant.layouts.app')

@section('title', 'Products')

@php
    $page = $products->currentPage();
    $lastPage = max(1, $products->lastPage());
    $pages = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
    $hasFilters = filled(request('search')) || filled(request('status')) || filled(request('stock')) || filled(request('category')) || !empty($imageSearchIds);
@endphp

@section('content')
    <div class="pm-page">
        @include('tenant.pages.catalog._module-nav', ['activeTab' => 'products'])

        {{-- Filters: same options as the former table filters, applied through the URL --}}
        <form class="pm-filters fu d1" method="GET" action="{{ route('tenant.products.index') }}" data-pm-filters>
            @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
            @if(!empty($imageSearchIds))<input type="hidden" name="image_ids" value="{{ implode(',', $imageSearchIds) }}">@endif
            <select name="status" class="field-control" aria-label="Status" data-pm-autosubmit>
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="stock" class="field-control" aria-label="Stock" data-pm-autosubmit>
                <option value="">All stock</option>
                <option value="in" @selected(request('stock') === 'in')>In stock</option>
                <option value="partial" @selected(request('stock') === 'partial')>Partially out of stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <select name="category" class="field-control" aria-label="Category" data-pm-autosubmit>
                <option value="">All categories</option>
                @foreach($categoryOptions as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('category') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            @if($hasFilters)
                <a href="{{ route('tenant.products.index') }}" class="pm-clear">Clear filters</a>
            @endif
            <a href="{{ route('tenant.products.sort') }}" class="pm-sort">Sort products</a>
        </form>

        @if(!empty($imageSearchIds))
            <p class="pm-note fu d1">Showing products matching your image search.</p>
        @endif

        @if($products->isEmpty())
            <div class="pm-empty fu d2">
                <h3>{{ $hasFilters ? 'No products match these filters' : 'No products yet' }}</h3>
                <p>{{ $hasFilters ? 'Try a different search or clear the filters.' : 'Add your first product to see it here.' }}</p>
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
