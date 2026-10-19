<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Auth;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }
}
