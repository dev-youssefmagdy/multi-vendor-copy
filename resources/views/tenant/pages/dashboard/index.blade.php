@extends('tenant.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <script id="dashboard-chart-data" type="application/json">@json($chartPayload)</script>

    @include('tenant.pages.dashboard._hero')

    @include('tenant.pages.dashboard._performance')

    @include('tenant.pages.dashboard._pay-alert')

    @include('tenant.pages.dashboard._opportunities')

    @include('tenant.pages.dashboard._new-in')

    @include('tenant.pages.dashboard._ads')

    @include('tenant.pages.dashboard._brand')

    @include('tenant.pages.dashboard._storefront')

    @include('tenant.pages.dashboard._product-request')

    @include('tenant.pages.dashboard._partner')

    {{-- Product modals shared with the products page --}}
    @include('tenant.pages.catalog.products._modals.social')
    @include('tenant.pages.catalog.products._modals.video-ad')

    @include('tenant.pages.dashboard._opp-flash-sale-modal')
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/dashboard/index.js')
@endpush
