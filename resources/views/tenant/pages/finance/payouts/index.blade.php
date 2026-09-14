@extends('tenant.layouts.app')

@section('title', 'Payouts Received')

@section('content')
    <x-tenant::page-header title="Payouts Received" badge="Finance" description="Review the payouts you have received from central for your net order share." />

    <x-tenant::stats-grid :stats="$stats" :columns="3" />

    <x-tenant::filters-card target="payouts-table" title="Filters" description="Filter payouts by status and search by invoice or transaction reference.">
        <x-tenant::input name="search" label="Search" placeholder="Invoice, transaction…" />
        <x-tenant::select2 name="status" label="Status" :options="$statusOptions" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="payouts-table" :url="route('tenant.finance.payouts.data')" :columns="$columns" title="Payout Records" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/payouts.js')
@endpush
