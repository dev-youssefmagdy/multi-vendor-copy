<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Services\Tenant\StoreTranslatorService;

class TranslatePagesJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'pages';
    }

    public function weight(): int
    {
        return 10;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        return $service->translateModel(
            Page::class,
            sourceLocale: $this->sourceLocale,
            targetLocale: $this->targetLocale,
            targetLanguage: $this->targetLanguage,
            brandContext: $this->brandContext,
        );
    }
}
