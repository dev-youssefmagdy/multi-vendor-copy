<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;

final class SaveFooterRequest extends TenantFormRequest
{
    public function rules(): array
    {
        $rules = [];

        foreach (app(TenantPanelRepository::class)->activeLanguages() as $language) {
            $rules["translations.{$language->code}.footer_text"] = ['nullable', 'string', 'max:1000'];
            $rules["translations.{$language->code}.footer_copyright"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
