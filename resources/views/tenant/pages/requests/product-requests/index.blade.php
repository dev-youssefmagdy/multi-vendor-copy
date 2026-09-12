@extends('tenant.layouts.app')

@section('title', 'Product Requests')

@section('content')
    <x-tenant::page-header title="Product Requests" badge="Catalog" description="Track the status of your product requests to the Neozena team.">
        <x-slot:actions>
            <a href="{{ route('tenant.product-requests.create') }}" class="btn btn-primary">+ New Request</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="product-requests-table" title="Filters" description="Narrow requests by status.">
        <x-tenant::select name="status" label="Status" placeholder="All Statuses" :options="$statusOptions" />
    </x-tenant::filters-card>

    <x-tenant::datatable
        id="product-requests-table"
        :url="route('tenant.product-requests.data')"
        :columns="$columns"
        :order="[[2, 'desc']]"
        title="Your Requests"
        description="Sorted by most recent activity."
        empty-title="No product requests yet"
        empty-copy="Submit a request when you'd like a new product added to the catalog."
    />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/product-requests-index.js')
@endpush
