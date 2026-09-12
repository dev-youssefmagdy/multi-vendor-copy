@extends('tenant.layouts.app')

@section('title', 'Products')

@section('content')
    <x-tenant::page-header title="Products" badge="Catalog" description="Manage tenant-scoped product details, pricing, availability, and featured status.">
        <x-slot:actions>
            <a href="{{ route('tenant.products.sort') }}" class="btn btn-secondary">Sort Products</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="products-table" title="Filters">
        <div class="products-search-wrap">
            <x-tenant::input name="search" label="Search" placeholder="Name or slug" />
            <button type="button" class="products-search-camera-btn" data-image-search-open="tenant-image-search-modal" title="Search by image">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8a2 2 0 0 1 2-2h1l1.2-1.6A2 2 0 0 1 9.8 3.6h4.4a2 2 0 0 1 1.6.8L17 6h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z"/>
                    <circle cx="12" cy="13" r="3.2"/>
                </svg>
            </button>
        </div>
        <div class="products-image-search-status" data-products-image-search-status hidden></div>
        <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
        <x-tenant::select2 name="stock" label="Stock" :options="['in' => 'In Stock', 'partial' => 'Partially Out of Stock', 'out' => 'Out of Stock']" placeholder="All" />
        <x-tenant::select2 name="category" label="Category" :options="$categoryOptions" placeholder="All Categories" />
        <input type="hidden" name="image_ids" data-products-image-ids-input>
    </x-tenant::filters-card>

    <x-tenant::datatable id="products-table" :url="route('tenant.products.data')" :columns="$columns" title="Vendor Products" quick-search />

    @include('tenant.pages.catalog.products._modals.social')
    @include('tenant.pages.catalog.products._modals.video-ad')
    @include('tenant.pages.catalog.products._modals.price-finder')
    @include('tenant.pages.catalog.products._modals.share')
    @include('tenant.pages.catalog.products._modals.price-list')

    <x-tenant::image-search-modal :action="route('tenant.products.image-search')" />
@endsection

@push('tenant-vite')
    @vite(['resources/js/tenant/pages/catalog/products-index.js'])
@endpush
