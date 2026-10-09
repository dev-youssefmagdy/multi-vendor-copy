@extends('tenant.layouts.app')

@section('title', __('Refunds'))

@section('content')
    <x-tenant::page-header :title="__('Refunds')" :badge="__('Sales')" :description="__('Every refund issued for your orders — cancellations, returns and manual refunds.')">
        <x-slot:actions>
            <a href="{{ route('tenant.returns.index') }}" class="btn btn-secondary">{{ __('Returns') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" :columns="4" />

    <x-tenant::datatable id="refunds-table" :url="route('tenant.refunds.data')" :columns="$columns" :title="__('Refunds')" quick-search :search-placeholder="__('Reference or order number…')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="refunds-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="$statusOptions" :placeholder="__('All statuses')" />
                <x-tenant::select2 name="source" :label="__('Source')" :options="$sourceOptions" :placeholder="__('All sources')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection
