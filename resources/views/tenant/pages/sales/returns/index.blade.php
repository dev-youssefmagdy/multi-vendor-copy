@extends('tenant.layouts.app')

@section('title', 'Return Requests')

@section('content')
    <x-tenant::page-header title="Return Requests" badge="Store" description="Review and manage product return requests from your customers.">
        <x-slot:actions>
            <a href="{{ route('tenant.returns.analytics') }}" class="btn btn-secondary">Analytics</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" :columns="4" />

    <x-tenant::filters-card target="returns-table" title="Filters" description="Filter returns by status and search by order number.">
        <x-tenant::input name="search" label="Search" placeholder="Order number…" />
        <x-tenant::select2 name="status" label="Status" :options="$statusOptions" placeholder="All statuses" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="returns-table" :url="route('tenant.returns.data')" :columns="$columns" title="Return Requests" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/returns-index.js')
@endpush
