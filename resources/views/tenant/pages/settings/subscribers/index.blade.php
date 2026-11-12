@extends('tenant.layouts.app')

@section('title', __('Subscribers'))

@section('content')
    <x-tenant::page-header :title="__('Subscribers')" :badge="__('Settings')" :description="__('Review and manage storefront newsletter subscribers for this tenant.')">
        <x-slot:actions>
            <a href="{{ route('tenant.settings.subscribers.export') }}" class="btn btn-secondary">{{ __('Export CSV') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="subscribers-table" :url="route('tenant.settings.subscribers.data')" :columns="$columns" :title="__('Subscriber List')" quick-search :search-placeholder="__('Email…')" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/subscribers.js')
@endpush
