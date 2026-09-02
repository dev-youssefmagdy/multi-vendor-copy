<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Translation;
use App\Models\Tenant\TranslationOverride;
use App\Services\OpenAiTranslationService;
use App\Translation\TenantTranslator;
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

            $itemsTranslated += $this->translateModel(Category::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
            $language->forceFill(['translation_progress' => 30])->save();

            $itemsTranslated += $this->translateModel(Product::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
            $language->forceFill(['translation_progress' => 80])->save();

            $itemsTranslated += $this->translateModel(Page::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
            $itemsTranslated += $this->translateModel(Banner::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
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
        return $this->translateModel(Setting::class, ['title', 'value'], $sourceLocale, $targetLocale, $targetLanguage, $brandContext, 'Tenant custom label setting');
    }

    /**
     * Translates every field of every row of $modelClass into $targetLocale in one pass:
     * a single query pulls all existing source/target translation rows (no N+1, no per-row
     * chunking), the whole pending text set is sent through translateBatch (which chunks
     * internally for the OpenAI API), and results are written back with one bulk upsert
     * keyed on the table's (language_id, translatable_type, translatable_id, field) unique
     * index. This scales to several thousand rows without the per-chunk overhead of
     * chunkById() + per-model delete/recreate.
     */
    protected function translateModel(
        string $modelClass,
        ?array $fields = null,
        string $sourceLocale = '',
        string $targetLocale = '',
        string $targetLanguage = '',
        string $brandContext = '',
        ?string $fallbackContext = null,
    ): int {
        $fields ??= self::MODEL_FIELDS[$modelClass];

        $languageIds = Language::query()->pluck('id', 'code');
        $sourceLanguageId = $languageIds->get($sourceLocale);
        $targetLanguageId = $languageIds->get($targetLocale);

        if (!$sourceLanguageId || !$targetLanguageId) {
            return 0;
        }

        $morphClass = (new $modelClass())->getMorphClass();
        $localeByLanguageId = [$sourceLanguageId => $sourceLocale, $targetLanguageId => $targetLocale];

        $existing = [];

        Translation::query()
            ->where('translatable_type', $morphClass)
            ->whereIn('language_id', [$sourceLanguageId, $targetLanguageId])
            ->whereIn('field', $fields)
            ->select(['translatable_id', 'language_id', 'field', 'value'])
            ->cursor()
            ->each(function ($row) use (&$existing, $localeByLanguageId) {
                $locale = $localeByLanguageId[$row->language_id] ?? null;

                if ($locale !== null) {
                    $existing[$row->translatable_id][$locale][$row->field] = (string) $row->value;
                }
            });

        $pending = [];

        foreach ($existing as $translatableId => $locales) {
            foreach ($fields as $field) {
                $sourceValue = trim((string) ($locales[$sourceLocale][$field] ?? ''));
                $targetValue = trim((string) ($locales[$targetLocale][$field] ?? ''));

                if ($sourceValue === '' || !$this->shouldTranslate($sourceValue, $targetValue)) {
                    continue;
                }

                $pending[] = ['translatable_id' => $translatableId, 'field' => $field, 'text' => $sourceValue];
            }
        }

        if ($pending === []) {
            return 0;
        }

        $translated = $this->openAi->translateBatch(
            array_map(fn(array $item) => $item['text'], $pending),
            $sourceLocale,
            $targetLocale,
            $targetLanguage,
            $brandContext ?: ($fallbackContext ?? ('Tenant store content: ' . class_basename($modelClass))),
        );

        $now = now();
        $rows = [];
        $translatedIds = [];

        foreach ($pending as $offset => $item) {
            $value = trim((string) ($translated[$offset] ?? $item['text']));

            if ($value === '') {
                continue;
            }

            $rows[] = [
                'language_id' => $targetLanguageId,
                'translatable_type' => $morphClass,
                'translatable_id' => $item['translatable_id'],
                'field' => $item['field'],
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $translatedIds[$item['translatable_id']] = true;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Translation::query()->upsert(
                $chunk,
                ['language_id', 'translatable_type', 'translatable_id', 'field'],
                ['value', 'updated_at'],
            );
        }

        return count($translatedIds);
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
}
