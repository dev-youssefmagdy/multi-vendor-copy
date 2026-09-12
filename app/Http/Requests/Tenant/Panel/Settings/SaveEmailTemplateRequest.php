<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveEmailTemplateRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'translations.*.subject' => ['nullable', 'string', 'max:255'],
            'translations.*.body' => ['nullable', 'string'],
        ];
    }

    protected function booleanFields(): array
    {
        return ['is_active'];
    }
}
