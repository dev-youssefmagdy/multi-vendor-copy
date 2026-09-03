<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Services\Tenant\StoreTranslatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Translates every tenant Product (with variants) in DB chunks so a large
 * catalog never loads into memory at once. Progress is reported fractionally
 * as chunks complete (rather than only once at the end like the other
 * section jobs), since this is by far the heaviest/slowest section.
 */
class TranslateProductsJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'products';
    }

    public function weight(): int
    {
        return 30;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        // Unused: handle() is overridden below to process products in chunks
        // with incremental progress instead of one single-shot call.
        return 0;
    }

    public function handle(StoreTranslatorService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $tenant = TenantModel::find($this->tenantId);

        if (!$tenant) {
            return;
        }

        tenancy()->initialize($tenant);

        $context = [
            'tenant_id' => $this->tenantId,
            'language_id' => $this->languageId,
            'section' => $this->section(),
            'batch_id' => $this->batch()?->id,
        ];

        try {
            $language = Language::query()->find($this->languageId);

            if (!$language) {
                return;
            }

            $service->resetUsage();

            $total = Product::query()->count();

            if ($total === 0) {
                Cache::put($this->reportCacheKey(), ['items' => 0, 'tokens' => 0], now()->addHours(2));
                Language::query()->where('id', $this->languageId)->increment('translation_progress', $this->weight());

                return;
            }

            $touched = 0;
            $processed = 0;
            $appliedWeight = 0;

            $service->productsQuery()->chunkById(StoreTranslatorService::PRODUCT_CHUNK_SIZE, function ($products) use (
                $service,
                &$touched,
                &$processed,
                &$appliedWeight,
                $total,
                $context,
            ) {
                if ($this->batch()?->cancelled()) {
                    return false;
                }

                $touched += $service->translateProductsChunk(
                    $products,
                    $this->sourceLocale,
                    $this->targetLocale,
                    $this->targetLanguage,
                    $this->brandContext,
                );

                $processed += $products->count();

                $targetWeight = (int) round($this->weight() * min($processed / $total, 1));
                $delta = $targetWeight - $appliedWeight;

                if ($delta > 0) {
                    Language::query()->where('id', $this->languageId)->increment('translation_progress', $delta);
                    $appliedWeight = $targetWeight;
                }

                Log::channel('ai_translations')->info('ai_translation.products_chunk_completed', $context + [
                    'processed' => $processed,
                    'total' => $total,
                    'progress_applied' => $appliedWeight,
                ]);

                return true;
            });

            // Make sure the full section weight lands even if rounding left it short.
            if ($appliedWeight < $this->weight()) {
                Language::query()->where('id', $this->languageId)->increment('translation_progress', $this->weight() - $appliedWeight);
            }

            $tokensUsed = $service->totalTokensUsed();

            Cache::put($this->reportCacheKey(), [
                'items' => $touched,
                'tokens' => $tokensUsed,
            ], now()->addHours(2));

            Log::channel('ai_translations')->info('ai_translation.section_completed', $context + [
                'items_translated' => $touched,
                'tokens_used' => $tokensUsed,
                'weight' => $this->weight(),
            ]);
        } catch (Throwable $e) {
            Log::channel('ai_translations')->error('ai_translation.section_failed', $context + [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        } finally {
            tenancy()->end();
        }
    }
}
