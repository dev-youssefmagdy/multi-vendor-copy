@extends('tenant.layouts.app')

@section('title', __('Vendor Purchases'))

@section('content')
    <x-tenant::page-header :title="__('Vendor Purchases')" :badge="__('Finance')" :description="__('Review what you owe central for product costs and shipping on each order, then settle via a payment gateway.')">
        <x-slot:actions>
            <a id="vendor-purchases-export-link" href="{{ route('tenant.finance.vendor-purchases.export') }}" class="btn btn-secondary">{{ __('Export CSV') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="vendor-purchases-table" :url="route('tenant.finance.vendor-purchases.data')" :columns="$columns" :title="__('Purchase Ledger')" quick-search :search-placeholder="__('Order UUID or customer')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="vendor-purchases-table" :title="__('Filters')" export-link="#vendor-purchases-export-link">
                <x-tenant::select2 name="settled" :label="__('Settlement')" :options="['unsettled' => __('Unsettled'), 'settled' => __('Settled')]" :placeholder="__('All')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/vendor-purchases.js')
@endpush
