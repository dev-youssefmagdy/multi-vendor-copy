<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TranslationOverride;
use App\Services\OpenAiTranslationService;
use App\Translation\TenantTranslator;
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
        protected StoreContextService $storeContext,
        protected TenantTranslationService $translationService,
    ) {
    }

    public function translateStore(Language $language, ?string $sourceLocale = null): void
    {
        if (!$this->openAi->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $targetLocale = strtolower((string) $language->code);
        $sourceLocale = $this->resolveSourceLocale($targetLocale, $sourceLocale);
        $sourceLang = $sourceLocale
            ? Language::query()->where('code', $sourceLocale)->first()
            : null;

        if ($sourceLocale === null || $sourceLocale === $targetLocale) {
            $language->forceFill([
                'translation_progress' => 100,
                'translation_status' => 'completed',
                'translation_summary' => json_encode(['items_translated' => 0]),
            ])->save();

            return;
        }

        $itemsTranslated = 0;
        $brandContext = $this->storeContext->build(
            targetLanguageName: $language->name,
            sourceLanguageName: $sourceLang?->name ?? $sourceLocale,
        );

        $this->openAi->resetUsage();

        try {
            $language->forceFill(['translation_status' => 'running', 'translation_progress' => 0])->save();

            // Weight: settings/custom labels 10%, categories 30%, products 80%, pages/banners 90%, static keys 95%, done 100%.
            $itemsTranslated += $this->translateSettings($sourceLocale, $targetLocale, $language->name, $brandContext);
            $language->forceFill(['translation_progress' => 10])->save();

            $itemsTranslated += $this->translateModel(Category::class, $sourceLocale, $targetLocale, $language->name, $brandContext);
            $language->forceFill(['translation_progress' => 30])->save();

            $itemsTranslated += $this->translateModel(Product::class, $sourceLocale, $targetLocale, $language->name, $brandContext);
            $language->forceFill(['translation_progress' => 80])->save();

            $itemsTranslated += $this->translateModel(Page::class, $sourceLocale, $targetLocale, $language->name, $brandContext);
            $itemsTranslated += $this->translateModel(Banner::class, $sourceLocale, $targetLocale, $language->name, $brandContext);
            $language->forceFill(['translation_progress' => 90])->save();

            $staticKeyCount = $this->translateStaticKeys($sourceLocale, $targetLocale, $language, $brandContext);
            $itemsTranslated += $staticKeyCount;
            $language->forceFill(['translation_progress' => 95])->save();

            $tokensUsed = $this->openAi->totalTokensUsed();
            $pricePer1k = (float) config('services.openai.translation_price_per_1k_tokens', 0);

            $language->forceFill([
                'translation_status' => 'completed',
                'translation_progress' => 100,
                'translation_summary' => json_encode([
                    'items_translated' => $itemsTranslated,
                    'static_keys_translated' => $staticKeyCount,
                ]),
                'last_translation_token_count' => $tokensUsed,
                'last_translation_cost_usd' => round($tokensUsed / 1000 * $pricePer1k, 4),
            ])->save();
        } catch (Throwable $e) {
            $language->forceFill(['translation_status' => 'failed'])->save();
            throw $e;
        }
    }

    protected function translateSettings(string $sourceLocale, string $targetLocale, string $targetLanguage, string $brandContext = ''): int
    {
        $count = 0;

        Setting::query()
            ->with('translations.language')
            ->chunkById(50, function ($settings) use (&$count, $sourceLocale, $targetLocale, $targetLanguage, $brandContext) {
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
                    $brandContext ?: 'Tenant custom label setting',
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

    protected function translateModel(string $modelClass, string $sourceLocale, string $targetLocale, string $targetLanguage, string $brandContext = ''): int
    {
        $fields = self::MODEL_FIELDS[$modelClass];
        $count = 0;

        $modelClass::query()
            ->with('translations.language')
            ->chunkById(50, function ($models) use (&$count, $fields, $modelClass, $sourceLocale, $targetLocale, $targetLanguage, $brandContext) {
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
                    $brandContext ?: ('Tenant store content: ' . class_basename($modelClass)),
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

    /**
     * Translate all static UI keys (lang file defaults, minus tenant overrides)
     * into TranslationOverride rows for the target language. Skips keys that
     * are already translated (i.e. have a target override that differs from
     * the source text) and keys locked by the marketplace admin.
     */
    protected function translateStaticKeys(
        string $sourceLocale,
        string $targetLocale,
        Language $language,
        string $brandContext = ''
    ): int {
        $rows = $this->translationService->keysForLocale($sourceLocale);

        if (empty($rows)) {
            return 0;
        }

        $targetOverrides = TranslationOverride::query()
            ->where('language_id', $language->id)
            ->pluck('value', 'key')
            ->all();

        $pending = [];

        foreach ($rows as $row) {
            $sourceValue = trim((string) ($row['value'] ?? $row['default'] ?? ''));
            $existingOverride = $targetOverrides[$row['key']] ?? null;

            if ($sourceValue === '') {
                continue;
            }

            if ($row['locked'] ?? false) {
                continue;
            }

            if ($existingOverride !== null && $existingOverride !== $sourceValue) {
                continue;
            }

            $pending[] = ['key' => $row['key'], 'text' => $sourceValue];
        }

        if (empty($pending)) {
            return 0;
        }

        $translated = $this->openAi->translateBatch(
            array_map(fn(array $item) => $item['text'], $pending),
            $sourceLocale,
            $targetLocale,
            $language->name,
            $brandContext ?: 'Ecommerce store UI labels and navigation text',
        );

        $count = 0;
        foreach ($pending as $offset => $item) {
            $value = trim((string) ($translated[$offset] ?? ''));

            if ($value === '' || $value === $item['text']) {
                continue;
            }

            TranslationOverride::query()->updateOrCreate(
                ['language_id' => $language->id, 'key' => $item['key']],
                ['value' => $value],
            );
            $count++;
        }

        TenantTranslator::flushCache();

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
