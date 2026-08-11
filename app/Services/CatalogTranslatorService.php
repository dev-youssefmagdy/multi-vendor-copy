<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\OpenAiTranslationService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CatalogTranslatorService
{
    protected const MODEL_FIELDS = [
        Catalog::class => ['name'],
        Category::class => ['name', 'description', 'meta_keywords', 'meta_description'],
        Product::class => ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'],
        Variation::class => ['name', 'description'],
        VariationOption::class => ['name'],
    ];

    public function __construct(
        protected OpenAiTranslationService $openAi,
        protected TranslationFileService $translationFiles,
        protected CentralCatalogTenantSyncService $tenantSyncService,
    ) {
    }

    public function translateNewLanguage(Language $language, ?string $sourceLocale = null): void
    {
        if (!$this->openAi->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $targetLocale = strtolower((string) $language->code);
        $sourceLocale = $this->resolveSourceLocale($targetLocale, $sourceLocale);

        if ($sourceLocale === null || $sourceLocale === $targetLocale) {
            $language->forceFill(['translation_progress' => 100])->save();
            return;
        }

        // Weight: resources = 20%, catalog models = 70%, tenant sync = 10%
        $this->translateLanguageResources($sourceLocale, $targetLocale, $language->name);
        $language->forceFill(['translation_progress' => 20])->save();

        $this->translateCatalogModels($sourceLocale, $targetLocale, $language->name);

        $language->forceFill(['translation_progress' => 90])->save();
        $this->tenantSyncService->syncAllTenants(['languages', 'categories', 'products']);
        $language->forceFill(['translation_progress' => 100])->save();
    }

    public function copyNewLanguage(Language $language, ?string $sourceLocale = null): void
    {
        $targetLocale = strtolower((string) $language->code);
        $sourceLocale = $this->resolveSourceLocale($targetLocale, $sourceLocale);

        if ($sourceLocale === null || $sourceLocale === $targetLocale) {
            $language->forceFill(['translation_progress' => 100])->save();
            return;
        }

        $this->copyLanguageResources($sourceLocale, $targetLocale);
        $this->copyCatalogModels($sourceLocale, $targetLocale);

        $this->tenantSyncService->syncAllTenants(['languages', 'categories', 'products']);
        $language->forceFill(['translation_progress' => 100])->save();
    }

    protected function copyLanguageResources(string $sourceLocale, string $targetLocale): void
    {
        foreach ($this->translationFiles->resources() as $resource) {
            $payload = $this->translationFiles->read((string) $resource['key']);
            $rows = $payload['rows'] ?? [];
            $modified = false;

            foreach ($rows as $index => $row) {
                $sourceValue = trim((string) data_get($row, "values.{$sourceLocale}", ''));
                $targetValue = trim((string) data_get($row, "values.{$targetLocale}", ''));

                if ($sourceValue === '' || $targetValue !== '') {
                    continue;
                }

                $rows[$index]['values'][$targetLocale] = $sourceValue;
                $modified = true;
            }

            if ($modified) {
                $this->translationFiles->save((string) $resource['key'], $rows);
            }
        }
    }

    protected function copyCatalogModels(string $sourceLocale, string $targetLocale): void
    {
        foreach (self::MODEL_FIELDS as $modelClass => $fields) {
            $modelClass::query()
                ->with('translations.language')
                ->chunkById(50, function ($models) use ($fields, $sourceLocale, $targetLocale) {
                    foreach ($models as $model) {
                        $translations = $this->existingTranslations($model);
                        $modified = false;

                        foreach ($fields as $field) {
                            $sourceValue = trim((string) ($translations[$sourceLocale][$field] ?? ''));
                            $targetValue = trim((string) ($translations[$targetLocale][$field] ?? ''));

                            if ($sourceValue === '' || $targetValue !== '') {
                                continue;
                            }

                            $translations[$targetLocale][$field] = $sourceValue;
                            $modified = true;
                        }

                        if ($modified) {
                            $model->syncTranslations($translations);
                        }
                    }
                });
        }
    }

    protected function translateLanguageResources(string $sourceLocale, string $targetLocale, string $targetLanguage): void
    {
        foreach ($this->translationFiles->resources() as $resource) {
            $payload = $this->translationFiles->read((string) $resource['key']);
            $rows = $payload['rows'] ?? [];
            $pending = [];

            foreach ($rows as $index => $row) {
                $sourceValue = trim((string) data_get($row, "values.{$sourceLocale}", ''));
                $targetValue = trim((string) data_get($row, "values.{$targetLocale}", ''));

                if ($sourceValue === '' || !$this->shouldTranslate($sourceValue, $targetValue)) {
                    continue;
                }

                $pending[] = [
                    'index' => $index,
                    'text' => $sourceValue,
                ];
            }

            if ($pending === []) {
                continue;
            }

            $translations = $this->openAi->translateBatch(
                array_map(fn(array $item) => $item['text'], $pending),
                $sourceLocale,
                $targetLocale,
                $targetLanguage,
                'Laravel language resource: ' . ($resource['label'] ?? $resource['key']),
            );

            foreach ($pending as $offset => $item) {
                $rows[$item['index']]['values'][$targetLocale] = trim((string) ($translations[$offset] ?? $item['text']));
            }

            $this->translationFiles->save((string) $resource['key'], $rows);
        }
    }

    protected function translateCatalogModels(string $sourceLocale, string $targetLocale, string $targetLanguage): void
    {
        foreach (self::MODEL_FIELDS as $modelClass => $fields) {
            $modelClass::query()
                ->with('translations.language')
                ->chunkById(50, function ($models) use ($fields, $modelClass, $sourceLocale, $targetLocale, $targetLanguage) {
                    $pending = [];
                    $states = [];

                    foreach ($models as $model) {
                        $translations = $this->existingTranslations($model);
                        $modelKey = $this->modelKey($model);

                        foreach ($fields as $field) {
                            $sourceValue = trim((string) ($translations[$sourceLocale][$field] ?? ''));
                            $targetValue = trim((string) ($translations[$targetLocale][$field] ?? ''));

                            if ($sourceValue === '' || !$this->shouldTranslate($sourceValue, $targetValue)) {
                                continue;
                            }

                            $states[$modelKey] ??= [
                                'model' => $model,
                                'translations' => $translations,
                            ];

                            $pending[] = [
                                'model_key' => $modelKey,
                                'field' => $field,
                                'text' => $sourceValue,
                            ];
                        }
                    }

                    if ($pending === []) {
                        return;
                    }

                    $translated = $this->openAi->translateBatch(
                        array_map(fn(array $item) => $item['text'], $pending),
                        $sourceLocale,
                        $targetLocale,
                        $targetLanguage,
                        'Central catalog model: ' . class_basename($modelClass),
                    );

                    foreach ($pending as $offset => $item) {
                        $states[$item['model_key']]['translations'][$targetLocale][$item['field']] = trim((string) ($translated[$offset] ?? $item['text']));
                    }

                    foreach ($states as $state) {
                        /** @var \Illuminate\Database\Eloquent\Model $model */
                        $model = $state['model'];
                        $model->syncTranslations($state['translations']);
                    }
                });
        }
    }

    protected function existingTranslations(Model $model): array
    {
        $model->loadMissing('translations.language');
        $translations = [];

        foreach ($model->translations as $translation) {
            $locale = $translation->language?->code;

            if (!is_string($locale) || $locale === '') {
                continue;
            }

            $translations[$locale][$translation->field] = (string) $translation->value;
        }

        return $translations;
    }

    protected function resolveSourceLocale(string $targetLocale, ?string $sourceLocale = null): ?string
    {
        $candidates = array_filter([
            $sourceLocale,
            Language::query()->where('code', '!=', $targetLocale)->where('is_default', true)->value('code'),
            Language::query()->where('code', '!=', $targetLocale)->orderByDesc('is_default')->value('code'),
            config('app.fallback_locale', 'en'),
        ]);

        foreach ($candidates as $candidate) {
            $candidate = strtolower((string) $candidate);

            if ($candidate !== '' && $candidate !== $targetLocale) {
                return $candidate;
            }
        }

        return null;
    }

    protected function shouldTranslate(string $sourceValue, string $targetValue): bool
    {
        if ($sourceValue === '') {
            return false;
        }

        return $targetValue === '' || $targetValue === $sourceValue;
    }

    protected function modelKey(Model $model): string
    {
        return $model::class . ':' . $model->getKey();
    }
}
