@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    <x-tenant::page-header :title="$title" :badge="$badge" :description="$description" />

    <div class="card fu d1" style="padding:28px 32px;max-width:760px;">
        <x-tenant::form action="{{ route('tenant.brand-requests.store') }}" :validate="route('tenant.brand-requests.validate')" success="redirect" :files="true" id="brand-request-form">
            <div class="stack-gap">
                <x-tenant::input name="title" label="Brand Title" required placeholder="e.g. NordicWear Outdoor Apparel" />

                <x-tenant::textarea name="description" label="Description" required rows="8"
                    help="Explain the brand, why you want to carry it, and any licensing or sourcing details."
                    placeholder="Describe the brand in detail..." />

                <x-tenant::dropzone name="files" multiple
                    label="Click to upload or drag and drop"
                    sublabel="Logos, brand guidelines, licensing documents, or reference files."
                    accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                    help="Optional, max 5 files &middot; 10 MB each" />

                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                    <a href="{{ route('tenant.brand-requests.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </x-tenant::form>
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/brand-create.js')
@endpush
