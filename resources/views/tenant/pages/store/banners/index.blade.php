@extends('tenant.layouts.app')

@section('title', 'Banners')

@section('content')
    <x-tenant::page-header title="Banners" badge="Storefront" description="Manage homepage banners per country, or set default banners shown everywhere." />

    <div class="fu d1 section-gap t-country-card-grid">
        <a href="{{ route('tenant.store.banners') }}" class="card t-country-card">
            <h3 class="panel-title">Default</h3>
            <p class="panel-copy">Shown to visitors when no country-specific banners exist.</p>
            <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('banner', $defaultCount) }}</span>
        </a>

        @foreach($countries as $country)
            <a href="{{ route('tenant.store.banners', ['countryId' => $country->id]) }}" class="card t-country-card">
                <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                <p class="panel-copy">Banners for visitors from {{ $country->name }}.</p>
                @php($count = (int) ($countryCounts[$country->id] ?? 0))
                <span class="badge mt-2">{{ $count }} {{ Str::plural('banner', $count) }}</span>
            </a>
        @endforeach
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/country-index.js')
@endpush
