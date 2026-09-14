@extends('tenant.layouts.app')

@section('title', 'Currencies')

@section('content')
    <x-tenant::page-header title="Currencies" badge="Settings" description="Control active currencies and the tenant storefront default currency." />

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="currencies-table" title="Filters">
        <x-tenant::input name="search" label="Search" placeholder="Code or name…" />
        <x-tenant::select2 name="status" label="Status" :options="['default' => 'Default', 'active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="currencies-table" :url="route('tenant.settings.currencies.data')" :columns="$columns" title="Tenant Currencies" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/currencies.js')
@endpush
