<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Translation;
use App\Models\Tenant\TranslationOverride;
use App\Services\OpenAiTranslationService;
use App\Translation\TenantTranslator;
use Illuminate\Support\Facades\Log;
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

    protected const VARIANT_FIELDS = ['title'];

    public function __construct(
        protected OpenAiTranslationService $openAi,
        protected StoreContextService $storeContext,
        protected TenantTranslationService $translationService,
    ) {
    }

    public function translateStore(Language $language, ?string $sourceLocale = null, ?int $triggeredBy = null): void
    {
        $log = Log::channel('ai_translations');
        $jobId = (string) str()->uuid();
        $tenantId = tenant('id');
        $startedAt = microtime(true);

        $context = [
            'job_id' => $jobId,
            'tenant_id' => $tenantId,
            'language_id' => $language->id,
            'triggered_by' => $triggeredBy,
        ];

        if (!$this->openAi->configured()) {
            $log->error('ai_translation.job_rejected', $context + ['reason' => 'OpenAI translation is not configured.']);

            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $targetLocale = strtolower((string) $language->code);
        $sourceLocale = $this->resolveSourceLocale($targetLocale, $sourceLocale);
        $sourceLang = $sourceLocale
            ? Language::query()->where('code', $sourceLocale)->first()
            : null;
        $context += ['source_locale' => $sourceLocale, 'target_locale' => $targetLocale];

        if ($sourceLocale === null || $sourceLocale === $targetLocale) {
            $language->forceFill([
                'translation_progress' => 100,
                'translation_status' => 'completed',
                'translation_summary' => json_encode(['items_translated' => 0]),
            ])->save();

            $log->info('ai_translation.job_skipped', $context + ['reason' => 'source and target locale are the same, nothing to translate']);

            return;
        }

        $log->info('ai_translation.job_started', $context);

        $itemsTranslated = 0;
        $sectionSummary = [];
        $brandContext = $this->storeContext->build(
            targetLanguageName: $language->name,
            sourceLanguageName: $sourceLang?->name ?? $sourceLocale,
        );

        $this->openAi->resetUsage();

        try {
            $language->forceFill(['translation_status' => 'running', 'translation_progress' => 0])->save();

            // Weight: settings/custom labels 10%, categories 30%, pages/banners 50%, static keys 70%, products 95%, done 100%.
            $sectionSummary['settings'] = $this->translateSettings($sourceLocale, $targetLocale, $language->name, $brandContext);
            $itemsTranslated += $sectionSummary['settings'];
            $language->forceFill(['translation_progress' => 10])->save();
            $log->info('ai_translation.section_completed', $context + ['section' => 'settings', 'items_translated' => $sectionSummary['settings'], 'progress' => 10]);

            $sectionSummary['categories'] = $this->translateModel(Category::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
            $itemsTranslated += $sectionSummary['categories'];
            $language->forceFill(['translation_progress' => 30])->save();
            $log->info('ai_translation.section_completed', $context + ['section' => 'categories', 'items_translated' => $sectionSummary['categories'], 'progress' => 30]);

            $sectionSummary['pages'] = $this->translateModel(Page::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
            $itemsTranslated += $sectionSummary['pages'];
            $sectionSummary['banners'] = $this->translateModel(Banner::class, sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext);
            $itemsTranslated += $sectionSummary['banners'];
            $language->forceFill(['translation_progress' => 50])->save();
            $log->info('ai_translation.section_completed', $context + ['section' => 'pages_and_banners', 'items_translated' => $sectionSummary['pages'] + $sectionSummary['banners'], 'progress' => 50]);

            $staticKeyCount = $this->translateStaticKeys($sourceLocale, $targetLocale, $language, $brandContext);
            $sectionSummary['static_keys'] = $staticKeyCount;
            $itemsTranslated += $staticKeyCount;
            $language->forceFill(['translation_progress' => 70])->save();
            $log->info('ai_translation.section_completed', $context + ['section' => 'static_keys', 'items_translated' => $staticKeyCount, 'progress' => 70]);

            $sectionSummary['products'] = $this->translateProducts(sourceLocale: $sourceLocale, targetLocale: $targetLocale, targetLanguage: $language->name, brandContext: $brandContext, language: $language);
            $itemsTranslated += $sectionSummary['products'];
            $language->forceFill(['translation_progress' => 95])->save();
            $log->info('ai_translation.section_completed', $context + ['section' => 'products', 'items_translated' => $sectionSummary['products'], 'progress' => 95]);

            $tokensUsed = $this->openAi->totalTokensUsed();
            $pricePer1k = (float) config('services.openai.translation_price_per_1k_tokens', 0);
            $costUsd = round($tokensUsed / 1000 * $pricePer1k, 4);
            $durationSeconds = round(microtime(true) - $startedAt, 2);

            $language->forceFill([
                'translation_status' => 'completed',
                'translation_progress' => 100,
                'translation_summary' => json_encode([
                    'items_translated' => $itemsTranslated,
                    'static_keys_translated' => $staticKeyCount,
                ]),
                'last_translation_token_count' => $tokensUsed,
                'last_translation_cost_usd' => $costUsd,
            ])->save();

            $log->info('ai_translation.job_completed', $context + [
                'status' => 'completed',
                'items_translated' => $itemsTranslated,
                'section_summary' => $sectionSummary,
                'tokens_used' => $tokensUsed,
                'cost_usd' => $costUsd,
                'duration_seconds' => $durationSeconds,
            ]);
        } catch (Throwable $e) {
            $durationSeconds = round(microtime(true) - $startedAt, 2);

            $language->forceFill(['translation_status' => 'failed'])->save();

            $log->error('ai_translation.job_failed', $context + [
                'status' => 'failed',
                'items_translated' => $itemsTranslated,
                'section_summary' => $sectionSummary,
                'tokens_used' => $this->openAi->totalTokensUsed(),
                'duration_seconds' => $durationSeconds,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

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
     * Translates every tenant Product together with its variants. Rather than
     * treating Product like a plain tenant model, this pulls all products with
     * their variants and each row's linked central Product/ProductVariant
     * translations: when the central catalog already has the target-locale
     * text (translated once, centrally, for every tenant), it is copied
     * straight into the tenant Translation table with no AI call; only text
     * missing from both the tenant and the central catalog is sent to OpenAI.
     */
    protected function translateProducts(string $sourceLocale, string $targetLocale, string $targetLanguage, string $brandContext = '', ?Language $language = null): int
    {
        $productFields = self::MODEL_FIELDS[Product::class];
        $variantFields = self::VARIANT_FIELDS;

        $languageIds = Language::query()->pluck('id', 'code');
        $sourceLanguageId = $languageIds->get($sourceLocale);
        $targetLanguageId = $languageIds->get($targetLocale);

        if (!$sourceLanguageId || !$targetLanguageId) {
            return 0;
        }

        $products = Product::query()
            ->with([
                'variants',
                'centralProduct.translations.language',
                'variants.centralVariant.translations.language',
            ])
            ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        $productMorph = (new Product())->getMorphClass();
        $variantMorph = (new ProductVariant())->getMorphClass();

        $existing = $this->existingTenantTranslations(
            [$productMorph, $variantMorph],
            [$sourceLanguageId => $sourceLocale, $targetLanguageId => $targetLocale],
        );

        $now = now();
        $copyRows = [];
        $pending = [];
        $touchedIds = [];

        foreach ($products as $product) {
            $this->planTranslationItem(
                $productMorph,
                $product->id,
                $productFields,
                $existing[$productMorph][$product->id] ?? [],
                $this->centralTranslations($product->centralProduct, $productFields),
                $sourceLocale,
                $targetLocale,
                $targetLanguageId,
                $now,
                $copyRows,
                $pending,
                $touchedIds,
            );

            foreach ($product->variants as $variant) {
                $this->planTranslationItem(
                    $variantMorph,
                    $variant->id,
                    $variantFields,
                    $existing[$variantMorph][$variant->id] ?? [],
                    $this->centralTranslations($variant->centralVariant, $variantFields),
                    $sourceLocale,
                    $targetLocale,
                    $targetLanguageId,
                    $now,
                    $copyRows,
                    $pending,
                    $touchedIds,
                );
            }
        }

        foreach (array_chunk($copyRows, 500) as $chunk) {
            Translation::query()->upsert(
                $chunk,
                ['language_id', 'translatable_type', 'translatable_id', 'field'],
                ['value', 'updated_at'],
            );
        }

        if ($pending !== []) {
            $onChunkTranslated = $language !== null
                ? function (int $completed, int $total) use ($language) {
                    $progress = 30 + (int) round(50 * $completed / max($total, 1));
                    $language->forceFill(['translation_progress' => min($progress, 80)])->save();
                }
                : null;

            $translated = $this->openAi->translateBatch(
                array_map(fn(array $item) => $item['text'], $pending),
                $sourceLocale,
                $targetLocale,
                $targetLanguage,
                $brandContext ?: 'Tenant store products and variants',
                $onChunkTranslated,
            );

            $rows = [];

            foreach ($pending as $offset => $item) {
                $value = trim((string) ($translated[$offset] ?? $item['text']));

                if ($value === '') {
                    continue;
                }

                $rows[] = [
                    'language_id' => $targetLanguageId,
                    'translatable_type' => $item['morph'],
                    'translatable_id' => $item['translatable_id'],
                    'field' => $item['field'],
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                Translation::query()->upsert(
                    $chunk,
                    ['language_id', 'translatable_type', 'translatable_id', 'field'],
                    ['value', 'updated_at'],
                );
            }
        }

        return count($touchedIds);
    }

    /**
     * Decide, for one field, whether the target text can be copied straight
     * from the linked central translation (no AI cost) or must be queued for
     * OpenAI translation, falling back to the central source text when the
     * tenant row itself has no source value.
     */
    protected function planTranslationItem(
        string $morph,
        int $id,
        array $fields,
        array $tenantLocales,
        array $centralLocales,
        string $sourceLocale,
        string $targetLocale,
        int $targetLanguageId,
        \Carbon\Carbon $now,
        array &$copyRows,
        array &$pending,
        array &$touchedIds,
    ): void {
        foreach ($fields as $field) {
            $targetValue = trim((string) ($tenantLocales[$targetLocale][$field] ?? ''));
            $centralTarget = trim((string) ($centralLocales[$targetLocale][$field] ?? ''));
            $sourceValue = trim((string) ($tenantLocales[$sourceLocale][$field] ?? $centralLocales[$sourceLocale][$field] ?? ''));

            if ($sourceValue === '' || !$this->shouldTranslate($sourceValue, $targetValue)) {
                continue;
            }

            $touchedIds["{$morph}:{$id}"] = true;

            if ($centralTarget !== '' && $centralTarget !== $sourceValue) {
                $copyRows[] = [
                    'language_id' => $targetLanguageId,
                    'translatable_type' => $morph,
                    'translatable_id' => $id,
                    'field' => $field,
                    'value' => $centralTarget,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                continue;
            }

            $pending[] = ['morph' => $morph, 'translatable_id' => $id, 'field' => $field, 'text' => $sourceValue];
        }
    }

    /**
     * Bulk-load existing tenant Translation rows for several morph types at
     * once, keyed as [morphClass][translatableId][locale][field] => value.
     */
    protected function existingTenantTranslations(array $morphClasses, array $localeByLanguageId): array
    {
        $existing = [];

        Translation::query()
            ->whereIn('translatable_type', $morphClasses)
            ->whereIn('language_id', array_keys($localeByLanguageId))
            ->select(['translatable_type', 'translatable_id', 'language_id', 'field', 'value'])
            ->cursor()
            ->each(function ($row) use (&$existing, $localeByLanguageId) {
                $locale = $localeByLanguageId[$row->language_id] ?? null;

                if ($locale !== null) {
                    $existing[$row->translatable_type][$row->translatable_id][$locale][$row->field] = (string) $row->value;
                }
            });

        return $existing;
    }

    /**
     * Read a central model's already-translated fields (source + target
     * locale) from its preloaded `translations.language` relation.
     */
    protected function centralTranslations(?\Illuminate\Database\Eloquent\Model $model, array $fields): array
    {
        if ($model === null || !$model->relationLoaded('translations')) {
            return [];
        }

        $locales = [];

        foreach ($model->translations as $translation) {
            $locale = $translation->language?->code;

            if (!is_string($locale) || $locale === '' || !in_array($translation->field, $fields, true)) {
                continue;
            }

            $locales[$locale][$translation->field] = (string) $translation->value;
        }

        return $locales;
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
