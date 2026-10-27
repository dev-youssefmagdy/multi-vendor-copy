@extends('tenant.layouts.app')

@section('title', 'Flash Sales')

@section('content')
    <x-tenant::page-header title="Flash Sales" badge="Storefront" description="Flash sale campaigns, each running in all countries or only in the countries you choose.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="flash-sale-modal">Add Flash Sale</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="flash-sales-table" :url="route('tenant.store.flash-sales.data')" :columns="$columns" title="Flash Sale Campaigns">
        <x-slot:toolbar>
            <x-tenant::filters-card target="flash-sales-table" title="Filters">
                <x-tenant::select2 name="country" label="Country" :options="['all' => 'Available in all countries'] + array_slice($countryOptions, 1, null, true)" placeholder="Any country" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>

    <x-tenant::modal id="flash-sale-modal" title="Add / Edit Flash Sale" size="lg">
        <x-tenant::form id="flash-sale-form" :action="route('tenant.store.flash-sales.store')" method="POST" :validate="route('tenant.store.flash-sales.validate')" files success="close-modal reload-table:#flash-sales-table">
            <div class="form-grid form-grid-2">
                <div class="span-2">
                    <x-tenant::select2 name="product_ids" label="Products" multiple required
                        ajax-url="{{ route('tenant.store.flash-sales.products.search') }}"
                        placeholder="Search products by name or slug…" />
                </div>
                <x-tenant::input type="number" step="0.01" min="0" max="100" name="discount_percentage" label="Discount Percentage" required />
                <x-tenant::date name="start_date" label="Start Date" enable-time required />
                <x-tenant::date name="end_date" label="End Date" enable-time required />
                <div>
                    <x-tenant::switch name="active" label="Flash sale is active" checked />
                </div>
                <div class="span-2">
                    <x-tenant::country-scope :options="$countryOptions" />
                </div>
                <div class="span-2">
                    <x-tenant::image-upload name="banner_image" label="Banner Image" current="{{ $defaultBanner }}" removable />
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Flash Sale</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/flash-sales.js')
@endpush
