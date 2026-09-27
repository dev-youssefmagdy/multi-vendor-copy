@extends('tenant.layouts.app')

@section('title', 'Subscribers')

@section('content')
    <x-tenant::page-header title="Subscribers" badge="Settings" description="Review and manage storefront newsletter subscribers for this tenant.">
        <x-slot:actions>
            <a href="{{ route('tenant.settings.subscribers.export') }}" class="btn btn-secondary">Export CSV</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="subscribers-table" :url="route('tenant.settings.subscribers.data')" :columns="$columns" title="Subscriber List" quick-search search-placeholder="Email…" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/subscribers.js')
@endpush
