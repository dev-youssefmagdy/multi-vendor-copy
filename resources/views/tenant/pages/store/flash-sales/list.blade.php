@extends('tenant.layouts.app')

@section('title', $country ? __('Flash Sales — :country', ['country' => $country->name]) : __('Flash Sales — Default'))

@section('content')
    @php
        $flashSalesDescription = $country
            ? "Flash sale campaigns for visitors from {$country->name}."
            : __('Default flash sales shown when no country-specific campaigns exist.');
    @endphp
    <x-tenant::page-header
        :title="$country ? __('Flash Sales — :flag :name', ['flag' => $country->flag_emoji, 'name' => $country->name]) : __('Flash Sales — Default')"
        :badge="__('Storefront')"
        :description="$flashSalesDescription"
    >
        <x-slot:actions>
            <a href="{{ route('tenant.store.flash-sales.index') }}" class="btn btn-secondary">{{ __('← All Countries') }}</a>
            <button type="button" class="btn btn-primary" data-modal-open="flash-sale-modal">{{ __('Add Flash Sale') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="flash-sales-table" :url="route('tenant.store.flash-sales.data', $countryId)" :columns="$columns" :title="__('Flash Sale Campaigns')" />

    <x-tenant::modal id="flash-sale-modal" :title="__('Add / Edit Flash Sale')" size="lg">
        <x-tenant::form id="flash-sale-form" :action="route('tenant.store.flash-sales.store')" method="POST" :validate="route('tenant.store.flash-sales.validate')" files success="close-modal reload-table:#flash-sales-table">
            <input type="hidden" name="country_id" value="{{ $countryId }}">
            <div class="form-grid form-grid-2">
                <div class="span-2">
                    <x-tenant::select2 name="product_ids" :label="__('Products')" multiple required
                        ajax-url="{{ route('tenant.store.flash-sales.products.search') }}"
                        :placeholder="__('Search products by name or slug…')" />
                </div>
                <x-tenant::input type="number" step="0.01" min="0" max="100" name="discount_percentage" :label="__('Discount Percentage')" required />
                <x-tenant::date name="start_date" :label="__('Start Date')" enable-time required />
                <x-tenant::date name="end_date" :label="__('End Date')" enable-time required />
                <div>
                    <x-tenant::switch name="active" :label="__('Flash sale is active')" checked />
                </div>
                <div class="span-2">
                    <x-tenant::image-upload name="banner_image" :label="__('Banner Image')" current="{{ $defaultBanner }}" removable />
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Flash Sale') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/flash-sales.js')
@endpush
