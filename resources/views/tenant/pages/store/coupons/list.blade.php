@extends('tenant.layouts.app')

@section('title', $country ? "Coupons — {$country->name}" : 'Coupons — Default')

@section('content')
    <x-tenant::page-header
        :title="$country ? 'Coupons — ' . $country->flag_emoji . ' ' . $country->name : 'Coupons — Default'"
        badge="Storefront"
        :description="$country ? \"Discount codes for visitors from {$country->name}.\" : 'Default coupons shown when no country-specific coupons exist.'"
    >
        <x-slot:actions>
            <a href="{{ route('tenant.store.coupons.index') }}" class="btn btn-secondary">← All Countries</a>
            <button type="button" class="btn btn-primary" data-modal-open="coupon-modal">Add Coupon</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="coupons-table" :url="route('tenant.store.coupons.data', $countryId)" :columns="$columns" title="Discount Codes" />

    <x-tenant::modal id="coupon-modal" title="Add / Edit Coupon">
        <x-tenant::form id="coupon-form" :action="route('tenant.store.coupons.store')" method="POST" :validate="route('tenant.store.coupons.validate')" success="close-modal reload-table:#coupons-table">
            <input type="hidden" name="country_id" value="{{ $countryId }}">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="code" label="Code" required maxlength="50" />
                <x-tenant::input name="name_text" label="Name" required maxlength="255" />
                <x-tenant::radio-group name="type" label="Type" variant="pills" required :options="['fixed' => 'Fixed amount', 'percentage' => 'Percentage']" value="fixed" />
                <x-tenant::input type="number" step="0.01" min="0" name="value" label="Value" required />
                <x-tenant::input type="number" step="0.01" min="0" name="minimum_spend" label="Minimum Spend" />
                <x-tenant::date name="start_date" label="Start Date" enable-time />
                <x-tenant::date name="end_date" label="End Date" enable-time />
            </div>

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Coupon</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/coupons.js')
@endpush
