<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Language as TenantLanguage;
use App\Models\Tenant\Product as TenantProduct;
use App\Services\OpenAiTranslationService;
use App\Services\Tenant\StoreContextService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Runs weekly — Neozena pays for AI translation of new catalog products.
 *
 * For each tenant:
 *   1. Find tenant products where needs_ai_translation = true
 *   2. For each active language that has been AI-translated (paid) previously,
 *      translate the product name + description + meta_keywords fields
 *   3. Mark ai_translated_at = now(), needs_ai_translation = false
 *
 * Usage:
 *   php artisan products:translate-new
 *   php artisan products:translate-new --dry-run
 *   php artisan products:translate-new --tenant=TENANT_ID
 */
class TranslateNewProductsCommand extends Command
{
    protected $signature = 'products:translate-new
                            {--dry-run : Preview without making AI calls or DB writes}
                            {--tenant= : Only process a specific tenant ID}';

    protected $description = 'Auto-translate newly synced catalog products into each tenant\'s active AI-translation languages. Neozena covers the AI cost.';

    protected const FIELDS = ['name', 'description', 'meta_keywords'];

    public function handle(OpenAiTranslationService $openAi): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        if (!$dryRun && !$openAi->configured()) {
            $this->error('OpenAI translation is not configured.');

            return self::FAILURE;
        }

        $tenants = $tenantId
            ? Tenant::query()->where('id', $tenantId)->get()
            : Tenant::query()->get();

        $totalProducts = 0;
        $totalLanguages = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                // Dedup plan phase 5: central-mode tenants have no `needs_ai_translation` copies; the missing
                // (product, language, field) values of the MERGED read are translated and stored as tenant-only
                // `central_product` translation rows (FieldOverrideStore::putTenantTranslation), never as copies.
                [$products, $languages] = \App\Repositories\Tenant\Catalog\ProductCatalog::enabled($tenant)
                    ? $this->processCentralTenant($openAi, $dryRun)
                    : $this->processTenant($openAi, $dryRun);
                $totalProducts += $products;
                $totalLanguages += $languages;
            } catch (Throwable $e) {
                $this->error("[{$tenant->id}] Error: {$e->getMessage()}");
                report($e);
            } finally {
                tenancy()->end();
            }
        }

        $verb = $dryRun ? 'would be' : 'were';
        $this->info("Done. {$totalProducts} product(s) {$verb} translated across {$totalLanguages} language(s).");

        return self::SUCCESS;
    }

    /** @return array{0: int, 1: int} [products touched, languages] */
    private function processCentralTenant(OpenAiTranslationService $openAi, bool $dryRun): array
    {
        $tenant = tenant();
        $languages = TenantLanguage::query()->where('is_active', true)->where('translation_status', 'completed')->get();
        $source = TenantLanguage::query()->where('is_default', true)->first();
        $sourceLocale = strtolower((string) ($source?->code ?? config('app.fallback_locale', 'en')));
        if ($languages->isEmpty()) {
            return [0, 0];
        }

        $store = new \App\Repositories\Tenant\Catalog\FieldOverrideStore();
        $products = \App\Models\Tenant\Catalog\MergedProduct::query()
            ->where('source', 'central')->where('active', true)->with('translations.language')->limit(200)->get();
        $storeContext = app(StoreContextService::class);
        $touched = [];

        foreach ($languages as $language) {
            $target = strtolower((string) $language->code);
            if ($target === $sourceLocale) {
                continue;
            }
            $brand = $storeContext->build(targetLanguageName: $language->name, sourceLanguageName: $source?->name ?? $sourceLocale);

            foreach ($products as $product) {
                $values = [];
                foreach ($product->translations as $t) {
                    $values[(string) $t->language?->code][$t->field] = (string) $t->value;
                }
                $pending = [];
                foreach (self::FIELDS as $field) {
                    $src = trim((string) ($values[$sourceLocale][$field] ?? ''));
                    if ($src !== '' && trim((string) ($values[$target][$field] ?? '')) === '') {
                        $pending[] = ['group' => $product->id, 'field' => $field, 'text' => $src];
                    }
                }
                if ($pending === []) {
                    continue;
                }
                $touched[$product->id] = true;
                if ($dryRun) {
                    $this->line("  [c:{$product->source_id}] {$language->name}: " . implode(', ', array_column($pending, 'field')));
                    continue;
                }
                $translated = $openAi->translateGroupedPending($pending, $sourceLocale, $target, $language->name, $brand);
                foreach ($pending as $offset => $item) {
                    $value = trim((string) ($translated[$offset] ?? ''));
                    if ($value !== '') {
                        $store->putTenantTranslation(\App\Repositories\Tenant\Catalog\ProductCatalog::CENTRAL_TRANSLATION_TYPE, (int) $product->source_id, $target, $item['field'], $value);
                    }
                }
            }
        }
        $this->line("[{$tenant->id}] central mode: " . count($touched) . ' product(s) translated');

        return [count($touched), $languages->count()];
    }

    private function processTenant(OpenAiTranslationService $openAi, bool $dryRun): array
    {
        $tenant = tenant();

        // Languages that have already been AI-translated (Neozena or the tenant paid for them).
        $activeLanguages = TenantLanguage::query()
            ->where('is_active', true)
            ->where('translation_status', 'completed')
            ->get();

        if ($activeLanguages->isEmpty()) {
            return [0, 0];
        }

        $sourceLanguage = TenantLanguage::query()
            ->where('is_default', true)
            ->orderByDesc('is_default')
            ->first();

        $sourceLocale = strtolower((string) ($sourceLanguage?->code ?? config('app.fallback_locale', 'en')));

        $products = TenantProduct::query()
            ->where('needs_ai_translation', true)
            ->where('active', true)
            ->with('translations.language')
            ->limit(200) // safety cap per run
            ->get();

        if ($products->isEmpty()) {
            return [0, 0];
        }

        $this->line("[{$tenant->id}] {$products->count()} product(s) x {$activeLanguages->count()} language(s)");

        $storeContext = app(StoreContextService::class);

        foreach ($activeLanguages as $language) {
            $targetLocale = strtolower((string) $language->code);

            if ($targetLocale === $sourceLocale) {
                continue;
            }

            $brandContext = $storeContext->build(
                targetLanguageName: $language->name,
                sourceLanguageName: $sourceLanguage?->name ?? $sourceLocale,
            );

            foreach ($products as $product) {
                $this->translateProduct($product, $language, $sourceLocale, $openAi, $brandContext, $dryRun);
            }
        }

        if (!$dryRun) {
            TenantProduct::query()
                ->whereIn('id', $products->pluck('id'))
                ->update([
                    'needs_ai_translation' => false,
                    'ai_translated_at' => now(),
                ]);
        }

        return [$products->count(), $activeLanguages->count()];
    }

    private function translateProduct(
        TenantProduct $product,
        TenantLanguage $language,
        string $sourceLocale,
        OpenAiTranslationService $openAi,
        string $brandContext,
        bool $dryRun,
    ): void {
        $targetLocale = strtolower((string) $language->code);

        $translations = [];
        foreach ($product->translations as $translation) {
            $locale = $translation->language?->code;

            if (is_string($locale) && $locale !== '') {
                $translations[$locale][$translation->field] = (string) $translation->value;
            }
        }

        $pending = [];

        foreach (self::FIELDS as $field) {
            $sourceValue = trim((string) ($translations[$sourceLocale][$field] ?? ''));
            $existingTarget = trim((string) ($translations[$targetLocale][$field] ?? ''));

            if ($sourceValue === '' || ($existingTarget !== '' && $existingTarget !== $sourceValue)) {
                continue;
            }

            $pending[] = ['group' => $product->id, 'field' => $field, 'text' => $sourceValue];
        }

        if ($pending === []) {
            return;
        }

        if ($dryRun) {
            $this->line("  [{$product->id}] {$language->name}: " . implode(', ', array_column($pending, 'field')));

            return;
        }

        $translated = $openAi->translateGroupedPending(
            $pending,
            $sourceLocale,
            $targetLocale,
            $language->name,
            $brandContext,
        );

        $translations[$targetLocale] ??= [];

        foreach ($pending as $offset => $item) {
            $value = trim((string) ($translated[$offset] ?? ''));

            if ($value !== '') {
                $translations[$targetLocale][$item['field']] = $value;
            }
        }

        $product->syncTranslations($translations);
    }
}
