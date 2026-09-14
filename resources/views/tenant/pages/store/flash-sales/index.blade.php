@extends('tenant.layouts.app')

@section('title', 'Flash Sales')

@section('content')
    <x-tenant::page-header title="Flash Sales" badge="Storefront" description="Manage limited-time discount campaigns per country, or set default campaigns shown everywhere." />

    <div class="fu d1 section-gap t-country-card-grid">
        <a href="{{ route('tenant.store.flash-sales') }}" class="card t-country-card">
            <h3 class="panel-title">Default</h3>
            <p class="panel-copy">Shown to visitors when no country-specific campaigns exist.</p>
            <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('campaign', $defaultCount) }}</span>
        </a>

        @foreach($countries as $country)
            <a href="{{ route('tenant.store.flash-sales', ['countryId' => $country->id]) }}" class="card t-country-card">
                <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                <p class="panel-copy">Flash sales for visitors from {{ $country->name }}.</p>
                @php($count = (int) ($countryCounts[$country->id] ?? 0))
                <span class="badge mt-2">{{ $count }} {{ Str::plural('campaign', $count) }}</span>
            </a>
        @endforeach
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/country-index.js')
@endpush
