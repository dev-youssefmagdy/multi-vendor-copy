<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Enums\OrderShippingStatus;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

final class UpdateShippingStatusRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'shipping_status' => ['required', Rule::enum(OrderShippingStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'shipping_status' => 'shipping status',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_status.required' => 'Invalid shipping status selected.',
            'shipping_status.enum' => 'Invalid shipping status selected.',
        ];
    }
}
