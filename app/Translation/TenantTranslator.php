<?php

namespace App\Translation;

use App\Models\Tenant\Language;
use App\Models\Tenant\TranslationOverride;
use Illuminate\Translation\Translator;

/**
 * Drop-in replacement for Laravel's default translator that makes every
 * __()/@lang() call resolve tenant DB overrides before falling back to the
 * lang files: effectively `databaseTranslation ?? __('default.translation')`.
 *
 * This is what makes the AI/manual translation editor (Settings > Translations)
 * actually take effect across the storefront and tenant panel, without every
 * blade view needing to change how it calls translations.
 */
class TenantTranslator extends Translator
{
    /** @var array<string, array<string, string>> Per-request cache of overrides, keyed by locale. */
    protected static array $overridesByLocale = [];

    public function get($key, $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->getLocale();

        $override = $this->overrideFor((string) $key, (string) $locale);

        if ($override !== null) {
            return $this->makeReplacements($override, $replace);
        }

        return parent::get($key, $replace, $locale, $fallback);
    }

    protected function overrideFor(string $key, string $locale): ?string
    {
        if (!function_exists('tenancy') || !tenancy()->initialized) {
            return null;
        }

        if (!array_key_exists($locale, static::$overridesByLocale)) {
            static::$overridesByLocale[$locale] = $this->loadOverrides($locale);
        }

        return static::$overridesByLocale[$locale][$key] ?? null;
    }

    /** @return array<string, string> */
    protected function loadOverrides(string $locale): array
    {
        $languageId = Language::query()->where('code', $locale)->value('id');

        if (!$languageId) {
            return [];
        }

        return TranslationOverride::query()
            ->where('language_id', $languageId)
            ->pluck('value', 'key')
            ->all();
    }

    /**
     * Clear the per-request override cache. Must run whenever an override is
     * saved (so the change is visible immediately) and whenever tenancy ends
     * or switches tenants (queue workers process multiple tenants per process).
     */
    public static function flushCache(): void
    {
        static::$overridesByLocale = [];
    }
}
