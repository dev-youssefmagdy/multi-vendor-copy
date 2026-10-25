<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

trait Translator
{
    public $defaultLocale;

    /** Per-request in-memory locale cache, keyed by tenant ID. Avoids repeated file-cache reads. */
    private static array $defaultLocaleCache = [];

    /**
     * Per-instance memo of lazily loaded translations (used when the
     * `translations` relation was not eager loaded). A WeakMap keeps it off
     * the model itself, so serialization / toArray() output is unchanged and
     * the entry is released together with the model instance.
     */
    private static ?\WeakMap $lazyTranslationsMemo = null;

    /** @see translationsByField() */
    private static ?\WeakMap $translationsByFieldMemo = null;

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $tenantId = (string) (tenancy()->tenant?->getTenantKey() ?? 'central');

        if (!isset(static::$defaultLocaleCache[$tenantId])) {
            $languageModel = $this->translationLanguageModelClass();
            static::$defaultLocaleCache[$tenantId] = Cache::driver('file')->rememberForever("default_language_code_{$tenantId}", function () use ($languageModel) {
                // check if model database table exists to avoid errors during migrations or seeding
                if (!\Schema::hasTable((new $languageModel())->getTable())) {
                    return config('app.fallback_locale', 'en');
                }
                return $languageModel::query()->where('is_default', true)->value('code') ?? config('app.fallback_locale', 'en');
            });
        }

        $this->defaultLocale = static::$defaultLocaleCache[$tenantId];
    }
    public function translations(): MorphMany
    {
        return $this->morphMany($this->translationModelClass(), 'translatable');
    }

    protected function translationModelClass(): string
    {
        return \App\Models\Translation::class;
    }

    protected function translationLanguageModelClass(): string
    {
        return \App\Models\Language::class;
    }

    public function __get($name)
    {
        if (in_array($name, $this->translated ?? [], true)) {
            return $this->t($name) ?? parent::__get($name);
        }

        return parent::__get($name);
    }

    public function t(string $key, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        // $languageModel = $this->translationLanguageModelClass();
        // $tenantId = tenant('id');
        $defaultLocale = $this->defaultLocale;
        //$defaultLocale = $languageModel::query()->where('is_default', true)->value('code') ?? config('app.fallback_locale', 'en');

        $translations = $this->loadedTranslations();

        // Same first-match semantics as scanning $translations in order for
        // (field, locale) and then (field, default locale), but only over the
        // rows of this field (storefront pages call t() thousands of times).
        $candidates = self::translationsByField($translations)[$key] ?? [];

        foreach ($candidates as $translation) {
            if ($translation->language?->code === $locale) {
                return $translation->value;
            }
        }

        foreach ($candidates as $translation) {
            if ($translation->language?->code === $defaultLocale) {
                return $translation->value;
            }
        }

        return null;
    }

    /**
     * Translation rows grouped by `field` (collection order kept), memoized per
     * collection instance and rebuilt whenever its size changes.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function translationsByField($translations): array
    {
        self::$translationsByFieldMemo ??= new \WeakMap();
        $memo = self::$translationsByFieldMemo[$translations] ?? null;
        $count = $translations->count();

        if ($memo !== null && $memo['count'] === $count) {
            return $memo['index'];
        }

        $index = [];
        foreach ($translations as $translation) {
            $field = $translation->field;
            if (is_string($field)) {
                $index[$field][] = $translation;
            }
        }

        self::$translationsByFieldMemo[$translations] = ['count' => $count, 'index' => $index];

        return $index;
    }

    /**
     * The eager-loaded `translations` relation when present; otherwise the
     * translations (with language) are queried once per model instance and
     * reused for every subsequent attribute read on that instance.
     */
    protected function loadedTranslations()
    {
        if ($this->relationLoaded('translations')) {
            return $this->getRelation('translations');
        }

        self::$lazyTranslationsMemo ??= new \WeakMap();
        $key = $this->getKey();
        $memo = self::$lazyTranslationsMemo[$this] ?? null;

        if ($memo === null || $memo['key'] !== $key) {
            $memo = ['key' => $key, 'translations' => $this->translations()->with('language')->get()];
            self::$lazyTranslationsMemo[$this] = $memo;
        }

        return $memo['translations'];
    }

    public function translationValue(string $field, ?string $locale = null): ?string
    {
        return $this->t($field, $locale);
    }

    public function getTranslatedKeys(): array
    {
        $translations = [];

        foreach ($this->translated ?? [] as $field) {
            $translations[$field] = $this->t($field, 'en');
        }

        return [
            'id' => $this->getKey(),
            'translations' => $translations,
        ];
    }

    public function translationsByLocale(array $fields): array
    {
        $languageModel = $this->translationLanguageModelClass();
        $languages = $languageModel::query()
            ->where('is_active', true)
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn((new $languageModel())->getTable(), 'sort_order'),
                fn($q) => $q->orderBy('sort_order')
            )
            ->orderByDesc('is_default')
            ->get();
        $translations = $this->loadedTranslations();

        return $languages->mapWithKeys(function ($language) use ($fields, $translations) {
            $payload = [];

            foreach ($fields as $field) {
                $payload[$field] = $translations
                    ->first(fn($translation) => $translation->field === $field && $translation->language_id === $language->id)?->value ?? '';
            }

            return [$language->code => $payload];
        })->all();
    }

    public function syncTranslations(array $translations): void
    {
        $languageModel = $this->translationLanguageModelClass();
        $languages = $languageModel::query()->pluck('id', 'code');
        $rows = [];

        foreach ($translations as $locale => $fields) {
            $languageId = $languages->get($locale);

            if (!$languageId) {
                continue;
            }

            foreach ($fields as $field => $value) {
                $cleanValue = is_string($value) ? trim($value) : null;

                if ($cleanValue === null || $cleanValue === '') {
                    continue;
                }

                $rows[] = [
                    'language_id' => $languageId,
                    'field' => $field,
                    'value' => $cleanValue,
                ];
            }
        }

        $this->translations()->delete();
        self::$lazyTranslationsMemo?->offsetUnset($this);

        if ($rows === []) {
            return;
        }

        $this->translations()->createMany($rows);
        $this->load('translations.language');
    }
}
