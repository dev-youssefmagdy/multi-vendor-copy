@extends('tenant.layouts.app')

@section('title', 'Coupons')

@section('content')
    <x-tenant::page-header title="Coupons" badge="Storefront" description="Manage discount codes per country, or set default coupons usable everywhere." />

    <div class="fu d1 section-gap t-country-card-grid">
        <a href="{{ route('tenant.store.coupons.list') }}" class="card t-country-card">
            <h3 class="panel-title">Default</h3>
            <p class="panel-copy">Shown to visitors when no country-specific coupons exist.</p>
            <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('coupon', $defaultCount) }}</span>
        </a>

        @foreach($countries as $country)
            <a href="{{ route('tenant.store.coupons.list', ['countryId' => $country->id]) }}" class="card t-country-card">
                <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                <p class="panel-copy">Coupons for visitors from {{ $country->name }}.</p>
                @php($count = (int) ($countryCounts[$country->id] ?? 0))
                <span class="badge mt-2">{{ $count }} {{ Str::plural('coupon', $count) }}</span>
            </a>
        @endforeach
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/country-index.js')
@endpush
