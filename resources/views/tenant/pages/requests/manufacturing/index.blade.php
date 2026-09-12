@extends('tenant.layouts.app')

@section('title', 'Manufacturing Requests')

@section('content')
    <x-tenant::page-header :title="$title" :badge="$badge" :description="$description">
        <x-slot:actions>
            <a id="manufacturing-export-link" href="{{ route('tenant.manufacturing.export') }}" class="btn btn-secondary">Export CSV</a>
            <a href="{{ route('tenant.manufacturing.create') }}" class="btn btn-primary">New Request</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="manufacturing-table" title="Filters" description="Filter your requests by product name or status." export-link="#manufacturing-export-link">
        <x-tenant::input name="search" label="Search" placeholder="Search by product name..." />
        <x-tenant::select2 name="status" label="Status" :options="$statusOptions" placeholder="All Statuses" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="manufacturing-table" :url="route('tenant.manufacturing.data')" :columns="$columns"
        title="Your Requests"
        empty-title="No manufacturing requests"
        empty-copy="Submit your first manufacturing request using the button above." />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/manufacturing-index.js')
@endpush
