<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\TenantNavigation;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Tenant\Page;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplianceCenterController extends Controller
{
    public function __construct(private readonly TenantPanelService $service) {}

    public function show(): View
    {
        $tenant = tenant();

        // $tenant is a Tenant model instance: VirtualColumn decodes `data`
        // straight onto the model's own attributes on retrieval and nulls
        // out ->data itself, so reads must go through the attribute
        // directly ($tenant->compliance_x), not data_get($tenant, 'data.x').
        $rawPhone = (string) ($tenant?->compliance_phone ?? $tenant?->phone ?? '');

        return view('tenant.setting.compliance-center', [
            'businessName' => (string) ($tenant?->compliance_business_name ?? ''),
            'storeName' => (string) ($tenant?->compliance_store_name ?? $tenant?->shop_name ?? ''),
            'countryId' => $tenant?->compliance_country ?: null,
            'city' => (string) ($tenant?->compliance_city ?? ''),
            'phone' => str_contains($rawPhone, 'object') ? '' : $rawPhone,
            'email' => (string) ($tenant?->compliance_email ?? $tenant?->email ?? ''),

            'ownerName' => (string) ($tenant?->compliance_owner_name ?? ''),
            'ownerIdNumber' => (string) ($tenant?->compliance_owner_id_number ?? ''),
            'ownerDob' => $tenant?->compliance_owner_dob ?: null,
            'ownerContact' => (string) ($tenant?->compliance_owner_contact ?? ''),

            'companyName' => (string) ($tenant?->compliance_company_name ?? ''),
            'registrationNumber' => (string) ($tenant?->compliance_registration_number ?? ''),
            'registrationExpiry' => $tenant?->compliance_registration_expiry ?: null,
            'vatNumber' => (string) ($tenant?->compliance_vat_number ?? ''),
            'registrationDocumentPath' => $tenant?->compliance_registration_document_path ?: null,

            'bankName' => (string) ($tenant?->compliance_bank_name ?? ''),
            'bankHolderName' => (string) ($tenant?->compliance_bank_holder_name ?? ''),
            'bankAccountNumber' => (string) ($tenant?->compliance_bank_account_number ?? ''),
            'bankIban' => (string) ($tenant?->compliance_bank_iban ?? ''),
            'bankCurrency' => (string) ($tenant?->compliance_bank_currency ?? ''),

            'docNationalIdPath' => $tenant?->compliance_doc_national_id_path ?: null,
            'docCommercialRegistrationPath' => $tenant?->compliance_doc_commercial_registration_path ?: null,
            'docTaxCertificatePath' => $tenant?->compliance_doc_tax_certificate_path ?: null,
            'docAdditionalPaths' => (array) ($tenant?->compliance_doc_additional_paths ?? []),

            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'policyPages' => $this->policyPages(),
            'completionPercent' => TenantNavigation::complianceCompletionPercent($tenant),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'storeName' => ['required', 'string', 'max:255'],
            'countryId' => ['nullable', 'integer', 'exists:countries,id'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],

            'ownerName' => ['required', 'string', 'max:255'],
            'ownerIdNumber' => ['required', 'string', 'max:100'],
            'ownerDob' => ['nullable', 'date'],
            'ownerContact' => ['nullable', 'string', 'max:100'],

            'companyName' => ['nullable', 'string', 'max:255'],
            'registrationNumber' => ['required', 'string', 'max:100'],
            'registrationExpiry' => ['nullable', 'date'],
            'vatNumber' => ['nullable', 'string', 'max:100'],
            'registrationDocumentUpload' => ['nullable', 'file', 'max:5120'],
            'existing_registration_document_path' => ['nullable', 'string'],
            'remove_registration_document' => ['nullable', 'boolean'],

            'bankName' => ['required', 'string', 'max:255'],
            'bankHolderName' => ['required', 'string', 'max:255'],
            'bankAccountNumber' => ['required', 'string', 'max:100'],
            'bankIban' => ['nullable', 'string', 'max:100'],
            'bankCurrency' => ['nullable', 'string', 'max:10'],

            'docNationalIdUpload' => ['nullable', 'file', 'max:5120'],
            'existing_doc_national_id_path' => ['nullable', 'string'],
            'remove_doc_national_id' => ['nullable', 'boolean'],

            'docCommercialRegistrationUpload' => ['nullable', 'file', 'max:5120'],
            'existing_doc_commercial_registration_path' => ['nullable', 'string'],
            'remove_doc_commercial_registration' => ['nullable', 'boolean'],

            'docTaxCertificateUpload' => ['nullable', 'file', 'max:5120'],
            'existing_doc_tax_certificate_path' => ['nullable', 'string'],
            'remove_doc_tax_certificate' => ['nullable', 'boolean'],

            'docAdditionalUploads.*' => ['nullable', 'file', 'max:5120'],
            'existing_doc_additional_paths' => ['nullable', 'array'],
            'remove_doc_additional_paths' => ['nullable', 'array'],
        ]);

        $registrationDocumentPath = $request->boolean('remove_registration_document')
            ? null
            : ($request->input('existing_registration_document_path') ?: null);
        if ($request->hasFile('registrationDocumentUpload')) {
            $registrationDocumentPath = $this->service->storeComplianceDocument($request->file('registrationDocumentUpload'));
        }

        $docNationalIdPath = $request->boolean('remove_doc_national_id')
            ? null
            : ($request->input('existing_doc_national_id_path') ?: null);
        if ($request->hasFile('docNationalIdUpload')) {
            $docNationalIdPath = $this->service->storeComplianceDocument($request->file('docNationalIdUpload'));
        }

        $docCommercialRegistrationPath = $request->boolean('remove_doc_commercial_registration')
            ? null
            : ($request->input('existing_doc_commercial_registration_path') ?: null);
        if ($request->hasFile('docCommercialRegistrationUpload')) {
            $docCommercialRegistrationPath = $this->service->storeComplianceDocument($request->file('docCommercialRegistrationUpload'));
        }

        $docTaxCertificatePath = $request->boolean('remove_doc_tax_certificate')
            ? null
            : ($request->input('existing_doc_tax_certificate_path') ?: null);
        if ($request->hasFile('docTaxCertificateUpload')) {
            $docTaxCertificatePath = $this->service->storeComplianceDocument($request->file('docTaxCertificateUpload'));
        }

        $existingAdditionalPaths = (array) $request->input('existing_doc_additional_paths', []);
        $removeAdditionalPaths = (array) $request->input('remove_doc_additional_paths', []);
        $docAdditionalPaths = array_values(array_diff($existingAdditionalPaths, $removeAdditionalPaths));
        foreach ($request->file('docAdditionalUploads', []) as $upload) {
            if ($upload) {
                $docAdditionalPaths[] = $this->service->storeComplianceDocument($upload);
            }
        }

        $this->service->updateCompliance([
            'business_name' => $validated['businessName'],
            'store_name' => $validated['storeName'],
            'country' => $validated['countryId'] ?? null,
            'city' => $validated['city'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],

            'owner_name' => $validated['ownerName'],
            'owner_id_number' => $validated['ownerIdNumber'],
            'owner_dob' => $validated['ownerDob'] ?? null,
            'owner_contact' => $validated['ownerContact'] ?? '',

            'company_name' => $validated['companyName'] ?? '',
            'registration_number' => $validated['registrationNumber'],
            'registration_expiry' => $validated['registrationExpiry'] ?? null,
            'vat_number' => $validated['vatNumber'] ?? '',
            'registration_document_path' => $registrationDocumentPath,

            'bank_name' => $validated['bankName'],
            'bank_holder_name' => $validated['bankHolderName'],
            'bank_account_number' => $validated['bankAccountNumber'],
            'bank_iban' => $validated['bankIban'] ?? '',
            'bank_currency' => $validated['bankCurrency'] ?? '',

            'doc_national_id_path' => $docNationalIdPath,
            'doc_commercial_registration_path' => $docCommercialRegistrationPath,
            'doc_tax_certificate_path' => $docTaxCertificatePath,
            'doc_additional_paths' => $docAdditionalPaths,
        ]);

        return redirect()
            ->route('tenant.settings.compliance')
            ->with('status', 'Compliance information updated successfully.')
            ->with('status_type', 'success');
    }

    private function policyPages(): array
    {
        $keywords = [
            'return' => 'Return Policy',
            'privacy' => 'Privacy Policy',
            'terms' => 'Terms of Service',
            'shipping' => 'Shipping Policy',
        ];

        $pages = Page::query()->get(['id', 'slug']);

        $result = [];
        foreach ($keywords as $needle => $label) {
            $match = $pages->first(fn (Page $page) => str_contains($page->slug, $needle));
            $result[] = [
                'label' => $label,
                'page' => $match,
            ];
        }

        return $result;
    }
}
