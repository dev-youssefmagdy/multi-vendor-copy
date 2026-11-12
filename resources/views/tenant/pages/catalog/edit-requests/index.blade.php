@extends('tenant.layouts.app')

@section('title', __('Product Edit Requests'))

@section('content')
    @include('tenant.pages.catalog._module-nav', ['activeTab' => 'edit-requests'])

    <x-tenant::stats-grid :stats="$stats" :columns="3" />

    <x-tenant::datatable id="edit-requests-table" :url="route('tenant.products.edit-requests.data')" :columns="$columns" :title="__('Edit Requests')" :empty-title="__('No edit requests yet')" :empty-copy="__('When you change a product name or description, your request will appear here for admin review.')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="edit-requests-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['all' => __('All'), 'pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')]" :placeholder="__('All')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/edit-requests.js')
@endpush
