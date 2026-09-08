<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Translates every static UI key in chunks so a large key set never sends one
 * giant OpenAI batch at once. Progress is reported fractionally as chunks
 * complete (rather than only once at the end like the other lightweight
 * section jobs), the same way TranslateProductsJob reports per product chunk.
 */
class TranslateStaticKeysJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'static_keys';
    }

    public function weight(): int
    {
        return 20;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        // Unused: handle() is overridden below to process static keys in
        // chunks with incremental progress instead of one single-shot call.
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

            $pending = $service->pendingStaticKeys($this->sourceLocale, $language);
            $total = count($pending);

            if ($total === 0) {
                Cache::driver('file')->put($this->reportCacheKey(), ['items' => 0, 'tokens' => 0], now()->addHours(2));
                Language::query()->where('id', $this->languageId)->increment('translation_progress', $this->weight());

                return;
            }

            $touched = 0;
            $processed = 0;
            $appliedWeight = 0;

            foreach (array_chunk($pending, StoreTranslatorService::STATIC_KEY_CHUNK_SIZE) as $chunk) {
                if ($this->batch()?->cancelled()) {
                    return;
                }

                $touched += $service->translateStaticKeysChunk(
                    $chunk,
                    $this->sourceLocale,
                    $this->targetLocale,
                    $language,
                    $this->brandContext,
                );

                $processed += count($chunk);

                $targetWeight = (int) round($this->weight() * min($processed / $total, 1));
                $delta = $targetWeight - $appliedWeight;

                if ($delta > 0) {
                    Language::query()->where('id', $this->languageId)->increment('translation_progress', $delta);
                    $appliedWeight = $targetWeight;
                }

                Log::channel('ai_translations')->info('ai_translation.static_keys_chunk_completed', $context + [
                    'processed' => $processed,
                    'total' => $total,
                    'progress_applied' => $appliedWeight,
                ]);
            }

            // Make sure the full section weight lands even if rounding left it short.
            if ($appliedWeight < $this->weight()) {
                Language::query()->where('id', $this->languageId)->increment('translation_progress', $this->weight() - $appliedWeight);
            }

            $tokensUsed = $service->totalTokensUsed();

            Cache::driver('file')->put($this->reportCacheKey(), [
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
