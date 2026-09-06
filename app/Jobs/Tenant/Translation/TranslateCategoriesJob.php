<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;

class TranslateCategoriesJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'categories';
    }

    public function weight(): int
    {
        return 20;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        return $service->translateModel(
            Category::class,
            sourceLocale: $this->sourceLocale,
            targetLocale: $this->targetLocale,
            targetLanguage: $this->targetLanguage,
            brandContext: $this->brandContext,
        );
    }
}
