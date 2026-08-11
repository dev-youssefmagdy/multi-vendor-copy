<?php

namespace App\Models\Tenant\Concerns;

use App\Traits\Translator;

trait HasTranslations
{
    use Translator;

    protected function translationModelClass(): string
    {
        return \App\Models\Tenant\Translation::class;
    }

    protected function translationLanguageModelClass(): string
    {
        return \App\Models\Tenant\Language::class;
    }
}
