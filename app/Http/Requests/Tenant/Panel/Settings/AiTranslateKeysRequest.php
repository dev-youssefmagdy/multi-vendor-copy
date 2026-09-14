<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class AiTranslateKeysRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'keys' => ['nullable', 'array'],
            'keys.*' => ['string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'keys' => 'translation keys',
        ];
    }
}
