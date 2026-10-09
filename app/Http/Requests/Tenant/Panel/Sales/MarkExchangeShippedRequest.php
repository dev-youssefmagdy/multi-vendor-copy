<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

/** Tenant panel: the exchange replacement was shipped (tracking number required). */
final class MarkExchangeShippedRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:190'],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_number.required' => __('Please enter the tracking number of the replacement.'),
        ];
    }
}
