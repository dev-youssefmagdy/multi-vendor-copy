<?php

use App\Services\Tenant\TenantTranslationService;

if (!function_exists('get_data')) {
    /**
     * Read a value from a tenant (or any VirtualColumn-backed) record's
     * `data` JSON column, for raw query-builder rows / arrays that never
     * went through the Eloquent model (and thus never had `data` decoded).
     *
     * Usage: get_data($record, 'data.primary_language_id')
     * The leading "data." segment is optional.
     */
    function get_data(object|array $record, string $key, mixed $default = null): mixed
    {
        $key = str_starts_with($key, 'data.') ? substr($key, 5) : $key;

        $data = is_array($record) ? ($record['data'] ?? null) : ($record->data ?? null);

        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        return data_get($data, $key, $default);
    }
}

if (!function_exists('tt')) {
    /**
     * Tenant-aware translation helper: databaseOverride ?? __($key).
     * Falls back straight to __() when tenancy isn't initialized (central context).
     */
    function tt(string $key, array $replace = [], ?string $locale = null): string
    {
        if (!function_exists('tenancy') || !tenancy()->initialized) {
            return __($key, $replace, $locale);
        }

        $value = app(TenantTranslationService::class)->resolve($key, $locale);

        if ($replace === []) {
            return $value;
        }

        $shorts = [];
        foreach ($replace as $replaceKey => $replaceValue) {
            $shorts[':' . ltrim((string) $replaceKey, ':')] = $replaceValue;
        }

        return strtr($value, $shorts);
    }
}
