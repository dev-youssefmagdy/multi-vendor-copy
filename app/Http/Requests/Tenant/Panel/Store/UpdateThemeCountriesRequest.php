<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class UpdateThemeCountriesRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'country_ids' => 'countries',
        ];
    }
}
