<?php

namespace App\Jobs;

use App\Models\Language;
use App\Services\CatalogTranslatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CopyLanguageCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $languageId,
        public ?string $sourceLocale = null,
    ) {
        $this->onQueue('translations');
    }

    public function handle(CatalogTranslatorService $translator): void
    {
        $language = Language::query()->find($this->languageId);

        if (!$language) {
            return;
        }

        $translator->copyNewLanguage($language, $this->sourceLocale);
    }
}
