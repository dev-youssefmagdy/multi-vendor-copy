@extends('tenant.layouts.app')

@section('title', __('Support Tickets'))

@section('content')
    <x-tenant::page-header :title="__('Support Tickets')" :badge="__('Help Desk')" :description="__('Raise and track support requests with the marketplace admin team.')">
        <x-slot:actions>
            <a href="{{ route('tenant.support.create') }}" class="btn btn-primary">{{ __('+ New Ticket') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable
        id="support-tickets-table"
        :url="route('tenant.support.data')"
        :columns="$columns"
        :order="[[5, 'desc']]"
        :title="__('Your Tickets')"
        :empty-title="__('No support tickets yet')"
        :empty-copy="__('Raise a new ticket if you need help from the marketplace team.')"
    />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/support/tickets-index.js')
@endpush
