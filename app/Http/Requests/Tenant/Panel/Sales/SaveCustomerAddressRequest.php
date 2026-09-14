<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveCustomerAddressRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['is_default'];
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:191'],
            'full_name' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:191'],
            'is_default' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'recipient name',
            'address_line_1' => 'address line 1',
            'is_default' => 'default address',
        ];
    }
}
