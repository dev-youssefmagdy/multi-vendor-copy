<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class UpdateComplianceRequest extends TenantFormRequest
{
    private const SECTIONS = ['business', 'owner', 'company', 'bank', 'documents'];

    public function section(): string
    {
        $section = $this->input('section');

        return in_array($section, self::SECTIONS, true) ? $section : 'business';
    }

    public function rules(): array
    {
        return match ($this->section()) {
            'owner' => [
                'ownerName' => ['required', 'string', 'max:255'],
                'ownerIdNumber' => ['required', 'string', 'max:100'],
                'ownerDob' => ['nullable', 'date'],
                'ownerContact' => ['nullable', 'string', 'max:100'],
            ],
            'company' => [
                'companyName' => ['nullable', 'string', 'max:255'],
                'registrationNumber' => ['required', 'string', 'max:100'],
                'registrationExpiry' => ['nullable', 'date'],
                'vatNumber' => ['nullable', 'string', 'max:100'],
                'registrationDocumentUpload' => ['nullable', 'file', 'max:5120'],
                'existing_registration_document_path' => ['nullable', 'string'],
                'remove_registration_document' => ['nullable', 'boolean'],
            ],
            'bank' => [
                'bankName' => ['required', 'string', 'max:255'],
                'bankHolderName' => ['required', 'string', 'max:255'],
                'bankAccountNumber' => ['required', 'string', 'max:100'],
                'bankIban' => ['nullable', 'string', 'max:100'],
                'currencyId' => ['nullable', 'integer', 'exists:'.config('tenancy.database.central_connection').'.currencies,id'],
            ],
            'documents' => [
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
            ],
            default => [
                'businessName' => ['required', 'string', 'max:255'],
                'storeName' => ['required', 'string', 'max:255'],
                // Country lives only in the central DB (Country::class uses CentralConnection),
                // but the default connection inside a tenant request is `tenant` — qualify
                // the rule with the central connection explicitly.
                'countryId' => ['nullable', 'integer', 'exists:'.config('tenancy.database.central_connection').'.countries,id'],
                'cityId' => ['required', 'integer', 'exists:'.config('tenancy.database.central_connection').'.cities,id'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
            ],
        };
    }

    public function attributes(): array
    {
        return [
            'businessName' => 'business name',
            'storeName' => 'store name',
            'countryId' => 'country',
            'cityId' => 'city',
            'ownerName' => 'owner name',
            'ownerIdNumber' => 'owner id number',
            'ownerDob' => 'date of birth',
            'ownerContact' => 'owner contact',
            'companyName' => 'company name',
            'registrationNumber' => 'registration number',
            'registrationExpiry' => 'registration expiry',
            'vatNumber' => 'VAT number',
            'bankName' => 'bank name',
            'bankHolderName' => 'account holder name',
            'bankAccountNumber' => 'account number',
            'bankIban' => 'IBAN',
            'currencyId' => 'currency',
        ];
    }
}
