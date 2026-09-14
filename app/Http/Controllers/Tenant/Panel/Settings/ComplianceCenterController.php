<?php

declare(strict_types=1);

// ROUTES (this controller replaces App\Http\Controllers\Tenant\ComplianceCenterController;
// the kept GET/POST `tenant.settings.compliance` / `tenant.settings.compliance.update` /
// `tenant.settings.compliance.cities-by-country` routes need their `use` import repointed
// to this namespace):
// Route::get('/compliance', [ComplianceCenterController::class, 'show'])->name('compliance');
// Route::post('/compliance', [ComplianceCenterController::class, 'update'])->name('compliance.update');
// Route::post('/compliance/validate', [ComplianceCenterController::class, 'validateUpdate'])->name('compliance.validate');
// Route::get('/compliance/cities-by-country/{countryId}', [ComplianceCenterController::class, 'citiesByCountry'])->name('compliance.cities-by-country');
// (all under the existing tenant.permission:settings.account.manage middleware)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Helpers\TenantNavigation;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\UpdateComplianceRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Tenant\Page;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ComplianceCenterController extends PanelController
{
    public function __construct(private readonly TenantPanelService $service)
    {
    }

    public function citiesByCountry(Request $request, int $countryId): JsonResponse
    {
        $cities = City::query()->where('country_id', $countryId)->orderBy('name')->get(['id', 'name']);

        if ($request->query('format') === 'select2') {
            return response()->json([
                'results' => $cities->map(fn (City $city) => ['id' => $city->id, 'text' => $city->name])->all(),
            ]);
        }

        return response()->json($cities);
    }

    public function show(): View
    {
        $tenant = tenant();
        $s = $this->service->complianceSettings();

        $rawPhone = (string) ($s['compliance_phone'] ?? $tenant?->phone ?? '');

        return view('tenant.pages.settings.compliance.index', [
            'businessName' => (string) ($s['compliance_business_name'] ?? ''),
            'storeName' => (string) ($s['compliance_store_name'] ?? $tenant?->shop_name ?? ''),
            'countryId' => $s['compliance_country'] ?? null,
            'cityId' => filled($s['compliance_city'] ?? null) ? (int) $s['compliance_city'] : null,
            'phone' => str_contains($rawPhone, 'object') ? '' : $rawPhone,
            'email' => (string) ($s['compliance_email'] ?? $tenant?->email ?? ''),

            'ownerName' => (string) ($s['compliance_owner_name'] ?? ''),
            'ownerIdNumber' => (string) ($s['compliance_owner_id_number'] ?? ''),
            'ownerDob' => $s['compliance_owner_dob'] ?? null,
            'ownerContact' => (string) ($s['compliance_owner_contact'] ?? ''),

            'companyName' => (string) ($s['compliance_company_name'] ?? ''),
            'registrationNumber' => (string) ($s['compliance_registration_number'] ?? ''),
            'registrationExpiry' => $s['compliance_registration_expiry'] ?? null,
            'vatNumber' => (string) ($s['compliance_vat_number'] ?? ''),
            'registrationDocumentPath' => $s['compliance_registration_document_path'] ?? null,

            'bankName' => (string) ($s['compliance_bank_name'] ?? ''),
            'bankHolderName' => (string) ($s['compliance_bank_holder_name'] ?? ''),
            'bankAccountNumber' => (string) ($s['compliance_bank_account_number'] ?? ''),
            'bankIban' => (string) ($s['compliance_bank_iban'] ?? ''),
            'currencyId' => filled($s['compliance_bank_currency'] ?? null) ? (int) $s['compliance_bank_currency'] : null,

            'docNationalIdPath' => $s['compliance_doc_national_id_path'] ?? null,
            'docCommercialRegistrationPath' => $s['compliance_doc_commercial_registration_path'] ?? null,
            'docTaxCertificatePath' => $s['compliance_doc_tax_certificate_path'] ?? null,
            'docAdditionalPaths' => (array) ($s['compliance_doc_additional_paths'] ?? []),

            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'cities' => filled($s['compliance_country'] ?? null)
                ? City::query()->where('country_id', (int) $s['compliance_country'])->orderBy('name')->get(['id', 'name'])
                : collect(),
            'currencies' => Currency::query()->orderBy('code')->get(['id', 'code', 'name']),
            'policyPages' => $this->policyPages(),
            'completionPercent' => TenantNavigation::complianceCompletionPercent(),
        ]);
    }

    public function update(UpdateComplianceRequest $request): JsonResponse
    {
        $section = $request->section();
        $validated = $request->validated();

        $updates = match ($section) {
            'business' => $this->businessUpdates($validated),
            'owner' => $this->ownerUpdates($validated),
            'company' => $this->companyUpdates($request, $validated),
            'bank' => $this->bankUpdates($validated),
            'documents' => $this->documentsUpdates($request, $validated),
        };

        $this->service->updateCompliance($updates);

        if ($request->input('from') === 'onboarding') {
            return $this->success('Compliance information updated successfully.', [], route('tenant.onboarding', ['tab' => 'setup']));
        }

        return $this->success('Compliance information updated successfully.', [], route('tenant.settings.compliance', ['section' => $section]));
    }

    public function validateUpdate(UpdateComplianceRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    private function businessUpdates(array $validated): array
    {
        return [
            'business_name' => $validated['businessName'],
            'store_name' => $validated['storeName'],
            'country' => $validated['countryId'] ?? null,
            'city' => $validated['cityId'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ];
    }

    private function ownerUpdates(array $validated): array
    {
        return [
            'owner_name' => $validated['ownerName'],
            'owner_id_number' => $validated['ownerIdNumber'],
            'owner_dob' => $validated['ownerDob'] ?? null,
            'owner_contact' => $validated['ownerContact'] ?? '',
        ];
    }

    private function companyUpdates(Request $request, array $validated): array
    {
        $registrationDocumentPath = $request->boolean('remove_registration_document')
            ? null
            : ($request->input('existing_registration_document_path') ?: null);
        if ($request->hasFile('registrationDocumentUpload')) {
            $registrationDocumentPath = $this->service->storeComplianceDocument($request->file('registrationDocumentUpload'));
        }

        return [
            'company_name' => $validated['companyName'] ?? '',
            'registration_number' => $validated['registrationNumber'],
            'registration_expiry' => $validated['registrationExpiry'] ?? null,
            'vat_number' => $validated['vatNumber'] ?? '',
            'registration_document_path' => $registrationDocumentPath,
        ];
    }

    private function bankUpdates(array $validated): array
    {
        return [
            'bank_name' => $validated['bankName'],
            'bank_holder_name' => $validated['bankHolderName'],
            'bank_account_number' => $validated['bankAccountNumber'],
            'bank_iban' => $validated['bankIban'] ?? '',
            'bank_currency' => $validated['currencyId'] ?? '',
        ];
    }

    private function documentsUpdates(Request $request, array $validated): array
    {
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

        return [
            'doc_national_id_path' => $docNationalIdPath,
            'doc_commercial_registration_path' => $docCommercialRegistrationPath,
            'doc_tax_certificate_path' => $docTaxCertificatePath,
            'doc_additional_paths' => $docAdditionalPaths,
        ];
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
