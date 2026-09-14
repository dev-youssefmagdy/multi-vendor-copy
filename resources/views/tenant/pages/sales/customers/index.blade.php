@extends('tenant.layouts.app')

@section('title', 'Customers')

@section('content')
    <x-tenant::page-header title="Customers" badge="CRM" description="Manage tenant customers with live order counts, lifetime value, and repeat-buyer context alongside the edit workflow.">
        <x-slot:actions>
            <a id="customers-export-link" href="{{ route('tenant.customers.export') }}" class="btn btn-secondary">Export CSV</a>
            <a href="{{ route('tenant.customers.create') }}" class="btn btn-primary">Add Customer</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="customers-table" title="Filters" export-link="#customers-export-link">
        <x-tenant::input name="search" label="Search" placeholder="Name, email, or phone" />
        <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="customers-table" :url="route('tenant.customers.data')" :columns="$columns" title="Customer List" quick-search />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/customers-index.js')
@endpush
