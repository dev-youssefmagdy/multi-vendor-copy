@extends('tenant.layouts.app')

@section('title', __('Sort Badge Products'))

@section('content')
    <x-tenant::page-header :title="__('Sort — :text', ['text' => __(ucwords(str_replace('-', ' ', $badge->text)))])" :badge="__('Catalog')" :description="__('Drag rows to reorder how these products appear for this badge on the storefront.')">
        <x-slot:actions>
            <a href="{{ route('tenant.badges.show', ['badge' => $badge, 'country_id' => $activeCountryId]) }}" class="btn btn-secondary">{{ __('Back to Assignment') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    {{-- Each country has its own order (Default = visitors from countries without a country-specific list). --}}
    @if(($countries ?? collect())->isNotEmpty())
        <x-tenant::card :title="__('Countries')">
            <div class="t-badge-country-tabs">
                <a href="{{ route('tenant.badges.sort', $badge) }}" class="t-badge-country-tab {{ $activeCountryId === null ? 'is-active' : '' }}" @if($activeCountryId === null) aria-current="page" @endif>{{ __('🌍 Default (All Countries)') }}</a>
                @foreach($countries as $country)
                    <a href="{{ route('tenant.badges.sort', ['badge' => $badge, 'country_id' => $country->id]) }}" class="t-badge-country-tab {{ $activeCountryId === $country->id ? 'is-active' : '' }}" @if($activeCountryId === $country->id) aria-current="page" @endif>{{ $country->flag_emoji }} {{ $country->name }}</a>
                @endforeach
            </div>
        </x-tenant::card>
    @endif

    <x-tenant::card :title="__('Assigned Products')">
        @if($products->isEmpty())
            <x-tenant::empty-state :title="__('No products assigned')" :copy="__('Assign products to this badge before ordering them.')" />
        @else
            <x-tenant::sortable-list id="badge-products-sort" :save-url="route('tenant.badges.sort', ['badge' => $badge, 'country_id' => $activeCountryId])" method="POST" payload-key="ids">
                @foreach($products as $product)
                    @include('tenant.pages.catalog.sort._row', ['item' => $product])
                @endforeach
            </x-tenant::sortable-list>
        @endif
    </x-tenant::card>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/sortable.js')
    {{-- Country tab styles live in badges.css (imported by badge-show.js, not a separate Vite entry). --}}
    <style>
        .t-badge-country-tabs { display: flex; overflow-x: auto; border-bottom: 1px solid var(--border-color); }
        .t-badge-country-tab { padding: 12px 20px; font-size: 13px; font-weight: 600; white-space: nowrap; border-bottom: 2px solid transparent; color: var(--t2); text-decoration: none; }
        .t-badge-country-tab.is-active { border-bottom-color: var(--primary); color: var(--primary); }
    </style>
@endpush
