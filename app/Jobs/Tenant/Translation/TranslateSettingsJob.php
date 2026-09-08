<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;

class TranslateSettingsJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'settings';
    }

    public function weight(): int
    {
        return 10;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        return $service->translateSettings($this->sourceLocale, $this->targetLocale, $this->targetLanguage, $this->brandContext);
    }
}
