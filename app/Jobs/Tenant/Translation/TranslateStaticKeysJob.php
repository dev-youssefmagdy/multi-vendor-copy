<?php

namespace App\Jobs\Tenant\Translation;

use App\Models\Tenant\Language;
use App\Services\Tenant\StoreTranslatorService;

class TranslateStaticKeysJob extends TranslatesStoreSection
{
    public function section(): string
    {
        return 'static_keys';
    }

    public function weight(): int
    {
        return 20;
    }

    protected function translateSection(StoreTranslatorService $service, Language $language): int
    {
        return $service->translateStaticKeys($this->sourceLocale, $this->targetLocale, $language, $this->brandContext);
    }
}
