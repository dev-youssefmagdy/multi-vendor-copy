<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SubmitCountryChangeRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'requested_country_ids' => ['required', 'array', 'min:1'],
            'requested_country_ids.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'requested_country_ids' => 'target countries',
        ];
    }
}
