@extends('tenant.layouts.app')

@section('title', __('Settlement Payments'))

@section('content')
    <x-tenant::page-header :title="__('Settlement Payments')" :badge="__('Finance')" :description="__('Review the settlement payments you have made to central for product cost and shipping.')" />

    <x-tenant::stats-grid :stats="$stats" :columns="3" />

    <x-tenant::datatable id="settlement-payments-table" :url="route('tenant.finance.settlement-payments.data')" :columns="$columns" :title="__('Settlement Records')" quick-search :search-placeholder="__('Invoice, order UUID, transaction…')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="settlement-payments-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="$statusOptions" value="paid" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/settlement-payments.js')
@endpush
