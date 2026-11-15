<?php

namespace App\Support;

use App\Models\Affiliate;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Central-side, read-only view of a vendor's identity and payout details.
 *
 * The vendor fills these in the Compliance Center, which stores them in the vendor's own
 * `settings` table (group = compliance), so central pages cannot query them with a join.
 * This class switches into the vendor's database once, reads the compliance rows,
 * resolves country, city and currency ids to names, and caches the result for the request.
 *
 * Used by: central Edit Tenant ("Merchant Info" tab), Compliance detail, Affiliate Payouts (details modal + CSV export).
 */
final class VendorProfile
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /** Fields shown/exported, in display order. */
    public const BANK_FIELDS = ['bank_name', 'bank_holder_name', 'bank_account_number', 'bank_iban', 'bank_currency'];

    /**
     * @return array<string, mixed> normalized profile; every key is always present ('' when unknown)
     */
    public static function forTenant(Tenant|string|null $tenant): array
    {
        if (is_string($tenant)) {
            $tenant = Tenant::query()->find($tenant);
        }

        if (!$tenant) {
            return self::blank();
        }

        $key = (string) $tenant->getKey();

        return self::$cache[$key] ??= self::build($tenant);
    }

    /**
     * Profile for the person behind an affiliate. Vendor affiliates (type = tenant) get their
     * store's compliance profile; external affiliates use the payout details on their own record.
     */
    public static function forAffiliate(?Affiliate $affiliate): array
    {
        if (!$affiliate) {
            return self::blank();
        }

        if ($affiliate->isTenant() && $affiliate->tenant_id) {
            $profile = self::forTenant($affiliate->tenant_id);
            $profile['owner_name'] = $profile['owner_name'] ?: (string) $affiliate->name;
            $profile['paypal_email'] = (string) ($affiliate->paypal_email ?? '');

            return $profile;
        }

        return array_merge(self::blank(), [
            'is_vendor' => false,
            'owner_name' => (string) $affiliate->name,
            'email' => (string) $affiliate->email,
            'bank_name' => (string) ($affiliate->bank_name ?? ''),
            'bank_account_number' => (string) ($affiliate->bank_account ?? ''),
            'bank_iban' => (string) ($affiliate->bank_iban ?? ''),
            'bank_holder_name' => (string) $affiliate->name,
            'paypal_email' => (string) ($affiliate->paypal_email ?? ''),
        ]);
    }

    /** Display name for a person: owner name, else business name, else store name. */
    public static function displayName(array $profile, string $fallback = ''): string
    {
        return ($profile['owner_name'] ?? '') ?: (($profile['business_name'] ?? '') ?: (($profile['store_name'] ?? '') ?: $fallback));
    }

    public static function hasBankDetails(array $profile): bool
    {
        return filled($profile['bank_name'] ?? null) || filled($profile['bank_iban'] ?? null) || filled($profile['bank_account_number'] ?? null);
    }

    /** One-line bank summary for tables/CSV, e.g. "CIH Bank · Issam K. · IBAN MA64… · MAD". */
    public static function bankSummary(array $profile): string
    {
        $profile = array_merge(self::blank(), $profile);

        return collect([
            $profile['bank_name'],
            $profile['bank_holder_name'],
            $profile['bank_iban'] ? 'IBAN ' . $profile['bank_iban'] : '',
            $profile['bank_account_number'] ? 'Acc ' . $profile['bank_account_number'] : '',
            $profile['bank_currency'],
        ])->filter(fn ($v) => filled($v))->implode(' · ');
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    private static function blank(): array
    {
        return [
            'is_vendor' => false,
            'tenant_id' => '',
            'store_name' => '',
            'store_url' => '',
            'account_type' => '',
            'business_name' => '',
            'owner_name' => '',
            'owner_id_number' => '',
            'owner_dob' => '',
            'owner_contact' => '',
            'email' => '',
            'phone' => '',
            'country' => '',
            'city' => '',
            'company_name' => '',
            'registration_number' => '',
            'registration_expiry' => '',
            'vat_number' => '',
            'bank_name' => '',
            'bank_holder_name' => '',
            'bank_account_number' => '',
            'bank_iban' => '',
            'bank_currency' => '',
            'paypal_email' => '',
            'compliance_status' => '',
            'documents' => [],
        ];
    }

    private static function build(Tenant $tenant): array
    {
        $settings = [];

        try {
            $settings = $tenant->run(fn () => Setting::query()
                ->where('group', 'compliance')
                ->pluck('value', 'name')
                ->all());
        } catch (\Throwable $e) {
            // A missing or broken vendor database must not break central pages: fall back to central data.
            Log::warning('VendorProfile: could not read compliance settings', ['tenant' => $tenant->getKey(), 'error' => $e->getMessage()]);
        }

        $s = fn (string $name): string => trim((string) ($settings['compliance_' . $name] ?? ''));
        $rawPhone = (string) ($tenant->phone ?? '');
        $domain = $tenant->domains()->value('domain');

        $documents = array_filter([
            'Registration document' => $s('registration_document_path'),
            'National ID' => $s('doc_national_id_path'),
            'Commercial registration' => $s('doc_commercial_registration_path'),
            'Tax certificate' => $s('doc_tax_certificate_path'),
        ]);
        foreach ((array) (json_decode((string) ($settings['compliance_doc_additional_paths'] ?? '[]'), true) ?: []) as $i => $path) {
            if (filled($path)) {
                $documents['Additional document ' . ($i + 1)] = (string) $path;
            }
        }

        return array_merge(self::blank(), [
            'is_vendor' => true,
            'tenant_id' => (string) $tenant->getKey(),
            'store_name' => $s('store_name') ?: (string) ($tenant->name ?? ''),
            'store_url' => $domain ? 'https://' . $domain : '',
            'account_type' => (string) ($tenant->account_type ?? '') === 'company' ? 'Company' : 'Individual',
            'business_name' => $s('business_name'),
            'owner_name' => $s('owner_name'),
            'owner_id_number' => $s('owner_id_number'),
            'owner_dob' => $s('owner_dob'),
            'owner_contact' => $s('owner_contact'),
            'email' => $s('email') ?: (string) ($tenant->email ?? ''),
            'phone' => $s('phone') ?: (str_contains($rawPhone, 'object') ? '' : $rawPhone),
            'country' => self::lookup(Country::class, $s('country'), 'name'),
            'city' => self::lookup(City::class, $s('city'), 'name'),
            'company_name' => $s('company_name'),
            'registration_number' => $s('registration_number'),
            'registration_expiry' => $s('registration_expiry'),
            'vat_number' => $s('vat_number'),
            'bank_name' => $s('bank_name'),
            'bank_holder_name' => $s('bank_holder_name'),
            'bank_account_number' => $s('bank_account_number'),
            'bank_iban' => $s('bank_iban'),
            'bank_currency' => self::lookup(Currency::class, $s('bank_currency'), 'code'),
            'compliance_status' => (string) ($tenant->compliance_status ?: 'pending'),
            'documents' => $documents,
        ]);
    }

    /** Resolve a stored id to a readable column; non-numeric values (legacy free text) are returned as-is. */
    private static function lookup(string $model, string $value, string $column): string
    {
        if ($value === '' || !ctype_digit($value)) {
            return $value;
        }

        try {
            $model = $model::query()->find((int) $value);
            $label = $model?->{$column};

            return is_string($label) && $label !== '' ? $label : $value;
        } catch (\Throwable) {
            return $value;
        }
    }
}
