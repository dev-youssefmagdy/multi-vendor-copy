@extends('tenant.layouts.app')

@section('title', 'Add Customer')

@section('content')
    <x-tenant::page-header title="Add Customer" badge="CRM" description="Create a new customer record for this tenant workspace." back="{{ route('tenant.customers.index') }}">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.customers.index') }}">← Customers</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card-collapse title="Customer Details" subtitle="Name, contact info, status and password." :open="true">
        <x-tenant::form action="{{ route('tenant.customers.store') }}" method="POST" validate="{{ route('tenant.customers.validate') }}" success="redirect">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="full_name" label="Full Name" required placeholder="Full name" />
                <x-tenant::input type="email" name="email" label="Email" required placeholder="customer@example.com" />
                <x-tenant::phone name="phone" label="Phone" />
                <x-tenant::select2 name="country_id" label="Country" :options="$countries" placeholder="— select country —" />
                <x-tenant::select2 name="city_id" label="City" placeholder="— select city —"
                    ajax-url="{{ route('tenant.cities.by-country') }}?format=select2"
                    depends-on="country_id" min-input="0" />
                <div class="form-grid-full">
                    <x-tenant::input name="address" label="Default Address" placeholder="Short freeform address" />
                </div>
                <x-tenant::input type="password" toggle name="password" label="Password" required placeholder="Min 6 characters" autocomplete="new-password" />
                <x-tenant::input type="password" toggle name="password_confirmation" label="Confirm Password" placeholder="Repeat password" autocomplete="new-password" />
                <x-tenant::switch name="active" :checked="true" label="Active account" />
            </div>

            <div class="page-actions compact-actions justify-end" style="margin-top:16px;">
                <a href="{{ route('tenant.customers.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Customer</button>
            </div>
        </x-tenant::form>
    </x-tenant::card-collapse>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/customer-create.js')
@endpush
