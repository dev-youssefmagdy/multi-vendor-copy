<?php

namespace App\Jobs\Translation;

use App\Models\Language;
use App\Services\CatalogTranslatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TranslateCatalogModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public int $languageId,
        public string $sourceLocale,
        public string $modelClass,
        public int $modelIndex,
        public int $totalModels,
    ) {
        $this->onQueue('translations');
    }

    public function handle(CatalogTranslatorService $translator): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::connection()->disableQueryLog();

        $language = Language::query()->find($this->languageId);

        if (!$language) {
            return;
        }

        $targetLocale = strtolower((string) $language->code);

        $translator->translateCatalogModelClass(
            $this->modelClass,
            $this->sourceLocale,
            $targetLocale,
            $language,
            $this->modelIndex,
            $this->totalModels,
        );
    }
}
