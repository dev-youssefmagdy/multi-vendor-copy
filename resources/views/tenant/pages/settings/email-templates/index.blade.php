@extends('tenant.layouts.app')

@section('title', __('Email Templates'))

@section('content')
    <x-tenant::page-header :title="__('Email Templates')" :badge="__('Settings')" :description="__('Review and adjust the vendor-facing email templates available to this tenant storefront.')" />

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="email-templates-table" :url="route('tenant.settings.email-templates.data')" :columns="$columns"
        :title="__('Tenant Email Templates')"
        :description="__('These templates are seeded from central defaults and stored locally for this tenant workspace.')"
        quick-search :search-placeholder="__('Template name or subject')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="email-templates-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('All statuses')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection
