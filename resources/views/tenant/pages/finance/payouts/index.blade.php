@extends('tenant.layouts.app')

@section('title', 'Payouts Received')

@section('content')
    <x-tenant::page-header title="Payouts Received" badge="Finance" description="Review the payouts you have received from central for your net order share." />

    <x-tenant::stats-grid :stats="$stats" :columns="3" />

    <x-tenant::datatable id="payouts-table" :url="route('tenant.finance.payouts.data')" :columns="$columns" title="Payout Records" quick-search search-placeholder="Invoice, transaction…">
        <x-slot:toolbar>
            <x-tenant::filters-card target="payouts-table" title="Filters">
                <x-tenant::select2 name="status" label="Status" :options="$statusOptions" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/payouts.js')
@endpush
