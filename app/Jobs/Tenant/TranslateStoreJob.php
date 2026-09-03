<?php

namespace App\Jobs\Tenant;

use App\Jobs\Tenant\Translation\TranslateBannersJob;
use App\Jobs\Tenant\Translation\TranslateCategoriesJob;
use App\Jobs\Tenant\Translation\TranslatePagesJob;
use App\Jobs\Tenant\Translation\TranslateProductsJob;
use App\Jobs\Tenant\Translation\TranslateSettingsJob;
use App\Jobs\Tenant\Translation\TranslateStaticKeysJob;
use App\Jobs\Tenant\Translation\TranslatesStoreSection;
use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Language;
use App\Services\AiTranslationPurchaseService;
use App\Services\Tenant\StoreTranslatorService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Entry point for a full-store AI translation run. Validates the request,
 * marks the Language as "running", then fans the work out into one
 * Bus::batch() of per-section jobs (settings, categories, pages, banners,
 * static keys, products — see App\Jobs\Tenant\Translation). The batch's
 * then()/catch() callbacks — not this job — finalize the Language row
 * (status/progress/summary/cost), which only happens once every section,
 * including the product-catalog job, has finished.
 */
class TranslateStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $tenantId,
        public int $languageId,
        public ?string $sourceLocale = null,
        public ?int $triggeredBy = null,
    ) {
        $this->onQueue('translations');
    }

    public function handle(StoreTranslatorService $service): void
    {
        $tenant = TenantModel::find($this->tenantId);

        if (!$tenant) {
            Log::channel('ai_translations')->warning('ai_translation.job_dequeued_missing_tenant', [
                'tenant_id' => $this->tenantId,
                'language_id' => $this->languageId,
            ]);

            return;
        }

        tenancy()->initialize($tenant);

        $jobId = (string) str()->uuid();
        $context = [
            'job_id' => $jobId,
            'tenant_id' => $this->tenantId,
            'language_id' => $this->languageId,
            'triggered_by' => $this->triggeredBy,
        ];

        Log::channel('ai_translations')->info('ai_translation.job_dequeued', $context);

        try {
            $language = Language::query()->find($this->languageId);

            if (!$language) {
                return;
            }

            $service->ensureConfigured();

            $targetLocale = strtolower((string) $language->code);
            $sourceLocale = $service->resolveSourceLocale($targetLocale, $this->sourceLocale);
            $sourceLanguage = $sourceLocale
                ? Language::query()->where('code', $sourceLocale)->first()
                : null;
            $context += ['source_locale' => $sourceLocale, 'target_locale' => $targetLocale];

            if ($sourceLocale === null || $sourceLocale === $targetLocale) {
                $language->forceFill([
                    'translation_status' => 'completed',
                    'translation_progress' => 100,
                    'translation_summary' => json_encode(['items_translated' => 0]),
                ])->save();

                Log::channel('ai_translations')->info('ai_translation.job_skipped', $context + [
                    'reason' => 'source and target locale are the same, nothing to translate',
                ]);

                return;
            }

            $brandContext = $service->buildBrandContext($language, $sourceLanguage, $sourceLocale);

            $language->forceFill(['translation_status' => 'running', 'translation_progress' => 0])->save();

            Log::channel('ai_translations')->info('ai_translation.job_started', $context);

            $this->dispatchSectionBatch($jobId, $context, $sourceLocale, $targetLocale, $language->name, $brandContext);
        } catch (Throwable $e) {
            Language::query()->where('id', $this->languageId)->update(['translation_status' => 'failed']);

            $language ??= Language::query()->find($this->languageId);

            if ($language?->central_language_id) {
                app(AiTranslationPurchaseService::class)->markFailed($this->tenantId, $language->central_language_id);
            }

            Log::channel('ai_translations')->error('ai_translation.job_failed', $context + [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    protected function dispatchSectionBatch(
        string $jobId,
        array $context,
        string $sourceLocale,
        string $targetLocale,
        string $targetLanguage,
        string $brandContext,
    ): void {
        $batchCacheKey = "ai_translation:batch:{$jobId}";
        $startedAt = microtime(true);

        $makeJob = fn(string $jobClass): TranslatesStoreSection => new $jobClass(
            $this->tenantId,
            $this->languageId,
            $sourceLocale,
            $targetLocale,
            $targetLanguage,
            $brandContext,
            $batchCacheKey,
        );

        $sectionJobs = [
            TranslateSettingsJob::class,
            TranslateCategoriesJob::class,
            TranslatePagesJob::class,
            TranslateBannersJob::class,
            TranslateStaticKeysJob::class,
            TranslateProductsJob::class,
        ];

        $jobs = array_map($makeJob, $sectionJobs);
        $sections = array_map(static fn(TranslatesStoreSection $job) => $job->section(), $jobs);

        $tenantId = $this->tenantId;
        $languageId = $this->languageId;

        Bus::batch($jobs)
            ->name("ai-translation:{$tenantId}:{$languageId}")
            ->onQueue('translations')
            ->allowFailures(false)
            ->then(function (Batch $batch) use ($tenantId, $languageId, $context, $sections, $batchCacheKey, $startedAt) {
                self::finalizeAsTenant($tenantId, function () use ($tenantId, $languageId, $context, $sections, $batchCacheKey, $startedAt) {
                    $language = Language::query()->find($languageId);

                    if (!$language) {
                        return;
                    }

                    $summary = [];
                    $itemsTranslated = 0;
                    $tokensUsed = 0;

                    foreach ($sections as $section) {
                        $report = Cache::driver('file')->pull("{$batchCacheKey}:{$section}") ?? ['items' => 0, 'tokens' => 0];
                        $summary[$section] = $report['items'];
                        $itemsTranslated += $report['items'];
                        $tokensUsed += $report['tokens'];
                    }

                    $pricePer1k = (float) config('services.openai.translation_price_per_1k_tokens', 0);
                    $costUsd = round($tokensUsed / 1000 * $pricePer1k, 4);
                    $durationSeconds = round(microtime(true) - $startedAt, 2);

                    $language->forceFill([
                        'translation_status' => 'completed',
                        'translation_progress' => 100,
                        'translation_summary' => json_encode([
                            'items_translated' => $itemsTranslated,
                            'static_keys_translated' => $summary['static_keys'] ?? 0,
                            'section_summary' => $summary,
                        ]),
                        'last_translation_token_count' => $tokensUsed,
                        'last_translation_cost_usd' => $costUsd,
                    ])->save();

                    if ($language->central_language_id) {
                        app(AiTranslationPurchaseService::class)->markCompleted($tenantId, $language->central_language_id);
                    }

                    Log::channel('ai_translations')->info('ai_translation.job_completed', $context + [
                        'status' => 'completed',
                        'items_translated' => $itemsTranslated,
                        'section_summary' => $summary,
                        'tokens_used' => $tokensUsed,
                        'cost_usd' => $costUsd,
                        'duration_seconds' => $durationSeconds,
                    ]);
                });
            })
            ->catch(function (Batch $batch, Throwable $e) use ($tenantId, $languageId, $context, $sections, $batchCacheKey) {
                self::finalizeAsTenant($tenantId, function () use ($tenantId, $languageId, $context, $sections, $batchCacheKey, $e) {
                    $language = Language::query()->find($languageId);

                    $language?->forceFill(['translation_status' => 'failed'])->save();

                    if ($language?->central_language_id) {
                        app(AiTranslationPurchaseService::class)->markFailed($tenantId, $language->central_language_id);
                    }

                    foreach ($sections as $section) {
                        Cache::driver('file')->forget("{$batchCacheKey}:{$section}");
                    }

                    Log::channel('ai_translations')->error('ai_translation.job_failed', $context + [
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ]);
                });
            })
            ->dispatch();
    }

    /**
     * then()/catch() batch callbacks run as their own queued jobs, outside
     * the tenancy context this job initialized — so they must re-initialize
     * tenancy themselves before touching any tenant model.
     */
    protected static function finalizeAsTenant(string $tenantId, \Closure $callback): void
    {
        $tenant = TenantModel::find($tenantId);

        if (!$tenant) {
            return;
        }

        tenancy()->initialize($tenant);

        try {
            $callback();
        } finally {
            tenancy()->end();
        }
    }
}
