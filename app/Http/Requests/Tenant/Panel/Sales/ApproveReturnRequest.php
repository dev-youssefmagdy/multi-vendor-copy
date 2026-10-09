<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

/** Tenant panel: approve a return request, with an optional note shown to the customer. */
final class ApproveReturnRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'approve_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
