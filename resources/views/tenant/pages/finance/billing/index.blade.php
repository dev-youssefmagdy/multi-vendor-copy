@extends('tenant.layouts.app')

@section('title', 'Billing')

@section('content')
    <x-tenant::page-header title="Billing" badge="Finance" description="Review order billing records, payment states, gateway routing, and nested gateway payloads for the current tenant.">
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="billing-table" :url="route('tenant.finance.billing.data')" :columns="$columns" title="Order Billing Ledger" quick-search search-placeholder="Order UUID, customer, or gateway">
        <x-slot:toolbar>
            <x-tenant::filters-card target="billing-table" title="Filters">
                <x-tenant::select2 name="paid" label="Payment State" :options="['paid' => 'Paid', 'unpaid' => 'Unpaid']" placeholder="All" />
                <x-tenant::select2 name="gateway" label="Gateway" :options="$gatewayOptions" placeholder="All" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/billing-index.js')
@endpush
