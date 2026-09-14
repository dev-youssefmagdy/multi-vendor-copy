@extends('tenant.layouts.app')

@section('title', 'Compliance Center')

@section('content')
    <x-tenant::page-header title="Compliance Center" badge="Compliance"
        description="Business, owner, company, banking, and verification details required to keep your store in good standing. Each section below saves independently.">
        <x-slot:meta>
            <span class="badge {{ $completionPercent >= 100 ? 'badge-green' : 'badge-amber' }}">{{ $completionPercent }}% Complete</span>
        </x-slot:meta>
    </x-tenant::page-header>

    {{-- ── Business Info ──────────────────────────────────────────────── --}}
    <x-tenant::form :action="route('tenant.settings.compliance.update')" method="POST"
        :validate="route('tenant.settings.compliance.validate')" success="redirect" files>
        <input type="hidden" name="section" value="business">
        <input type="hidden" name="from" value="{{ request('from') }}">
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
                <div class="acct-section-save">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="businessName" label="Business Name" required maxlength="255" :value="$businessName" />
                <x-tenant::input name="storeName" label="Store Name" required maxlength="255" :value="$storeName" />
                <x-tenant::select2 name="countryId" label="Country" placeholder="Select country"
                    :options="$countries->pluck('name', 'id')->all()" :value="$countryId" />
                <x-tenant::select2 name="cityId" label="City" placeholder="Select city" required
                    :options="$cities->pluck('name', 'id')->all()" :value="$cityId" :disabled="$cities->isEmpty()"
                    data-cities-url="{{ route('tenant.settings.compliance.cities-by-country', ['countryId' => '__ID__']) }}" />
                <x-tenant::phone name="phone" label="Phone" required :value="$phone" />
                <x-tenant::input type="email" name="email" label="Email" required maxlength="255" :value="$email" />
            </div>
        </section>
    </x-tenant::form>

    {{-- ── Owner Info ──────────────────────────────────────────────────── --}}
    <x-tenant::form :action="route('tenant.settings.compliance.update')" method="POST"
        :validate="route('tenant.settings.compliance.validate')" success="redirect">
        <input type="hidden" name="section" value="owner">
        <input type="hidden" name="from" value="{{ request('from') }}">
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
                <div class="acct-section-save">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="ownerName" label="Owner Full Name" required maxlength="255" :value="$ownerName" />
                <x-tenant::input name="ownerIdNumber" label="Owner ID / Passport Number" required maxlength="100" :value="$ownerIdNumber" />
                <x-tenant::input type="date" name="ownerDob" label="Date of Birth (optional)" :value="$ownerDob" />
                <x-tenant::input name="ownerContact" label="Owner Contact Details" placeholder="Phone or alternate email" maxlength="100" :value="$ownerContact" />
            </div>
        </section>
    </x-tenant::form>

    {{-- ── Company Info ────────────────────────────────────────────────── --}}
    <x-tenant::form :action="route('tenant.settings.compliance.update')" method="POST"
        :validate="route('tenant.settings.compliance.validate')" success="redirect" files>
        <input type="hidden" name="section" value="company">
        <input type="hidden" name="from" value="{{ request('from') }}">
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
                <div class="acct-section-save">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="companyName" label="Company Name" maxlength="255" :value="$companyName" />
                <x-tenant::input name="registrationNumber" label="Commercial Registration Number" required maxlength="100" :value="$registrationNumber" />
                <x-tenant::input type="date" name="registrationExpiry" label="Registration Expiry Date" :value="$registrationExpiry" />
                <x-tenant::input name="vatNumber" label="VAT / Tax Registration Number" maxlength="100" :value="$vatNumber" />
                <div class="acct-span-full">
                    <label class="field-label">Commercial Registration Document</label>
                    <input type="hidden" name="existing_registration_document_path" value="{{ $registrationDocumentPath }}">
                    @if ($registrationDocumentPath)
                        <div class="compliance-current-doc">
                            <a href="{{ $registrationDocumentPath }}" target="_blank" class="btn btn-secondary btn-sm">View uploaded document</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_registration_document" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <x-tenant::file name="registrationDocumentUpload" />
                </div>
            </div>
        </section>
    </x-tenant::form>

    {{-- ── Bank Account ────────────────────────────────────────────────── --}}
    <x-tenant::form :action="route('tenant.settings.compliance.update')" method="POST"
        :validate="route('tenant.settings.compliance.validate')" success="redirect">
        <input type="hidden" name="section" value="bank">
        <input type="hidden" name="from" value="{{ request('from') }}">
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
                <div class="acct-section-save">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="bankName" label="Bank Name" required maxlength="255" :value="$bankName" />
                <x-tenant::input name="bankHolderName" label="Account Holder Name" required maxlength="255" :value="$bankHolderName" />
                <x-tenant::input name="bankIban" label="IBAN" maxlength="100" :value="$bankIban" />
                <x-tenant::input name="bankAccountNumber" label="Account Number" required maxlength="100" :value="$bankAccountNumber" />
                <x-tenant::select2 name="currencyId" label="Currency" placeholder="Select currency"
                    :options="$currencies->mapWithKeys(fn ($c) => [$c->id => $c->code.' — '.$c->name])->all()" :value="$currencyId" />
            </div>
        </section>
    </x-tenant::form>

    {{-- ── Verification Documents ──────────────────────────────────────── --}}
    <x-tenant::form :action="route('tenant.settings.compliance.update')" method="POST"
        :validate="route('tenant.settings.compliance.validate')" success="redirect" files>
        <input type="hidden" name="section" value="documents">
        <input type="hidden" name="from" value="{{ request('from') }}">
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
                <div class="acct-section-save">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">National ID</label>
                    <input type="hidden" name="existing_doc_national_id_path" value="{{ $docNationalIdPath }}">
                    @if ($docNationalIdPath)
                        <div class="compliance-current-doc">
                            <a href="{{ $docNationalIdPath }}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_doc_national_id" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <x-tenant::file name="docNationalIdUpload" />
                </div>
                <div>
                    <label class="field-label">Commercial Registration</label>
                    <input type="hidden" name="existing_doc_commercial_registration_path" value="{{ $docCommercialRegistrationPath }}">
                    @if ($docCommercialRegistrationPath)
                        <div class="compliance-current-doc">
                            <a href="{{ $docCommercialRegistrationPath }}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_doc_commercial_registration" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <x-tenant::file name="docCommercialRegistrationUpload" />
                </div>
                <div>
                    <label class="field-label">Tax Certificate</label>
                    <input type="hidden" name="existing_doc_tax_certificate_path" value="{{ $docTaxCertificatePath }}">
                    @if ($docTaxCertificatePath)
                        <div class="compliance-current-doc">
                            <a href="{{ $docTaxCertificatePath }}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                            <label class="compliance-remove-label">
                                <input type="checkbox" name="remove_doc_tax_certificate" value="1"> Remove
                            </label>
                        </div>
                    @endif
                    <x-tenant::file name="docTaxCertificateUpload" />
                </div>
                <div class="acct-span-full">
                    <label class="field-label">Additional Documents</label>
                    @if (!empty($docAdditionalPaths))
                        <div class="compliance-additional-docs">
                            @foreach ($docAdditionalPaths as $i => $path)
                                <span class="compliance-current-doc">
                                    <input type="hidden" name="existing_doc_additional_paths[]" value="{{ $path }}">
                                    <a href="{{ $path }}" target="_blank" class="btn btn-secondary btn-sm">Document {{ $i + 1 }}</a>
                                    <label class="compliance-remove-label">
                                        <input type="checkbox" name="remove_doc_additional_paths[]" value="{{ $path }}"> Remove
                                    </label>
                                </span>
                            @endforeach
                        </div>
                    @endif
                    <x-tenant::file name="docAdditionalUploads" multiple />
                </div>
            </div>
        </section>
    </x-tenant::form>

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
                <div class="compliance-policy-row">
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
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/compliance.js')
@endpush
