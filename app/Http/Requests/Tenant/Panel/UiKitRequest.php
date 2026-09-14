<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel;

final class UiKitRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'textarea' => ['nullable', 'string'],
            'select' => ['required', 'in:one,two,three'],
        ];
    }
}
