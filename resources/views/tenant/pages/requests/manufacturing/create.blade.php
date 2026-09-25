@extends('tenant.layouts.app')

@section('title', 'New Manufacturing Request')

@section('content')
    <x-tenant::form
        id="manufacturing-create-form"
        :action="route('tenant.manufacturing.store')"
        :validate="route('tenant.manufacturing.validate')"
        success="redirect"
    >
        <div class="rq-form-page">
            <div class="db-welcome fu d0">
                <div class="db-welcome-copy">
                    <h1 class="db-welcome-title">{{ $pageTitle }}</h1>
                    <p class="db-welcome-sub">{{ $pageDescription }}</p>
                </div>
                <div class="rq-actions">
                    <a href="{{ route('tenant.manufacturing.index') }}" class="btn btn-lg od-download rq-back"><svg class="rq-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12h17M3.5 12c0-.7 4.3-5.3 5.75-6.25M3.5 12c0 .7 4.3 5.3 5.75 6.25"/></svg>Back</a>
                    <button type="submit" class="btn btn-primary btn-lg rq-add">Submit Request</button>
                </div>
            </div>

            <div class="rq-form-card fu d1">
                <x-tenant::input name="product_name" label="Product name" required placeholder="Localized product name" />
                <x-tenant::input type="number" name="quantity" label="Quantity" required min="1" max="99999" placeholder="0" />

                <x-tenant::textarea name="description" label="Description" :rows="9" placeholder="Add any specifications, dimensions, materials, or special requirements..." />

                {{-- Link to an existing product: search box + always-visible result list
                     (manufacturing-create.js, fed by tenant.manufacturing.products.search). --}}
                <div class="t-field rq-picker" data-field="linked_product_id" data-rq-picker data-url="{{ route('tenant.manufacturing.products.search') }}">
                    <label for="rq-picker-search" class="field-label">Link to Existing Product (optional)</label>
                    <input type="hidden" name="linked_product_id" value="{{ old('linked_product_id') }}" data-rq-picker-value>
                    <label class="rq-picker-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
                        <input type="search" id="rq-picker-search" placeholder="Search" autocomplete="off" data-rq-picker-input>
                    </label>
                    <ul class="rq-picker-list" role="listbox" aria-label="Existing products" data-rq-picker-list>
                        <li class="rq-picker-empty">Loading…</li>
                    </ul>
                    <p class="field-error" data-error-for="linked_product_id" role="alert" hidden></p>
                </div>

                {{-- Mobile only: the design repeats Submit at the end of the form. --}}
                <button type="submit" class="btn btn-primary btn-lg rq-submit-end rq-span-2">Submit Request</button>
            </div>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/manufacturing-create.js')
@endpush
