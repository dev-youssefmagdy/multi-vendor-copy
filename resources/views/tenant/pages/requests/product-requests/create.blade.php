@extends('tenant.layouts.app')

@section('title', 'New Product Request')

@section('content')
    <x-tenant::page-header title="New Product Request" badge="Catalog" description="Describe the product you want the Neozena team to add to the central catalog.">
        <x-slot:actions>
            <a href="{{ route('tenant.product-requests.index') }}" class="btn btn-secondary">Cancel</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="card fu d1" style="padding:28px 32px;max-width:760px;">
        <x-tenant::form
            action="{{ route('tenant.product-requests.store') }}"
            validate="{{ route('tenant.product-requests.validate') }}"
            success="redirect"
            files
        >
            <div class="stack-gap">
                <x-tenant::input
                    name="title"
                    label="Product Title"
                    required
                    placeholder="e.g. Wireless Bluetooth Earbuds with Noise Cancellation"
                />

                <x-tenant::textarea
                    name="description"
                    label="Description"
                    help="Include specifications, target market, estimated price range, and any other relevant details."
                    rows="8"
                    required
                    placeholder="Describe the product in detail..."
                />

                <x-tenant::input
                    type="url"
                    name="product_url"
                    label="Product URL"
                    help="Link to the product on the manufacturer, supplier, or reference site (optional)."
                    placeholder="https://example.com/product/..."
                />

                <x-tenant::dropzone
                    name="files"
                    label="Drop attachments here or click to upload"
                    sublabel="Images, PDFs, spreadsheets or reference files, max 5 files, 10 MB each"
                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                    max-files="5"
                    max-kb="10240"
                />

                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                    <a href="{{ route('tenant.product-requests.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </x-tenant::form>
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/product-request-create.js')
@endpush
