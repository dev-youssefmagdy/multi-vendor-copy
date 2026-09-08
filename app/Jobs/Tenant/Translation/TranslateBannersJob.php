<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant\Banner;
use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;

class TranslateBannersJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'banners';
    }

    public function weight(): int
    {
        return 10;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        return $service->translateModel(
            Banner::class,
            sourceLocale: $this->sourceLocale,
            targetLocale: $this->targetLocale,
            targetLanguage: $this->targetLanguage,
            brandContext: $this->brandContext,
        );
    }
}
