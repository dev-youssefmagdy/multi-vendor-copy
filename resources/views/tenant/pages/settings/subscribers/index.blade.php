@extends('tenant.layouts.app')

@section('title', 'Subscribers')

@section('content')
    <x-tenant::page-header title="Subscribers" badge="Settings" description="Review and manage storefront newsletter subscribers for this tenant.">
        <x-slot:actions>
            <a href="{{ route('tenant.settings.subscribers.export') }}" class="btn btn-secondary">Export CSV</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="subscribers-table" title="Filters">
        <x-tenant::input name="search" label="Search" placeholder="Email…" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="subscribers-table" :url="route('tenant.settings.subscribers.data')" :columns="$columns" title="Subscriber List" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/subscribers.js')
@endpush
