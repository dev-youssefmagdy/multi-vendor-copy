<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Common tenant-aware plumbing shared by every "translate one store section"
 * job that TranslateStoreJob bundles into a single Bus::batch(). Each
 * concrete job only implements translateSection() with its own section of
 * StoreTranslatorService; this base class handles tenancy bootstrapping,
 * batch cancellation, atomic progress increments, and reporting the item
 * count + token usage back to the batch's "then" callback via cache.
 */
abstract class TranslatesStoreSection implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    /** Unique key of this section, used for logging and cache reporting. */
    abstract public function section(): string;

    /** Percentage points this section contributes to overall progress. */
    abstract public function weight(): int;

    /** Runs the section's translation work and returns the items-translated count. */
    abstract protected function translateSection(StoreTranslatorService $service, Language $language): int;

    public function __construct(
        public string $tenantId,
        public int $languageId,
        public string $sourceLocale,
        public string $targetLocale,
        public string $targetLanguage,
        public string $brandContext,
        public string $batchCacheKey,
    ) {
        $this->onQueue('translations');
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

            $itemsTranslated = $this->translateSection($service, $language);

            $tokensUsed = $service->totalTokensUsed();

            Cache::put($this->reportCacheKey(), [
                'items' => $itemsTranslated,
                'tokens' => $tokensUsed,
            ], now()->addHours(2));

            Language::query()->where('id', $this->languageId)->increment('translation_progress', $this->weight());

            Log::channel('ai_translations')->info('ai_translation.section_completed', $context + [
                'items_translated' => $itemsTranslated,
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

    public function reportCacheKey(): string
    {
        return "{$this->batchCacheKey}:{$this->section()}";
    }
}
