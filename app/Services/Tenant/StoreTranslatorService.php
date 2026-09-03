<?php

namespace App\Services\Tenant;

use App\Enums\TranslationSource;
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
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Translates the full tenant store (custom labels, categories, products,
 * pages, and banners) into a given tenant language, using the same
 * OpenAiTranslationService + syncTranslations() pattern as
 * App\Services\CatalogTranslatorService (the central catalog equivalent).
 *
 * This service only contains the translation logic for each store section.
 * Orchestration (queueing, progress tracking, batching, and finalizing the
 * Language row) lives in the App\Jobs\Tenant\Translation job classes and in
 * App\Jobs\Tenant\TranslateStoreJob, which builds a Bus::batch() of these
 * per-section jobs.
 *
 * NOTE on pricing: token-usage-based billing is not implemented yet — this
 * service tracks a simple "items translated" count for the completion
 * summary. Price-per-token calculation is pending pricing configuration
 * (see AI_TRANSLATION_PRICE_PER_1K_TOKENS placeholder in config/services.php).
 */
class StoreTranslatorService
{
    public const MODEL_FIELDS = [
        Category::class => ['name', 'description', 'meta_keywords', 'meta_description'],
        Product::class => ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'],
        Page::class => ['title', 'body'],
        Banner::class => ['title', 'subtitle', 'button_text'],
    ];

    public const VARIANT_FIELDS = ['title'];

    /** Products are processed in DB chunks of this size by TranslateProductsJob. */
    public const PRODUCT_CHUNK_SIZE = 200;

    /** Static keys are processed in chunks of this size by TranslateStaticKeysJob. */
    public const STATIC_KEY_CHUNK_SIZE = 50;

    public function __construct(
        protected OpenAiTranslationService $openAi,
        protected StoreContextService $storeContext,
        protected TenantTranslationService $translationService,
    ) {
    }

    public function ensureConfigured(): void
    {
        if (!$this->openAi->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }
    }

    public function buildBrandContext(Language $targetLanguage, ?Language $sourceLanguage, string $sourceLocale): string
    {
        return $this->storeContext->build(
            targetLanguageName: $targetLanguage->name,
            sourceLanguageName: $sourceLanguage?->name ?? $sourceLocale,
        );
    }

    public function totalTokensUsed(): int
    {
        return $this->openAi->totalTokensUsed();
    }

    public function resetUsage(): void
    {
        $this->openAi->resetUsage();
    }

    public function translateSettings(string $sourceLocale, string $targetLocale, string $targetLanguage, string $brandContext = ''): int
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
    public function translateModel(
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
            ->select(['translatable_id', 'language_id', 'field', 'value', 'source'])
            ->cursor()
            ->each(function ($row) use (&$existing, $localeByLanguageId) {
                $locale = $localeByLanguageId[$row->language_id] ?? null;

                if ($locale !== null) {
                    $existing[$row->translatable_id][$locale][$row->field] = [
                        'value' => (string) $row->value,
                        'source' => $row->getRawOriginal('source'),
                    ];
                }
            });

        $pending = [];

        foreach ($existing as $translatableId => $locales) {
            foreach ($fields as $field) {
                $sourceValue = trim((string) ($locales[$sourceLocale][$field]['value'] ?? ''));
                $target = $locales[$targetLocale][$field] ?? null;
                $targetValue = trim((string) ($target['value'] ?? ''));
                $targetSource = $target['source'] ?? null;

                $alreadyReviewed = $targetValue !== '' && in_array($targetSource, [
                    TranslationSource::Manual->value,
                    TranslationSource::Ai->value,
                    TranslationSource::CentralCopy->value,
                ], true);

                if ($sourceValue === '' || $alreadyReviewed) {
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
                'source' => TranslationSource::Ai->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $translatedIds[$item['translatable_id']] = true;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Translation::query()->upsert(
                $chunk,
                ['language_id', 'translatable_type', 'translatable_id', 'field'],
                ['value', 'source', 'updated_at'],
            );
        }

        return count($translatedIds);
    }

    /**
     * Base query for the products translation pass: every tenant Product with
     * its variants and each row's linked central Product/ProductVariant
     * translations preloaded, ordered by id so it is safe to page through
     * with chunkById() from TranslateProductsJob.
     */
    public function productsQuery()
    {
        return Product::query()
            ->with([
                'variants',
                'centralProduct.translations.language',
                'variants.centralVariant.translations.language',
            ])
            ->orderBy('id');
    }

    /**
     * Translates one chunk of tenant Products (with their variants) fetched
     * via productsQuery()->chunkById(). Rather than treating Product like a
     * plain tenant model, this reads each row's linked central
     * Product/ProductVariant translations: when the central catalog already
     * has the target-locale text (translated once, centrally, for every
     * tenant), it is copied straight into the tenant Translation table with
     * no AI call; only text missing from both the tenant and the central
     * catalog is sent to OpenAI.
     *
     * Returns the number of products+variants that received a translation
     * in this chunk.
     */
    public function translateProductsChunk(
        Collection $products,
        string $sourceLocale,
        string $targetLocale,
        string $targetLanguage,
        string $brandContext = '',
    ): int {
        if ($products->isEmpty()) {
            return 0;
        }

        $productFields = self::MODEL_FIELDS[Product::class];
        $variantFields = self::VARIANT_FIELDS;

        $languageIds = Language::query()->pluck('id', 'code');
        $sourceLanguageId = $languageIds->get($sourceLocale);
        $targetLanguageId = $languageIds->get($targetLocale);

        if (!$sourceLanguageId || !$targetLanguageId) {
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
                ['value', 'source', 'updated_at'],
            );
        }

        if ($pending !== []) {
            $translated = $this->openAi->translateBatch(
                array_map(fn(array $item) => $item['text'], $pending),
                $sourceLocale,
                $targetLocale,
                $targetLanguage,
                $brandContext ?: 'Tenant store products and variants',
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
                    'source' => TranslationSource::Ai->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                Translation::query()->upsert(
                    $chunk,
                    ['language_id', 'translatable_type', 'translatable_id', 'field'],
                    ['value', 'source', 'updated_at'],
                );
            }
        }

        return count($touchedIds);
    }

    /**
     * Decide, for one field, whether it needs (re)translating at all, and if
     * so whether the target text can be copied straight from the linked
     * central translation (no AI cost) or must be queued for OpenAI.
     *
     * A field is only skipped when its current target-locale row is Manual
     * (a vendor/admin typed it on purpose) or already Ai/CentralCopy (a
     * previous translation run already reviewed it — re-sending unchanged
     * text like brand names to OpenAI on every run just re-bills the same
     * tokens for the same result). Anything else — no row yet, or a row
     * still marked Default — is a never-reviewed placeholder and gets
     * (re)translated, i.e. TranslateStoreJob overrides every default value.
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
            $target = $tenantLocales[$targetLocale][$field] ?? null;
            $targetValue = trim((string) ($target['value'] ?? ''));
            $targetSource = $target['source'] ?? null;
            $centralTarget = trim((string) ($centralLocales[$targetLocale][$field] ?? ''));
            $sourceValue = trim((string) ($tenantLocales[$sourceLocale][$field]['value'] ?? $centralLocales[$sourceLocale][$field] ?? ''));

            if ($sourceValue === '') {
                continue;
            }

            $alreadyReviewed = $targetValue !== '' && in_array($targetSource, [
                TranslationSource::Manual->value,
                TranslationSource::Ai->value,
                TranslationSource::CentralCopy->value,
            ], true);

            if ($alreadyReviewed) {
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
                    'source' => TranslationSource::CentralCopy->value,
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
     * once, keyed as [morphClass][translatableId][locale][field] => ['value' => ..., 'source' => ...].
     */
    protected function existingTenantTranslations(array $morphClasses, array $localeByLanguageId): array
    {
        $existing = [];

        Translation::query()
            ->whereIn('translatable_type', $morphClasses)
            ->whereIn('language_id', array_keys($localeByLanguageId))
            ->select(['translatable_type', 'translatable_id', 'language_id', 'field', 'value', 'source'])
            ->cursor()
            ->each(function ($row) use (&$existing, $localeByLanguageId) {
                $locale = $localeByLanguageId[$row->language_id] ?? null;

                if ($locale !== null) {
                    $existing[$row->translatable_type][$row->translatable_id][$locale][$row->field] = [
                        'value' => (string) $row->value,
                        'source' => $row->getRawOriginal('source'),
                    ];
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
     * Build the list of static UI keys (lang file defaults) still needing
     * translation for the target language.
     *
     * keysForLocale($sourceLocale) is only used here to enumerate every
     * known key with its English source text — it loads *English's own*
     * overrides internally, which is irrelevant, so the target language's
     * review status is looked up separately below. A key is pending when
     * its target-language override has never been reviewed (no row yet, so
     * it defaults to TranslationSource::Default). Manual/Ai/CentralCopy
     * overrides are left alone — Manual because a vendor typed it on
     * purpose, Ai/CentralCopy because a previous run already reviewed it
     * (re-translating an unchanged brand name like "VISA" every run just
     * re-bills OpenAI for the same result).
     *
     * Returned in fixed order so TranslateStaticKeysJob can safely page
     * through it with array_chunk() and report incremental progress, the
     * same way productsQuery()->chunkById() pages through products.
     */
    public function pendingStaticKeys(string $sourceLocale, Language $language): array
    {
        $rows = $this->translationService->keysForLocale($sourceLocale);

        if (empty($rows)) {
            return [];
        }

        $targetOverrideSources = TranslationOverride::query()
            ->where('language_id', $language->id)
            ->get(['key', 'source'])
            ->keyBy('key');

        $pending = [];

        foreach ($rows as $row) {
            $sourceValue = trim((string) ($row['value'] ?? $row['default'] ?? ''));

            if ($sourceValue === '' || ($row['locked'] ?? false)) {
                continue;
            }

            $targetSource = $targetOverrideSources->get($row['key'])?->source ?? TranslationSource::Default;

            if ($targetSource !== TranslationSource::Default) {
                continue;
            }

            $pending[] = ['key' => $row['key'], 'text' => $sourceValue];
        }

        return $pending;
    }

    /**
     * Translate one chunk of pending static keys (as built by
     * pendingStaticKeys()) into TranslationOverride rows for the target
     * language. Every reviewed key is saved with source=Ai — even when the
     * translation comes back unchanged (untranslatable brand names, SKUs,
     * emails) — so it's marked reviewed and pendingStaticKeys() won't keep
     * re-sending it to OpenAI on every future run. Returns the number of
     * keys whose value actually changed (i.e. a genuine translation, not
     * just a reviewed-and-left-alone term).
     */
    public function translateStaticKeysChunk(
        array $pending,
        string $sourceLocale,
        string $targetLocale,
        Language $language,
        string $brandContext = ''
    ): int {
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

            if ($value === '') {
                continue;
            }

            TranslationOverride::query()->updateOrCreate(
                ['language_id' => $language->id, 'key_hash' => hash('sha256', $item['key'])],
                ['key' => $item['key'], 'value' => $value, 'source' => TranslationSource::Ai],
            );

            if ($value !== $item['text']) {
                $count++;
            }
        }

        TenantTranslator::flushCache();

        return $count;
    }

    public function resolveSourceLocale(string $targetLocale, ?string $sourceLocale = null): ?string
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
}
