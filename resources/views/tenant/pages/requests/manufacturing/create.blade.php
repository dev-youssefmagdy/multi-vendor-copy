@extends('tenant.layouts.app')

@section('title', __('New Manufacturing Request'))

@section('content')
    <x-tenant::form
        id="manufacturing-create-form"
        :action="route('tenant.manufacturing.store')"
        :validate="route('tenant.manufacturing.validate')"
        success="redirect"
        files
    >
        <div class="rq-form-page">
            <div class="db-welcome fu d0">
                <div class="db-welcome-copy">
                    <h1 class="db-welcome-title">{{ $pageTitle }}</h1>
                    <p class="db-welcome-sub">{{ $pageDescription }}</p>
                </div>
                <div class="rq-actions">
                    <a href="{{ route('tenant.manufacturing.index') }}" class="btn btn-lg od-download rq-back"><svg class="rq-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12h17M3.5 12c0-.7 4.3-5.3 5.75-6.25M3.5 12c0 .7 4.3 5.3 5.75 6.25"/></svg>{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary btn-lg rq-add">{{ __('Submit Request') }}</button>
                </div>
            </div>

            <div class="rq-form-card fu d1">
                <x-tenant::input name="product_name" :label="__('Product name')" required :placeholder="__('Localized product name')" />
                <x-tenant::input type="number" name="quantity" :label="__('Quantity')" required min="1" max="99999" placeholder="0" />

                <x-tenant::textarea name="description" :label="__('Description')" :rows="9" :placeholder="__('Add any specifications, dimensions, materials, or special requirements...')" />

                {{-- PANELS-UPDATES-MANUFACTURING: a link to the product to manufacture + reference images. --}}
                <x-tenant::input type="url" name="reference_url" :label="__('Product link (optional)')" placeholder="https://example.com/product/..." />

                <div class="rq-files">
                    <span class="field-label">{{ __('Product images (optional)') }}</span>
                    <x-tenant::dropzone
                        name="images"
                        :label="__('Drop images here or click to upload')"
                        :sublabel="__('JPG, PNG, WEBP or GIF, max 5 images, 10 MB each')"
                        accept=".jpg,.jpeg,.png,.webp,.gif"
                        max-files="5"
                        max-kb="10240"
                    />
                </div>

                {{-- Link to an existing product: search box + result list that only appears
                     once the vendor types (manufacturing-create.js, fed by tenant.manufacturing.products.search). --}}
                <div class="t-field rq-picker" data-field="linked_product_id" data-rq-picker data-url="{{ route('tenant.manufacturing.products.search') }}">
                    <label for="rq-picker-search" class="field-label">{{ __('Similar product in our catalog (optional)') }}</label>
                    <input type="hidden" name="linked_product_id" value="{{ old('linked_product_id') }}" data-rq-picker-value>
                    <label class="rq-picker-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
                        <input type="search" id="rq-picker-search" placeholder="{{ __('Type a product name to find a similar one…') }}" autocomplete="off" data-rq-picker-input>
                    </label>
                    <ul class="rq-picker-list" role="listbox" aria-label="{{ __('Existing products') }}" data-rq-picker-list style="display:none"></ul>
                    <p class="field-error" data-error-for="linked_product_id" role="alert" hidden></p>
                </div>

                {{-- Mobile only: the design repeats Submit at the end of the form. --}}
                <button type="submit" class="btn btn-primary btn-lg rq-submit-end rq-span-2">{{ __('Submit Request') }}</button>
            </div>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/manufacturing-create.js')
@endpush
