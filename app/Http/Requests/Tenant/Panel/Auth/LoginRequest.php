<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Auth;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class LoginRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['remember'];
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }
}
