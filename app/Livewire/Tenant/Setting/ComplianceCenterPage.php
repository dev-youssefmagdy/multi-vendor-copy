<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Country;
use App\Models\Tenant\Page;
use App\Services\Tenant\TenantPanelService;
use Livewire\WithFileUploads;

class ComplianceCenterPage extends ContentPage
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.compliance-center';
    }

    // Business Info
    public string $businessName = '';
    public string $storeName = '';
    public ?int $countryId = null;
    public string $city = '';
    public string $phone = '';
    public string $email = '';

    // Owner Info
    public string $ownerName = '';
    public string $ownerIdNumber = '';
    public ?string $ownerDob = null;
    public string $ownerContact = '';

    // Company Info
    public string $companyName = '';
    public string $registrationNumber = '';
    public ?string $registrationExpiry = null;
    public string $vatNumber = '';
    public ?string $registrationDocumentPath = null;
    public $registrationDocumentUpload = null;

    // Bank Account
    public string $bankName = '';
    public string $bankHolderName = '';
    public string $bankAccountNumber = '';
    public string $bankIban = '';
    public string $bankCurrency = '';

    // Verification Documents
    public ?string $docNationalIdPath = null;
    public ?string $docCommercialRegistrationPath = null;
    public ?string $docTaxCertificatePath = null;
    public array $docAdditionalPaths = [];
    public $docNationalIdUpload = null;
    public $docCommercialRegistrationUpload = null;
    public $docTaxCertificateUpload = null;
    public $docAdditionalUploads = [];

    public function mount(): void
    {
        $tenant = tenant();

        $this->businessName = (string) data_get($tenant, 'data.compliance_business_name', '');
        $this->storeName = (string) data_get($tenant, 'data.compliance_store_name', data_get($tenant, 'data.shop_name', ''));
        $this->countryId = data_get($tenant, 'data.compliance_country') ?: null;
        $this->city = (string) data_get($tenant, 'data.compliance_city', '');
        $this->phone = (string) data_get($tenant, 'data.compliance_phone', $tenant?->phone ?? '');
        $this->email = (string) data_get($tenant, 'data.compliance_email', $tenant?->email ?? '');

        $this->ownerName = (string) data_get($tenant, 'data.compliance_owner_name', '');
        $this->ownerIdNumber = (string) data_get($tenant, 'data.compliance_owner_id_number', '');
        $this->ownerDob = data_get($tenant, 'data.compliance_owner_dob') ?: null;
        $this->ownerContact = (string) data_get($tenant, 'data.compliance_owner_contact', '');

        $this->companyName = (string) data_get($tenant, 'data.compliance_company_name', '');
        $this->registrationNumber = (string) data_get($tenant, 'data.compliance_registration_number', '');
        $this->registrationExpiry = data_get($tenant, 'data.compliance_registration_expiry') ?: null;
        $this->vatNumber = (string) data_get($tenant, 'data.compliance_vat_number', '');
        $this->registrationDocumentPath = data_get($tenant, 'data.compliance_registration_document_path') ?: null;

        $this->bankName = (string) data_get($tenant, 'data.compliance_bank_name', '');
        $this->bankHolderName = (string) data_get($tenant, 'data.compliance_bank_holder_name', '');
        $this->bankAccountNumber = (string) data_get($tenant, 'data.compliance_bank_account_number', '');
        $this->bankIban = (string) data_get($tenant, 'data.compliance_bank_iban', '');
        $this->bankCurrency = (string) data_get($tenant, 'data.compliance_bank_currency', '');

        $this->docNationalIdPath = data_get($tenant, 'data.compliance_doc_national_id_path') ?: null;
        $this->docCommercialRegistrationPath = data_get($tenant, 'data.compliance_doc_commercial_registration_path') ?: null;
        $this->docTaxCertificatePath = data_get($tenant, 'data.compliance_doc_tax_certificate_path') ?: null;
        $this->docAdditionalPaths = (array) data_get($tenant, 'data.compliance_doc_additional_paths', []);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Compliance Center',
            'badge' => 'Compliance',
            'description' => 'Business, owner, company, banking, and verification details required to keep your store in good standing.',
        ];
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'policyPages' => $this->policyPages(),
            'completionPercent' => \App\Helpers\TenantNavigation::complianceCompletionPercent(tenant()),
        ]);
    }

    protected function policyPages(): array
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
            $match = $pages->first(fn(Page $page) => str_contains($page->slug, $needle));
            $result[] = [
                'label' => $label,
                'page' => $match,
            ];
        }

        return $result;
    }

    public function removeDocument(string $field): void
    {
        $property = match ($field) {
            'registration' => 'registrationDocumentPath',
            'national_id' => 'docNationalIdPath',
            'commercial_registration' => 'docCommercialRegistrationPath',
            'tax_certificate' => 'docTaxCertificatePath',
            default => null,
        };

        if ($property) {
            $this->{$property} = null;
        }
    }

    public function removeAdditionalDocument(int $index): void
    {
        unset($this->docAdditionalPaths[$index]);
        $this->docAdditionalPaths = array_values($this->docAdditionalPaths);
    }

    public function save(TenantPanelService $service): void
    {
        $validated = $this->validate([
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

            'bankName' => ['required', 'string', 'max:255'],
            'bankHolderName' => ['required', 'string', 'max:255'],
            'bankAccountNumber' => ['required', 'string', 'max:100'],
            'bankIban' => ['nullable', 'string', 'max:100'],
            'bankCurrency' => ['nullable', 'string', 'max:10'],

            'docNationalIdUpload' => ['nullable', 'file', 'max:5120'],
            'docCommercialRegistrationUpload' => ['nullable', 'file', 'max:5120'],
            'docTaxCertificateUpload' => ['nullable', 'file', 'max:5120'],
            'docAdditionalUploads.*' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($this->registrationDocumentUpload) {
            $this->registrationDocumentPath = $service->storeComplianceDocument($this->registrationDocumentUpload);
        }
        if ($this->docNationalIdUpload) {
            $this->docNationalIdPath = $service->storeComplianceDocument($this->docNationalIdUpload);
        }
        if ($this->docCommercialRegistrationUpload) {
            $this->docCommercialRegistrationPath = $service->storeComplianceDocument($this->docCommercialRegistrationUpload);
        }
        if ($this->docTaxCertificateUpload) {
            $this->docTaxCertificatePath = $service->storeComplianceDocument($this->docTaxCertificateUpload);
        }
        foreach ($this->docAdditionalUploads as $upload) {
            if ($upload) {
                $this->docAdditionalPaths[] = $service->storeComplianceDocument($upload);
            }
        }

        $service->updateCompliance([
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
            'registration_document_path' => $this->registrationDocumentPath,

            'bank_name' => $validated['bankName'],
            'bank_holder_name' => $validated['bankHolderName'],
            'bank_account_number' => $validated['bankAccountNumber'],
            'bank_iban' => $validated['bankIban'] ?? '',
            'bank_currency' => $validated['bankCurrency'] ?? '',

            'doc_national_id_path' => $this->docNationalIdPath,
            'doc_commercial_registration_path' => $this->docCommercialRegistrationPath,
            'doc_tax_certificate_path' => $this->docTaxCertificatePath,
            'doc_additional_paths' => $this->docAdditionalPaths,
        ]);

        $this->registrationDocumentUpload = null;
        $this->docNationalIdUpload = null;
        $this->docCommercialRegistrationUpload = null;
        $this->docTaxCertificateUpload = null;
        $this->docAdditionalUploads = [];

        $this->toast('Compliance information updated successfully.');
    }
}
