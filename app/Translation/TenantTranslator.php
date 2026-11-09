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

    /** @var array<string, array<string, true>> Overrides already checked against the lang files this request. */
    protected static array $verifiedOverrides = [];

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

        $override = static::$overridesByLocale[$locale][$key] ?? null;

        if ($override === null || isset(static::$verifiedOverrides[$locale][$key])) {
            return $override;
        }

        if ($this->shadowsRealTranslation($key, (string) $override, $locale)) {
            unset(static::$overridesByLocale[$locale][$key]);

            return null;
        }

        static::$verifiedOverrides[$locale][$key] = true;

        return $override;
    }

    /**
     * True when $value is not a real translation for a non-source locale: it
     * is blank, or just repeats the English source (the key or its en lang
     * file value), as left behind by a failed AI call or an unedited save.
     */
    public function isUntranslatedValue(string $key, string $value, string $locale): bool
    {
        $sourceLocale = $this->getFallback() ?: 'en';

        if ($locale === $sourceLocale) {
            return false;
        }

        $value = trim($value);

        return $value === ''
            || $value === trim($key)
            || $value === trim((string) $this->fileLine($key, $sourceLocale));
    }

    protected function shadowsRealTranslation(string $key, string $override, string $locale): bool
    {
        if (!$this->isUntranslatedValue($key, $override, $locale)) {
            return false;
        }

        $fileLine = trim((string) $this->fileLine($key, $locale));

        return $fileLine !== '' && $fileLine !== trim($override);
    }

    /** The raw lang file line for this exact locale, or null when the file has none. */
    protected function fileLine(string $key, string $locale): ?string
    {
        $line = parent::get($key, [], $locale, false);

        return is_string($line) && $line !== $key ? $line : null;
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
        static::$verifiedOverrides = [];
    }
}
