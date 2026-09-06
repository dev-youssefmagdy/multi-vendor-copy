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

class TranslateLanguageResourcesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public int $languageId,
        public string $sourceLocale,
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

        $translator->translateLanguageResources($this->sourceLocale, $targetLocale, $language->name);

        $language->forceFill(['translation_progress' => 20])->save();
    }
}
