<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Language;
use App\Models\Tenant\TranslationOverride;
use App\Repositories\AppSettingRepository;
use App\Services\OpenAiTranslationService;
use App\Services\TranslationFileService;
use App\Translation\TenantTranslator;
use RuntimeException;

class TenantTranslationService
{
    /** Per-request memoization of the locked-keys list (rarely changes). */
    private static ?array $lockedKeysCache = null;

    public function __construct(
        protected TranslationFileService $translationFiles,
        protected StoreContextService $storeContext,
    ) {
    }

    /**
     * Resolve a static UI-string key using: tenant DB override ?? __($key).
     *
     * The override lookup itself now happens inside App\Translation\TenantTranslator
     * (the app's 'translator' binding), so plain __($key, [], $locale) already
     * applies tenant overrides everywhere — this method is kept as a thin,
     * explicitly-named wrapper for call sites that prefer the `tt()`-style API.
     */
    public function resolve(string $key, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) __($key, [], $locale);
    }

    /**
     * All known static translation keys merged with this tenant's overrides
     * for the given language code, shaped for the editor UI.
     */
    public function keysForLocale(string $localeCode): array
    {
        $language = Language::query()->where('code', $localeCode)->first();
        $lockedKeys = $this->lockedKeys();

        $defaults = [];
        foreach ($this->translationFiles->resources() as $resource) {
            $payload = $this->translationFiles->read((string) $resource['key']);

            foreach ($payload['rows'] ?? [] as $row) {
                $key = (string) $row['key'];
                $defaults[$key] = (string) data_get($row, 'values.' . config('app.fallback_locale', 'en'), '');
            }
        }

        $overrides = [];
        if ($language) {
            $overrides = TranslationOverride::query()
                ->where('language_id', $language->id)
                ->pluck('value', 'key')
                ->all();
        }

        $rows = [];
        foreach ($defaults as $key => $default) {
            $override = $overrides[$key] ?? null;

            $rows[] = [
                'key' => $key,
                'default' => $default,
                'override' => $override,
                'value' => $override ?? $default,
                'locked' => in_array($key, $lockedKeys, true),
            ];
        }

        usort($rows, fn(array $a, array $b) => $a['key'] <=> $b['key']);

        return $rows;
    }

    public function saveOverride(string $languageId, string $key, string $value): void
    {
        if (in_array($key, $this->lockedKeys(), true)) {
            throw new RuntimeException("The translation key \"{$key}\" is locked by the marketplace admin and cannot be edited.");
        }

        TranslationOverride::query()->updateOrCreate(
            ['language_id' => $languageId, 'key_hash' => hash('sha256', $key)],
            ['key' => $key, 'value' => $value],
        );

        TenantTranslator::flushCache();
    }

    public function translateKeyWithAi(Language $language, string $key, OpenAiTranslationService $ai): string
    {
        if (in_array($key, $this->lockedKeys(), true)) {
            throw new RuntimeException("The translation key \"{$key}\" is locked by the marketplace admin and cannot be edited.");
        }

        $default = (string) __($key, [], config('app.fallback_locale', 'en'));

        if (trim($default) === '') {
            return '';
        }

        $translated = $ai->translateBatch(
            [$default],
            config('app.fallback_locale', 'en'),
            strtolower((string) $language->code),
            $language->name,
            $this->storeContext->build($language->name),
        );

        $value = trim((string) ($translated[0] ?? $default));

        $this->saveOverride((string) $language->id, $key, $value);

        return $value;
    }

    /**
     * Batch AI-translate a list of keys into the given language. Returns the
     * number of keys actually translated (locked keys are skipped).
     */
    public function translateKeysWithAi(Language $language, array $keys, OpenAiTranslationService $ai): int
    {
        $locked = $this->lockedKeys();
        $keys = array_values(array_diff(array_unique($keys), $locked));

        if ($keys === []) {
            return 0;
        }

        $defaultLocale = config('app.fallback_locale', 'en');
        $defaults = [];

        foreach ($keys as $key) {
            $value = trim((string) __($key, [], $defaultLocale));

            if ($value !== '') {
                $defaults[$key] = $value;
            }
        }

        if ($defaults === []) {
            return 0;
        }

        $translated = $ai->translateBatch(
            array_values($defaults),
            $defaultLocale,
            strtolower((string) $language->code),
            $language->name,
            $this->storeContext->build($language->name),
        );

        $count = 0;
        foreach (array_keys($defaults) as $index => $key) {
            $value = trim((string) ($translated[$index] ?? $defaults[$key]));
            $this->saveOverride((string) $language->id, $key, $value);
            $count++;
        }

        return $count;
    }

    /**
     * The admin-locked key list, read from the central AppSetting store.
     * Read-only from the tenant side.
     */
    public function lockedKeys(): array
    {
        if (self::$lockedKeysCache !== null) {
            return self::$lockedKeysCache;
        }

        $raw = tenancy()->central(function () {
            return app(AppSettingRepository::class)
                ->getByKeys(['translation_locked_keys'])
                ->get('translation_locked_keys')?->value;
        });

        $decoded = json_decode((string) $raw, true);

        return self::$lockedKeysCache = is_array($decoded) ? $decoded : [];
    }
}
