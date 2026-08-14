<?php

use App\Services\Tenant\TenantTranslationService;

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
