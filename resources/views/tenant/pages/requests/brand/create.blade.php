@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    <x-tenant::form action="{{ route('tenant.brand-requests.store') }}" :validate="route('tenant.brand-requests.validate')" success="redirect" :files="true" id="brand-request-form">
        <div class="rq-form-page">
            <div class="db-welcome fu d0">
                <div class="db-welcome-copy">
                    <h1 class="db-welcome-title">{{ $title }}</h1>
                    <p class="db-welcome-sub">{{ $description }}</p>
                </div>
                <div class="rq-actions">
                    <a href="{{ route('tenant.brand-requests.index') }}" class="btn btn-lg od-download rq-back"><svg class="rq-back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12h17M3.5 12c0-.7 4.3-5.3 5.75-6.25M3.5 12c0 .7 4.3 5.3 5.75 6.25"/></svg>Back</a>
                    <button type="submit" class="btn btn-primary btn-lg rq-add">Submit Request</button>
                </div>
            </div>

            <div class="rq-form-card fu d1">
                <x-tenant::input name="title" label="Brand title" required placeholder="e.g. NordicWear Outdoor Apparel" wrapper-class="rq-span-2" />

                <x-tenant::textarea name="description" label="Description" required :rows="9"
                    help="Explain the brand, why you want to carry it, and any licensing or sourcing details."
                    placeholder="Describe the brand in detail..." />

                <div class="rq-files">
                    <span class="field-label">Attachments (optional)</span>
                    <x-tenant::dropzone name="files" multiple
                        label="Click to upload or drag and drop"
                        sublabel="Logos, brand guidelines, licensing documents, or reference files."
                        accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                        help="Optional, max 5 files &middot; 10 MB each" />
                </div>

                {{-- Mobile only: the design repeats Submit at the end of the form. --}}
                <button type="submit" class="btn btn-primary btn-lg rq-submit-end rq-span-2">Submit Request</button>
            </div>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/brand-create.js')
@endpush
