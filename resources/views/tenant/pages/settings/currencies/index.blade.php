@extends('tenant.layouts.app')

@section('title', __('Currencies'))

@section('content')
    <x-tenant::page-header :title="__('Currencies')" :badge="__('Settings')" :description="__('Control active currencies and the tenant storefront default currency.')" />

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="currencies-table" :url="route('tenant.settings.currencies.data')" :columns="$columns" :title="__('Tenant Currencies')" quick-search :search-placeholder="__('Code or name…')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="currencies-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['default' => __('Default'), 'active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('All')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/currencies.js')
@endpush
