@extends('tenant.layouts.app')

@section('title', 'Orders')

@section('content')
    <x-tenant::page-header title="Orders" badge="Sales" description="Track tenant orders, payment collection, fulfillment progress, and full order payload details from one queue.">
        <x-slot:actions>
            <a id="orders-export-link" href="{{ route('tenant.orders.export') }}" class="btn btn-secondary">Export CSV</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="orders-table" title="Filters" export-link="#orders-export-link">
        <x-tenant::input name="search" label="Search" placeholder="Order UUID or customer" />
        <x-tenant::select2 name="status" label="Status" :options="$statusOptions" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="orders-table" :url="route('tenant.orders.data')" :columns="$columns" :order="[[7, 'desc']]" title="Order Queue" quick-search />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/orders-index.js')
@endpush
