<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Auth;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class ForgotPasswordRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
