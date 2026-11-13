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
                'accountType' => ['required', 'in:individual,company'],
                // Individuals have no separate business name (the field is hidden for them).
                'businessName' => ['required_if:accountType,company', 'nullable', 'string', 'max:255'],
                'storeName' => ['required', 'string', 'max:255'],
                // Country lives only in the central DB (Country::class uses CentralConnection),
                // but the default connection inside a tenant request is `tenant` — qualify
                // the rule with the central connection explicitly.
                'countryId' => ['required', 'integer', 'exists:'.config('tenancy.database.central_connection').'.countries,id'],
                'cityId' => ['nullable', 'integer', 'exists:'.config('tenancy.database.central_connection').'.cities,id'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
            ],
        };
    }

    public function attributes(): array
    {
        return [
            'accountType' => __('account type'),
            'businessName' => __('business name'),
            'storeName' => __('store name'),
            'countryId' => __('country'),
            'cityId' => __('city'),
            'ownerName' => __('owner name'),
            'ownerIdNumber' => __('owner id number'),
            'ownerDob' => __('date of birth'),
            'ownerContact' => __('owner contact'),
            'companyName' => __('company name'),
            'registrationNumber' => __('registration number'),
            'registrationExpiry' => __('registration expiry'),
            'vatNumber' => __('VAT number'),
            'bankName' => __('bank name'),
            'bankHolderName' => __('account holder name'),
            'bankAccountNumber' => __('account number'),
            'bankIban' => __('IBAN'),
            'currencyId' => __('currency'),
        ];
    }
}
