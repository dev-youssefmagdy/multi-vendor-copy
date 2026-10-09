<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

/** Tenant panel: mark a refund completed manually, with an optional external reference. */
final class CompleteRefundRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:190'],
        ];
    }
}
