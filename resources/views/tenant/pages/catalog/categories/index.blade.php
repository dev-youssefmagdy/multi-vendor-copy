@extends('tenant.layouts.app')

@section('title', 'Categories')

@section('content')
    @include('tenant.pages.catalog._module-nav', ['activeTab' => 'categories', 'addUrl' => route('tenant.categories.create'), 'addLabel' => 'Add category'])

    <div class="pm-page-actions">
        <a href="{{ route('tenant.categories.sort') }}" class="btn btn-secondary">Sort categories</a>
    </div>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="categories-table" :url="route('tenant.categories.data')" :columns="$columns" title="Tenant Categories" quick-search search-placeholder="Name or slug">
        <x-slot:toolbar>
            <x-tenant::filters-card target="categories-table" title="Filters">
                <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
                <x-tenant::select2 name="owner" label="Ownership" :options="['mine' => 'My categories only']" placeholder="All categories" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/categories-index.js')
@endpush
