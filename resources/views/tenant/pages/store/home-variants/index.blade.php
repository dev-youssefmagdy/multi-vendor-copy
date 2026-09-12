@extends('tenant.layouts.app')

@section('title', 'Home Variants')

@section('content')
    <x-tenant::page-header title="Home Variants" badge="Storefront" description="Pick which home page layout and color palette to use, optionally per country." />

    <form method="GET" action="{{ route('tenant.store.home-variants') }}" class="hv-switcher-form section-gap" data-hv-switcher-form>
        <x-tenant::select name="theme" label="Theme" :value="$selectedThemeId"
            :options="$themes->map(fn ($theme) => ['value' => $theme->id, 'label' => $theme->name])->all()"
            data-hv-theme-select />
        <noscript><button type="submit" class="btn btn-secondary">Apply</button></noscript>
    </form>

    <div class="card table-card-shell" data-hv-scope data-theme-id="{{ $selectedThemeId }}" data-select-url="{{ route('tenant.store.home-variants.select') }}">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Home Page Variant</h3>
                <p class="panel-copy">Choose which variant of {{ $selectedTheme?->name }}'s home page to show. Set a per-country override to give visitors from that country a different layout or colors.</p>
            </div>
        </div>

        @if($availableVariants->isEmpty())
            <x-tenant::empty-state title="No variants available" copy="The platform hasn't published any home page variants for this theme yet." />
        @else
            <x-tenant::datatable id="home-variants-table"
                mode="client"
                :rows="$rows"
                :columns="$columns"
                :paging="false" />
        @endif
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/home-variants.js')
@endpush
