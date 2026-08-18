@extends('layouts.tenant')

@section('content')
<style>
.acct-section-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.acct-section-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(0, 229, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.acct-section-icon {
    width: 18px;
    height: 18px;
    color: var(--cyan);
}

.acct-span-full {
    grid-column: 1 / -1;
}

.compliance-file {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--border2);
    background: rgba(255, 255, 255, 0.02);
    color: var(--t1);
    font-size: 13px;
    cursor: pointer;
}

.compliance-file::file-selector-button {
    padding: 4px 12px;
    border-radius: 6px;
    border: 1px solid var(--border2);
    background: rgba(0, 229, 255, 0.08);
    color: var(--cyan);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    margin-right: 10px;
    transition: background 0.15s;
}

.compliance-file::file-selector-button:hover {
    background: rgba(0, 229, 255, 0.15);
}

.compliance-remove-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--t2);
    cursor: pointer;
}
</style>

<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Compliance Center</h1>
                <span class="page-badge">Compliance</span>
                <span class="badge {{ $completionPercent >= 100 ? 'badge-green' : 'badge-amber' }}">{{ $completionPercent }}% Complete</span>
            </div>
            <p class="page-copy">Business, owner, company, banking, and verification details required to keep your store in good standing.</p>
        </div>
        <div class="page-actions">
            <x-btn type="submit" form="compliance-center-form">Save Changes</x-btn>
        </div>
    </div>

    <form id="compliance-center-form" method="POST" action="{{ route('tenant.settings.compliance.update') }}" enctype="multipart/form-data">
        @csrf

        {{-- ── Business Info ──────────────────────────────────────────────── --}}
        <section class="card form-card fu d1 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m8-16h.01M11 9h.01M11 13h.01M11 17h.01M15 9h.01M15 13h.01M15 17h.01"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Business Info</h3>
                    <p class="panel-copy">Where your business legally operates from.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Business Name</label>
                    <x-input type="text" name="businessName" value="{{ old('businessName', $businessName) }}" :error="$errors->has('businessName')" />
                    @error('businessName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Store Name</label>
                    <x-input type="text" name="storeName" value="{{ old('storeName', $storeName) }}" :error="$errors->has('storeName')" />
                    @error('storeName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Country</label>
                    <x-select name="countryId" placeholder="Select country" :error="$errors->has('countryId')">
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected(old('countryId', $countryId) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </x-select>
                    @error('countryId')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">City</label>
                    <x-input type="text" name="city" value="{{ old('city', $city) }}" :error="$errors->has('city')" />
                    @error('city')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Phone</label>
                    <x-input type="tel" data-phone-input name="phone" value="{{ old('phone', $phone) }}" :error="$errors->has('phone')" />
                    @error('phone')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Email</label>
                    <x-input type="email" name="email" value="{{ old('email', $email) }}" :error="$errors->has('email')" />
                    @error('email')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- ── Owner Info ──────────────────────────────────────────────────── --}}
        <section class="card form-card fu d2 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Owner Info</h3>
                    <p class="panel-copy">The legal owner or representative of this store.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Owner Full Name</label>
                    <x-input type="text" name="ownerName" value="{{ old('ownerName', $ownerName) }}" :error="$errors->has('ownerName')" />
                    @error('ownerName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Owner ID / Passport Number</label>
                    <x-input type="text" name="ownerIdNumber" value="{{ old('ownerIdNumber', $ownerIdNumber) }}" :error="$errors->has('ownerIdNumber')" />
                    @error('ownerIdNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Date of Birth (optional)</label>
                    <x-input type="date" name="ownerDob" value="{{ old('ownerDob', $ownerDob) }}" :error="$errors->has('ownerDob')" />
                    @error('ownerDob')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Owner Contact Details</label>
                    <x-input type="text" name="ownerContact" placeholder="Phone or alternate email" value="{{ old('ownerContact', $ownerContact) }}" :error="$errors->has('ownerContact')" />
                    @error('ownerContact')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- ── Company Info ────────────────────────────────────────────────── --}}
        <section class="card form-card fu d3 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Company Info (if applicable)</h3>
                    <p class="panel-copy">Registered company details, if you operate under a legal entity.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Company Name</label>
                    <x-input type="text" name="companyName" value="{{ old('companyName', $companyName) }}" :error="$errors->has('companyName')" />
                    @error('companyName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Commercial Registration Number</label>
                    <x-input type="text" name="registrationNumber" value="{{ old('registrationNumber', $registrationNumber) }}" :error="$errors->has('registrationNumber')" />
                    @error('registrationNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Registration Expiry Date</label>
                    <x-input type="date" name="registrationExpiry" value="{{ old('registrationExpiry', $registrationExpiry) }}" :error="$errors->has('registrationExpiry')" />
                    @error('registrationExpiry')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">VAT / Tax Registration Number</label>
                    <x-input type="text" name="vatNumber" value="{{ old('vatNumber', $vatNumber) }}" :error="$errors->has('vatNumber')" />
                    @error('vatNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="acct-span-full">
                    <label class="field-label">Commercial Registration Document</label>
                    <input type="hidden" name="existing_registration_document_path" value="{{ $registrationDocumentPath }}">
                    @if ($registrationDocumentPath)
                        <div class="flex items-center gap-2" style="margin-bottom:8px;">
                            <a href="{{ $registrationDocumentPath }}" target="_blank" class="btn btn-secondary btn-sm">View uploaded document</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_registration_document" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <input type="file" name="registrationDocumentUpload" class="compliance-file" />
                    @error('registrationDocumentUpload')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- ── Bank Account ────────────────────────────────────────────────── --}}
        <section class="card form-card fu d4 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Bank Account</h3>
                    <p class="panel-copy">Used to process payouts for your store's earnings.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Bank Name</label>
                    <x-input type="text" name="bankName" value="{{ old('bankName', $bankName) }}" :error="$errors->has('bankName')" />
                    @error('bankName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Account Holder Name</label>
                    <x-input type="text" name="bankHolderName" value="{{ old('bankHolderName', $bankHolderName) }}" :error="$errors->has('bankHolderName')" />
                    @error('bankHolderName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">IBAN</label>
                    <x-input type="text" name="bankIban" value="{{ old('bankIban', $bankIban) }}" :error="$errors->has('bankIban')" />
                    @error('bankIban')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Account Number</label>
                    <x-input type="text" name="bankAccountNumber" value="{{ old('bankAccountNumber', $bankAccountNumber) }}" :error="$errors->has('bankAccountNumber')" />
                    @error('bankAccountNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Currency</label>
                    <x-input type="text" name="bankCurrency" placeholder="e.g. USD" value="{{ old('bankCurrency', $bankCurrency) }}" :error="$errors->has('bankCurrency')" />
                    @error('bankCurrency')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- ── Verification Documents ──────────────────────────────────────── --}}
        <section class="card form-card fu d5 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Verification Documents</h3>
                    <p class="panel-copy">Upload the documents our compliance team needs to verify your store.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">National ID</label>
                    <input type="hidden" name="existing_doc_national_id_path" value="{{ $docNationalIdPath }}">
                    @if ($docNationalIdPath)
                        <div class="flex items-center gap-2" style="margin-bottom:8px;">
                            <a href="{{ $docNationalIdPath }}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_doc_national_id" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <input type="file" name="docNationalIdUpload" class="compliance-file" />
                    @error('docNationalIdUpload')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Commercial Registration</label>
                    <input type="hidden" name="existing_doc_commercial_registration_path" value="{{ $docCommercialRegistrationPath }}">
                    @if ($docCommercialRegistrationPath)
                        <div class="flex items-center gap-2" style="margin-bottom:8px;">
                            <a href="{{ $docCommercialRegistrationPath }}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_doc_commercial_registration" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <input type="file" name="docCommercialRegistrationUpload" class="compliance-file" />
                    @error('docCommercialRegistrationUpload')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Tax Certificate</label>
                    <input type="hidden" name="existing_doc_tax_certificate_path" value="{{ $docTaxCertificatePath }}">
                    @if ($docTaxCertificatePath)
                        <div class="flex items-center gap-2" style="margin-bottom:8px;">
                            <a href="{{ $docTaxCertificatePath }}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_doc_tax_certificate" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <input type="file" name="docTaxCertificateUpload" class="compliance-file" />
                    @error('docTaxCertificateUpload')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="acct-span-full">
                    <label class="field-label">Additional Documents</label>
                    @if (!empty($docAdditionalPaths))
                        <div class="flex flex-wrap gap-2" style="margin-bottom:8px;">
                            @foreach ($docAdditionalPaths as $i => $path)
                                <span class="flex items-center gap-2">
                                    <input type="hidden" name="existing_doc_additional_paths[]" value="{{ $path }}">
                                    <a href="{{ $path }}" target="_blank" class="btn btn-secondary btn-sm">Document {{ $i + 1 }}</a>
                                    <label class="compliance-remove-label">
                                        <input type="checkbox" name="remove_doc_additional_paths[]" value="{{ $path }}"> Remove
                                    </label>
                                </span>
                            @endforeach
                        </div>
                    @endif
                    <input type="file" name="docAdditionalUploads[]" multiple class="compliance-file" />
                    @error('docAdditionalUploads.*')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- ── Store Policies ──────────────────────────────────────────────── --}}
        <section class="card form-card fu d6">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 5.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Store Policies</h3>
                    <p class="panel-copy">Your Return, Privacy, Terms, and Shipping pages, managed under Storefront → Pages.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                @foreach ($policyPages as $entry)
                    <div class="flex items-center justify-between" style="padding:10px 14px;border:1px solid var(--border);border-radius:10px;">
                        <span class="field-label" style="margin:0;">{{ $entry['label'] }}</span>
                        @if ($entry['page'])
                            <a href="{{ route('tenant.store.pages.edit', $entry['page']->id) }}" class="btn btn-secondary btn-sm">Edit page</a>
                        @else
                            <a href="{{ route('tenant.store.pages.create') }}" class="btn btn-secondary btn-sm">Create page</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

    </form>

</main>
@endsection
