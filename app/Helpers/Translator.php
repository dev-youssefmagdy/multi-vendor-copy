<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

trait Translator
{
    public $defaultLocale;

    /** Per-request in-memory locale cache, keyed by tenant ID. Avoids repeated file-cache reads. */
    private static array $defaultLocaleCache = [];

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $tenantId = (string) (tenant('id') ?? 'central');

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

        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->with('language')->get();

        $match = $translations->first(function ($translation) use ($key, $locale) {
            return $translation->field === $key && $translation->language?->code === $locale;
        });

        if ($match) {
            return $match->value;
        }

        return $translations->first(function ($translation) use ($key, $defaultLocale) {
            return $translation->field === $key && $translation->language?->code === $defaultLocale;
        })?->value;
    }

    public function translationValue(string $field, ?string $locale = null): ?string
    {
        return $this->t($field, $locale);
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
        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->with('language')->get();

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

        if ($rows === []) {
            return;
        }

        $this->translations()->createMany($rows);
        $this->load('translations.language');
    }
}
