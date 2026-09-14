<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class RejectReturnRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'reject_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
