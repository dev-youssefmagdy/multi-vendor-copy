<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class StoreCustomerRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['active'];
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'country_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'password' => ['required', 'string', 'min:6', 'same:password_confirmation'],
            'password_confirmation' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'country_id' => 'country',
            'city_id' => 'city',
            'password_confirmation' => 'password confirmation',
        ];
    }
}
