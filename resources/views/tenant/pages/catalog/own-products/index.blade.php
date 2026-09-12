@extends('tenant.layouts.app')

@section('title', 'My Products')

@section('content')
    <x-tenant::page-header title="My Products" badge="Own Catalog" description="Manage products you have added directly — these are not shipped from central inventory.">
        <x-slot:actions>
            <a href="{{ route('tenant.own-products.create') }}" class="btn btn-primary">Add Product</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="own-products-table" title="Filters">
        <x-tenant::input name="search" label="Search" placeholder="Search by name..." />
        <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
        <x-tenant::select2 name="stock" label="Stock" :options="['in' => 'In Stock', 'partial' => 'Partially Out of Stock', 'out' => 'Out of Stock']" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="own-products-table" :url="route('tenant.own-products.data')" :columns="$columns" title="Own Products" quick-search />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/own-products-index.js')
@endpush
