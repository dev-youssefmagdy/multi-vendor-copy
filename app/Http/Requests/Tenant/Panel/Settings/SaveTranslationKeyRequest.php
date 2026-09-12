<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveTranslationKeyRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'key' => 'translation key',
            'value' => 'translation value',
        ];
    }
}
