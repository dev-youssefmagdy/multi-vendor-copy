@extends('tenant.layouts.app')

@section('title', __('Payouts Received'))

@section('content')
    <x-tenant::page-header :title="__('Payouts Received')" :badge="__('Finance')" :description="__('Review the payouts you have received from central for your net order share.')" />

    <x-tenant::stats-grid :stats="$stats" :columns="3" />

    <x-tenant::datatable id="payouts-table" :url="route('tenant.finance.payouts.data')" :columns="$columns" :title="__('Payout Records')" quick-search :search-placeholder="__('Invoice, transaction…')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="payouts-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="$statusOptions" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/payouts.js')
@endpush
