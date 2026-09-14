@extends('tenant.layouts.app')

@section('title', 'Vendor Purchases')

@section('content')
    <x-tenant::page-header title="Vendor Purchases" badge="Finance" description="Review what you owe central for product costs and shipping on each order, then settle via a payment gateway.">
        <x-slot:actions>
            <a id="vendor-purchases-export-link" href="{{ route('tenant.finance.vendor-purchases.export') }}" class="btn btn-secondary">Export CSV</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="vendor-purchases-table" title="Filters" export-link="#vendor-purchases-export-link">
        <x-tenant::input name="search" label="Search" placeholder="Order UUID or customer" />
        <x-tenant::select2 name="settled" label="Settlement" :options="['unsettled' => 'Unsettled', 'settled' => 'Settled']" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="vendor-purchases-table" :url="route('tenant.finance.vendor-purchases.data')" :columns="$columns" title="Purchase Ledger" quick-search />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/vendor-purchases.js')
@endpush
