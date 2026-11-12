@extends('tenant.layouts.app')

@section('title', __('Pages'))

@section('content')
    <x-tenant::page-header :title="__('Pages')" :badge="__('Storefront')" :description="__('Maintain storefront static pages for this tenant workspace.')">
        <x-slot:actions>
            <a href="{{ route('tenant.store.pages.create') }}" class="btn btn-primary">{{ __('Add Page') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="pages-table" :url="route('tenant.store.pages.data')" :columns="$columns" :title="__('Store Pages')" quick-search :search-placeholder="__('Title or slug')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="pages-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['active' => __('Active'), 'inactive' => __('Draft')]" :placeholder="__('All')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/pages-index.js')
@endpush
