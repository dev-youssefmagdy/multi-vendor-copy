<?php

namespace App\Jobs\Translation;

use App\Models\Language;
use App\Services\CatalogTranslatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTranslatedCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public int $languageId,
    ) {
        $this->onQueue('translations');
    }

    public function handle(CatalogTranslatorService $translator): void
    {
        $translator->syncTranslatedCatalog();
    }
}
