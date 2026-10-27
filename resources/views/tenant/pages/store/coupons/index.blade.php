@extends('tenant.layouts.app')

@section('title', 'Coupons')

@section('content')
    <x-tenant::page-header title="Coupons" badge="Storefront" description="Discount codes, each available in all countries or only in the countries you choose.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="coupon-modal">Add Coupon</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="coupons-table" :url="route('tenant.store.coupons.data')" :columns="$columns" title="Discount Codes">
        <x-slot:toolbar>
            <x-tenant::filters-card target="coupons-table" title="Filters">
                <x-tenant::select2 name="country" label="Country" :options="['all' => 'Available in all countries'] + array_slice($countryOptions, 1, null, true)" placeholder="Any country" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>

    <x-tenant::modal id="coupon-modal" title="Add / Edit Coupon">
        <x-tenant::form id="coupon-form" :action="route('tenant.store.coupons.store')" method="POST" :validate="route('tenant.store.coupons.validate')" success="close-modal reload-table:#coupons-table">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="code" label="Code" required maxlength="50" />
                <x-tenant::input name="name_text" label="Name" required maxlength="255" />
                <x-tenant::radio-group name="type" label="Type" variant="pills" required :options="['fixed' => 'Fixed amount', 'percentage' => 'Percentage']" value="fixed" />
                <x-tenant::input type="number" step="0.01" min="0" name="value" label="Value" required />
                <x-tenant::input type="number" step="0.01" min="0" name="minimum_spend" label="Minimum Spend" />
                <x-tenant::date name="start_date" label="Start Date" enable-time />
                <x-tenant::date name="end_date" label="End Date" enable-time />
                <div class="span-2">
                    <x-tenant::country-scope :options="$countryOptions" />
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Coupon</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/coupons.js')
@endpush
