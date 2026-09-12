@extends('tenant.layouts.app')

@section('title', 'Product Edit Requests')

@section('content')
    <x-tenant::page-header title="Product Edit Requests" badge="Catalog" description="When you edit a product name or description, it goes through admin review before going live. Track your requests here." />

    <x-tenant::stats-grid :stats="$stats" :columns="3" />

    <x-tenant::filters-card target="edit-requests-table" title="Filters">
        <x-tenant::select2 name="status" label="Status" :options="['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']" placeholder="All" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="edit-requests-table" :url="route('tenant.products.edit-requests.data')" :columns="$columns" title="Edit Requests" empty-title="No edit requests yet" empty-copy="When you change a product name or description, your request will appear here for admin review." />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/edit-requests.js')
@endpush
