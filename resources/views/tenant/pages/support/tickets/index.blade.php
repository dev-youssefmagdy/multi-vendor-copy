@extends('tenant.layouts.app')

@section('title', 'Support Tickets')

@section('content')
    <x-tenant::page-header title="Support Tickets" badge="Help Desk" description="Raise and track support requests with the marketplace admin team.">
        <x-slot:actions>
            <a href="{{ route('tenant.support.create') }}" class="btn btn-primary">+ New Ticket</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable
        id="support-tickets-table"
        :url="route('tenant.support.data')"
        :columns="$columns"
        :order="[[5, 'desc']]"
        title="Your Tickets"
        empty-title="No support tickets yet"
        empty-copy="Raise a new ticket if you need help from the marketplace team."
    />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/support/tickets-index.js')
@endpush
