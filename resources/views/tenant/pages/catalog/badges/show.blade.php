@extends('tenant.layouts.app')

@section('title', $badgeTitle)

@section('content')
    <x-tenant::page-header :title="$badgeTitle" badge="Catalog" description="Select which products appear under the {{ $badge->text }} badge, then click Save.">
        <x-slot:actions>
            <a href="{{ route('tenant.badges.sort', $badge) }}" class="btn btn-secondary">Sort Order</a>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Products</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" :columns="2" />

    <x-tenant::card title="Countries" data-badge-country-tabs>
        <div class="t-badge-country-tabs">
            <a href="{{ route('tenant.badges.show', $badge) }}" class="t-badge-country-tab {{ $activeCountryId === null ? 'is-active' : '' }}">🌍 Default (All Countries)</a>
            @foreach($countries as $country)
                <a href="{{ route('tenant.badges.show', ['badge' => $badge, 'country_id' => $country->id]) }}" class="t-badge-country-tab {{ $activeCountryId === $country->id ? 'is-active' : '' }}">{{ $country->flag_emoji }} {{ $country->name }}</a>
            @endforeach
        </div>
        @if($activeCountryId === null)
            <p class="panel-copy">Default products are shown to visitors from countries with no country-specific badge assignment.</p>
        @else
            @php $activeCountry = $countries->firstWhere('id', $activeCountryId); @endphp
            <p class="panel-copy">Products in {{ $activeCountry?->flag_emoji }} {{ $activeCountry?->name }} override the Default list for visitors from this country. Leave empty to inherit the Default assignment.</p>
        @endif
    </x-tenant::card>

    <x-tenant::card title="Product Assignment" subtitle="Use the category filter to bulk-merge products, then fine-tune with the search picker below.">
        <div class="form-grid form-grid-2" style="align-items:end;margin-bottom:16px" data-badge-category-assign>
            <x-tenant::select2 name="category_id" label="Bulk-assign by category" placeholder="— choose a category —" :options="collect($categoryTree)->mapWithKeys(fn ($row) => [$row['id'] => str_repeat('— ', $row['depth']) . $row['name']])->all()" />
            <button type="button" class="btn btn-primary btn-sm" data-assign-category-btn data-assign-url="{{ route('tenant.badges.assign-category', $badge) }}" hidden>Merge all in category</button>
        </div>
        <p class="panel-copy" data-assign-category-msg hidden></p>

        <x-tenant::select2
            name="product_ids"
            label="Assigned products"
            multiple
            ajax-url="{{ route('tenant.badges.search', $badge) }}"
            :selected="$selectedProductLabels"
            placeholder="Search products by name or SKU…"
            data-badge-product-picker
        />

        <div class="page-actions compact-actions justify-end" style="margin-top:20px">
            <button type="button" class="btn btn-primary" data-save-assignment
                data-action-url="{{ route('tenant.badges.save', $badge) }}"
                data-action-method="POST"
                data-country-id="{{ $activeCountryId }}">Save assignment</button>
        </div>
    </x-tenant::card>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/badge-show.js')
@endpush
