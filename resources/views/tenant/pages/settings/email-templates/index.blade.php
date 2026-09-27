@extends('tenant.layouts.app')

@section('title', 'Email Templates')

@section('content')
    <x-tenant::page-header title="Email Templates" badge="Settings" description="Review and adjust the vendor-facing email templates available to this tenant storefront." />

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="email-templates-table" :url="route('tenant.settings.email-templates.data')" :columns="$columns"
        title="Tenant Email Templates"
        description="These templates are seeded from central defaults and stored locally for this tenant workspace."
        quick-search search-placeholder="Template name or subject">
        <x-slot:toolbar>
            <x-tenant::filters-card target="email-templates-table" title="Filters">
                <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All statuses" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>
@endsection
