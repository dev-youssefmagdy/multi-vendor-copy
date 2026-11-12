@extends('tenant.layouts.app')

@section('title', __('Add Customer'))

@section('content')
    <x-tenant::page-header :title="__('Add Customer')" badge="CRM" :description="__('Create a new customer record for this tenant workspace.')" back="{{ route('tenant.customers.index') }}">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.customers.index') }}">{{ __('← Customers') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card-collapse :title="__('Customer Details')" :subtitle="__('Name, contact info, status and password.')" :open="true">
        <x-tenant::form action="{{ route('tenant.customers.store') }}" method="POST" validate="{{ route('tenant.customers.validate') }}" success="redirect">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="full_name" :label="__('Full Name')" required :placeholder="__('Full name')" />
                <x-tenant::input type="email" name="email" :label="__('Email')" required placeholder="customer@example.com" />
                <x-tenant::phone name="phone" :label="__('Phone')" country-field="country_id" :country-iso-map="$countryIsoMap" />
                <x-tenant::select2 name="country_id" :label="__('Country')" :options="$countries" :placeholder="__('— select country —')" />
                <x-tenant::select2 name="city_id" :label="__('City')" :placeholder="__('— select city —')"
                    ajax-url="{{ route('tenant.cities.by-country') }}?format=select2"
                    depends-on="country_id" min-input="0" />
                <div class="form-grid-full">
                    <x-tenant::input name="address" :label="__('Default Address')" :placeholder="__('Short freeform address')" />
                </div>
                <x-tenant::input type="password" toggle name="password" :label="__('Password')" required :placeholder="__('Min 6 characters')" autocomplete="new-password" />
                <x-tenant::input type="password" toggle name="password_confirmation" :label="__('Confirm Password')" :placeholder="__('Repeat password')" autocomplete="new-password" />
                <x-tenant::switch name="active" :checked="true" :label="__('Active account')" />
            </div>

            <div class="page-actions compact-actions justify-end">
                <a href="{{ route('tenant.customers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Create Customer') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::card-collapse>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/customer-create.js')
@endpush
