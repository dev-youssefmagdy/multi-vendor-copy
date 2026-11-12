@extends('tenant.layouts.app')

@section('title', __('New Product Request'))

@section('content')
    <x-tenant::form
        action="{{ route('tenant.product-requests.store') }}"
        validate="{{ route('tenant.product-requests.validate') }}"
        success="redirect"
        files
    >
        <div class="rq-form-page">
            <div class="db-welcome fu d0">
                <div class="db-welcome-copy">
                    <h1 class="db-welcome-title">{{ __('New Product Request') }}</h1>
                    <p class="db-welcome-sub">{{ __('Describe the product you want the Neozena team to add to the central catalog.') }}</p>
                </div>
                <div class="rq-actions">
                    <a href="{{ route('tenant.product-requests.index') }}" class="btn btn-lg od-download rq-back"><svg class="rq-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12h17M3.5 12c0-.7 4.3-5.3 5.75-6.25M3.5 12c0 .7 4.3 5.3 5.75 6.25"/></svg>{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary btn-lg rq-add">{{ __('Submit Request') }}</button>
                </div>
            </div>

            <div class="rq-form-card fu d1">
                <x-tenant::input name="title" :label="__('Product title')" required :placeholder="__('e.g. Wireless Bluetooth Earbuds with Noise Cancellation')" />

                <x-tenant::input type="url" name="product_url" :label="__('Product URL (optional)')" placeholder="https://example.com/product/..." />

                <x-tenant::textarea
                    name="description"
                    :label="__('Description')"
                    :help="__('Include specifications, target market, estimated price range, and any other relevant details.')"
                    :rows="9"
                    required
                    :placeholder="__('Describe the product in detail...')"
                />

                <div class="rq-files">
                    <span class="field-label">{{ __('Attachments (optional)') }}</span>
                    <x-tenant::dropzone
                        name="files"
                        :label="__('Drop attachments here or click to upload')"
                        :sublabel="__('Images, PDFs, spreadsheets or reference files, max 5 files, 10 MB each')"
                        accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                        max-files="5"
                        max-kb="10240"
                    />
                </div>

                {{-- Mobile only: the design repeats Submit at the end of the form. --}}
                <button type="submit" class="btn btn-primary btn-lg rq-submit-end rq-span-2">{{ __('Submit Request') }}</button>
            </div>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/product-request-create.js')
@endpush
