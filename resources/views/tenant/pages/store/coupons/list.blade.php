@extends('tenant.layouts.app')

@section('title', $country ? __('Coupons — :country', ['country' => $country->name]) : __('Coupons — Default'))

@section('content')
    @php
        $couponsDescription = $country
            ? "Discount codes for visitors from {$country->name}."
            : __('Default coupons shown when no country-specific coupons exist.');
    @endphp
    <x-tenant::page-header
        :title="$country ? __('Coupons — :flag :name', ['flag' => $country->flag_emoji, 'name' => $country->name]) : __('Coupons — Default')"
        :badge="__('Storefront')"
        :description="$couponsDescription"
    >
        <x-slot:actions>
            <a href="{{ route('tenant.store.coupons.index') }}" class="btn btn-secondary">{{ __('← All Countries') }}</a>
            <button type="button" class="btn btn-primary" data-modal-open="coupon-modal">{{ __('Add Coupon') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="coupons-table" :url="route('tenant.store.coupons.data', $countryId)" :columns="$columns" :title="__('Discount Codes')" />

    <x-tenant::modal id="coupon-modal" :title="__('Add / Edit Coupon')">
        <x-tenant::form id="coupon-form" :action="route('tenant.store.coupons.store')" method="POST" :validate="route('tenant.store.coupons.validate')" success="close-modal reload-table:#coupons-table">
            <input type="hidden" name="country_id" value="{{ $countryId }}">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="code" :label="__('Code')" required maxlength="50" />
                <x-tenant::input name="name_text" :label="__('Name')" required maxlength="255" />
                <x-tenant::radio-group name="type" :label="__('Type')" variant="cards" required :options="[
                    ['value' => 'fixed', 'label' => __('Fixed amount'), 'icon' => '$', 'description' => __('Take a set amount off the order')],
                    ['value' => 'percentage', 'label' => __('Percentage'), 'icon' => '%', 'description' => __('Take a percent off the order')],
                ]" value="fixed" wrapper-class="span-2" />
                <x-tenant::input type="number" step="0.01" min="0" name="value" :label="__('Value')" required />
                <x-tenant::input type="number" step="0.01" min="0" name="minimum_spend" :label="__('Minimum Spend')" />
                <x-tenant::date name="start_date" :label="__('Start Date')" enable-time />
                <x-tenant::date name="end_date" :label="__('End Date')" enable-time />
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Coupon') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/coupons.js')
@endpush
