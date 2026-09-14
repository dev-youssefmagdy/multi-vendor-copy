<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class MarkReturnRefundedRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'refund_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
