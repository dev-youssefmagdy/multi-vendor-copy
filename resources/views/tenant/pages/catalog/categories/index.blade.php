@extends('tenant.layouts.app')

@section('title', 'Categories')

@section('content')
    <x-tenant::page-header title="Categories" badge="Catalog" description="Manage tenant category ordering, featured state, and translated storefront copy.">
        <x-slot:actions>
            <a href="{{ route('tenant.categories.sort') }}" class="btn btn-secondary">Sort Categories</a>
            <a href="{{ route('tenant.categories.create') }}" class="btn btn-primary">Add Category</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="categories-table" title="Filters">
        <x-tenant::input name="search" label="Search" placeholder="Name or slug" />
        <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
        <x-tenant::select2 name="owner" label="Ownership" :options="['mine' => 'My categories only']" placeholder="All categories" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="categories-table" :url="route('tenant.categories.data')" :columns="$columns" title="Tenant Categories" quick-search />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/categories-index.js')
@endpush
