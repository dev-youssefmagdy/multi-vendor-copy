<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveGeneralSettingsRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'profit_percentage' => ['required', 'numeric', 'min:0', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'profit_percentage' => 'profit percentage',
        ];
    }
}
