@extends('tenant.layouts.app')

@section('title', __('Categories'))

@section('content')
    @include('tenant.pages.catalog._module-nav', ['activeTab' => 'categories', 'addUrl' => route('tenant.categories.create'), 'addLabel' => __('Add category')])

    <div class="pm-page-actions">
        <a href="{{ route('tenant.categories.sort') }}" class="btn btn-secondary">{{ __('Sort categories') }}</a>
    </div>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="categories-table" :url="route('tenant.categories.data')" :columns="$columns" :title="__('Tenant Categories')" quick-search :search-placeholder="__('Name or slug')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="categories-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('All')" />
                <x-tenant::select2 name="owner" :label="__('Ownership')" :options="['mine' => __('My categories only')]" :placeholder="__('All categories')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/categories-index.js')
@endpush
