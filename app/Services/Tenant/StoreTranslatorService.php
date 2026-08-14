<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Services\OpenAiTranslationService;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

/**
 * Translates the full tenant store (custom labels, categories, products,
 * pages, and banners) into a given tenant language, using the same
 * OpenAiTranslationService + syncTranslations() pattern as
 * App\Services\CatalogTranslatorService (the central catalog equivalent).
 *
 * NOTE on pricing: token-usage-based billing is not implemented yet — this
 * service tracks a simple "items translated" count for the completion
 * summary. Price-per-token calculation is pending pricing configuration
 * (see AI_TRANSLATION_PRICE_PER_1K_TOKENS placeholder in config/services.php).
 */
class StoreTranslatorService
{
    protected const MODEL_FIELDS = [
        Category::class => ['name', 'description', 'meta_keywords', 'meta_description'],
        Product::class => ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'],
        Page::class => ['title', 'body'],
        Banner::class => ['title', 'subtitle', 'button_text'],
    ];

    public function __construct(
        protected OpenAiTranslationService $openAi,
    ) {
    }

    public function translateStore(Language $language, ?string $sourceLocale = null): void
    {
        if (!$this->openAi->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $targetLocale = strtolower((string) $language->code);
        $sourceLocale = $this->resolveSourceLocale($targetLocale, $sourceLocale);

        if ($sourceLocale === null || $sourceLocale === $targetLocale) {
            $language->forceFill([
                'translation_progress' => 100,
                'translation_status' => 'completed',
                'translation_summary' => json_encode(['items_translated' => 0]),
            ])->save();

            return;
        }

        $itemsTranslated = 0;

        try {
            $language->forceFill(['translation_status' => 'running', 'translation_progress' => 0])->save();

            // Weight: settings/custom labels 10%, categories 20%, products 50%, pages/banners 20%.
            $itemsTranslated += $this->translateSettings($sourceLocale, $targetLocale, $language->name);
            $language->forceFill(['translation_progress' => 10])->save();

            $itemsTranslated += $this->translateModel(Category::class, $sourceLocale, $targetLocale, $language->name);
            $language->forceFill(['translation_progress' => 30])->save();

            $itemsTranslated += $this->translateModel(Product::class, $sourceLocale, $targetLocale, $language->name);
            $language->forceFill(['translation_progress' => 80])->save();

            $itemsTranslated += $this->translateModel(Page::class, $sourceLocale, $targetLocale, $language->name);
            $itemsTranslated += $this->translateModel(Banner::class, $sourceLocale, $targetLocale, $language->name);
            $language->forceFill(['translation_progress' => 100])->save();

            $language->forceFill([
                'translation_status' => 'completed',
                'translation_summary' => json_encode(['items_translated' => $itemsTranslated]),
            ])->save();
        } catch (Throwable $e) {
            $language->forceFill(['translation_status' => 'failed'])->save();
            throw $e;
        }
    }

    protected function translateSettings(string $sourceLocale, string $targetLocale, string $targetLanguage): int
    {
        $count = 0;

        Setting::query()
            ->with('translations.language')
            ->chunkById(50, function ($settings) use (&$count, $sourceLocale, $targetLocale, $targetLanguage) {
                $pending = [];
                $states = [];

                foreach ($settings as $setting) {
                    $translations = $this->existingTranslations($setting);
                    $key = $this->modelKey($setting);

                    foreach (['title', 'value'] as $field) {
                        $sourceValue = trim((string) ($translations[$sourceLocale][$field] ?? ''));
                        $targetValue = trim((string) ($translations[$targetLocale][$field] ?? ''));

                        if ($sourceValue === '' || !$this->shouldTranslate($sourceValue, $targetValue)) {
                            continue;
                        }

                        $states[$key] ??= ['model' => $setting, 'translations' => $translations];
                        $pending[] = ['model_key' => $key, 'field' => $field, 'text' => $sourceValue];
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
                    'Tenant custom label setting',
                );

                foreach ($pending as $offset => $item) {
                    $states[$item['model_key']]['translations'][$targetLocale][$item['field']] = trim((string) ($translated[$offset] ?? $item['text']));
                }

                foreach ($states as $state) {
                    $state['model']->syncTranslations($state['translations']);
                    $count++;
                }
            });

        return $count;
    }

    protected function translateModel(string $modelClass, string $sourceLocale, string $targetLocale, string $targetLanguage): int
    {
        $fields = self::MODEL_FIELDS[$modelClass];
        $count = 0;

        $modelClass::query()
            ->with('translations.language')
            ->chunkById(50, function ($models) use (&$count, $fields, $modelClass, $sourceLocale, $targetLocale, $targetLanguage) {
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

                        $states[$modelKey] ??= ['model' => $model, 'translations' => $translations];
                        $pending[] = ['model_key' => $modelKey, 'field' => $field, 'text' => $sourceValue];
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
                    'Tenant store model: ' . class_basename($modelClass),
                );

                foreach ($pending as $offset => $item) {
                    $states[$item['model_key']]['translations'][$targetLocale][$item['field']] = trim((string) ($translated[$offset] ?? $item['text']));
                }

                foreach ($states as $state) {
                    $state['model']->syncTranslations($state['translations']);
                    $count++;
                }
            });

        return $count;
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
