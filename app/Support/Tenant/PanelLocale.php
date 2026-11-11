<?php

declare(strict_types=1);

namespace App\Support\Tenant;

/**
 * UI language of the vendor panel (/admin). Independent of the storefront
 * language: the vendor picks it from the header language switcher and it is
 * remembered in a long-lived cookie. Only locales that ship panel
 * translations (lang/{code}.json) are offered.
 */
final class PanelLocale
{
    public const COOKIE = 'tenant_panel_locale';

    /** code => native name */
    public const SUPPORTED = [
        'en' => 'English',
        'ar' => 'العربية',
        'fr' => 'Français',
    ];

    private const RTL = ['ar', 'he', 'fa', 'ur'];

    public static function isSupported(?string $code): bool
    {
        return is_string($code) && array_key_exists($code, self::SUPPORTED);
    }

    public static function isRtl(string $code): bool
    {
        return in_array($code, self::RTL, true);
    }
}
