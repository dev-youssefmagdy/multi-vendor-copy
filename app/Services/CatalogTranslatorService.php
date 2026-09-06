<?php

namespace App\Services;

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
        $sourceLocale = $this->prepareTranslation($language, $sourceLocale);

        if ($sourceLocale === null) {
            return;
        }

        // Weight: resources = 20%, catalog models = 70%, tenant sync = 10%
        $this->translateLanguageResources($sourceLocale, strtolower((string) $language->code), $language->name);
        $language->forceFill(['translation_progress' => 20])->save();

        $this->translateCatalogModels($sourceLocale, strtolower((string) $language->code), $language);

        $this->syncTranslatedCatalog();
        $this->finalizeTranslation($language);
    }

    /**
     * Validate the language is translatable and resolve the source locale.
     * Returns null (after marking the language complete) when no translation is needed.
     */
    public function prepareTranslation(Language $language, ?string $sourceLocale = null): ?string
    {
        if (!$this->openAi->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $targetLocale = strtolower((string) $language->code);
        $resolved = $this->resolveSourceLocale($targetLocale, $sourceLocale);

        if ($resolved === null || $resolved === $targetLocale) {
            $language->forceFill([
                'translation_progress' => 100,
                'translation_status' => 'completed',
                'translation_error' => null,
            ])->save();
            return null;
        }

        $language->forceFill([
            'translation_progress' => 0,
            'translation_status' => 'processing',
            'translation_source_locale' => $resolved,
            'translation_error' => null,
        ])->save();

        return $resolved;
    }

    /** @return array<class-string> */
    public function catalogModelClasses(): array
    {
        return array_keys(self::MODEL_FIELDS);
    }

    public function syncTranslatedCatalog(): void
    {
        $this->tenantSyncService->syncAllTenants(['languages', 'categories', 'products']);
    }

    public function finalizeTranslation(Language $language): void
    {
        $language->forceFill([
            'translation_progress' => 100,
            'translation_status' => 'completed',
            'translation_error' => null,
        ])->save();
    }

    public function markTranslationFailed(Language $language, string $error): void
    {
        $language->forceFill([
            'translation_status' => 'failed',
            'translation_error' => mb_substr($error, 0, 1000),
        ])->save();
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

    public function translateLanguageResources(string $sourceLocale, string $targetLocale, string $targetLanguage): void
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
                    'group' => $index,
                    'field' => 'value',
                    'text' => $sourceValue,
                ];
            }

            if ($pending === []) {
                continue;
            }

            $translated = $this->openAi->translateGroupedPending(
                $pending,
                $sourceLocale,
                $targetLocale,
                $targetLanguage,
                'Laravel language resource: ' . ($resource['label'] ?? $resource['key']),
            );

            foreach ($pending as $offset => $item) {
                $rows[$item['group']]['values'][$targetLocale] = trim((string) ($translated[$offset] ?? $item['text']));
            }

            $this->translationFiles->save((string) $resource['key'], $rows);
        }
    }

    protected function translateCatalogModels(string $sourceLocale, string $targetLocale, Language $language): void
    {
        $modelClasses = $this->catalogModelClasses();
        $totalModels = count($modelClasses);

        foreach ($modelClasses as $modelIndex => $modelClass) {
            $this->translateCatalogModelClass($modelClass, $sourceLocale, $targetLocale, $language, $modelIndex, $totalModels);
        }
    }

    /**
     * Translate a single catalog model class and advance the language's
     * translation_progress within the 20-90% band, based on this model's
     * position among $totalModels.
     */
    public function translateCatalogModelClass(
        string $modelClass,
        string $sourceLocale,
        string $targetLocale,
        Language $language,
        int $modelIndex,
        int $totalModels,
    ): void {
        $targetLanguage = $language->name;
        $fields = self::MODEL_FIELDS[$modelClass];
        $progressStart = 20;
        $progressEnd = 90;

        $modelClass::query()
            ->with('translations.language')
            ->chunkById(100, function ($models) use ($fields, $modelClass, $sourceLocale, $targetLocale, $targetLanguage) {
                $items = [];
                $states = [];

                foreach ($models as $model) {
                    $translations = $this->existingTranslations($model);
                    $modelKey = $this->modelKey($model);
                    $pendingFields = [];

                    foreach ($fields as $field) {
                        $sourceValue = trim((string) ($translations[$sourceLocale][$field] ?? ''));
                        $targetValue = trim((string) ($translations[$targetLocale][$field] ?? ''));

                        if ($sourceValue === '' || !$this->shouldTranslate($sourceValue, $targetValue)) {
                            continue;
                        }

                        $pendingFields[$field] = $sourceValue;
                    }

                    if ($pendingFields === []) {
                        continue;
                    }

                    $states[$modelKey] = [
                        'model' => $model,
                        'translations' => $translations,
                    ];

                    $items[] = ['id' => $modelKey, 'translations' => $pendingFields];
                }

                if ($items === []) {
                    return;
                }

                $translated = $this->openAi->translateStructuredBatch(
                    $items,
                    $sourceLocale,
                    $targetLocale,
                    $targetLanguage,
                    'Central catalog model: ' . class_basename($modelClass),
                );

                foreach ($translated as $modelKey => $translatedFields) {
                    foreach ($translatedFields as $field => $value) {
                        $states[$modelKey]['translations'][$targetLocale][$field] = $value;
                    }
                }

                foreach ($states as $state) {
                    /** @var \Illuminate\Database\Eloquent\Model $model */
                    $model = $state['model'];
                    $model->syncTranslations($state['translations']);
                }
            });

        $progress = $progressStart + (int) round(($progressEnd - $progressStart) * ($modelIndex + 1) / $totalModels);
        $language->forceFill(['translation_progress' => $progress])->save();
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
            Language::query()->where('code', '!=', $targetLocale)->orderBy('sort_order')->orderByDesc('is_default')->value('code'),
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
