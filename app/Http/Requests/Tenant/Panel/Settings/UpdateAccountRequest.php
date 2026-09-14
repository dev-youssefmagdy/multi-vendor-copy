<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class UpdateAccountRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'shopName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }

    public function attributes(): array
    {
        return [
            'adminName' => 'admin name',
            'adminEmail' => 'admin email',
            'shopName' => 'shop name',
        ];
    }
}
