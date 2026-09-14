@extends('tenant.layouts.app')

@section('title', 'New Manufacturing Request')

@section('content')
    <x-tenant::form
        id="manufacturing-create-form"
        :action="route('tenant.manufacturing.store')"
        :validate="route('tenant.manufacturing.validate')"
        success="redirect"
    >
        <x-tenant::page-header :title="$pageTitle" :badge="$badge" :description="$pageDescription">
            <x-slot:actions>
                <a href="{{ route('tenant.manufacturing.index') }}" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </x-slot:actions>
        </x-tenant::page-header>

        <x-tenant::card-collapse title="Request Details" :open="true">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="product_name" label="Product Name" required placeholder="What product do you need manufactured?" />
                <x-tenant::input type="number" name="quantity" label="Quantity" required min="1" max="99999" value="1" />

                <div class="form-col-full">
                    <x-tenant::textarea name="description" label="Description" :rows="4" placeholder="Add any specifications, dimensions, materials, or special requirements..." />
                </div>

                <div>
                    <x-tenant::select2
                        name="linked_product_id"
                        label="Link to Existing Product"
                        placeholder="Search by name or slug…"
                        ajax-url="{{ route('tenant.manufacturing.products.search') }}"
                        allow-clear
                    />
                </div>
            </div>
        </x-tenant::card-collapse>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/manufacturing-create.js')
@endpush
