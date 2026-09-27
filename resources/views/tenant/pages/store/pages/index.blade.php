@extends('tenant.layouts.app')

@section('title', 'Pages')

@section('content')
    <x-tenant::page-header title="Pages" badge="Storefront" description="Maintain storefront static pages for this tenant workspace.">
        <x-slot:actions>
            <a href="{{ route('tenant.store.pages.create') }}" class="btn btn-primary">Add Page</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="pages-table" :url="route('tenant.store.pages.data')" :columns="$columns" title="Store Pages" quick-search search-placeholder="Title or slug">
        <x-slot:toolbar>
            <x-tenant::filters-card target="pages-table" title="Filters">
                <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Draft']" placeholder="All" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/pages-index.js')
@endpush
