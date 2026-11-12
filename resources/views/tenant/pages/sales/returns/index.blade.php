@extends('tenant.layouts.app')

@section('title', __('Return Requests'))

@section('content')
    <x-tenant::page-header :title="__('Return Requests')" :badge="__('Store')" :description="__('Review and manage product return requests from your customers.')">
        <x-slot:actions>
            <a href="{{ route('tenant.returns.analytics') }}" class="btn btn-secondary">{{ __('Analytics') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" :columns="4" />

    <x-tenant::datatable id="returns-table" :url="route('tenant.returns.data')" :columns="$columns" :title="__('Return Requests')" quick-search :search-placeholder="__('Order number…')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="returns-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="$statusOptions" :placeholder="__('All statuses')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/returns-index.js')
@endpush
