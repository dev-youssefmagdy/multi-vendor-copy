<?php

namespace App\Jobs;

use App\Jobs\Translation\FinalizeLanguageTranslationJob;
use App\Jobs\Translation\SyncTranslatedCatalogJob;
use App\Jobs\Translation\TranslateCatalogModelJob;
use App\Jobs\Translation\TranslateLanguageResourcesJob;
use App\Models\Language;
use App\Services\CatalogTranslatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class TranslateLanguageCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $languageId,
        public ?string $sourceLocale = null,
    ) {
        $this->onQueue('translations');
    }

    /**
     * A full-catalog translation makes many sequential OpenAI calls and can run
     * for hours. Rather than doing it all in one long-lived job (which risks the
     * worker being OOM-killed mid-run), split it into a chain of short jobs, one
     * per catalog model, so each step starts and finishes cleanly.
     */
    public function handle(CatalogTranslatorService $translator): void
    {
        $language = Language::query()->find($this->languageId);

        if (!$language) {
            return;
        }

        try {
            $sourceLocale = $translator->prepareTranslation($language, $this->sourceLocale);
        } catch (\Throwable $e) {
            $translator->markTranslationFailed($language, $e->getMessage());
            throw $e;
        }

        if ($sourceLocale === null) {
            return;
        }

        $modelClasses = $translator->catalogModelClasses();
        $totalModels = count($modelClasses);

        $chain = [new TranslateLanguageResourcesJob($this->languageId, $sourceLocale)];

        foreach ($modelClasses as $modelIndex => $modelClass) {
            $chain[] = new TranslateCatalogModelJob($this->languageId, $sourceLocale, $modelClass, $modelIndex, $totalModels);
        }

        $chain[] = new SyncTranslatedCatalogJob($this->languageId);
        $chain[] = new FinalizeLanguageTranslationJob($this->languageId);

        $languageId = $this->languageId;

        Bus::chain($chain)
            ->onQueue('translations')
            ->catch(function (\Throwable $e) use ($languageId) {
                $language = Language::query()->find($languageId);

                if ($language) {
                    app(CatalogTranslatorService::class)->markTranslationFailed($language, $e->getMessage());
                }
            })
            ->dispatch();
    }
}
