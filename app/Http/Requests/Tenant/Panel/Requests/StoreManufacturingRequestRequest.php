<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Requests;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class StoreManufacturingRequestRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'linked_product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
