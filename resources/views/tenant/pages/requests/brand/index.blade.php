@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    <x-tenant::page-header :title="$title" :badge="$badge" :description="$description">
        <x-slot:actions>
            <a href="{{ route('tenant.brand-requests.create') }}" class="btn btn-primary">+ New Request</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="brand-requests-table" description="Narrow requests by status.">
        <div>
            <x-tenant::select name="status" label="Status" placeholder="All Statuses" :options="collect($statusOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
        </div>
    </x-tenant::filters-card>

    <x-tenant::datatable id="brand-requests-table" :url="route('tenant.brand-requests.data')" :columns="$columns"
        title="Your Requests" description="Sorted by most recent submission."
        empty-title="No brand requests yet"
        empty-copy="Submit a request when you'd like to carry a new brand in your store."
        :order="[[2, 'desc']]" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/brand-index.js')
@endpush
